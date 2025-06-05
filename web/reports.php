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

// Tarih aralığı filtreleme
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// API'den verileri çek
$weekly_stats = $bot_api->getWeeklyProfitStats();
$market_overview = $bot_api->getMarketOverview();
$today_stats = $bot_api->getTodayStats();
$total_balance = $bot_api->getBalance();

// Özet rapor verileri
$total_profit = array_sum(array_column($weekly_stats, 'profit'));
$profit_days = count(array_filter($weekly_stats, function($day) { return $day['profit'] > 0; }));
$loss_days = count(array_filter($weekly_stats, function($day) { return $day['profit'] < 0; }));

// En iyi performans gösteren semboller
$all_coins = $bot_api->getActiveCoins();
usort($all_coins, function($a, $b) {
    return $b['change_24h'] <=> $a['change_24h'];
});
$best_performers = array_slice($all_coins, 0, 5);

// Sayfa başlığı
$page_title = 'Performans Raporları';
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
    <link rel="stylesheet" href="assets/css/reports.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0"><i class="fas fa-chart-line text-primary mr-2"></i><?php echo $page_title; ?></h3>
                    
                    <!-- Son Güncelleme Bilgisi -->
                    <div class="text-muted">
                        <small>
                            <i class="fas fa-sync-alt mr-1"></i>
                            Son Güncelleme: <?php echo date('d.m.Y H:i:s'); ?>
                            <button id="refresh-data" class="btn btn-sm btn-outline-secondary ml-2">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                        </small>
                    </div>
                </div>
                
                <!-- Özet Bilgiler -->
                <div class="row mb-4">
                    <!-- Toplam Bakiye -->
                    <div class="col-md-3">
                        <div class="card report-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Toplam Bakiye</h6>
                                        <h3><?php echo number_format($total_balance, 2); ?> USDT</h3>
                                    </div>
                                    <div class="report-summary-icon text-info">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-3">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Mevcut toplam hesap bakiyesi
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Haftalık Kar/Zarar -->
                    <div class="col-md-3">
                        <div class="card report-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Haftalık Kar/Zarar</h6>
                                        <h3 class="<?php echo $total_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                            <?php echo $total_profit >= 0 ? '+' : ''; ?><?php echo number_format($total_profit, 2); ?> USDT
                                        </h3>
                                    </div>
                                    <div class="report-summary-icon <?php echo $total_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <i class="fas <?php echo $total_profit >= 0 ? 'fa-chart-line' : 'fa-chart-down'; ?>"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-arrow-up text-success mr-1"></i> Kazançlı: <?php echo $profit_days; ?> gün</span>
                                        <span><i class="fas fa-arrow-down text-danger mr-1"></i> Zararlı: <?php echo $loss_days; ?> gün</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Toplam İşlem -->
                    <div class="col-md-3">
                        <div class="card report-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Toplam İşlem</h6>
                                        <h3><?php echo array_sum(array_column($weekly_stats, 'trades_count')); ?></h3>
                                    </div>
                                    <div class="report-summary-icon text-warning">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-3">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Son 7 gün içinde yapılan işlemler
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BTC Dominance -->
                    <div class="col-md-3">
                        <div class="card report-card market-data-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">BTC Dominance</h6>
                                        <h3><?php echo number_format($market_overview['btc_dominance'], 2); ?>%</h3>
                                    </div>
                                    <div class="report-summary-icon text-warning">
                                        <i class="fab fa-bitcoin"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-3">
                                    <i class="fas fa-globe mr-1"></i>
                                    Toplam Piyasa Değeri: $<?php echo number_format($market_overview['total_market_cap'] / 1000000000, 1); ?> milyar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ana Rapor İçeriği - Tablarla -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <ul class="nav nav-tabs card-header-tabs report-tab" id="reportTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="profit-tab" data-toggle="tab" href="#profit" role="tab">
                                    <i class="fas fa-chart-line mr-1"></i> Kar/Zarar Analizi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="coins-tab" data-toggle="tab" href="#coins" role="tab">
                                    <i class="fab fa-bitcoin mr-1"></i> Coin Performansı
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="market-tab" data-toggle="tab" href="#market" role="tab">
                                    <i class="fas fa-globe mr-1"></i> Piyasa Özeti
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content" id="reportTabContent">
                        <!-- Kar/Zarar Analizi Tab Içeriği -->
                        <div class="tab-pane fade show active" id="profit" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="text-muted mb-3">Günlük Kar/Zarar Grafiği</h6>
                                    <div class="chart-container" style="position: relative; height:300px;">
                                        <canvas id="profitChart"></canvas>
                                    </div>
                                    <div class="text-center mt-3">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeTimeframe('week')">Haftalık</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeTimeframe('month')">Aylık</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeTimeframe('year')">Yıllık</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-3">Performans Özeti</h6>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Toplam Kar/Zarar:</td>
                                                <td class="<?php echo $total_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                    <?php echo $total_profit >= 0 ? '+' : ''; ?><?php echo number_format($total_profit, 2); ?> USDT
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Kazançlı Günler:</td>
                                                <td><span class="badge badge-success"><?php echo $profit_days; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td>Zararlı Günler:</td>
                                                <td><span class="badge badge-danger"><?php echo $loss_days; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td>Ortalama Günlük Kar:</td>
                                                <td>
                                                    <?php 
                                                    $avg_daily_profit = array_sum(array_column($weekly_stats, 'profit')) / count($weekly_stats);
                                                    echo '<span class="' . ($avg_daily_profit >= 0 ? 'profit-positive' : 'profit-negative') . '">';
                                                    echo ($avg_daily_profit >= 0 ? '+' : '') . number_format($avg_daily_profit, 2) . ' USDT';
                                                    echo '</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Başarı Oranı:</td>
                                                <td>
                                                    <?php
                                                    $success_rate = ($profit_days / count($weekly_stats)) * 100;
                                                    echo number_format($success_rate, 1) . '%';
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <h6 class="text-muted mt-4 mb-3">En İyi Performans Gösterenler</h6>
                                    <ul class="list-group">
                                        <?php foreach ($best_performers as $idx => $coin): ?>
                                            <li class="list-group-item py-2 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge badge-light mr-2"><?php echo $idx + 1; ?></span>
                                                    <?php echo $coin['symbol']; ?>
                                                </div>
                                                <span class="badge badge-success badge-pill">
                                                    +<?php echo number_format($coin['change_24h'], 2); ?>%
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Coin Performansı Tab Içeriği -->
                        <div class="tab-pane fade" id="coins" role="tabpanel">
                            <div class="mb-3">
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="coinSearchInput" class="form-control" placeholder="Coin ara...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="coinPerformanceTable">
                                    <thead>
                                        <tr>
                                            <th>Sembol</th>
                                            <th>Güncel Fiyat</th>
                                            <th>24s Değişim</th>
                                            <th>7g Değişim</th>
                                            <th>Sinyal</th>
                                            <th>RSI</th>
                                            <th>Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_coins as $coin): ?>
                                        <tr>
                                            <td><strong><?php echo $coin['symbol']; ?></strong></td>
                                            <td>
                                                <?php 
                                                if (isset($coin['price'])) {
                                                    echo number_format($coin['price'], $coin['price'] < 10 ? 6 : 2); 
                                                } else {
                                                    echo "N/A";
                                                }
                                                ?> USDT
                                            </td>
                                            <td class="<?php echo $coin['change_24h'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                <?php echo $coin['change_24h'] >= 0 ? '+' : ''; ?><?php echo number_format($coin['change_24h'], 2); ?>%
                                            </td>
                                            <td>
                                                <?php
                                                // 7 günlük değişim (örnek veri)
                                                $change_7d = rand(-15, 20) / 10;
                                                echo '<span class="' . ($change_7d >= 0 ? 'profit-positive' : 'profit-negative') . '">';
                                                echo ($change_7d >= 0 ? '+' : '') . number_format($change_7d, 2) . '%';
                                                echo '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $signal = strtoupper($coin['signal'] ?? 'NEUTRAL');
                                                if ($signal == 'BUY') {
                                                    echo '<span class="badge badge-success">AL</span>';
                                                } elseif ($signal == 'SELL') {
                                                    echo '<span class="badge badge-danger">SAT</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">BEKLİYOR</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $rsiValue = isset($coin['indicators']['rsi']) ? $coin['indicators']['rsi'] : '--';
                                                // Check if RSI value is an array and handle it appropriately
                                                if (is_array($rsiValue)) {
                                                    // If it's an array, take the first value or a specific key
                                                    $rsiValue = isset($rsiValue[0]) ? $rsiValue[0] : '--';
                                                }
                                                
                                                if ($rsiValue != '--' && !is_array($rsiValue)) {
                                                    $rsiClass = '';
                                                    if ($rsiValue <= 30) $rsiClass = 'profit-positive';
                                                    elseif ($rsiValue >= 70) $rsiClass = 'profit-negative';
                                                    echo '<span class="'.$rsiClass.'">'.$rsiValue.'</span>';
                                                } else {
                                                    echo '--';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $ma = isset($coin['indicators']['moving_average']) ? $coin['indicators']['moving_average'] : null;
                                                if ($ma && !is_array($ma)) {
                                                    if ($ma['short_ma'] > $ma['long_ma']) {
                                                        echo '<i class="fas fa-arrow-up trend-arrow-up"></i> Yükseliş';
                                                    } elseif ($ma['short_ma'] < $ma['long_ma']) {
                                                        echo '<i class="fas fa-arrow-down trend-arrow-down"></i> Düşüş';
                                                    } else {
                                                        echo '<i class="fas fa-minus trend-arrow-neutral"></i> Yatay';
                                                    }
                                                } elseif ($ma && is_array($ma)) {
                                                    // Handle the case where $ma is an array
                                                    echo '<i class="fas fa-minus trend-arrow-neutral"></i> Veri İşleniyor';
                                                } else {
                                                    echo '--';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Piyasa Özeti Tab Içeriği -->
                        <div class="tab-pane fade" id="market" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Piyasa İstatistikleri</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tbody>
                                                    <tr>
                                                        <td>BTC Dominance:</td>
                                                        <td><?php echo number_format($market_overview['btc_dominance'], 2); ?>%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Toplam Piyasa Değeri:</td>
                                                        <td>$<?php echo number_format($market_overview['total_market_cap'] / 1000000000, 1); ?> milyar</td>
                                                    </tr>
                                                    <tr>
                                                        <td>24s Toplam İşlem Hacmi:</td>
                                                        <td>$<?php echo number_format($market_overview['total_volume_24h'] / 1000000000, 1); ?> milyar</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Son Güncelleme:</td>
                                                        <td><?php echo date('d.m.Y H:i:s', strtotime($market_overview['last_updated'])); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">En İyi & En Kötü Performans</h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">Son 24 saat içinde piyasalarda en iyi ve en kötü performans gösteren coinler</p>
                                            <div class="d-flex justify-content-between mb-3">
                                                <div>
                                                    <span class="text-success font-weight-bold">
                                                        <i class="fas fa-arrow-up mr-1"></i>En İyi: 
                                                    </span>
                                                    <span><?php echo $market_overview['best_performer']['symbol']; ?></span>
                                                </div>
                                                <span class="badge badge-success">+<?php echo number_format($market_overview['best_performer']['change'], 2); ?>%</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <span class="text-danger font-weight-bold">
                                                        <i class="fas fa-arrow-down mr-1"></i>En Kötü: 
                                                    </span>
                                                    <span><?php echo $market_overview['worst_performer']['symbol']; ?></span>
                                                </div>
                                                <span class="badge badge-danger"><?php echo number_format($market_overview['worst_performer']['change'], 2); ?>%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Son İşlemler</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            $recent_trades = $bot_api->getRecentTrades(10);
                                            
                                            if (empty($recent_trades)): ?>
                                                <div class="text-center text-muted py-4">
                                                    <p>Henüz işlem yapılmadı.</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Tarih</th>
                                                                <th>Coin</th>
                                                                <th>Tür</th>
                                                                <th>Fiyat</th>
                                                                <th>K/Z</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($recent_trades as $trade): ?>
                                                            <tr>
                                                                <td class="small"><?php echo date('d.m H:i', strtotime($trade['timestamp'])); ?></td>
                                                                <td><?php echo $trade['symbol']; ?></td>
                                                                <td>
                                                                    <span class="badge <?php echo $trade['type'] == 'BUY' ? 'badge-success' : 'badge-danger'; ?>">
                                                                        <?php echo $trade['type'] == 'BUY' ? 'AL' : 'SAT'; ?>
                                                                    </span>
                                                                </td>
                                                                <td><?php echo number_format($trade['price'], $trade['price'] < 1 ? 6 : 2); ?></td>
                                                                <td class="<?php echo $trade['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detaylı Rapor Tablosu -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Günlük Detaylı Rapor</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download mr-1"></i> Raporu İndir
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>İşlem Sayısı</th>
                                        <th>Alım</th>
                                        <th>Satım</th>
                                        <th>Kar/Zarar</th>
                                        <th>Değişim %</th>
                                        <th>En İyi Coin</th>
                                        <th>Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($weekly_stats as $day): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($day['date'])); ?></td>
                                            <td><?php echo $day['trades_count']; ?></td>
                                            <td><?php echo round($day['trades_count'] * 0.5); ?></td>
                                            <td><?php echo round($day['trades_count'] * 0.5); ?></td>
                                            <td class="<?php echo $day['profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                <strong>
                                                    <?php echo $day['profit'] >= 0 ? '+' : ''; ?>
                                                    <?php echo number_format($day['profit'], 2); ?> USDT
                                                </strong>
                                            </td>
                                            <td class="<?php echo $day['profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                <?php 
                                                $change_percent = ($day['profit'] / $total_balance) * 100;
                                                echo $change_percent >= 0 ? '+' : '';
                                                echo number_format($change_percent, 2) . '%';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $coins = ['BTC/USDT', 'ETH/USDT', 'SOL/USDT', 'ADA/USDT', 'DOT/USDT'];
                                                echo $coins[array_rand($coins)];
                                                ?>
                                            </td>
                                            <td>
                                                <a href="daily_report.php?date=<?php echo $day['date']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td>Toplam</td>
                                        <td><?php echo array_sum(array_column($weekly_stats, 'trades_count')); ?></td>
                                        <td><?php echo round(array_sum(array_column($weekly_stats, 'trades_count')) * 0.5); ?></td>
                                        <td><?php echo round(array_sum(array_column($weekly_stats, 'trades_count')) * 0.5); ?></td>
                                        <td class="<?php echo $total_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                            <?php echo $total_profit >= 0 ? '+' : ''; ?><?php echo number_format($total_profit, 2); ?> USDT
                                        </td>
                                        <td class="<?php echo $total_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                            <?php 
                                            $total_change = ($total_profit / $total_balance) * 100;
                                            echo $total_change >= 0 ? '+' : '';
                                            echo number_format($total_change, 2) . '%';
                                            ?>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
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
        // Sayfa yüklendiğinde
        $(document).ready(function() {
            // Kar/Zarar grafiği
            createProfitChart();
            
            // Coin arama filtreleme
            $("#coinSearchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#coinPerformanceTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
            
            // Verileri yenile butonu
            $("#refresh-data").click(function() {
                window.location.reload();
            });
        });
        
        // Kar/Zarar grafiği oluşturma
        function createProfitChart() {
            const profitCtx = document.getElementById('profitChart').getContext('2d');
            
            // Weekly stats verilerinden chart verisi oluştur
            const chartData = {
                labels: [<?php echo implode(', ', array_map(function($day) { 
                    return "'" . date('d.m', strtotime($day['date'])) . "'"; 
                }, $weekly_stats)); ?>],
                profits: [<?php echo implode(', ', array_column($weekly_stats, 'profit')); ?>],
                trades: [<?php echo implode(', ', array_column($weekly_stats, 'trades_count')); ?>]
            };
            
            const profitChart = new Chart(profitCtx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Günlük Kar/Zarar (USDT)',
                        data: chartData.profits,
                        backgroundColor: chartData.profits.map(value => value >= 0 ? 'rgba(40, 167, 69, 0.6)' : 'rgba(220, 53, 69, 0.6)'),
                        borderColor: chartData.profits.map(value => value >= 0 ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)'),
                        borderWidth: 1
                    }, {
                        label: 'İşlem Sayısı',
                        data: chartData.trades,
                        type: 'line',
                        fill: false,
                        borderColor: 'rgba(54, 162, 235, 0.8)',
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        pointBorderColor: 'rgba(54, 162, 235, 0.8)',
                        pointBackgroundColor: 'rgba(54, 162, 235, 0.8)',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Kar/Zarar (USDT)'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'İşlem Sayısı'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.datasetIndex === 0) {
                                        label += (context.raw >= 0 ? '+' : '') + context.raw.toFixed(2) + ' USDT';
                                    } else {
                                        label += context.raw + ' işlem';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Zaman aralığı değiştirme
        function changeTimeframe(timeframe) {
            let startDate = new Date();
            let endDate = new Date();
            
            switch(timeframe) {
                case 'week':
                    startDate.setDate(startDate.getDate() - 7);
                    break;
                case 'month':
                    startDate.setMonth(startDate.getMonth() - 1);
                    break;
                case 'year':
                    startDate.setFullYear(startDate.getFullYear() - 1);
                    break;
            }
            
            // Tarih formatı YYYY-MM-DD
            document.location.href = 'reports.php?start_date=' + startDate.toISOString().split('T')[0] + '&end_date=' + endDate.toISOString().split('T')[0];
        }
    </script>
</body>
</html>