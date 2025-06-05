<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı ve API bağlantısı
require_once 'api/bot_api.php';
$bot_api = new BotAPI();

// Bot durumunu al
$status = $bot_api->getStatus();
$bot_running = $status['running'];

// Coin listesini al
$all_coins = $bot_api->getActiveCoins();

// API bağlantı durumunu al
$exchange_info = $bot_api->getSettings()['exchange'];

// Sayfa başlığı
$page_title = 'Aktif Coinler';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/coins.css">
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
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-coins"></i> <?php echo $page_title; ?></h5>
                        <div class="d-flex align-items-center">
                            <!-- Zaman Aralığı Seçimi -->
                            <div class="time-interval-selector mr-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light time-interval-btn active" data-interval="5m">5 Dakika</button>
                                    <button type="button" class="btn btn-light time-interval-btn" data-interval="10m">10 Dakika</button>
                                    <button type="button" class="btn btn-light time-interval-btn" data-interval="15m">15 Dakika</button>
                                    <button type="button" class="btn btn-light time-interval-btn" data-interval="1h">1 Saat</button>
                                </div>
                            </div>

                            <!-- Durum Göstergeleri - Daha büyük ve net simgelerle -->
                            <div class="d-flex align-items-center mr-3">
                                <!-- Binance API Durumu -->
                                <div class="d-flex align-items-center mr-3">
                                    <span class="status-icon <?php echo isset($status['exchange_connected']) && $status['exchange_connected'] ? 'bg-success' : 'bg-danger'; ?> mr-2" style="width: 12px; height: 12px;"></span>
                                    <span class="<?php echo isset($status['exchange_connected']) && $status['exchange_connected'] ? 'text-success' : 'text-danger'; ?>" style="font-size: 1rem; font-weight: 600;">
                                        <i class="fas fa-exchange-alt"></i> API
                                    </span>
                                </div>
                                
                                <!-- Bot Durumu -->
                                <div class="d-flex align-items-center mr-3">
                                    <span class="status-icon <?php echo $bot_running ? 'bg-success' : 'bg-danger'; ?> mr-2" style="width: 12px; height: 12px;"></span>
                                    <span class="<?php echo $bot_running ? 'text-success' : 'text-danger'; ?>" style="font-size: 1rem; font-weight: 600;">
                                        <i class="fas fa-robot"></i> Bot
                                    </span>
                                </div>
                                
                                <!-- Veri Durumu -->
                                <div class="d-flex align-items-center mr-3">
                                    <span id="data-status-icon" class="status-icon bg-warning mr-2" style="width: 12px; height: 12px;"></span>
                                    <span id="data-status-text" class="text-warning" style="font-size: 1rem; font-weight: 600;">
                                        <i class="fas fa-database"></i> Veri
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Son Güncelleme -->
                            <div class="mr-3">
                                <span class="text-light" style="font-size: 0.9rem;"><i class="far fa-clock"></i> <span id="last-update-time"><?php echo date('H:i:s'); ?></span></span>
                            </div>
                            
                            <!-- Yenile Butonu -->
                            <button id="refresh-data" class="btn btn-sm btn-light">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                        </div>
                    </div>
                    <div class="card-body position-relative">
                        <!-- Yükleme Göstergesi -->
                        <div id="loading-overlay" class="loading-overlay">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-2" role="status">
                                    <span class="sr-only">Yükleniyor...</span>
                                </div>
                                <p class="mb-0">Veriler güncelleniyor...</p>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="input-group input-group-sm" style="width: 300px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="coin-search" class="form-control" placeholder="Coin ara (örn: BTC, ETH...)">
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <small class="text-muted">Son güncelleme: <span id="last-update-time"><?php echo date('Y-m-d H:i:s'); ?></span></small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Filtrele
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="filterDropdown">
                                        <h6 class="dropdown-header">Sinyal Filtreleri</h6>
                                        <a class="dropdown-item filter-option" href="#" data-filter="all">Tümü</a>
                                        <a class="dropdown-item filter-option" href="#" data-filter="buy">Satın Al Sinyali</a>
                                        <a class="dropdown-item filter-option" href="#" data-filter="sell">Sat Sinyali</a>
                                        <a class="dropdown-item filter-option" href="#" data-filter="neutral">Bekle Sinyali</a>
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">Değişim Filtreleri</h6>
                                        <a class="dropdown-item filter-option" href="#" data-filter="positive">Pozitif Değişim</a>
                                        <a class="dropdown-item filter-option" href="#" data-filter="negative">Negatif Değişim</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-striped coin-table" id="coin-table">
                                <thead>
                                    <tr>
                                        <th>Sembol</th>
                                        <th>Fiyat</th>
                                        <th>24s Değişim</th>
                                        <th>Sinyal</th>
                                        <th>Toplam Analiz</th>
                                        <th>RSI</th>
                                        <th>MACD</th>
                                        <th>Bollinger</th>
                                        <th>Hareketli Ort.</th>
                                        <th>ADX</th>
                                        <th>Stochastic</th>
                                        <th>PSAR</th>
                                        <th>Son Güncelleme</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody id="coin-list">
                                    <?php if (empty($all_coins)): ?>
                                    <tr>
                                        <td colspan="14" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                <p>Henüz takip edilen coin bulunamadı.</p>
                                                <p>Veritabanında coin kaydı olup olmadığını kontrol edin.</p>
                                                
                                                <a href="add_basic_coins.php" class="btn btn-success mt-3">
                                                    <i class="fas fa-plus-circle"></i> Temel Coinleri Ekle
                                                </a>
                                                
                                                <?php if (!$bot_running): ?>
                                                <p class="text-danger mt-3">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Bot şu anda çalışmıyor. Coinlerin görünmesi için botu başlatın.
                                                </p>
                                                <button class="btn btn-success start-bot-btn"><i class="fas fa-play"></i> Botu Başlat</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($all_coins as $coin): ?>
                                    <tr class="<?php 
                                        $signal = strtoupper($coin['signal'] ?? 'NEUTRAL');
                                        echo $signal == 'BUY' ? 'coin-signal-buy' : ($signal == 'SELL' ? 'coin-signal-sell' : '');
                                    ?>" data-symbol="<?php echo $coin['symbol']; ?>">
                                        <td><strong><?php echo $coin['symbol']; ?></strong></td>
                                        <td class="price-cell">--</td>
                                        <td class="change-cell">--</td>
                                        <td>
                                            <?php 
                                            $signalClass = '';
                                            $signalText = 'BEKLİYOR';
                                            $dotClass = 'dot-neutral';
                                            $signalReason = '';
                                            
                                            if ($signal == 'BUY') {
                                                $signalClass = 'badge-success';
                                                $signalText = 'AL';
                                                $dotClass = 'dot-buy';
                                            } elseif ($signal == 'SELL') {
                                                $signalClass = 'badge-danger';
                                                $signalText = 'SAT';
                                                $dotClass = 'dot-sell';
                                            } else {
                                                $signalClass = 'badge-secondary';
                                            }
                                            ?>
                                            <span class="signal-dot <?php echo $dotClass; ?>"></span>
                                            <span class="badge <?php echo $signalClass; ?>"><?php echo $signalText; ?></span>
                                            
                                            <?php 
                                            // Zaman aralığını ve işlem modunu göster
                                            $interval = isset($_GET['interval']) ? $_GET['interval'] : '5m';
                                            $trade_direction = isset($coin['trade_direction']) ? $coin['trade_direction'] : 'LONG';
                                            
                                            if ($signal == 'BUY' || $signal == 'SELL') {
                                                echo '<br><small class="text-muted mt-1">('.$interval.' · '.($trade_direction == 'LONG' ? 'Long' : 'Short').')</small>';
                                            }
                                            
                                            // Sinyal oluşturma nedenini belirle
                                            $leadingIndicator = '';
                                            $triggerDescription = '';

                                            // Reason bilgisi varsa onu göster
                                            if (isset($coin['reason']) && !empty($coin['reason'])) {
                                                $triggerDescription = $coin['reason'];
                                            } else {
                                                // İndikatörlere göre en baskın sinyali tespit et
                                                if (isset($coin['indicators'])) {
                                                    // RSI kontrolü
                                                    if (isset($coin['indicators']['rsi']['value'])) {
                                                        $rsi = $coin['indicators']['rsi']['value'];
                                                        if ($rsi <= 30 && $signal == 'BUY') {
                                                            $leadingIndicator = 'RSI';
                                                            $triggerDescription = 'RSI aşırı satım bölgesinde ('.$rsi.')';
                                                        } elseif ($rsi >= 70 && $signal == 'SELL') {
                                                            $leadingIndicator = 'RSI';
                                                            $triggerDescription = 'RSI aşırı alım bölgesinde ('.$rsi.')';
                                                        }
                                                    }
                                                    
                                                    // MACD kontrolü
                                                    if (empty($triggerDescription) && isset($coin['indicators']['macd']['signal'])) {
                                                        if ($coin['indicators']['macd']['signal'] == 'BUY' && $signal == 'BUY') {
                                                            $leadingIndicator = 'MACD';
                                                            $triggerDescription = 'MACD al sinyali verdi';
                                                        } elseif ($coin['indicators']['macd']['signal'] == 'SELL' && $signal == 'SELL') {
                                                            $leadingIndicator = 'MACD';
                                                            $triggerDescription = 'MACD sat sinyali verdi';
                                                        }
                                                    }
                                                    
                                                    // Bollinger kontrolü
                                                    if (empty($triggerDescription) && isset($coin['indicators']['bollinger']) && isset($coin['price'])) {
                                                        if ($coin['price'] <= $coin['indicators']['bollinger']['lower'] && $signal == 'BUY') {
                                                            $leadingIndicator = 'Bollinger';
                                                            $triggerDescription = 'Fiyat alt bandın altında';
                                                        } elseif ($coin['price'] >= $coin['indicators']['bollinger']['upper'] && $signal == 'SELL') {
                                                            $leadingIndicator = 'Bollinger';
                                                            $triggerDescription = 'Fiyat üst bandın üstünde';
                                                        }
                                                    }
                                                    
                                                    // Moving average kontrolü
                                                    if (empty($triggerDescription) && isset($coin['indicators']['moving_averages'])) {
                                                        $ma = $coin['indicators']['moving_averages'];
                                                        if (isset($ma['ma20']) && isset($ma['ma50'])) {
                                                            if ($ma['ma20'] > $ma['ma50'] && $signal == 'BUY') {
                                                                $leadingIndicator = 'EMA';
                                                                $triggerDescription = 'MA20 > MA50 (Altın Çapraz)';
                                                            } elseif ($ma['ma20'] < $ma['ma50'] && $signal == 'SELL') {
                                                                $leadingIndicator = 'EMA';
                                                                $triggerDescription = 'MA20 < MA50 (Ölüm Çaprazı)';
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                // Hala açıklama yoksa, varsayılan açıklama
                                                if (empty($triggerDescription) && ($signal == 'BUY' || $signal == 'SELL')) {
                                                    $triggerDescription = "Çoklu indikatör analizi";
                                                }
                                            }
                                            
                                            // Sinyalin sebebini göster
                                            if (!empty($triggerDescription) && ($signal == 'BUY' || $signal == 'SELL')) {
                                                echo '<div class="signal-reason mt-1" style="font-size: 0.75rem; color: #666;">';
                                                
                                                if (!empty($leadingIndicator)) {
                                                    echo '<i class="fas fa-info-circle"></i> <strong>'.$leadingIndicator.':</strong> ';
                                                } else {
                                                    echo '<i class="fas fa-info-circle"></i> ';
                                                }
                                                
                                                echo $triggerDescription.'</div>';
                                            }
                                            ?>
                                            
                                            <?php if (($signal == 'BUY' || $signal == 'SELL') && !empty($coin['last_signal_time'])): ?>
                                                <div class="signal-time mt-1" style="font-size: 0.7rem; color: #999;">
                                                    <i class="far fa-clock"></i> <?php echo date('H:i:s', strtotime($coin['last_signal_time'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="analysis-cell">
                                            <?php 
                                            // Toplam analiz değerini hesapla ve görüntüle
                                            $totalBuySignals = 0;
                                            $totalSellSignals = 0;
                                            $totalNeutralSignals = 0;
                                            $totalSignals = 0;
                                            
                                            // RSI sinyali
                                            if (isset($coin['indicators']['rsi'])) {
                                                $totalSignals++;
                                                if ($coin['indicators']['rsi']['value'] <= 30) $totalBuySignals++;
                                                elseif ($coin['indicators']['rsi']['value'] >= 70) $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // MACD sinyali
                                            if (isset($coin['indicators']['macd'])) {
                                                $totalSignals++;
                                                if (isset($coin['indicators']['macd']['signal']) && $coin['indicators']['macd']['signal'] == 'BUY') $totalBuySignals++;
                                                elseif (isset($coin['indicators']['macd']['signal']) && $coin['indicators']['macd']['signal'] == 'SELL') $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // Bollinger Bantları sinyali
                                            if (isset($coin['indicators']['bollinger'])) {
                                                $totalSignals++;
                                                if (isset($coin['price']) && isset($coin['indicators']['bollinger']['lower']) && $coin['price'] <= $coin['indicators']['bollinger']['lower']) $totalBuySignals++;
                                                elseif (isset($coin['price']) && isset($coin['indicators']['bollinger']['upper']) && $coin['price'] >= $coin['indicators']['bollinger']['upper']) $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // Hareketli Ortalama sinyali
                                            if (isset($coin['indicators']['moving_averages'])) {
                                                $totalSignals++;
                                                if (isset($coin['indicators']['moving_averages']['ma20']) && isset($coin['indicators']['moving_averages']['ma50']) && $coin['indicators']['moving_averages']['ma20'] > $coin['indicators']['moving_averages']['ma50']) $totalBuySignals++;
                                                elseif (isset($coin['indicators']['moving_averages']['ma20']) && isset($coin['indicators']['moving_averages']['ma50']) && $coin['indicators']['moving_averages']['ma20'] < $coin['indicators']['moving_averages']['ma50']) $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // ADX sinyali
                                            if (isset($coin['indicators']['adx'])) {
                                                $totalSignals++;
                                                if (isset($coin['indicators']['adx']['trend']) && $coin['indicators']['adx']['trend'] == 'bullish' && $coin['indicators']['adx']['value'] >= 25) $totalBuySignals++;
                                                elseif (isset($coin['indicators']['adx']['trend']) && $coin['indicators']['adx']['trend'] == 'bearish' && $coin['indicators']['adx']['value'] >= 25) $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // Stochastic sinyali
                                            if (isset($coin['indicators']['stochastic'])) {
                                                $totalSignals++;
                                                if (isset($coin['indicators']['stochastic']['value']) && $coin['indicators']['stochastic']['value'] <= 20) $totalBuySignals++;
                                                elseif (isset($coin['indicators']['stochastic']['value']) && $coin['indicators']['stochastic']['value'] >= 80) $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // PSAR sinyali
                                            if (isset($coin['indicators']['psar']) || isset($coin['indicators']['parabolic_sar'])) {
                                                $psarData = $coin['indicators']['parabolic_sar'] ?? $coin['indicators']['psar'] ?? [];
                                                $totalSignals++;
                                                if (isset($psarData['trend']) && $psarData['trend'] == 'bullish') $totalBuySignals++;
                                                elseif (isset($psarData['trend']) && $psarData['trend'] == 'bearish') $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // TradingView sinyali (varsa)
                                            if (isset($coin['indicators']['tradingview'])) {
                                                $totalSignals++;
                                                if (isset($coin['indicators']['tradingview']['signal']) && $coin['indicators']['tradingview']['signal'] == 'BUY') $totalBuySignals++;
                                                elseif (isset($coin['indicators']['tradingview']['signal']) && $coin['indicators']['tradingview']['signal'] == 'SELL') $totalSellSignals++;
                                                else $totalNeutralSignals++;
                                            }
                                            
                                            // Toplam sinyal puanı (indikatör sayısına göre değişir)
                                            if ($totalSignals > 0) {
                                                $buyPercentage = round(($totalBuySignals / $totalSignals) * 100);
                                                $sellPercentage = round(($totalSellSignals / $totalSignals) * 100);
                                                $neutralPercentage = round(($totalNeutralSignals / $totalSignals) * 100);
                                                
                                                // Ana rengi belirle
                                                if ($buyPercentage > $sellPercentage && $buyPercentage > $neutralPercentage) {
                                                    $mainClass = 'text-success';
                                                    $mainSignal = 'AL';
                                                } elseif ($sellPercentage > $buyPercentage && $sellPercentage > $neutralPercentage) {
                                                    $mainClass = 'text-danger';
                                                    $mainSignal = 'SAT';
                                                } else {
                                                    $mainClass = 'text-secondary';
                                                    $mainSignal = 'NÖTR';
                                                }
                                                
                                                // Progress bar HTML'i (tooltipli)
                                                echo '<div class="progress position-relative" style="height: 20px;" data-toggle="tooltip" title="AL: '.$buyPercentage.'%, SAT: '.$sellPercentage.'%, NÖTR: '.$neutralPercentage.'%">';
                                                echo '<div class="progress-bar bg-success" role="progressbar" style="width: '.$buyPercentage.'%" aria-valuenow="'.$buyPercentage.'" aria-valuemin="0" aria-valuemax="100"></div>';
                                                echo '<div class="progress-bar bg-danger" role="progressbar" style="width: '.$sellPercentage.'%" aria-valuenow="'.$sellPercentage.'" aria-valuemin="0" aria-valuemax="100"></div>';
                                                echo '<div class="progress-bar bg-secondary" role="progressbar" style="width: '.$neutralPercentage.'%" aria-valuenow="'.$neutralPercentage.'" aria-valuemin="0" aria-valuemax="100"></div>';
                                                echo '<span class="justify-content-center d-flex position-absolute w-100 '.$mainClass.'" style="line-height: 20px; font-weight: bold;">'.$mainSignal.'</span>';
                                                echo '</div>';
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $rsiValue = isset($coin['indicators']['rsi']['value']) ? $coin['indicators']['rsi']['value'] : '--';
                                            if ($rsiValue != '--') {
                                                $rsiClass = '';
                                                if ($rsiValue <= 30) $rsiClass = 'text-success';
                                                elseif ($rsiValue >= 70) $rsiClass = 'text-danger';
                                                echo '<span class="'.$rsiClass.'">'.$rsiValue.'</span>';
                                            } else {
                                                echo $rsiValue;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $macdValue = isset($coin['indicators']['macd']['value']) ? $coin['indicators']['macd']['value'] : '--';
                                            if ($macdValue != '--') {
                                                $macdClass = $macdValue >= 0 ? 'text-success' : 'text-danger';
                                                echo '<span class="'.$macdClass.'">'.number_format($macdValue, 4).'</span>';
                                            } else {
                                                echo $macdValue;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $bollinger = isset($coin['indicators']['bollinger']) ? $coin['indicators']['bollinger'] : null;
                                            if ($bollinger) {
                                                $position = '';
                                                if ($coin['price'] <= $bollinger['lower']) $position = 'Alt';
                                                elseif ($coin['price'] >= $bollinger['upper']) $position = 'Üst';
                                                else $position = 'Orta';
                                                
                                                $positionClass = '';
                                                if ($position == 'Alt') $positionClass = 'text-success';
                                                elseif ($position == 'Üst') $positionClass = 'text-danger';
                                                
                                                echo '<span class="'.$positionClass.'">'.$position.'</span>';
                                                echo ' <button class="btn btn-xs btn-link p-0" data-toggle="tooltip" title="Üst: '.number_format($bollinger['upper'], 2).' | Orta: '.number_format($bollinger['middle'], 2).' | Alt: '.number_format($bollinger['lower'], 2).'">
                                                    <i class="fas fa-info-circle small"></i>
                                                </button>';
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $ma = isset($coin['indicators']['moving_averages']) ? $coin['indicators']['moving_averages'] : null;
                                            if ($ma) {
                                                $trend = '';
                                                if (isset($ma['ma20']) && isset($ma['ma50']) && $ma['ma20'] > $ma['ma50']) {
                                                    $trend = 'Yükseliş';
                                                } elseif (isset($ma['ma20']) && isset($ma['ma50']) && $ma['ma20'] < $ma['ma50']) {
                                                    $trend = 'Düşüş';
                                                } else {
                                                    $trend = 'Yatay';
                                                }
                                                
                                                $trendClass = '';
                                                if ($trend == 'Yükseliş') $trendClass = 'text-success';
                                                elseif ($trend == 'Düşüş') $trendClass = 'text-danger';
                                                
                                                echo '<span class="'.$trendClass.'">'.$trend.'</span>';
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $adxValue = isset($coin['indicators']['adx']['value']) ? $coin['indicators']['adx']['value'] : '--';
                                            if ($adxValue != '--') {
                                                $adxClass = '';
                                                if ($adxValue >= 25) $adxClass = 'text-success'; // Güçlü trend
                                                echo '<span class="'.$adxClass.'">'.$adxValue.'</span>';
                                                
                                                // ADX trend yönü (varsa)
                                                if (isset($coin['indicators']['adx']['trend'])) {
                                                    $trendIcon = '';
                                                    if ($coin['indicators']['adx']['trend'] == 'bullish') {
                                                        $trendIcon = '<i class="fas fa-arrow-up text-success ml-1"></i>';
                                                    } elseif ($coin['indicators']['adx']['trend'] == 'bearish') {
                                                        $trendIcon = '<i class="fas fa-arrow-down text-danger ml-1"></i>';
                                                    }
                                                    echo $trendIcon;
                                                }
                                            } else {
                                                echo $adxValue;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            // Stochastic değerlerini güncelleme
                                            if (isset($coin['indicators']['stochastic'])) {
                                                // k değeri öncelikli olarak kullanılsın, yoksa value değerini kontrol et
                                                $stochValue = $coin['indicators']['stochastic']['k'] ?? $coin['indicators']['stochastic']['value'] ?? '--';
                                                $stochClass = '';
                                                
                                                if ($stochValue <= 20) $stochClass = 'text-success'; // Aşırı satım
                                                elseif ($stochValue >= 80) $stochClass = 'text-danger'; // Aşırı alım
                                                
                                                $stochHtml = '<span class="'.$stochClass.'">'.$stochValue.'</span>';
                                                
                                                // K ve D değerleri varsa tooltip ekle
                                                if (isset($coin['indicators']['stochastic']['k']) && isset($coin['indicators']['stochastic']['d'])) {
                                                    $stochHtml .= ' <button class="btn btn-xs btn-link p-0" data-toggle="tooltip" 
                                                    title="K: '.number_format($coin['indicators']['stochastic']['k'], 2).' | D: '.number_format($coin['indicators']['stochastic']['d'], 2).'">
                                                        <i class="fas fa-info-circle small"></i>
                                                    </button>';
                                                }
                                                
                                                echo $stochHtml;
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($coin['indicators']['psar']) || isset($coin['indicators']['parabolic_sar'])) {
                                                $psarData = $coin['indicators']['parabolic_sar'] ?? $coin['indicators']['psar'] ?? [];
                                                $psarValue = isset($psarData['value']) ? $psarData['value'] : '--';
                                                if ($psarValue != '--') {
                                                    $psarClass = '';
                                                    $psarTrend = isset($psarData['trend']) ? $psarData['trend'] : (isset($psarData['signal']) ? $psarData['signal'] : '');
                                                    if ($psarTrend === 'bullish') {
                                                        $psarClass = 'text-success';
                                                        echo '<span class="'.$psarClass.'">'.number_format($psarValue, 6).' <i class="fas fa-arrow-up"></i></span>';
                                                    } elseif ($psarTrend === 'bearish') {
                                                        $psarClass = 'text-danger';
                                                        echo '<span class="'.$psarClass.'">'.number_format($psarValue, 6).' <i class="fas fa-arrow-down"></i></span>';
                                                    } else {
                                                        echo number_format($psarValue, 6);
                                                    }
                                                } else {
                                                    echo $psarValue;
                                                }
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                        <td class="update-time">
                                            <?php
                                            $updateTime = isset($coin['last_updated']) ? date('H:i:s', strtotime($coin['last_updated'])) : '--';
                                            echo $updateTime;
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-info coin-detail-btn" data-coin="<?php echo htmlspecialchars(json_encode($coin)); ?>">
                                                    <i class="fas fa-chart-line"></i>
                                                </button>
                                                <a href="coin_detail.php?symbol=<?php echo urlencode($coin['symbol']); ?>" class="btn btn-primary">
                                                    <i class="fas fa-search-plus"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- İndikatörler Hakkında Bilgi Kartı -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Teknik İndikatör Açıklamaları</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6><strong>RSI (Göreceli Güç İndeksi)</strong></h6>
                                    <p class="small text-muted">0-100 arasında değer alır. <span class="text-success">30 altı aşırı satım</span> bölgesi olarak değerlendirilirken, <span class="text-danger">70 üstü aşırı alım</span> bölgesi olarak kabul edilir.</p>
                                </div>
                                <div class="mb-3">
                                    <h6><strong>MACD (Hareketli Ortalama Yakınsama/Uzaklaşma)</strong></h6>
                                    <p class="small text-muted">Kısa ve uzun vadeli hareketli ortalamaların farkını gösterir. Sinyal çizgisini yukarı kesmesi alım, aşağı kesmesi satım sinyali olarak değerlendirilir.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6><strong>Bollinger Bantları</strong></h6>
                                    <p class="small text-muted">Fiyatın oynaklığını gösterir. <span class="text-success">Alt banda yaklaşma</span> potansiyel alım, <span class="text-danger">üst banda yaklaşma</span> potansiyel satım fırsatıdır.</p>
                                </div>
                                <div class="mb-3">
                                    <h6><strong>Hareketli Ortalamalar</strong></h6>
                                    <p class="small text-muted">Kısa vadeli MA'nın uzun vadeli MA'yı yukarı kesmesi (<span class="text-success">altın çapraz</span>) alım, aşağı kesmesi (<span class="text-danger">ölüm çaprazı</span>) satım sinyalidir.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Coin Detay Modalı -->
    <div class="modal fade" id="coinDetailModal" tabindex="-1" role="dialog" aria-labelledby="coinDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="coinDetailModalLabel">Coin Detayları</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Temel Bilgiler</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Sembol:</strong></td>
                                            <td id="detail-symbol"></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fiyat:</strong></td>
                                            <td id="detail-price"></td>
                                        </tr>
                                        <tr>
                                            <td><strong>24s Değişim:</strong></td>
                                            <td id="detail-change"></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sinyal:</strong></td>
                                            <td id="detail-signal"></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sinyal Sebebi:</strong></td>
                                            <td id="detail-reason"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Teknik İndikatörler</h6>
                                </div>
                                <div class="card-body">
                                    <div id="indicators-container">
                                        <!-- İndikatörler burada yer alacak -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="detail-more-link" href="#" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Detaylı Analiz
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gizli API test iframe'i -->
    <iframe id="api-test-frame" src="api/direct_price_test.php" style="width:0;height:0;border:0; border:none; position: absolute;"></iframe>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Global değişkenler
            let currentInterval = '5m'; // Varsayılan zaman aralığı
            
            // Tooltips'i etkinleştir
            $('[data-toggle="tooltip"]').tooltip();
            
            // Arama filtreleme
            $("#coin-search").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#coin-table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
            
            // Zaman aralığı seçimi butonu işlemleri
            $(".time-interval-btn").on("click", function() {
                const interval = $(this).data('interval');
                
                // Aktif buton sınıfını değiştir
                $(".time-interval-btn").removeClass('active');
                $(this).addClass('active');
                
                // Seçilen zaman aralığını güncelle
                currentInterval = interval;
                
                // Verileri yeni zaman aralığıyla yükle
                loadCoinData(currentInterval);
                
                // Kullanıcıya bilgi ver
                $("#data-status-text").html('<i class="fas fa-spinner fa-spin"></i> ' + interval + ' veriler yükleniyor');
            });
            
            // Mevcut fiyatları saklamak için global değişken - blur efektini önlemek için
            let priceCache = {};
            let pendingUpdates = {};
            let updateQueue = [];
            let isUpdating = false;

            // requestAnimationFrame kullanarak daha verimli güncellemeler
            function processPriceUpdates() {
                if (updateQueue.length === 0) {
                    isUpdating = false;
                    return;
                }
                
                isUpdating = true;
                
                // Her animasyon frame'inde sadece birkaç güncelleme yap
                // bu sayede tarayıcı aşırı yüklenmeyecek
                const batchSize = Math.min(5, updateQueue.length);
                const currentBatch = updateQueue.splice(0, batchSize);
                
                currentBatch.forEach(update => {
                    const { symbol, price, priceCell, changePercent, changeCell } = update;
                    
                    // Fiyat güncellemesi
                    if (priceCell) {
                        const formattedPrice = formatPrice(price);
                        priceCell.text(formattedPrice);
                        priceCell.attr('data-price', price);
                        
                        // Sadece fiyat değiştiyse animasyonu tetikle
                        if (priceCache[symbol] && priceCache[symbol] !== price) {
                            const priceDirection = price > priceCache[symbol] ? 'price-up' : 'price-down';
                            
                            // Animasyon sınıfını uygula
                            priceCell.removeClass('price-up price-down');
                            void priceCell.offsetWidth; // CSS animasyonu yeniden başlatmak için
                            priceCell.addClass(priceDirection);
                        }
                        
                        // Cache'i güncelle
                        priceCache[symbol] = price;
                    }
                    
                    // 24s değişim güncellemesi
                    if (changeCell && changePercent !== undefined) {
                        if (changePercent > 0) {
                            changeCell.removeClass('change-negative change-neutral').addClass('change-positive');
                            changeCell.html(`<i class="fas fa-caret-up"></i> ${changePercent.toFixed(2)}%`);
                        } else if (changePercent < 0) {
                            changeCell.removeClass('change-positive change-neutral').addClass('change-negative');
                            changeCell.html(`<i class="fas fa-caret-down"></i> ${Math.abs(changePercent).toFixed(2)}%`);
                        } else {
                            changeCell.removeClass('change-positive change-negative').addClass('change-neutral');
                            changeCell.text('0.00%');
                        }
                    }
                });
                
                // Daha fazla güncelleme varsa bir sonraki frame'de devam et
                if (updateQueue.length > 0) {
                    requestAnimationFrame(processPriceUpdates);
                } else {
                    isUpdating = false;
                }
            }

            // Zaman aralığına göre coin verilerini yükle
            function loadCoinData(interval) {
                // URL'yi oluştur ve konsola yazdır (hata ayıklama için)
                const apiUrl = `api/get_active_coins.php?interval=${interval}`;
                console.log(`Coin verileri yükleniyor... URL: ${apiUrl}`);
                
                // Yükleme göstergesini göster
                $("#loading-overlay").addClass('visible');
                
                // Veri durumunu "Yükleniyor" olarak ayarla
                updateDataStatus("loading");
                
                // API'den verileri çek
                fetch(apiUrl)
                    .then(response => {
                        console.log("API yanıt durumu:", response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP hata! Durum: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("API yanıtı:", data); // Yanıtı konsola yazdır
                        if (data && data.success && data.data) {
                            // Coin verilerini güncelle
                            updateCoinAnalysis(data.data);
                            
                            // Sonra fiyatları güncelle
                            fetchPriceData();
                            
                            // Durum göstergesini güncelle
                            updateDataStatus("success");
                        } else {
                            console.error("API'den veriler alınamadı:", data);
                            updateDataStatus("error");
                        }
                    })
                    .catch(error => {
                        console.error("Veri yükleme hatası:", error);
                        updateDataStatus("error");
                    })
                    .finally(() => {
                        // Yükleme göstergesini gizle
                        $("#loading-overlay").removeClass('visible');
                        
                        // Son güncelleme zamanını güncelle
                        const now = new Date();
                        $("#last-update-time").text(now.toLocaleTimeString());
                    });
            }
            
            // Coin analiz verilerini güncelleme fonksiyonu
            function updateCoinAnalysis(coinData) {
                if (!coinData || !Array.isArray(coinData)) return;
                
                // Her bir coin için analizleri güncelle
                coinData.forEach(coin => {
                    try {
                        // İlgili satırı bul
                        const row = $(`#coin-table tbody tr[data-symbol="${coin.symbol}"]`);
                        if (row.length === 0) return;
                        
                        // Sinyal güncelleme
                        const signal = coin.signal?.toUpperCase() || 'NEUTRAL';
                        let signalClass = 'badge-secondary';
                        let signalText = 'BEKLİYOR';
                        let dotClass = 'dot-neutral';
                        
                        if (signal === 'BUY') {
                            signalClass = 'badge-success';
                            signalText = 'AL';
                            dotClass = 'dot-buy';
                            row.addClass('coin-signal-buy').removeClass('coin-signal-sell');
                        } else if (signal === 'SELL') {
                            signalClass = 'badge-danger';
                            signalText = 'SAT';
                            dotClass = 'dot-sell';
                            row.addClass('coin-signal-sell').removeClass('coin-signal-buy');
                        } else {
                            row.removeClass('coin-signal-buy coin-signal-sell');
                        }
                        
                        const signalHtml = `
                            <span class="signal-dot ${dotClass}"></span>
                            <span class="badge ${signalClass}">${signalText}</span>
                        `;
                        row.find('td:nth-child(4)').html(signalHtml);
                        
                        // RSI güncelleme
                        if (coin.indicators && coin.indicators.rsi) {
                            const rsiValue = coin.indicators.rsi.value;
                            let rsiClass = '';
                            if (rsiValue <= 30) rsiClass = 'text-success';
                            else if (rsiValue >= 70) rsiClass = 'text-danger';
                            row.find('td:nth-child(6)').html(`<span class="${rsiClass}">${rsiValue}</span>`);
                        }
                        
                        // MACD güncelleme
                        if (coin.indicators && coin.indicators.macd) {
                            const macdValue = coin.indicators.macd.value;
                            const macdClass = macdValue >= 0 ? 'text-success' : 'text-danger';
                            row.find('td:nth-child(7)').html(`<span class="${macdClass}">${parseFloat(macdValue).toFixed(4)}</span>`);
                        }
                        
                        // Bollinger güncelleme
                        if (coin.indicators && coin.indicators.bollinger) {
                            const bb = coin.indicators.bollinger;
                            let position = '';
                            let positionClass = '';
                            
                            if (coin.price <= bb.lower) {
                                position = 'Alt';
                                positionClass = 'text-success';
                            } else if (coin.price >= bb.upper) {
                                position = 'Üst';
                                positionClass = 'text-danger';
                            } else {
                                position = 'Orta';
                            }
                            
                            const bbHtml = `
                                <span class="${positionClass}">${position}</span>
                                <button class="btn btn-xs btn-link p-0" data-toggle="tooltip" title="Üst: ${parseFloat(bb.upper).toFixed(2)} | Orta: ${parseFloat(bb.middle).toFixed(2)} | Alt: ${parseFloat(bb.lower).toFixed(2)}">
                                    <i class="fas fa-info-circle small"></i>
                                </button>
                            `;
                            row.find('td:nth-child(8)').html(bbHtml);
                        }
                        
                        // Hareketli Ortalamalar güncelleme
                        if (coin.indicators && coin.indicators.moving_averages) {
                            const ma = coin.indicators.moving_averages;
                            let trend = '';
                            let trendClass = '';
                            
                            if (ma.ma20 > ma.ma50) {
                                trend = 'Yükseliş';
                                trendClass = 'text-success';
                            } else if (ma.ma20 < ma.ma50) {
                                trend = 'Düşüş';
                                trendClass = 'text-danger';
                            } else {
                                trend = 'Yatay';
                            }
                            
                            row.find('td:nth-child(9)').html(`<span class="${trendClass}">${trend}</span>`);
                        }
                        
                        // ADX değerlerini güncelleme
                        if (coin.indicators && coin.indicators.adx) {
                            const adxValue = coin.indicators.adx.value;
                            const adxTrend = coin.indicators.adx.trend || '';
                            let adxClass = '';
                            
                            if (adxValue >= 25) {
                                adxClass = adxTrend === 'bullish' ? 'text-success' : (adxTrend === 'bearish' ? 'text-danger' : '');
                            }
                            
                            const adxHtml = `<span class="${adxClass}">${adxValue}</span>`;
                            if (adxTrend) {
                                const trendIcon = adxTrend === 'bullish' ? 
                                    '<i class="fas fa-arrow-up text-success ml-1"></i>' : 
                                    (adxTrend === 'bearish' ? '<i class="fas fa-arrow-down text-danger ml-1"></i>' : '');
                                row.find('td:nth-child(10)').html(`${adxHtml} ${trendIcon}`);
                            } else {
                                row.find('td:nth-child(10)').html(adxHtml);
                            }
                        }
                        
                        // Stochastic değerlerini güncelleme
                        if (coin.indicators && coin.indicators.stochastic) {
                            // k değeri öncelikli olarak kullanılsın, yoksa value değerini kontrol et
                            const stochValue = coin.indicators.stochastic.k || coin.indicators.stochastic.value || '--';
                            let stochClass = '';
                            
                            if (stochValue <= 20) stochClass = 'text-success'; // Aşırı satım
                            else if (stochValue >= 80) stochClass = 'text-danger'; // Aşırı alım
                            
                            let stochHtml = `<span class="${stochClass}">${stochValue}</span>`;
                            
                            // K ve D değerleri varsa tooltip ekle
                            if (coin.indicators.stochastic.k && coin.indicators.stochastic.d) {
                                stochHtml += ` <button class="btn btn-xs btn-link p-0" data-toggle="tooltip" 
                                title="K: ${parseFloat(coin.indicators.stochastic.k).toFixed(2)} | D: ${parseFloat(coin.indicators.stochastic.d).toFixed(2)}">
                                    <i class="fas fa-info-circle small"></i>
                                </button>`;
                            }
                            
                            row.find('td:nth-child(11)').html(stochHtml);
                        }
                        
                        // PSAR değerlerini güncelleme
                        if (coin.indicators && (coin.indicators.psar || coin.indicators.parabolic_sar)) {
                            // İki anahtardan birini kontrol et (API farklı anahtarlar kullanabilir)
                            const psarData = coin.indicators.parabolic_sar || coin.indicators.psar || {};
                            const psarValue = psarData.value || '--';
                            
                            if (psarValue !== '--') {
                                let psarClass = '';
                                
                                // Trend bilgisini kontrol et (iki olası anahtar)
                                const psarTrend = psarData.trend || psarData.signal || '';
                                
                                if (psarTrend === 'bullish') psarClass = 'text-success';
                                else if (psarTrend === 'bearish') psarClass = 'text-danger';
                                
                                const psarHtml = `<span class="${psarClass}">${parseFloat(psarValue).toFixed(6)} ${
                                    psarTrend === 'bullish' ? '<i class="fas fa-arrow-up"></i>' : 
                                    (psarTrend === 'bearish' ? '<i class="fas fa-arrow-down"></i>' : '')
                                }</span>`;
                                
                                row.find('td:nth-child(12)').html(psarHtml);
                            } else {
                                row.find('td:nth-child(12)').text('--');
                            }
                        }
                        
                        // Son güncelleme zamanı
                        if (coin.last_updated) {
                            const updateTime = new Date(coin.last_updated).toLocaleTimeString();
                            row.find('.update-time').text(updateTime);
                        }
                        
                        // Toplam analiz göstergesini güncelle
                        updateTotalAnalysis(row, coin);
                        
                        // Coin detay butonunu güncelle
                        row.find('.coin-detail-btn').data('coin', coin);
                        
                    } catch (err) {
                        console.error(`${coin.symbol} analizi güncellenirken hata:`, err);
                    }
                });
                
                // Tooltip'leri yeniden başlat
                $('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
            }
            
            // Toplam analiz değerlerini hesaplama ve güncelleme
            function updateTotalAnalysis(row, coin) {
                // Toplam analiz değerini hesapla ve görüntüle
                let totalBuySignals = 0;
                let totalSellSignals = 0;
                let totalNeutralSignals = 0;
                let totalSignals = 0;
                
                // RSI sinyali
                if (coin.indicators && coin.indicators.rsi) {
                    totalSignals++;
                    if (coin.indicators.rsi.value <= 30) totalBuySignals++;
                    else if (coin.indicators.rsi.value >= 70) totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // MACD sinyali
                if (coin.indicators && coin.indicators.macd) {
                    totalSignals++;
                    if (coin.indicators.macd.signal === 'BUY') totalBuySignals++;
                    else if (coin.indicators.macd.signal === 'SELL') totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // Bollinger Bantları sinyali
                if (coin.indicators && coin.indicators.bollinger) {
                    totalSignals++;
                    if (coin.price <= coin.indicators.bollinger.lower) totalBuySignals++;
                    else if (coin.price >= coin.indicators.bollinger.upper) totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // Hareketli Ortalama sinyali
                if (coin.indicators && coin.indicators.moving_averages) {
                    totalSignals++;
                    if (coin.indicators.moving_averages.ma20 > coin.indicators.moving_averages.ma50) totalBuySignals++;
                    else if (coin.indicators.moving_averages.ma20 < coin.indicators.moving_averages.ma50) totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // ADX sinyali
                if (coin.indicators && coin.indicators.adx) {
                    totalSignals++;
                    if (coin.indicators.adx.trend === 'bullish' && coin.indicators.adx.value >= 25) totalBuySignals++;
                    else if (coin.indicators.adx.trend === 'bearish' && coin.indicators.adx.value >= 25) totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // Stochastic sinyali
                if (coin.indicators && coin.indicators.stochastic) {
                    totalSignals++;
                    if (coin.indicators.stochastic.value <= 20) totalBuySignals++;
                    else if (coin.indicators.stochastic.value >= 80) totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // PSAR sinyali
                if (coin.indicators && (coin.indicators.psar || coin.indicators.parabolic_sar)) {
                    const psarData = coin.indicators.parabolic_sar || coin.indicators.psar || {};
                    totalSignals++;
                    if (psarData.trend === 'bullish') totalBuySignals++;
                    else if (psarData.trend === 'bearish') totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // TradingView sinyali (varsa)
                if (coin.indicators && coin.indicators.tradingview) {
                    totalSignals++;
                    if (coin.indicators.tradingview.signal === 'BUY') totalBuySignals++;
                    else if (coin.indicators.tradingview.signal === 'SELL') totalSellSignals++;
                    else totalNeutralSignals++;
                }
                
                // Toplam sinyal puanı (indikatör sayısına göre değişir)
                if (totalSignals > 0) {
                    const buyPercentage = Math.round((totalBuySignals / totalSignals) * 100);
                    const sellPercentage = Math.round((totalSellSignals / totalSignals) * 100);
                    const neutralPercentage = Math.round((totalNeutralSignals / totalSignals) * 100);
                    
                    // Ana rengi belirle
                    let mainClass, mainSignal;
                    if (buyPercentage > sellPercentage && buyPercentage > neutralPercentage) {
                        mainClass = 'text-success';
                        mainSignal = 'AL';
                    } else if (sellPercentage > buyPercentage && sellPercentage > neutralPercentage) {
                        mainClass = 'text-danger';
                        mainSignal = 'SAT';
                    } else {
                        mainClass = 'text-secondary';
                        mainSignal = 'NÖTR';
                    }
                    
                    // Progress bar HTML'i
                    const analysisHtml = `
                        <div class="progress position-relative" style="height: 20px;" data-toggle="tooltip" title="AL: ${buyPercentage}%, SAT: ${sellPercentage}%, NÖTR: ${neutralPercentage}%">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${buyPercentage}%" aria-valuenow="${buyPercentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: ${sellPercentage}%" aria-valuenow="${sellPercentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: ${neutralPercentage}%" aria-valuenow="${neutralPercentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            <span class="justify-content-center d-flex position-absolute w-100 ${mainClass}" style="line-height: 20px; font-weight: bold;">${mainSignal}</span>
                        </div>
                    `;
                    
                    row.find('.analysis-cell').html(analysisHtml);
                } else {
                    row.find('.analysis-cell').html('--');
                }
            }

            // Doğrudan API'den veri çekme fonksiyonu - Sembol uyumsuzluklarını çözen gelişmiş versiyon
            function fetchPriceData() {
                console.log("Doğrudan API'den fiyat verisi alınıyor...");
                
                // İlgili sembolleri topla ve format dönüşümlerini hazırla
                const coinSymbols = [];
                const symbolMap = {}; // Tablodaki sembol -> API sembolü eşleştirmesi
                
                $("#coin-table tbody tr").each(function() {
                    const symbol = $(this).attr('data-symbol');
                    if (symbol) {
                        // Olası format dönüşümleri (örn: BTC/USDT -> BTCUSDT veya BTCUSDT -> BTCUSDT)
                        const apiSymbol = symbol.replace('/', '');
                        coinSymbols.push(apiSymbol);
                        symbolMap[apiSymbol] = symbol; // Eşleştirme kaydı
                    }
                });
                
                if (coinSymbols.length === 0) {
                    updateDataStatus("error");
                    return;
                }
                
                // Direkt olarak public Binance API'sını kullanarak tüm sembollerin fiyatlarını çek
                fetch('https://api.binance.com/api/v3/ticker/24hr')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP hata! Durum: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && Array.isArray(data) && data.length > 0) {
                            const processedData = {};
                            let matchCount = 0;
                            
                            // Tablodaki her coin için uygun fiyatları bulmaya çalış
                            coinSymbols.forEach(apiSymbol => {
                                // API verisinde bu sembole uygun kaydı ara
                                const ticker = data.find(item => item.symbol === apiSymbol);
                                
                                if (ticker) {
                                    // Eşleşen veri bulundu
                                    matchCount++;
                                    processedData[symbolMap[apiSymbol] || apiSymbol] = ticker;
                                }
                            });
                            
                            if (matchCount > 0) {
                                // Verileri güncelle
                                updatePrices(processedData);
                                updateDataStatus("success");
                            } else {
                                useBackupMethod();
                            }
                        } else {
                            useBackupMethod();
                        }
                    })
                    .catch(error => {
                        console.error("Binance API hatası:", error);
                        useBackupMethod();
                    })
                    .finally(() => {
                        // Son güncelleme zamanını güncelle
                        $("#last-update-time").text(new Date().toLocaleTimeString());
                    });
            }
            
            // Fiyat verilerini güncelleme fonksiyonu - Gelişmiş hata işleme ve smooth animasyonlar
            function updatePrices(data) {
                if (!data) return;
                
                let updatedCount = 0;
                
                // Update queue'yu temizle
                updateQueue = [];
                
                // Her bir sembol için verileri güncelle
                Object.keys(data).forEach(symbol => {
                    const coinData = data[symbol];
                    if (!coinData) return;
                    
                    try {
                        // Tablodan ilgili satırı bul
                        const row = $(`#coin-table tbody tr[data-symbol="${symbol}"]`);
                        if (row.length === 0) return;
                        
                        // Sembol için fiyat var mı kontrol et
                        const price = parseFloat(coinData.lastPrice || coinData.price || '0');
                        if (isNaN(price) || price <= 0) return;
                        
                        // Fiyat ve değişim hücrelerini bul
                        const priceCell = row.find('.price-cell');
                        const changeCell = row.find('.change-cell');
                        const changePercent = parseFloat(coinData.priceChangePercent || '0');
                        
                        // Güncelleme kuyruğuna ekle
                        updateQueue.push({
                            symbol,
                            price,
                            priceCell,
                            changePercent,
                            changeCell
                        });
                        
                        updatedCount++;
                    } catch (err) {
                        console.error(`${symbol} güncellenirken hata:`, err);
                    }
                });
                
                // Eğer güncelleme bekleyen fiyatlar varsa ve şu an güncelleme yapmıyorsak işleme başla
                if (updateQueue.length > 0 && !isUpdating) {
                    requestAnimationFrame(processPriceUpdates);
                }
                
                if (updatedCount === 0) {
                    updateDataStatus("error");
                }
            }
            
            // Yedek veri alma metodu
            function useBackupMethod() {
                updateDataStatus("warning");
                
                fetch('api/direct_price_test.php?action=get_prices')
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success) {
                            updatePrices(data.data);
                            updateDataStatus("success");
                        } else {
                            updateDataStatus("error");
                        }
                    })
                    .catch(error => {
                        updateDataStatus("error");
                    });
            }
            
            // Veri durum göstergesini güncelleme fonksiyonu
            function updateDataStatus(status) {
                const statusIcon = $('#data-status-icon');
                const statusText = $('#data-status-text');
                
                switch(status) {
                    case "success":
                        statusIcon.removeClass("bg-warning bg-danger").addClass("bg-success");
                        statusText.removeClass("text-warning text-danger").addClass("text-success");
                        statusText.html('<i class="fas fa-database"></i> Veri');
                        break;
                    case "error":
                        statusIcon.removeClass("bg-warning bg-success").addClass("bg-danger");
                        statusText.removeClass("text-warning text-success").addClass("text-danger");
                        statusText.html('<i class="fas fa-exclamation-triangle"></i> Veri');
                        break;
                    case "warning":
                        statusIcon.removeClass("bg-success bg-danger").addClass("bg-warning");
                        statusText.removeClass("text-success text-danger").addClass("text-warning");
                        statusText.html('<i class="fas fa-exclamation-circle"></i> Veri');
                        break;
                    case "loading":
                        statusIcon.removeClass("bg-success bg-danger").addClass("bg-warning");
                        statusText.removeClass("text-success text-danger").addClass("text-warning");
                        statusText.html('<i class="fas fa-spinner fa-spin"></i> Veri');
                        break;
                }
            }
            
            // Sayı ve fiyat formatı fonksiyonu
            function formatPrice(price) {
                if (isNaN(price) || price <= 0) return '0';
                
                if (price < 0.00001) {
                    return price.toExponential(6);
                } else if (price < 0.0001) {
                    return price.toFixed(8);
                } else if (price < 0.01) {
                    return price.toFixed(6); 
                } else if (price < 1) {
                    return price.toFixed(4);
                } else if (price < 10) {
                    return price.toFixed(3);
                } else if (price < 1000) {
                    return price.toFixed(2);
                } else {
                    return price.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }

            // İlk yüklemede verileri al
            loadCoinData(currentInterval);
            
            // Periyodik olarak verileri güncelle (30 saniyede bir)
            setInterval(() => loadCoinData(currentInterval), 30000);
            
            // Yenile butonu işlemleri
            $("#refresh-data").click(function() {
                loadCoinData(currentInterval);
            });

            // Detail butonlarına click olayı ekleme
            $(document).on("click", ".coin-detail-btn", function() {
                try {
                    const coinData = $(this).data('coin');
                    
                    // Temel bilgileri doldur
                    $("#detail-symbol").text(coinData.symbol);
                    
                    // Güncel fiyat bilgisini al
                    const priceCell = $(this).closest('tr').find('.price-cell');
                    const currentPrice = priceCell.text() !== '--' ? parseFloat(priceCell.text().replace(',', '')) : (coinData.price || '--');
                    $("#detail-price").text(currentPrice !== '--' ? currentPrice.toFixed(8).replace(/\.?0+$/, '') : '--');
                    
                    // 24s değişimi al
                    const changeCell = $(this).closest('tr').find('.change-cell');
                    const currentChange = changeCell.text() || '--';
                    if (currentChange !== '--') {
                        const changeClass = currentChange.includes('caret-up') ? 'text-success' : 'text-danger';
                        $("#detail-change").html(`<span class="${changeClass}">${currentChange}</span>`);
                    } else {
                        $("#detail-change").text('--');
                    }
                    
                    // Sinyal
                    let signalClass = 'badge-secondary';
                    let signalText = 'BEKLİYOR';
                    
                    if (coinData.signal === 'BUY') {
                        signalClass = 'badge-success';
                        signalText = 'AL';
                    } else if (coinData.signal === 'SELL') {
                        signalClass = 'badge-danger';
                        signalText = 'SAT';
                    }
                    
                    $("#detail-signal").html(`<span class="badge ${signalClass}">${signalText}</span>`);
                    $("#detail-reason").text(coinData.reason || 'Belirtilmemiş');
                    
                    // İndikatörleri doldur
                    const indicators = coinData.indicators || {};
                    let indicatorsHtml = '';
                    
                    // RSI
                    if (indicators.rsi) {
                        let rsiValue = indicators.rsi.value;
                        let rsiClass = '';
                        if (rsiValue <= 30) rsiClass = 'rsi-low';
                        else if (rsiValue >= 70) rsiClass = 'rsi-high';
                        else rsiClass = 'rsi-neutral';
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">RSI:</span>
                                <span class="${rsiClass}">${rsiValue}</span>
                            </div>
                        `;
                    }
                    
                    // MACD
                    if (indicators.macd) {
                        const macdValue = indicators.macd.value;
                        const macdClass = macdValue >= 0 ? 'text-success' : 'text-danger';
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">MACD:</span>
                                <span class="${macdClass}">${parseFloat(macdValue).toFixed(4)}</span>
                            </div>
                        `;
                    }
                    
                    // Bollinger Bands
                    if (indicators.bollinger) {
                        const bb = indicators.bollinger;
                        let position = '';
                        let positionClass = '';
                        
                        if (currentPrice <= bb.lower) {
                            position = 'Alt bant (Alım bölgesi)';
                            positionClass = 'text-success';
                        } else if (currentPrice >= bb.upper) {
                            position = 'Üst bant (Satım bölgesi)';
                            positionClass = 'text-danger';
                        } else {
                            position = 'Orta bant';
                            positionClass = '';
                        }
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">Bollinger:</span>
                                <span class="${positionClass}">${position}</span>
                            </div>
                            <div class="indicator-detail pl-3">
                                <span class="indicator-label small">- Üst:</span>
                                <span>${parseFloat(bb.upper).toFixed(4)}</span>
                            </div>
                            <div class="indicator-detail pl-3">
                                <span class="indicator-label small">- Orta:</span>
                                <span>${parseFloat(bb.middle).toFixed(4)}</span>
                            </div>
                            <div class="indicator-detail pl-3">
                                <span class="indicator-label small">- Alt:</span>
                                <span>${parseFloat(bb.lower).toFixed(4)}</span>
                            </div>
                        `;
                    }
                    
                    // Hareketli Ortalamalar
                    if (indicators.moving_averages) {
                        const ma = indicators.moving_averages;
                        let trend = '';
                        let trendClass = '';
                        
                        if (ma.ma20 > ma.ma50) {
                            trend = 'Yükseliş trendi (Altın çapraz)';
                            trendClass = 'text-success';
                        } else if (ma.ma20 < ma.ma50) {
                            trend = 'Düşüş trendi (Ölüm çaprazı)';
                            trendClass = 'text-danger';
                        } else {
                            trend = 'Yatay seyir';
                            trendClass = '';
                        }
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">Hareketli Ort.:</span>
                                <span class="${trendClass}">${trend}</span>
                            </div>
                            <div class="indicator-detail pl-3">
                                <span class="indicator-label small">- MA20:</span>
                                <span>${parseFloat(ma.ma20).toFixed(4)}</span>
                            </div>
                            <div class="indicator-detail pl-3">
                                <span class="indicator-label small">- MA50:</span>
                                <span>${parseFloat(ma.ma50).toFixed(4)}</span>
                            </div>
                        `;
                    }
                    
                    // ADX
                    if (indicators.adx) {
                        const adxValue = indicators.adx.value;
                        const adxTrend = indicators.adx.trend || '';
                        let adxClass = '';
                        
                        if (adxValue >= 25) {
                            adxClass = adxTrend === 'bullish' ? 'text-success' : (adxTrend === 'bearish' ? 'text-danger' : '');
                        }
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">ADX:</span>
                                <span class="${adxClass}">${adxValue}${adxTrend ? ' (' + (adxTrend === 'bullish' ? 'Yükseliş' : 'Düşüş') + ')' : ''}</span>
                            </div>
                        `;
                    }
                    
                    // Stochastic
                    if (indicators.stochastic) {
                        const stochValue = indicators.stochastic.k || indicators.stochastic.value || '--';
                        let stochClass = '';
                        
                        if (stochValue <= 20) stochClass = 'text-success'; // Aşırı satım
                        else if (stochValue >= 80) stochClass = 'text-danger'; // Aşırı alım
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">Stochastic:</span>
                                <span class="${stochClass}">${stochValue}</span>
                            </div>
                        `;
                        
                        // Stochastic K ve D değerleri (varsa)
                        if (indicators.stochastic.k && indicators.stochastic.d) {
                            indicatorsHtml += `
                                <div class="indicator-detail pl-3">
                                    <span class="indicator-label small">- %K:</span>
                                    <span>${parseFloat(indicators.stochastic.k).toFixed(2)}</span>
                                </div>
                                <div class="indicator-detail pl-3">
                                    <span class="indicator-label small">- %D:</span>
                                    <span>${parseFloat(indicators.stochastic.d).toFixed(2)}</span>
                                </div>
                            `;
                        }
                    }
                    
                    // Parabolic SAR
                    if (indicators.psar || indicators.parabolic_sar) {
                        const psarData = indicators.parabolic_sar || indicators.psar || {};
                        const psarValue = psarData.value || '--';
                        const psarTrend = psarData.trend || psarData.signal || '';
                        let psarClass = '';
                        
                        if (psarTrend === 'bullish') psarClass = 'text-success';
                        else if (psarTrend === 'bearish') psarClass = 'text-danger';
                        
                        indicatorsHtml += `
                            <div class="indicator-detail">
                                <span class="indicator-label">PSAR:</span>
                                <span class="${psarClass}">${parseFloat(psarValue).toFixed(6)} ${
                                    psarTrend === 'bullish' ? '<i class="fas fa-arrow-up"></i>' : 
                                    (psarTrend === 'bearish' ? '<i class="fas fa-arrow-down"></i>' : '')
                                }</span>
                            </div>
                        `;
                    }
                    
                    $("#indicators-container").html(indicatorsHtml || '<div class="text-muted">İndikatör verisi bulunamadı.</div>');
                    
                    // Detay linki
                    $("#detail-more-link").attr('href', `coin_detail.php?symbol=${encodeURIComponent(coinData.symbol)}&interval=${currentInterval}`);
                    
                    // Modalı göster
                    $("#coinDetailModal").modal('show');
                } catch (error) {
                    console.error("Coin verisi ayrıştırılamadı:", error);
                    alert("Coin detay bilgileri yüklenemedi. Lütfen sayfayı yenileyip tekrar deneyin.");
                }
            });
        });
    </script>
</body>
</html>