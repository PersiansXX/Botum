<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

// Pozisyon ID'si kontrolü
$position_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($position_id <= 0) {
    header('Location: open_positions.php');
    exit;
}

try {
    // Pozisyon bilgilerini al - GÜVENLİ VERSİYON
    $query = "SELECT * FROM open_positions WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Sorgu hazırlama hatası: " . $conn->error);
    }
    
    $stmt->bind_param("i", $position_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: open_positions.php');
        exit;
    }

    $position = $result->fetch_assoc();
    $stmt->close();

    // Trade geçmişini al (aynı sembol için) - GÜVENLİ VERSİYON
    $trade_history = [];
    if (!empty($position['symbol'])) {
        $history_query = "SELECT * FROM trades WHERE symbol = ? ORDER BY entry_time DESC LIMIT 10";
        $stmt2 = $conn->prepare($history_query);
        
        if ($stmt2) {
            $stmt2->bind_param("s", $position['symbol']);
            $stmt2->execute();
            $history_result = $stmt2->get_result();
            $trade_history = $history_result->fetch_all(MYSQLI_ASSOC);
            $stmt2->close();
        } else {
            error_log("Trade geçmişi sorgu hatası: " . $conn->error);
        }
    }

} catch (Exception $e) {
    error_log("Position detail hatası: " . $e->getMessage());
    header('Location: open_positions.php?error=db_error');
    exit;
}

// Risk analizi hesapla
function calculateRiskLevel($leverage, $stop_loss_pct, $volatility = null) {
    $risk_score = 0;
    
    // Kaldıraç riski
    if ($leverage >= 10) $risk_score += 30;
    elseif ($leverage >= 5) $risk_score += 20;
    elseif ($leverage >= 3) $risk_score += 10;
    
    // Stop loss riski
    if (empty($stop_loss_pct) || $stop_loss_pct > 5) $risk_score += 25;
    elseif ($stop_loss_pct > 3) $risk_score += 15;
    elseif ($stop_loss_pct > 1) $risk_score += 5;
    
    // Volatilite riski (eğer varsa)
    if ($volatility !== null) {
        if ($volatility > 8) $risk_score += 25;
        elseif ($volatility > 5) $risk_score += 15;
        elseif ($volatility > 3) $risk_score += 10;
    }
    
    if ($risk_score >= 50) return ['level' => 'YÜKSEK', 'class' => 'danger', 'score' => $risk_score];
    elseif ($risk_score >= 25) return ['level' => 'ORTA', 'class' => 'warning', 'score' => $risk_score];
    else return ['level' => 'DÜŞÜK', 'class' => 'success', 'score' => $risk_score];
}

// Pozisyon süresini hesapla
$position_duration = '';
if ($position['entry_time']) {
    $entry_time = new DateTime($position['entry_time']);
    $now = new DateTime();
    $interval = $entry_time->diff($now);
    
    if ($interval->days > 0) {
        $position_duration = $interval->days . ' gün, ' . $interval->h . ' saat';
    } else {
        $position_duration = $interval->h . ' saat, ' . $interval->i . ' dakika';
    }
}

// Stop loss yüzdesini hesapla
$stop_loss_pct = 0;
if (!empty($position['stop_loss']) && $position['entry_price'] > 0) {
    $stop_loss_pct = abs(($position['stop_loss'] - $position['entry_price']) / $position['entry_price']) * 100;
}

// Risk seviyesini hesapla
$risk_analysis = calculateRiskLevel($position['leverage'] ?? 1, $stop_loss_pct);

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

// Gerçek anlık fiyatı al
$current_price = getCurrentPrice($position['symbol']);

// Eğer API'den fiyat alınamazsa, veritabanından en son bilinen fiyatı kullan
if ($current_price === null) {
    // En son işlem fiyatını kontrol et
    $price_query = "SELECT current_price FROM open_positions WHERE symbol = ? ORDER BY updated_at DESC LIMIT 1";
    $stmt_price = $conn->prepare($price_query);
    if ($stmt_price) {
        $stmt_price->bind_param("s", $position['symbol']);
        $stmt_price->execute();
        $price_result = $stmt_price->get_result();
        if ($price_result->num_rows > 0) {
            $price_data = $price_result->fetch_assoc();
            $current_price = $price_data['current_price'] ?? $position['entry_price'];
        } else {
            $current_price = $position['entry_price']; // Son çare olarak giriş fiyatı
        }
        $stmt_price->close();
    } else {
        $current_price = $position['entry_price']; // Son çare olarak giriş fiyatı
    }
}

