<?php
session_start();
// API için sabit tanımlama - güvenlik için
define('TRADING_BOT', true);

// Bellek limitini arttır
ini_set('memory_limit', '256M');

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

// Veritabanı bağlantısı
require_once '../includes/db_connect.php';

// Binance API sınıfı
require_once 'binance_api.php';

// Bakiye tipini kontrol et
$balance_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Hata ayıklama modu
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// Önbellek zorlama (cache bypass)
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

try {
    // Binance API nesnesi oluştur
    $binance_api = new BinanceAPI();
    $binance_api->setDebug($debug);
    
    // API anahtarlarının var olduğunu doğrula
    if (empty($binance_api->getApiKey()) || empty($binance_api->getApiSecret())) {
        // API anahtarlarını veritabanından almayı dene
        $api_keys = getBinanceApiKeys();
        if ($api_keys['success']) {
            $binance_api->setApiKeys($api_keys['api_key'], $api_keys['api_secret']);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'API anahtarları bulunamadı veya geçersiz.',
                'error_details' => $api_keys['message'] ?? 'Bilinmeyen hata'
            ]);
            exit;
        }
    }
    
    // Bakiye tipine göre metodu çağır
    switch($balance_type) {
        case 'spot':
            // Spot bakiye
            $balance_data = $binance_api->getAccountBalance();
            if (isset($balance_data['error'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Spot bakiye alınamadı: ' . $balance_data['error'],
                    'debug_info' => $debug ? $balance_data : null
                ]);
                exit;
            }
            
            $spot_total = isset($balance_data['totalBalance']) ? floatval($balance_data['totalBalance']) : 0;
            echo json_encode([
                'success' => true,
                'total' => $spot_total,
                'timestamp' => time(),
                'type' => 'spot'
            ]);
            break;
            
        case 'futures':
            // Futures bakiye - alternatif metot kullanılıyor
            try {
                // Futures bakiyesi için özel fonksiyon
                $futures_total = getFuturesBalanceFromAPI($binance_api);
                
                echo json_encode([
                    'success' => true,
                    'total' => $futures_total,
                    'timestamp' => time(),
                    'type' => 'futures'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Futures bakiye alınamadı: ' . $e->getMessage(),
                    'debug_info' => $debug ? ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()] : null
                ]);
            }
            break;
            
        case 'margin':
            // Marjin bakiye
            try {
                // Marjin bakiyesi için özel fonksiyon
                $margin_data = getMarginBalanceFromAPI($binance_api);
                
                echo json_encode([
                    'success' => true,
                    'cross_margin' => $margin_data['cross_margin'],
                    'isolated_margin' => $margin_data['isolated_margin'],
                    'total' => $margin_data['total'],
                    'timestamp' => time(),
                    'type' => 'margin'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Marjin bakiye alınamadı: ' . $e->getMessage(),
                    'debug_info' => $debug ? ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()] : null
                ]);
            }
            break;
            
        case 'all':
        default:
            // Önbellek kontrolü - eğer force_refresh değilse ve önbellek varsa onu kullan
            if (!$force_refresh) {
                $cache_file = __DIR__ . '/cache/balance_cache.json';
                if (file_exists($cache_file)) {
                    $cache_time = filemtime($cache_file);
                    $current_time = time();
                    // Önbellek 5 dakikadan daha yeni ise (300 saniye)
                    if (($current_time - $cache_time) < 300) {
                        $cache_data = file_get_contents($cache_file);
                        $cache_response = json_decode($cache_data, true);
                        if ($cache_response && isset($cache_response['total'])) {
                            // Önbellekten yanıt döndür
                            $cache_response['cached'] = true;
                            $cache_response['cache_time'] = date('Y-m-d H:i:s', $cache_time);
                            echo json_encode($cache_response);
                            exit;
                        }
                    }
                }
            }
            
            // Tüm bakiyeleri al
            $balance_summary = [
                'spot_total' => 0,
                'futures_total' => 0,
                'margin_total' => 0,
                'cross_margin' => 0,
                'isolated_margin' => 0,
                'total' => 0
            ];
            
            // Spot bakiye
            $balance_data = $binance_api->getAccountBalance();
            if (!isset($balance_data['error']) && isset($balance_data['totalBalance'])) {
                $balance_summary['spot_total'] = floatval($balance_data['totalBalance']);
            }
            
            // Futures bakiye (alternatif metot kullanarak)
            try {
                $balance_summary['futures_total'] = getFuturesBalanceFromAPI($binance_api);
            } catch (Exception $e) {
                // Futures bakiye hatası - loglama yapabilirsiniz
                error_log("Futures bakiye alınamadı: " . $e->getMessage());
            }
            
            // Marjin bakiye (yeni eklenen fonksiyon)
            try {
                $margin_data = getMarginBalanceFromAPI($binance_api);
                $balance_summary['cross_margin'] = $margin_data['cross_margin'];
                $balance_summary['isolated_margin'] = $margin_data['isolated_margin'];
                $balance_summary['margin_total'] = $margin_data['total'];
            } catch (Exception $e) {
                // Marjin bakiye hatası - loglama yapabilirsiniz
                error_log("Marjin bakiye alınamadı: " . $e->getMessage());
            }
            
            // Toplam bakiye
            $balance_summary['total'] = $balance_summary['spot_total'] + 
                                        $balance_summary['futures_total'] + 
                                        $balance_summary['margin_total'];
            
            // Bakiye bilgilerini JSON dosyasına kaydet (opsiyonel)
            if ($balance_summary['total'] > 0) {
                $json_file = __DIR__ . '/binance_total_balances.json';
                file_put_contents($json_file, json_encode([
                    'total_spot' => $balance_summary['spot_total'],
                    'total_futures' => $balance_summary['futures_total'],
                    'total_margin' => $balance_summary['cross_margin'],
                    'total_isolated' => $balance_summary['isolated_margin'],
                    'total_balance' => $balance_summary['total'],
                    'last_update' => date('Y-m-d H:i:s'),
                    'timestamp' => time()
                ], JSON_PRETTY_PRINT));
                
                // Önbelleğe sonuçları kaydet
                if (!file_exists(__DIR__ . '/cache')) {
                    mkdir(__DIR__ . '/cache', 0755, true);
                }
                $cache_file = __DIR__ . '/cache/balance_cache.json';
                file_put_contents($cache_file, json_encode([
                    'success' => true,
                    'spot_total' => $balance_summary['spot_total'],
                    'futures_total' => $balance_summary['futures_total'],
                    'margin_total' => $balance_summary['margin_total'],
                    'cross_margin' => $balance_summary['cross_margin'],
                    'isolated_margin' => $balance_summary['isolated_margin'],
                    'total' => $balance_summary['total'],
                    'timestamp' => time(),
                    'last_update' => date('Y-m-d H:i:s')
                ]));
            }
            
            echo json_encode([
                'success' => true,
                'spot_total' => $balance_summary['spot_total'],
                'futures_total' => $balance_summary['futures_total'],
                'margin_total' => $balance_summary['margin_total'],
                'cross_margin' => $balance_summary['cross_margin'],
                'isolated_margin' => $balance_summary['isolated_margin'],
                'total' => $balance_summary['total'],
                'timestamp' => time(),
                'last_update' => date('Y-m-d H:i:s')
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'İşlem hatası: ' . $e->getMessage(),
        'debug_info' => $debug ? ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()] : null
    ]);
}

