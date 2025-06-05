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
$page_title = 'Açık Pozisyonlar';

// Sadece gerçekten açık pozisyonları getir - çok sıkı filtre
$query = "SELECT * FROM open_positions 
          WHERE close_time IS NULL 
          AND (close_price IS NULL OR close_price = 0)
          AND (status != 'closed' AND status != 'CLOSED')
          ORDER BY entry_time DESC";
$result = $conn->query($query);
$open_positions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Ekstra kontrol - eğer close_time varsa veya status closed ise atlayalım
        if (empty($row['close_time']) && 
            $row['close_time'] !== '0000-00-00 00:00:00' && 
            strtolower($row['status']) !== 'closed') {
            $open_positions[] = $row;
        }
    }
}

// GERÇEKTEKİ ANLIK FİYATI AL - Binance API'den
function getCurrentPrice($symbol) {
    try {
        // Binance API'den anlık fiyat al
        $api_url = "https://api.binance.com/api/v3/ticker/price?symbol=" . strtoupper($symbol);
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 saniye timeout
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($api_url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['price'])) {
                return (float)$data['price'];
            }
        }
        
        return null; // API başarısız olursa null döndür
    } catch (Exception $e) {
        error_log("Fiyat alma hatası: " . $e->getMessage());
        return null;
    }
}

