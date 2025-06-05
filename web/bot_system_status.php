<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// AJAX isteği kontrolü
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $systemStatus = new SystemStatus($conn);
    $response = [
        'bot_status' => $systemStatus->checkBotStatus(),
        'system_resources' => $systemStatus->getSystemResources(),
        'database_status' => $systemStatus->checkDatabaseStatus(),
        'bot_metrics' => $systemStatus->getBotMetrics(),
        'api_status' => $systemStatus->checkApiStatus(),
        'ws_status' => $systemStatus->checkWebSocketStatus()
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$page_title = 'Bot Sistem Durumu';
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
        .status-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .status-icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        
        .status-working { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        
        .detail-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            font-family: monospace;
            max-height: 150px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <div class="col-md-10">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-server"></i> Bot Sistem Durumu</h5>
                        <span id="last-check" class="small"></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Bot Durumu -->
                            <div class="col-md-6">
                                <div id="bot-status-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i id="bot-status-icon" class="fas fa-circle status-icon"></i>
                                            <div>
                                                <h5 class="card-title mb-1">Bot Durumu</h5>
                                                <p id="bot-status-text" class="card-text mb-0"></p>
                                            </div>
                                        </div>
                                        <div id="bot-status-details" class="detail-box mt-3" style="display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sistem Kaynakları -->
                            <div class="col-md-6">
                                <div id="system-resources-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-microchip status-icon"></i>
                                            <h5 class="card-title mb-1">Sistem Kaynakları</h5>
                                        </div>
                                        <div class="detail-box">
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div id="cpu-usage-bar" class="progress-bar" role="progressbar" style="width: 0%">
                                                    CPU: 0%
                                                </div>
                                            </div>
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div id="memory-usage-bar" class="progress-bar bg-info" role="progressbar" style="width: 0%">
                                                    RAM: 0%
                                                </div>
                                            </div>
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div id="disk-usage-bar" class="progress-bar bg-warning" role="progressbar" style="width: 0%">
                                                    Disk: 0%
                                                </div>
                                            </div>
                                            <p id="load-average-text" class="mb-0 mt-2"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Veritabanı Durumu -->
                            <div class="col-md-6">
                                <div id="database-status-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i id="db-status-icon" class="fas fa-database status-icon"></i>
                                            <div>
                                                <h5 class="card-title mb-1">Veritabanı Durumu</h5>
                                                <p id="db-status-text" class="card-text mb-0"></p>
                                            </div>
                                        </div>
                                        <div id="db-status-details" class="detail-box mt-3">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bot Metrikleri -->
                            <div class="col-md-6">
                                <div id="bot-metrics-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-chart-line status-icon"></i>
                                            <h5 class="card-title mb-1">Bot Metrikleri</h5>
                                        </div>
                                        <div id="bot-metrics-details" class="detail-box">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- API Durumu -->
                            <div class="col-md-6">
                                <div id="api-status-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i id="api-status-icon" class="fas fa-plug status-icon"></i>
                                            <div>
                                                <h5 class="card-title mb-1">API Durumu</h5>
                                                <p id="api-status-text" class="card-text mb-0"></p>
                                            </div>
                                        </div>
                                        <div id="api-status-details" class="detail-box mt-3">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Performans -->
                            <div class="col-md-12">
                                <div id="performance-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tachometer-alt status-icon status-working"></i>
                                            <h5 class="card-title mb-1">Performans</h5>
                                        </div>
                                        <div id="performance-details" class="detail-box">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h6>İşlem Performansı</h6>
                                                    <div class="progress mb-2" style="height: 20px;">
                                                        <div id="win-rate-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%">
                                                            Kazanç Oranı: 0%
                                                        </div>
                                                    </div>
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td>Toplam İşlem</td>
                                                            <td id="total-trades-value">0</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Karlı İşlemler</td>
                                                            <td id="profit-trades-value">0</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Zararlı İşlemler</td>
                                                            <td id="loss-trades-value">0</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6>Kar/Zarar Performansı</h6>
                                                    <div class="progress mb-2" style="height: 20px;">
                                                        <div id="pnl-bar" class="progress-bar bg-info" role="progressbar" style="width: 0%">
                                                            Toplam Kar: 0%
                                                        </div>
                                                    </div>
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td>Ortalama Kar</td>
                                                            <td id="avg-profit-value">0%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Ortalama Zarar</td>
                                                            <td id="avg-loss-value">0%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Risk/Ödül Oranı</td>
                                                            <td id="risk-reward-value">0</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6>Zaman Performansı</h6>
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td>Ortalama İşlem Süresi</td>
                                                            <td id="avg-trade-duration">0s</td>
                                                        </tr>
                                                        <tr>
                                                            <td>En Uzun Kar İşlemi</td>
                                                            <td id="longest-profit-trade">0s</td>
                                                        </tr>
                                                        <tr>
                                                            <td>En Kısa Kar İşlemi</td>
                                                            <td id="shortest-profit-trade">0s</td>
                                                        </tr>
                                                        <tr>
                                                            <td>İşlem Sıklığı</td>
                                                            <td id="trade-frequency">0/gün</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- WebSocket Durumu -->
                            <div class="col-md-6">
                                <div id="ws-status-card" class="card status-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i id="ws-status-icon" class="fas fa-exchange-alt status-icon"></i>
                                            <div>
                                                <h5 class="card-title mb-1">WebSocket Durumu</h5>
                                                <p id="ws-status-text" class="card-text mb-0"></p>
                                            </div>
                                        </div>
                                        <div id="ws-status-details" class="detail-box mt-3">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary rounded-circle refresh-btn" onclick="updateStatus(true)">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    function updateLastCheck() {
        const now = new Date();
        document.getElementById('last-check').textContent = 'Son Kontrol: ' + now.toLocaleTimeString();
    }

    function updateStatus(showLoading = false) {
        if (showLoading) {
            $('.status-icon').addClass('fa-spin');
        }

        $.ajax({
            url: window.location.href,
            type: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(data) {
                updateLastCheck();
                
                // Bot Durumu
                const botStatus = data.bot_status;
                $('#bot-status-icon').removeClass().addClass('fas status-icon');
                if (botStatus.running) {
                    $('#bot-status-icon').addClass('fa-check-circle status-working');
                    $('#bot-status-text').html('Çalışıyor');
                    $('#bot-status-details').html(`
                        <p>PID: ${botStatus.pid}</p>
                        <p>Çalışma Süresi: ${botStatus.uptime}</p>
                        <p>Son Aktivite: ${botStatus.last_activity}</p>
                    `).show();
                } else {
                    $('#bot-status-icon').addClass('fa-times-circle status-error');
                    $('#bot-status-text').html('Çalışmıyor');
                    $('#bot-status-details').hide();
                }

                // Sistem Kaynakları
                const sysRes = data.system_resources;
                $('#cpu-usage-bar').css('width', sysRes.cpu_usage + '%')
                    .text('CPU: ' + Math.round(sysRes.cpu_usage) + '%');
                $('#memory-usage-bar').css('width', sysRes.memory_usage + '%')
                    .text('RAM: ' + Math.round(sysRes.memory_usage) + '%');
                $('#disk-usage-bar').css('width', sysRes.disk_usage + '%')
                    .text('Disk: ' + Math.round(sysRes.disk_usage) + '%');
                $('#load-average-text').text('Load Average: ' + sysRes.load_average.map(x => x.toFixed(2)).join(' '));

                // Veritabanı Durumu
                const dbStatus = data.database_status;
                $('#db-status-icon').removeClass().addClass('fas fa-database status-icon');
                if (dbStatus.connected) {
                    $('#db-status-icon').addClass('status-working');
                    $('#db-status-text').html('Bağlı');
                    let tableHtml = '<table class="table table-sm"><tr><th>Tablo</th><th>Satır</th><th>Boyut</th></tr>';
                    dbStatus.tables.forEach(table => {
                        tableHtml += `<tr><td>${table.name}</td><td>${table.rows}</td><td>${table.size}</td></tr>`;
                    });
                    tableHtml += '</table>';
                    $('#db-status-details').html(`
                        <p>Yanıt Süresi: ${dbStatus.response_time}ms</p>
                        <p>Aktif Bağlantılar: ${dbStatus.active_connections}</p>
                        ${tableHtml}
                    `);
                } else {
                    $('#db-status-icon').addClass('status-error');
                    $('#db-status-text').html('Bağlantı Hatası');
                    $('#db-status-details').html(`<p class="text-danger">${dbStatus.message}</p>`);
                }

                // Bot Metrikleri
                const metrics = data.bot_metrics;
                if (metrics.status === 'success') {
                    const stats = metrics.trade_stats;
                    $('#bot-metrics-details').html(`
                        <p>Son 24 Saat:</p>
                        <ul>
                            <li>Toplam İşlem: ${stats.total_trades}</li>
                            <li>Karlı İşlem: ${stats.profitable_trades}</li>
                            <li>Başarı Oranı: ${stats.success_rate}%</li>
                            <li>Ortalama Kar: ${stats.avg_profit}%</li>
                            <li>Toplam Kar: ${stats.total_profit}%</li>
                        </ul>
                        <p>Aktif Stratejiler: ${metrics.active_strategies}</p>
                    `);
                } else {
                    $('#bot-metrics-details').html(`<p class="text-danger">${metrics.message}</p>`);
                }

                // API Durumu
                const apiStatus = data.api_status;
                $('#api-status-icon').removeClass().addClass('fas fa-plug status-icon');
                if (apiStatus.connected) {
                    $('#api-status-icon').addClass('status-working');
                    $('#api-status-text').html('Bağlı');
                    $('#api-status-details').html(`
                        <p>Yanıt Süresi: ${apiStatus.response_time}ms</p>
                        <p>Rate Limits:</p>
                        <ul>
                            ${Object.entries(apiStatus.rate_limits).map(([type, limit]) => 
                                `<li>${type}: ${limit.limit}/${limit.interval}</li>`
                            ).join('')}
                        </ul>
                    `);
                } else {
                    $('#api-status-icon').addClass('status-error');
                    $('#api-status-text').html('Bağlantı Hatası');
                    $('#api-status-details').html(`<p class="text-danger">${apiStatus.message}</p>`);
                }

                // WebSocket Durumu
                const wsStatus = data.ws_status;
                $('#ws-status-icon').removeClass().addClass('fas fa-exchange-alt status-icon');
                if (wsStatus.connected) {
                    $('#ws-status-icon').addClass('status-working');
                    $('#ws-status-text').html('Bağlı');
                    $('#ws-status-details').html(`
                        <p>Son Mesaj: ${wsStatus.last_message}</p>
                        <p>Takip Edilen Çiftler:</p>
                        <ul>
                            ${wsStatus.subscribed_pairs.map(pair => `<li>${pair}</li>`).join('')}
                        </ul>
                    `);
                } else {
                    $('#ws-status-icon').addClass(wsStatus.status === 'warning' ? 'status-warning' : 'status-error');
                    $('#ws-status-text').html('Bağlı Değil');
                    $('#ws-status-details').html(`<p class="text-warning">${wsStatus.message}</p>`);
                }
            },
            error: function() {
                alert('Güncelleme sırasında bir hata oluştu!');
            },
            complete: function() {
                $('.status-icon').removeClass('fa-spin');
            }
        });
    }

    // Sayfa yüklendiğinde ve her 30 saniyede bir güncelle
    updateStatus();
    setInterval(updateStatus, 30000);
    </script>
</body>
</html>