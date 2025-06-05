<?php
session_start();

// Güvenlik kontrolü - sadece giriş yapmış kullanıcılar için
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Oturum bulunamadı. Lütfen tekrar giriş yapın.'
    ]);
    exit;
}

// Hata raporlama
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON response'u bozmamak için ekrana hata basma

// Hata ayıklama modu
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// API bilgilerini al
require_once "check_bot_settings_structure.php";

// İstek tipi
$account_type = isset($_GET['type']) ? $_GET['type'] : 'spot';

// Bakiyeleri al
$api_keys = getBinanceApiKeys();

if (!$api_keys['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'API bilgileri alınamadı: ' . $api_keys['message'],
        'debug_info' => $debug ? ['api_keys_result' => $api_keys] : null
    ]);
    exit;
}

try {
    // İstek tipine göre fonksiyonu çağır
    switch($account_type) {
        case 'spot':
            $balances = getSpotBalances($api_keys);
            break;
        case 'margin':
            $balances = getMarginBalances($api_keys);
            break;
        case 'isolated':
            $balances = getIsolatedMarginBalances($api_keys);
            break;
        case 'futures':
            $balances = getFuturesBalances($api_keys);
            break;
        case 'all':
            $balances = getAllBalances($api_keys);
            break;
        case 'summary':
            $balances = getBalanceSummary($api_keys);
            break;
        default:
            $balances = getSpotBalances($api_keys);
    }
    
    echo json_encode($balances);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'İşlem hatası: ' . $e->getMessage(),
        'debug_info' => $debug ? [
            'exception_class' => get_class($e),
            'exception_trace' => $e->getTraceAsString()
        ] : null
    ]);
}

// Spot bakiyeleri al
function getSpotBalances($api_keys) {
    global $debug;
    
    // Binance API ile bakiye sorgulama
    $timestamp = time() * 1000;
    $params = "timestamp=" . $timestamp;
    $signature = hash_hmac('sha256', $params, $api_keys['api_secret']);
    
    $ch = curl_init();
    
    // URL ve Query parametreleri
    $url = "https://api.binance.com/api/v3/account?" . $params . "&signature=" . $signature;
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // Temel curl ayarları - Python kodunuzdan esinlenerek
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-MBX-APIKEY: " . $api_keys['api_key']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout süresini artırdık
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Trading Bot)');
    
    // API yanıtını al
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // CURL hata kontrolü
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'API bağlantı hatası: ' . $error_msg,
            'debug_info' => $debug ? [
                'curl_error' => $error_msg,
                'curl_errno' => curl_errno($ch),
                'url' => $url
            ] : null
        ];
    }
    
    curl_close($ch);
    
    // Yanıt kontrolü
    if ($http_code != 200) {
        return [
            'success' => false,
            'message' => 'API yanıt kodu: ' . $http_code,
            'debug_info' => $debug ? ['response' => $response] : null
        ];
    }
    
    // JSON parse
    $result_array = json_decode($response, true);
    
    // Parse hata kontrolü
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'API yanıtını ayrıştırma hatası: ' . json_last_error_msg(),
            'debug_info' => $debug ? ['response' => $response] : null
        ];
    }
    
    // API hata kontrolü
    if (isset($result_array['code']) && isset($result_array['msg'])) {
        return [
            'success' => false,
            'message' => 'Binance API Hatası: ' . $result_array['msg'] . ' (Kod: ' . $result_array['code'] . ')',
            'debug_info' => $debug ? $result_array : null
        ];
    }
    
    // Bakiyelerin varlığını kontrol et
    if (!isset($result_array['balances']) || !is_array($result_array['balances'])) {
        return [
            'success' => false,
            'message' => 'API yanıtında bakiye bilgisi bulunamadı',
            'debug_info' => $debug ? array_keys($result_array) : null
        ];
    }
    
    // Bakiyeleri topla - Python kodunuzdaki gibi
    $balances = [];
    $zero_balance_count = 0;
    
    foreach ($result_array['balances'] as $balance) {
        $free = floatval($balance['free']);
        $locked = floatval($balance['locked']);
        $total = $free + $locked;
        
        if ($total > 0) {
            $balances[] = [
                'asset' => $balance['asset'],
                'free' => $free,
                'locked' => $locked,
                'total' => $total
            ];
        } else {
            $zero_balance_count++;
        }
    }
    
    // Değere göre sırala - Python kodunuzdaki gibi
    usort($balances, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    
    return [
        'success' => true,
        'balances' => $balances,
        'count' => count($balances),
        'zero_balance_count' => $zero_balance_count,
        'timestamp' => time(),
        'debug_info' => $debug ? [
            'total_balances' => count($result_array['balances'])
        ] : null
    ];
}