// Kar/zarar hesaplama - GERÇEK VERİLERLE
$unrealized_pnl = ($current_price - $position['entry_price']) * $position['amount'];
$unrealized_pnl_pct = (($current_price / $position['entry_price']) - 1) * 100;

$page_title = 'Pozisyon Detayı - ' . $position['symbol'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .position-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .risk-meter {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .risk-fill {
            height: 100%;
            transition: width 1s ease;
        }
        .price-chart {
            height: 300px;
        }
        .timeline-item {
            border-left: 3px solid #007bff;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
            position: absolute;
            left: -7.5px;
            top: 5px;
        }
    </style>
</head>
<body>
    <!-- Başlık Bölümü -->
    <div class="position-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-chart-line"></i>
                        <?php echo htmlspecialchars($position['symbol']); ?>
                        <span class="badge badge-light ml-2">
                            <?php echo $position['leverage'] ?? 1; ?>x
                        </span>
                        <span class="badge badge-<?php echo $position['type'] == 'LONG' ? 'success' : 'danger'; ?> ml-2">
                            <?php echo strtoupper($position['type'] ?? 'LONG'); ?>
                        </span>
                    </h1>
                    <p class="mb-0 mt-2">
                        <i class="fas fa-clock"></i> 
                        Açılış: <?php echo $position['entry_time'] ? date('d.m.Y H:i:s', strtotime($position['entry_time'])) : 'Bilinmiyor'; ?>
                        <span class="ml-3">
                            <i class="fas fa-hourglass-half"></i>
                            Süre: <?php echo $position_duration; ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group">
                        <a href="open_positions.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                        <button class="btn btn-outline-light" onclick="location.reload()">
                            <i class="fas fa-sync"></i> Yenile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Ana İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4><?php echo number_format($position['entry_price'], 6); ?></h4>
                        <small>Giriş Fiyatı</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body text-center">
                        <h4><?php echo number_format($current_price, 6); ?></h4>
                        <small>Güncel Fiyat</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-<?php echo $unrealized_pnl >= 0 ? 'success' : 'danger'; ?> text-white">
                    <div class="card-body text-center">
                        <h4><?php echo ($unrealized_pnl >= 0 ? '+' : '') . number_format($unrealized_pnl, 2); ?> USDT</h4>
                        <small>Gerçekleşmemiş K/Z</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h4><?php echo ($unrealized_pnl_pct >= 0 ? '+' : '') . number_format($unrealized_pnl_pct, 2); ?>%</h4>
                        <small>K/Z Yüzdesi</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pozisyon Detayları -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Pozisyon Detayları</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Sembol:</strong></td>
                                        <td><?php echo htmlspecialchars($position['symbol']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pozisyon Türü:</strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $position['type'] == 'LONG' ? 'success' : 'danger'; ?>">
                                                <?php echo strtoupper($position['type'] ?? 'LONG'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Miktar:</strong></td>
                                        <td><?php echo number_format($position['amount'], 6); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kaldıraç:</strong></td>
                                        <td><?php echo $position['leverage'] ?? 1; ?>x</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trade Modu:</strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $position['trade_mode'] == 'live' ? 'primary' : 'warning'; ?>">
                                                <?php echo strtoupper($position['trade_mode'] ?? 'PAPER'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Yatırım Tutarı:</strong></td>
                                        <td><?php echo number_format($position['trade_amount_usd'] ?? 0, 2); ?> USDT</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Stop Loss:</strong></td>
                                        <td>
                                            <?php if (!empty($position['stop_loss'])): ?>
                                                <span class="text-danger">
                                                    <?php echo number_format($position['stop_loss'], 6); ?>
                                                    <small>(<?php echo number_format($stop_loss_pct, 2); ?>%)</small>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Belirlenmemiş</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Take Profit:</strong></td>
                                        <td>
                                            <?php if (!empty($position['take_profit'])): ?>
                                                <span class="text-success">
                                                    <?php echo number_format($position['take_profit'], 6); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Belirlenmemiş</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Durum:</strong></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo strtoupper($position['status'] ?? 'OPEN'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pozisyon ID:</strong></td>
                                        <td><small class="text-muted"><?php echo $position['id']; ?></small></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fiyat Grafiği -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-area"></i> Fiyat Grafiği (Simülasyon)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="priceChart" class="price-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Risk Analizi ve İşlemler -->
            <div class="col-md-4">
                <!-- Risk Analizi -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt"></i> Risk Analizi</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <h4 class="text-<?php echo $risk_analysis['class']; ?>">
                                <?php echo $risk_analysis['level']; ?> RİSK
                            </h4>
                            <div class="risk-meter">
                                <div class="risk-fill bg-<?php echo $risk_analysis['class']; ?>" 
                                     style="width: <?php echo $risk_analysis['score']; ?>%"></div>
                            </div>
                            <small class="text-muted">Risk Skoru: <?php echo $risk_analysis['score']; ?>/100</small>
                        </div>
                        
                        <ul class="list-unstyled">
                            <li><i class="fas fa-caret-right text-primary"></i> Kaldıraç: <?php echo $position['leverage'] ?? 1; ?>x</li>
                            <li><i class="fas fa-caret-right text-warning"></i> Stop Loss: <?php echo number_format($stop_loss_pct, 1); ?>%</li>
                            <li><i class="fas fa-caret-right text-info"></i> Pozisyon Süresi: <?php echo $position_duration; ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> Hızlı İşlemler</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger btn-block" onclick="closePosition()">
                                <i class="fas fa-times-circle"></i> Pozisyonu Kapat
                            </button>
                            <button class="btn btn-warning btn-block" onclick="editStopLoss()">
                                <i class="fas fa-edit"></i> Stop Loss Düzenle
                            </button>
                            <button class="btn btn-success btn-block" onclick="editTakeProfit()">
                                <i class="fas fa-bullseye"></i> Take Profit Düzenle
                            </button>
                            <button class="btn btn-info btn-block" onclick="addToPosition()">
                                <i class="fas fa-plus"></i> Pozisyona Ekle
                            </button>
                        </div>
                    </div>
                </div>

                <!-- İşlem Geçmişi -->
                <?php if (!empty($trade_history)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Son İşlemler (<?php echo $position['symbol']; ?>)</h5>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach (array_slice($trade_history, 0, 5) as $trade): ?>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($trade['symbol']); ?></strong>
                                <span class="badge badge-<?php echo $trade['pnl'] >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($trade['pnl'] >= 0 ? '+' : '') . number_format($trade['pnl'], 2); ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d.m.Y H:i', strtotime($trade['entry_time'])); ?>
                                - <?php echo strtoupper($trade['type']); ?>
                                - <?php echo $trade['leverage'] ?? 1; ?>x
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Fiyat grafiği oluştur
        const ctx = document.getElementById('priceChart').getContext('2d');
        const entryPrice = <?php echo $position['entry_price']; ?>;
        const currentPrice = <?php echo $current_price; ?>;
        
        // Simülasyon veri oluştur
        const labels = [];
        const data = [];
        const now = new Date();
        
        for (let i = 23; i >= 0; i--) {
            const time = new Date(now.getTime() - i * 60 * 60 * 1000);
            labels.push(time.getHours().toString().padStart(2, '0') + ':00');
            
            // Basit rastgele fiyat hareketi simülasyonu
            const variation = (Math.random() - 0.5) * 0.02; // ±1% değişim
            const price = entryPrice * (1 + variation * (24 - i) / 24);
            data.push(price);
        }
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '<?php echo $position["symbol"]; ?> Fiyatı',
                    data: data,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Giriş Fiyatı',
                    data: new Array(24).fill(entryPrice),
                    borderColor: '#28a745',
                    borderDash: [5, 5],
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });

        // Hızlı işlem fonksiyonları
        function closePosition() {
            if (confirm('Bu pozisyonu kapatmak istediğinizden emin misiniz?')) {
                window.location.href = 'open_positions.php';
            }
        }

        function editStopLoss() {
            const newStopLoss = prompt('Yeni Stop Loss fiyatını girin:', <?php echo $position['stop_loss'] ?? 0; ?>);
            if (newStopLoss && !isNaN(newStopLoss)) {
                alert('Stop Loss güncelleme özelliği yakında eklenecek.');
            }
        }

        function editTakeProfit() {
            const newTakeProfit = prompt('Yeni Take Profit fiyatını girin:', <?php echo $position['take_profit'] ?? 0; ?>);
            if (newTakeProfit && !isNaN(newTakeProfit)) {
                alert('Take Profit güncelleme özelliği yakında eklenecek.');
            }
        }

        function addToPosition() {
            const additionalAmount = prompt('Pozisyona eklemek istediğiniz tutarı girin (USDT):');
            if (additionalAmount && !isNaN(additionalAmount)) {
                alert('Pozisyona ekleme özelliği yakında eklenecek.');
            }
        }

        // Otomatik yenileme (60 saniye)
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>