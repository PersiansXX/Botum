<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

// Sayfa başlığı
$page_title = 'Binance Fiyatları';

// İzlenmek istenen coinler
$default_coins = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'ADAUSDT', 'DOGEUSDT', 'XRPUSDT', 'DOTUSDT', 'MATICUSDT', 'LINKUSDT'];
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
        /* Gerçek zamanlı fiyat değişimleri için animasyonlar */
        @keyframes priceUp {
            0% { background-color: rgba(40, 167, 69, 0); color: inherit; }
            50% { background-color: rgba(40, 167, 69, 0.3); color: #28a745; }
            100% { background-color: rgba(40, 167, 69, 0); color: inherit; }
        }
        
        @keyframes priceDown {
            0% { background-color: rgba(220, 53, 69, 0); color: inherit; }
            50% { background-color: rgba(220, 53, 69, 0.3); color: #dc3545; }
            100% { background-color: rgba(220, 53, 69, 0); color: inherit; }
        }
        
        .price-up {
            animation: priceUp 1s ease;
            color: #28a745;
            font-weight: bold;
        }
        
        .price-down {
            animation: priceDown 1s ease;
            color: #dc3545;
            font-weight: bold;
        }
        
        /* Tablo stillendirme */
        .coin-table thead th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }
        
        .coin-row:hover {
            background-color: #f8f9fa;
        }
        
        /* Yükleme göstergesi */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        #new-coin-form {
            margin-bottom: 20px;
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
                        <h5 class="m-0">
                            <i class="fas fa-chart-line mr-2"></i> Canlı Binance Fiyatları
                        </h5>
                        <div class="d-flex align-items-center">
                            <span id="connection-status" class="badge badge-success mr-2">
                                <i class="fas fa-wifi"></i> Bağlı
                            </span>
                            <span class="mr-2">
                                Son Güncelleme: <span id="last-update-time"><?php echo date('H:i:s'); ?></span>
                            </span>
                            <button id="refresh-data" class="btn btn-sm btn-light">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body p-0 position-relative">
                        <!-- Yükleme göstergesi -->
                        <div id="loading-overlay" class="loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Yükleniyor...</span>
                            </div>
                        </div>
                        
                        <!-- Coin ekleme formu -->
                        <div class="p-3 border-bottom">
                            <form id="new-coin-form" class="form-inline">
                                <div class="form-group mb-2">
                                    <label for="new-coin-symbol" class="mr-2">Yeni Coin Ekle:</label>
                                    <input type="text" class="form-control form-control-sm mr-2" id="new-coin-symbol" 
                                           placeholder="BTCUSDT" maxlength="20" pattern="[A-Za-z0-9]+" required>
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-plus"></i> Ekle
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Fiyat tablosu -->
                        <div class="table-responsive">
                            <table id="coin-table" class="table table-striped table-sm mb-0 coin-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Sembol</th>
                                        <th>Fiyat</th>
                                        <th>24s Değişim</th>
                                        <th>24s Yüksek</th>
                                        <th>24s Düşük</th>
                                        <th>24s Hacim (USDT)</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($default_coins as $coin) : ?>
                                    <tr class="coin-row" data-symbol="<?php echo $coin; ?>">
                                        <td><strong><?php echo $coin; ?></strong></td>
                                        <td class="price-cell">--</td>
                                        <td class="change-cell">--</td>
                                        <td class="high-cell">--</td>
                                        <td class="low-cell">--</td>
                                        <td class="volume-cell">--</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger remove-coin" data-symbol="<?php echo $coin; ?>" title="Listeden kaldır">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card-footer text-muted small">
                        <i class="fas fa-info-circle"></i> Fiyatlar Binance API üzerinden gerçek zamanlı olarak güncellenmektedir. 
                        WebSocket bağlantısı kullanılarak saniyeler içinde değişimler gösterilir.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gerekli JS kütüphaneleri -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="assets/js/binance-live-data.js"></script>
    
    <script>
        $(document).ready(function() {
            // BinanceLiveData sınıfını başlat
            const binanceLiveData = new BinanceLiveData({
                tableId: 'coin-table',
                refreshInterval: 15000, // 15 saniye
                refreshButtonId: 'refresh-data',
                loadingOverlayId: 'loading-overlay',
                lastUpdateTimeId: 'last-update-time'
            });
            
            // Yeni coin ekleme
            $('#new-coin-form').on('submit', function(e) {
                e.preventDefault();
                
                // Girilen sembol
                let symbol = $('#new-coin-symbol').val().toUpperCase();
                if (!symbol.endsWith('USDT')) {
                    symbol += 'USDT'; // USDT çiftine dönüştür
                }
                
                // Bu sembol zaten tabloda var mı kontrol et
                if ($(`#coin-table tr[data-symbol="${symbol}"]`).length > 0) {
                    alert(`${symbol} zaten listede bulunuyor!`);
                    return;
                }
                
                // Yeni satır ekle
                const newRow = `
                    <tr class="coin-row" data-symbol="${symbol}">
                        <td><strong>${symbol}</strong></td>
                        <td class="price-cell">--</td>
                        <td class="change-cell">--</td>
                        <td class="high-cell">--</td>
                        <td class="low-cell">--</td>
                        <td class="volume-cell">--</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger remove-coin" data-symbol="${symbol}" title="Listeden kaldır">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                $('#coin-table tbody').append(newRow);
                $('#new-coin-symbol').val(''); // Formu temizle
                
                // WebSocket bağlantısını yenile
                binanceLiveData.closeWebSocket();
                binanceLiveData.initWebSocketConnection();
                
                // Verileri hemen yenile
                binanceLiveData.refreshData();
            });
            
            // Coin silme işlemi
            $(document).on('click', '.remove-coin', function() {
                const symbol = $(this).data('symbol');
                
                // Default coin'lerden biriyse silmeyi engelle
                if (<?php echo json_encode($default_coins); ?>.includes(symbol) && $('#coin-table tbody tr').length <= <?php echo count($default_coins); ?>) {
                    alert('En az bir coin bulunmalıdır!');
                    return;
                }
                
                // Satırı kaldır
                $(this).closest('tr').fadeOut(300, function() {
                    $(this).remove();
                    
                    // WebSocket bağlantısını yenile
                    binanceLiveData.closeWebSocket();
                    binanceLiveData.initWebSocketConnection();
                });
            });
            
            // WebSocket bağlantı durumunu kontrol et
            function checkConnectionStatus() {
                const statusElement = $('#connection-status');
                
                if (binanceLiveData.websocket && binanceLiveData.websocket.readyState === 1) {
                    // Bağlı
                    statusElement.removeClass('badge-danger badge-warning').addClass('badge-success');
                    statusElement.html('<i class="fas fa-wifi"></i> Bağlı');
                } else if (binanceLiveData.websocket && binanceLiveData.websocket.readyState === 0) {
                    // Bağlanıyor
                    statusElement.removeClass('badge-danger badge-success').addClass('badge-warning');
                    statusElement.html('<i class="fas fa-sync fa-spin"></i> Bağlanıyor');
                } else {
                    // Bağlantı Kesildi
                    statusElement.removeClass('badge-success badge-warning').addClass('badge-danger');
                    statusElement.html('<i class="fas fa-exclamation-triangle"></i> Bağlantı Kesildi');
                }
            }
            
            // Periyodik olarak bağlantı durumunu kontrol et
            setInterval(checkConnectionStatus, 5000);
            
            // İlk kontrolü yap
            setTimeout(checkConnectionStatus, 2000);
        });
    </script>
</body>
</html>