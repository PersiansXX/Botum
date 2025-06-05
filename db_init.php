<?php
// MySQL veritabanı için tablo oluşturma scripti
// Bu script, trading bot için gerekli tabloları oluşturur

// Hata raporlama
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Veritabanı bağlantısı
require_once 'web/includes/db_connect.php';

// Veritabanı bağlantısı için parametreler
$db_host = "localhost";
$db_user = "root";  // Kendi kullanıcı adınızı kullanın
$db_pass = "Efsane44.";  // Kendi şifrenizi kullanın
$db_name = "trading_bot_db";

// Bağlantı oluşturma
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Başarılı bağlantı mesajı
echo "Veritabanı bağlantısı başarılı!\n";
echo "Tablolar oluşturuluyor...\n";

// UTF-8 karakter seti
$conn->set_charset("utf8mb4");

// ========== TÜM TABLOLARI OLUŞTUR ==========

// bot_settings tablosu - Bot ayarları için
$sql_bot_settings = "
CREATE TABLE IF NOT EXISTS bot_settings (
    id INT PRIMARY KEY DEFAULT 1,
    settings LONGTEXT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// active_coins tablosu - Aktif izlenen coinler için
$sql_active_coins = "
CREATE TABLE IF NOT EXISTS active_coins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL UNIQUE,
    signal VARCHAR(20) DEFAULT 'NEUTRAL',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// discovered_coins tablosu - Keşfedilen coinler için
$sql_discovered_coins = "
CREATE TABLE IF NOT EXISTS discovered_coins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL UNIQUE,
    price DECIMAL(20, 8) NOT NULL,
    price_change_pct DECIMAL(10, 2) DEFAULT 0,
    volume_usd DECIMAL(20, 2) DEFAULT 0,
    buy_signals INT DEFAULT 0,
    sell_signals INT DEFAULT 0,
    trade_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    discovery_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// coin_prices tablosu - Coin fiyat bilgileri için
$sql_coin_prices = "
CREATE TABLE IF NOT EXISTS coin_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    volume DECIMAL(30, 8) DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(symbol, timestamp)
)";

// coin_analysis tablosu - Coin analiz sonuçları için
$sql_coin_analysis = "
CREATE TABLE IF NOT EXISTS coin_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    timeframe VARCHAR(10) DEFAULT '5m',
    rsi_value DECIMAL(10, 2) DEFAULT NULL,
    rsi_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    macd_value DECIMAL(10, 4) DEFAULT NULL,
    macd_signal_line DECIMAL(10, 4) DEFAULT NULL,
    macd_histogram DECIMAL(10, 4) DEFAULT NULL,
    macd_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    bollinger_upper DECIMAL(20, 8) DEFAULT NULL,
    bollinger_middle DECIMAL(20, 8) DEFAULT NULL,
    bollinger_lower DECIMAL(20, 8) DEFAULT NULL,
    bollinger_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    ma20 DECIMAL(20, 8) DEFAULT NULL,
    ma50 DECIMAL(20, 8) DEFAULT NULL,
    ma100 DECIMAL(20, 8) DEFAULT NULL,
    ma200 DECIMAL(20, 8) DEFAULT NULL,
    ma_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    supertrend_value DECIMAL(20, 8) DEFAULT NULL,
    supertrend_direction VARCHAR(10) DEFAULT NULL,
    supertrend_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    overall_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    indicators_json LONGTEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(symbol, timestamp)
)";

// price_analysis tablosu - Fiyat analiz sonuçları için
$sql_price_analysis = "
CREATE TABLE IF NOT EXISTS price_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    timeframe VARCHAR(10) DEFAULT '5m',
    rsi DECIMAL(10, 2) DEFAULT NULL,
    macd DECIMAL(10, 4) DEFAULT NULL,
    macd_signal DECIMAL(10, 4) DEFAULT NULL,
    macd_histogram DECIMAL(10, 4) DEFAULT NULL,
    bollinger_upper DECIMAL(20, 8) DEFAULT NULL,
    bollinger_middle DECIMAL(20, 8) DEFAULT NULL,
    bollinger_lower DECIMAL(20, 8) DEFAULT NULL,
    trade_signal VARCHAR(20) DEFAULT 'NEUTRAL',
    analysis_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(symbol, analysis_time)
)";