/**
 * API anahtarlarını çeşitli kaynaklardan almayı dener
 * @return array API anahtarları ve durum bilgisi
 */
function getBinanceApiKeys() {
    global $conn, $debug;
    
    try {
        // Bot settings tablosunu kontrol et
        $query = "SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            // settings_json alanından kontrol et
            if (!empty($row['settings_json'])) {
                $settings = json_decode($row['settings_json'], true);
                if (is_array($settings)) {
                    // api_keys içindeki anahtarları kontrol et
                    if (isset($settings['api_keys']['binance_api_key']) && isset($settings['api_keys']['binance_api_secret'])) {
                        return [
                            'success' => true,
                            'api_key' => $settings['api_keys']['binance_api_key'],
                            'api_secret' => $settings['api_keys']['binance_api_secret'],
                            'source' => 'bot_settings.settings_json.api_keys'
                        ];
                    }
                    
                    // api.binance içindeki anahtarları kontrol et
                    if (isset($settings['api']['binance']['api_key']) && 
                        (isset($settings['api']['binance']['api_secret']) || isset($settings['api']['binance']['secret']))) {
                        $secret = isset($settings['api']['binance']['api_secret']) ? 
                                 $settings['api']['binance']['api_secret'] : $settings['api']['binance']['secret'];
                        
                        return [
                            'success' => true,
                            'api_key' => $settings['api']['binance']['api_key'],
                            'api_secret' => $secret,
                            'source' => 'bot_settings.settings_json.api.binance'
                        ];
                    }
                    
                    // Doğrudan settings içindeki anahtarları kontrol et
                    if (isset($settings['api_key']) && isset($settings['api_secret'])) {
                        return [
                            'success' => true,
                            'api_key' => $settings['api_key'],
                            'api_secret' => $settings['api_secret'],
                            'source' => 'bot_settings.settings_json'
                        ];
                    }
                }
            }
            
            // settings alanından kontrol et
            if (!empty($row['settings'])) {
                $settings = json_decode($row['settings'], true);
                if (is_array($settings)) {
                    if (isset($settings['api_key']) && isset($settings['api_secret'])) {
                        return [
                            'success' => true,
                            'api_key' => $settings['api_key'],
                            'api_secret' => $settings['api_secret'],
                            'source' => 'bot_settings.settings'
                        ];
                    }
                }
            }
            
            error_log("Ayarlar veritabanından başarıyla alındı (settings alanından)");
        }
        
        // API keys tablosunu kontrol et
        $query = "SELECT * FROM api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            if (!empty($row['api_key']) && !empty($row['api_secret'])) {
                return [
                    'success' => true,
                    'api_key' => $row['api_key'],
                    'api_secret' => $row['api_secret'],
                    'source' => 'api_keys'
                ];
            }
        }
        
        // Config dosyalarını kontrol et
        $config_file = dirname(__DIR__, 2) . "/config/api_keys.json";
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            if (isset($config['binance']) && isset($config['binance']['api_key']) && isset($config['binance']['api_secret'])) {
                return [
                    'success' => true,
                    'api_key' => $config['binance']['api_key'],
                    'api_secret' => $config['binance']['api_secret'],
                    'source' => 'config/api_keys.json'
                ];
            }
        }
        
        // Bot config dosyasını kontrol et
        $bot_config_file = dirname(__DIR__, 2) . "/config/bot_config.json";
        if (file_exists($bot_config_file)) {
            $config = json_decode(file_get_contents($bot_config_file), true);
            if (isset($config['api_key']) && isset($config['api_secret'])) {
                return [
                    'success' => true,
                    'api_key' => $config['api_key'],
                    'api_secret' => $config['api_secret'],
                    'source' => 'config/bot_config.json'
                ];
            }
        }
        
        // Hiçbir yerden alınamadı
        return [
            'success' => false,
            'message' => 'API anahtarları bulunamadı'
        ];
    } catch (Exception $e) {
        if ($debug) {
            error_log("API anahtarları alınırken hata: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'API anahtarları alınırken hata: ' . $e->getMessage()
        ];
    }
}

