<?php
// Direkt Binance API erişimi için en temel API dosyası
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Gelen istek parametrelerini al
$action = isset($_GET['action']) ? $_GET['action'] : 'get_prices';
$symbols = isset($_GET['symbols']) ? explode(',', $_GET['symbols']) : ['BTCUSDT', 'ETHUSDT'];

// Basit CURL işlevi
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => "CURL Hatası: $error"];
    }
    
    curl_close($ch);
    
    // JSON yanıtını PHP dizisine dönüştür
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'JSON Ayrıştırma Hatası: ' . json_last_error_msg()];
    }
    
    return ['success' => true, 'data' => $data];
}

$response = [];

switch ($action) {
    case 'get_prices':
        $tickerData = [];
        
        try {
            // Tüm fiyatları bir defada çek
            $result = makeRequest('https://api.binance.com/api/v3/ticker/24hr');
            
            if ($result['success']) {
                $allData = $result['data'];
                
                // İstenen sembolleri filtrele
                foreach ($allData as $ticker) {
                    if (in_array($ticker['symbol'], $symbols)) {
                        $tickerData[$ticker['symbol']] = $ticker;
                    }
                }
                
                $response = [
                    'success' => true, 
                    'data' => $tickerData,
                    'count' => count($tickerData),
                    'timestamp' => time()
                ];
            } else {
                $response = $result;
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
        break;
        
    default:
        $response = ['success' => false, 'error' => 'Geçersiz işlem: ' . $action];
}

// JSON yanıtını gönder
echo json_encode($response, JSON_PRETTY_PRINT);
?>