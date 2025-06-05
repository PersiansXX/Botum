<?php
// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('TRADING_BOT', true);
// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim!'
    ]);
    exit;
}

header('Content-Type: application/json');

// Parametre kontrolü
if (!isset($_POST['symbol']) || empty($_POST['symbol'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Coin sembolü belirtilmedi'
    ]);
    exit;
}

$symbol = $_POST['symbol'];

// Veritabanı bağlantı bilgileri
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'Efsane44.',
    'database' => 'trading_bot_db'
];

try {
    // Veritabanı bağlantısı
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database']);
    if ($conn->connect_error) {
        throw new Exception("Veritabanı bağlantı hatası: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Önce active_coins tablosunu kontrol et
    $table_check = "SHOW TABLES LIKE 'active_coins'";
    $result = $conn->query($table_check);
    
    if ($result->num_rows == 0) {
        // Tablo yoksa oluştur
        $create_table_query = "
        CREATE TABLE IF NOT EXISTS active_coins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(20) NOT NULL,
            added_by VARCHAR(50) DEFAULT 'system',
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (symbol)
        )";
        
        if (!$conn->query($create_table_query)) {
            throw new Exception("Tablo oluşturma hatası: " . $conn->error);
        }
    } else {
        // Tablo var, sütun var mı kontrol et
        $column_check = "SHOW COLUMNS FROM active_coins LIKE 'added_by'";
        $column_result = $conn->query($column_check);
        
        if ($column_result->num_rows == 0) {
            // Sütun yoksa ekle
            $alter_query = "ALTER TABLE active_coins 
                            ADD COLUMN added_by VARCHAR(50) DEFAULT 'system' AFTER symbol,
                            ADD COLUMN added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER added_by";
            
            if (!$conn->query($alter_query)) {
                throw new Exception("Sütun ekleme hatası: " . $conn->error);
            }
        }
    }
    
    // Coin zaten aktif listede mi kontrol et
    $check_query = "SELECT id FROM active_coins WHERE symbol = ?";
    $check_stmt = $conn->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception("Sorgu hazırlama hatası: " . $conn->error);
    }
    
    $check_stmt->bind_param("s", $symbol);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Coin zaten aktif listede, sadece güncelle
        $update_query = "UPDATE active_coins SET last_updated = NOW() WHERE symbol = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            throw new Exception("Sorgu hazırlama hatası: " . $conn->error);
        }
        
        $update_stmt->bind_param("s", $symbol);
        if (!$update_stmt->execute()) {
            throw new Exception("Güncelleme hatası: " . $update_stmt->error);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Coin zaten aktif listede, güncellendi: ' . $symbol,
            'is_new' => false
        ]);
    } else {
        // Coini aktif listeye ekle
        $insert_query = "INSERT INTO active_coins (symbol, added_by) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        if (!$insert_stmt) {
            throw new Exception("Sorgu hazırlama hatası: " . $conn->error);
        }
        
        $added_by = $_SESSION['user_id'] ?? 'system';
        $insert_stmt->bind_param("ss", $symbol, $added_by);
        if (!$insert_stmt->execute()) {
            throw new Exception("Ekleme hatası: " . $insert_stmt->error);
        }
        
        // discovered_coins tablosunu da güncelle
        $update_discovered_query = "UPDATE discovered_coins SET is_active = 1 WHERE symbol = ?";
        $update_discovered_stmt = $conn->prepare($update_discovered_query);
        if ($update_discovered_stmt) {
            $update_discovered_stmt->bind_param("s", $symbol);
            $update_discovered_stmt->execute();
            // Hata kontrolü yapma, hata olsa bile devam et
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Coin başarıyla aktif listeye eklendi: ' . $symbol,
            'is_new' => true
        ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>