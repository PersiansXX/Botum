<?php
// Hata ayıklama için PHP hata raporlamasını etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

// Bot API'ye bağlan
require_once 'api/bot_api.php';
$bot_api = new BotAPI();

// Tab seçimi
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Güvenli tab seçimi
$allowed_tabs = ['general', 'timeframes', 'auto_discovery', 'indicators', 'indicator_signals', 
                'strategies', 'risk_management', 'risk_management_advanced', 
                'backtesting', 'smart_trend', 'adaptive_parameters', 
                'api_optimization', 'api_settings', 'notifications_logging'];

if (!in_array($tab, $allowed_tabs)) {
    $tab = 'general';
}

// Mevcut ayarları al
$settings = $bot_api->getSettings();

// POST işlemi kontrolü
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: POST verilerini logla
    error_log("POST verileri: " . print_r($_POST, true));
    
    // Formdan gelen verileri doğrula ve güncelle
    try {
        $updated_settings = [
            'exchange' => $_POST['exchange'],
            'base_currency' => $_POST['base_currency'],
            'min_volume' => (float) $_POST['min_volume'],
            'max_coins' => (int) $_POST['max_coins'],
            'min_trade_amount' => (float) $_POST['min_trade_amount'],
            'max_trade_amount' => (float) $_POST['max_trade_amount'],
            'position_size' => (float) $_POST['position_size'],
            'api_delay' => (float) $_POST['api_delay'],
            'scan_interval' => (int) $_POST['scan_interval'],
            'use_tradingview' => isset($_POST['use_tradingview']),
            'tradingview_exchange' => $_POST['tradingview_exchange'],
            
            // Çoklu zaman aralığı ayarları
            'timeframes' => isset($_POST['timeframes']) ? $_POST['timeframes'] : ['1h'],
            'primary_timeframe' => $_POST['primary_timeframe'],
            'timeframe_weight_short' => (int) $_POST['timeframe_weight_short'],
            'timeframe_weight_medium' => (int) $_POST['timeframe_weight_medium'],
            'timeframe_weight_long' => (int) $_POST['timeframe_weight_long'],
            'timeframe_consensus' => $_POST['timeframe_consensus'],
            
            // Akıllı Trend Analizi ayarları
            'integration_settings' => [
                'use_smart_trend' => isset($_POST['use_smart_trend']),
                'smart_trend_settings' => [
                    'detection_method' => $_POST['trend_detection_method'],
                    'sensitivity' => (float) $_POST['trend_sensitivity'],
                    'lookback_period' => (int) $_POST['trend_lookback_period'],
                    'confirmation_period' => (int) $_POST['trend_confirmation_period'],
                    'signal_quality_threshold' => (float) $_POST['signal_quality_threshold']
                ]
            ],
              // Gelişmiş Risk Yönetimi ayarları
            'advanced_risk_management' => [
                'enabled' => isset($_POST['advanced_risk_enabled']),
                'dynamic_position_sizing' => isset($_POST['dynamic_position_sizing']),
                'position_size_method' => isset($_POST['position_size_method']) ? $_POST['position_size_method'] : (isset($settings['advanced_risk_management']['position_size_method']) ? $settings['advanced_risk_management']['position_size_method'] : 'fixed'),
                'max_risk_per_trade' => (float) $_POST['max_risk_per_trade'],
                'volatility_based_stops' => isset($_POST['volatility_based_stops']),
                'adaptive_take_profit' => isset($_POST['adaptive_take_profit']),
                'auto_adjust_risk' => isset($_POST['auto_adjust_risk']),
                'max_open_positions' => (int) $_POST['max_open_positions']
            ],
            
            // Adaptif Parametreler ayarları
            'adaptive_parameters' => [
                'enabled' => isset($_POST['adaptive_params_enabled']),
                'adaptation_speed' => (float) $_POST['adaptation_speed'],
                'market_regime_detection' => isset($_POST['market_regime_detection']),
                'volatility_adjustment' => isset($_POST['volatility_adjustment']),
                'trend_strength_adjustment' => isset($_POST['trend_strength_adjustment']),
                'reset_after_market_shift' => isset($_POST['reset_after_market_shift']),
                'learning_rate' => (float) $_POST['learning_rate'],
                'min_history_required' => (int) $_POST['min_history_required']
            ],
            
            // API Optimizasyonu ayarları
            'api_optimization' => [
                'enabled' => isset($_POST['api_optimization_enabled']),
                'api_call_limit_per_minute' => (int) $_POST['api_call_limit_per_minute'],
                'api_call_distribution' => $_POST['api_call_distribution'],
                'cache_duration' => (int) $_POST['cache_duration'],
                'prioritize_active_trades' => isset($_POST['prioritize_active_trades']),
                'reduce_timeframes_count' => isset($_POST['reduce_timeframes_count']),
                'optimize_indicator_calculations' => isset($_POST['optimize_indicator_calculations'])
            ],
            
            // Bildirimler ayarları
            'notifications' => [
                'telegram' => [
                    'enabled' => isset($_POST['telegram_enabled']),
                    'bot_token' => isset($_POST['telegram_bot_token']) ? $_POST['telegram_bot_token'] : '',
                    'chat_id' => isset($_POST['telegram_chat_id']) ? $_POST['telegram_chat_id'] : '',
                    'message_format' => isset($_POST['telegram_message_format']) ? $_POST['telegram_message_format'] : 'simple',
                    'rate_limit' => isset($_POST['telegram_rate_limit']) ? (int) $_POST['telegram_rate_limit'] : 1,
                    'types' => [
                        'trades' => isset($_POST['telegram_trades']),
                        'errors' => isset($_POST['telegram_errors']),
                        'profits' => isset($_POST['telegram_profits']),
                        'status' => isset($_POST['telegram_status'])
                    ]
                ],
                'email' => [
                    'enabled' => isset($_POST['email_enabled']),
                    'smtp_host' => isset($_POST['email_smtp_host']) ? $_POST['email_smtp_host'] : 'smtp.gmail.com',
                    'smtp_port' => isset($_POST['email_smtp_port']) ? (int) $_POST['email_smtp_port'] : 587,
                    'username' => isset($_POST['email_username']) ? $_POST['email_username'] : '',
                    'password' => isset($_POST['email_password']) ? $_POST['email_password'] : '',
                    'recipients' => !empty($_POST['email_recipients']) ? array_map('trim', explode(',', $_POST['email_recipients'])) : [],
                    'types' => [
                        'critical' => isset($_POST['email_critical']),
                        'daily_reports' => isset($_POST['email_daily_reports']),
                        'weekly_reports' => isset($_POST['email_weekly_reports']),
                        'system_status' => isset($_POST['email_system_status'])
                    ]
                ]
            ],
            
            // Günlükleme ayarları
            'logging' => [
                'level' => isset($_POST['log_level']) ? $_POST['log_level'] : 'INFO',
                'max_file_size' => isset($_POST['log_file_size']) ? (int) $_POST['log_file_size'] : 10,
                'retention_days' => isset($_POST['log_retention_days']) ? (int) $_POST['log_retention_days'] : 30,
                'format' => isset($_POST['log_format']) ? $_POST['log_format'] : 'simple',
                'backup_count' => isset($_POST['log_backup_count']) ? (int) $_POST['log_backup_count'] : 5,
                'rotation' => isset($_POST['log_rotation']),
                'compression' => isset($_POST['log_compression']),
                'categories' => [
                    'trades' => isset($_POST['log_trades']),
                    'indicators' => isset($_POST['log_indicators']),
                    'api' => isset($_POST['log_api']),
                    'errors' => isset($_POST['log_errors'])
                ]
            ],
            
            // Performans izleme ayarları
            'monitoring' => [
                'performance_interval' => isset($_POST['performance_monitoring_interval']) ? (int) $_POST['performance_monitoring_interval'] : 60,
                'memory_threshold' => isset($_POST['memory_usage_threshold']) ? (int) $_POST['memory_usage_threshold'] : 80,
                'cpu_monitoring' => isset($_POST['cpu_monitoring']),
                'disk_monitoring' => isset($_POST['disk_monitoring'])
            ],
            
            // Yeni eklenen otomatik coin keşfetme ayarları
            'auto_discovery' => [
                'enabled' => isset($_POST['auto_discovery_enabled']),
                'discovery_interval' => (int) $_POST['discovery_interval'],
                'min_volume_for_discovery' => (float) $_POST['min_volume_for_discovery'],
                'min_price_change' => (float) $_POST['min_price_change'],
                'min_volume_change' => (float) $_POST['min_volume_change'],
                'max_coins_to_discover' => (int) $_POST['max_coins_to_discover'],
                'auto_add_to_watchlist' => isset($_POST['auto_add_to_watchlist'])
            ],
            
            'indicators' => [
                'bollinger_bands' => [
                    'enabled' => isset($_POST['bb_enabled']),
                    'window' => (int) $_POST['bb_window'],
                    'num_std' => (float) $_POST['bb_num_std']
                ],
                'rsi' => [
                    'enabled' => isset($_POST['rsi_enabled']),
                    'window' => (int) $_POST['rsi_window']
                ],
                'macd' => [
                    'enabled' => isset($_POST['macd_enabled']),
                    'fast_period' => (int) $_POST['macd_fast'],
                    'slow_period' => (int) $_POST['macd_slow'],
                    'signal_period' => (int) $_POST['macd_signal']
                ],
                'moving_average' => [
                    'enabled' => isset($_POST['ma_enabled']),
                    'short_window' => (int) $_POST['ma_short'],
                    'long_window' => (int) $_POST['ma_long']
                ],
                'supertrend' => [
                    'enabled' => isset($_POST['supertrend_enabled']),
                    'period' => (int) $_POST['supertrend_period'],
                    'multiplier' => (float) $_POST['supertrend_multiplier']
                ],
                'vwap' => [
                    'enabled' => isset($_POST['vwap_enabled']),
                    'period' => isset($_POST['vwap_period']) ? (int) $_POST['vwap_period'] : 14
                ],
                'pivot_points' => [
                    'enabled' => isset($_POST['pivot_points_enabled']),
                    'method' => isset($_POST['pivot_points_method']) ? $_POST['pivot_points_method'] : 'standard'
                ],
                'fibonacci' => [
                    'enabled' => isset($_POST['fibonacci_enabled']),
                    'period' => isset($_POST['fibonacci_period']) ? (int) $_POST['fibonacci_period'] : 20
                ],
                'stochastic' => [
                    'enabled' => isset($_POST['stochastic_enabled']),
                    'k_period' => (int) $_POST['stochastic_k_period'],
                    'd_period' => (int) $_POST['stochastic_d_period'],
                    'slowing' => (int) $_POST['stochastic_slowing']
                ]
            ],
            'strategies' => [
                'short_term' => [
                    'enabled' => isset($_POST['short_term_enabled'])
                ],
                'trend_following' => [
                    'enabled' => isset($_POST['trend_following_enabled'])
                ],
                'breakout' => [
                    'enabled' => isset($_POST['breakout_enabled'])
                ],
                // Yeni volatilite kırılma stratejisi
                'volatility_breakout' => [
                    'enabled' => isset($_POST['volatility_breakout_enabled'])
                ]
            ],
            'risk_management' => [
                'enabled' => isset($_POST['risk_enabled']),
                
                // Genel Risk Ayarları
                'max_portfolio_risk' => isset($_POST['max_portfolio_risk']) ? (float) $_POST['max_portfolio_risk'] : 5.0,
                'max_position_size' => isset($_POST['max_position_size']) ? (float) $_POST['max_position_size'] : 10.0,
                'max_daily_loss' => isset($_POST['max_daily_loss']) ? (float) $_POST['max_daily_loss'] : 3.0,
                'max_consecutive_losses' => isset($_POST['max_consecutive_losses']) ? (int) $_POST['max_consecutive_losses'] : 5,
                'recovery_mode_threshold' => isset($_POST['recovery_mode_threshold']) ? (float) $_POST['recovery_mode_threshold'] : 2.0,
                'enable_drawdown_protection' => isset($_POST['enable_drawdown_protection']),
                'emergency_stop' => isset($_POST['emergency_stop']),
                
                // Stop Loss Ayarları
                'stop_loss' => [
                    'default_percentage' => isset($_POST['default_stop_loss']) ? (float) $_POST['default_stop_loss'] : 2.0,
                    'max_percentage' => isset($_POST['max_stop_loss']) ? (float) $_POST['max_stop_loss'] : 5.0,
                    'type' => isset($_POST['stop_loss_type']) ? $_POST['stop_loss_type'] : 'percentage',
                    'trailing_stop' => isset($_POST['trailing_stop']),
                    'trailing_distance' => isset($_POST['trailing_distance']) ? (float) $_POST['trailing_distance'] : 1.0
                ],
                
                // Take Profit Ayarları
                'take_profit' => [
                    'default_percentage' => isset($_POST['default_take_profit']) ? (float) $_POST['default_take_profit'] : 4.0,
                    'risk_reward_ratio' => isset($_POST['risk_reward_ratio']) ? (float) $_POST['risk_reward_ratio'] : 2.0,
                    'partial_percentage' => isset($_POST['partial_take_profit']) ? (int) $_POST['partial_take_profit'] : 50,
                    'dynamic' => isset($_POST['dynamic_take_profit']),
                    'scale_out' => isset($_POST['scale_out'])
                ],
                
                // Pozisyon Yönetimi
                'position_management' => [
                    'max_open_positions' => isset($_POST['max_open_positions']) ? (int) $_POST['max_open_positions'] : 5,
                    'max_positions_per_coin' => isset($_POST['max_positions_per_coin']) ? (int) $_POST['max_positions_per_coin'] : 1,
                    'sizing_method' => isset($_POST['position_sizing_method']) ? $_POST['position_sizing_method'] : 'fixed_percentage',
                    'correlation_limit' => isset($_POST['correlation_limit']) ? (float) $_POST['correlation_limit'] : 0.7,
                    'diversification_check' => isset($_POST['diversification_check']),
                    'sector_allocation' => isset($_POST['sector_allocation'])
                ],
                
                // Geriye uyumluluk için eski alanlar
                'max_risk_per_trade' => isset($_POST['max_risk_per_trade']) ? (float) $_POST['max_risk_per_trade'] : 0.02,
                'trailing_stop_distance' => isset($_POST['trailing_stop_distance']) ? (float) $_POST['trailing_stop_distance'] : (isset($settings['risk_management']['trailing_stop_distance']) ? $settings['risk_management']['trailing_stop_distance'] : 0.01),
                'trailing_stop_activation_pct' => isset($_POST['trailing_stop_activation_pct']) ? (float) $_POST['trailing_stop_activation_pct'] : (isset($settings['risk_management']['trailing_stop_activation_pct']) ? $settings['risk_management']['trailing_stop_activation_pct'] : 0.02),
                'trailing_stop_pct' => isset($_POST['trailing_stop_pct']) ? (float) $_POST['trailing_stop_pct'] : (isset($settings['risk_management']['trailing_stop_pct']) ? $settings['risk_management']['trailing_stop_pct'] : 0.01)
            ],
            'backtesting' => [
                'default_start_date' => $_POST['default_start_date'],
                'default_end_date' => $_POST['default_end_date'],
                'initial_capital' => (float) $_POST['initial_capital'],
                'trading_fee' => (float) $_POST['trading_fee'],
                'slippage' => (float) $_POST['slippage'],
                'enable_visualization' => isset($_POST['enable_visualization'])
            ],
            // Yeni eklenen Telegram ayarları
            'telegram' => [
                'enabled' => isset($_POST['telegram_enabled']),
                'token' => $_POST['telegram_token'],
                'chat_id' => $_POST['telegram_chat_id'],
                'trade_signals' => isset($_POST['telegram_trade_signals']),
                'discovered_coins' => isset($_POST['telegram_discovered_coins'])
            ],
            // Yeni eklenen işlem modu ve ayarları
            'trade_mode' => $_POST['trade_mode'],
            'market_type' => $_POST['market_type'],
            'auto_trade' => isset($_POST['auto_trade']),
            'trade_direction' => $_POST['trade_direction'],
            // Yeni eklenen kaldıraç ayarları
            'leverage' => (int) $_POST['leverage'],
            'leverage_mode' => $_POST['leverage_mode'],
            // API anahtarları
            'api_keys' => [
                'binance_api_key' => $_POST['binance_api_key'],
                'binance_api_secret' => $_POST['binance_api_secret'],
                'kucoin_api_key' => $_POST['kucoin_api_key'],
                'kucoin_api_secret' => $_POST['kucoin_api_secret'],
                'kucoin_api_passphrase' => $_POST['kucoin_api_passphrase']
            ],
            // İndikatör sinyalleri ve ağırlıkları için yeni eklenen ayarlar
            'indicator_weights' => [
                'rsi' => (int) $_POST['rsi_weight'],
                'macd' => (int) $_POST['macd_weight'],
                'bollinger_bands' => (int) $_POST['bollinger_weight'],
                'moving_average' => (int) $_POST['ma_weight'],
                'supertrend' => (int) $_POST['supertrend_weight'],
                'stochastic' => (int) $_POST['stochastic_weight'],
                'adx' => (int) $_POST['adx_weight'],
                'other' => (int) $_POST['other_weight']
            ],
            'signal_consensus_method' => $_POST['signal_consensus_method'],
            'signal_consensus_threshold' => (int) $_POST['signal_consensus_threshold'],
            'signal_confirmation_count' => (int) $_POST['signal_confirmation_count'],
            'signal_conflicting_action' => $_POST['signal_conflicting_action'],
            'indicator_signals' => [
                'rsi' => [
                    'oversold' => (int) $_POST['rsi_oversold'],
                    'overbought' => (int) $_POST['rsi_overbought'],
                    'center_line_cross' => isset($_POST['rsi_center_line_cross']),
                    'divergence' => isset($_POST['rsi_divergence'])
                ],
                'bollinger_bands' => [
                    'squeeze_threshold' => (float) $_POST['bb_squeeze_threshold'],
                    'breakout_confirmation_candles' => (int) $_POST['bb_breakout_confirmation_candles'],
                    'use_percentage_b' => isset($_POST['bb_percentage_b']),
                    'mean_reversion' => isset($_POST['bb_mean_reversion'])
                ],
                'macd' => [
                    'signal_strength' => (float) $_POST['macd_signal_strength'],
                    'zero_line_cross' => isset($_POST['macd_zero_line_cross']),
                    'histogram_divergence' => isset($_POST['macd_histogram_divergence']),
                    'trigger_type' => $_POST['macd_trigger_type']
                ],
                'supertrend' => [
                    'confirmation_candles' => (int) $_POST['supertrend_confirmation_candles'],
                    'filter_adx' => isset($_POST['supertrend_filter_adx']),
                    'adx_threshold' => isset($_POST['supertrend_adx_threshold']) ? (int) $_POST['supertrend_adx_threshold'] : (isset($settings['indicator_signals']['supertrend']['adx_threshold']) ? $settings['indicator_signals']['supertrend']['adx_threshold'] : 25)
                ]
            ]
        ];
        
        // Debug: Güncellenecek ayarları logla
        error_log("Güncellenecek ayarlar: " . json_encode($updated_settings, JSON_PRETTY_PRINT));
        
        // Ayarları güncelle
        $update_result = $bot_api->updateSettings($updated_settings);
        
        // Debug: Güncelleme sonucunu logla
        error_log("Güncelleme sonucu: " . ($update_result ? 'BAŞARILI' : 'BAŞARISIZ'));
        
        if ($update_result) {
            $message = 'Ayarlar başarıyla güncellendi! (' . date('Y-m-d H:i:s') . ')';
            $message_type = 'success';
            
            // Cache'i temizle
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            // Ayarları yeniden yükle
            $settings = $bot_api->getSettings(); 
            
            // Debug: Yeni ayarları logla
            error_log("Yeni ayarlar: " . json_encode($settings, JSON_PRETTY_PRINT));
            
        } else {
            $message = 'Ayarlar güncellenirken bir hata oluştu! Lütfen veritabanı bağlantısını kontrol edin.';
            $message_type = 'danger';
            
            // Detaylı hata bilgisi
            error_log("AYAR GÜNCELLEMESİ BAŞARISIZ - Zaman: " . date('Y-m-d H:i:s'));
        }
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $message_type = 'danger';
        error_log("AYAR GÜNCELLEME HATASI: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    }
}

// Sayfa başlığı
$page_title = 'Bot Ayarları';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/settings.css?v=<?php echo time(); ?>">
    
    <!-- jQuery ve Bootstrap JS dosyaları -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sol Menü -->
            <div class="col-md-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-10 settings-wrapper">
                <div class="settings-card">
                    <div class="settings-header">
                        <h5><i class="fas fa-cogs"></i> Bot Ayarları</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> m-3">
                            <?php echo $message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" action="" class="settings-form">
                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                                        <i class="fas fa-cogs"></i> Genel Ayarlar
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="timeframes-tab" data-toggle="tab" href="#timeframes" role="tab" aria-controls="timeframes" aria-selected="false">
                                        <i class="fas fa-clock"></i> Zaman Aralıkları
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="auto-discovery-tab" data-toggle="tab" href="#auto-discovery" role="tab" aria-controls="auto-discovery" aria-selected="false">
                                        <i class="fas fa-search-dollar"></i> Otomatik Keşif
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="indicators-tab" data-toggle="tab" href="#indicators" role="tab" aria-controls="indicators" aria-selected="false">
                                        <i class="fas fa-chart-line"></i> İndikatörler
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="indicator-signals-tab" data-toggle="tab" href="#indicator-signals" role="tab" aria-controls="indicator-signals" aria-selected="false">
                                        <i class="fas fa-signal"></i> İndikatör Sinyalleri
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="strategies-tab" data-toggle="tab" href="#strategies" role="tab" aria-controls="strategies" aria-selected="false">
                                        <i class="fas fa-chess"></i> Stratejiler
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="risk-management-tab" data-toggle="tab" href="#risk-management" role="tab" aria-controls="risk-management" aria-selected="false">
                                        <i class="fas fa-shield-alt"></i> Risk Yönetimi
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="risk-management-advanced-tab" data-toggle="tab" href="#risk-management-advanced" role="tab" aria-controls="risk-management-advanced" aria-selected="false">
                                        <i class="fas fa-user-shield"></i> Gelişmiş Risk
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="backtesting-tab" data-toggle="tab" href="#backtesting" role="tab" aria-controls="backtesting" aria-selected="false">
                                        <i class="fas fa-vial"></i> Backtesting
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="smart-trend-tab" data-toggle="tab" href="#smart-trend" role="tab" aria-controls="smart-trend" aria-selected="false">
                                        <i class="fas fa-chart-line"></i> Akıllı Trend
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="adaptive-parameters-tab" data-toggle="tab" href="#adaptive-parameters" role="tab" aria-controls="adaptive-parameters" aria-selected="false">
                                        <i class="fas fa-sliders-h"></i> Adaptif Parametreler
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="api-optimization-tab" data-toggle="tab" href="#api-optimization" role="tab" aria-controls="api-optimization" aria-selected="false">
                                        <i class="fas fa-tachometer-alt"></i> API Optimizasyonu
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="api-tab" data-toggle="tab" href="#api-settings" role="tab" aria-controls="api-settings" aria-selected="false">
                                        <i class="fas fa-key"></i> API Ayarları
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="notifications-logging-tab" data-toggle="tab" href="#notifications-logging" role="tab" aria-controls="notifications-logging" aria-selected="false">
                                        <i class="fas fa-bell"></i> Bildirimler & Günlükler
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Genel Ayarlar -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel">
                                    <?php include 'settings/general.php'; ?>
                                </div>
                                
                                <!-- Zaman Aralıkları -->
                                <div class="tab-pane fade" id="timeframes" role="tabpanel">
                                    <?php include 'settings/timeframes.php'; ?>
                                </div>
                                
                                <!-- Otomatik Keşif Ayarları -->
                                <div class="tab-pane fade" id="auto-discovery" role="tabpanel">
                                    <?php include 'settings/auto_discovery.php'; ?>
                                </div>
                                  
                                <!-- İndikatör Ayarları -->
                                <div class="tab-pane fade" id="indicators" role="tabpanel">
                                    <?php include 'settings/indicators.php'; ?>
                                </div>
                                
                                <!-- İndikatör Sinyalleri -->
                                <div class="tab-pane fade" id="indicator-signals" role="tabpanel">
                                    <?php include 'settings/indicator_signals.php'; ?>
                                </div>
                                  
                                <!-- Stratejiler -->
                                <div class="tab-pane fade" id="strategies" role="tabpanel">
                                    <?php include 'settings/strategies.php'; ?>
                                </div>
                                
                                <!-- Risk Yönetimi -->
                                <div class="tab-pane fade" id="risk-management" role="tabpanel">
                                    <?php include 'settings/risk_management.php'; ?>
                                </div>
                                
                                <!-- Gelişmiş Risk Yönetimi -->
                                <div class="tab-pane fade" id="risk-management-advanced" role="tabpanel">
                                    <?php include 'settings/risk_management_advanced.php'; ?>
                                </div>
                                
                                <!-- Backtesting -->
                                <div class="tab-pane fade" id="backtesting" role="tabpanel">
                                    <?php include 'settings/backtesting.php'; ?>
                                </div>
                                
                                <!-- Akıllı Trend Analizi -->
                                <div class="tab-pane fade" id="smart-trend" role="tabpanel">
                                    <?php include 'settings/smart_trend.php'; ?>
                                </div>
                                
                                <!-- Adaptif Parametreler -->
                                <div class="tab-pane fade" id="adaptive-parameters" role="tabpanel">
                                    <?php include 'settings/adaptive_parameters.php'; ?>
                                </div>

                                <!-- API Optimizasyonu -->
                                <div class="tab-pane fade" id="api-optimization" role="tabpanel">
                                    <?php include 'settings/api_optimization.php'; ?>
                                </div>
                                
                                <!-- API Ayarları -->
                                <div class="tab-pane fade" id="api-settings" role="tabpanel">
                                    <?php include 'settings/api_settings.php'; ?>
                                </div>
                                
                                <!-- Bildirimler & Günlükler -->
                                <div class="tab-pane fade" id="notifications-logging" role="tabpanel">
                                    <?php include 'settings/notifications_logging.php'; ?>
                                </div>
                            </div>
                            
                            <div class="settings-footer">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save"></i> Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM yüklendi, sistem başlatılıyor...");
            
            // Immediate UI update
            forceUpdateAllUI();
            
            // Bootstrap tab sistemi
            if (typeof $ !== 'undefined') {
                $('#settingsTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                    var target = e.target.getAttribute('href');
                    localStorage.setItem('activeSettingsTab', target);
                    setTimeout(forceUpdateAllUI, 100);
                });
                
                var activeTab = localStorage.getItem('activeSettingsTab');
                if (activeTab && document.querySelector(activeTab)) {
                    $('#settingsTabs a[href="' + activeTab + '"]').tab('show');
                }
            }
            
            // Form event listeners
            setupFormListeners();
        });

        // Zorla tüm UI'ları güncelle
        function forceUpdateAllUI() {
            console.log("Zorla UI güncelleme başlatılıyor...");
            
            // Advanced Risk Management
            var advancedRiskCheckbox = document.getElementById('advanced_risk_enabled');
            var advancedRiskElements = document.querySelectorAll('.advanced-risk-setting');
            
            console.log("Advanced risk checkbox:", advancedRiskCheckbox ? advancedRiskCheckbox.checked : "BULUNAMADI");
            console.log("Advanced risk elements sayısı:", advancedRiskElements.length);
            
            // Tüm advanced-risk-setting elemanlarını zorla aktif yap
            advancedRiskElements.forEach(function(element) {
                element.style.opacity = '1';
                element.style.backgroundColor = 'white';
                element.style.color = '#495057';
                element.style.pointerEvents = 'auto';
                element.style.cursor = 'default';
                element.style.borderColor = '#ced4da';
                element.disabled = false;
                
                console.log("Element güncellendi:", element.tagName, element.id || element.className);
            });
            
            // Smart Trend
            var smartTrendElements = document.querySelectorAll('.smart-trend-setting');
            smartTrendElements.forEach(function(element) {
                element.style.opacity = '1';
                element.style.backgroundColor = 'white';
                element.style.pointerEvents = 'auto';
                element.disabled = false;
            });
            
            // Adaptive Parameters
            var adaptiveElements = document.querySelectorAll('.adaptive-param-setting');
            adaptiveElements.forEach(function(element) {
                element.style.opacity = '1';
                element.style.backgroundColor = 'white';
                element.style.pointerEvents = 'auto';
                element.disabled = false;
            });
            
            // API Optimization
            var apiElements = document.querySelectorAll('.api-optimization-setting');
            apiElements.forEach(function(element) {
                element.style.opacity = '1';
                element.style.backgroundColor = 'white';
                element.style.pointerEvents = 'auto';
                element.disabled = false;
            });
            
            // Feature cards
            var featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(function(card) {
                card.classList.add('enabled');
                card.classList.remove('disabled');
            });
            
            console.log("UI güncelleme tamamlandı!");
        }

        function setupFormListeners() {
            // Checkbox event listeners
            var checkboxes = document.querySelectorAll('.custom-control-input');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    setTimeout(forceUpdateAllUI, 50);
                });
            });
            
            // Save button
            var saveButton = document.querySelector('.btn-save');
            if (saveButton) {
                saveButton.addEventListener('click', function(e) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
                    this.disabled = true;
                    setTimeout(function() {
                        document.querySelector('.settings-form').submit();
                    }, 100);
                });
            }
        }

        // Sayfa yüklendikten sonra da çalıştır
        window.addEventListener('load', function() {
            setTimeout(forceUpdateAllUI, 200);
            setTimeout(forceUpdateAllUI, 1000); // Ekstra güvenlik
        });
        
        // Debug function
        window.debugUI = function() {
            console.log("=== UI DEBUG ===");
            var advancedElements = document.querySelectorAll('.advanced-risk-setting');
            advancedElements.forEach(function(el, index) {
                console.log("Element " + index + ":", {
                    tag: el.tagName,
                    id: el.id,
                    class: el.className,
                    opacity: el.style.opacity,
                    backgroundColor: el.style.backgroundColor,
                    disabled: el.disabled
                });
            });
        };
    </script>
</body>
</html>