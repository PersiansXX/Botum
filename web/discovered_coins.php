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

// Sayfa başlığı
$page_title = 'Keşfedilen Potansiyel Coinler';
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
        :root {
            --primary-color: #2962ff;
            --secondary-color: #0d47a1;
            --success-color: #00c853;
            --danger-color: #f44336;
            --warning-color: #ffab00;
            --info-color: #00b0ff;
            --light-bg: #f5f7fa;
            --dark-bg: #263238;
            --border-radius: 8px;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
        }

        body {
            background-color: #f0f2f5;
            color: #333;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
            letter-spacing: 0.3px;
        }
        
        /* Card styling */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-image: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-bottom: none;
            padding: 1rem;
        }

        /* Keşif zaman etiketi için stil */
        .discovery-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            font-size: 0.7rem;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            z-index: 10;
        }

        /* Keşif kartlarının daha ilgi çekici görünmesi için stil */
        .discovery-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .discovery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .discovery-card .card-body {
            padding: 1.25rem;
        }
        
        .discovery-header {
            position: relative;
            padding: 0;
            overflow: hidden;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            height: 120px; /* Sabit yükseklik */
            background: linear-gradient(145deg, #2b5876, #4e4376);
        }
        
        .symbol-badge {
            position: absolute;
            bottom: 10px;
            left: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 10;
        }
        
        .price-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }
        
        .change-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
        }
        
        .change-positive {
            background-color: var(--success-color);
            color: white;
        }
        
        .change-negative {
            background-color: var(--danger-color);
            color: white;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .info-label {
            color: #666;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .footer-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        /* Görsel göstergeler */
        .signal-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .dot-buy { 
            background: linear-gradient(45deg, #00c853, #64dd17);
        }
        
        .dot-sell { 
            background: linear-gradient(45deg, #f44336, #ff1744);
        }
        
        .dot-neutral { 
            background: linear-gradient(45deg, #78909c, #607d8b);
        }
        
        .signal-text {
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .signal-buy {
            color: var(--success-color);
        }
        
        .signal-sell {
            color: var(--danger-color);
        }
        
        .signal-neutral {
            color: #78909c;
        }
        
        /* Volume analizi için stil */
        .volume-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .volume-high {
            background-color: rgba(0, 200, 83, 0.1);
            color: var(--success-color);
        }
        
        .volume-medium {
            background-color: rgba(255, 171, 0, 0.1);
            color: var(--warning-color);
        }
        
        .volume-low {
            background-color: rgba(120, 144, 156, 0.1);
            color: #78909c;
        }
        
        .progress-bar-success {
            background-color: var(--success-color);
        }
        
        .progress-bar-danger {
            background-color: var(--danger-color);
        }
        
        .progress-bar-warning {
            background-color: #ffab00;
        }
        
        /* Yükleniyor animasyonu */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(41, 98, 255, 0.1);
            border-radius: 50%;
            border-left-color: var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Filtre alanı */
        .filters {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .filter-label {
            font-weight: 600;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        /* Progress Bar */
        .signals-progress {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
            margin-bottom: 15px;
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
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-search-dollar"></i> <?php echo $page_title; ?></h5>
                        <div class="d-flex align-items-center">
                            <!-- Durum Göstergeleri -->
                            <div class="d-flex align-items-center mr-3">
                                <!-- Bot Durumu -->
                                <div class="d-flex align-items-center mr-3">
                                    <span class="status-icon <?php echo $bot_running ? 'bg-success' : 'bg-danger'; ?> mr-2" style="width: 12px; height: 12px;"></span>
                                    <span class="<?php echo $bot_running ? 'text-success' : 'text-danger'; ?>" style="font-size: 0.9rem; font-weight: 600;">
                                        <i class="fas fa-robot"></i> Bot
                                    </span>
                                </div>
                                
                                <!-- Veri Durumu -->
                                <div class="d-flex align-items-center">
                                    <span id="data-status-icon" class="status-icon bg-warning mr-2" style="width: 12px; height: 12px;"></span>
                                    <span id="data-status-text" class="text-warning" style="font-size: 0.9rem; font-weight: 600;">
                                        <i class="fas fa-database"></i> Veri
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Son Güncelleme -->
                            <div class="mr-3">
                                <span class="text-light" style="font-size: 0.8rem;"><i class="far fa-clock"></i> <span id="last-update-time"><?php echo date('H:i:s'); ?></span></span>
                            </div>
                            
                            <!-- Yenile Butonu -->
                            <button id="refresh-data" class="btn btn-sm btn-light">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Açıklama -->
                        <div class="alert alert-info mb-4" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Bu sayfada ne görüyorum?</strong>
                            <p class="mb-0">Bu sayfa, botun otomatik olarak keşfettiği potansiyel yüksek getirili coinleri göstermektedir. Bu coinler şu kriterlere göre seçilir:</p>
                            <ul class="mb-0 mt-2">
                                <li><strong>Hacim Artışı:</strong> Son 24 saatte yüksek hacim artışı gösteren coinler</li>
                                <li><strong>Fiyat Hareketi:</strong> Kısa sürede hızlı yükseliş gösteren coinler</li>
                                <li><strong>Teknik Analiz:</strong> Teknik indikatörlere göre alım sinyali veren coinler</li>
                            </ul>
                            <p class="mb-0 mt-2"><em>Not: Bu bilgiler sadece bilgilendirme amaçlıdır, yatırım tavsiyesi değildir.</em></p>
                        </div>
                        
                        <!-- Filtreler -->
                        <div class="filters mb-4">
                            <div class="d-flex flex-wrap">
                                <div class="filter-group">
                                    <span class="filter-label">Sinyal:</span>
                                    <select id="signal-filter" class="form-control form-control-sm">
                                        <option value="all">Tümü</option>
                                        <option value="BUY">Alım Sinyali</option>
                                        <option value="SELL">Satım Sinyali</option>
                                        <option value="NEUTRAL">Nötr</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <span class="filter-label">Fiyat Değişimi:</span>
                                    <select id="change-filter" class="form-control form-control-sm">
                                        <option value="all">Tümü</option>
                                        <option value="positive">Pozitif (%)</option>
                                        <option value="negative">Negatif (%)</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <span class="filter-label">İşlem Hacmi:</span>
                                    <select id="volume-filter" class="form-control form-control-sm">
                                        <option value="all">Tümü</option>
                                        <option value="high">Yüksek (>1M$)</option>
                                        <option value="medium">Orta (100K-1M$)</option>
                                        <option value="low">Düşük (<100K$)</option>
                                    </select>
                                </div>
                                
                                <div class="ml-auto">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                        <input type="text" id="coin-search" class="form-control" placeholder="Coin ara (örn: BTC, ETH...)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Keşfedilen Coinler -->
                        <div class="row" id="discovered-coins-container">
                            <!-- Coinler buraya dinamik olarak yüklenecek -->
                            <div class="col-12 loading">
                                <div class="loading-spinner"></div>
                            </div>
                        </div>
                        
                        <!-- Hata ayıklama bölümü -->
                        <div class="card mt-3" id="debug-card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">Hata Ayıklama Bilgileri</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <strong>API Yanıtı:</strong>
                                    <pre id="api-response" style="max-height: 200px; overflow: auto;"></pre>
                                </div>
                                <div class="btn-group">
                                    <button id="check-database" class="btn btn-warning btn-sm">
                                        <i class="fas fa-database"></i> Veritabanını Kontrol Et
                                    </button>
                                    <button id="list-all-tables" class="btn btn-info btn-sm">
                                        <i class="fas fa-table"></i> Tüm Tabloları Listele
                                    </button>
                                </div>
                                <div id="db-result" class="mt-3"></div>
                                <div id="tables-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Analiz Metodolojisi -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Coin Keşfetme Metodolojisi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="font-weight-bold">Hacim ve Fiyat Analizi</h6>
                                <p>Bot, piyasadaki binlerce coin arasından yüksek işlem hacmi veya önemli fiyat hareketliliği gösterenleri tespit eder. Bu, bir projenin görünürlüğünün ve ilginin arttığına işaret edebilir.</p>
                                
                                <h6 class="font-weight-bold">Ani Hacim Artışları</h6>
                                <p>Normalde düşük hacimli ancak aniden yüksek hacim artışı gösteren coinler potansiyel fırsatları işaret edebilir. Bot, bu tür anomalileri otomatik olarak tespit ederek izlemeye alır.</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="font-weight-bold">Teknik Analiz İndikatörleri</h6>
                                <p>Keşfedilen her coin için çoklu teknik indikatörler hesaplanır (RSI, MACD, Bollinger Bantları ve Hareketli Ortalamalar). Bu göstergeler bir araya gelerek alım sinyali veren coinleri tespit eder.</p>
                                
                                <h6 class="font-weight-bold">Otomatik İzlemeye Alma</h6>
                                <p>Potansiyeli yüksek coinler otomatik olarak izleme listesine eklenerek sürekli takip edilir ve fiyat değişimleri analiz edilir. Bu sayede erken fırsatları yakalama imkanı sunulur.</p>
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
                                    <h6 class="mb-0">Keşif Bilgileri</h6>
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
                                            <td><strong>İşlem Hacmi:</strong></td>
                                            <td id="detail-volume"></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Keşif Zamanı:</strong></td>
                                            <td id="detail-discovery-time"></td>
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
                    
                    <div class="mt-4">
                        <h6 class="font-weight-bold">Keşif Nedeni</h6>
                        <div id="detail-reason" class="alert alert-light"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="add-to-watchlist" class="btn btn-success">
                        <i class="far fa-star"></i> İzleme Listesine Ekle
                    </button>
                    <a id="detail-more-link" href="#" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Detaylı Analiz
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/tr.js"></script>
    
    <script>
        $(document).ready(function() {
            // Türkçe dil desteğini etkinleştir
            moment.locale('tr');
            
            // Tooltips'i etkinleştir
            $('[data-toggle="tooltip"]').tooltip();
            
            // Keşfedilen coinlerin saklanacağı değişkenler
            let discoveredCoins = [];
            let filteredCoins = [];
            
            // İlk yüklemede verileri al
            fetchDiscoveredCoins();
            
            // Periyodik olarak verileri güncelle (30 saniyede bir)
            setInterval(fetchDiscoveredCoins, 30000);
            
            // Yenile butonu işlemleri
            $("#refresh-data").click(function() {
                fetchDiscoveredCoins();
            });
            
            // Keşfedilen coinleri alma fonksiyonu
            function fetchDiscoveredCoins() {
                // Veri durum göstergesini güncelle
                updateDataStatus("loading");
                
                $.ajax({
                    url: 'api/get_discovered_coins.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success && response.data) {
                            discoveredCoins = response.data;
                            applyFilters(); // Filtreleri uygula ve göster
                            updateDataStatus("success");
                            
                            // Son güncelleme zamanını güncelle
                            $("#last-update-time").text(moment().format('HH:mm:ss'));
                            
                            // Hata ayıklama bilgilerini güncelle
                            $("#api-response").text(JSON.stringify(response, null, 2));
                            $("#debug-card").show();
                        } else {
                            updateDataStatus("error");
                            showError("Veriler alınamadı.");
                        }
                    },
                    error: function(xhr, status, error) {
                        updateDataStatus("error");
                        showError("API hatası: " + error);
                    }
                });
            }
            
            // Hata mesajı gösterme fonksiyonu
            function showError(message) {
                $("#discovered-coins-container").html(`
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> ${message}
                        </div>
                    </div>
                `);
            }
            
            // Filtreleme ve arama fonksiyonları
            $("#signal-filter, #change-filter, #volume-filter").change(function() {
                applyFilters();
            });
            
            $("#coin-search").on("keyup", function() {
                applyFilters();
            });
            
            // Filtreleri uygulama fonksiyonu
            function applyFilters() {
                const signalFilter = $("#signal-filter").val();
                const changeFilter = $("#change-filter").val();
                const volumeFilter = $("#volume-filter").val();
                const searchTerm = $("#coin-search").val().toLowerCase();
                
                // Tüm filtreleri uygula
                filteredCoins = discoveredCoins.filter(function(coin) {
                    // Sinyal filtresi
                    if (signalFilter !== "all" && coin.trade_signal !== signalFilter) {
                        return false;
                    }
                    
                    // Değişim filtresi
                    if (changeFilter === "positive" && coin.price_change_pct <= 0) {
                        return false;
                    } else if (changeFilter === "negative" && coin.price_change_pct >= 0) {
                        return false;
                    }
                    
                    // İşlem hacmi filtresi
                    if (volumeFilter === "high" && coin.volume_usd < 1000000) {
                        return false;
                    } else if (volumeFilter === "medium" && (coin.volume_usd < 100000 || coin.volume_usd >= 1000000)) {
                        return false;
                    } else if (volumeFilter === "low" && coin.volume_usd >= 100000) {
                        return false;
                    }
                    
                    // Arama filtresi
                    if (searchTerm && !coin.symbol.toLowerCase().includes(searchTerm)) {
                        return false;
                    }
                    
                    return true;
                });
                
                // Filtrelenmiş coinleri göster
                renderDiscoveredCoins(filteredCoins);
            }
            
            // Veri durum göstergesini güncelleyen fonksiyon
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
                    case "loading":
                        statusIcon.removeClass("bg-success bg-danger").addClass("bg-warning");
                        statusText.removeClass("text-success text-danger").addClass("text-warning");
                        statusText.html('<i class="fas fa-spinner fa-spin"></i> Veri');
                        break;
                }
            }
            
            // Keşfedilen coinleri gösterme fonksiyonu
            function renderDiscoveredCoins(coins) {
                if (!coins || !coins.length) {
                    $("#discovered-coins-container").html(`
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i> Henüz keşfedilen coin bulunamadı veya filtrelere uygun sonuç yok.
                            </div>
                        </div>
                    `);
                    return;
                }
                
                let html = '';
                
                // Her bir coin için kart oluştur
                coins.forEach(function(coin) {
                    // Sinyal rengi ve metni
                    let signalClass = 'signal-neutral';
                    let signalText = 'NÖTR';
                    let dotClass = 'dot-neutral';
                    
                    if (coin.trade_signal === 'BUY') {
                        signalClass = 'signal-buy';
                        signalText = 'AL';
                        dotClass = 'dot-buy';
                    } else if (coin.trade_signal === 'SELL') {
                        signalClass = 'signal-sell';
                        signalText = 'SAT';
                        dotClass = 'dot-sell';
                    }
                    
                    // Değişim rengi ve metni
                    let changeClass = coin.price_change_pct >= 0 ? 'change-positive' : 'change-negative';
                    let changeText = coin.price_change_pct >= 0 ? '+' + coin.price_change_pct.toFixed(2) + '%' : coin.price_change_pct.toFixed(2) + '%';
                    
                    // Hacim sınıfını belirle
                    let volumeClass = 'volume-low';
                    let volumeText = 'Düşük Hacim';
                    
                    if (coin.volume_usd >= 1000000) {
                        volumeClass = 'volume-high';
                        volumeText = 'Yüksek Hacim';
                    } else if (coin.volume_usd >= 100000) {
                        volumeClass = 'volume-medium';
                        volumeText = 'Orta Hacim';
                    }
                    
                    // Fiyat formatını belirle
                    let formattedPrice = formatPrice(coin.last_price);
                    
                    // Sinyal değerlerini hesapla
                    const buySignals = coin.buy_signals || 0;
                    const sellSignals = coin.sell_signals || 0;
                    const totalSignals = buySignals + sellSignals;
                    
                    // Yüzde oranlarını hesapla
                    const buyPercent = totalSignals > 0 ? Math.round((buySignals / totalSignals) * 100) : 0;
                    const sellPercent = totalSignals > 0 ? Math.round((sellSignals / totalSignals) * 100) : 0;
                    
                    // Keşif zamanını formatla
                    const discoveryTime = moment(coin.discovery_time).fromNow();
                    
                    html += `
                        <div class="col-md-3 mb-4">
                            <div class="card discovery-card">
                                <div class="discovery-header">
                                    <span class="symbol-badge">${coin.symbol}</span>
                                    <span class="price-badge">${formattedPrice}</span>
                                    <span class="change-badge ${changeClass}">${changeText}</span>
                                    <span class="discovery-badge">${discoveryTime} keşfedildi</span>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="signal-dot ${dotClass}"></span>
                                            <span class="signal-text ${signalClass}">${signalText}</span>
                                            
                                            ${(coin.trade_signal === 'BUY' || coin.trade_signal === 'SELL') ? 
                                            `<br><small class="text-muted mt-1">(${coin.timeframe || '5m'} · ${coin.trade_direction === 'SHORT' ? 'Short' : 'Long'})</small>` : ''}
                                        </div>
                                        <span class="volume-pill ${volumeClass}">${volumeText}</span>
                                    </div>
                                    
                                    <div class="signals-progress">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: ${buyPercent}%"></div>
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: ${sellPercent}%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-success">${buySignals} alım</small>
                                            <small class="text-danger">${sellSignals} satım</small>
                                        </div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">İşlem Hacmi:</span>
                                        <span class="info-value">$${formatVolume(coin.volume_usd)}</span>
                                    </div>
                                    
                                    <div class="footer-actions">
                                        <button class="btn btn-sm btn-primary coin-detail-btn" data-coin='${JSON.stringify(coin)}'>
                                            <i class="fas fa-chart-line"></i> Detay
                                        </button>
                                        <button class="btn btn-sm btn-success add-to-active-btn" data-symbol="${coin.symbol}">
                                            <i class="fas fa-plus-circle"></i> Aktif Listeye Ekle
                                        </button>
                                        <a href="coin_detail.php?symbol=${encodeURIComponent(coin.symbol)}" class="btn btn-sm btn-info">
                                            <i class="fas fa-search-plus"></i> Analiz
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                $("#discovered-coins-container").html(html);
                
                // Coin detay butonlarını etkinleştir
                $(".coin-detail-btn").click(function() {
                    const coinData = $(this).data('coin');
                    showCoinDetailModal(coinData);
                });
            }
            
            // Hacim formatı fonksiyonu
            function formatVolume(volume) {
                if (volume >= 1000000) {
                    return (volume / 1000000).toFixed(2) + 'M';
                } else if (volume >= 1000) {
                    return (volume / 1000).toFixed(2) + 'K';
                } else {
                    return volume.toFixed(2);
                }
            }
            
            // Fiyat formatı fonksiyonu
            function formatPrice(price) {
                if (!price && price !== 0) return '--';
                
                if (price < 0.00001) {
                    return price.toExponential(4);
                } else if (price < 0.001) {
                    return price.toFixed(8);
                } else if (price < 0.1) {
                    return price.toFixed(6); 
                } else if (price < 1) {
                    return price.toFixed(4);
                } else if (price < 100) {
                    return price.toFixed(2);
                } else {
                    return Math.round(price).toLocaleString('en-US');
                }
            }
            
            // Coin detay modalını gösterme fonksiyonu
            function showCoinDetailModal(coin) {
                // Temel bilgileri doldur
                $("#detail-symbol").text(coin.symbol);
                $("#detail-price").text(formatPrice(coin.last_price));
                
                // Değişim
                const changeClass = coin.price_change_pct >= 0 ? 'text-success' : 'text-danger';
                const changeIcon = coin.price_change_pct >= 0 ? '<i class="fas fa-caret-up"></i>' : '<i class="fas fa-caret-down"></i>';
                $("#detail-change").html(`<span class="${changeClass}">${changeIcon} ${Math.abs(coin.price_change_pct).toFixed(2)}%</span>`);
                
                // Hacim
                $("#detail-volume").text('$' + formatVolume(coin.volume_usd));
                
                // Keşif zamanı
                const discoveryTimeFormatted = moment(coin.discovery_time).format('DD MMMM YYYY, HH:mm:ss');
                $("#detail-discovery-time").text(discoveryTimeFormatted);
                
                // İndikatörleri doldur
                let indicatorsHtml = '';
                
                // RSI
                if (coin.indicators && coin.indicators.rsi) {
                    const rsi = coin.indicators.rsi.value;
                    let rsiClass = '';
                    
                    if (rsi <= 30) rsiClass = 'text-success';
                    else if (rsi >= 70) rsiClass = 'text-danger';
                    
                    indicatorsHtml += `
                        <div class="d-flex justify-content-between mb-2">
                            <span>RSI:</span>
                            <span class="${rsiClass}">${rsi.toFixed(2)}</span>
                        </div>
                    `;
                }
                
                // MACD
                if (coin.indicators && coin.indicators.macd) {
                    const macdValue = coin.indicators.macd.value;
                    const macdClass = macdValue >= 0 ? 'text-success' : 'text-danger';
                    
                    indicatorsHtml += `
                        <div class="d-flex justify-content-between mb-2">
                            <span>MACD:</span>
                            <span class="${macdClass}">${macdValue.toFixed(6)}</span>
                        </div>
                    `;
                }
                
                // Bollinger Bands
                if (coin.indicators && coin.indicators.bollinger) {
                    const bb = coin.indicators.bollinger;
                    indicatorsHtml += `
                        <div class="d-flex justify-content-between mb-2">
                            <span>Bollinger Üst:</span>
                            <span>${bb.upper.toFixed(6)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Bollinger Orta:</span>
                            <span>${bb.middle.toFixed(6)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Bollinger Alt:</span>
                            <span>${bb.lower.toFixed(6)}</span>
                        </div>
                    `;
                }
                
                // Hareketli Ortalamalar
                if (coin.indicators && coin.indicators.moving_averages) {
                    const ma = coin.indicators.moving_averages;
                    indicatorsHtml += `
                        <div class="d-flex justify-content-between mb-2">
                            <span>MA20:</span>
                            <span>${ma.ma20.toFixed(6)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>MA50:</span>
                            <span>${ma.ma50.toFixed(6)}</span>
                        </div>
                    `;
                }
                
                $("#indicators-container").html(indicatorsHtml || '<div class="text-muted">İndikatör verisi bulunamadı.</div>');
                
                // Keşif nedeni
                let reasonHtml = '';
                
                if (coin.note) {
                    reasonHtml += `<p>${coin.note}</p>`;
                }
                
                reasonHtml += `
                    <p><strong>Fiyat Değişimi:</strong> ${coin.price_change_pct >= 0 ? '+' : ''}${coin.price_change_pct.toFixed(2)}% (Son 24 saat)</p>
                    <p><strong>İşlem Hacmi:</strong> $${formatVolume(coin.volume_usd)}</p>
                    
                    <p><strong>Sinyal Dağılımı:</strong> ${coin.buy_signals || 0} alım sinyali, ${coin.sell_signals || 0} satım sinyali</p>
                    
                    <p><em>Bu coin, botun otomatik keşif algoritması tarafından tespit edilmiş ve potansiyel fırsatlar listesine eklenmiştir.</em></p>
                `;
                
                $("#detail-reason").html(reasonHtml);
                
                // İzleme listesine ekle butonu
                $("#add-to-watchlist").data('symbol', coin.symbol);
                
                // Detay linki
                $("#detail-more-link").attr('href', `coin_detail.php?symbol=${encodeURIComponent(coin.symbol)}`);
                
                // Modalı göster
                $("#coinDetailModal").modal('show');
            }
            
            // İzleme listesine ekleme fonksiyonu
            $("#add-to-watchlist").click(function() {
                const symbol = $(this).data('symbol');
                
                if (!symbol) return;
                
                $.ajax({
                    url: 'api/bot_api.php',
                    type: 'POST',
                    data: {
                        action: 'add_to_watchlist',
                        symbol: symbol
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            alert('Coin başarıyla izleme listesine eklendi!');
                        } else {
                            alert('Coin izleme listesine eklenirken bir hata oluştu: ' + (response.message || 'Bilinmeyen hata'));
                        }
                    },
                    error: function() {
                        alert('İstek gönderilirken bir hata oluştu.');
                    }
                });
            });
            
            // Aktif listeye coin ekleme fonksiyonu
            $(document).on("click", ".add-to-active-btn", function() {
                const symbol = $(this).data('symbol');
                const button = $(this);
                
                if (!symbol) return;
                
                // Butonun durumunu güncelle
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ekleniyor...');
                
                $.ajax({
                    url: 'api/add_to_active_coins.php',
                    type: 'POST',
                    data: {
                        symbol: symbol
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            // Başarılı ekleme durumunda butonun görünümünü güncelle
                            button.removeClass('btn-success').addClass('btn-secondary')
                                .html('<i class="fas fa-check"></i> Eklendi')
                                .prop('disabled', true);
                            
                            // Başarılı bildirim göster
                            alert('Coin başarıyla aktif listeye eklendi: ' + symbol);
                        } else {
                            // Hata durumunda butonu eski haline getir
                            button.prop('disabled', false)
                                .html('<i class="fas fa-plus-circle"></i> Aktif Listeye Ekle');
                            
                            // Hata mesajı göster
                            alert('Aktif listeye eklenirken bir hata oluştu: ' + (response && response.message ? response.message : 'Bilinmeyen hata'));
                        }
                    },
                    error: function(xhr, status, error) {
                        // Bağlantı hatası durumunda butonu eski haline getir
                        button.prop('disabled', false)
                            .html('<i class="fas fa-plus-circle"></i> Aktif Listeye Ekle');
                        
                        // Hata mesajı göster
                        alert('API bağlantı hatası: ' + error);
                    }
                });
            });
            
            // Veritabanını kontrol etme fonksiyonu
            $("#check-database").click(function() {
                $("#db-result").html('<div class="alert alert-info">Veritabanı kontrol ediliyor...</div>');
                
                $.ajax({
                    url: 'api/db_check_coins.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            let html = '<div class="alert alert-success">';
                            html += '<h6>Veritabanı Kontrol Sonuçları:</h6>';
                            html += '<ul>';
                            if (response.has_table) {
                                html += '<li><i class="fas fa-check text-success"></i> discovered_coins tablosu mevcut.</li>';
                            } else {
                                html += '<li><i class="fas fa-times text-danger"></i> discovered_coins tablosu mevcut değil!</li>';
                            }
                            
                            if (response.row_count > 0) {
                                html += `<li><i class="fas fa-check text-success"></i> Tabloda ${response.row_count} kayıt mevcut.</li>`;
                            } else {
                                html += '<li><i class="fas fa-times text-danger"></i> Tabloda hiç kayıt yok!</li>';
                            }
                            
                            html += '</ul>';
                            
                            if (response.sample_data && response.sample_data.length > 0) {
                                html += '<p>Örnek kayıtlar:</p>';
                                html += '<pre style="max-height: 150px; overflow: auto;">';
                                html += JSON.stringify(response.sample_data, null, 2);
                                html += '</pre>';
                            }
                            
                            html += '</div>';
                            $("#db-result").html(html);
                        } else {
                            $("#db-result").html('<div class="alert alert-danger">Veritabanı kontrol edilirken bir hata oluştu: ' + (response.message || 'Bilinmeyen hata') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $("#db-result").html('<div class="alert alert-danger">API hatası: ' + error + '</div>');
                    }
                });
            });
            
            // Tüm tabloları listeleme fonksiyonu
            $("#list-all-tables").click(function() {
                $("#tables-result").html('<div class="alert alert-info">Tüm tablolar listeleniyor...</div>');
                
                $.ajax({
                    url: 'api/list_all_tables.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            let html = '<div class="alert alert-info">';
                            html += '<h6>Veritabanında Bulunan Tüm Tablolar:</h6>';
                            html += '<ul>';
                            
                            if (response.all_tables && response.all_tables.length > 0) {
                                response.all_tables.forEach(function(table) {
                                    html += `<li>${table}</li>`;
                                });
                            } else {
                                html += '<li>Hiç tablo bulunamadı.</li>';
                            }
                            
                            html += '</ul>';
                            
                            // Price Analysis tablosu hakkında bilgiler
                            if (response.price_analysis_info) {
                                html += '<h6 class="mt-4">price_analysis Tablosu Bilgileri:</h6>';
                                html += `<p>Toplam Kayıt Sayısı: <strong>${response.price_analysis_info.row_count}</strong></p>`;
                                
                                if (response.price_analysis_info.structure && response.price_analysis_info.structure.length > 0) {
                                    html += '<p>Tablo Yapısı:</p>';
                                    html += '<table class="table table-sm table-bordered">';
                                    html += '<thead><tr><th>Alan</th><th>Tip</th><th>Null</th><th>Anahtar</th></tr></thead>';
                                    html += '<tbody>';
                                    
                                    response.price_analysis_info.structure.forEach(function(field) {
                                        html += `<tr>
                                            <td>${field.Field}</td>
                                            <td>${field.Type}</td>
                                            <td>${field.Null}</td>
                                            <td>${field.Key || '-'}</td>
                                        </tr>`;
                                    });
                                    
                                    html += '</tbody></table>';
                                }
                                
                                if (response.price_analysis_info.sample_data && response.price_analysis_info.sample_data.length > 0) {
                                    html += '<p>Örnek Kayıtlar:</p>';
                                    html += '<pre style="max-height: 200px; overflow: auto;">';
                                    html += JSON.stringify(response.price_analysis_info.sample_data, null, 2);
                                    html += '</pre>';
                                }
                            }
                            
                            // Potansiyel coin tabloları hakkında bilgiler
                            if (response.potential_coin_tables && response.potential_coin_tables.length > 0) {
                                html += '<h6 class="mt-4">Potansiyel Coin Tabloları:</h6>';
                                
                                response.potential_coin_tables.forEach(function(table) {
                                    html += `<div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">${table.table_name} (${table.row_count} kayıt)</h6>
                                        </div>`;
                                    
                                    if (table.sample_data && table.sample_data.length > 0) {
                                        html += '<div class="card-body">';
                                        html += '<p>Örnek Kayıtlar:</p>';
                                        html += '<pre style="max-height: 150px; overflow: auto;">';
                                        html += JSON.stringify(table.sample_data, null, 2);
                                        html += '</pre>';
                                        html += '</div>';
                                    } else {
                                        html += '<div class="card-body">Bu tabloda kayıt bulunamadı.</div>';
                                    }
                                    
                                    html += '</div>';
                                });
                            }
                            
                            html += '</div>';
                            $("#tables-result").html(html);
                        } else {
                            $("#tables-result").html('<div class="alert alert-danger">Tablolar listelenirken bir hata oluştu: ' + (response.message || 'Bilinmeyen hata') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $("#tables-result").html('<div class="alert alert-danger">API hatası: ' + error + '</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>