<?php
// Define constant to prevent direct access to API files
define('TRADING_BOT', true);
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

// Binance API'ye bağlan (bakiye için)
require_once 'api/binance_api.php';
$binance_api = new BinanceAPI();

// Bot durumunu al
$bot_status = $bot_api->getStatus();

// Binance bağlantı durumu (gerçek API bağlantısı burada kontrol edilmeli)
$exchange_config = $bot_api->getSettings()['exchange'] ?? 'binance';
$exchange_status = true; // Bu değer gerçek API kontrolü ile değiştirilmeli

// Aktif coinleri al
$active_coins = $bot_api->getActiveCoins();

// Son işlemleri al
$recent_trades = $bot_api->getRecentTrades(5);

// İstatistikleri al
$today_stats = $bot_api->getTodayStats();
$weekly_stats = $bot_api->getWeeklyProfitStats();
$market_overview = $bot_api->getMarketOverview();

// Bakiye almaya çalışırken hatalarımızı ayrıntılı olarak logla
$balance_source = '';
$balance_error = '';

// Güncel bakiye bilgisini önce bot API'sinden almayı deneyelim
$total_balance = 0;
$usdt_balance = 0;

try {
    error_log("[Index] Bot API'den bakiye alınmaya çalışılıyor...");
    $total_balance = $bot_api->getBalance();
    if ($total_balance > 0) {
        $usdt_balance = $total_balance; // Varsayılan olarak toplam bakiyeyi USDT bakiyesi olarak ayarlayalım
        $balance_source = 'bot_api';
        error_log("[Index] Bot API'den bakiye başarıyla alındı: $total_balance");
    } else {
        error_log("[Index] Bot API bakiyesi sıfır veya geçersiz: $total_balance");
    }
} catch (Exception $e) {
    error_log("[Index] Bot API bakiye hatası: " . $e->getMessage());
    $balance_error .= "Bot API: " . $e->getMessage() . "; ";
}

// Eğer bot bakiyesi alınamadıysa, Binance API'yi deneyelim
if ($total_balance <= 0) {
    try {
        error_log("[Index] Binance API'den bakiye alınmaya çalışılıyor...");
        $binance_api->setDebug(true); // Debug bilgisini aktifleştir
        $balance_info = $binance_api->getAccountBalance('', '');
        
        if (isset($balance_info['error'])) {
            error_log("[Index] Binance API bakiye hatası: " . $balance_info['error']);
            $balance_error .= "Binance API: " . $balance_info['error'] . "; ";
        } else {
            $total_balance = isset($balance_info['totalBalance']) ? $balance_info['totalBalance'] : 0;
            $balances = isset($balance_info['balances']) ? $balance_info['balances'] : [];
            $balance_source = 'binance_api';
            
            // USDT bakiyesini bul
            foreach ($balances as $balance) {
                if ($balance['asset'] === 'USDT') {
                    $usdt_balance = $balance['free'];
                    break;
                }
            }
            error_log("[Index] Binance API'den bakiye başarıyla alındı: $total_balance USDT: $usdt_balance");
        }
    } catch (Exception $e) {
        error_log("[Index] Binance API bakiye hatası: " . $e->getMessage());
        $balance_error .= "Binance API: " . $e->getMessage();
    }
}

// Son kontrol: Hala bakiye alınamadıysa kullanıcıya bilgi ver
if ($total_balance <= 0) {
    error_log("[Index] Her iki kaynaktan da bakiye alınamadı, varsayılan değer kullanılıyor");
    $total_balance = 0;
    $usdt_balance = 0;
}

