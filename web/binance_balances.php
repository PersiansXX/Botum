<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API bilgilerini al
require_once "check_bot_settings_structure.php";

// Sayfa başlığı
$page_title = 'Binance Bakiyeleri';

// İstek tipi - varsayılan olarak spot
$account_type = isset($_GET['type']) ? $_GET['type'] : 'spot';
$valid_types = ['spot', 'margin', 'isolated', 'futures', 'all'];
if (!in_array($account_type, $valid_types)) {
    $account_type = 'spot';
}
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
        .tab-content {
            margin-top: 20px;
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        .currency-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
        .balance-card {
            transition: all 0.3s ease;
        }
        .balance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        #errorMessage {
            display: none;
        }
        .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-bottom-color: #f8f9fa;
        }
        .tab-pane {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 15px;
            border-radius: 0 0 .25rem .25rem;
        }
        .total-value {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <?php 
    // Ana menüyü dahil et (eğer varsa)
    if (file_exists('includes/navbar.php')) {
        include 'includes/navbar.php';
    }
    ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sol Menü (eğer varsa) -->
            <?php if (file_exists('includes/sidebar.php')): ?>
            <div class="col-md-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            <div class="col-md-10">
            <?php else: ?>
            <div class="col-md-12">
            <?php endif; ?>
                <!-- Ana İçerik -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-wallet"></i> Binance Bakiyeleri
                        </h6>
                        <div>
                            <button id="refreshBalances" class="btn btn-sm btn-primary">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                            <a href="index.php" class="btn btn-sm btn-secondary ml-2">
                                <i class="fas fa-home"></i> Ana Sayfa
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Hata mesajı -->
                        <div id="errorMessage" class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <span id="errorText"></span>
                        </div>
                        
                        <!-- Hesap Tipleri Tabları -->
                        <ul class="nav nav-tabs" id="accountTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?= ($account_type == 'spot') ? 'active' : '' ?>" id="spot-tab" data-toggle="tab" href="#spot" role="tab">
                                    <i class="fas fa-coins"></i> Spot
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($account_type == 'margin') ? 'active' : '' ?>" id="margin-tab" data-toggle="tab" href="#margin" role="tab">
                                    <i class="fas fa-chart-line"></i> Cross Margin
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($account_type == 'isolated') ? 'active' : '' ?>" id="isolated-tab" data-toggle="tab" href="#isolated" role="tab">
                                    <i class="fas fa-layer-group"></i> Isolated Margin
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($account_type == 'futures') ? 'active' : '' ?>" id="futures-tab" data-toggle="tab" href="#futures" role="tab">
                                    <i class="fas fa-rocket"></i> Futures
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($account_type == 'all') ? 'active' : '' ?>" id="all-tab" data-toggle="tab" href="#all" role="tab">
                                    <i class="fas fa-wallet"></i> Tüm Bakiyeler
                                </a>
                            </li>
                        </ul>
                        
                        <!-- Tab içerikleri -->
                        <div class="tab-content" id="accountTabContent">
                            <!-- Spot Hesap -->
                            <div class="tab-pane fade <?= ($account_type == 'spot') ? 'show active' : '' ?>" id="spot" role="tabpanel">
                                <div class="loading spot-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Yükleniyor...</span>
                                    </div>
                                </div>
                                <div class="spot-content" style="display: none;">
                                    <!-- AJAX ile yüklenecek -->
                                </div>
                            </div>
                            
                            <!-- Cross Margin -->
                            <div class="tab-pane fade <?= ($account_type == 'margin') ? 'show active' : '' ?>" id="margin" role="tabpanel">
                                <div class="loading margin-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Yükleniyor...</span>
                                    </div>
                                </div>
                                <div class="margin-content" style="display: none;">
                                    <!-- AJAX ile yüklenecek -->
                                </div>
                            </div>
                            
                            <!-- Isolated Margin -->
                            <div class="tab-pane fade <?= ($account_type == 'isolated') ? 'show active' : '' ?>" id="isolated" role="tabpanel">
                                <div class="loading isolated-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Yükleniyor...</span>
                                    </div>
                                </div>
                                <div class="isolated-content" style="display: none;">
                                    <!-- AJAX ile yüklenecek -->
                                </div>
                            </div>
                            
                            <!-- Futures -->
                            <div class="tab-pane fade <?= ($account_type == 'futures') ? 'show active' : '' ?>" id="futures" role="tabpanel">
                                <div class="loading futures-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Yükleniyor...</span>
                                    </div>
                                </div>
                                <div class="futures-content" style="display: none;">
                                    <!-- AJAX ile yüklenecek -->
                                </div>
                            </div>
                            
                            <!-- Tüm Bakiyeler -->
                            <div class="tab-pane fade <?= ($account_type == 'all') ? 'show active' : '' ?>" id="all" role="tabpanel">
                                <div class="loading all-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Yükleniyor...</span>
                                    </div>
                                </div>
                                <div class="all-content" style="display: none;">
                                    <!-- AJAX ile yüklenecek -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Özet Bilgiler -->
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Bakiye Özeti</h6>
                            </div>
                            <div class="card-body" id="balanceSummary">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body py-2 px-3">
                                                <h6 class="card-title mb-1">Spot Toplam</h6>
                                                <p class="card-text total-value" id="spotTotal">Yükleniyor...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body py-2 px-3">
                                                <h6 class="card-title mb-1">Margin Toplam</h6>
                                                <p class="card-text total-value" id="marginTotal">Yükleniyor...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body py-2 px-3">
                                                <h6 class="card-title mb-1">Futures Toplam</h6>
                                                <p class="card-text total-value" id="futuresTotal">Yükleniyor...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body py-2 px-3">
                                                <h6 class="card-title mb-1">Toplam Değer</h6>
                                                <p class="card-text" id="grandTotal">Yükleniyor...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-muted small mt-2">Son güncelleme: <span id="lastUpdateTime"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sayfa yüklendiğinde
        $(document).ready(function() {
            // Aktif tabı yükle
            loadActiveTab();
            
            // Tab değiştiğinde
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var tabId = $(e.target).attr('href').substring(1);
                loadTabContent(tabId);
                
                // URL'yi güncelle
                history.pushState(null, null, 'binance_balances.php?type=' + tabId);
            });
            
            // Yenile butonu
            $('#refreshBalances').click(function() {
                $(this).prop('disabled', true);
                $(this).html('<i class="fas fa-sync-alt fa-spin"></i> Yenileniyor...');
                
                // Aktif tabı yeniden yükle
                var activeTabId = $('.nav-link.active').attr('href').substring(1);
                loadTabContent(activeTabId, true);
                
                // Özet bilgileri yeniden yükle
                loadSummary(true);
                
                setTimeout(() => {
                    $(this).prop('disabled', false);
                    $(this).html('<i class="fas fa-sync-alt"></i> Yenile');
                }, 2000);
            });
        });
        
        // Aktif tabı yükle
        function loadActiveTab() {
            var activeTabId = $('.nav-link.active').attr('href').substring(1);
            loadTabContent(activeTabId);
            loadSummary();
        }
        
        // Tab içeriğini yükle
        function loadTabContent(tabId, forceRefresh = false) {
            var $loading = $('.' + tabId + '-loading');
            var $content = $('.' + tabId + '-content');
            
            // Eğer içerik zaten yüklendiyse ve forceRefresh false ise göster
            if ($content.html().trim() !== '' && !forceRefresh) {
                $loading.hide();
                $content.show();
                return;
            }
            
            // İçerik yüklenmemişse veya yenileme isteniyorsa
            $loading.show();
            $content.hide();
            
            $.ajax({
                url: 'get_binance_balances_ajax.php',
                method: 'GET',
                data: { type: tabId, nocache: new Date().getTime() },
                dataType: 'json',
                success: function(response) {
                    $loading.hide();
                    
                    if (response.success) {
                        renderBalances(tabId, response);
                        $('#errorMessage').hide();
                    } else {
                        $('#errorText').text(response.message);
                        $('#errorMessage').show();
                    }
                },
                error: function(xhr, status, error) {
                    $loading.hide();
                    $('#errorText').text('Bakiyeler yüklenirken hata oluştu: ' + error);
                    $('#errorMessage').show();
                }
            });
        }
        
        // Özet bilgileri yükle
        function loadSummary(forceRefresh = false) {
            $.ajax({
                url: 'get_binance_balances_ajax.php',
                method: 'GET',
                data: { type: 'summary', nocache: new Date().getTime() },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#spotTotal').text(formatCurrency(response.summary.spot_total) + ' USDT');
                        $('#marginTotal').text(formatCurrency(response.summary.margin_total) + ' USDT');
                        $('#futuresTotal').text(formatCurrency(response.summary.futures_total) + ' USDT');
                        $('#grandTotal').text(formatCurrency(response.summary.grand_total) + ' USDT');
                        $('#lastUpdateTime').text(new Date(response.timestamp * 1000).toLocaleString());
                    }
                }
            });
        }
        
        // Bakiyeleri görüntüle
        function renderBalances(tabId, data) {
            var $content = $('.' + tabId + '-content');
            var html = '';
            
            if (tabId === 'spot') {
                // Spot bakiyelerini görüntüle
                html = renderSpotBalances(data.balances);
            } else if (tabId === 'margin') {
                // Margin bakiyelerini görüntüle
                html = renderMarginBalances(data.balances);
            } else if (tabId === 'isolated') {
                // Isolated margin bakiyelerini görüntüle
                html = renderIsolatedBalances(data.balances);
            } else if (tabId === 'futures') {
                // Futures bakiyelerini görüntüle
                html = renderFuturesBalances(data.balances);
            } else if (tabId === 'all') {
                // Tüm bakiyeleri görüntüle
                html = renderAllBalances(data);
            }
            
            $content.html(html);
            $content.show();
            
            // Filtreleme ve arama
            setupFiltering(tabId);
        }
        
        // Spot bakiyelerini görüntüle
        function renderSpotBalances(balances) {
            if (!balances || balances.length === 0) {
                return '<div class="alert alert-info">Spot hesabınızda bakiye bulunamadı.</div>';
            }
            
            let html = `
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Coin ara..." id="spotSearch">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="spotTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Coin</th>
                                <th>Miktar</th>
                                <th>Kullanılabilir</th>
                                <th>Kilitli</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            balances.forEach(function(balance) {
                html += `
                    <tr>
                        <td><strong>${balance.asset}</strong></td>
                        <td>${formatNumber(balance.total)}</td>
                        <td>${formatNumber(balance.free)}</td>
                        <td>${formatNumber(balance.locked)}</td>
                    </tr>`;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="text-muted">Toplam ${balances.length} farklı coin bulundu.</div>`;
            
            return html;
        }
        
        // Margin bakiyelerini görüntüle
        function renderMarginBalances(balances) {
            if (!balances || balances.length === 0) {
                return '<div class="alert alert-info">Cross margin hesabınızda bakiye bulunamadı.</div>';
            }
            
            let html = `
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Coin ara..." id="marginSearch">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="marginTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Coin</th>
                                <th>Miktar</th>
                                <th>Kullanılabilir</th>
                                <th>Kilitli</th>
                                <th>Borç</th>
                                <th>Faiz</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            balances.forEach(function(balance) {
                html += `
                    <tr>
                        <td><strong>${balance.asset}</strong></td>
                        <td>${formatNumber(balance.total)}</td>
                        <td>${formatNumber(balance.free)}</td>
                        <td>${formatNumber(balance.locked)}</td>
                        <td>${formatNumber(balance.borrowed || 0)}</td>
                        <td>${formatNumber(balance.interest || 0)}</td>
                    </tr>`;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="text-muted">Toplam ${balances.length} farklı coin bulundu.</div>`;
            
            return html;
        }
        
        // Isolated Margin bakiyelerini görüntüle
        function renderIsolatedBalances(balances) {
            if (!balances || balances.length === 0) {
                return '<div class="alert alert-info">Isolated margin hesabınızda bakiye bulunamadı.</div>';
            }
            
            let html = `
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Coin veya çift ara..." id="isolatedSearch">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="isolatedTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Coin</th>
                                <th>Trading Pair</th>
                                <th>Miktar</th>
                                <th>Kullanılabilir</th>
                                <th>Kilitli</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            balances.forEach(function(balance) {
                html += `
                    <tr>
                        <td><strong>${balance.asset}</strong></td>
                        <td>${balance.pair || '-'}</td>
                        <td>${formatNumber(balance.total)}</td>
                        <td>${formatNumber(balance.free)}</td>
                        <td>${formatNumber(balance.locked)}</td>
                    </tr>`;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="text-muted">Toplam ${balances.length} farklı coin bulundu.</div>`;
            
            return html;
        }
        
        // Futures bakiyelerini görüntüle
        function renderFuturesBalances(balances) {
            if (!balances || balances.length === 0) {
                return '<div class="alert alert-info">Futures hesabınızda bakiye bulunamadı.</div>';
            }
            
            let html = `
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Coin ara..." id="futuresSearch">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="futuresTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Coin</th>
                                <th>Toplam</th>
                                <th>Kullanılabilir</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            balances.forEach(function(balance) {
                html += `
                    <tr>
                        <td><strong>${balance.asset}</strong></td>
                        <td>${formatNumber(balance.balance)}</td>
                        <td>${formatNumber(balance.availableBalance || balance.balance)}</td>
                    </tr>`;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="text-muted">Toplam ${balances.length} farklı coin bulundu.</div>`;
            
            return html;
        }
        
        // Tüm bakiyeleri görüntüle
        function renderAllBalances(data) {
            let html = `
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Tüm coinlerde ara..." id="allSearch">
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Bu sayfada tüm hesaplarınızdaki bakiyelerin özeti gösterilmektedir.
                </div>
                
                <div class="row">`;
                
            // Hesap türlerine göre sayaclar
            let accountCounts = {
                spot: data.balances_spot ? data.balances_spot.length : 0,
                margin: data.balances_margin ? data.balances_margin.length : 0,
                isolated: data.balances_isolated ? data.balances_isolated.length : 0,
                futures: data.balances_futures ? data.balances_futures.length : 0
            };
            
            // Tüm coinleri birleştir ve asset'e göre grupla
            let allCoins = {};
            
            // Spot coinleri
            if (data.balances_spot) {
                data.balances_spot.forEach(function(balance) {
                    if (!allCoins[balance.asset]) {
                        allCoins[balance.asset] = {
                            spot: balance.total,
                            margin: 0,
                            isolated: 0,
                            futures: 0,
                            total: balance.total
                        };
                    } else {
                        allCoins[balance.asset].spot = balance.total;
                        allCoins[balance.asset].total += balance.total;
                    }
                });
            }
            
            // Margin coinleri
            if (data.balances_margin) {
                data.balances_margin.forEach(function(balance) {
                    if (!allCoins[balance.asset]) {
                        allCoins[balance.asset] = {
                            spot: 0,
                            margin: balance.total,
                            isolated: 0,
                            futures: 0,
                            total: balance.total
                        };
                    } else {
                        allCoins[balance.asset].margin = balance.total;
                        allCoins[balance.asset].total += balance.total;
                    }
                });
            }
            
            // Isolated margin coinleri
            if (data.balances_isolated) {
                data.balances_isolated.forEach(function(balance) {
                    if (!allCoins[balance.asset]) {
                        allCoins[balance.asset] = {
                            spot: 0,
                            margin: 0,
                            isolated: balance.total,
                            futures: 0,
                            total: balance.total
                        };
                    } else {
                        allCoins[balance.asset].isolated = balance.total;
                        allCoins[balance.asset].total += balance.total;
                    }
                });
            }
            
            // Futures coinleri
            if (data.balances_futures) {
                data.balances_futures.forEach(function(balance) {
                    if (!allCoins[balance.asset]) {
                        allCoins[balance.asset] = {
                            spot: 0,
                            margin: 0,
                            isolated: 0,
                            futures: balance.balance,
                            total: balance.balance
                        };
                    } else {
                        allCoins[balance.asset].futures = balance.balance;
                        allCoins[balance.asset].total += parseFloat(balance.balance);
                    }
                });
            }
            
            // Tablo olarak göster
            html += `
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="allCoinsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Coin</th>
                                    <th>Spot</th>
                                    <th>Margin</th>
                                    <th>Isolated</th>
                                    <th>Futures</th>
                                    <th>Toplam</th>
                                </tr>
                            </thead>
                            <tbody>`;
            
            // Toplam bakiyeye göre sırala
            let sortedCoins = Object.keys(allCoins).sort(function(a, b) {
                return allCoins[b].total - allCoins[a].total;
            });
            
            sortedCoins.forEach(function(coin) {
                let coinData = allCoins[coin];
                html += `
                    <tr data-asset="${coin}">
                        <td><strong>${coin}</strong></td>
                        <td>${formatNumber(coinData.spot)}</td>
                        <td>${formatNumber(coinData.margin)}</td>
                        <td>${formatNumber(coinData.isolated)}</td>
                        <td>${formatNumber(coinData.futures)}</td>
                        <td><strong>${formatNumber(coinData.total)}</strong></td>
                    </tr>`;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>`;
            
            html += `
                </div>
                <div class="text-muted">Toplam ${sortedCoins.length} farklı coin bulundu (Spot: ${accountCounts.spot}, Margin: ${accountCounts.margin}, Isolated: ${accountCounts.isolated}, Futures: ${accountCounts.futures}).</div>`;
            
            return html;
        }
        
        // Filtreleme kurulumu
        function setupFiltering(tabId) {
            if (tabId === 'spot') {
                $('#spotSearch').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $("#spotTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            } else if (tabId === 'margin') {
                $('#marginSearch').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $("#marginTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            } else if (tabId === 'isolated') {
                $('#isolatedSearch').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $("#isolatedTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            } else if (tabId === 'futures') {
                $('#futuresSearch').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $("#futuresTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            } else if (tabId === 'all') {
                $('#allSearch').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $("#allCoinsTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            }
        }
        
        // Sayı formatla
        function formatNumber(number) {
            if (number === undefined || number === null) return '0.00000000';
            
            let num = parseFloat(number);
            if (num === 0) return '0.00000000';
            if (num < 0.00000001) return '< 0.00000001';
            
            if (num < 0.0001) {
                return num.toFixed(8);
            } else if (num < 1) {
                return num.toFixed(6);
            } else if (num < 1000) {
                return num.toFixed(4);
            } else {
                return num.toFixed(2);
            }
        }
        
        // Para birimi formatla
        function formatCurrency(number) {
            if (number === undefined || number === null) return '0.00';
            
            let num = parseFloat(number);
            if (isNaN(num)) return '0.00';
            
            return new Intl.NumberFormat('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        }
    </script>
</body>
</html>
