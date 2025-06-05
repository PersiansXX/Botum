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

// Coin sembolünü kontrol et
if (!isset($_GET['symbol'])) {
    header('Location: coins.php');
    exit;
}

$symbol = trim($_GET['symbol']);
$all_coins = $bot_api->getActiveCoins();

// Seçilen coin'i bul
$selected_coin = null;
foreach ($all_coins as $coin) {
    if ($coin['symbol'] === $symbol) {
        $selected_coin = $coin;
        break;
    }
}

// Coin bulunamadıysa listeye yönlendir
if (!$selected_coin) {
    header('Location: coins.php');
    exit;
}

// Grafik için sembolü hazırla (örn. BTCUSDT -> BINANCE:BTCUSDT)
$tradingview_symbol = str_replace('/', '', $symbol); // Varsa / işaretini kaldır
$exchange = $bot_api->getSettings()['exchange'] ?? 'BINANCE';
$chart_symbol = strtoupper($exchange) . ':' . strtoupper($tradingview_symbol);

// Sayfa başlığı
$page_title = $symbol . ' Detaylı Analiz';
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
    <style>
        /* İndikatör bilgi kartları için stil */
        .indicator-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .indicator-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .indicator-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Sinyaller için renkler */
        .signal-buy {
            color: #28a745;
        }
        
        .signal-sell {
            color: #dc3545;
        }
        
        .signal-neutral {
            color: #6c757d;
        }
        
        /* Grafik konteyneri için stil */
        .chart-container {
            width: 100%;
            height: 600px;
            position: relative;
        }
        
        /* Yükleniyor göstergesi */
        .chart-loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(255,255,255,0.8);
            z-index: 10;
        }
    </style>
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
                <!-- Başlık ve Ana Bilgiler -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i> <?php echo $symbol; ?> Detaylı Analiz
                        </h5>
                        <div class="d-flex">
                            <a href="coins.php" class="btn btn-sm btn-light mr-2">
                                <i class="fas fa-arrow-left"></i> Coin Listesine Dön
                            </a>
                            <button id="refresh-data" class="btn btn-sm btn-light">
                                <i class="fas fa-sync-alt"></i> Verileri Yenile
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Fiyat Bilgisi -->
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 indicator-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign indicator-icon text-primary"></i>
                                        <h6>Güncel Fiyat</h6>
                                        <h4 id="current-price"><?php echo number_format($selected_coin['price'], $selected_coin['price'] < 10 ? 6 : 2); ?></h4>
                                        <p class="mb-0">
                                            <span class="<?php echo $selected_coin['change_24h'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $selected_coin['change_24h'] >= 0 ? '+' : ''; ?><?php echo number_format($selected_coin['change_24h'], 2); ?>%
                                            </span>
                                            <small class="text-muted">(24s)</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ana Sinyal -->
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 indicator-card">
                                    <div class="card-body text-center">
                                        <?php 
                                        $signal = strtoupper($selected_coin['signal'] ?? 'NEUTRAL');
                                        $signal_class = $signal == 'BUY' ? 'signal-buy' : ($signal == 'SELL' ? 'signal-sell' : 'signal-neutral');
                                        $signal_icon = $signal == 'BUY' ? 'fa-arrow-up' : ($signal == 'SELL' ? 'fa-arrow-down' : 'fa-minus');
                                        $signal_text = $signal == 'BUY' ? 'AL' : ($signal == 'SELL' ? 'SAT' : 'BEKLİYOR');
                                        ?>
                                        <i class="fas <?php echo $signal_icon; ?> indicator-icon <?php echo $signal_class; ?>"></i>
                                        <h6>Sinyal</h6>
                                        <h4 class="<?php echo $signal_class; ?>"><?php echo $signal_text; ?></h4>
                                        <p class="mb-0 small text-muted">
                                            <?php echo $selected_coin['reason'] ?? 'İşlem sinyali henüz oluşmadı'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- RSI Değeri -->
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 indicator-card">
                                    <div class="card-body text-center">
                                        <?php 
                                        $rsi_value = isset($selected_coin['indicators']['rsi']['value']) ? $selected_coin['indicators']['rsi']['value'] : '--';
                                        $rsi_class = '';
                                        $rsi_status = '';
                                        
                                        if ($rsi_value != '--') {
                                            if ($rsi_value <= 30) {
                                                $rsi_class = 'signal-buy';
                                                $rsi_status = 'Aşırı Satım';
                                            } elseif ($rsi_value >= 70) {
                                                $rsi_class = 'signal-sell';
                                                $rsi_status = 'Aşırı Alım';
                                            } else {
                                                $rsi_class = 'signal-neutral';
                                                $rsi_status = 'Normal';
                                            }
                                        }
                                        ?>
                                        <i class="fas fa-tachometer-alt indicator-icon <?php echo $rsi_class; ?>"></i>
                                        <h6>RSI (14)</h6>
                                        <h4 class="<?php echo $rsi_class; ?>"><?php echo $rsi_value; ?></h4>
                                        <p class="mb-0 small">
                                            <?php echo $rsi_status; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- MACD -->
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 indicator-card">
                                    <div class="card-body text-center">
                                        <?php 
                                        $macd_value = isset($selected_coin['indicators']['macd']['value']) ? $selected_coin['indicators']['macd']['value'] : null;
                                        $macd_signal = isset($selected_coin['indicators']['macd']['signal']) ? $selected_coin['indicators']['macd']['signal'] : 'NEUTRAL';
                                        $macd_class = $macd_signal == 'BUY' ? 'signal-buy' : ($macd_signal == 'SELL' ? 'signal-sell' : 'signal-neutral');
                                        $macd_icon = $macd_signal == 'BUY' ? 'fa-arrow-up' : ($macd_signal == 'SELL' ? 'fa-arrow-down' : 'fa-minus');
                                        ?>
                                        <i class="fas <?php echo $macd_icon; ?> indicator-icon <?php echo $macd_class; ?>"></i>
                                        <h6>MACD</h6>
                                        <h4 class="<?php echo $macd_class; ?>">
                                            <?php echo $macd_value !== null ? number_format($macd_value, 4) : '--'; ?>
                                        </h4>
                                        <p class="mb-0 small text-muted">
                                            <?php 
                                            if ($macd_signal == 'BUY') echo 'Yükseliş Sinyali';
                                            elseif ($macd_signal == 'SELL') echo 'Düşüş Sinyali';
                                            else echo 'Sinyal Yok';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TradingView Grafik -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-area"></i> Fiyat Grafiği & İndikatörler
                        </h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-light active time-frame" data-interval="60">1s</button>
                            <button type="button" class="btn btn-sm btn-light time-frame" data-interval="D">G</button>
                            <button type="button" class="btn btn-sm btn-light time-frame" data-interval="W">H</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="chart-container">
                            <div id="chart-loading" class="chart-loading">
                                <div class="text-center">
                                    <div class="spinner-border text-primary mb-2" role="status">
                                        <span class="sr-only">Yükleniyor...</span>
                                    </div>
                                    <p class="mb-0">Grafik yükleniyor...</p>
                                </div>
                            </div>
                            <div id="tradingview_chart"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Diğer İndikatör Detayları -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-signal"></i> Teknik İndikatör Detayları
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Bollinger Bands -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Bollinger Bantları</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $bollinger = isset($selected_coin['indicators']['bollinger']) ? $selected_coin['indicators']['bollinger'] : null;
                                        if ($bollinger): 
                                        
                                        // Bollinger pozisyonunu belirle
                                        $position = '';
                                        $percentage = 0;
                                        $positionClass = '';
                                        $positionHint = '';
                                        
                                        if ($selected_coin['price'] <= $bollinger['lower']) {
                                            $position = 'Alt Bant';
                                            $positionClass = 'text-success';
                                            $positionHint = 'Aşırı satım bölgesi - potansiyel alım fırsatı';
                                            
                                            // Alt banda göre pozisyonu yüzde olarak hesapla
                                            $range = $bollinger['middle'] - $bollinger['lower'];
                                            $diff = $bollinger['middle'] - $selected_coin['price'];
                                            $percentage = min(100, max(0, ($diff / $range) * 100));
                                        } elseif ($selected_coin['price'] >= $bollinger['upper']) {
                                            $position = 'Üst Bant';
                                            $positionClass = 'text-danger';
                                            $positionHint = 'Aşırı alım bölgesi - potansiyel satım fırsatı';
                                            
                                            // Üst banda göre pozisyonu yüzde olarak hesapla
                                            $range = $bollinger['upper'] - $bollinger['middle'];
                                            $diff = $selected_coin['price'] - $bollinger['middle'];
                                            $percentage = min(100, max(0, ($diff / $range) * 100));
                                        } else {
                                            $position = 'Orta Bant';
                                            $positionClass = 'text-secondary';
                                            $positionHint = 'Normal fiyat hareketi bölgesi';
                                            
                                            // Orta banda göre pozisyonu yüzde olarak hesapla
                                            if ($selected_coin['price'] > $bollinger['middle']) {
                                                $range = $bollinger['upper'] - $bollinger['middle'];
                                                $diff = $selected_coin['price'] - $bollinger['middle'];
                                                $percentage = min(100, max(0, ($diff / $range) * 50));
                                            } else {
                                                $range = $bollinger['middle'] - $bollinger['lower'];
                                                $diff = $bollinger['middle'] - $selected_coin['price'];
                                                $percentage = min(100, max(0, ($diff / $range) * 50));
                                            }
                                        }
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Alt Bant (Alım)</span>
                                            <strong class="text-success"><?php echo number_format($bollinger['lower'], 2); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Orta Bant (20 EMA)</span>
                                            <strong><?php echo number_format($bollinger['middle'], 2); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <span>Üst Bant (Satım)</span>
                                            <strong class="text-danger"><?php echo number_format($bollinger['upper'], 2); ?></strong>
                                        </div>
                                        
                                        <p class="mb-2">Fiyat Pozisyonu: <strong class="<?php echo $positionClass; ?>"><?php echo $position; ?></strong></p>
                                        <p class="small text-muted mb-3"><?php echo $positionHint; ?></p>
                                        
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-success" style="width: 33.3%">Alt</div>
                                            <div class="progress-bar bg-secondary" style="width: 33.3%">Orta</div>
                                            <div class="progress-bar bg-danger" style="width: 33.3%">Üst</div>
                                        </div>
                                        <div class="position-relative mt-1" style="height: 20px;">
                                            <div style="position: absolute; left: <?php echo $percentage; ?>%; transform: translateX(-50%);">
                                                <i class="fas fa-caret-up fa-2x <?php echo $positionClass; ?>"></i>
                                            </div>
                                        </div>
                                        
                                        <?php else: ?>
                                        <p class="text-muted">Bollinger Bandı verileri bulunamadı.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hareketli Ortalamalar -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Hareketli Ortalamalar</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $ma = isset($selected_coin['indicators']['moving_averages']) ? $selected_coin['indicators']['moving_averages'] : null;
                                        if ($ma): 
                                        
                                        // Trend durumu
                                        $trend = '';
                                        $trendDesc = '';
                                        $trendClass = '';
                                        
                                        if (isset($ma['ma20']) && isset($ma['ma50'])) {
                                            if ($ma['ma20'] > $ma['ma50']) {
                                                $trend = 'Yükseliş Trendi (Bullish)';
                                                $trendClass = 'text-success';
                                                $trendDesc = 'Kısa vadeli MA uzun vadeli MA\'nın üzerinde (Altın Çapraz)';
                                            } elseif ($ma['ma20'] < $ma['ma50']) {
                                                $trend = 'Düşüş Trendi (Bearish)';
                                                $trendClass = 'text-danger';
                                                $trendDesc = 'Kısa vadeli MA uzun vadeli MA\'nın altında (Ölüm Çaprazı)';
                                            } else {
                                                $trend = 'Nötr Trend';
                                                $trendClass = 'text-secondary';
                                                $trendDesc = 'Kısa ve uzun vadeli MA\'lar birbirine çok yakın';
                                            }
                                        }
                                        ?>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Periyot</th>
                                                        <th>Değer</th>
                                                        <th>Sinyal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (isset($ma['ma20'])): ?>
                                                    <tr>
                                                        <td>MA 20</td>
                                                        <td><?php echo number_format($ma['ma20'], 2); ?></td>
                                                        <td>
                                                            <span class="<?php echo $selected_coin['price'] > $ma['ma20'] ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo $selected_coin['price'] > $ma['ma20'] ? 'Fiyat Üstte' : 'Fiyat Altta'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($ma['ma50'])): ?>
                                                    <tr>
                                                        <td>MA 50</td>
                                                        <td><?php echo number_format($ma['ma50'], 2); ?></td>
                                                        <td>
                                                            <span class="<?php echo $selected_coin['price'] > $ma['ma50'] ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo $selected_coin['price'] > $ma['ma50'] ? 'Fiyat Üstte' : 'Fiyat Altta'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($ma['ma100'])): ?>
                                                    <tr>
                                                        <td>MA 100</td>
                                                        <td><?php echo number_format($ma['ma100'], 2); ?></td>
                                                        <td>
                                                            <span class="<?php echo $selected_coin['price'] > $ma['ma100'] ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo $selected_coin['price'] > $ma['ma100'] ? 'Fiyat Üstte' : 'Fiyat Altta'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($ma['ma200'])): ?>
                                                    <tr>
                                                        <td>MA 200</td>
                                                        <td><?php echo number_format($ma['ma200'], 2); ?></td>
                                                        <td>
                                                            <span class="<?php echo $selected_coin['price'] > $ma['ma200'] ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo $selected_coin['price'] > $ma['ma200'] ? 'Fiyat Üstte' : 'Fiyat Altta'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="alert alert-light mt-3">
                                            <h5 class="<?php echo $trendClass; ?>"><?php echo $trend; ?></h5>
                                            <p class="mb-0 small"><?php echo $trendDesc; ?></p>
                                        </div>
                                        
                                        <?php else: ?>
                                        <p class="text-muted">Hareketli ortalama verileri bulunamadı.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ek İndikatörler -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i> Gelişmiş İndikatörler
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- ADX -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">ADX (Ortalama Yön İndeksi)</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php
                                        if (isset($selected_coin['indicators']['adx'])) {
                                            $adx_value = isset($selected_coin['indicators']['adx']['value']) ? $selected_coin['indicators']['adx']['value'] : '--';
                                            
                                            if ($adx_value != '--') {
                                                $adx_class = '';
                                                $adx_message = '';
                                                
                                                if ($adx_value < 20) {
                                                    $adx_class = 'text-secondary';
                                                    $adx_message = 'Zayıf Trend';
                                                } elseif ($adx_value < 40) {
                                                    $adx_class = 'text-primary';
                                                    $adx_message = 'Orta Trend';
                                                } else {
                                                    $adx_class = 'text-success';
                                                    $adx_message = 'Güçlü Trend';
                                                }
                                                
                                                // ADX değerini ve mesajını göster
                                                echo '<h4 class="' . $adx_class . '">' . number_format($adx_value, 2) . '</h4>';
                                                echo '<p class="mb-0 small">' . $adx_message . '</p>';
                                                
                                                // +DI ve -DI değerlerini göster
                                                if (isset($selected_coin['indicators']['adx']['pdi']) && isset($selected_coin['indicators']['adx']['mdi'])) {
                                                    $pdi = $selected_coin['indicators']['adx']['pdi'];
                                                    $mdi = $selected_coin['indicators']['adx']['mdi'];
                                                    $total = $pdi + $mdi;
                                                    $pdi_percent = $total > 0 ? ($pdi / $total) * 100 : 50;
                                                    
                                                    echo '<div class="d-flex justify-content-between mt-3">';
                                                    echo '<span>+DI: <strong class="text-success">' . number_format($pdi, 2) . '</strong></span>';
                                                    echo '<span>-DI: <strong class="text-danger">' . number_format($mdi, 2) . '</strong></span>';
                                                    echo '</div>';
                                                    
                                                    echo '<div class="progress mt-2" style="height: 8px;">';
                                                    echo '<div class="progress-bar bg-success" style="width: ' . $pdi_percent . '%" role="progressbar"></div>';
                                                    echo '<div class="progress-bar bg-danger" style="width: ' . (100 - $pdi_percent) . '%" role="progressbar"></div>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="text-muted">Veri yok</div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Parabolic SAR -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Parabolic SAR</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php
                                        if (isset($selected_coin['indicators']['parabolic_sar'])) {
                                            $sar_value = isset($selected_coin['indicators']['parabolic_sar']['value']) ? $selected_coin['indicators']['parabolic_sar']['value'] : '--';
                                            $price = $selected_coin['price'];
                                            
                                            if ($sar_value != '--' && $price) {
                                                $sar_trend = '';
                                                $sar_class = '';
                                                
                                                if ($price > $sar_value) {
                                                    $sar_trend = 'Yükseliş Trendi';
                                                    $sar_class = 'text-success';
                                                } else {
                                                    $sar_trend = 'Düşüş Trendi';
                                                    $sar_class = 'text-danger';
                                                }
                                                
                                                echo '<h4 class="' . $sar_class . '">' . number_format($sar_value, 6) . '</h4>';
                                                echo '<p class="mb-0">' . $sar_trend . '</p>';
                                                echo '<div class="d-flex justify-content-between mt-3">';
                                                echo '<span>Fiyat: <strong>' . number_format($price, 6) . '</strong></span>';
                                                echo '<span>SAR: <strong>' . number_format($sar_value, 6) . '</strong></span>';
                                                echo '</div>';
                                            } else {
                                                echo '<div class="text-muted">Veri yok</div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Stochastic -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Stochastic Oscillator</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php
                                        if (isset($selected_coin['indicators']['stochastic'])) {
                                            $k_value = isset($selected_coin['indicators']['stochastic']['k']) ? $selected_coin['indicators']['stochastic']['k'] : '--';
                                            $d_value = isset($selected_coin['indicators']['stochastic']['d']) ? $selected_coin['indicators']['stochastic']['d'] : '--';
                                            
                                            if ($k_value != '--' && $d_value != '--') {
                                                $stoch_class = '';
                                                $stoch_message = '';
                                                
                                                if ($k_value <= 20 && $d_value <= 20) {
                                                    $stoch_class = 'text-success';
                                                    $stoch_message = 'Aşırı Satım Bölgesi';
                                                } elseif ($k_value >= 80 && $d_value >= 80) {
                                                    $stoch_class = 'text-danger';
                                                    $stoch_message = 'Aşırı Alım Bölgesi';
                                                } else {
                                                    $stoch_class = 'text-secondary';
                                                    $stoch_message = 'Nötr Bölge';
                                                }
                                                
                                                // Kesişme durumu
                                                $cross_message = '';
                                                $cross_class = '';
                                                if ($k_value > $d_value) {
                                                    $cross_message = 'K > D (Bullish)';
                                                    $cross_class = 'text-success';
                                                } else {
                                                    $cross_message = 'K < D (Bearish)';
                                                    $cross_class = 'text-danger';
                                                }
                                                
                                                echo '<div class="d-flex justify-content-around">';
                                                echo '<div>';
                                                echo '<h5>%K</h5>';
                                                echo '<h4 class="' . $stoch_class . '">' . number_format($k_value, 2) . '</h4>';
                                                echo '</div>';
                                                echo '<div>';
                                                echo '<h5>%D</h5>';
                                                echo '<h4 class="' . $stoch_class . '">' . number_format($d_value, 2) . '</h4>';
                                                echo '</div>';
                                                echo '</div>';
                                                
                                                echo '<p class="mb-0 mt-2 ' . $stoch_class . '">' . $stoch_message . '</p>';
                                                echo '<p class="mt-2 small ' . $cross_class . '">' . $cross_message . '</p>';
                                            } else {
                                                echo '<div class="text-muted">Veri yok</div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ichimoku Cloud -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Ichimoku Cloud</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php
                                        if (isset($selected_coin['indicators']['ichimoku'])) {
                                            $tenkan = isset($selected_coin['indicators']['ichimoku']['tenkan']) ? $selected_coin['indicators']['ichimoku']['tenkan'] : '--';
                                            $kijun = isset($selected_coin['indicators']['ichimoku']['kijun']) ? $selected_coin['indicators']['ichimoku']['kijun'] : '--';
                                            $senkou_a = isset($selected_coin['indicators']['ichimoku']['senkou_a']) ? $selected_coin['indicators']['ichimoku']['senkou_a'] : '--';
                                            $senkou_b = isset($selected_coin['indicators']['ichimoku']['senkou_b']) ? $selected_coin['indicators']['ichimoku']['senkou_b'] : '--';
                                            $chikou = isset($selected_coin['indicators']['ichimoku']['chikou']) ? $selected_coin['indicators']['ichimoku']['chikou'] : '--';
                                            $price = $selected_coin['price'];
                                            
                                            if ($tenkan != '--' && $kijun != '--' && $senkou_a != '--' && $senkou_b != '--') {
                                                $cloud_color = $senkou_a > $senkou_b ? 'bg-success-light' : 'bg-danger-light';
                                                $position = '';
                                                $position_class = '';
                                                
                                                // Bulut durumu
                                                if ($price > max($senkou_a, $senkou_b)) {
                                                    $position = 'Bulutun Üstünde (Bullish)';
                                                    $position_class = 'text-success';
                                                } elseif ($price < min($senkou_a, $senkou_b)) {
                                                    $position = 'Bulutun Altında (Bearish)';
                                                    $position_class = 'text-danger';
                                                } else {
                                                    $position = 'Bulut İçinde (Nötr)';
                                                    $position_class = 'text-secondary';
                                                }
                                                
                                                // Tenkan/Kijun kesişimi
                                                $cross = '';
                                                $cross_class = '';
                                                if ($tenkan > $kijun) {
                                                    $cross = 'Tenkan-sen > Kijun-sen (Bullish)';
                                                    $cross_class = 'text-success';
                                                } elseif ($tenkan < $kijun) {
                                                    $cross = 'Tenkan-sen < Kijun-sen (Bearish)';
                                                    $cross_class = 'text-danger';
                                                }
                                                
                                                echo '<h5 class="' . $position_class . '">' . $position . '</h5>';
                                                
                                                echo '<div class="table-responsive mt-3">';
                                                echo '<table class="table table-sm table-borderless">';
                                                echo '<tr>';
                                                echo '<td>Tenkan-sen (Dönüş):</td>';
                                                echo '<td><strong>' . number_format($tenkan, 4) . '</strong></td>';
                                                echo '</tr>';
                                                echo '<tr>';
                                                echo '<td>Kijun-sen (Taban):</td>';
                                                echo '<td><strong>' . number_format($kijun, 4) . '</strong></td>';
                                                echo '</tr>';
                                                echo '<tr>';
                                                echo '<td>Senkou Span A:</td>';
                                                echo '<td><strong>' . number_format($senkou_a, 4) . '</strong></td>';
                                                echo '</tr>';
                                                echo '<tr>';
                                                echo '<td>Senkou Span B:</td>';
                                                echo '<td><strong>' . number_format($senkou_b, 4) . '</strong></td>';
                                                echo '</tr>';
                                                echo '</table>';
                                                echo '</div>';
                                                
                                                echo '<p class="mt-2 small ' . $cross_class . '">' . $cross . '</p>';
                                            } else {
                                                echo '<div class="text-muted">Veri yok</div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SuperTrend -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">SuperTrend</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php
                                        if (isset($selected_coin['indicators']['supertrend'])) {
                                            $supertrend_value = isset($selected_coin['indicators']['supertrend']['value']) ? $selected_coin['indicators']['supertrend']['value'] : '--';
                                            $supertrend_signal = isset($selected_coin['indicators']['supertrend']['signal']) ? $selected_coin['indicators']['supertrend']['signal'] : 'NEUTRAL';
                                            $price = $selected_coin['price'];
                                            
                                            if ($supertrend_value != '--' && $price) {
                                                $st_class = '';
                                                $st_icon = '';
                                                $st_message = '';
                                                
                                                if ($supertrend_signal == 'BUY') {
                                                    $st_class = 'text-success';
                                                    $st_icon = 'fa-arrow-up';
                                                    $st_message = 'Yükseliş Trendi';
                                                } elseif ($supertrend_signal == 'SELL') {
                                                    $st_class = 'text-danger';
                                                    $st_icon = 'fa-arrow-down';
                                                    $st_message = 'Düşüş Trendi';
                                                } else {
                                                    $st_class = 'text-secondary';
                                                    $st_icon = 'fa-minus';
                                                    $st_message = 'Nötr';
                                                }
                                                
                                                echo '<i class="fas ' . $st_icon . ' fa-2x mb-3 ' . $st_class . '"></i>';
                                                echo '<h4 class="' . $st_class . '">' . $st_message . '</h4>';
                                                echo '<h5 class="mt-3">' . number_format($supertrend_value, 6) . '</h5>';
                                                
                                                echo '<div class="d-flex justify-content-between mt-3">';
                                                echo '<span>Fiyat:</span>';
                                                echo '<strong>' . number_format($price, 6) . '</strong>';
                                                echo '</div>';
                                                echo '<div class="d-flex justify-content-between">';
                                                echo '<span>SuperTrend:</span>';
                                                echo '<strong class="' . $st_class . '">' . number_format($supertrend_value, 6) . '</strong>';
                                                echo '</div>';
                                            } else {
                                                echo '<div class="text-muted">Veri yok</div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VWAP -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">VWAP (Hacim Ağırlıklı Ortalama Fiyat)</h6>
                                    </div>
                                    <div class="card-body text-center">
                                    <?php
                                    // VWAP verisi var mı kontrol et
                                    if (isset($selected_coin['indicators']['vwap'])) {
                                        $vwap_value = $selected_coin['indicators']['vwap']['value'] ?? '--';
                                        $price = $selected_coin['price'];
                                        
                                        // Eğer VWAP değeri mevcutsa
                                        if ($vwap_value != '--' && $price) {
                                            $vwap_class = '';
                                            $vwap_message = '';
                                            
                                            // Fiyat VWAP'dan yüksekse (bullish)
                                            if ($price > $vwap_value) {
                                                $vwap_class = 'text-success';
                                                $vwap_message = 'Fiyat VWAP\'ın üzerinde (Bullish)';
                                            } 
                                            // Fiyat VWAP'dan düşükse (bearish)
                                            else {
                                                $vwap_class = 'text-danger';
                                                $vwap_message = 'Fiyat VWAP\'ın altında (Bearish)';
                                            }
                                            
                                            // Fiyat ve VWAP arasındaki farkın yüzdesi
                                            $percent_diff = (($price - $vwap_value) / $vwap_value) * 100;
                                            
                                            // Değerleri göster
                                            echo '<h4 class="' . $vwap_class . '">' . number_format($vwap_value, 6) . '</h4>';
                                            echo '<p class="mb-0 ' . $vwap_class . '">' . $vwap_message . '</p>';
                                            echo '<div class="mt-3">';
                                            echo '<p class="mb-1">Fiyat: <strong>' . number_format($price, 6) . '</strong></p>';
                                            echo '<p>Fark: <strong class="' . $vwap_class . '">' . number_format($percent_diff, 2) . '%</strong></p>';
                                            echo '</div>';
                                        } 
                                        // VWAP değeri yoksa
                                        else {
                                            echo '<div class="text-muted">Veri yok</div>';
                                        }
                                    } 
                                    // VWAP indikatörü yoksa
                                    else {
                                        echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                    }
                                    ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pivot Points -->
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 indicator-card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Pivot Noktaları</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php
                                        if (isset($selected_coin['indicators']['pivot_points'])) {
                                            $pp = $selected_coin['indicators']['pivot_points'];
                                            $price = $selected_coin['price'];
                                            
                                            if (isset($pp['pivot']) && $price) {
                                                // Hangi pivot seviyelerinin arasında olduğunu belirle
                                                $position = '';
                                                $levels = [];
                                                
                                                if (isset($pp['r3'])) $levels['R3'] = $pp['r3'];
                                                if (isset($pp['r2'])) $levels['R2'] = $pp['r2'];
                                                if (isset($pp['r1'])) $levels['R1'] = $pp['r1'];
                                                if (isset($pp['pivot'])) $levels['PP'] = $pp['pivot'];
                                                if (isset($pp['s1'])) $levels['S1'] = $pp['s1'];
                                                if (isset($pp['s2'])) $levels['S2'] = $pp['s2'];
                                                if (isset($pp['s3'])) $levels['S3'] = $pp['s3'];
                                                
                                                // Fiyatı yüksekten düşüğe sıralı pivot seviyeleri arasında konumlandır
                                                arsort($levels);
                                                $prev_level_name = null;
                                                $prev_level_value = null;
                                                
                                                foreach ($levels as $level_name => $level_value) {
                                                    if ($price >= $level_value) {
                                                        if ($prev_level_name) {
                                                            $position = $prev_level_name . ' ve ' . $level_name . ' arasında';
                                                            break;
                                                        } else {
                                                            $position = $level_name . ' üzerinde';
                                                            break;
                                                        }
                                                    }
                                                    $prev_level_name = $level_name;
                                                    $prev_level_value = $level_value;
                                                }
                                                
                                                // Eğer hala pozisyon belirlenemediyse, fiyat en düşük seviyenin altında
                                                if (empty($position) && $prev_level_name) {
                                                    $position = $prev_level_name . ' altında';
                                                }
                                                
                                                echo '<h5>Güncel Pozisyon</h5>';
                                                echo '<p class="text-info">' . $position . '</p>';
                                                
                                                echo '<div class="table-responsive mt-3">';
                                                echo '<table class="table table-sm">';
                                                echo '<thead>';
                                                echo '<tr>';
                                                echo '<th>Seviye</th>';
                                                echo '<th>Değer</th>';
                                                echo '</tr>';
                                                echo '</thead>';
                                                echo '<tbody>';
                                                
                                                if (isset($pp['r3'])) {
                                                    echo '<tr>';
                                                    echo '<td>R3 (Dirençler)</td>';
                                                    echo '<td><strong class="text-danger">' . number_format($pp['r3'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                if (isset($pp['r2'])) {
                                                    echo '<tr>';
                                                    echo '<td>R2</td>';
                                                    echo '<td><strong class="text-danger">' . number_format($pp['r2'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                if (isset($pp['r1'])) {
                                                    echo '<tr>';
                                                    echo '<td>R1</td>';
                                                    echo '<td><strong class="text-danger">' . number_format($pp['r1'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                if (isset($pp['pivot'])) {
                                                    echo '<tr>';
                                                    echo '<td>PP (Pivot)</td>';
                                                    echo '<td><strong class="text-primary">' . number_format($pp['pivot'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                if (isset($pp['s1'])) {
                                                    echo '<tr>';
                                                    echo '<td>S1 (Destekler)</td>';
                                                    echo '<td><strong class="text-success">' . number_format($pp['s1'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                if (isset($pp['s2'])) {
                                                    echo '<tr>';
                                                    echo '<td>S2</td>';
                                                    echo '<td><strong class="text-success">' . number_format($pp['s2'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                if (isset($pp['s3'])) {
                                                    echo '<tr>';
                                                    echo '<td>S3</td>';
                                                    echo '<td><strong class="text-success">' . number_format($pp['s3'], 4) . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                
                                                echo '</tbody>';
                                                echo '</table>';
                                                echo '</div>';
                                            } else {
                                                echo '<div class="text-muted">Veri yok</div>';
                                            }
                                        } else {
                                            echo '<div class="text-muted">Bu indikatör verisi mevcut değil</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detaylı İndikatör Sinyalleri -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie"></i> İndikatörlerin Verdiği Sinyaller
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>İndikatör</th>
                                        <th>Değer</th>
                                        <th>Sinyal</th>
                                        <th>Açıklama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- RSI -->
                                    <?php if (isset($selected_coin['indicators']['rsi'])): 
                                        $rsi = $selected_coin['indicators']['rsi']['value'];
                                        $signal = "";
                                        $signalClass = "";
                                        $explanation = "";
                                        
                                        if ($rsi <= 30) {
                                            $signal = "AL";
                                            $signalClass = "badge-success";
                                            $explanation = "30 ve altındaki değerler aşırı satım bölgesini gösterir, genellikle alım fırsatıdır";
                                        } elseif ($rsi >= 70) {
                                            $signal = "SAT";
                                            $signalClass = "badge-danger";
                                            $explanation = "70 ve üstündeki değerler aşırı alım bölgesini gösterir, genellikle satım fırsatıdır";
                                        } else {
                                            $signal = "NÖTR";
                                            $signalClass = "badge-secondary";
                                            $explanation = "30-70 arasındaki değerler normal işlem aralığını gösterir";
                                        }
                                    ?>
                                    <tr>
                                        <td><strong>RSI (Göreceli Güç İndeksi)</strong></td>
                                        <td><?php echo number_format($rsi, 2); ?></td>
                                        <td><span class="badge <?php echo $signalClass; ?>"><?php echo $signal; ?></span></td>
                                        <td><?php echo $explanation; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <!-- MACD -->
                                    <?php if (isset($selected_coin['indicators']['macd'])): 
                                        $macd = $selected_coin['indicators']['macd'];
                                        $macdValue = $macd['value'] ?? 0;
                                        $macdSignal = $macd['signal'] ?? 'NEUTRAL';
                                        
                                        if ($macdSignal == 'BUY') {
                                            $signalBadge = "badge-success";
                                            $signalText = "AL";
                                            $explanation = "MACD çizgisi sinyal çizgisini yukarı doğru kesti (pozitif geçiş)";
                                        } elseif ($macdSignal == 'SELL') {
                                            $signalBadge = "badge-danger";
                                            $signalText = "SAT";
                                            $explanation = "MACD çizgisi sinyal çizgisini aşağı doğru kesti (negatif geçiş)";
                                        } else {
                                            $signalBadge = "badge-secondary";
                                            $signalText = "NÖTR";
                                            $explanation = "MACD çizgisi ve sinyal çizgisi arasında kesişim yok";
                                        }
                                    ?>
                                    <tr>
                                        <td><strong>MACD (Hareketli Ortalama Yakınsama/Uzaklaşma)</strong></td>
                                        <td><?php echo number_format($macdValue, 6); ?></td>
                                        <td><span class="badge <?php echo $signalBadge; ?>"><?php echo $signalText; ?></span></td>
                                        <td><?php echo $explanation; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <!-- Bollinger Bands -->
                                    <?php if (isset($selected_coin['indicators']['bollinger']) && isset($selected_coin['price'])): 
                                        $bb = $selected_coin['indicators']['bollinger'];
                                        $price = $selected_coin['price'];
                                        
                                        if ($price <= $bb['lower']) {
                                            $signalBadge = "badge-success";
                                            $signalText = "AL";
                                            $explanation = "Fiyat alt banda değiyor veya altında, bu genellikle aşırı satım (alım fırsatı) gösterir";
                                        } elseif ($price >= $bb['upper']) {
                                            $signalBadge = "badge-danger";
                                            $signalText = "SAT";
                                            $explanation = "Fiyat üst banda değiyor veya üstünde, bu genellikle aşırı alım (satım fırsatı) gösterir";
                                        } else {
                                            $signalBadge = "badge-secondary";
                                            $signalText = "NÖTR";
                                            $explanation = "Fiyat bantlar arasında normal hareket ediyor";
                                        }
                                        
                                        $position = $price <= $bb['lower'] ? "Alt Bant (Alım)" : 
                                                  ($price >= $bb['upper'] ? "Üst Bant (Satım)" : "Orta Bant");
                                    ?>
                                    <tr>
                                        <td><strong>Bollinger Bantları</strong></td>
                                        <td><?php echo $position; ?></td>
                                        <td><span class="badge <?php echo $signalBadge; ?>"><?php echo $signalText; ?></span></td>
                                        <td><?php echo $explanation; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <!-- Moving Averages -->
                                    <?php if (isset($selected_coin['indicators']['moving_averages'])): 
                                        $ma = $selected_coin['indicators']['moving_averages'];
                                        
                                        if (isset($ma['ma20']) && isset($ma['ma50'])) {
                                            if ($ma['ma20'] > $ma['ma50']) {
                                                $signalBadge = "badge-success";
                                                $signalText = "AL";
                                                $explanation = "Kısa vadeli MA (20), uzun vadeli MA'nın (50) üstünde (Altın Çapraz)";
                                            } elseif ($ma['ma20'] < $ma['ma50']) {
                                                $signalBadge = "badge-danger";
                                                $signalText = "SAT";
                                                $explanation = "Kısa vadeli MA (20), uzun vadeli MA'nın (50) altında (Ölüm Çaprazı)";
                                            } else {
                                                $signalBadge = "badge-secondary";
                                                $signalText = "NÖTR";
                                                $explanation = "MA değerleri birbirine çok yakın, net bir trend yok";
                                            }
                                            
                                            $trend = $ma['ma20'] > $ma['ma50'] ? "Yükseliş Trendi (Bullish)" : 
                                                    ($ma['ma20'] < $ma['ma50'] ? "Düşüş Trendi (Bearish)" : "Nötr");
                                        }
                                    ?>
                                    <tr>
                                        <td><strong>Hareketli Ortalamalar</strong></td>
                                        <td><?php echo $trend ?? '--'; ?></td>
                                        <td><span class="badge <?php echo $signalBadge ?? 'badge-secondary'; ?>"><?php echo $signalText ?? 'NÖTR'; ?></span></td>
                                        <td><?php echo $explanation ?? 'Hareketli ortalama verisi eksik'; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <!-- Price vs MA -->
                                    <?php if (isset($selected_coin['indicators']['moving_averages']) && isset($selected_coin['price'])): 
                                        $ma = $selected_coin['indicators']['moving_averages'];
                                        $price = $selected_coin['price'];
                                        
                                        $priceVsMa20Signal = "";
                                        $priceVsMa20Class = "";
                                        $priceVsMa20Explanation = "";
                                        
                                        if (isset($ma['ma20'])) {
                                            if ($price > $ma['ma20']) {
                                                $priceVsMa20Signal = "AL";
                                                $priceVsMa20Class = "badge-success";
                                                $priceVsMa20Explanation = "Fiyat 20-günlük MA'nın üzerinde, kısa vadeli yükseliş eğilimi";
                                            } else {
                                                $priceVsMa20Signal = "SAT";
                                                $priceVsMa20Class = "badge-danger";
                                                $priceVsMa20Explanation = "Fiyat 20-günlük MA'nın altında, kısa vadeli düşüş eğilimi";
                                            }
                                        }
                                        
                                        $priceVsMa50Signal = "";
                                        $priceVsMa50Class = "";
                                        $priceVsMa50Explanation = "";
                                        
                                        if (isset($ma['ma50'])) {
                                            if ($price > $ma['ma50']) {
                                                $priceVsMa50Signal = "AL";
                                                $priceVsMa50Class = "badge-success";
                                                $priceVsMa50Explanation = "Fiyat 50-günlük MA'nın üzerinde, orta vadeli yükseliş eğilimi";
                                            } else {
                                                $priceVsMa50Signal = "SAT";
                                                $priceVsMa50Class = "badge-danger";
                                                $priceVsMa50Explanation = "Fiyat 50-günlük MA'nın altında, orta vadeli düşüş eğilimi";
                                            }
                                        }
                                    ?>
                                    <?php if (isset($ma['ma20'])): ?>
                                    <tr>
                                        <td><strong>Fiyat vs MA20</strong></td>
                                        <td><?php echo "Fiyat " . ($price > $ma['ma20'] ? ">" : "<") . " MA20"; ?></td>
                                        <td><span class="badge <?php echo $priceVsMa20Class; ?>"><?php echo $priceVsMa20Signal; ?></span></td>
                                        <td><?php echo $priceVsMa20Explanation; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($ma['ma50'])): ?>
                                    <tr>
                                        <td><strong>Fiyat vs MA50</strong></td>
                                        <td><?php echo "Fiyat " . ($price > $ma['ma50'] ? ">" : "<") . " MA50"; ?></td>
                                        <td><span class="badge <?php echo $priceVsMa50Class; ?>"><?php echo $priceVsMa50Signal; ?></span></td>
                                        <td><?php echo $priceVsMa50Explanation; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Özet Sinyal -->
                                    <tr class="table-active">
                                        <td><strong>TOPLAM GÖRÜNÜM</strong></td>
                                        <td colspan="3">
                                            <?php
                                            $totalBuySignals = 0;
                                            $totalSellSignals = 0;
                                            $totalSignals = 0;
                                            $signalSources = [];
                                            
                                            // RSI sinyali
                                            if (isset($selected_coin['indicators']['rsi'])) {
                                                $totalSignals++;
                                                $rsi = $selected_coin['indicators']['rsi']['value'];
                                                if ($rsi <= 30) {
                                                    $totalBuySignals++;
                                                    $signalSources['buy'][] = "RSI";
                                                } elseif ($rsi >= 70) {
                                                    $totalSellSignals++;
                                                    $signalSources['sell'][] = "RSI";
                                                }
                                            }
                                            
                                            // MACD sinyali
                                            if (isset($selected_coin['indicators']['macd']) && isset($selected_coin['indicators']['macd']['signal'])) {
                                                $totalSignals++;
                                                $macdSignal = $selected_coin['indicators']['macd']['signal'];
                                                if ($macdSignal == 'BUY') {
                                                    $totalBuySignals++;
                                                    $signalSources['buy'][] = "MACD";
                                                } elseif ($macdSignal == 'SELL') {
                                                    $totalSellSignals++;
                                                    $signalSources['sell'][] = "MACD";
                                                }
                                            }
                                            
                                            // Bollinger sinyali
                                            if (isset($selected_coin['indicators']['bollinger']) && isset($selected_coin['price'])) {
                                                $totalSignals++;
                                                $bb = $selected_coin['indicators']['bollinger'];
                                                $price = $selected_coin['price'];
                                                if ($price <= $bb['lower']) {
                                                    $totalBuySignals++;
                                                    $signalSources['buy'][] = "Bollinger";
                                                } elseif ($price >= $bb['upper']) {
                                                    $totalSellSignals++;
                                                    $signalSources['sell'][] = "Bollinger";
                                                }
                                            }
                                            
                                            // MA sinyali
                                            if (isset($selected_coin['indicators']['moving_averages'])) {
                                                $totalSignals++;
                                                $ma = $selected_coin['indicators']['moving_averages'];
                                                if (isset($ma['ma20']) && isset($ma['ma50'])) {
                                                    if ($ma['ma20'] > $ma['ma50']) {
                                                        $totalBuySignals++;
                                                        $signalSources['buy'][] = "Moving Avg";
                                                    } elseif ($ma['ma20'] < $ma['ma50']) {
                                                        $totalSellSignals++;
                                                        $signalSources['sell'][] = "Moving Avg";
                                                    }
                                                }
                                            }
                                            
                                            // Price vs MA20
                                            if (isset($selected_coin['indicators']['moving_averages']) && isset($selected_coin['price']) && isset($selected_coin['indicators']['moving_averages']['ma20'])) {
                                                $totalSignals++;
                                                if ($selected_coin['price'] > $selected_coin['indicators']['moving_averages']['ma20']) {
                                                    $totalBuySignals++;
                                                    $signalSources['buy'][] = "Price>MA20";
                                                } else {
                                                    $totalSellSignals++;
                                                    $signalSources['sell'][] = "Price<MA20";
                                                }
                                            }
                                            
                                            // Sonuç değerlendirmesi
                                            if ($totalSignals > 0) {
                                                $buyPercentage = ($totalBuySignals / $totalSignals) * 100;
                                                $sellPercentage = ($totalSellSignals / $totalSignals) * 100;
                                                
                                                echo '<div class="progress" style="height: 20px;">';
                                                echo '<div class="progress-bar bg-success" role="progressbar" style="width: '.$buyPercentage.'%" 
                                                      aria-valuenow="'.$buyPercentage.'" aria-valuemin="0" aria-valuemax="100">'.$totalBuySignals.' Al</div>';
                                                echo '<div class="progress-bar bg-danger" role="progressbar" style="width: '.$sellPercentage.'%" 
                                                      aria-valuenow="'.$sellPercentage.'" aria-valuemin="0" aria-valuemax="100">'.$totalSellSignals.' Sat</div>';
                                                echo '</div>';
                                                
                                                echo '<div class="mt-2">';
                                                if (!empty($signalSources['buy'])) {
                                                    echo '<span class="text-success"><strong>Alım Sinyali Veren İndikatörler:</strong> ' . implode(", ", $signalSources['buy']) . '</span><br>';
                                                }
                                                if (!empty($signalSources['sell'])) {
                                                    echo '<span class="text-danger"><strong>Satım Sinyali Veren İndikatörler:</strong> ' . implode(", ", $signalSources['sell']) . '</span>';
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '<div class="alert alert-warning">İndikatör verileri yetersiz.</div>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TradingView Widget JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://s3.tradingview.com/tv.js"></script>
    <script>
    $(document).ready(function() {
        // TradingView grafik yükleme
        var widget = new TradingView.widget({
            "autosize": true,
            "symbol": "<?php echo $chart_symbol; ?>",
            "interval": "60", // Default 1 saat
            "timezone": "Europe/Istanbul",
            "theme": "light",
            "style": "1",
            "locale": "tr",
            "toolbar_bg": "#f1f3f6",
            "enable_publishing": false,
            "withdateranges": true,
            "hide_side_toolbar": false,
            "allow_symbol_change": false,
            "save_image": false,
            "container_id": "tradingview_chart",
            "studies": [
                // Default gösterilecek indikatörler
                {"id": "MAExp@tv-basicstudies", "inputs": {"length": 20}},
                {"id": "MAExp@tv-basicstudies", "inputs": {"length": 50}},
                {"id": "RSI@tv-basicstudies"},
                {"id": "MACD@tv-basicstudies"},
                {"id": "BB@tv-basicstudies", "inputs": {"length": 20, "stdDev": 2}}
            ]
        });
        
        // Grafik yüklendiğinde loading'i kaldır
        widget.onChartReady(function() {
            $("#chart-loading").fadeOut();
        });
        
        // Zaman aralığı değiştirme
        $(".time-frame").click(function(e) {
            e.preventDefault();
            
            $(".time-frame").removeClass("active");
            $(this).addClass("active");
            
            var interval = $(this).data("interval");
            widget.chart().setResolution(interval);
        });
        
        // Verileri yenileme
        $("#refresh-data").click(function() {
            location.reload();
        });
    });
    </script>
</body>
</html>