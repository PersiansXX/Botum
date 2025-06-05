<?php
/**
 * Binance API yardımcı sınıfı
 * 
 * Binance API'ye istek atmak ve yanıtları işlemek için kullanılır
 * CURL kullanarak API istekleri yapar ve sonuçları JSON olarak döndürür
 */

class BinanceAPI {
    // API endpoint'leri
    private $api_url = 'https://api.binance.com/api/v3/';
    private $debug = false; // DEBUG MODUNU AKTİF ET
    private $timeout = 30; // TIMEOUT SÜRESINI DÜŞÜR
    private $max_retries = 3;
    private $retry_delay = 2;
    private $api_key = '';
    private $api_secret = '';
    
    /**
     * Constructor - API anahtarlarını opsiyonel olarak alabilir
     * 
     * @param string $api_key API Key (opsiyonel)
     * @param string $api_secret API Secret (opsiyonel)
     */
    public function __construct($api_key = null, $api_secret = null) {
        if ($api_key && $api_secret) {
            $this->api_key = trim($api_key);  // Boşlukları temizle
            $this->api_secret = trim($api_secret);  // Boşlukları temizle
        } else {
            // Ayarları bir kez yükle
            $this->loadApiKeys();
        }
    }
    
    /**
     * API anahtarlarını dosyadan veya veritabanından yükler
     */
    private function loadApiKeys() {
        try {
            // Önce config dosyasından API anahtarları alınmaya çalışılır
            $config_file = dirname(__DIR__, 2) . "/config/api_keys.json";
            
            if (file_exists($config_file)) {
                $config = json_decode(file_get_contents($config_file), true);
                if (isset($config['binance']) && isset($config['binance']['api_key']) && isset($config['binance']['api_secret'])) {
                    $this->api_key = trim($config['binance']['api_key']);  // Boşlukları temizle
                    $this->api_secret = trim($config['binance']['api_secret']);  // Boşlukları temizle
                    return;
                }
            }
            
            // Config dosyasında yoksa bot config dosyasına bak
            $bot_config_file = dirname(__DIR__, 2) . "/config/bot_config.json";
            
            if (file_exists($bot_config_file)) {
                $config = json_decode(file_get_contents($bot_config_file), true);
                if (isset($config['api_key']) && isset($config['api_secret'])) {
                    $this->api_key = trim($config['api_key']);  // Boşlukları temizle
                    $this->api_secret = trim($config['api_secret']);  // Boşlukları temizle
                    return;
                }
            }
            
            // Config dosyalarında yoksa veritabanından çek
            require_once dirname(__FILE__) . '/../includes/db_connect.php';
            
            // Bot API'ye erişim dene
            require_once 'bot_api.php';
            $bot_api = new BotAPI();
            $settings = $bot_api->getSettings();
            
            if (isset($settings['api_key']) && isset($settings['api_secret'])) {
                $this->api_key = trim($settings['api_key']);  // Boşlukları temizle
                $this->api_secret = trim($settings['api_secret']);  // Boşlukları temizle
                return;
            }
            
            // Son çare olarak veritabanında api_keys tablosunu kontrol et
            global $conn;
            
            // $conn null ise veya bağlantı kopmuşsa, yeni bağlantı oluştur
            if (!isset($conn) || $conn === null || !($conn instanceof mysqli) || $conn->connect_errno) {
                // Veritabanı sabitlerini kullanarak yeni bağlantı oluştur
                if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
                    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                } else {
                    // Sabitler tanımlanmamışsa varsayılan değerleri kullan
                    $db_host = "localhost";
                    $db_user = "root";
                    $db_pass = "Efsane44.";
                    $db_name = "trading_bot_db";
                    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                }
                
                if ($conn->connect_error) {
                    error_log("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
                    throw new Exception("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
                }
                
                $conn->set_charset("utf8mb4");
            }
            
            // Veritabanı bağlantı durumunu kontrol et
            if ($conn instanceof mysqli && !$conn->connect_errno) {
                $api_query = "SELECT * FROM api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1";
                $api_result = $conn->query($api_query);
                
                if ($api_result && $api_result->num_rows > 0) {
                    $api_row = $api_result->fetch_assoc();
                    $this->api_key = trim($api_row['api_key']);  // Boşlukları temizle
                    $this->api_secret = trim($api_row['api_secret']);  // Boşlukları temizle
                }
            } else {
                error_log("Veritabanı bağlantısı geçerli değil");
            }
            
        } catch (Exception $e) {
            error_log("API anahtarları yüklenirken hata: " . $e->getMessage());
        }
    }
    
    /**
     * API anahtarlarını ayarlar
     * 
     * @param string $api_key API Key
     * @param string $api_secret API Secret
     */
    public function setApiKeys($api_key, $api_secret) {
        $this->api_key = trim($api_key);  // Boşlukları temizle
        $this->api_secret = trim($api_secret);  // Boşlukları temizle
    }
    
    // Hata ayıklama modu aktif/deaktif
    public function setDebug($debug) {
        $this->debug = $debug;
    }
    
    /**
     * Timeout değerini ayarlar (saniye cinsinden)
     * 
     * @param int $timeout Timeout süresi (saniye)
     */
    public function setTimeout($timeout) {
        $this->timeout = max(5, intval($timeout));
    }
    
    /**
     * Retry parametrelerini ayarlar
     * 
     * @param int $max_retries Maksimum yeniden deneme sayısı
     * @param int $retry_delay İki deneme arası bekleme süresi (saniye)
     */
    public function setRetryOptions($max_retries, $retry_delay = 2) {
        $this->max_retries = max(0, intval($max_retries));
        $this->retry_delay = max(1, intval($retry_delay));
    }
    
    /**
     * API anahtarlarını döndürür (var ise)
     * 
     * @return string API Key
     */
    public function getApiKey() {
        return $this->api_key;
    }
    
    /**
     * API secret anahtarını döndürür (var ise)
     * 
     * @return string API Secret
     */
    public function getApiSecret() {
        return $this->api_secret;
    }
    
    /**
     * CURL ile API isteği yapar
     * 
     * @param string $endpoint API endpoint
     * @param array $params İstek parametreleri
     * @param string $method HTTP metodu (GET, POST vs)
     * @return array API yanıtı (JSON decode edilmiş)
     */
    private function makeRequest($endpoint, $params = [], $method = 'GET') {
        $url = $this->api_url . $endpoint;
        
        // GET istekleri için URL'e parametre eklenir
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Debug modunda URL ve parametreleri göster
        if ($this->debug) {
            error_log("[DEBUG] Binance API Request: $url");
            error_log("[DEBUG] Params: " . json_encode($params));
        }
        
        $attempt = 0;
        $last_error = null;
        
        // Yeniden deneme mekanizması
        while ($attempt < $this->max_retries) {
            try {
                // İlk deneme değilse bekleme süresi ekle
                if ($attempt > 0) {
                    if ($this->debug) {
                        error_log("[DEBUG] Retry attempt $attempt for: $url");
                    }
                    sleep($this->retry_delay);
                }
                
                $attempt++;
                
                // CURL oturumu başlat
                $ch = curl_init();
                
                // CURL seçeneklerini ayarla
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
                
                // User-Agent ekle (Binance bazen User-Agent olmadan istekleri reddediyor)
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                
                // HTTP metodu
                if ($method === 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    // POST verisini ayarla
                    if (!empty($params)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                
                // CURL isteğini çalıştır
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $info = curl_getinfo($ch);
                
                // Debug modunda tüm CURL bilgilerini göster
                if ($this->debug) {
                    error_log("[DEBUG] CURL Info: " . json_encode($info));
                    error_log("[DEBUG] HTTP Code: $http_code");
                    error_log("[DEBUG] Response: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : ''));
                }
                
                // CURL hatası kontrolü
                if ($response === false) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    
                    // Hatayı logla
                    if ($this->debug) {
                        error_log("[ERROR] CURL Error: $error");
                    }
                    
                    $last_error = "CURL Error: $error";
                    // Yeniden denemeye devam et
                    continue;
                }
                
                curl_close($ch);
                
                // HTTP durum kodu kontrolü - 429 (Rate limiting) veya 5xx hatalarında yeniden deneyelim
                if ($http_code == 429 || $http_code >= 500) {
                    if ($this->debug) {
                        error_log("[ERROR] HTTP Error $http_code: $response - Retrying...");
                    }
                    $last_error = "HTTP Error $http_code: $response";
                    
                    // 429 Rate limit hatalarında daha uzun bekleyelim
                    if ($http_code == 429) {
                        sleep($this->retry_delay * 2);
                    }
                    
                    continue;
                }
                
                // Diğer HTTP hataları için istisna fırlat
                if ($http_code >= 400) {
                    if ($this->debug) {
                        error_log("[ERROR] HTTP Error $http_code: $response");
                    }
                    throw new Exception("HTTP Error $http_code: $response");
                }
                
                // JSON formatını PHP dizisine dönüştür
                $data = json_decode($response, true);
                
                // JSON parse hatası kontrolü
                if (json_last_error() !== JSON_ERROR_NONE) {
                    if ($this->debug) {
                        error_log("[ERROR] JSON Parse Error: " . json_last_error_msg());
                        error_log("[ERROR] Response: " . substr($response, 0, 1000));
                    }
                    throw new Exception("JSON Parse Error: " . json_last_error_msg());
                }
                
                // API hatası kontrolü
                if (isset($data['code']) && isset($data['msg'])) {
                    if ($this->debug) {
                        error_log("API Error {$data['code']}: {$data['msg']}");
                    }
                    throw new Exception("API Error {$data['code']}: {$data['msg']}");
                }
                
                return $data;
            } catch (Exception $e) {
                $last_error = $e->getMessage();
                
                if ($this->debug) {
                    error_log("[ERROR] Attempt $attempt failed: " . $e->getMessage());
                }
                
                // Son deneme değilse devam et
                if ($attempt < $this->max_retries) {
                    continue;
                }
                
                // Tüm denemeler başarısız oldu
                throw new Exception("All retry attempts failed: " . $last_error);
            }
        }
        
        // Bu noktaya asla ulaşılmamalı ama kod güvenliği için
        throw new Exception("Request failed after $this->max_retries attempts: " . $last_error);
    }
    
    /**
     * Tek bir sembolün ticker verilerini getirir
     * 
     * @param string $symbol Sembol (örn. BTCUSDT)
     * @return array Ticker verileri
     */
    public function getTicker($symbol) {
        try {
            // Ticker bilgilerini çek
            $data = $this->makeRequest('ticker/24hr', ['symbol' => $symbol]);
            
            // API yanıtını doğrula
            if (!isset($data['lastPrice']) || empty($data['lastPrice'])) {
                // Boş veya eksik yanıt
                if ($this->debug) {
                    error_log("Empty ticker response for $symbol: " . json_encode($data));
                }
                
                // Varsayılan değerler ile dön
                return [
                    'symbol' => $symbol,
                    'lastPrice' => '0',
                    'priceChangePercent' => '0.00',
                    'priceChange' => '0',
                    'highPrice' => '0',
                    'lowPrice' => '0',
                    'volume' => '0',
                    'quoteVolume' => '0',
                    'error' => 'Invalid API response'
                ];
            }
            
            return $data;
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("getTicker error: " . $e->getMessage());
            }
            
            // Hata durumunda varsayılan değerler ile dön
            return [
                'symbol' => $symbol,
                'lastPrice' => '0',
                'priceChangePercent' => '0.00',
                'priceChange' => '0',
                'highPrice' => '0',
                'lowPrice' => '0',
                'volume' => '0',
                'quoteVolume' => '0',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Tüm sembollerin ticker verilerini getirir
     * 
     * @return array Tüm tickerlar
     */
    public function getAllTickers() {
        try {
            return $this->makeRequest('ticker/24hr');
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("getAllTickers error: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * K-Line (mum) verileri getirir
     * 
     * @param string $symbol Sembol (örn. BTCUSDT)
     * @param string $interval Aralık (örn. 1m, 5m, 15m, 30m, 1h, 4h, 1d)
     * @param int $limit Maksimum kayıt sayısı (max 1000)
     * @return array K-Line verileri
     */
    public function getKlines($symbol, $interval = '1h', $limit = 100) {
        try {
            $params = [
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];
            
            return $this->makeRequest('klines', $params);
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("getKlines error: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Order Book verileri getirir
     * 
     * @param string $symbol Sembol (örn. BTCUSDT)
     * @param int $limit Limit (max 5000)
     * @return array Order Book verileri
     */
    public function getOrderBook($symbol, $limit = 100) {
        try {
            $params = [
                'symbol' => $symbol,
                'limit' => $limit
            ];
            
            return $this->makeRequest('depth', $params);
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("getOrderBook error: " . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Hesap bakiyesi bilgilerini getirir (Kimlik doğrulama gerektirir)
     * 
     * @param string $api_key API Key (opsiyonel, varsayılan olarak constructor'da ayarlanan değer kullanılır)
     * @param string $api_secret API Secret (opsiyonel, varsayılan olarak constructor'da ayarlanan değer kullanılır)
     * @return array Bakiye bilgileri
     */
    public function getAccountBalance($api_key = '', $api_secret = '') {
        try {
            // Debug modu için log başlangıcı
            error_log("[Binance API] getAccountBalance başlıyor...");
            
            // Parametre olarak verilen API anahtarlarını kullan (boşlukları temizleyerek)
            if (!empty($api_key) && !empty($api_secret)) {
                $api_key = trim($api_key);  // Boşlukları temizle
                $api_secret = trim($api_secret);  // Boşlukları temizle
            } 
            // Constructor'da ayarlanan anahtarları kullan
            else if (!empty($this->api_key) && !empty($this->api_secret)) {
                $api_key = $this->api_key;
                $api_secret = $this->api_secret;
            } 
            // Anahtarlar yoksa bot API'den almaya çalış
            else {
                // API anahtarları yükle
                $this->loadApiKeys();
                
                $api_key = $this->api_key;
                $api_secret = $this->api_secret;
                
                // Hala anahtarlar yoksa hata fırlat
                if (empty($api_key) || empty($api_secret)) {
                    throw new Exception("API anahtarları bulunamadı");
                }
            }
            
            // Debug için anahtarları maskele ve göster
            if ($this->debug) {
                $masked_key = substr($api_key, 0, 4) . '...' . substr($api_key, -4);
                error_log("[DEBUG] Using API Key: $masked_key");
            }
            
            // Binance API istekleri için HMAC-SHA256 imzalı istek gerekir
            $timestamp = round(microtime(true) * 1000);
            $params = [
                'timestamp' => $timestamp
            ];
            
            // İmza oluştur (HMAC SHA256)
            $query_string = http_build_query($params);
            $signature = hash_hmac('sha256', $query_string, $api_secret);
            $params['signature'] = $signature;
            
            // API isteği için birkaç özel başlık gerekiyor
            $headers = [
                'X-MBX-APIKEY: ' . $api_key
            ];
            
            // Spot bakiye API endpoint'i
            $url = 'https://api.binance.com/api/v3/account';
            
            // URL parametrelerini ekle
            $url .= '?' . $query_string . '&signature=' . $signature;
            
            // Debug modunda URL'yi göster (hassas bilgileri maskele)
            if ($this->debug) {
                $debug_url = preg_replace('/signature=([a-f0-9]+)/', 'signature=***MASKED***', $url);
                error_log("[DEBUG] Balance API Request: $debug_url");
            }
            
            // CURL isteği
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Debug modunda yanıtı göster
            if ($this->debug) {
                error_log("[DEBUG] HTTP Code: $http_code");
                error_log("[DEBUG] Response: " . substr($response, 0, 1000));
            }
            
            // CURL hatası kontrolü
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("CURL Error: $error");
            }
            
            curl_close($ch);
            
            // HTTP durum kodu kontrolü
            if ($http_code >= 400) {
                throw new Exception("HTTP Error $http_code: $response");
            }
            
            // JSON formatını PHP dizisine dönüştür
            $data = json_decode($response, true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Parse Error: " . json_last_error_msg());
            }
            
            // API hatası kontrolü
            if (isset($data['code']) && isset($data['msg'])) {
                throw new Exception("API Error {$data['code']}: {$data['msg']}");
            }
            
            // İşlenmiş bakiye verilerini döndür
            return $this->formatBalanceData($data);
        } catch (Exception $e) {
            // Her zaman hatayı günlüğe kaydet (debug modu açık olmasa bile)
            error_log("Binance getAccountBalance hatası: " . $e->getMessage());
            error_log("Hata detayı: " . $e->getTraceAsString());
            
            if ($this->debug) {
                error_log("getAccountBalance debug detayı: " . $e->getTraceAsString());
            }
            
            // Hata durumunda boş dizi döndür
            return [
                'error' => $e->getMessage(),
                'balances' => [],
                'errorTime' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Ham bakiye verilerini formatlayarak daha kullanışlı hale getirir
     * 
     * @param array $data Binance API'den gelen ham bakiye verileri
     * @return array İşlenmiş bakiye verileri
     */
    private function formatBalanceData($data) {
        $result = [
            'totalBalance' => 0,
            'balances' => [],
            'timestamp' => time()
        ];
        
        // Yanıt boş veya geçersiz ise erken dön
        if (empty($data) || !is_array($data)) {
            error_log("formatBalanceData: Boş veya geçersiz veri alındı");
            return [
                'error' => 'Boş veya geçersiz veri',
                'balances' => [],
                'totalBalance' => 0
            ];
        }
        
        // API yanıtı hata içeriyorsa kontrol et
        if (isset($data['code']) && isset($data['msg'])) {
            error_log("formatBalanceData: API hatası - Kod: {$data['code']}, Mesaj: {$data['msg']}");
            return [
                'error' => "API hatası: {$data['code']} - {$data['msg']}",
                'balances' => [],
                'totalBalance' => 0
            ];
        }
        
        // Bakiyeleri işle
        if (isset($data['balances']) && is_array($data['balances'])) {
            // Debug bilgisi
            error_log("formatBalanceData: İşlenecek " . count($data['balances']) . " bakiye bulundu");
            
            // USDT eşdeğerini hesaplamak için tüm tickerları al
            try {
                $tickers = $this->getAllTickers();
                $ticker_map = [];
                
                // Ticker fiyatlarını diziye dönüştür
                foreach ($tickers as $ticker) {
                    if (isset($ticker['symbol']) && isset($ticker['lastPrice'])) {
                        $ticker_map[$ticker['symbol']] = $ticker['lastPrice'];
                    }
                }
                
                // Bakiye verilerini işle
                foreach ($data['balances'] as $balance) {
                    if (!isset($balance['asset']) || !isset($balance['free']) || !isset($balance['locked'])) {
                        continue; // Geçersiz bakiye verisi, atla
                    }
                    
                    $asset = $balance['asset'];
                    $free = floatval($balance['free']);
                    $locked = floatval($balance['locked']);
                    $total = $free + $locked;
                    
                    // Boş bakiyeleri atla
                    if ($total <= 0) {
                        continue;
                    }
                    
                    // USDT eşdeğerini hesapla
                    $value_usdt = 0;
                    
                    if ($asset === 'USDT') {
                        $value_usdt = $total;
                    } elseif ($asset === 'BUSD') {
                        // BUSD genellikle 1:1 USDT ile değişir
                        $value_usdt = $total;
                    } elseif (isset($ticker_map[$asset . 'USDT'])) {
                        // Doğrudan USDT çifti varsa
                        $value_usdt = $total * floatval($ticker_map[$asset . 'USDT']);
                    } elseif (isset($ticker_map[$asset . 'BUSD'])) {
                        // BUSD çifti varsa (USDT eşdeğeri olarak kabul et)
                        $value_usdt = $total * floatval($ticker_map[$asset . 'BUSD']);
                    } elseif ($asset === 'BTC') {
                        // BTC özel durum
                        if (isset($ticker_map['BTCUSDT'])) {
                            $value_usdt = $total * floatval($ticker_map['BTCUSDT']);
                        }
                    } elseif (isset($ticker_map['BTC' . $asset])) {
                        // BTC/XXX çifti (ters çevrilmiş)
                        $btc_price = 1 / floatval($ticker_map['BTC' . $asset]);
                        if (isset($ticker_map['BTCUSDT'])) {
                            $value_usdt = $total * $btc_price * floatval($ticker_map['BTCUSDT']);
                        }
                    }
                    
                    // Sonuca ekle
                    $result['balances'][] = [
                        'asset' => $asset,
                        'free' => $free,
                        'locked' => $locked,
                        'total' => $total,
                        'value_usdt' => $value_usdt
                    ];
                    
                    // Toplam bakiyeye ekle
                    $result['totalBalance'] += $value_usdt;
                }
                
                // Bakiyeleri USDT değerine göre sırala (büyükten küçüğe)
                usort($result['balances'], function($a, $b) {
                    return $b['value_usdt'] - $a['value_usdt'];
                });
                
                // Toplam bakiye hesaplandı mı kontrol et
                if ($result['totalBalance'] <= 0 && !empty($result['balances'])) {
                    error_log("formatBalanceData: Uyarı - Bakiyeler var ama toplam bakiye sıfır");
                } else {
                    error_log("formatBalanceData: Toplam bakiye başarıyla hesaplandı: " . $result['totalBalance']);
                }
            } catch (Exception $e) {
                error_log("formatBalanceData işleme hatası: " . $e->getMessage());
                
                // Yine de mevcut balances verisini döndür ama hata bilgisi ekle
                $result['error'] = "Bakiye işleme hatası: " . $e->getMessage();
            }
        } else {
            // 'balances' anahtarı yoksa veya dizi değilse
            error_log("formatBalanceData: API yanıtında 'balances' anahtarı bulunamadı veya dizi değil");
            $result['error'] = "API yanıtında 'balances' verisi bulunamadı";
            
            // Yanıtın içeriğini logla (debug amaçlı)
            error_log("API yanıtı içeriği: " . print_r($data, true));
        }
        
        return $result;
    }
    
    /**
     * Sipariş durumunu kontrol eder
     * 
     * @param string $symbol Sembol (örn. BTCUSDT)
     * @param string $order_id Sipariş ID
     * @param string $api_key API Key (opsiyonel, varsayılan olarak constructor'da ayarlanan değer)
     * @param string $api_secret API Secret (opsiyonel, varsayılan olarak constructor'da ayarlanan değer)
     * @return array Sipariş durumu
     */
    public function checkOrderStatus($symbol, $order_id, $api_key = '', $api_secret = '') {
        try {
            // API anahtarlarını kontrol et ve boşlukları temizle
            if (!empty($api_key) && !empty($api_secret)) {
                $api_key = trim($api_key);  // Boşlukları temizle
                $api_secret = trim($api_secret);  // Boşlukları temizle
            } else if (!empty($this->api_key) && !empty($this->api_secret)) {
                $api_key = $this->api_key;
                $api_secret = $this->api_secret;
            } else {
                // API anahtarları yükle
                $this->loadApiKeys();
                
                $api_key = $this->api_key;
                $api_secret = $this->api_secret;
                
                // Hala anahtarlar yoksa hata fırlat
                if (empty($api_key) || empty($api_secret)) {
                    throw new Exception("API anahtarları bulunamadı");
                }
            }
            
            // İstek parametrelerini hazırla
            $timestamp = round(microtime(true) * 1000);
            $params = [
                'symbol' => $symbol,
                'orderId' => $order_id,
                'timestamp' => $timestamp
            ];
            
            // İmza oluştur
            $query_string = http_build_query($params);
            $signature = hash_hmac('sha256', $query_string, $api_secret);
            $params['signature'] = $signature;
            
            // API isteği için başlıklar
            $headers = [
                'X-MBX-APIKEY: ' . $api_key
            ];
            
            // Sipariş durumu endpoint'i
            $url = 'https://api.binance.com/api/v3/order';
            $url .= '?' . $query_string . '&signature=' . $signature;
            
            // CURL isteği
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("CURL Error: $error");
            }
            
            curl_close($ch);
            
            // HTTP durum kodu kontrolü
            if ($http_code >= 400) {
                throw new Exception("HTTP Error $http_code: $response");
            }
            
            // JSON yanıtını işle
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Parse Error: " . json_last_error_msg());
            }
            
            // API hatası kontrolü
            if (isset($data['code']) && isset($data['msg'])) {
                throw new Exception("API Error {$data['code']}: {$data['msg']}");
            }
            
            return $data;
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("checkOrderStatus error: " . $e->getMessage());
            }
            
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Futures hesap bakiyesi bilgilerini getirir (Kimlik doğrulama gerektirir)
     * 
     * @param string $api_key API Key (opsiyonel, varsayılan olarak constructor'da ayarlanan değer kullanılır)
     * @param string $api_secret API Secret (opsiyonel, varsayılan olarak constructor'da ayarlanan değer kullanılır)
     * @return array Futures bakiye bilgileri
     */
    public function getFuturesBalance($api_key = '', $api_secret = '') {
        try {
            // Debug modu için log başlangıcı
            error_log("[Binance API] getFuturesBalance başlıyor...");
            
            // Parametre olarak verilen API anahtarlarını kullan (boşlukları temizleyerek)
            if (!empty($api_key) && !empty($api_secret)) {
                $api_key = trim($api_key);  // Boşlukları temizle
                $api_secret = trim($api_secret);  // Boşlukları temizle
            } 
            // Constructor'da ayarlanan anahtarları kullan
            else if (!empty($this->api_key) && !empty($this->api_secret)) {
                $api_key = $this->api_key;
                $api_secret = $this->api_secret;
            } 
            // Anahtarlar yoksa bot API'den almaya çalış
            else {
                // API anahtarları yükle
                $this->loadApiKeys();
                
                $api_key = $this->api_key;
                $api_secret = $this->api_secret;
                
                // Hala anahtarlar yoksa hata fırlat
                if (empty($api_key) || empty($api_secret)) {
                    throw new Exception("API anahtarları bulunamadı");
                }
            }
            
            // Debug için anahtarları maskele ve göster
            if ($this->debug) {
                $masked_key = substr($api_key, 0, 4) . '...' . substr($api_key, -4);
                error_log("[DEBUG] Using API Key: $masked_key");
            }
            
            // Binance Futures API istekleri için HMAC-SHA256 imzalı istek gerekir
            $timestamp = round(microtime(true) * 1000);
            $params = [
                'timestamp' => $timestamp
            ];
            
            // İmza oluştur (HMAC SHA256)
            $query_string = http_build_query($params);
            $signature = hash_hmac('sha256', $query_string, $api_secret);
            $params['signature'] = $signature;
            
            // API isteği için birkaç özel başlık gerekiyor
            $headers = [
                'X-MBX-APIKEY: ' . $api_key
            ];
            
            // Futures hesap bakiyesi API endpoint'i
            $url = 'https://fapi.binance.com/fapi/v2/account';
            
            // URL parametrelerini ekle
            $url .= '?' . $query_string . '&signature=' . $signature;
            
            // Debug modunda URL'yi göster (hassas bilgileri maskele)
            if ($this->debug) {
                $debug_url = preg_replace('/signature=([a-f0-9]+)/', 'signature=***MASKED***', $url);
                error_log("[DEBUG] Futures Balance API Request: $debug_url");
            }
            
            // CURL isteği
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Debug modunda yanıtı göster
            if ($this->debug) {
                error_log("[DEBUG] HTTP Code: $http_code");
                error_log("[DEBUG] Response: " . substr($response, 0, 1000));
            }
            
            // CURL hatası kontrolü
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("CURL Error: $error");
            }
            
            curl_close($ch);
            
            // HTTP durum kodu kontrolü
            if ($http_code >= 400) {
                throw new Exception("HTTP Error $http_code: $response");
            }
            
            // JSON formatını PHP dizisine dönüştür
            $data = json_decode($response, true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Parse Error: " . json_last_error_msg());
            }
            
            // API hatası kontrolü
            if (isset($data['code']) && isset($data['msg'])) {
                throw new Exception("API Error {$data['code']}: {$data['msg']}");
            }
            
            // İşlenmiş futures bakiye verilerini döndür
            return $this->formatFuturesBalanceData($data);
            
        } catch (Exception $e) {
            // Her zaman hatayı günlüğe kaydet (debug modu açık olmasa bile)
            error_log("Binance getFuturesBalance hatası: " . $e->getMessage());
            
            if ($this->debug) {
                error_log("getFuturesBalance debug detayı: " . $e->getTraceAsString());
            }
            
            // Hata durumunda boş dizi döndür
            return [
                'error' => $e->getMessage(),
                'totalWalletBalance' => 0,
                'totalUnrealizedProfit' => 0,
                'positions' => [],
                'errorTime' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Ham futures bakiye verilerini formatlayarak daha kullanışlı hale getirir
     * 
     * @param array $data Binance API'den gelen ham futures bakiye verileri
     * @return array İşlenmiş futures bakiye verileri
     */
    private function formatFuturesBalanceData($data) {
        $result = [
            'totalWalletBalance' => 0,
            'totalUnrealizedProfit' => 0,
            'totalMaintMargin' => 0,
            'availableBalance' => 0,
            'positions' => [],
            'assets' => [],
            'timestamp' => time()
        ];
        
        // Yanıt boş veya geçersiz ise erken dön
        if (empty($data) || !is_array($data)) {
            error_log("formatFuturesBalanceData: Boş veya geçersiz veri alındı");
            return [
                'error' => 'Boş veya geçersiz veri',
                'totalWalletBalance' => 0,
                'totalUnrealizedProfit' => 0,
                'positions' => [],
                'assets' => []
            ];
        }
        
        // API yanıtı hata içeriyorsa kontrol et
        if (isset($data['code']) && isset($data['msg'])) {
            error_log("formatFuturesBalanceData: API hatası - Kod: {$data['code']}, Mesaj: {$data['msg']}");
            return [
                'error' => "API hatası: {$data['code']} - {$data['msg']}",
                'totalWalletBalance' => 0,
                'totalUnrealizedProfit' => 0,
                'positions' => [],
                'assets' => []
            ];
        }
        
        // Bakiye bilgilerini ayıkla
        if (isset($data['totalWalletBalance'])) {
            $result['totalWalletBalance'] = floatval($data['totalWalletBalance']);
        }
        
        if (isset($data['totalUnrealizedProfit'])) {
            $result['totalUnrealizedProfit'] = floatval($data['totalUnrealizedProfit']);
        }
        
        if (isset($data['totalMaintMargin'])) {
            $result['totalMaintMargin'] = floatval($data['totalMaintMargin']);
        }
        
        if (isset($data['availableBalance'])) {
            $result['availableBalance'] = floatval($data['availableBalance']);
        }
        
        // Pozisyonları işle
        if (isset($data['positions']) && is_array($data['positions'])) {
            foreach ($data['positions'] as $position) {
                if (isset($position['symbol']) && $position['positionAmt'] != 0) {
                    $result['positions'][] = [
                        'symbol' => $position['symbol'],
                        'positionAmt' => floatval($position['positionAmt']),
                        'entryPrice' => floatval($position['entryPrice']),
                        'markPrice' => floatval($position['markPrice']),
                        'unRealizedProfit' => floatval($position['unRealizedProfit']),
                        'liquidationPrice' => floatval($position['liquidationPrice']),
                        'leverage' => floatval($position['leverage']),
                        'marginType' => $position['marginType']
                    ];
                }
            }
        }
        
        // Varlıkları işle
        if (isset($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (isset($asset['asset'])) {
                    $result['assets'][] = [
                        'asset' => $asset['asset'],
                        'walletBalance' => floatval($asset['walletBalance']),
                        'unrealizedProfit' => floatval($asset['unrealizedProfit']),
                        'marginBalance' => floatval($asset['marginBalance']),
                        'maintMargin' => floatval($asset['maintMargin']),
                        'initialMargin' => floatval($asset['initialMargin']),
                        'positionInitialMargin' => floatval($asset['positionInitialMargin']),
                        'openOrderInitialMargin' => floatval($asset['openOrderInitialMargin']),
                        'availableBalance' => floatval($asset['availableBalance'])
                    ];
                }
            }
        }
        
        return $result;
    }
}
?>