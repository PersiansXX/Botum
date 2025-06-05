<?php
session_start();
// Hata raporlama - Geliştirme sırasında hataları görmek için
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Performans takibi için başlangıç zamanı
$start_time = microtime(true);

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

// Bot API'ye bağlan
require_once 'api/bot_api.php';
$bot_api = new BotAPI();

// Force refresh isteği geldi mi kontrol et
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

// Bakiye bilgileri
$total_balance = 0;
$spot_total_balance = 0;
$futures_total_balance = 0;
$margin_total_balance = 0;

// JSON dosyasını kontrol et
$json_file = __DIR__ . '/api/binance_total_balances.json';
$json_exists = file_exists($json_file);
$json_is_recent = $json_exists && (time() - filemtime($json_file) < 3600);

// Force refresh isteği varsa veya JSON dosyası yoksa/eski ise Python scriptini çalıştır 
// Ancak asenkron çalıştırıp beklemeyeceğiz
if ($force_refresh || !$json_is_recent) {
    try {
        // Python script'ini arkaplanda çalıştır
        $python_script_path = dirname(__DIR__) . '/abuzer_bakiye.py';
        $command = "python $python_script_path";
        
        // Script'i arkaplanda çalıştır ve beklemeden devam et
        exec("nohup $command > /dev/null 2>&1 &");
        
        // Kullanıcıya bilgi vermek için log ekleyelim
        error_log("Bakiye yenileme scripti arkaplanda başlatıldı: $command");
    } catch (Exception $e) {
        error_log("Python script çalıştırma hatası: " . $e->getMessage());
    }
}

// JSON dosyasını kullanarak bakiye bilgilerini al
if ($json_exists) {
    try {
        // JSON dosyasından bakiyeleri al
        $balances_json = file_get_contents($json_file);
        $balances_data = json_decode($balances_json, true);
        
        if ($balances_data && json_last_error() === JSON_ERROR_NONE) {
            $spot_total_balance = floatval($balances_data['total_spot'] ?? 0);
            $futures_total_balance = floatval($balances_data['total_futures'] ?? 0);
            $margin_total_balance = floatval($balances_data['total_margin'] ?? 0) + 
                                floatval($balances_data['total_isolated'] ?? 0);
            $total_balance = $spot_total_balance + $futures_total_balance + $margin_total_balance;
            
            error_log("Bakiyeler JSON dosyasından doğrudan alındı: " . number_format($total_balance, 2));
        } else {
            error_log("JSON dosyası geçersiz veya boş: " . json_last_error_msg());
        }
    } catch (Exception $e) {
        error_log("JSON okuma hatası: " . $e->getMessage());
    }
}

// JSON başarısız olursa BotAPI'yi kullan
if ($total_balance <= 0) {
    try {
        // Toplam bakiye (futures dahil)
        $api_total_balance = $bot_api->getBalance(true);
        
        if ($api_total_balance !== "BAKİYE HATA") {
            // API'den başarıyla alındıysa değerleri kullan
            $total_balance = $api_total_balance;
            
            // Spot bakiye
            $spot_total_balance = $bot_api->getBalance(false);
            if ($spot_total_balance === "BAKİYE HATA") {
                $spot_total_balance = 0;
            }
            
            // Futures bakiye
            $futures_total_balance = $bot_api->getFuturesBalance();
            if ($futures_total_balance === "BAKİYE HATA") {
                $futures_total_balance = 0;
            }
            
            // Margin bakiyesi hesapla
            $margin_total_balance = $total_balance - $spot_total_balance - $futures_total_balance;
            $margin_total_balance = max(0, $margin_total_balance); // Negatif olmamasını sağla
            
            error_log("Bakiyeler API'den başarıyla alındı: Toplam=" . $total_balance . 
                    ", Spot=" . $spot_total_balance . ", Futures=" . $futures_total_balance);
        }
    } catch (Exception $e) {
        error_log("Bakiye alınırken genel hata: " . $e->getMessage());
    }
}

// Bot durumunu al
$bot_status = $bot_api->getStatus();

// Aktif coinleri al
$active_coins = $bot_api->getActiveCoins();

// Son işlemleri al
$recent_trades = $bot_api->getRecentTrades(5);

// İstatistikleri al
$today_stats = $bot_api->getTodayStats();
$weekly_stats = $bot_api->getWeeklyProfitStats();
$market_overview = $bot_api->getMarketOverview();

