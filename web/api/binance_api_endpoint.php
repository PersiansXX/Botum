<?php
/**
 * Binance API yardımcı endpoint
 * Coins.php için doğrudan Binance API üzerinden verileri çeker
 */
 
define('TRADING_BOT', true);

// Hata raporlama
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", dirname(__DIR__, 2) . "/bot_error.log");

// CORS ayarları
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Gerekli dosyaları dahil et
require_once __DIR__ . "/binance_api.php";

// Action parametresini kontrol et
$action = isset($_GET["action"]) ? $_GET["action"] : "";

// Binance API sınıfını başlat
$binanceAPI = new BinanceAPI();
$binanceAPI->setDebug(true);

try {
    $response = [];
    
    if ($action == "get_prices") {
        // Sembol listesini al
        $symbols = isset($_GET["symbols"]) ? explode(",", $_GET["symbols"]) : [];
        
        // Sonuç dizisi
        $result = [];
        
        // Her sembol için veri çek
        if (!empty($symbols)) {
            foreach ($symbols as $symbol) {
                try {
                    $ticker = $binanceAPI->getTicker($symbol);
                    if (!isset($ticker["error"])) {
                        $result[$symbol] = $ticker;
                    } else {
                        error_log("Error getting ticker for $symbol: " . $ticker["error"]);
                    }
                } catch (Exception $e) {
                    error_log("Error fetching $symbol: " . $e->getMessage());
                }
            }
        } else {
            // Sembol belirtilmemişse tüm tickerları çek
            try {
                $allTickers = $binanceAPI->getAllTickers();
                foreach ($allTickers as $ticker) {
                    if (isset($ticker["symbol"])) {
                        $symbol = $ticker["symbol"];
                        $result[$symbol] = $ticker;
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching all tickers: " . $e->getMessage());
            }
        }
        
        $response = [
            "success" => true,
            "data" => $result,
            "timestamp" => time()
        ];
    } else {
        $response = [
            "success" => false,
            "error" => "Invalid action parameter"
        ];
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    $response = [
        "success" => false,
        "error" => $e->getMessage()
    ];
}

// JSON cevabı gönder
echo json_encode($response, JSON_PRETTY_PRINT);
?>