// trades tablosu - Gerçekleşen işlemler için
$sql_trades = "
CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL,
    type VARCHAR(10) NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    amount DECIMAL(20, 8) NOT NULL,
    total_value DECIMAL(20, 8) NOT NULL,
    fee DECIMAL(20, 8) DEFAULT 0,
    strategy VARCHAR(50),
    profit_loss DECIMAL(20, 8) DEFAULT 0,
    profit_loss_pct DECIMAL(10, 2) DEFAULT 0,
    entry_price DECIMAL(20, 8) DEFAULT NULL,
    entry_time DATETIME DEFAULT NULL,
    trade_reason TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(symbol, timestamp)
)";

// open_positions tablosu - Açık pozisyonlar için
$sql_open_positions = "
CREATE TABLE IF NOT EXISTS open_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(50) NOT NULL,
    entry_price DECIMAL(20, 8) NOT NULL,
    amount DECIMAL(20, 8) NOT NULL,
    total_value DECIMAL(20, 8) NOT NULL,
    current_price DECIMAL(20, 8) DEFAULT NULL,
    current_value DECIMAL(20, 8) DEFAULT NULL,
    profit_loss DECIMAL(20, 8) DEFAULT 0,
    profit_loss_pct DECIMAL(10, 2) DEFAULT 0,
    position_type VARCHAR(10) DEFAULT 'LONG',
    stop_loss DECIMAL(20, 8) DEFAULT NULL,
    take_profit DECIMAL(20, 8) DEFAULT NULL,
    trailing_stop_active TINYINT(1) DEFAULT 0,
    trailing_stop_value DECIMAL(20, 8) DEFAULT NULL,
    strategy VARCHAR(50),
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(symbol)
)";

// errors_log tablosu - Hata kayıtları için
$sql_errors_log = "
CREATE TABLE IF NOT EXISTS errors_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_type VARCHAR(50) NOT NULL,
    error_message TEXT NOT NULL,
    error_context TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// bot_status tablosu - Bot durumu için
$sql_bot_status = "
CREATE TABLE IF NOT EXISTS bot_status (
    id INT PRIMARY KEY DEFAULT 1,
    running TINYINT(1) DEFAULT 0,
    pid INT DEFAULT NULL,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    memory_usage VARCHAR(50) DEFAULT NULL,
    cpu_usage VARCHAR(50) DEFAULT NULL,
    status_message VARCHAR(255) DEFAULT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Tablo oluşturma sorgularını bir dizide tutalım
$tables = [
    'bot_settings' => $sql_bot_settings,
    'active_coins' => $sql_active_coins,
    'discovered_coins' => $sql_discovered_coins,
    'coin_prices' => $sql_coin_prices,
    'coin_analysis' => $sql_coin_analysis,
    'price_analysis' => $sql_price_analysis,
    'trades' => $sql_trades,
    'open_positions' => $sql_open_positions,
    'errors_log' => $sql_errors_log,
    'bot_status' => $sql_bot_status
];

// Tabloları oluştur
$success_count = 0;
$error_count = 0;

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "$table_name tablosu başarıyla oluşturuldu veya zaten var!\n";
        $success_count++;
    } else {
        echo "HATA: $table_name tablosu oluşturulamadı: " . $conn->error . "\n";
        $error_count++;
    }
}