// Margin bakiyeleri al (Cross Margin)
function getMarginBalances($api_keys) {
    global $debug;
    
    // Binance API ile bakiye sorgulama
    $timestamp = time() * 1000;
    $params = "timestamp=" . $timestamp;
    $signature = hash_hmac('sha256', $params, $api_keys['api_secret']);
    
    $ch = curl_init();
    
    // URL ve Query parametreleri - Python kodunuza benzer
    $url = "https://api.binance.com/sapi/v1/margin/account?" . $params . "&signature=" . $signature;
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // Temel curl ayarları
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-MBX-APIKEY: " . $api_keys['api_key']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Trading Bot)');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'Margin API bağlantı hatası: ' . $error_msg
        ];
    }
    
    curl_close($ch);
    
    if ($http_code != 200) {
        return [
            'success' => false,
            'message' => 'Margin API yanıt kodu: ' . $http_code,
            'debug_info' => $debug ? ['response' => $response] : null
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Margin API yanıtını ayrıştırma hatası: ' . json_last_error_msg()
        ];
    }
    
    if (!isset($result['userAssets']) || !is_array($result['userAssets'])) {
        return [
            'success' => false,
            'message' => 'Margin API yanıtında bakiye bilgisi bulunamadı'
        ];
    }
    
    $balances = [];
    
    foreach ($result['userAssets'] as $asset) {
        $free = floatval($asset['free']);
        $locked = floatval($asset['locked']);
        $borrowed = floatval($asset['borrowed']);
        $interest = floatval($asset['interest']);
        $total = $free + $locked;
        
        if ($total > 0 || $borrowed > 0) {
            $balances[] = [
                'asset' => $asset['asset'],
                'free' => $free,
                'locked' => $locked,
                'total' => $total,
                'borrowed' => $borrowed,
                'interest' => $interest
            ];
        }
    }
    
    // Toplam değere göre sırala
    usort($balances, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    
    return [
        'success' => true,
        'balances' => $balances,
        'count' => count($balances),
        'timestamp' => time()
    ];
}

// Isolated Margin bakiyeleri al
function getIsolatedMarginBalances($api_keys) {
    global $debug;
    
    // Binance API ile bakiye sorgulama
    $timestamp = time() * 1000;
    $params = "timestamp=" . $timestamp;
    $signature = hash_hmac('sha256', $params, $api_keys['api_secret']);
    
    $ch = curl_init();
    
    // URL ve Query parametreleri - Python kodunuza benzer
    $url = "https://api.binance.com/sapi/v1/margin/isolated/account?" . $params . "&signature=" . $signature;
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // Temel curl ayarları
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-MBX-APIKEY: " . $api_keys['api_key']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Trading Bot)');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'Isolated Margin API bağlantı hatası: ' . $error_msg
        ];
    }
    
    curl_close($ch);
    
    if ($http_code != 200) {
        return [
            'success' => false,
            'message' => 'Isolated Margin API yanıt kodu: ' . $http_code
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Isolated Margin API yanıtını ayrıştırma hatası: ' . json_last_error_msg()
        ];
    }
    
    if (!isset($result['assets']) || !is_array($result['assets'])) {
        return [
            'success' => false,
            'message' => 'Isolated Margin API yanıtında bakiye bilgisi bulunamadı'
        ];
    }
    
    $balances = [];
    
    // Python kodunuzda olduğu gibi, base ve quote varlıkları işleme
    foreach ($result['assets'] as $pair) {
        // Base varlıkları işle
        foreach ($pair['baseAssetsList'] as $baseAsset) {
            $free = floatval($baseAsset['free']);
            $locked = floatval($baseAsset['locked']);
            $total = $free + $locked;
            
            if ($total > 0) {
                $balances[] = [
                    'asset' => $baseAsset['asset'],
                    'free' => $free,
                    'locked' => $locked,
                    'total' => $total,
                    'pair' => $pair['symbol']
                ];
            }
        }
        
        // Quote varlıkları işle
        foreach ($pair['quoteAssetsList'] as $quoteAsset) {
            $free = floatval($quoteAsset['free']);
            $locked = floatval($quoteAsset['locked']);
            $total = $free + $locked;
            
            if ($total > 0) {
                $balances[] = [
                    'asset' => $quoteAsset['asset'],
                    'free' => $free,
                    'locked' => $locked,
                    'total' => $total,
                    'pair' => $pair['symbol']
                ];
            }
        }
    }
    
    // Toplam değere göre sırala
    usort($balances, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    
    return [
        'success' => true,
        'balances' => $balances,
        'count' => count($balances),
        'timestamp' => time()
    ];
}

