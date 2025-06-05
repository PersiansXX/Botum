<?php
/**
 * Trading Bot Merkezi Konfigürasyon Dosyası
 * Bu dosya tüm sistemde kullanılacak konfigürasyon değişkenlerini içerir
 */

// Veritabanı Bağlantı Bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Efsane44.');
define('DB_NAME', 'trading_bot_db');

// Sistem Yolları - CentOS için
define('ROOT_DIR', '/var/www/html');
define('BOT_DIR', ROOT_DIR . '/bot');
define('CONFIG_DIR', ROOT_DIR . '/config');

// Bot Dosya ve Script Konumları
define('LOG_FILE', BOT_DIR . '/bot.log');
define('ERROR_LOG', BOT_DIR . '/bot_error.log');
define('PID_FILE', BOT_DIR . '/bot.pid');
define('MANUALLY_STOPPED_FILE', BOT_DIR . '/bot_manually_stopped');
define('START_SCRIPT', ROOT_DIR . '/start_bot.sh');
define('STOP_SCRIPT', ROOT_DIR . '/stop_trading_bot.sh');
define('BOT_CONFIG_FILE', CONFIG_DIR . '/bot_config.json');

// Python ve Bot Ayarları
define('PYTHON_PATH', '/usr/bin/python3');
define('BOT_SCRIPT', 'trading_bot.py');
define('TRADING_USER', 'tradingbot');

// Debug modu
define('DEBUG_MODE', true);

// Sistem fonksiyonları
function debug_log($message) {
    if (DEBUG_MODE) {
        error_log($message);
    }
}

/**
 * Veritabanı bağlantısı oluşturur ve döndürür
 * @return mysqli Bağlantı nesnesi
 */
function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        debug_log("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
        die("Veritabanı bağlantısı başarısız. Lütfen sistem yöneticisine başvurun.");
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Bir sürecin çalışıp çalışmadığını kontrol eder (CentOS 7 için optimize edilmiş)
 * 
 * @param int $pid Proses ID
 * @return bool Proses çalışıyor mu
 */
function is_process_running($pid) {
    if (empty($pid)) return false;
    
    // CentOS 7 için proses kontrolü - ps ile kontrol
    $output = [];
    exec("ps -p " . escapeshellarg($pid) . " -o pid --no-headers 2>/dev/null", $output);
    
    if (!empty($output) && trim($output[0]) == $pid) {
        return true;
    }
    
    // /proc klasörü ile alternatif kontrol
    if (file_exists("/proc/$pid")) {
        return true;
    }
    
    return false;
}

/**
 * Sistem komutunu güvenli şekilde çalıştırır ve sonuçları döndürür
 * 
 * @param string $command Çalıştırılacak komut
 * @return array [çıktı, dönüş_kodu]
 */
function run_system_command($command) {
    $output = [];
    $return_var = 0;
    
    exec($command . " 2>&1", $output, $return_var);
    
    debug_log("Komut çalıştırıldı: $command");
    debug_log("Dönüş kodu: $return_var");
    
    return [$output, $return_var];
}

/**
 * Belirtilen dosyanın izinlerini ayarlar
 * 
 * @param string $filepath Dosya yolu
 * @param int $mode İzin modu (örn. 0644)
 * @return bool Başarılı mı
 */
function set_file_permissions($filepath, $mode = 0644) {
    if (file_exists($filepath)) {
        return @chmod($filepath, $mode);
    }
    return false;
}
?>