/**
 * Futures bakiye bilgilerini direkt API çağrısı ile alır
 * Bu metot eksik getFuturesBalance() metodunu alternatif olarak gerçekleştirir
 * 
 * @param BinanceAPI $api_client BinanceAPI nesnesi
 * @return float Futures hesap bakiyesi (USDT cinsinden)
 */
function getFuturesBalanceFromAPI($api_client) {
    // API anahtarlarını al
    $api_key = $api_client->getApiKey();
    $api_secret = $api_client->getApiSecret();
    
    if (empty($api_key) || empty($api_secret)) {
        throw new Exception("Futures API anahtarları eksik");
    }
    
    // Zaman damgası
    $timestamp = round(microtime(true) * 1000);
    $params = "timestamp=$timestamp";
    
    // İmza oluştur
    $signature = hash_hmac('sha256', $params, $api_secret);
    
    // Futures API endpoint
    $url = "https://fapi.binance.com/fapi/v2/balance?$params&signature=$signature";
    
    // CURL isteği
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-MBX-APIKEY: $api_key"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Futures API bağlantı hatası: $error");
    }
    
    curl_close($ch);
    
    // HTTP durum kodu kontrolü
    if ($http_code != 200) {
        throw new Exception("Futures API HTTP hatası: $http_code - $response");
    }
    
    // JSON yanıtını ayrıştır
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Futures API JSON ayrıştırma hatası: " . json_last_error_msg());
    }
    
    // API hatası kontrolü
    if (isset($data['code']) && isset($data['msg'])) {
        throw new Exception("Futures API hatası {$data['code']}: {$data['msg']}");
    }
    
    // USDT bakiyesini bul
    $total_usdt = 0;
    foreach ($data as $asset) {
        if ($asset['asset'] === 'USDT') {
            $total_usdt += floatval($asset['balance']);
        }
    }
    
    return $total_usdt;
}