// Debug için yükleme süresini logla
error_log("Dashboard verileri API'den yüklendi. Yükleme süresi: " . round(microtime(true) - $start_time, 4) . " sn");

// Binance bağlantı durumu
$exchange_config = $bot_api->getSettings()['exchange'] ?? 'binance';
$exchange_status = true;

// Bakiye sıfırsa yenileme butonu göster
$show_refresh_button = ($total_balance <= 0);

// Sayfa başlığı
$page_title = 'Trading Bot Dashboard';

// Sayfa yükleme süresini hesapla
$page_load_time = microtime(true) - $start_time;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- TradingView Widget Kütüphanesi -->
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js" defer></script>
    
    <!-- Animasyon için stil -->
    <style>
        .balance-updated {
            animation: balanceUpdate 1s ease;
        }
        
        .balance-error {
            animation: balanceError 1s ease;
        }
        
        @keyframes balanceUpdate {
            0% { color: inherit; }
            25% { color: #28a745; }
            100% { color: inherit; }
        }
        
        @keyframes balanceError {
            0% { color: inherit; }
            25% { color: #dc3545; }
            100% { color: inherit; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <?php if ($show_refresh_button): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Bakiyeler güncel değil!</strong> Bakiyeleri güncellemek için 
            <a href="?refresh=1" class="btn btn-sm btn-warning">Bakiyeleri Yenile</a> butonuna tıklayın.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Sol Menü -->
            <div class="col-md-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-10">
                <!-- Üst Bilgiler -->
                <div class="row mb-4">
                    <!-- Bakiye Özeti -->
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">                                        
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Güncel Bakiye (<?php echo $bot_api->getSettings()['base_currency']; ?>)
                                            <?php if (isset($bot_api->getSettings()['market_type'])): ?>
                                            <span class="badge badge-info ml-1"><?php 
                                                $market_type = $bot_api->getSettings()['market_type'];
                                                echo ($market_type == 'spot') ? 'SPOT' : (($market_type == 'futures') ? 'VADELİ' : 'MARJİN'); 
                                            ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 d-flex align-items-center">
                                            <span id="total-balance"><?php echo number_format($total_balance, 2); ?></span>
                                            <button id="refresh-balance-btn" class="btn btn-sm btn-outline-primary ml-2" title="Bakiyeyi yenile">
                                                <i class="fas fa-sync-alt fa-sm refresh-icon"></i>
                                            </button>
                                            <span id="last-update-time" class="text-xs text-muted ml-2"></span>
                                        </div>
                                        <div class="text-xs mt-1">
                                            <span id="balance-update-status" class="text-success" style="display:none;">
                                                <i class="fas fa-check-circle"></i> Bakiye güncellendi
                                            </span>
                                            <?php if (!empty($balance_error)): ?>
                                            <span class="text-danger">
                                                <a href="api/test_balance.php" target="_blank" title="<?php echo htmlspecialchars($balance_error); ?>">
                                                    <i class="fas fa-exclamation-circle"></i> Bakiye sorunu
                                                </a>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Günlük Kar/Zarar -->
                    <div class="col-md-3">
                        <div class="card <?php echo $today_stats['profit_loss'] >= 0 ? 'border-left-success' : 'border-left-danger'; ?> shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold <?php echo $today_stats['profit_loss'] >= 0 ? 'text-success' : 'text-danger'; ?> text-uppercase mb-1">
                                            Günlük Kar/Zarar (<?php echo $bot_api->getSettings()['base_currency'] ?? 'USDT'; ?>)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $today_stats['profit_loss'] >= 0 ? '+' : ''; ?><?php echo number_format($today_stats['profit_loss'], 2); ?>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <?php echo number_format($today_stats['profit_loss_percentage'], 2); ?>% değişim
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas <?php echo $today_stats['profit_loss'] >= 0 ? 'fa-chart-line' : 'fa-chart-down'; ?> fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Günlük İşlem Sayısı -->
                    <div class="col-md-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Bugünkü İşlemler</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $today_stats['total_trades']; ?>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span class="text-success"><?php echo $today_stats['buy_trades']; ?> alış</span> / 
                                            <span class="text-danger"><?php echo $today_stats['sell_trades']; ?> satış</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bot Durumu -->
                    <div class="col-md-3">
                        <div class="card <?php echo $bot_status['running'] ? 'border-left-success' : 'border-left-danger'; ?> shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold <?php echo $bot_status['running'] ? 'text-success' : 'text-danger'; ?> text-uppercase mb-1">
                                            Bot Durumu</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $bot_status['running'] ? 'Çalışıyor' : 'Durdu'; ?>
                                            <button class="btn btn-sm <?php echo $bot_status['running'] ? 'btn-warning' : 'btn-success'; ?> ml-2 toggle-bot" data-action="<?php echo $bot_status['running'] ? 'stop' : 'start'; ?>">
                                                <i class="fas <?php echo $bot_status['running'] ? 'fa-stop-circle' : 'fa-play-circle'; ?>"></i>
                                                <?php echo $bot_status['running'] ? 'Durdur' : 'Başlat'; ?>
                                            </button>
                                            <?php if (!$bot_status['running']): ?>
                                            <button class="btn btn-sm btn-info ml-1 restart-bot">
                                                <i class="fas fa-sync"></i> Yeniden Başlat
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas <?php echo $bot_status['running'] ? 'fa-play-circle' : 'fa-stop-circle'; ?> fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bakiye Detay Kartları Satırı -->
                <div class="row mb-4">
                    <!-- Spot Bakiye Kartı -->
                    <div class="col-md-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Spot Bakiye (<?php echo $bot_api->getSettings()['base_currency'] ?? 'USDT'; ?>)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="spot-balance">
                                            <?php 
                                            if ($spot_total_balance === "BAKİYE HATA") {
                                                echo '<span class="text-danger">BAKİYE HATA</span>';
                                            } else {
                                                echo number_format($spot_total_balance, 2);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-coins fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Futures Bakiye Kartı -->
                    <div class="col-md-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Vadeli Bakiye (<?php echo $bot_api->getSettings()['base_currency'] ?? 'USDT'; ?>)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="futures-balance">
                                            <?php 
                                            if ($futures_total_balance === "BAKİYE HATA") {
                                                echo '<span class="text-danger">BAKİYE HATA</span>';
                                            } else {
                                                echo number_format($futures_total_balance, 2);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Margin Bakiye Kartı -->
                    <div class="col-md-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Marjin Bakiye (<?php echo $bot_api->getSettings()['base_currency'] ?? 'USDT'; ?>)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="margin-balance">
                                            <?php echo number_format($margin_total_balance, 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Grafik Bölümü -->
                    <div class="col-md-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Son 7 Günlük Performans</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                        aria-labelledby="dropdownMenuLink">
                                        <div class="dropdown-header">Grafik Seçenekleri:</div>
                                        <a class="dropdown-item" href="#" data-period="7">Son 7 Gün</a>
                                        <a class="dropdown-item" href="#" data-period="30">Son 30 Gün</a>
                                        <a class="dropdown-item" href="reports.php">Detaylı Rapor</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="profitChart" style="min-height: 250px;"></canvas>
                            </div>
                            <div class="card-footer text-center small">
                                <span class="mr-2">
                                    <i class="fas fa-circle text-success"></i> Karlı İşlemler
                                </span>
                                <span class="mr-2">
                                    <i class="fas fa-circle text-danger"></i> Zararlı İşlemler
                                </span>
                                <span>
                                    <i class="fas fa-arrow-trend-up text-info"></i> Toplam Net Kar: 
                                    <strong class="<?php echo array_sum(array_column($weekly_stats, 'profit')) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format(array_sum(array_column($weekly_stats, 'profit')), 2); ?> <?php echo $bot_api->getSettings()['base_currency']; ?>
                                    </strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Son İşlemler -->
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Son İşlemler</h6>
                                <a href="trades.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list fa-sm"></i> Tümünü Gör
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_trades)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <p>Henüz işlem yapılmadı.</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Coin</th>
                                                <th>Tür</th>
                                                <th>Fiyat</th>
                                                <th>K/Z</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_trades as $trade): ?>
                                            <tr>
                                                <td><?php echo $trade['symbol']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $trade['type'] == 'BUY' ? 'badge-success' : 'badge-danger'; ?>">
                                                        <?php echo $trade['type'] == 'BUY' ? 'AL' : 'SAT'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($trade['price'], $trade['price'] < 1 ? 6 : 2); ?></td>
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
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- İzlenen En İyi Performans Coin'leri -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Günün En İyi Performansları</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                // En iyi performans gösteren coinler (24 saatlik değişime göre)
                                usort($active_coins, function($a, $b) {
                                    return $b['change_24h'] <=> $a['change_24h'];
                                });
                                $top_coins = array_slice($active_coins, 0, 3);
                                ?>
                                <?php if (empty($top_coins)): ?>
                                <div class="text-center text-muted py-3">
                                    <p>Yeterli veri yok.</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Coin</th>
                                                <th>Fiyat</th>
                                                <th>24s Değişim</th>
                                                <th>Sinyal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_coins as $coin): ?>
                                            <tr>
                                                <td><?php echo $coin['symbol']; ?></td>
                                                <td>
                                                    <?php 
                                                    if (isset($coin['price'])) {
                                                        echo number_format($coin['price'], $coin['price'] < 1 ? 6 : 2); 
                                                    } else {
                                                        echo "N/A";
                                                    }
                                                    ?>
                                                </td>
                                                <td class="<?php echo $coin['change_24h'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $coin['change_24h'] >= 0 ? '+' : ''; ?><?php echo number_format($coin['change_24h'], 2); ?>%
                                                </td>
                                                <td>
                                                    <?php
                                                    // Yeni sütun yapısına uygun olarak trade_signal değerini kullan
                                                    $signal = strtoupper($coin['signal'] ?? 'NEUTRAL');
                                                    $badge_class = $signal == 'BUY' ? 'badge-success' : ($signal == 'SELL' ? 'badge-danger' : 'badge-secondary');
                                                    $signal_text = $signal == 'BUY' ? 'AL' : ($signal == 'SELL' ? 'SAT' : 'BEKLİYOR');
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo $signal_text; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İkinci Satır -->
                <div class="row">
                    <!-- Aktif Stratejiler -->
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Aktif Stratejiler</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $strategies = $bot_api->getActiveStrategies();
                                foreach ($strategies as $key => $strategy):
                                ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                    <div>
                                        <h6 class="mb-0"><?php echo $strategy['name']; ?></h6>
                                        <small class="text-muted"><?php echo $strategy['description']; ?></small>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input strategy-toggle" 
                                            id="strategy-<?php echo $key; ?>" 
                                            data-strategy="<?php echo $key; ?>" 
                                            <?php echo $strategy['enabled'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="strategy-<?php echo $key; ?>"></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Son Loglar -->
                    <div class="col-md-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Son Loglar</h6>
                                <button class="btn btn-sm btn-info refresh-logs">
                                    <i class="fas fa-sync-alt fa-sm"></i> Yenile
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="log-container" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Yükleniyor...</span>
                                        </div>
                                        <p class="mt-2">Loglar yükleniyor...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/performance_optimizer.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <!-- AJAX ile bakiye güncellemesi için script -->
    <script>
    $(document).ready(function() {
        // Bakiyeyi güncelleme fonksiyonu
        function refreshBalances() {
            // Yenileme simgesini döndürmeye başla
            $('.refresh-icon').addClass('fa-spin');
            
            // Durum mesajını göster
            $('#balance-update-status').hide();
            
            $.ajax({
                url: 'api/get_balances.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        // Toplam bakiye güncelle
                        $('#total-balance').text(parseFloat(data.total_balance).toFixed(2));
                        $('#total-balance').addClass('balance-updated');
                        
                        // Spot bakiye güncelle (varsa)
                        if ($('#spot-balance').length && data.spot_balance) {
                            $('#spot-balance').text(parseFloat(data.spot_balance).toFixed(2));
                            $('#spot-balance').addClass('balance-updated');
                        }
                        
                        // Futures bakiye güncelle (varsa)
                        if ($('#futures-balance').length && data.futures_balance) {
                            $('#futures-balance').text(parseFloat(data.futures_balance).toFixed(2));
                            $('#futures-balance').addClass('balance-updated');
                        }
                        
                        // Margin bakiye güncelle (varsa)
                        if ($('#margin-balance').length && data.margin_balance) {
                            $('#margin-balance').text(parseFloat(data.margin_balance).toFixed(2));
                            $('#margin-balance').addClass('balance-updated');
                        }
                        
                        // Şimdiki zamanı localStorage'a kaydet
                        const currentTime = Date.now();
                        localStorage.setItem('lastBalanceUpdate', currentTime);
                        
                        // Son güncelleme zamanını göster
                        $('#last-update-time').text('Son güncelleme: ' + new Date().toLocaleTimeString());
                        
                        // Durum mesajını göster
                        $('#balance-update-status').text('Bakiye güncellendi').removeClass('text-danger').addClass('text-success').show();
                        
                        // Animasyonu kaldır
                        setTimeout(function() {
                            $('.balance-updated').removeClass('balance-updated');
                            $('#balance-update-status').fadeOut(2000);
                        }, 2000);
                        
                        console.log("Bakiyeler güncellendi: " + new Date().toLocaleTimeString());
                    } else {
                        console.error("Bakiye güncellenirken hata: " + data.message);
                        
                        // Hata durumu mesajını göster
                        $('#balance-update-status').text('Bakiye güncellenemedi').removeClass('text-success').addClass('text-danger').show();
                        
                        // Hata varsa animasyon ekle
                        $('.balance-value').addClass('balance-error');
                        
                        // Animasyonu kaldır
                        setTimeout(function() {
                            $('.balance-error').removeClass('balance-error');
                            $('#balance-update-status').fadeOut(2000);
                        }, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX hatası: " + error);
                    
                    // Hata durumu mesajını göster
                    $('#balance-update-status').text('Bağlantı hatası').removeClass('text-success').addClass('text-danger').show();
                    
                    // Animasyonu kaldır
                    setTimeout(function() {
                        $('#balance-update-status').fadeOut(2000);
                    }, 2000);
                },
                complete: function() {
                    // Yenileme simgesinin dönmesini durdur
                    $('.refresh-icon').removeClass('fa-spin');
                }
            });
        }
        
        // Yenileme butonu için olay dinleyici
        $('#refresh-balance-btn').on('click', function(e) {
            e.preventDefault();
            refreshBalances();
        });
        
        // Otomatik yenileme - sayfa yüklendikten 30 saniye sonra başla ve her 5 dakikada bir güncelle
        setTimeout(function() {
            refreshBalances();
            setInterval(refreshBalances, 300000); // 5 dakikada bir (300000 ms)
        }, 30000);
        
        // Sayfa ilk yüklendiğinde son güncelleme zamanını göster
        if (localStorage.getItem('lastBalanceUpdate')) {
            $('#last-update-time').text('Son güncelleme: ' + new Date(parseInt(localStorage.getItem('lastBalanceUpdate'))).toLocaleTimeString());
        } else {
            $('#last-update-time').text('Son güncelleme: ' + new Date().toLocaleTimeString());
            localStorage.setItem('lastBalanceUpdate', Date.now());
        }
    });
    </script>

    <!-- Kar/Zarar Grafiği -->
    <script>
        // Grafik verileri
        const profitData = {
            labels: <?php echo json_encode(array_map(function($item) { 
                return date('d M', strtotime($item['date']));
            }, $weekly_stats)); ?>,
            profits: <?php echo json_encode(array_map(function($item) { 
                return $item['profit'];
            }, $weekly_stats)); ?>,
            trades: <?php echo json_encode(array_map(function($item) { 
                return $item['trades_count'];
            }, $weekly_stats)); ?>
        };

        // Grafik oluştur
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('profitChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: profitData.labels,
                    datasets: [{
                        label: 'Günlük Kar/Zarar',
                        data: profitData.profits,
                        backgroundColor: profitData.profits.map(value => value >= 0 ? 'rgba(75, 192, 192, 0.2)' : 'rgba(255, 99, 132, 0.2)'),
                        borderColor: profitData.profits.map(value => value >= 0 ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)'),
                        borderWidth: 1
                    }, {
                        label: 'İşlem Sayısı',
                        data: profitData.trades,
                        type: 'line',
                        fill: false,
                        borderColor: 'rgba(54, 162, 235, 0.8)',
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        pointBorderColor: 'rgba(54, 162, 235, 0.8)',
                        pointBackgroundColor: 'rgba(54, 162, 235, 0.8)',
                        yAxisID: 'y2'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(2) + ' USDT';
                                }
                            },
                            title: {
                                display: true,
                                text: 'Kar/Zarar'
                            }
                        },
                        y2: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'İşlem Sayısı'
                            }
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var value = context.parsed.y;
                                    
                                    if (context.datasetIndex === 0) {
                                        return label + ': ' + parseFloat(value).toFixed(2) + ' <?php echo $bot_api->getSettings()['base_currency']; ?>';
                                    } else {
                                        return label + ': ' + value;
                                    }
                                }
                            }
                        },
                        legend: {
                            display: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>