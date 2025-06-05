<?php
/**
 * API Yapılandırma Dosyası
 * Bu dosya API anahtarları gibi hassas bilgileri saklar
 * GIT veya başka versiyonlama sistemlerine eklenmemelidir!
 */

// Doğrudan erişimi engelle
if (!defined('TRADING_BOT')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Binance API bilgileri
$binance_api_config = [
    'api_key' => '', // API anahtarınızı buraya girin
    'api_secret' => '', // API gizli anahtarınızı buraya girin
    'test_mode' => true
];

// API bilgilerini döndür
function get_api_credentials($exchange = 'binance') {
    global $binance_api_config;
    
    switch($exchange) {
        case 'binance':
            return $binance_api_config;
        default:
            return null;
    }
}

/**
 * Önbelleğe alınmış API anahtarlarını döndürür
 * Frontend'e API anahtarlarının gitmesini önler
 * 
 * @param string $exchange Borsa adı
 * @param bool $public_only Sadece genel bilgileri içersin mi
 * @return array API yapılandırması
 */
function get_api_config($exchange = 'binance', $public_only = false) {
    $config = get_api_credentials($exchange);
    
    // Eğer sadece genel bilgiler isteniyorsa hassas verileri temizle
    if ($public_only && is_array($config)) {
        // API anahtarlarını maskele
        if (isset($config['api_key']) && !empty($config['api_key'])) {
            $config['api_key'] = '***HIDDEN***';
        }
        if (isset($config['api_secret']) && !empty($config['api_secret'])) {
            $config['api_secret'] = '***HIDDEN***';
        }
        // Diğer olası hassas verileri maskele
        foreach ($config as $key => $value) {
            if (strpos(strtolower($key), 'secret') !== false || 
                strpos(strtolower($key), 'key') !== false || 
                strpos(strtolower($key), 'password') !== false || 
                strpos(strtolower($key), 'token') !== false) {
                if (!empty($value)) {
                    $config[$key] = '***HIDDEN***';
                }
            }
        }
    }
    
    return $config;
}

/**
 * API anahtarlarını ayarlar
 * 
 * @param array $credentials API kimlik bilgileri
 * @param string $exchange Borsa adı
 * @return bool İşlem başarılı mı
 */
function set_api_credentials($credentials, $exchange = 'binance') {
    global $binance_api_config;
    
    if (!is_array($credentials)) {
        return false;
    }
    
    switch($exchange) {
        case 'binance':
            // Sadece belirli anahtarları güncelle
            if (isset($credentials['api_key'])) {
                $binance_api_config['api_key'] = trim($credentials['api_key']);
            }
            if (isset($credentials['api_secret'])) {
                $binance_api_config['api_secret'] = trim($credentials['api_secret']);
            }
            if (isset($credentials['test_mode'])) {
                $binance_api_config['test_mode'] = (bool)$credentials['test_mode'];
            }
            
            // Dosyaya kaydetme fonksiyonu buraya eklenebilir
            // save_api_config();
            
            return true;
        default:
            return false;
    }
}
