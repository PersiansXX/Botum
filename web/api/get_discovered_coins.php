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

// Veritabanı bağlantısı
require_once 'bot_api.php';
$bot_api = new BotAPI();

try {
    // Keşfedilen coinleri veritabanından al
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
    
    // Sorgu çalıştırmadan önce tablonun varlığını kontrol et
    $table_check = "SHOW TABLES LIKE 'discovered_coins'";
    $result = $conn->query($table_check);
    
    if ($result->num_rows == 0) {
        // Tablo yoksa oluştur
        $create_table_query = "
        CREATE TABLE IF NOT EXISTS discovered_coins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(20) NOT NULL,
            discovery_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            price DECIMAL(20, 8) NOT NULL,
            volume_usd DECIMAL(20, 2),
            price_change_pct DECIMAL(10, 2),
            buy_signals INT,
            sell_signals INT,
            trade_signal VARCHAR(10),
            is_active TINYINT(1) DEFAULT 1,
            notes TEXT,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table_query) === TRUE) {
            echo json_encode([
                'success' => true,
                'message' => 'discovered_coins tablosu oluşturuldu, ancak henüz veri yok.',
                'data' => []
            ]);
            exit;
        } else {
            throw new Exception("Tablo oluşturma hatası: " . $conn->error);
        }
    }
    
    // discovered_coins tablosundan verileri çek (en son keşfedilenler önce)
    $query = "SELECT * FROM discovered_coins WHERE is_active = 1 ORDER BY discovery_time DESC LIMIT 100";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Sorgu hatası: " . $conn->error);
    }
    
    $coins = [];
    $row_count = $result->num_rows;
    
    if ($row_count > 0) {
        while ($row = $result->fetch_assoc()) {
            // Her bir coinin analiz verilerini çek
            $coin_symbol = $row['symbol'];
            
            // Önce price_analysis tablosunu kontrol et
            $table_check = "SHOW TABLES LIKE 'price_analysis'";
            $table_result = $conn->query($table_check);
            $has_analysis_table = ($table_result->num_rows > 0);
            
            $indicators = [];
            
            if ($has_analysis_table) {
                $analysis_query = "SELECT * FROM price_analysis WHERE symbol = ? ORDER BY analysis_time DESC LIMIT 1";
                $stmt = $conn->prepare($analysis_query);
                $stmt->bind_param("s", $coin_symbol);
                $stmt->execute();
                $analysis_result = $stmt->get_result();
                $analysis_data = $analysis_result->fetch_assoc();
                
                // İndikatör verilerini oluştur
                if ($analysis_data) {
                    $indicators = [
                        'rsi' => [
                            'value' => (float) $analysis_data['rsi'],
                            'signal' => $analysis_data['rsi'] <= 30 ? 'BUY' : ($analysis_data['rsi'] >= 70 ? 'SELL' : 'NEUTRAL')
                        ],
                        'macd' => [
                            'value' => (float) $analysis_data['macd'],
                            'signal_line' => (float) $analysis_data['macd_signal'],
                            'signal' => $analysis_data['macd'] > $analysis_data['macd_signal'] ? 'BUY' : 'SELL'
                        ],
                        'bollinger' => [
                            'upper' => (float) $analysis_data['bollinger_upper'],
                            'middle' => (float) $analysis_data['bollinger_middle'],
                            'lower' => (float) $analysis_data['bollinger_lower']
                        ],
                        'moving_averages' => [
                            'ma20' => (float) $analysis_data['ma20'],
                            'ma50' => (float) $analysis_data['ma50'],
                            'ma100' => (float) $analysis_data['ma100'],
                            'ma200' => (float) $analysis_data['ma200']
                        ]
                    ];
                }
            }
            
            // Keşif ve analiz verilerini birleştir
            $coin_data = [
                'symbol' => $row['symbol'],
                'discovery_time' => $row['discovery_time'],
                'last_price' => (float) $row['price'],
                'volume_usd' => (float) $row['volume_usd'],
                'price_change_pct' => (float) $row['price_change_pct'],
                'buy_signals' => (int) $row['buy_signals'],
                'sell_signals' => (int) $row['sell_signals'],
                'trade_signal' => $row['trade_signal'],
                'note' => $row['notes'],
                'last_updated' => $row['last_updated'],
                'indicators' => $indicators
            ];
            
            $coins[] = $coin_data;
        }
    }
    
    // Sonuçlar hakkında bilgi çıktısı
    $response = [
        'success' => true,
        'rows_found' => $row_count,
        'data' => $coins
    ];
    
    $conn->close();
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>