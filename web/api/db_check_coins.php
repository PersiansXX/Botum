<?php
// Hata ayıklama modunu açık tut
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Giriş yapılmışsa kontrol et
session_start();
if (!isset($_SESSION['user_id'])) {
    // API direkt çağrıldığında JSON yanıtı döndür
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim!'
    ]);
    exit;
}

header('Content-Type: application/json');

try {
    // Veritabanı bağlantısı
    $db_config = [
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'Efsane44.',
        'database' => 'trading_bot_db'
    ];

    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database']);
    if ($conn->connect_error) {
        throw new Exception("Veritabanı bağlantı hatası: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // discovered_coins tablosunu kontrol et
    $table_check = "SHOW TABLES LIKE 'discovered_coins'";
    $result = $conn->query($table_check);
    $has_table = $result->num_rows > 0;
    
    $row_count = 0;
    $sample_data = [];
    
    if ($has_table) {
        // Kayıt sayısını kontrol et
        $count_query = "SELECT COUNT(*) as count FROM discovered_coins";
        $count_result = $conn->query($count_query);
        $count_data = $count_result->fetch_assoc();
        $row_count = $count_data['count'];
        
        // Örnek kayıtları al
        if ($row_count > 0) {
            $sample_query = "SELECT * FROM discovered_coins ORDER BY discovery_time DESC LIMIT 5";
            $sample_result = $conn->query($sample_query);
            
            while ($row = $sample_result->fetch_assoc()) {
                $sample_data[] = $row;
            }
        }
    }
    
    // Bot log dosyalarını kontrol et
    $bot_log_path = '../../bot/bot.log';
    $recent_logs = [];
    
    if (file_exists($bot_log_path)) {
        $log_content = file_get_contents($bot_log_path);
        // "Keşfedilen" kelimesini içeren son 10 satırı al
        $log_lines = explode("\n", $log_content);
        $discovery_logs = array_filter($log_lines, function($line) {
            return strpos(strtolower($line), "keşfedil") !== false || 
                  strpos($line, "discover") !== false;
        });
        $recent_logs = array_slice($discovery_logs, -10);
    }
    
    $response = [
        'success' => true,
        'has_table' => $has_table,
        'row_count' => $row_count,
        'sample_data' => $sample_data,
        'discovery_logs' => $recent_logs
    ];
    
    $conn->close();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>