// Futures bakiyeleri al
function getFuturesBalances($api_keys) {
    global $debug;
    
    // Binance API ile bakiye sorgulama
    $timestamp = time() * 1000;
    $params = "timestamp=" . $timestamp;
    $signature = hash_hmac('sha256', $params, $api_keys['api_secret']);
    
    $ch = curl_init();
    
    // URL ve Query parametreleri - Python kodunuza benzer
    $url = "https://fapi.binance.com/fapi/v2/balance?" . $params . "&signature=" . $signature;
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // Temel curl ayarları
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-MBX-APIKEY: " . $api_keys['api_key']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Trading Bot)');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'Futures API bağlantı hatası: ' . $error_msg
        ];
    }
    
    curl_close($ch);
    
    if ($http_code != 200) {
        return [
            'success' => false,
            'message' => 'Futures API yanıt kodu: ' . $http_code
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Futures API yanıtını ayrıştırma hatası: ' . json_last_error_msg()
        ];
    }
    
    if (!is_array($result)) {
        return [
            'success' => false,
            'message' => 'Futures API yanıtında bakiye bilgisi bulunamadı'
        ];
    }
    
    $balances = [];
    
    foreach ($result as $asset) {
        $balance = floatval($asset['balance']);
        
        if ($balance > 0) {
            $balances[] = [
                'asset' => $asset['asset'],
                'balance' => $balance,
                'availableBalance' => floatval($asset['availableBalance']),
                'crossWalletBalance' => floatval($asset['crossWalletBalance'])
            ];
        }
    }
    
    // Toplam değere göre sırala
    usort($balances, function($a, $b) {
        return $b['balance'] <=> $a['balance'];
    });
    
    return [
        'success' => true,
        'balances' => $balances,
        'count' => count($balances),
        'timestamp' => time()
    ];
}

// Tüm bakiyeleri al
function getAllBalances($api_keys) {
    // Tüm bakiye tiplerini çek
    $spot_result = getSpotBalances($api_keys);
    $margin_result = getMarginBalances($api_keys);
    $isolated_result = getIsolatedMarginBalances($api_keys);
    $futures_result = getFuturesBalances($api_keys);
    
    // Sonuçları birleştir
    $all_results = [
        'success' => true,
        'balances_spot' => $spot_result['success'] ? $spot_result['balances'] : [],
        'balances_margin' => $margin_result['success'] ? $margin_result['balances'] : [],
        'balances_isolated' => $isolated_result['success'] ? $isolated_result['balances'] : [],
        'balances_futures' => $futures_result['success'] ? $futures_result['balances'] : [],
        'timestamp' => time()
    ];
    
    return $all_results;
}

// Bakiye özeti
function getBalanceSummary($api_keys) {
    // Tüm bakiyeleri al
    $spot_result = getSpotBalances($api_keys);
    $margin_result = getMarginBalances($api_keys);
    $isolated_result = getIsolatedMarginBalances($api_keys);
    $futures_result = getFuturesBalances($api_keys);
    
    // Toplam değerleri hesapla (USDT bazında kabul edildi)
    $spot_total = 0;
    if ($spot_result['success']) {
        foreach ($spot_result['balances'] as $balance) {
            $spot_total += $balance['total'];
        }
    }
    
    $margin_cross_total = 0;
    if ($margin_result['success']) {
        foreach ($margin_result['balances'] as $balance) {
            $margin_cross_total += $balance['total'];
        }
    }
    
    $margin_isolated_total = 0;
    if ($isolated_result['success']) {
        foreach ($isolated_result['balances'] as $balance) {
            $margin_isolated_total += $balance['total'];
        }
    }
    
    $futures_total = 0;
    if ($futures_result['success']) {
        foreach ($futures_result['balances'] as $balance) {
            $futures_total += $balance['balance'];
        }
    }
    
    $margin_total = $margin_cross_total + $margin_isolated_total;
    $grand_total = $spot_total + $margin_total + $futures_total;
    
    return [
        'success' => true,
        'summary' => [
            'spot_total' => $spot_total,
            'margin_cross_total' => $margin_cross_total,
            'margin_isolated_total' => $margin_isolated_total,
            'margin_total' => $margin_total,
            'futures_total' => $futures_total,
            'grand_total' => $grand_total
        ],
        'timestamp' => time()
    ];
}
