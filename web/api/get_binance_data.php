<?php
/**
 * Binance API'sinden veri çeken PHP dosyası
 * Gerçek zamanlı fiyat ve işlem verilerini sağlar
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 2) . '/bot_error.log');

// CORS ayarları (gerekirse)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Binance API sınıfını dahil et
require_once __DIR__ . '/binance_api.php';

// İstek türünü al
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Binance API nesnesi oluştur
$binanceAPI = new BinanceAPI();
$binanceAPI->setDebug(true); // Hata ayıklama modunu aktif et

try {
    $response = [];
    
    switch ($action) {
        case 'ticker':
            // Belirli bir coin için ticker bilgisi
            $symbol = isset($_GET['symbol']) ? $_GET['symbol'] : 'BTCUSDT';
            $result = $binanceAPI->getTicker($symbol);
            $response = [
                'success' => true,
                'data' => $result
            ];
            break;
            
        case 'all_tickers':
            // Tüm ticker'lar
            $result = $binanceAPI->getAllTickers();
            $response = [
                'success' => true,
                'data' => $result
            ];
            break;
            
        case 'all_stats':
            // İhtiyaç duyulan tüm coin verileri
            $symbols = isset($_GET['symbols']) ? explode(',', $_GET['symbols']) : null;
            
            // Semboller belirtilmemişse standart sembolleri kullan
            if (!$symbols) {
                $symbols = [
                    'BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'ADAUSDT', 
                    'DOGEUSDT', 'XRPUSDT', 'DOTUSDT', 'MATICUSDT', 'LINKUSDT'
                ];
            }
            
            // Her sembol için veri çek
            $coinData = [];
            foreach ($symbols as $symbol) {
                try {
                    $ticker = $binanceAPI->getTicker($symbol);
                    $klines = $binanceAPI->getKlines($symbol, '1d', 1);
                    
                    // 24 saatlik high ve low değerleri
                    $high24h = isset($klines[0][2]) ? $klines[0][2] : null;
                    $low24h = isset($klines[0][3]) ? $klines[0][3] : null;
                    
                    // Veriyi birleştir
                    $coinData[$symbol] = [
                        'symbol' => $symbol,
                        'lastPrice' => $ticker['lastPrice'],
                        'priceChangePercent' => $ticker['priceChangePercent'],
                        'priceChange' => $ticker['priceChange'],
                        'highPrice' => $ticker['highPrice'] ?? $high24h,
                        'lowPrice' => $ticker['lowPrice'] ?? $low24h,
                        'volume' => $ticker['volume'],
                        'quoteVolume' => $ticker['quoteVolume'],
                        'lastUpdateTime' => date('Y-m-d H:i:s')
                    ];
                    
                } catch (Exception $e) {
                    error_log("Symbol $symbol error: " . $e->getMessage());
                    // Hata oluşturan coin'i atla
                    continue;
                }
            }
            
            $response = [
                'success' => true,
                'data' => $coinData,
                'timestamp' => time()
            ];
            break;
            
        case 'klines':
            // Mum grafik verileri
            $symbol = isset($_GET['symbol']) ? $_GET['symbol'] : 'BTCUSDT';
            $interval = isset($_GET['interval']) ? $_GET['interval'] : '1h';
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            
            $result = $binanceAPI->getKlines($symbol, $interval, $limit);
            $response = [
                'success' => true,
                'data' => $result
            ];
            break;
            
        default:
            $response = [
                'success' => false,
                'error' => 'Invalid action specified'
            ];
            break;
    }
    
} catch (Exception $e) {
    error_log("Binance API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// JSON formatında yanıt gönder
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>