/**
 * Marjin bakiye bilgilerini direkt API çağrısı ile alır
 * 
 * @param BinanceAPI $api_client BinanceAPI nesnesi
 * @return array Marjin hesap bakiyeleri (Cross, Isolated ve toplam)
 */
function getMarginBalanceFromAPI($api_client) {
    // API anahtarlarını al
    $api_key = $api_client->getApiKey();
    $api_secret = $api_client->getApiSecret();
    
    if (empty($api_key) || empty($api_secret)) {
        throw new Exception("Marjin API anahtarları eksik");
    }
    
    // Sonuç array'i
    $margin_result = [
        'cross_margin' => 0,
        'isolated_margin' => 0,
        'total' => 0
    ];
    
    // ---------------
    // 1. Cross Margin bakiyesini al
    // ---------------
    $timestamp = round(microtime(true) * 1000);
    $params = "timestamp=$timestamp";
    
    // İmza oluştur
    $signature = hash_hmac('sha256', $params, $api_secret);
    
    // Cross Margin API endpoint
    $url = "https://api.binance.com/sapi/v1/margin/account?$params&signature=$signature";
    
    // CURL isteği
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-MBX-APIKEY: $api_key"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("Cross Margin API bağlantı hatası: $error");
    } elseif ($http_code == 200) {
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // Cross Margin varlıklarını topla
            if (isset($data['totalAssetOfBtc'])) {
                // BTC cinsinden toplam varlık değeri
                $btc_value = floatval($data['totalAssetOfBtc']);
                
                // BTC/USDT fiyatını al
                $btc_price_url = "https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT";
                $btc_response = @file_get_contents($btc_price_url);
                if ($btc_response !== false) {
                    $btc_data = json_decode($btc_response, true);
                    if (isset($btc_data['price'])) {
                        $btc_price = floatval($btc_data['price']);
                        // USDT değeri hesapla
                        $margin_result['cross_margin'] = $btc_value * $btc_price;
                    }
                }
            } else {
                // totalAssetOfBtc yoksa, varlıkları manuel topla
                if (isset($data['userAssets'])) {
                    foreach ($data['userAssets'] as $asset) {
                        $free = floatval($asset['free']);
                        $locked = floatval($asset['locked']);
                        $borrowed = floatval($asset['borrowed']);
                        $interest = floatval($asset['interest']);
                        
                        $total_amount = $free + $locked - $borrowed - $interest;
                        
                        // Asset USDT ise doğrudan ekle
                        if ($asset['asset'] === 'USDT') {
                            $margin_result['cross_margin'] += $total_amount;
                        } else {
                            // Diğer varlıkların USDT değerini hesapla
                            $price_url = "https://api.binance.com/api/v3/ticker/price?symbol={$asset['asset']}USDT";
                            $price_response = @file_get_contents($price_url);
                            
                            if ($price_response !== false) {
                                $price_data = json_decode($price_response, true);
                                if (isset($price_data['price'])) {
                                    $price = floatval($price_data['price']);
                                    $margin_result['cross_margin'] += $total_amount * $price;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    curl_close($ch);
    
    // ---------------
    // 2. Isolated Margin bakiyesini al
    // ---------------
    $timestamp = round(microtime(true) * 1000);
    $params = "timestamp=$timestamp";
    
    // İmza oluştur
    $signature = hash_hmac('sha256', $params, $api_secret);
    
    // Isolated Margin API endpoint
    $url = "https://api.binance.com/sapi/v1/margin/isolated/account?$params&signature=$signature";
    
    // CURL isteği
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-MBX-APIKEY: $api_key"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("Isolated Margin API bağlantı hatası: $error");
    } elseif ($http_code == 200) {
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($data['assets'])) {
            foreach ($data['assets'] as $asset_pair) {
                // Her çift için bakiye bilgilerini al
                if (isset($asset_pair['baseAsset']) && isset($asset_pair['quoteAsset'])) {
                    // Base asset (örn. BTC/USDT'deki BTC)
                    $base_asset = $asset_pair['baseAsset'];
                    $base_free = floatval($base_asset['free']);
                    $base_locked = floatval($base_asset['locked']);
                    $base_borrowed = floatval($base_asset['borrowed']);
                    $base_interest = floatval($base_asset['interest']);
                    
                    $base_total = $base_free + $base_locked - $base_borrowed - $base_interest;
                    
                    if ($base_total > 0) {
                        // Base asset'in USDT değerini hesapla
                        $price_url = "https://api.binance.com/api/v3/ticker/price?symbol={$base_asset['asset']}USDT";
                        $price_response = @file_get_contents($price_url);
                        
                        if ($price_response !== false) {
                            $price_data = json_decode($price_response, true);
                            if (isset($price_data['price'])) {
                                $price = floatval($price_data['price']);
                                $margin_result['isolated_margin'] += $base_total * $price;
                            }
                        }
                    }
                    
                    // Quote asset (örn. BTC/USDT'deki USDT)
                    $quote_asset = $asset_pair['quoteAsset'];
                    $quote_free = floatval($quote_asset['free']);
                    $quote_locked = floatval($quote_asset['locked']);
                    $quote_borrowed = floatval($quote_asset['borrowed']);
                    $quote_interest = floatval($quote_asset['interest']);
                    
                    $quote_total = $quote_free + $quote_locked - $quote_borrowed - $quote_interest;
                    
                    if ($quote_total > 0) {
                        // Quote asset USDT ise doğrudan ekle
                        if ($quote_asset['asset'] === 'USDT') {
                            $margin_result['isolated_margin'] += $quote_total;
                        } else {
                            // Diğer varlıkların USDT değerini hesapla
                            $price_url = "https://api.binance.com/api/v3/ticker/price?symbol={$quote_asset['asset']}USDT";
                            $price_response = @file_get_contents($price_url);
                            
                            if ($price_response !== false) {
                                $price_data = json_decode($price_response, true);
                                if (isset($price_data['price'])) {
                                    $price = floatval($price_data['price']);
                                    $margin_result['isolated_margin'] += $quote_total * $price;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    curl_close($ch);
    
    // Toplam marjin bakiyesini hesapla
    $margin_result['total'] = $margin_result['cross_margin'] + $margin_result['isolated_margin'];
    
    return $margin_result;
}
?>