// Varsayılan bot ayarları için JSON
$default_settings = [
    'exchange' => 'binance',
    'base_currency' => 'USDT',
    'trade_mode' => 'paper',
    'position_size' => 0.02,
    'min_volume' => 1000000,
    'max_coins' => 10,
    'min_trade_amount' => 10,
    'max_trade_amount' => 1000,
    'api_delay' => 1.0,
    'scan_interval' => 300,
    'use_tradingview' => false,
    'trade_direction' => 'both',
    'indicators' => [
        'bollinger_bands' => [
            'enabled' => true,
            'window' => 20,
            'num_std' => 2.0
        ],
        'rsi' => [
            'enabled' => true,
            'window' => 14
        ],
        'macd' => [
            'enabled' => true,
            'fast_period' => 12,
            'slow_period' => 26,
            'signal_period' => 9
        ],
        'moving_average' => [
            'enabled' => true,
            'short_window' => 9,
            'long_window' => 21
        ],
        'supertrend' => [
            'enabled' => true,
            'period' => 10,
            'multiplier' => 3.0
        ],
        'vwap' => [
            'enabled' => false,
            'period' => 14
        ],
        'pivot_points' => [
            'enabled' => false,
            'method' => 'standard'
        ],
        'fibonacci' => [
            'enabled' => false,
            'period' => 100
        ],
        'stochastic' => [
            'enabled' => false,
            'k_period' => 14,
            'd_period' => 3,
            'slowing' => 3
        ]
    ],
    'strategies' => [
        'short_term' => [
            'enabled' => true
        ],
        'trend_following' => [
            'enabled' => true
        ],
        'breakout' => [
            'enabled' => true
        ],
        'volatility_breakout' => [
            'enabled' => false
        ]
    ],
    'risk_management' => [
        'enabled' => true,
        'stop_loss' => 5.0,
        'take_profit' => 10.0,
        'trailing_stop' => true,
        'trailing_stop_distance' => 2.0,
        'trailing_stop_activation_pct' => 3.0,
        'trailing_stop_pct' => 1.5,
        'max_open_positions' => 5,
        'max_risk_per_trade' => 2.0
    ],
    'backtesting' => [
        'default_start_date' => date('Y-m-d', strtotime('-30 days')),
        'default_end_date' => date('Y-m-d'),
        'initial_capital' => 1000.0,
        'trading_fee' => 0.1,
        'slippage' => 0.05,
        'enable_visualization' => true
    ],
    'auto_discovery' => [
        'enabled' => true,
        'discovery_interval' => 3600,
        'min_volume_for_discovery' => 500000,
        'min_price_change' => 3.0,
        'min_volume_change' => 20.0,
        'max_coins_to_discover' => 5,
        'auto_add_to_watchlist' => true
    ]
];

// Varsayılan ayarları ekle (eğer tablo boşsa)
$check_settings = "SELECT COUNT(*) as count FROM bot_settings";
$result = $conn->query($check_settings);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $json_settings = json_encode($default_settings, JSON_PRETTY_PRINT);
    $insert_settings = "INSERT INTO bot_settings (id, settings) VALUES (1, ?)";
    
    $stmt = $conn->prepare($insert_settings);
    $stmt->bind_param("s", $json_settings);
    
    if ($stmt->execute()) {
        echo "Varsayılan bot ayarları veritabanına eklendi!\n";
    } else {
        echo "HATA: Varsayılan ayarlar eklenemedi: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "Bot ayarları zaten veritabanında mevcut!\n";
}

// Özet raporu
echo "\n===== KURULUM ÖZET RAPORU =====\n";
echo "Toplam tablo sayısı: " . count($tables) . "\n";
echo "Başarıyla oluşturulan tablolar: $success_count\n";
echo "Hata olan tablolar: $error_count\n";

if ($error_count == 0) {
    echo "\nKurulum başarıyla tamamlandı! Sistemi kullanmaya başlayabilirsiniz.\n";
} else {
    echo "\nKurulum tamamlandı ancak bazı hatalar mevcut. Lütfen hata mesajlarını kontrol edin.\n";
}

// Bağlantıyı kapat
$conn->close();
?>