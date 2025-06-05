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
    
    // Tüm tabloları listele
    $query = "SHOW TABLES";
    $result = $conn->query($query);
    
    $tables = [];
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    
    // "price_analysis" tablosunu özellikle incele
    $price_analysis_info = null;
    if (in_array('price_analysis', $tables)) {
        // Tablo yapısını al
        $structure_query = "DESCRIBE price_analysis";
        $structure_result = $conn->query($structure_query);
        $structure = [];
        
        if ($structure_result) {
            while ($row = $structure_result->fetch_assoc()) {
                $structure[] = $row;
            }
        }
        
        // Kayıt sayısını al
        $count_query = "SELECT COUNT(*) as count FROM price_analysis";
        $count_result = $conn->query($count_query);
        $count_data = $count_result->fetch_assoc();
        $row_count = $count_data['count'];
        
        // Örnek kayıtlar
        $sample_data = [];
        if ($row_count > 0) {
            $sample_query = "SELECT * FROM price_analysis ORDER BY analysis_time DESC LIMIT 3";
            $sample_result = $conn->query($sample_query);
            
            while ($row = $sample_result->fetch_assoc()) {
                $sample_data[] = $row;
            }
        }
        
        $price_analysis_info = [
            'structure' => $structure,
            'row_count' => $row_count,
            'sample_data' => $sample_data
        ];
    }
    
    // "discovered" veya "coin" içeren tabloları ara
    $potential_tables = [];
    foreach ($tables as $table) {
        if (strpos($table, 'discover') !== false || 
            strpos($table, 'coin') !== false || 
            strpos($table, 'potential') !== false) {
            
            // Bu tablolardaki kayıt sayısını al
            $count_query = "SELECT COUNT(*) as count FROM `$table`";
            $count_result = $conn->query($count_query);
            $count_data = $count_result->fetch_assoc();
            $row_count = $count_data['count'];
            
            // Örnek kayıtlar
            $sample_data = [];
            if ($row_count > 0) {
                $sample_query = "SELECT * FROM `$table` LIMIT 2";
                $sample_result = $conn->query($sample_query);
                
                while ($row = $sample_result->fetch_assoc()) {
                    $sample_data[] = $row;
                }
            }
            
            $potential_tables[] = [
                'table_name' => $table,
                'row_count' => $row_count,
                'sample_data' => $sample_data
            ];
        }
    }
    
    $response = [
        'success' => true,
        'all_tables' => $tables,
        'price_analysis_info' => $price_analysis_info,
        'potential_coin_tables' => $potential_tables
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