<?php
// get_balance.php - API Bakiye bilgilerini JSON olarak döndüren servis

// TRADING_BOT sabitini tanımla - API isteklerinin tüm dosyaları içe aktarabilmesi için
define('TRADING_BOT', true);

// Set JSON content type
header('Content-Type: application/json');

try {
    // Log oluştur
    error_log("get_balance.php - Bakiye bilgisi çekiliyor");
    
    // Öncelikle Binance toplam bakiye dosyasını kontrol et
    // Ana klasör yolu
    $root_dir = dirname(dirname(dirname(__FILE__))); // Trading bot ana dizini
    
    // Olası dosya yolları
    $binance_balances_paths = [
        __DIR__ . '/binance_total_balances.json', // Doğrudan web/api/ klasöründe
        $root_dir . '/web/api/binance_total_balances.json', // Tam yol ile
        $root_dir . '/binance_total_balances.json' // Ana dizinde
    ];
    
    $binance_balances_file = null;
    $balances_data = null;
    
    // Dosyayı bul
    foreach ($binance_balances_paths as $path) {
        error_log("get_balance.php - Dosya kontrol ediliyor: " . $path);
        if (file_exists($path)) {
            $binance_balances_file = $path;
            error_log("get_balance.php - Bakiye dosyası bulundu: " . $path);
            break;
        }
    }
    
    // Dosya bulundu mu kontrol et
    if ($binance_balances_file) {
        $file_content = file_get_contents($binance_balances_file);
        $balances_data = json_decode($file_content, true);
        error_log("get_balance.php - Dosya içeriği: " . substr($file_content, 0, 100) . "...");
        
        $file_age = time() - filemtime($binance_balances_file);
        
        // Dosya 1 saatten daha yeni ise, kullan
        if ($file_age < 3600 && isset($balances_data['timestamp'])) {
            error_log("get_balance.php - Geçerli bakiye dosyası kullanılıyor (yaş: {$file_age} saniye)");
            
            // USDT cinsinden toplam bakiyeyi hesapla
            $total_balance = $balances_data['total_spot'] + 
                           $balances_data['total_margin'] + 
                           $balances_data['total_isolated'] + 
                           $balances_data['total_futures'];
            
            // Yanıt ver
            echo json_encode([
                "success" => true,
                "data" => [
                    "total_balance" => $total_balance,
                    "usdt_balance" => $balances_data['total_spot'],
                    "spot_balance" => $balances_data['total_spot'],
                    "margin_balance" => $balances_data['total_margin'],
                    "isolated_margin_balance" => $balances_data['total_isolated'],
                    "futures_balance" => $balances_data['total_futures'],
                    "wallet_btc_value" => $balances_data['wallet_btc_value'],
                    "source" => "binance_api",
                    "file_path" => $binance_balances_file
                ],
                "timestamp" => $balances_data['timestamp'],
                "age_seconds" => $file_age
            ]);
            exit;
        } else {
            error_log("get_balance.php: Binance bakiye dosyası eski veya geçersiz. Dosya yaşı: " . $file_age . " saniye.");
        }
    } else {
        error_log("get_balance.php: Binance bakiye dosyası bulunamadı");
    }
    
    // Eğer Binance dosyasından bakiye alınamadıysa, bot_api'yi dene
    error_log("get_balance.php - Bot API'den bakiye alınıyor");
    require_once 'bot_api.php';
    
    // Bot API'sini başlat
    $bot_api = new BotAPI();
    
    // Bot API'den bakiyeyi al
    $bot_balance = $bot_api->getBalance();
    error_log("get_balance.php - Bot API'den alınan bakiye: " . $bot_balance);
    
    // Eğer MySQL veritabanı balances tablosu boşsa veya yoksa, bakiye çekemeye bilir
    // Bu durumda fetch_binance_balances.py script'ini çalıştır
    if ($bot_balance <= 0) {
        error_log("get_balance.php - Bot API'den bakiye alınamadı, fetch_binance_balances.py çalıştırılıyor");
        
        try {
            $python_path = "python"; // Windows için python3 yerine python
            $script_path = $root_dir . "/fetch_binance_balances.py";
            
            if (file_exists($script_path)) {
                error_log("get_balance.php - Script çalıştırılıyor: " . $script_path);
                $output = [];
                exec("$python_path \"$script_path\" 2>&1", $output, $return_var);
                
                if ($return_var === 0) {
                    error_log("get_balance.php - Script başarıyla çalıştırıldı: " . implode("\n", $output));
                    
                    // Yeni oluşturulan dosyayı kontrol et
                    foreach ($binance_balances_paths as $path) {
                        if (file_exists($path) && filemtime($path) > time() - 30) { // Son 30 saniyede oluşturulduysa
                            $binance_balances_file = $path;
                            $balances_data = json_decode(file_get_contents($binance_balances_file), true);
                            
                            if ($balances_data && isset($balances_data['total_usdt'])) {
                                $total_balance = $balances_data['total_usdt'];
                                error_log("get_balance.php - Script sonrası bakiye bulundu: " . $total_balance);
                                
                                echo json_encode([
                                    "success" => true,
                                    "data" => [
                                        "total_balance" => $total_balance,
                                        "usdt_balance" => $balances_data['total_spot'],
                                        "spot_balance" => $balances_data['total_spot'],
                                        "margin_balance" => $balances_data['total_margin'],
                                        "isolated_margin_balance" => $balances_data['total_isolated'],
                                        "futures_balance" => $balances_data['total_futures'],
                                        "wallet_btc_value" => $balances_data['wallet_btc_value'],
                                        "source" => "binance_api_fresh"
                                    ],
                                    "timestamp" => $balances_data['timestamp']
                                ]);
                                exit;
                            }
                        }
                    }
                } else {
                    error_log("get_balance.php - Script çalıştırılırken hata oluştu: " . implode("\n", $output));
                }
            } else {
                error_log("get_balance.php - Script bulunamadı: " . $script_path);
            }
        } catch (Exception $e) {
            error_log("get_balance.php - Script çalıştırılırken istisna: " . $e->getMessage());
        }
    }
    
    // Tüm denemeler başarısız olduğunda, bot_api'den alınan bakiyeyi kullan
    // (Eğer o da 0 ise varsayılan değeri kullanacaktır)
    echo json_encode([
        "success" => true,
        "data" => [
            "total_balance" => $bot_balance,
            "usdt_balance" => $bot_balance,
            "source" => "bot_api"
        ],
        "timestamp" => time()
    ]);
    
} catch (Exception $e) {
    // Hata durumunda
    error_log("get_balance.php: Hata oluştu: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => "API Error: " . $e->getMessage(),
        "timestamp" => time()
    ]);
}
?>