// Sayfa başlığı
$page_title = 'Trading Bot Dashboard';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- WebSocket Bakiye Güncelleme Skripti -->    
    <script>
        // Bakiye bilgilerini güncellemek için WebSocket kullanımı
        document.addEventListener('DOMContentLoaded', function() {
            // Bakiye güncelleme aralığı (ms)
            const UPDATE_INTERVAL = 20000; // 20 saniye
            const RETRY_INTERVAL = 5000; // 5 saniye (hata durumunda tekrar deneme süresi)
            const MAX_RETRIES = 3; // Maksimum tekrar deneme sayısı
            const baseCurrency = '<?php echo $bot_api->getSettings()["base_currency"]; ?>';
            
            // Bakiye DOM elemanları
            const totalBalanceElement = document.getElementById('total-balance');
            const lastBalanceValue = localStorage.getItem('last_balance_value') || '<?php echo number_format($total_balance, 2); ?>';
            
            // Hata takibi için değişkenler
            let consecutiveErrors = 0;
            let lastSuccessfulUpdate = Date.now();
            
            // İlk yüklemede localStorage'dan değeri ayarla
            if (totalBalanceElement && lastBalanceValue) {
                totalBalanceElement.textContent = lastBalanceValue;
            }
            
            // Bakiye bilgilerini güncelleme fonksiyonu
            function updateBalance() {
                fetch('api/get_balance.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        // Log the raw response for debugging
                        return response.text().then(text => {
                            try {
                                // Try to parse the response as JSON
                                const data = JSON.parse(text);
                                return data;
                            } catch (e) {
                                // Log the parsing error and the raw response
                                console.error("JSON Parse Error:", e);
                                console.log("Raw Response:", text);
                                throw new Error("JSON parsing failed: " + e.message + ". Raw data: " + text.substring(0, 100) + "...");
                            }
                        });
                    })
                    .then(data => {
                        if (data && data.success && data.data) {
                            // Toplam bakiyeyi güncelle
                            if (totalBalanceElement) {
                                totalBalanceElement.textContent = parseFloat(data.data.total_balance).toFixed(2);
                                // Başarılı güncellemeyi localStorage'a kaydet
                                localStorage.setItem('last_balance_value', parseFloat(data.data.total_balance).toFixed(2));
                            }
                            
                            // USDT bakiyesini göster (varsa)
                            const usdtBalanceElement = document.getElementById('usdt-balance');
                            if (usdtBalanceElement && data.data.usdt_balance) {
                                usdtBalanceElement.textContent = parseFloat(data.data.usdt_balance).toFixed(2);
                            }
                            
                            // Balance source indicator
                            const sourceElement = document.getElementById('balance-source');
                            if (sourceElement) {
                                sourceElement.textContent = data.data.source === 'binance_api' ? 'Binance' : 'Bot';
                            }
                            
                            // Güncelleme istatistikleri
                            consecutiveErrors = 0;
                            lastSuccessfulUpdate = Date.now();
                            
                            // Güncelleme animasyonu
                            if (totalBalanceElement) {
                                totalBalanceElement.classList.add('balance-updated');
                                setTimeout(() => totalBalanceElement.classList.remove('balance-updated'), 1000);
                            }
                        } else {
                            console.error("Invalid data structure:", data);
                            consecutiveErrors++;
                        }
                    })
                    .catch(error => {
                        console.error("API erişim hatası:", error);
                        consecutiveErrors++;
                        
                        // Birkaç başarısız denemeden sonra hata mesajı göster
                        if (consecutiveErrors > MAX_RETRIES) {
                            if (totalBalanceElement) {
                                totalBalanceElement.innerHTML = `<span class="text-danger">Bağlantı hatası</span>`;
                            }
                        }
                    });
            }
            
            // İlk bakiye güncellemesini yap
            setTimeout(updateBalance, 2000); // Sayfa yüklendikten 2 saniye sonra ilk güncellemeyi yap
            
            // Belirli aralıklarla bakiye bilgilerini güncelle
            setInterval(updateBalance, UPDATE_INTERVAL);
        });
    </script>
    
    <!-- Animasyon için stil -->
    <style>
        .balance-updated {
            animation: balanceUpdate 1s ease;
        }
        
        @keyframes balanceUpdate {
            0% { color: inherit; }
            25% { color: #28a745; }
            100% { color: inherit; }
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
                <!-- Üst Bilgiler -->
                <div class="row mb-4">
                    <!-- Bakiye Özeti -->
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Güncel Bakiye (<?php echo $bot_api->getSettings()['base_currency']; ?>)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-balance">
                                            <?php echo number_format($total_balance, 2); ?>
                                        </div>
                                        <?php if (!empty($balance_error)): ?>
                                        <div class="text-xs text-danger">
                                            <a href="api/test_balance.php" target="_blank" title="<?php echo htmlspecialchars($balance_error); ?>">
                                                <i class="fas fa-exclamation-circle"></i> Bakiye sorunu
                                            </a>
                                        </div>
                                        <?php endif; ?>
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
                                            Günlük Kar/Zarar (<?php echo $bot_api->getSettings()['base_currency']; ?>)</div>
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
    <script src="assets/js/dashboard.js"></script>

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
                        yAxisID: 'y-axis-2'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            id: 'y-axis-1',
                            position: 'left',
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(2) + ' <?php echo $bot_api->getSettings()['base_currency']; ?>';
                                }
                            },
                            title: {
                                display: true,
                                text: 'Kar/Zarar'
                            }
                        },
                        y1: {
                            id: 'y-axis-2',
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: 'İşlem Sayısı'
                            }
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var datasetLabel = data.datasets[tooltipItem.datasetIndex].label;
                                var value = tooltipItem.value;
                                
                                if (tooltipItem.datasetIndex === 0) {
                                    return datasetLabel + ': ' + parseFloat(value).toFixed(2) + ' <?php echo $bot_api->getSettings()['base_currency']; ?>';
                                } else {
                                    return datasetLabel + ': ' + value;
                                }
                            }
                        }
                    },
                    legend: {
                        display: true
                    }
                }
            });
        });
    </script>
</body>
</html>