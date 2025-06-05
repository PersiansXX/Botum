<?php
// WebSocket API aracılığıyla gerçek zamanlı kripto fiyat güncellemeleri sağlar
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../includes/db_connect.php';

// İsteği kontrol et
$symbol = isset($_GET['symbol']) ? trim($_GET['symbol']) : null;
$action = isset($_GET['action']) ? trim($_GET['action']) : 'get';

// Binance API URL'leri
$binance_ticker_url = "https://api.binance.com/api/v3/ticker/price";
$binance_24h_url = "https://api.binance.com/api/v3/ticker/24hr";

// Sonuç dizisi
$result = [];

try {
    if ($action === 'get') {
        // Tek bir sembol için veri getir
        if ($symbol) {
            // Binance API'den anlık fiyat bilgisi al
            $ticker_url = $binance_ticker_url . "?symbol=" . strtoupper(str_replace('/', '', $symbol));
            $ticker_response = file_get_contents($ticker_url);
            $ticker_data = json_decode($ticker_response, true);

            // 24 saatlik değişim bilgisini al
            $change_url = $binance_24h_url . "?symbol=" . strtoupper(str_replace('/', '', $symbol));
            $change_response = file_get_contents($change_url);
            $change_data = json_decode($change_response, true);
            
            // Sonuçları formatla
            if (isset($ticker_data['price']) && isset($change_data['priceChangePercent'])) {
                $result = [
                    'success' => true,
                    'symbol' => $symbol,
                    'price' => floatval($ticker_data['price']),
                    'change_24h' => floatval($change_data['priceChangePercent']),
                    'timestamp' => time() * 1000 // milisaniye cinsinden timestamp
                ];
            } else {
                throw new Exception("API'den veri alınamadı");
            }
        }
        // Tüm aktif coinler için veri getir
        else {
            // Veritabanından aktif coinleri al
            $stmt = $conn->prepare("SELECT DISTINCT symbol FROM coins WHERE is_active = 1 ORDER BY symbol");
            $stmt->execute();
            $coins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $coin_data = [];
            foreach ($coins as $coin) {
                $sym = strtoupper(str_replace('/', '', $coin['symbol']));
                
                // Her coin için Binance'den veri al
                try {
                    $ticker_url = $binance_ticker_url . "?symbol=" . $sym;
                    $ticker_response = file_get_contents($ticker_url);
                    $ticker_data = json_decode($ticker_response, true);
                    
                    $change_url = $binance_24h_url . "?symbol=" . $sym;
                    $change_response = file_get_contents($change_url);
                    $change_data = json_decode($change_response, true);
                    
                    if (isset($ticker_data['price']) && isset($change_data['priceChangePercent'])) {
                        $coin_data[] = [
                            'symbol' => $coin['symbol'],
                            'price' => floatval($ticker_data['price']),
                            'change_24h' => floatval($change_data['priceChangePercent']),
                            'timestamp' => time() * 1000
                        ];
                    }
                } catch (Exception $e) {
                    // Bu coin için veri alınamadı, geçiyoruz
                    continue;
                }
                
                // API limitlerine takılmamak için her istekten sonra kısa bir bekleme
                usleep(200000); // 200ms
            }
            
            $result = [
                'success' => true,
                'data' => $coin_data,
                'timestamp' => time() * 1000
            ];
        }
    } else {
        throw new Exception("Geçersiz işlem");
    }
} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time() * 1000
    ];
}

echo json_encode($result);