// Her pozisyon için kar/zarar hesapla - GERÇEK VERİLERLE
foreach ($open_positions as &$position) {
    // Gerçek anlık fiyatı al
    $current_price = getCurrentPrice($position['symbol']);
    
    // Eğer API'den fiyat alınamazsa, veritabanından en son bilinen fiyatı kullan
    if ($current_price === null) {
        // Önce pozisyonun kendi current_price değerini kontrol et
        if (!empty($position['current_price']) && $position['current_price'] > 0) {
            $current_price = $position['current_price'];
        } else {
            $current_price = $position['entry_price']; // Son çare olarak giriş fiyatı
        }
    }
    
    // Hesaplamaları yap
    $position['current_price'] = $current_price;
    $position['unrealized_pnl'] = ($current_price - $position['entry_price']) * $position['amount'];
    $position['unrealized_pnl_pct'] = (($current_price / $position['entry_price']) - 1) * 100;
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
        .position-card {
            transition: transform 0.2s ease-in-out;
            border-left: 4px solid #28a745;
        }
        .position-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .profit-positive {
            color: #28a745 !important;
        }
        .profit-negative {
            color: #dc3545 !important;
        }
        .profit-neutral {
            color: #6c757d !important;
        }
        .action-btn {
            margin: 2px;
        }
        .position-details {
            font-size: 0.9em;
        }
        .price-badge {
            font-family: 'Courier New', monospace;
        }
        .leverage-badge {
            background: linear-gradient(45deg, #007bff, #6f42c1);
        }
        .risk-low {
            background-color: #d4edda;
            color: #155724;
        }
        .risk-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        .risk-high {
            background-color: #f8d7da;
            color: #721c24;
        }
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 0.9em;
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
                <!-- Debug Bilgileri -->
                <div class="debug-info">
                    <strong>Filtre Bilgileri:</strong>
                    <a href="debug_open_positions.php" target="_blank" class="btn btn-sm btn-info float-right">
                        <i class="fas fa-bug"></i> Detaylı Analiz
                    </a>
                    <br>
                    <small>
                        Sadece açık pozisyonlar gösteriliyor (close_time NULL ve status != closed) | 
                        Bulunan açık pozisyon sayısı: <?php echo count($open_positions); ?>
                    </small>
                </div>

                <!-- Başlık ve Özet -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">
                                    <i class="fas fa-chart-area"></i> Açık Pozisyonlar
                                </h4>
                                <div>
                                    <span class="badge badge-light mr-2">
                                        <i class="fas fa-layer-group"></i> <?php echo count($open_positions); ?> Pozisyon
                                    </span>
                                    <button class="btn btn-sm btn-light" onclick="location.reload()">
                                        <i class="fas fa-sync-alt"></i> Yenile
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($open_positions)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-chart-area fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Henüz açık pozisyon bulunmuyor</h5>
                                        <p class="text-muted">Bot çalıştığında buraya açık pozisyonlar görünecektir.</p>
                                        <a href="dashboard.php" class="btn btn-primary">
                                            <i class="fas fa-tachometer-alt"></i> Dashboard'a Git
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- Özet İstatistikler -->
                                    <?php
                                    $total_investment = 0;
                                    $total_unrealized_pnl = 0;
                                    $total_leverage = 0;
                                    
                                    foreach ($open_positions as $pos) {
                                        $total_investment += $pos['trade_amount_usd'] ?? 0;
                                        $total_unrealized_pnl += $pos['unrealized_pnl'] ?? 0;
                                        $total_leverage += $pos['leverage'] ?? 1;
                                    }
                                    
                                    $avg_leverage = count($open_positions) > 0 ? $total_leverage / count($open_positions) : 0;
                                    ?>
                                    <div class="row mb-4">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body text-center">
                                                    <h5><?php echo number_format($total_investment, 2); ?> USDT</h5>
                                                    <small>Toplam Yatırım</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card <?php echo $total_unrealized_pnl >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                                                <div class="card-body text-center">
                                                    <h5><?php echo ($total_unrealized_pnl >= 0 ? '+' : '') . number_format($total_unrealized_pnl, 2); ?> USDT</h5>
                                                    <small>Toplam K/Z</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body text-center">
                                                    <h5><?php echo number_format($avg_leverage, 1); ?>x</h5>
                                                    <small>Ortalama Kaldıraç</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-secondary text-white">
                                                <div class="card-body text-center">
                                                    <h5><?php echo count($open_positions); ?></h5>
                                                    <small>Açık Pozisyon</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pozisyon Listesi -->
                                    <div class="row">
                                        <?php foreach ($open_positions as $position): ?>
                                            <div class="col-lg-6 col-xl-4 mb-4">
                                                <div class="card position-card h-100 shadow-sm">
                                                    <!-- Pozisyon Başlığı -->
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <h5 class="mb-0">
                                                            <i class="fas fa-coins text-warning"></i>
                                                            <?php echo htmlspecialchars($position['symbol']); ?>
                                                        </h5>
                                                        <div>
                                                            <?php if (isset($position['leverage']) && $position['leverage'] > 1): ?>
                                                                <span class="badge leverage-badge text-white">
                                                                    <?php echo $position['leverage']; ?>x
                                                                </span>
                                                            <?php endif; ?>
                                                            <span class="badge badge-success"><?php echo strtoupper($position['type'] ?? 'LONG'); ?></span>
                                                            <!-- Trade Mode Badge -->
                                                            <?php if (isset($position['trade_mode'])): ?>
                                                                <span class="badge <?php echo $position['trade_mode'] == 'live' ? 'badge-primary' : 'badge-warning'; ?>">
                                                                    <?php echo strtoupper($position['trade_mode']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Pozisyon Detayları -->
                                                    <div class="card-body">
                                                        <div class="position-details">
                                                            <!-- Fiyat Bilgileri -->
                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <strong>Giriş Fiyatı:</strong><br>
                                                                    <span class="price-badge badge badge-secondary">
                                                                        <?php echo number_format($position['entry_price'], 6); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="col-6">
                                                                    <strong>Güncel Fiyat:</strong><br>
                                                                    <span class="price-badge badge badge-info">
                                                                        <?php echo number_format($position['current_price'], 6); ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Miktar ve Yatırım -->
                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <strong>Miktar:</strong><br>
                                                                    <span><?php echo number_format($position['amount'], 6); ?></span>
                                                                </div>
                                                                <div class="col-6">
                                                                    <strong>Yatırım:</strong><br>
                                                                    <span><?php echo number_format($position['trade_amount_usd'] ?? 0, 2); ?> USDT</span>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Stop Loss & Take Profit -->
                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <strong>Stop Loss:</strong><br>
                                                                    <?php if (!empty($position['stop_loss'])): ?>
                                                                        <span class="text-danger">
                                                                            <?php echo number_format($position['stop_loss'], 6); ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Yok</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-6">
                                                                    <strong>Take Profit:</strong><br>
                                                                    <?php if (!empty($position['take_profit'])): ?>
                                                                        <span class="text-success">
                                                                            <?php echo number_format($position['take_profit'], 6); ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Yok</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Kar/Zarar -->
                                                            <div class="row mb-3">
                                                                <div class="col-12">
                                                                    <strong>Gerçekleşmemiş K/Z:</strong><br>
                                                                    <h5 class="<?php echo $position['unrealized_pnl'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                                        <?php echo ($position['unrealized_pnl'] >= 0 ? '+' : '') . number_format($position['unrealized_pnl'], 2); ?> USDT
                                                                        <small>(<?php echo ($position['unrealized_pnl_pct'] >= 0 ? '+' : '') . number_format($position['unrealized_pnl_pct'], 2); ?>%)</small>
                                                                    </h5>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Debug Info -->
                                                            <div class="row mb-3">
                                                                <div class="col-12">
                                                                    <small class="text-muted">
                                                                        <strong>ID:</strong> <?php echo $position['id']; ?> | 
                                                                        <strong>Status:</strong> <?php echo htmlspecialchars($position['status'] ?? 'NULL'); ?> | 
                                                                        <strong>Mode:</strong> <?php echo htmlspecialchars($position['trade_mode'] ?? 'NULL'); ?> | 
                                                                        <strong>Close Time:</strong> <?php echo $position['close_time'] ?: 'NULL'; ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Açılış Zamanı -->
                                                            <div class="row mb-3">
                                                                <div class="col-12">
                                                                    <strong>Açılış Zamanı:</strong><br>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-clock"></i>
                                                                        <?php echo $position['entry_time'] ? date('d.m.Y H:i:s', strtotime($position['entry_time'])) : 'Bilinmiyor'; ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Pozisyon İşlemleri -->
                                                    <div class="card-footer bg-light">
                                                        <div class="btn-group btn-group-sm w-100" role="group">
                                                            <button type="button" class="btn btn-danger action-btn close-position" 
                                                                    data-position-id="<?php echo $position['id']; ?>"
                                                                    data-symbol="<?php echo htmlspecialchars($position['symbol']); ?>">
                                                                <i class="fas fa-times-circle"></i> Pozisyonu Kapat
                                                            </button>
                                                            <button type="button" class="btn btn-warning action-btn edit-position" 
                                                                    data-position-id="<?php echo $position['id']; ?>">
                                                                <i class="fas fa-edit"></i> Düzenle
                                                            </button>
                                                            <button type="button" class="btn btn-info action-btn view-details" 
                                                                    data-position-id="<?php echo $position['id']; ?>">
                                                                <i class="fas fa-info-circle"></i> Detay
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pozisyon Kapatma Modal -->
    <div class="modal fade" id="closePositionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pozisyonu Kapat</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Bu pozisyonu kapatmak istediğinizden emin misiniz?</p>
                    <div class="alert alert-warning">
                        <strong>Dikkat:</strong> Bu işlem geri alınamaz. Pozisyon piyasa fiyatından satılacaktır.
                    </div>
                    <div id="position-info"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirm-close-position">
                        <i class="fas fa-times-circle"></i> Evet, Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        let selectedPositionId = null;
        let selectedSymbol = null;
        
        // Pozisyon kapatma
        $('.close-position').click(function() {
            selectedPositionId = $(this).data('position-id');
            selectedSymbol = $(this).data('symbol');
            
            $('#position-info').html(`
                <strong>Sembol:</strong> ${selectedSymbol}<br>
                <strong>Pozisyon ID:</strong> ${selectedPositionId}
            `);
            
            $('#closePositionModal').modal('show');
        });
        
        // Pozisyon kapatma onayı
        $('#confirm-close-position').click(function() {
            if (selectedPositionId) {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Kapatılıyor...');
                
                $.ajax({
                    url: 'ajax/close_position.php',
                    method: 'POST',
                    data: {
                        position_id: selectedPositionId,
                        action: 'close'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Pozisyon başarıyla kapatıldı!');
                            location.reload();
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Pozisyon kapatılırken bir hata oluştu.');
                    },
                    complete: function() {
                        $('#confirm-close-position').prop('disabled', false).html('<i class="fas fa-times-circle"></i> Evet, Kapat');
                        $('#closePositionModal').modal('hide');
                    }
                });
            }
        });
        
        // Pozisyon düzenleme
        $('.edit-position').click(function() {
            const positionId = $(this).data('position-id');
            // Bu fonksiyon geliştirilecek - stop loss / take profit düzenleme
            alert('Pozisyon düzenleme özelliği yakında eklenecek.');
        });
        
        // Pozisyon detayları
        $('.view-details').click(function() {
            const positionId = $(this).data('position-id');
            // Bu fonksiyon geliştirilecek - detaylı analiz sayfası
            window.open(`position_detail.php?id=${positionId}`, '_blank');
        });
        
        // Otomatik yenileme (30 saniyede bir)
        setInterval(function() {
            location.reload();
        }, 30000);
    });
    </script>
</body>
</html>