<?php
/**
 * API Bakiye Testi
 * Bu dosya, bakiye API'sinin düzgün çalışıp çalışmadığını test etmek için kullanılır.
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Başlık ayarları
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>API Bakiye Test Sayfası</h1>";
echo "<pre>";

try {
    echo "1. Bot API'yi yüklüyor...\n";
    require_once 'api/bot_api.php';
    $bot_api = new BotAPI();
    echo "✓ Bot API başarıyla yüklendi\n\n";
    
    echo "2. Bot API bakiye bilgisi alınıyor...\n";
    $bot_balance = $bot_api->getBalance();
    echo "✓ Bot API bakiye: " . $bot_balance . "\n\n";
    
    echo "3. Binance API'yi yüklüyor...\n";
    require_once 'api/binance_api.php';
    $binance_api = new BinanceAPI();
    $binance_api->setDebug(true); // Hata ayıklama modunu aktifleştir
    echo "✓ Binance API başarıyla yüklendi\n\n";
    
    echo "4. API keyleri kontrol ediliyor...\n";
    $config_file = dirname(__DIR__) . "/config/api_keys.json";
    echo "Config dosya yolu: " . $config_file . "\n";
    
    $api_key = '';
    $api_secret = '';
    
    if (file_exists($config_file)) {
        echo "✓ Config dosyası bulundu\n";
        $config = json_decode(file_get_contents($config_file), true);
        if (isset($config['binance']) && isset($config['binance']['api_key']) && isset($config['binance']['api_secret'])) {
            $api_key = $config['binance']['api_key'];
            $api_secret = $config['binance']['api_secret'];
            echo "✓ Config dosyasında API anahtarları bulundu\n";
            echo "API Key: " . substr($api_key, 0, 5) . '...' . substr($api_key, -5) . "\n";
            echo "API Secret: " . substr($api_secret, 0, 5) . '...' . substr($api_secret, -5) . "\n\n";
        } else {
            echo "✗ Config dosyasında API anahtarları bulunamadı\n\n";
        }
    } else {
        echo "✗ Config dosyası bulunamadı: " . $config_file . "\n\n";
    }
    
    echo "5. Binance API bakiye bilgisi alınıyor...\n";
    $balance_info = $binance_api->getAccountBalance($api_key, $api_secret);
    
    if (isset($balance_info['error'])) {
        echo "✗ Binance API bakiye hatası: " . $balance_info['error'] . "\n\n";
    } else {
        $total_balance = isset($balance_info['totalBalance']) ? $balance_info['totalBalance'] : 0;
        $balances = isset($balance_info['balances']) ? $balance_info['balances'] : [];
        
        echo "✓ Toplam bakiye: " . $total_balance . "\n";
        echo "✓ Varlık sayısı: " . count($balances) . "\n\n";
        
        echo "6. En değerli varlıklar:\n";
        $i = 0;
        foreach ($balances as $balance) {
            if ($i >= 5) break;
            echo "   - " . $balance['asset'] . ": " . $balance['total'] . " (" . $balance['value_usdt'] . " USDT)\n";
            $i++;
        }
        
        // Bakiyeleri daha detaylı inceleme ekleniyor
        echo "\n6.1. USDT Bakiye Detayları:\n";
        $usdt_balance = 0;
        foreach ($balances as $balance) {
            if ($balance['asset'] === 'USDT') {
                $usdt_balance = $balance['total'];
                echo "   - USDT bakiyesi bulundu: " . $usdt_balance . " USDT\n";
                break;
            }
        }
        if ($usdt_balance == 0) {
            echo "   - USDT bakiyesi bulunamadı veya sıfır\n";
        }
    }
    
    echo "\n7. WebSocket API formatında bakiye bilgisi test ediliyor...\n";
    
    // USDT bakiyesini hesapla
    $calculated_usdt_balance = 0;
    if (isset($balance_info['balances'])) {
        foreach ($balance_info['balances'] as $balance) {
            if ($balance['asset'] === 'USDT') {
                $calculated_usdt_balance = $balance['total'];
                break;
            }
        }
    }
    
    $response_data = [
        "success" => true,
        "data" => [
            "total_balance" => isset($balance_info['totalBalance']) ? $balance_info['totalBalance'] : $bot_balance,
            "usdt_balance" => $calculated_usdt_balance, // USDT bakiyesini kullan
            "balances" => isset($balance_info['balances']) ? $balance_info['balances'] : [],
            "source" => (isset($balance_info['totalBalance']) && $balance_info['totalBalance'] > 0) ? "binance_api" : "bot_api"
        ],
        "timestamp" => time()
    ];
    
    echo "   - Toplam bakiye: " . $response_data['data']['total_balance'] . " USDT\n";
    echo "   - USDT bakiye: " . $response_data['data']['usdt_balance'] . " USDT\n";
    echo "   - Veri kaynağı: " . $response_data['data']['source'] . "\n\n";
    
    // JSON formatını test et
    $json_string = json_encode($response_data);
    if ($json_string === false) {
        echo "✗ JSON formatına dönüştürme hatası: " . json_last_error_msg() . "\n";
    } else {
        echo "✓ JSON formatı geçerli\n";
        echo "✓ JSON içeriği örneği (kısmi):\n" . substr($json_string, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "✗ HATA: " . $e->getMessage() . "\n";
    echo "Detay: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Ana sayfaya dön</a></p>";
