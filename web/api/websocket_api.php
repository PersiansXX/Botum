<?php
/**
 * WebSocket API endpoint
 * Web paneline WebSocket Manager'dan gelen verileri aktarır
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", dirname(__DIR__, 2) . "/bot_error.log");

// CORS ayarları
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Bot klasörüne Python komutu için erişim sağla
$botDir = dirname(__DIR__, 2) . "/bot";
$pythonCmd = "python"; // Sisteme göre "python3" olabilir

// Action parametresini kontrol et
$action = isset($_GET["action"]) ? $_GET["action"] : "";
$symbol = isset($_GET["symbol"]) ? $_GET["symbol"] : "";
$type = isset($_GET["type"]) ? $_GET["type"] : "ticker"; // varsayılan olarak ticker
$interval = isset($_GET["interval"]) ? $_GET["interval"] : "1m"; // kline için varsayılan interval
$forceRefresh = isset($_GET["refresh"]) && $_GET["refresh"] === "1"; // Zorla yenileme parametresi

$response = ["success" => false, "error" => "İşlem başarısız"];

try {
    switch ($action) {
        case "get_account_balance":
            // Bot API'den bakiye bilgisini al
            require_once(dirname(__DIR__) . "/api/bot_api.php");
            $bot_api = new BotAPI();
            
            // Bot API üzerinden bakiye bilgisini al (yedek olarak)
            $bot_balance = $bot_api->getBalance();
              
            // Binance API ile direkt bağlantı kur
            require_once(dirname(__DIR__) . "/api/binance_api.php");
            $binance_api = new BinanceAPI();
            $binance_api->setDebug(true); // Hata ayıklama modunu açık tut
              
            // API anahtarlarını sadece veritabanından çekeceğiz
            $api_key = '';
            $api_secret = '';
            
            // Veritabanı bağlantısı
            require_once dirname(__FILE__) . '/../includes/db_connect.php';
            
            // Veritabanından API anahtarlarını çek
            $api_query = "SELECT * FROM api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1";
            $api_result = mysqli_query($conn, $api_query);
            
            if ($api_result && mysqli_num_rows($api_result) > 0) {
                $api_row = mysqli_fetch_assoc($api_result);
                $api_key = $api_row['api_key'];
                $api_secret = $api_row['api_secret'];
            } else {
                throw new Exception("API anahtarları veritabanında bulunamadı");
            }
            
            // Spot bakiye bilgisini al (doğrudan Binance'den gerçek zamanlı çekiliyor)
            $spot_balance_info = $binance_api->getAccountBalance($api_key, $api_secret);
            $spot_total_balance = isset($spot_balance_info['totalBalance']) ? $spot_balance_info['totalBalance'] : $bot_balance;
            $spot_balances = isset($spot_balance_info['balances']) ? $spot_balance_info['balances'] : [];
            $usdt_balance = 0;
            
            // USDT bakiyesini bul
            foreach ($spot_balances as $balance) {
                if ($balance['asset'] === 'USDT') {
                    $usdt_balance = $balance['free'];
                    break;
                }
            }
            
            // Futures bakiye bilgisini al (doğrudan Binance'den gerçek zamanlı çekiliyor)
            $futures_balance_info = $binance_api->getFuturesBalance($api_key, $api_secret);
            $futures_total_balance = isset($futures_balance_info['totalBalance']) ? $futures_balance_info['totalBalance'] : 0;
            $futures_balances = isset($futures_balance_info['balances']) ? $futures_balance_info['balances'] : [];
            
            // Margin bakiye bilgisini al (doğrudan Binance'den gerçek zamanlı çekiliyor)
            $margin_balance_info = $binance_api->getMarginBalance($api_key, $api_secret);
            $margin_total_balance = isset($margin_balance_info['totalBalance']) ? $margin_balance_info['totalBalance'] : 0;
            $margin_balances = isset($margin_balance_info['balances']) ? $margin_balance_info['balances'] : [];
            
            // Toplam bakiyeyi hesapla (spot + futures + margin)
            $total_balance = $spot_total_balance + $futures_total_balance + $margin_total_balance;
            
            // Eğer Binance API'den bakiye alınamadıysa, Bot API'den alınan bakiyeyi kullan
            if ($total_balance <= 0) {
                $total_balance = $bot_balance;
                $usdt_balance = $bot_balance; // USDT bakiyesi de aynı olsun
            }
            
            // Başarı durumunda tam response
            $response = [
                "success" => true,
                "data" => [
                    "total_balance" => $total_balance,
                    "usdt_balance" => $usdt_balance,
                    "spot" => [
                        "total_balance" => $spot_total_balance,
                        "balances" => $spot_balances
                    ],
                    "futures" => [
                        "total_balance" => $futures_total_balance,
                        "balances" => $futures_balances
                    ],
                    "margin" => [
                        "total_balance" => $margin_total_balance,
                        "balances" => $margin_balances
                    ],
                    "source" => "binance_api_direct",
                    "connection" => "realtime"
                ],
                "timestamp" => time()
            ];
            
            // İsteğe bağlı olarak cache'e kaydet (sonraki istekler için performans)
            $cache_file = dirname(__DIR__, 2) . "/api/binance_total_balances.json";
            $cache_data = [
                "total_spot" => $spot_total_balance,
                "total_margin" => $margin_total_balance,
                "total_isolated" => 0, // Isolated margin hesaplar için ayrı bir API çağrısı gerekebilir
                "total_futures" => $futures_total_balance,
                "wallet_btc_value" => 0, // BTC değeri için ayrı bir API çağrısı gerekebilir
                "timestamp" => time()
            ];
            
            @file_put_contents($cache_file, json_encode($cache_data, JSON_PRETTY_PRINT));
            break;
            
        case "get_websocket_data":
            if (empty($symbol)) {
                throw new Exception("Symbol parametresi gerekli");
            }
            
            // Veri tipine göre uygun Python komutu oluştur
            $cmd = "cd $botDir && $pythonCmd -c \"from websocket_manager import websocket_manager; ";
            
            switch ($type) {
                case "ticker":
                    $cmd .= "import json; print(json.dumps(websocket_manager.get_ticker('$symbol')))\"";
                    break;
                case "kline":
                    $cmd .= "import json; print(json.dumps(websocket_manager.get_kline('$symbol', '$interval')))\"";
                    break;
                case "depth":
                    $cmd .= "import json; print(json.dumps(websocket_manager.get_depth('$symbol')))\"";
                    break;
                case "trades":
                    $cmd .= "import json; print(json.dumps(websocket_manager.get_trades('$symbol')))\"";
                    break;
                case "bookticker":
                    $cmd .= "import json; print(json.dumps(websocket_manager.get_book_ticker('$symbol')))\"";
                    break;
                default:
                    throw new Exception("Geçersiz veri tipi: $type");
            }
            
            // Python komutunu çalıştır
            $result = shell_exec($cmd);
            $data = json_decode($result, true);
            
            if ($data === null) {
                throw new Exception("WebSocket verileri alınamadı: " . json_last_error_msg());
            }
            
            $response = [
                "success" => true,
                "data" => $data,
                "timestamp" => time(),
                "type" => $type,
                "symbol" => $symbol
            ];
            
            if ($type == "kline") {
                $response["interval"] = $interval;
            }
            break;
            
        case "start_websocket":
            if (empty($symbol)) {
                throw new Exception("Symbol parametresi gerekli");
            }
            
            // WebSocket bağlantısını başlat
            $cmd = "cd $botDir && $pythonCmd -c \"from websocket_manager import websocket_manager; ";
            
            if ($type == "kline") {
                $cmd .= "websocket_manager.start_symbol_socket('$symbol', '$type', interval='$interval'); print('OK')\"";
            } else {
                $cmd .= "websocket_manager.start_symbol_socket('$symbol', '$type'); print('OK')\"";
            }
            
            $result = trim(shell_exec($cmd));
            
            if ($result !== "OK") {
                throw new Exception("WebSocket bağlantısı başlatılamadı");
            }
            
            $response = [
                "success" => true,
                "message" => "$symbol için $type WebSocket bağlantısı başlatıldı",
                "symbol" => $symbol,
                "type" => $type
            ];
            
            if ($type == "kline") {
                $response["interval"] = $interval;
            }
            break;
            
        case "list_active_sockets":
            // Aktif WebSocket bağlantılarını listele
            $cmd = "cd $botDir && $pythonCmd -c \"from websocket_manager import websocket_manager; import json; print(json.dumps(websocket_manager.list_active_sockets()))\"";
            $result = shell_exec($cmd);
            $data = json_decode($result, true);
            
            if ($data === null) {
                throw new Exception("Aktif soketler alınamadı: " . json_last_error_msg());
            }
            
            $response = [
                "success" => true,
                "data" => $data,
                "timestamp" => time()
            ];
            break;
            
        default:
            throw new Exception("Geçersiz action parametresi");
    }
} catch (Exception $e) {
    error_log("WebSocket API Error: " . $e->getMessage());
    $response = [
        "success" => false,
        "error" => $e->getMessage(),
        "trace" => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
    ];
}

// JSON cevabı gönder
echo json_encode($response, JSON_PRETTY_PRINT);
?>