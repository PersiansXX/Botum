<?php
/**
 * Bakiye işlemleri için API sınıfı
 * Bu dosya bakiye ile ilgili tüm işlemleri yönetir
 */
class BalanceAPI {
    private $db;
    private $binance_api;
    private $cache_file;
    private $cache_expiry = 3600; // 1 saat

    public function __construct($binance_api = null) {
        // Veritabanı bağlantısı
        if (file_exists('../includes/db_connect.php')) {
            require_once '../includes/db_connect.php';
            $this->db = $GLOBALS['conn'] ?? null;
        }
        
        // Binance API bağlantısı
        $this->binance_api = $binance_api;
        
        // Cache dosyası yolu
        $this->cache_file = __DIR__ . '/binance_total_balances.json';
    }
    
    /**
     * Binance bakiyelerini tüm hesap tiplerinden çeker
     * @return array Bakiye bilgileri
     */
    public function getAllBalances() {
        // Önce cache'i kontrol et
        $cached_data = $this->getCachedBalances();
        if ($cached_data) {
            return $cached_data;
        }
        
        // Binance API kullanılabilirse API'den çek
        if ($this->binance_api) {
            try {
                // API anahtarlarını sadece veritabanından çekeceğiz
                $api_key = '';
                $api_secret = '';
                
                // Veritabanı bağlantısı
                if ($this->db) {
                    // Veritabanından API anahtarlarını çek
                    $api_query = "SELECT * FROM api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1";
                    $api_result = mysqli_query($this->db, $api_query);
                    
                    if ($api_result && mysqli_num_rows($api_result) > 0) {
                        $api_row = mysqli_fetch_assoc($api_result);
                        $api_key = $api_row['api_key'];
                        $api_secret = $api_row['api_secret'];
                    }
                }
                
                // Spot bakiye bilgisini al
                $spot_balance_info = $this->binance_api->getAccountBalance($api_key, $api_secret);
                $spot_total_balance = isset($spot_balance_info['totalBalance']) ? $spot_balance_info['totalBalance'] : 0;
                $spot_balances = isset($spot_balance_info['balances']) ? $spot_balance_info['balances'] : [];
                
                // USDT bakiyesini bul
                $usdt_balance = 0;
                foreach ($spot_balances as $balance) {
                    if ($balance['asset'] === 'USDT') {
                        $usdt_balance = $balance['free'];
                        break;
                    }
                }
                
                // Futures bakiye bilgisini al
                $futures_balance_info = $this->binance_api->getFuturesBalance($api_key, $api_secret);
                $futures_total_balance = isset($futures_balance_info['totalBalance']) ? $futures_balance_info['totalBalance'] : 0;
                $futures_balances = isset($futures_balance_info['balances']) ? $futures_balance_info['balances'] : [];
                
                // Margin bakiye bilgisini al
                $margin_balance_info = $this->binance_api->getMarginBalance($api_key, $api_secret);
                $margin_total_balance = isset($margin_balance_info['totalBalance']) ? $margin_balance_info['totalBalance'] : 0;
                $margin_balances = isset($margin_balance_info['balances']) ? $margin_balance_info['balances'] : [];
                
                // Toplam bakiyeyi hesapla
                $total_balance = $spot_total_balance + $futures_total_balance + $margin_total_balance;
                
                $result = [
                    'success' => true,
                    'total_balance' => $total_balance,
                    'usdt_balance' => $usdt_balance,
                    'spot' => [
                        'total_balance' => $spot_total_balance,
                        'balances' => $spot_balances
                    ],
                    'futures' => [
                        'total_balance' => $futures_total_balance,
                        'balances' => $futures_balances
                    ],
                    'margin' => [
                        'total_balance' => $margin_total_balance,
                        'balances' => $margin_balances
                    ],
                    'source' => 'binance_api',
                    'timestamp' => time()
                ];
                
                return $result;
            } catch (Exception $e) {
                // Hata durumunda log kaydı tut
                error_log("Bakiye çekme hatası: " . $e->getMessage());
            }
        }
        
        // Python script dosyasını kontrol et
        return $this->getCachedBalances(true); // force_check=true
    }
    
    /**
     * Cache'lenmiş bakiye bilgilerini çeker
     * @param bool $force_check Cache süresini kontrol etmeden okuma yapılsın mı
     * @return array|false Bakiye bilgileri veya false
     */
    public function getCachedBalances($force_check = false) {
        if (!file_exists($this->cache_file)) {
            return false;
        }
        
        // Cache süresi kontrolü
        if (!$force_check && time() - filemtime($this->cache_file) > $this->cache_expiry) {
            return false;
        }
        
        $data = @file_get_contents($this->cache_file);
        if (!$data) {
            return false;
        }
        
        $balances_info = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($balances_info['timestamp'])) {
            return false;
        }
        
        $spot_total_balance = $balances_info['total_spot'] ?? 0;
        $futures_total_balance = $balances_info['total_futures'] ?? 0;
        $margin_total_balance = ($balances_info['total_margin'] ?? 0) + ($balances_info['total_isolated'] ?? 0);
        $total_balance = $spot_total_balance + $futures_total_balance + $margin_total_balance;
        
        return [
            'success' => true,
            'total_balance' => $total_balance,
            'usdt_balance' => $spot_total_balance, // USDT bakiye yaklaşık olarak spot toplam olarak varsayıldı
            'spot' => [
                'total_balance' => $spot_total_balance
            ],
            'futures' => [
                'total_balance' => $futures_total_balance
            ],
            'margin' => [
                'total_balance' => $margin_total_balance
            ],
            'source' => 'python_script',
            'timestamp' => $balances_info['timestamp'],
            'wallet_btc_value' => $balances_info['wallet_btc_value'] ?? 0
        ];
    }
    
    /**
     * Bakiyeleri API endpoint'i olarak döndür
     * AJAX çağrıları için kullanılır
     */
    public function getBalancesAsJson() {
        header('Content-Type: application/json');
        $balances = $this->getAllBalances();
        
        if (!$balances || !isset($balances['success']) || !$balances['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Bakiye bilgileri alınamadı.'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $balances,
            'timestamp' => time()
        ]);
    }
}

// API endpoint olarak kullanılıyorsa
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    // AJAX çağrısı olarak kullanılıyor
    require_once __DIR__ . '/binance_api.php';
    $binance_api = new BinanceAPI();
    $balance_api = new BalanceAPI($binance_api);
    $balance_api->getBalancesAsJson();
}
