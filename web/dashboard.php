<?php
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

// Bot durumunu al
$bot_status = $bot_api->getStatus();

// Bugünkü istatistikleri al
$today_stats = $bot_api->getTodayStats();

// Aktif coinleri al
$active_coins = $bot_api->getActiveCoins();

// Sayfa başlığı
$page_title = 'Bot Kontrol Paneli';
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
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                <!-- Özet Bilgiler -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between">
                                <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Sistem Durumu</h5>
                                <span id="last-update" class="small"><?php echo date('H:i:s'); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Bot Durumu -->
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 status-card">
                                            <div class="card-body text-center">
                                                <div class="status-indicator <?php echo $bot_status['running'] ? 'status-running' : 'status-stopped'; ?>">
                                                    <i class="fas <?php echo $bot_status['running'] ? 'fa-play' : 'fa-stop'; ?>"></i>
                                                </div>
                                                <h5 class="mt-3">Bot Durumu</h5>
                                                <p class="mb-2 <?php echo $bot_status['running'] ? 'text-success' : 'text-danger'; ?>">
                                                    <strong><?php echo $bot_status['running'] ? 'Çalışıyor' : 'Durduruldu'; ?></strong>
                                                </p>
                                                <div class="btn-group" role="group">
                                                    <?php if ($bot_status['running']): ?>
                                                        <button class="btn btn-sm btn-danger toggle-bot" data-action="stop">
                                                            <i class="fas fa-stop-circle"></i> Durdur
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success toggle-bot" data-action="start">
                                                            <i class="fas fa-play-circle"></i> Başlat
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-info toggle-bot" data-action="restart">
                                                        <i class="fas fa-sync-alt"></i> Yeniden Başlat
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aktif Coinler -->
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 status-card">
                                            <div class="card-body text-center">
                                                <h3 class="text-info"><?php echo count($active_coins); ?></h3>
                                                <h5>Aktif Coinler</h5>
                                                <p class="mb-2 small text-muted">Takip edilen coin sayısı</p>
                                                <a href="coins.php" class="btn btn-sm btn-info">
                                                    <i class="fas fa-coins"></i> Detaylar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Günlük İşlemler -->
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 status-card">
                                            <div class="card-body text-center">
                                                <h3 class="text-success"><?php echo $today_stats['total_trades']; ?></h3>
                                                <h5>Günlük İşlemler</h5>
                                                <p class="mb-2 small text-muted">
                                                    <?php echo $today_stats['buy_trades']; ?> alım | <?php echo $today_stats['sell_trades']; ?> satım
                                                </p>
                                                <a href="trades.php" class="btn btn-sm btn-success">
                                                    <i class="fas fa-exchange-alt"></i> Detaylar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Günlük Kar/Zarar -->
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 status-card">
                                            <div class="card-body text-center">
                                                <?php $pnl = $today_stats['profit_loss']; ?>
                                                <h3 class="<?php echo $pnl >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $pnl >= 0 ? '+' : ''; ?><?php echo number_format($pnl, 2); ?> USDT
                                                </h3>
                                                <h5>Günlük Kar/Zarar</h5>
                                                <p class="mb-2 small text-muted">
                                                    <?php echo number_format($today_stats['profit_loss_percentage'], 2); ?>% değişim
                                                </p>
                                                <a href="reports.php" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-chart-bar"></i> Raporlar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Son İşlemler -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Son İşlemler</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Sembol</th>
                                                <th>İşlem</th>
                                                <th>Fiyat</th>
                                                <th>K/Z</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_trades = $bot_api->getRecentTrades(5);
                                            foreach ($recent_trades as $trade):
                                            ?>
                                            <tr>
                                                <td><?php echo date('H:i', strtotime($trade['timestamp'])); ?></td>
                                                <td><?php echo $trade['symbol']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $trade['type'] == 'BUY' ? 'badge-success' : 'badge-danger'; ?>">
                                                        <?php echo $trade['type'] == 'BUY' ? 'AL' : 'SAT'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($trade['price'], $trade['price'] < 10 ? 6 : 2); ?></td>
                                                <td class="<?php echo isset($trade['profit_loss']) && $trade['profit_loss'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php 
                                                    if (isset($trade['profit_loss'])) {
                                                        echo $trade['profit_loss'] >= 0 ? '+' : '';
                                                        echo number_format($trade['profit_loss'], 2);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="trades.php" class="btn btn-sm btn-outline-success">Tüm İşlemleri Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bot Logları -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between">
                                <h5 class="mb-0"><i class="fas fa-list"></i> Bot Logları</h5>
                                <button class="btn btn-sm btn-light refresh-logs">
                                    <i class="fas fa-sync-alt"></i> Yenile
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div id="log-container" class="p-2 bg-dark text-light" style="height: 300px; overflow-y: auto; font-family: monospace;">
                                    <?php
                                    // Bot log dosyasını oku ve göster
                                    $log_files = [
                                        __DIR__ . '/../bot.log',
                                        __DIR__ . '/../bot/bot.log'
                                    ];
                                    
                                    $logs = [];
                                    foreach ($log_files as $log_file) {
                                        if (file_exists($log_file) && is_readable($log_file)) {
                                            try {
                                                $file_logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                                if (is_array($file_logs)) {
                                                    $logs = array_merge($logs, $file_logs);
                                                }
                                            } catch (Exception $e) {
                                                // Logları okumak istisna fırlattıysa yoksay
                                            }
                                        }
                                    }
                                    
                                    // Logları ters sırala (en son loglar üstte)
                                    $logs = array_reverse($logs);
                                    
                                    // En son 100 log
                                    $logs = array_slice($logs, 0, 100);
                                    
                                    if (!empty($logs)) {
                                        foreach ($logs as $log) {
                                            $log_class = 'log-normal';
                                            
                                            // Log tipine göre renklendir
                                            if (stripos($log, 'ERROR') !== false) {
                                                $log_class = 'log-error';
                                            } elseif (stripos($log, 'WARNING') !== false) {
                                                $log_class = 'log-warning';
                                            } elseif (stripos($log, 'SUCCESS') !== false || stripos($log, 'PROFIT') !== false) {
                                                $log_class = 'log-success';
                                            }
                                            
                                            echo '<div class="log-entry ' . $log_class . '">' . htmlspecialchars($log) . '</div>';
                                        }
                                    } else {
                                        echo '<div class="text-muted p-3">Log dosyası bulunamadı veya boş.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aktif Stratejiler -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white d-flex justify-content-between">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Aktif Stratejiler</h5>
                                <a href="settings.php" class="btn btn-sm btn-light">
                                    <i class="fas fa-cog"></i> Tüm Ayarlar
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    // Bot ayarlarından stratejileri al
                                    $strategies = $bot_api->getActiveStrategies();
                                    foreach ($strategies as $strategy => $details):
                                    ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo $details['name']; ?></h6>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input strategy-toggle" 
                                                           id="strategy-<?php echo $strategy; ?>" 
                                                           data-strategy="<?php echo $strategy; ?>" 
                                                           <?php echo $details['enabled'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="strategy-<?php echo $strategy; ?>"></label>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text small"><?php echo $details['description']; ?></p>
                                            </div>
                                            <div class="card-footer text-center">
                                                <a href="strategy_settings.php?name=<?php echo $strategy; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-cog"></i> Strateji Ayarları
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- İndikatör Analizleri -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between">
                                <h5 class="mb-0"><i class="fas fa-signal"></i> Aktif Coin Analizleri</h5>
                                <button class="btn btn-sm btn-light refresh-analysis">
                                    <i class="fas fa-sync-alt"></i> Analizleri Güncelle
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Coin</th>
                                                <th>Fiyat</th>
                                                <th>RSI</th>
                                                <th>MACD</th>
                                                <th>Bollinger</th>
                                                <th>TradingView</th>
                                                <th>Sinyal</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Tüm aktif coinlerin analizlerini getir
                                            $coin_analyses = $bot_api->getCoinAnalyses();
                                            
                                            foreach ($active_coins as $coin):
                                                $symbol = $coin['symbol'];
                                                $analysis = isset($coin_analyses[$symbol]) ? $coin_analyses[$symbol] : null;
                                                
                                                if (!$analysis) continue;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $symbol; ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo number_format($analysis['price'], $analysis['price'] < 10 ? 6 : 2); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $rsi_value = isset($analysis['indicators']['rsi']['value']) ? $analysis['indicators']['rsi']['value'] : '-';
                                                    $rsi_signal = isset($analysis['indicators']['rsi']['signal']) ? $analysis['indicators']['rsi']['signal'] : '-';
                                                    $rsi_class = $rsi_signal == 'BUY' ? 'text-success' : ($rsi_signal == 'SELL' ? 'text-danger' : 'text-secondary');
                                                    ?>
                                                    <span class="<?php echo $rsi_class; ?>">
                                                        <?php echo $rsi_value ? number_format($rsi_value, 2) : '-'; ?>
                                                        <i class="fas <?php echo $rsi_signal == 'BUY' ? 'fa-arrow-up' : ($rsi_signal == 'SELL' ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $macd_value = isset($analysis['indicators']['macd']['value']) ? $analysis['indicators']['macd']['value'] : null;
                                                    $macd_signal = isset($analysis['indicators']['macd']['signal']) ? $analysis['indicators']['macd']['signal'] : '-';
                                                    $macd_class = $macd_signal == 'BUY' ? 'text-success' : ($macd_signal == 'SELL' ? 'text-danger' : 'text-secondary');
                                                    ?>
                                                    <span class="<?php echo $macd_class; ?>">
                                                        <?php if ($macd_value !== null): ?>
                                                            <?php echo number_format($macd_value, 4); ?>
                                                        <?php endif; ?>
                                                        <i class="fas <?php echo $macd_signal == 'BUY' ? 'fa-arrow-up' : ($macd_signal == 'SELL' ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $bollinger = isset($analysis['indicators']['bollinger']) ? $analysis['indicators']['bollinger'] : null;
                                                    $bb_signal = isset($bollinger['signal']) ? $bollinger['signal'] : '-';
                                                    $bb_class = $bb_signal == 'BUY' ? 'text-success' : ($bb_signal == 'SELL' ? 'text-danger' : 'text-secondary');
                                                    
                                                    // Pozisyonu belirle (fiyat hangi banda yakın)
                                                    $position = '';
                                                    if ($bollinger && isset($analysis['price'], $bollinger['lower'], $bollinger['upper'])) {
                                                        if ($analysis['price'] <= $bollinger['lower']) {
                                                            $position = ' (Alt)';
                                                        } elseif ($analysis['price'] >= $bollinger['upper']) {
                                                            $position = ' (Üst)';
                                                        } else {
                                                            $position = ' (Orta)';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="<?php echo $bb_class; ?>">
                                                        <i class="fas <?php echo $bb_signal == 'BUY' ? 'fa-arrow-up' : ($bb_signal == 'SELL' ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                                                        <?php echo $bb_signal . $position; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $tv = isset($analysis['indicators']['tradingview']) ? $analysis['indicators']['tradingview'] : null;
                                                    $tv_signal = isset($tv['signal']) ? $tv['signal'] : '-';
                                                    $tv_recommend = isset($tv['recommend_all']) ? $tv['recommend_all'] : null;
                                                    $tv_class = $tv_signal == 'BUY' ? 'text-success' : ($tv_signal == 'SELL' ? 'text-danger' : 'text-secondary');
                                                    ?>
                                                    <span class="<?php echo $tv_class; ?>">
                                                        <i class="fas <?php echo $tv_signal == 'BUY' ? 'fa-arrow-up' : ($tv_signal == 'SELL' ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                                                        <?php echo $tv_recommend !== null ? number_format($tv_recommend, 2) : '-'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $trade_signal = isset($analysis['trade_signal']) ? $analysis['trade_signal'] : 'NEUTRAL';
                                                    $signal_class = $trade_signal == 'BUY' ? 'badge-success' : ($trade_signal == 'SELL' ? 'badge-danger' : 'badge-secondary');
                                                    ?>
                                                    <span class="badge <?php echo $signal_class; ?>">
                                                        <?php echo $trade_signal == 'BUY' ? 'ALIM' : ($trade_signal == 'SELL' ? 'SATIM' : 'BEKLE'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-info coin-detail" data-symbol="<?php echo $symbol; ?>">
                                                            <i class="fas fa-chart-line"></i>
                                                        </button>
                                                        <button class="btn btn-success manual-trade" data-symbol="<?php echo $symbol; ?>" data-action="buy">
                                                            <i class="fas fa-shopping-cart"></i>
                                                        </button>
                                                        <button class="btn btn-danger manual-trade" data-symbol="<?php echo $symbol; ?>" data-action="sell">
                                                            <i class="fas fa-cash-register"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Log yenileme
        $(".refresh-logs").click(function() {
            $.ajax({
                url: "api/get_logs.php",
                success: function(data) {
                    $("#log-container").html(data);
                }
            });
        });
        
        // Bot durumu değiştirme
        $(".toggle-bot").click(function() {
            const action = $(this).data("action");
            
            if (action) {
                // Butonu devre dışı bırak
                $(this).prop("disabled", true);
                
                $.ajax({
                    url: "api/bot_api.php?action=" + action,
                    method: "POST",
                    dataType: "json",
                    success: function(response) {
                        alert(response.message);
                        // Sayfayı yenile
                        location.reload();
                    },
                    error: function() {
                        alert("İşlem sırasında bir hata oluştu.");
                        // Butonu tekrar etkinleştir
                        $(".toggle-bot").prop("disabled", false);
                    }
                });
            }
        });
        
        // Strateji durumu değiştirme
        $(".strategy-toggle").change(function() {
            const strategy = $(this).data("strategy");
            const enabled = $(this).prop("checked");
            
            $.ajax({
                url: "update_strategy.php",
                method: "POST",
                data: { 
                    strategy: strategy,
                    enabled: enabled ? 1 : 0
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // İşlem başarılı
                        console.log("Strateji güncellendi:", strategy, enabled);
                    } else {
                        alert("Strateji güncellenirken bir hata oluştu: " + response.message);
                    }
                },
                error: function() {
                    alert("Strateji güncellenirken bir hata oluştu.");
                    // Değişikliği geri al (UI'yı güncelle)
                    $("#strategy-" + strategy).prop("checked", !enabled);
                }
            });
        });
    });
    </script>
</body>
</html>