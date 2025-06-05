<?php
// Doğrudan erişimi engelle
if (!defined('TRADING_BOT')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Form POST işlemleri merkezi olarak burada yapılır
// Bu, tekrar eden kodu önler ve ayarları düzenli şekilde günceller

// Basit anahtar-değer eşleşmeleri
$simple_settings = [
    'exchange' => 'string',
    'base_currency' => 'string',
    'min_volume' => 'float',
    'max_coins' => 'integer',
    'min_trade_amount' => 'float',
    'max_trade_amount' => 'float',
    'position_size' => 'float',
    'api_delay' => 'float',
    'scan_interval' => 'integer',
    'leverage' => 'integer',
    'leverage_mode' => 'string',
    'trade_mode' => 'string',
    'market_type' => 'string',
    'trade_direction' => 'string',
    'primary_timeframe' => 'string',
    'timeframe_weight_short' => 'integer',
    'timeframe_weight_medium' => 'integer',
    'timeframe_weight_long' => 'integer',
    'timeframe_consensus' => 'string',
    'signal_consensus_method' => 'string',
    'signal_consensus_threshold' => 'integer',
    'signal_confirmation_count' => 'integer',
    'signal_conflicting_action' => 'string'
];

// Basit ayarları işle
foreach ($simple_settings as $key => $type) {
    if (isset($_POST[$key])) {
        switch ($type) {
            case 'integer':
                $updated_settings[$key] = (int) $_POST[$key];
                break;
            case 'float':
                $updated_settings[$key] = (float) $_POST[$key];
                break;
            default:
                $updated_settings[$key] = $_POST[$key];
        }
    }
}

// Checkbox ayarları işle (boolean değerler)
$checkbox_settings = [
    'use_tradingview',
    'auto_trade',
    'telegram_enabled',
    'telegram_trade_signals',
    'telegram_discovered_coins',
    'telegram_position_updates',
    'telegram_performance_updates',
    'enable_visualization',
    'auto_discovery_enabled',
    'auto_add_to_watchlist',
    'trailing_stop',
    'dynamic_position_sizing',
    'volatility_based_stops',
    'adaptive_take_profit',
    'auto_adjust_risk',
    'market_regime_detection',
    'reset_after_market_shift',
    'volatility_adjustment',
    'trend_strength_adjustment',
    'prioritize_active_trades',
    'reduce_timeframes_count',
    'optimize_indicator_calculations'
];

foreach ($checkbox_settings as $key) {
    $updated_settings[$key] = isset($_POST[$key]);
}

// İndikatör ayarlarını güncelle
updateIndicatorSettings($updated_settings, $_POST);

// Strateji ayarlarını güncelle
updateStrategySettings($updated_settings, $_POST);

// Risk yönetimi ayarlarını güncelle
updateRiskManagementSettings($updated_settings, $_POST);

// Gelişmiş risk yönetimi ayarlarını güncelle
updateAdvancedRiskManagementSettings($updated_settings, $_POST);

// Backtesting ayarlarını güncelle
updateBacktestingSettings($updated_settings, $_POST);

// Akıllı trend ayarlarını güncelle
updateSmartTrendSettings($updated_settings, $_POST);

// Adaptif parametreler ayarlarını güncelle
updateAdaptiveParametersSettings($updated_settings, $_POST);

// API optimizasyon ayarlarını güncelle
updateApiOptimizationSettings($updated_settings, $_POST);

// API anahtarları ayarlarını güncelle
updateApiKeySettings($updated_settings, $_POST);

// Telegram ayarlarını güncelle
updateTelegramSettings($updated_settings, $_POST);

// Otomatik keşif ayarlarını güncelle
updateAutoDiscoverySettings($updated_settings, $_POST);

// İndikatör ağırlıklarını güncelle
updateIndicatorWeights($updated_settings, $_POST);

// Çoklu zaman aralığı ayarlarını güncelle
if (isset($_POST['timeframes'])) {
    $updated_settings['timeframes'] = $_POST['timeframes'];
} else {
    // Eğer hiçbir timeframe seçilmediyse, en azından 1h'i varsayılan olarak ekleyelim
    $updated_settings['timeframes'] = ['1h'];
}

// Yardımcı fonksiyonlar
function updateIndicatorSettings(&$settings, $post_data) {
    // İndikatör ayarlarını güncelle
    $indicators = [
        'bollinger_bands' => [
            'enabled' => 'bb_enabled',
            'window' => 'bb_window',
            'num_std' => 'bb_num_std'
        ],
        'rsi' => [
            'enabled' => 'rsi_enabled',
            'window' => 'rsi_window'
        ],
        'macd' => [
            'enabled' => 'macd_enabled',
            'fast_period' => 'macd_fast',
            'slow_period' => 'macd_slow',
            'signal_period' => 'macd_signal'
        ],
        'moving_average' => [
            'enabled' => 'ma_enabled',
            'short_window' => 'ma_short',
            'long_window' => 'ma_long'
        ],
        'supertrend' => [
            'enabled' => 'supertrend_enabled',
            'period' => 'supertrend_period',
            'multiplier' => 'supertrend_multiplier'
        ],
        'vwap' => [
            'enabled' => 'vwap_enabled',
            'period' => 'vwap_period'
        ],
        'pivot_points' => [
            'enabled' => 'pivot_points_enabled',
            'method' => 'pivot_points_method'
        ],
        'fibonacci' => [
            'enabled' => 'fibonacci_enabled',
            'period' => 'fibonacci_period'
        ],
        'stochastic' => [
            'enabled' => 'stochastic_enabled',
            'k_period' => 'stochastic_k_period',
            'd_period' => 'stochastic_d_period',
            'slowing' => 'stochastic_slowing'
        ]
    ];
    
    // İndikatör ayarları için ana yapıyı oluştur
    if (!isset($settings['indicators'])) {
        $settings['indicators'] = [];
    }
    
    foreach ($indicators as $indicator => $fields) {
        if (!isset($settings['indicators'][$indicator])) {
            $settings['indicators'][$indicator] = [];
        }
        
        foreach ($fields as $field => $post_key) {
            if (isset($post_data[$post_key])) {
                if ($field === 'enabled') {
                    $settings['indicators'][$indicator][$field] = isset($post_data[$post_key]);
                } elseif (strpos($field, 'period') !== false || $field === 'window') {
                    $settings['indicators'][$indicator][$field] = (int) $post_data[$post_key];
                } elseif ($field === 'num_std' || $field === 'multiplier') {
                    $settings['indicators'][$indicator][$field] = (float) $post_data[$post_key];
                } else {
                    $settings['indicators'][$indicator][$field] = $post_data[$post_key];
                }
            }
        }
    }
}

function updateStrategySettings(&$settings, $post_data) {
    $strategies = [
        'short_term' => 'short_term_enabled',
        'trend_following' => 'trend_following_enabled',
        'breakout' => 'breakout_enabled',
        'volatility_breakout' => 'volatility_breakout_enabled'
    ];
    
    // Stratejiler ana yapısını oluştur
    if (!isset($settings['strategies'])) {
        $settings['strategies'] = [];
    }
    
    foreach ($strategies as $strategy => $post_key) {
        if (!isset($settings['strategies'][$strategy])) {
            $settings['strategies'][$strategy] = [];
        }
        $settings['strategies'][$strategy]['enabled'] = isset($post_data[$post_key]);
    }
}

function updateRiskManagementSettings(&$settings, $post_data) {
    $risk_fields = [
        'enabled' => 'risk_enabled',
        'stop_loss' => 'stop_loss',
        'take_profit' => 'take_profit',
        'trailing_stop' => 'trailing_stop',
        'trailing_stop_distance' => 'trailing_stop_distance',
        'trailing_stop_activation_pct' => 'trailing_stop_activation_pct',
        'trailing_stop_pct' => 'trailing_stop_pct',
        'max_open_positions' => 'max_open_positions',
        'max_risk_per_trade' => 'max_risk_per_trade'
    ];
    
    // Risk yönetimi ana yapısını oluştur
    if (!isset($settings['risk_management'])) {
        $settings['risk_management'] = [];
    }
    
    foreach ($risk_fields as $field => $post_key) {
        if (isset($post_data[$post_key])) {
            if (in_array($field, ['enabled', 'trailing_stop'])) {
                $settings['risk_management'][$field] = isset($post_data[$post_key]);
            } elseif (in_array($field, ['max_open_positions'])) {
                $settings['risk_management'][$field] = (int) $post_data[$post_key];
            } else {
                $settings['risk_management'][$field] = (float) $post_data[$post_key];
            }
        }
    }
}

function updateAdvancedRiskManagementSettings(&$settings, $post_data) {
    // Gelişmiş risk yönetimi ana yapısını oluştur
    if (!isset($settings['advanced_risk_management'])) {
        $settings['advanced_risk_management'] = [];
    }
    
    $settings['advanced_risk_management']['enabled'] = isset($post_data['advanced_risk_enabled']);
    
    // Eğer gelişmiş risk yönetimi etkinse veya değilse bile, ayarları sakla
    $settings['advanced_risk_management']['dynamic_position_sizing'] = isset($post_data['dynamic_position_sizing']);
    $settings['advanced_risk_management']['position_size_method'] = isset($post_data['position_size_method']) ? $post_data['position_size_method'] : 'fixed';
    $settings['advanced_risk_management']['volatility_based_stops'] = isset($post_data['volatility_based_stops']);
    $settings['advanced_risk_management']['adaptive_take_profit'] = isset($post_data['adaptive_take_profit']);
    $settings['advanced_risk_management']['auto_adjust_risk'] = isset($post_data['auto_adjust_risk']);
    $settings['advanced_risk_management']['reset_after_market_shift'] = isset($post_data['reset_after_market_shift']);
    
    // Sayısal değerleri al
    if (isset($post_data['max_risk_per_trade'])) {
        $settings['advanced_risk_management']['max_risk_per_trade'] = (float) $post_data['max_risk_per_trade'];
    }
    
    if (isset($post_data['max_open_positions'])) {
        $settings['advanced_risk_management']['max_open_positions'] = (int) $post_data['max_open_positions'];
    }
}

function updateBacktestingSettings(&$settings, $post_data) {
    // Backtesting ana yapısını oluştur
    if (!isset($settings['backtesting'])) {
        $settings['backtesting'] = [];
    }
    
    $fields = [
        'default_start_date' => 'string',
        'default_end_date' => 'string',
        'initial_capital' => 'float',
        'trading_fee' => 'float',
        'slippage' => 'float'
    ];
    
    foreach ($fields as $field => $type) {
        if (isset($post_data[$field])) {
            if ($type === 'float') {
                $settings['backtesting'][$field] = (float) $post_data[$field];
            } elseif ($type === 'integer') {
                $settings['backtesting'][$field] = (int) $post_data[$field];
            } else {
                $settings['backtesting'][$field] = $post_data[$field];
            }
        }
    }
    
    $settings['backtesting']['enable_visualization'] = isset($post_data['enable_visualization']);
}

function updateSmartTrendSettings(&$settings, $post_data) {
    // Integration settings ana yapısını oluştur
    if (!isset($settings['integration_settings'])) {
        $settings['integration_settings'] = [];
    }
    
    $settings['integration_settings']['use_smart_trend'] = isset($post_data['use_smart_trend']);
    
    // Smart trend ayarlarını oluştur
    if (!isset($settings['integration_settings']['smart_trend_settings'])) {
        $settings['integration_settings']['smart_trend_settings'] = [];
    }
    
    // Bu alanların varlığını kontrol et ve varsayılan değerler kullan
    $settings['integration_settings']['smart_trend_settings']['detection_method'] = 
        isset($post_data['trend_detection_method']) ? $post_data['trend_detection_method'] : 'multi_timeframe';
    
    $settings['integration_settings']['smart_trend_settings']['sensitivity'] = 
        isset($post_data['trend_sensitivity']) ? (float) $post_data['trend_sensitivity'] : 0.5;
    
    $settings['integration_settings']['smart_trend_settings']['lookback_period'] = 
        isset($post_data['trend_lookback_period']) ? (int) $post_data['trend_lookback_period'] : 100;
    
    $settings['integration_settings']['smart_trend_settings']['confirmation_period'] = 
        isset($post_data['trend_confirmation_period']) ? (int) $post_data['trend_confirmation_period'] : 3;
    
    $settings['integration_settings']['smart_trend_settings']['signal_quality_threshold'] = 
        isset($post_data['signal_quality_threshold']) ? (float) $post_data['signal_quality_threshold'] : 0.7;
}

function updateAdaptiveParametersSettings(&$settings, $post_data) {
    // Adaptive parameters ana yapısını oluştur
    if (!isset($settings['adaptive_parameters'])) {
        $settings['adaptive_parameters'] = [];
    }
    
    $settings['adaptive_parameters']['enabled'] = isset($post_data['adaptive_params_enabled']);
    
    // Diğer ayarları da sakla
    $settings['adaptive_parameters']['adaptation_speed'] = isset($post_data['adaptation_speed']) ? 
        (float) $post_data['adaptation_speed'] : 0.5;
    
    $settings['adaptive_parameters']['learning_rate'] = isset($post_data['learning_rate']) ? 
        (float) $post_data['learning_rate'] : 0.05;
    
    $settings['adaptive_parameters']['market_regime_detection'] = isset($post_data['market_regime_detection']);
    $settings['adaptive_parameters']['volatility_adjustment'] = isset($post_data['volatility_adjustment']);
    $settings['adaptive_parameters']['trend_strength_adjustment'] = isset($post_data['trend_strength_adjustment']);
    $settings['adaptive_parameters']['reset_after_market_shift'] = isset($post_data['reset_after_market_shift']);
    
    $settings['adaptive_parameters']['min_history_required'] = isset($post_data['min_history_required']) ? 
        (int) $post_data['min_history_required'] : 100;
}

function updateApiOptimizationSettings(&$settings, $post_data) {
    // API optimizasyon ana yapısını oluştur
    if (!isset($settings['api_optimization'])) {
        $settings['api_optimization'] = [];
    }
    
    $settings['api_optimization']['enabled'] = isset($post_data['api_optimization_enabled']);
    
    // API çağrı limiti (saniyede)
    $settings['api_optimization']['api_call_limit_per_minute'] = isset($post_data['api_call_limit_per_minute']) ? 
        (int) $post_data['api_call_limit_per_minute'] : 60;
    
    // API çağrı dağılım stratejisi
    $settings['api_optimization']['api_call_distribution'] = isset($post_data['api_call_distribution']) ? 
        $post_data['api_call_distribution'] : 'even';
    
    // Önbellek süresi
    $settings['api_optimization']['cache_duration'] = isset($post_data['cache_duration']) ? 
        (int) $post_data['cache_duration'] : 60;
    
    // Diğer ayarlar
    $settings['api_optimization']['prioritize_active_trades'] = isset($post_data['prioritize_active_trades']);
    $settings['api_optimization']['reduce_timeframes_count'] = isset($post_data['reduce_timeframes_count']);
    $settings['api_optimization']['optimize_indicator_calculations'] = isset($post_data['optimize_indicator_calculations']);
}

function updateApiKeySettings(&$settings, $post_data) {
    // API anahtarları ana yapısını oluştur
    if (!isset($settings['api_keys'])) {
        $settings['api_keys'] = [];
    }
    
    $api_key_fields = [
        'binance_api_key',
        'binance_api_secret',
        'kucoin_api_key',
        'kucoin_api_secret',
        'kucoin_api_passphrase'
    ];
    
    foreach ($api_key_fields as $field) {
        if (isset($post_data[$field])) {
            $settings['api_keys'][$field] = $post_data[$field];
        }
    }
}

function updateTelegramSettings(&$settings, $post_data) {
    // Telegram ana yapısını oluştur
    if (!isset($settings['telegram'])) {
        $settings['telegram'] = [];
    }
    
    $settings['telegram']['enabled'] = isset($post_data['telegram_enabled']);
    
    if (isset($post_data['telegram_token'])) {
        $settings['telegram']['token'] = $post_data['telegram_token'];
    }
    
    if (isset($post_data['telegram_chat_id'])) {
        $settings['telegram']['chat_id'] = $post_data['telegram_chat_id'];
    }
    
    $settings['telegram']['trade_signals'] = isset($post_data['telegram_trade_signals']);
    $settings['telegram']['discovered_coins'] = isset($post_data['telegram_discovered_coins']);
    $settings['telegram']['position_updates'] = isset($post_data['telegram_position_updates']);
    $settings['telegram']['performance_updates'] = isset($post_data['telegram_performance_updates']);
}

function updateAutoDiscoverySettings(&$settings, $post_data) {
    // Auto discovery ana yapısını oluştur
    if (!isset($settings['auto_discovery'])) {
        $settings['auto_discovery'] = [];
    }
    
    $settings['auto_discovery']['enabled'] = isset($post_data['auto_discovery_enabled']);
    
    $settings['auto_discovery']['discovery_interval'] = isset($post_data['discovery_interval']) ? 
        (int) $post_data['discovery_interval'] : 600;
    
    $settings['auto_discovery']['min_volume_for_discovery'] = isset($post_data['min_volume_for_discovery']) ? 
        (float) $post_data['min_volume_for_discovery'] : 1000;
    
    $settings['auto_discovery']['min_price_change'] = isset($post_data['min_price_change']) ? 
        (float) $post_data['min_price_change'] : 5;
    
    $settings['auto_discovery']['min_volume_change'] = isset($post_data['min_volume_change']) ? 
        (float) $post_data['min_volume_change'] : 10;
    
    $settings['auto_discovery']['max_coins_to_discover'] = isset($post_data['max_coins_to_discover']) ? 
        (int) $post_data['max_coins_to_discover'] : 10;
    
    $settings['auto_discovery']['auto_add_to_watchlist'] = isset($post_data['auto_add_to_watchlist']);
}

function updateIndicatorWeights(&$settings, $post_data) {
    // İndikatör ağırlıkları ana yapısını oluştur
    if (!isset($settings['indicator_weights'])) {
        $settings['indicator_weights'] = [];
    }
    
    $fields = [
        'rsi' => 'rsi_weight',
        'macd' => 'macd_weight',
        'bollinger_bands' => 'bollinger_weight',
        'moving_average' => 'ma_weight',
        'supertrend' => 'supertrend_weight',
        'stochastic' => 'stochastic_weight',
        'adx' => 'adx_weight',
        'other' => 'other_weight'
    ];
    
    foreach ($fields as $field => $post_key) {
        if (isset($post_data[$post_key])) {
            $settings['indicator_weights'][$field] = (int) $post_data[$post_key];
        } else {
            // Varsayılan değerler
            switch ($field) {
                case 'rsi':
                case 'macd':
                    $settings['indicator_weights'][$field] = 20;
                    break;
                case 'bollinger_bands':
                case 'moving_average':
                    $settings['indicator_weights'][$field] = 15;
                    break;
                case 'supertrend':
                case 'stochastic':
                    $settings['indicator_weights'][$field] = 10;
                    break;
                default:
                    $settings['indicator_weights'][$field] = 5;
            }
        }
    }
}
