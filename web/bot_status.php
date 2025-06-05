<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Bot durumu için direkt kontrol - BotAPI sınıfı olmadan da çalışacak şekilde
function checkBotStatus() {
    $bot_dir = '/var/www/html/bot';
    $log_file = "$bot_dir/bot.log";
    $error_log = "$bot_dir/bot_error.log";
    $pid_file = "$bot_dir/bot.pid";
    
    $status = [
        'running' => false,
        'pid' => 0,
        'uptime' => 'N/A',
        'cpu_usage' => 'N/A',
        'memory_usage' => 'N/A',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // 1. PID dosyası kontrolü
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        if (!empty($pid) && is_numeric($pid)) {
            $status['pid'] = $pid;
            
            // PID süreci çalışıyor mu?
            exec("ps -p $pid > /dev/null 2>&1", $output, $return_var);
            if ($return_var === 0) {
                $status['running'] = true;
                
                // Çalışma süresi ve kaynak bilgileri
                exec("ps -p $pid -o etimes= -o pcpu= -o pmem=", $resource_output);
                if (!empty($resource_output[0])) {
                    $resources = preg_split('/\s+/', trim($resource_output[0]));
                    if (count($resources) >= 3) {
                        $uptime_seconds = intval($resources[0]);
                        
                        // Çalışma süresini formatlı hale getir
                        $days = floor($uptime_seconds / 86400);
                        $uptime_seconds %= 86400;
                        $hours = floor($uptime_seconds / 3600);
                        $uptime_seconds %= 3600;
                        $minutes = floor($uptime_seconds / 60);
                        $seconds = $uptime_seconds % 60;
                        
                        $uptime = '';
                        if ($days > 0) $uptime .= $days . 'g ';
                        if ($hours > 0) $uptime .= $hours . 's ';
                        if ($minutes > 0) $uptime .= $minutes . 'd ';
                        $uptime .= $seconds . 'sn';
                        
                        $status['uptime'] = $uptime;
                        $status['cpu_usage'] = floatval($resources[1]) . '%';
                        $status['memory_usage'] = floatval($resources[2]) . '%';
                    }
                }
            }
        }
    }
    
    // 2. PID dosyası yoksa veya PID çalışmıyorsa tüm trading_bot.py süreçlerini kontrol et
    if (!$status['running']) {
        exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $process_output);
        if (!empty($process_output)) {
            $status['running'] = true;
            
            // İlk sürecin PID'ini al
            preg_match('/^\s*\S+\s+(\d+)/', $process_output[0], $matches);
            if (!empty($matches[1])) {
                $pid = $matches[1];
                $status['pid'] = $pid;
                
                // PID dosyasını güncelle
                @file_put_contents($pid_file, $pid);
                @chmod($pid_file, 0666);
                
                // Çalışma süresi ve kaynak bilgileri
                exec("ps -p $pid -o etimes= -o pcpu= -o pmem=", $resource_output);
                if (!empty($resource_output[0])) {
                    $resources = preg_split('/\s+/', trim($resource_output[0]));
                    if (count($resources) >= 3) {
                        $uptime_seconds = intval($resources[0]);
                        
                        // Çalışma süresini formatlı hale getir
                        $days = floor($uptime_seconds / 86400);
                        $uptime_seconds %= 86400;
                        $hours = floor($uptime_seconds / 3600);
                        $uptime_seconds %= 3600;
                        $minutes = floor($uptime_seconds / 60);
                        $seconds = $uptime_seconds % 60;
                        
                        $uptime = '';
                        if ($days > 0) $uptime .= $days . 'g ';
                        if ($hours > 0) $uptime .= $hours . 's ';
                        if ($minutes > 0) $uptime .= $minutes . 'd ';
                        $uptime .= $seconds . 'sn';
                        
                        $status['uptime'] = $uptime;
                        $status['cpu_usage'] = floatval($resources[1]) . '%';
                        $status['memory_usage'] = floatval($resources[2]) . '%';
                    }
                }
            }
        }
    }
    
    return $status;
}

// BotAPI sınıfını kullanmaya çalış, yoksa direkt fonksiyonu kullan
if (file_exists('api/bot_api.php')) {
    require_once 'api/bot_api.php';
    try {
        $bot_api = new BotAPI();
        $status = $bot_api->getStatus();
        
        // Eski API'den dönen veriler yoksa direkt kontrolü kullan
        if (!isset($status['running'])) {
            $status = checkBotStatus();
        }
    } catch (Exception $e) {
        $status = checkBotStatus();
    }
} else {
    $status = checkBotStatus();
}

// Bot durumu bilgileri
$running = $status['running'] ?? false;
$pid = $status['pid'] ?? 'Yok';
$uptime = $status['uptime'] ?? 'N/A';
$memory = $status['memory_usage'] ?? 'N/A';
$cpu = $status['cpu_usage'] ?? 'N/A';
$last_update = $status['last_updated'] ?? 'N/A';

// Bot durumuna göre mesaj ayarla
if ($running) {
    // Doğrudan süreç bilgilerini al
    if (!empty($pid) && $pid != 'Yok') {
        // CPU ve Bellek kullanımını doğrudan kontrol et
        exec("ps -p $pid -o pcpu= -o pmem=", $resource_output);
        if (!empty($resource_output[0])) {
            $resources = preg_split('/\s+/', trim($resource_output[0]));
            if (count($resources) >= 2) {
                $cpu = floatval($resources[0]) . '%';
                $memory = floatval($resources[1]) . '%';
            }
        }
        
        // Çalışma süresini doğrudan kontrol et
        exec("ps -p $pid -o etimes=", $uptime_output);
        if (!empty($uptime_output[0])) {
            $uptime_seconds = intval(trim($uptime_output[0]));
            
            // Çalışma süresini formatlı hale getir
            $days = floor($uptime_seconds / 86400);
            $uptime_seconds %= 86400;
            $hours = floor($uptime_seconds / 3600);
            $uptime_seconds %= 3600;
            $minutes = floor($uptime_seconds / 60);
            $seconds = $uptime_seconds % 60;
            
            $uptime = '';
            if ($days > 0) $uptime .= $days . 'g ';
            if ($hours > 0) $uptime .= $hours . 's ';
            if ($minutes > 0) $uptime .= $minutes . 'd ';
            $uptime .= $seconds . 'sn';
        }
    }
    
    $status_message = "Bot aktif olarak çalışıyor. (PID: $pid)";
    $status_class = "success";
    $status_icon = "check-circle";
} else {
    $status_message = "Bot şu anda pasif durumda.";
    $status_class = "danger";
    $status_icon = "times-circle";
}

// Son güncellenme zamanını formatla
if ($last_update != 'N/A') {
    $last_update_timestamp = strtotime($last_update);
    $time_diff = time() - $last_update_timestamp;
    
    if ($time_diff < 60) {
        $last_update_text = "az önce";
    } elseif ($time_diff < 3600) {
        $last_update_text = floor($time_diff / 60) . " dk önce";
    } elseif ($time_diff < 86400) {
        $last_update_text = floor($time_diff / 3600) . " sa önce";
    } else {
        $last_update_text = floor($time_diff / 86400) . " gün önce";
    }
} else {
    $last_update_text = 'Bilinmiyor';
}

// Bot log dosyaları - CentOS için tam yol
$bot_dir = '/var/www/html/bot';
$log_file = "$bot_dir/bot.log";
$error_log = "$bot_dir/bot_error.log";

// Son log kayıtları (CentOS için özel komutlar)
$last_logs = [];
if (file_exists($log_file)) {
    exec("tail -n 20 " . escapeshellarg($log_file), $last_logs);
} else {
    // Web sunucusundaki göreceli yol dene
    $local_log_file = __DIR__ . '/../bot/bot.log';
    if (file_exists($local_log_file)) {
        exec("tail -n 20 " . escapeshellarg($local_log_file), $last_logs);
    }
}

// Son hata kayıtları
$last_errors = [];
if (file_exists($error_log)) {
    exec("tail -n 20 " . escapeshellarg($error_log), $last_errors);
} else {
    // Web sunucusundaki göreceli yol dene
    $local_error_log = __DIR__ . '/../bot/bot_error.log';
    if (file_exists($local_error_log)) {
        exec("tail -n 20 " . escapeshellarg($local_error_log), $last_errors);
    }
}

// PID dosyası kontrolleri
$pid_file = "$bot_dir/bot.pid";
$pid_exists = file_exists($pid_file);
$pid_content = $pid_exists ? file_get_contents($pid_file) : 'Yok';

// CentOS'ta işlem durumu kontrolü
$ps_output = [];
if (isset($pid) && !empty($pid) && $pid != 'Yok') {
    exec("ps -p " . escapeshellarg($pid) . " -o pid,ppid,cmd,etime,pcpu,pmem --no-headers 2>/dev/null", $ps_output);
}
$process_exists = !empty($ps_output);

// Bot süreç durumu kontrolü - CentOS için
if ($process_exists && empty($uptime)) {
    // PS çıktısından süreç bilgilerini ayıkla
    $process_parts = preg_split('/\s+/', trim($ps_output[0]), 6);
    if (count($process_parts) >= 6) {
        $uptime = $process_parts[3] ?? 'N/A';
        $cpu = $process_parts[4] ?? '0.0';
        $memory = $process_parts[5] ?? '0.0';
        
        // Değerlere birim ekle
        $cpu .= '%';
        $memory .= '%';
    }
}

// Bugünkü istatistikler - API yoksa boş değerler
try {
    if (isset($bot_api)) {
        $today_stats = $bot_api->getTodayStats();
        $trade_count = $today_stats['total_trades'] ?? 0;
        $profit_today = $today_stats['profit_loss'] ?? 0;
    } else {
        $trade_count = 0;
        $profit_today = 0;
    }
} catch (Exception $e) {
    $trade_count = 0;
    $profit_today = 0;
}

// Sayfa başlığı
$page_title = 'Bot Durumu ve Yönetimi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/bot-status.css">
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
                <div class="mb-4">
                    <h4 class="font-weight-bold">Bot Durum Kontrolü</h4>
                    <p class="text-muted">Bot çalışma durumunu izleyebilir ve kontrol edebilirsiniz. Tüm sistem parametreleri ve loglar burada görüntülenmektedir.</p>
                </div>
            
                <!-- Bot Durum Kartı -->
                <div class="status-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-robot"></i> Bot Durumu</h5>
                        <button class="refresh-btn" id="refresh-status">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="status-icon text-<?php echo $status_class; ?>">
                            <i class="fas fa-<?php echo $status_icon; ?>"></i>
                        </div>
                        <h3 class="text-<?php echo $status_class; ?> mt-3">
                            <?php echo $running ? 'Bot Çalışıyor' : 'Bot Çalışmıyor'; ?>
                        </h3>
                        <p class="text-muted mt-2"><?php echo $status_message; ?></p>
                        
                        <!-- Bot İstatistikleri -->
                        <div class="bot-stats">
                            <div class="stat-item">
                                <span class="label"><i class="fas fa-clock"></i> Çalışma Süresi</span>
                                <span class="value"><?php echo $running ? $uptime : '--:--:--'; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="label"><i class="fas fa-memory"></i> Bellek Kullanımı</span>
                                <span class="value"><?php echo $running ? $memory : '0%'; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="label"><i class="fas fa-microchip"></i> CPU Kullanımı</span>
                                <span class="value"><?php echo $running ? $cpu : '0%'; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="label"><i class="fas fa-sync-alt"></i> Son Güncelleme</span>
                                <span class="value"><?php echo $running ? $last_update_text : '--'; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="label"><i class="fas fa-exchange-alt"></i> Günlük İşlemler</span>
                                <span class="value"><?php echo $trade_count; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="label"><i class="fas fa-chart-line"></i> Günlük K/Z</span>
                                <span class="value <?php echo $profit_today >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $profit_today >= 0 ? '+' : ''; ?><?php echo number_format($profit_today, 2); ?> USDT
                                </span>
                            </div>
                        </div>
                        
                        <!-- Bot Kontrol Butonları -->
                        <div class="bot-controls">
                            <?php if ($running): ?>
                                <button class="btn btn-danger bot-control" data-action="stop">
                                    <i class="fas fa-stop-circle"></i> Botu Durdur
                                </button>
                                <button class="btn btn-info bot-control" data-action="restart">
                                    <i class="fas fa-sync-alt"></i> Yeniden Başlat
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success bot-control" data-action="start">
                                    <i class="fas fa-play-circle"></i> Botu Başlat
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Bot Log Bilgileri -->
                    <div class="col-md-6">
                        <div class="status-card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-file-alt"></i> Bot Logları</h5>
                                <button class="refresh-btn" id="refresh-logs">
                                    <i class="fas fa-sync-alt"></i> Yenile
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="log-container dark" id="log-container">
                                    <?php if (!empty($last_logs)): ?>
                                        <?php foreach ($last_logs as $log): ?>
                                            <div class="log-line">
                                                <?php 
                                                    // Log satırını renklendir
                                                    $log_line = htmlspecialchars($log);
                                                    if (stripos($log, 'error') !== false || stripos($log, 'exception') !== false) {
                                                        echo '<span class="error">' . $log_line . '</span>';
                                                    } elseif (stripos($log, 'warning') !== false) {
                                                        echo '<span class="warning">' . $log_line . '</span>';
                                                    } elseif (stripos($log, 'info') !== false || stripos($log, 'success') !== false) {
                                                        echo '<span class="info">' . $log_line . '</span>';
                                                    } else {
                                                        echo $log_line;
                                                    }
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="log-line info">Log kaydı bulunamadı veya bot henüz başlatılmadı.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bot Hata Bilgileri -->
                    <div class="col-md-6">
                        <div class="status-card mb-4">
                            <div class="card-header" style="background: linear-gradient(135deg, var(--danger), #e83e8c);">
                                <h5><i class="fas fa-exclamation-triangle"></i> Hata Logları</h5>
                                <button class="refresh-btn" id="refresh-errors">
                                    <i class="fas fa-sync-alt"></i> Yenile
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="log-container dark" id="error-container">
                                    <?php if (!empty($last_errors)): ?>
                                        <?php foreach ($last_errors as $error): ?>
                                            <div class="log-line error"><?php echo htmlspecialchars($error); ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="log-line">Hata kaydı bulunamadı.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bot Teknik Detayları -->
                <div class="status-card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--secondary), #858796);">
                        <h5><i class="fas fa-terminal"></i> Teknik Detaylar</h5>
                    </div>
                    <div class="card-body">
                        <div class="service-status-container">
                            <!-- PID Bilgileri -->
                            <div class="service-status-card">
                                <h6><i class="fas fa-id-card"></i> PID Bilgileri</h6>
                                <table class="table tech-info-table">
                                    <tbody>
                                        <tr>
                                            <th>PID Dosyası</th>
                                            <td>
                                                <?php echo $pid_exists ? 
                                                    '<span class="badge badge-success">Mevcut</span>' : 
                                                    '<span class="badge badge-danger">Bulunamadı</span>'; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>PID Değeri</th>
                                            <td><?php echo $pid_content; ?></td>
                                        </tr>
                                        <tr>
                                            <th>PID Durumu</th>
                                            <td>
                                                <?php if (!empty($pid) && $pid != 'Yok' && is_numeric($pid)): ?>
                                                    <?php 
                                                        exec("ps -p $pid > /dev/null 2>&1", $null, $pid_exists);
                                                        echo ($pid_exists === 0) ? 
                                                            '<span class="badge badge-success">Çalışıyor</span>' : 
                                                            '<span class="badge badge-danger">Çalışmıyor</span>';
                                                    ?>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Geçersiz PID</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Servis Bilgileri -->
                            <div class="service-status-card">
                                <h6><i class="fas fa-server"></i> Servis Bilgileri</h6>
                                <table class="table tech-info-table">
                                    <tbody>
                                        <tr>
                                            <th>Bot Python Dosyası</th>
                                            <td>
                                                <?php 
                                                    $bot_file = "$bot_dir/trading_bot.py";
                                                    $file_exists = file_exists($bot_file);
                                                    echo $file_exists ? 
                                                        '<span class="badge badge-success">Mevcut</span>' : 
                                                        '<span class="badge badge-danger">Bulunamadı</span>'; 
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Yapılandırma Dosyası</th>
                                            <td>
                                                <?php 
                                                    $config_file = "$bot_dir/config/bot_config.json";
                                                    $config_exists = file_exists($config_file);
                                                    echo $config_exists ? 
                                                        '<span class="badge badge-success">Mevcut</span>' : 
                                                        '<span class="badge badge-danger">Bulunamadı</span>'; 
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Çalışan Bot Süreçleri</th>
                                            <td>
                                                <?php
                                                    exec("ps -ef | grep trading_bot.py | grep -v grep | wc -l", $process_count);
                                                    $count = (int)trim($process_count[0] ?? '0');
                                                    
                                                    if ($count > 0) {
                                                        echo '<span class="badge badge-success">' . $count . ' süreç çalışıyor</span>';
                                                    } else {
                                                        echo '<span class="badge badge-danger">Süreç bulunamadı</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gelişmiş Kontroller -->
                <div class="status-card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #6c6ce5, #8a8aff);">
                        <h5><i class="fas fa-tools"></i> Gelişmiş Kontroller</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-broom text-warning"></i> Bakım İşlemleri</h5>
                                        <p class="card-text small text-muted">Bot sisteminde bakım işlemleri gerçekleştirin.</p>
                                        <div class="btn-group w-100">
                                            <button class="btn btn-outline-primary advanced-action" data-action="clean_logs">
                                                <i class="fas fa-file-medical"></i> Logları Temizle
                                            </button>
                                            <button class="btn btn-outline-warning advanced-action" data-action="check_integrity">
                                                <i class="fas fa-shield-alt"></i> Sistem Kontrolü
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-chart-bar text-info"></i> Performans</h5>
                                        <p class="card-text small text-muted">Bot performansını optimize etmek için kullanın.</p>
                                        <div class="btn-group w-100">
                                            <button class="btn btn-outline-success advanced-action" data-action="optimize">
                                                <i class="fas fa-tachometer-alt"></i> Performans Optimizasyonu
                                            </button>
                                            <button class="btn btn-outline-info advanced-action" data-action="diagnostics">
                                                <i class="fas fa-microscope"></i> Tanılama Raporu
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="alert alert-warning small" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Dikkat!</strong> Gelişmiş kontroller sisteminizde beklenmedik değişikliklere neden olabilir. Sadece ne yaptığınızı biliyorsanız kullanın.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Otomatik Yenileme Göstergesi -->
    <div class="auto-refresh-indicator" id="auto-refresh-indicator">
        <div class="pulse"></div>
        <span id="refresh-countdown">60</span> saniye içinde yenilenecek
    </div>
    
    <!-- Bot İşlem Modalı -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Bot Durumu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">İşlem yapılıyor...</span>
                        </div>
                        <p class="mt-3">Lütfen bekleyin, işlem gerçekleştiriliyor...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script Bölümü -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Otomatik yenileme
            let refreshInterval;
            let countdownTimer;
            let refreshTime = 60; // saniye
            let remainingTime = refreshTime;
            
            function startAutoRefresh() {
                // Sayaç başlat
                countdownTimer = setInterval(function() {
                    remainingTime--;
                    $("#refresh-countdown").text(remainingTime);
                    
                    if (remainingTime <= 0) {
                        location.reload();
                    }
                }, 1000);
            }
            
            function stopAutoRefresh() {
                clearInterval(countdownTimer);
                $("#auto-refresh-indicator").hide();
            }
            
            // Sayfa yüklendiğinde oto-yenileme başlat
            startAutoRefresh();
            
            // Log yenileme işlemleri
            $("#refresh-logs").click(function() {
                $(this).find('i').addClass('fa-spin');
                
                $.ajax({
                    url: "api/get_logs.php",
                    success: function(data) {
                        $("#log-container").html(data);
                    },
                    complete: function() {
                        $("#refresh-logs").find('i').removeClass('fa-spin');
                        
                        // Animasyon ile güncelleme efekti
                        $("#log-container").addClass("flash-update");
                        setTimeout(function() {
                            $("#log-container").removeClass("flash-update");
                        }, 500);
                    }
                });
            });
            
            // Hata logları yenileme
            $("#refresh-errors").click(function() {
                $(this).find('i').addClass('fa-spin');
                
                $.ajax({
                    url: "api/get_logs.php?type=error",
                    success: function(data) {
                        $("#error-container").html(data);
                    },
                    complete: function() {
                        $("#refresh-errors").find('i').removeClass('fa-spin');
                        
                        // Animasyon ile güncelleme efekti
                        $("#error-container").addClass("flash-update");
                        setTimeout(function() {
                            $("#error-container").removeClass("flash-update");
                        }, 500);
                    }
                });
            });
            
            // Sayfa yenileme
            $("#refresh-status").click(function() {
                // Dönüş animasyonu
                $(this).find('i').addClass('fa-spin');
                
                // 1 saniye sonra sayfayı yenile
                setTimeout(function() {
                    location.reload();
                }, 800);
            });

            // Bot durumunu kontrol eden fonksiyon
            function checkBotStatus() {
                return $.ajax({
                    url: "bot_control.php",
                    method: "GET",
                    data: {
                        action: "status",
                        no_auth_check: "1"
                    },
                    dataType: "json"
                });
            }
            
            // Bot Kontrol butonları
            $(".bot-control").click(function() {
                const action = $(this).data("action");
                
                // Tüm kontrol butonlarını devre dışı bırak
                $(".bot-control").prop("disabled", true);
                
                // İşlem adını ayarla
                let actionText = "İşlem Yapılıyor";
                if (action === "start") actionText = "Bot Başlatılıyor";
                else if (action === "stop") actionText = "Bot Durduruluyor";
                else if (action === "restart") actionText = "Bot Yeniden Başlatılıyor";
                
                // Modal başlığını güncelle
                $("#modalTitle").text(actionText);
                
                // Modal içeriğini yükleme animasyonu ile güncelle
                $("#modalBody").html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">İşlem yapılıyor...</span>
                        </div>
                        <p class="mt-3">${actionText}, lütfen bekleyin...</p>
                    </div>
                `);
                
                // Modal'ı göster
                $("#statusModal").modal('show');
                
                // Bot kontrol API'sine istek gönder
                $.ajax({
                    url: "bot_control.php",
                    method: "POST",
                    data: {
                        action: action
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            if (action === "start") {
                                // Bot başlatma işlemi başarılı olduğunda, durum kontrolünü başlat
                                let checkAttempts = 0;
                                const maxAttempts = 30; // Maksimum 30 kontrol (30 saniye)
                                
                                $("#modalBody").html(`
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Bot başlatılıyor...</span>
                                        </div>
                                        <p class="mt-3">Bot başlatma komutu gönderildi. Bot başlatılıyor, lütfen bekleyin...</p>
                                        <div class="mt-3">
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="startProgress"></div>
                                            </div>
                                        </div>
                                    </div>
                                `);
                                
                                // İlerleme çubuğu başlangıç değeri
                                let progress = 0;
                                
                                // Bot durumunu periyodik olarak kontrol et
                                const statusCheckInterval = setInterval(function() {
                                    checkAttempts++;
                                    
                                    // İlerleme çubuğunu güncelle
                                    progress = (checkAttempts / maxAttempts) * 100;
                                    $("#startProgress").css("width", progress + "%");
                                    
                                    checkBotStatus().then(function(statusResponse) {
                                        if (statusResponse.running) {
                                            // Bot çalışmaya başladı - kontrolü durdur ve başarı mesajını göster
                                            clearInterval(statusCheckInterval);
                                            
                                            $("#modalBody").html(`
                                                <div class="text-center py-4">
                                                    <div class="mb-3">
                                                        <i class="fas fa-check-circle fa-4x text-success"></i>
                                                    </div>
                                                    <h5 class="text-success">Bot Başarıyla Başlatıldı!</h5>
                                                    <p class="mt-3">Bot şu anda aktif olarak çalışıyor.</p>
                                                </div>
                                            `);
                                            
                                            // Sayfayı 2 saniye sonra yenile
                                            setTimeout(function() {
                                                location.reload();
                                            }, 2000);
                                        } else if (checkAttempts >= maxAttempts) {
                                            // Maksimum deneme sayısına ulaşıldı, ancak bot çalışmıyor
                                            clearInterval(statusCheckInterval);
                                            
                                            $("#modalBody").html(`
                                                <div class="text-center py-4">
                                                    <div class="mb-3">
                                                        <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                                                    </div>
                                                    <h5 class="text-warning">Başlatma Zaman Aşımı</h5>
                                                    <p class="mt-3">Bot başlatma komutu gönderildi, ancak bot henüz çalışmaya başlamadı. Lütfen loglarda hata olup olmadığını kontrol edin.</p>
                                                </div>
                                            `);
                                            
                                            // Sayfayı 5 saniye sonra yenile
                                            setTimeout(function() {
                                                location.reload();
                                            }, 5000);
                                        }
                                    }).catch(function() {
                                        // Hata olursa bir şey yapma - kontrole devam et
                                    });
                                }, 1000); // Her saniye kontrol et
                            } else {
                                // Diğer işlemler için normal başarı mesajı göster
                                $("#modalBody").html(`
                                    <div class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle fa-4x text-success"></i>
                                        </div>
                                        <h5 class="text-success">İşlem Başarılı</h5>
                                        <p class="mt-3">${response.message}</p>
                                    </div>
                                `);
                                
                                // Durdurma veya yeniden başlatma işlemlerinde sayfayı yenile
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            }
                        } else {
                            // İşlem başarısız
                            $("#modalBody").html(`
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
                                    </div>
                                    <h5 class="text-danger">İşlem Başarısız</h5>
                                    <p class="mt-3">${response.message || "Bir hata oluştu."}</p>
                                </div>
                            `);
                            
                            // Butonları tekrar etkinleştir
                            setTimeout(function() {
                                $(".bot-control").prop("disabled", false);
                                $("#statusModal").modal('hide');
                            }, 3000);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hata durumunda bildirim
                        $("#modalBody").html(`
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-exclamation-circle fa-4x text-danger"></i>
                                </div>
                                <h5 class="text-danger">Bağlantı Hatası</h5>
                                <p class="mt-3">İşlem gerçekleştirilirken bir hata oluştu. Lütfen tekrar deneyin.</p>
                                <div class="mt-3 small text-muted">
                                    Teknik detay: ${error || "Bağlantı hatası"}
                                </div>
                            </div>
                        `);
                        
                        // Butonları tekrar etkinleştir
                        setTimeout(function() {
                            $(".bot-control").prop("disabled", false);
                        }, 3000);
                    }
                });
            });
            
            // Gelişmiş kontrol butonları
            $(".advanced-action").click(function() {
                const action = $(this).data("action");
                let actionText = "İşlem Yapılıyor";
                
                switch(action) {
                    case "clean_logs":
                        actionText = "Log Dosyaları Temizleniyor";
                        break;
                    case "check_integrity":
                        actionText = "Sistem Bütünlüğü Kontrol Ediliyor";
                        break;
                    case "optimize":
                        actionText = "Performans Optimizasyonu Yapılıyor";
                        break;
                    case "diagnostics":
                        actionText = "Tanılama Raporu Oluşturuluyor";
                        break;
                }
                
                // Butonları devre dışı bırak
                $(".advanced-action").prop("disabled", true);
                $(this).html('<i class="fas fa-spinner fa-spin"></i> İşleniyor...');
                
                // Modal başlığını güncelle
                $("#modalTitle").text(actionText);
                
                // Modal içeriğini yükleme animasyonu ile güncelle
                $("#modalBody").html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">İşlem yapılıyor...</span>
                        </div>
                        <p class="mt-3">${actionText}, lütfen bekleyin...</p>
                    </div>
                `);
                
                // Modal'ı göster
                $("#statusModal").modal('show');
                
                // Bot gelişmiş kontrol API'sine istek gönder
                $.ajax({
                    url: "advanced_control.php",
                    method: "POST",
                    data: {
                        action: action
                    },
                    dataType: "json",
                    success: function(response) {
                        // Başarı/hata durumuna göre içeriği güncelle
                        const statusClass = response && response.success ? "text-success" : "text-danger";
                        const icon = response && response.success ? "check-circle" : "exclamation-triangle";
                        const message = response ? (response.message || "İşlem tamamlandı.") : "İşlem sırasında bir hata oluştu.";
                        
                        $("#modalBody").html(`
                            <div class="text-center py-3">
                                <div class="mb-3">
                                    <i class="fas fa-${icon} fa-3x ${statusClass}"></i>
                                </div>
                                <h5 class="${statusClass}">${response && response.success ? "İşlem Başarılı" : "İşlem Başarısız"}</h5>
                                <p class="mt-3">${message}</p>
                                ${response && response.details ? `<div class="mt-3 p-3 bg-light text-left small" style="max-height:200px;overflow-y:auto;border-radius:5px;">${response.details}</div>` : ''}
                            </div>
                        `);
                        
                        // Butonları tekrar etkinleştir
                        $(".advanced-action").prop("disabled", false);
                        $(".advanced-action").each(function() {
                            const btnAction = $(this).data("action");
                            let btnIcon = "fas fa-cog";
                            let btnText = "Aksiyon";
                            
                            switch(btnAction) {
                                case "clean_logs":
                                    btnIcon = "fas fa-file-medical";
                                    btnText = "Logları Temizle";
                                    break;
                                case "check_integrity":
                                    btnIcon = "fas fa-shield-alt";
                                    btnText = "Sistem Kontrolü";
                                    break;
                                case "optimize":
                                    btnIcon = "fas fa-tachometer-alt";
                                    btnText = "Performans Optimizasyonu";
                                    break;
                                case "diagnostics":
                                    btnIcon = "fas fa-microscope";
                                    btnText = "Tanılama Raporu";
                                    break;
                            }
                            
                            $(this).html(`<i class="${btnIcon}"></i> ${btnText}`);
                        });
                    },
                    error: function() {
                        // Hata durumunda bildirim
                        $("#modalBody").html(`
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-exclamation-circle fa-3x text-danger"></i>
                                </div>
                                <h5 class="text-danger">Bağlantı Hatası</h5>
                                <p class="mt-3">İşlem gerçekleştirilirken bir hata oluştu. Lütfen tekrar deneyin.</p>
                            </div>
                        `);
                        
                        // Butonları tekrar etkinleştir
                        $(".advanced-action").prop("disabled", false);
                        $(".advanced-action").each(function() {
                            const btnAction = $(this).data("action");
                            let btnIcon = "fas fa-cog";
                            let btnText = "Aksiyon";
                            
                            switch(btnAction) {
                                case "clean_logs":
                                    btnIcon = "fas fa-file-medical";
                                    btnText = "Logları Temizle";
                                    break;
                                case "check_integrity":
                                    btnIcon = "fas fa-shield-alt";
                                    btnText = "Sistem Kontrolü";
                                    break;
                                case "optimize":
                                    btnIcon = "fas fa-tachometer-alt";
                                    btnText = "Performans Optimizasyonu";
                                    break;
                                case "diagnostics":
                                    btnIcon = "fas fa-microscope";
                                    btnText = "Tanılama Raporu";
                                    break;
                            }
                            
                            $(this).html(`<i class="${btnIcon}"></i> ${btnText}`);
                        });
                    }
                });
            });
            
            // Otomatik yenileme göstergesini tıklama ile kapatma/açma
            $("#auto-refresh-indicator").click(function() {
                const isActive = $(this).data("active") !== false;
                
                if (isActive) {
                    // Durdur
                    stopAutoRefresh();
                    $(this).data("active", false);
                    $(this).html('<i class="fas fa-clock"></i> Otomatik yenileme kapalı');
                    $(this).addClass('bg-warning text-dark');
                    $(this).find('.pulse').hide();
                } else {
                    // Yeniden başlat
                    remainingTime = refreshTime;
                    startAutoRefresh();
                    $(this).data("active", true);
                    $(this).html('<div class="pulse"></div><span id="refresh-countdown">' + refreshTime + '</span> saniye içinde yenilenecek');
                    $(this).removeClass('bg-warning text-dark');
                }
            });
            
            // İlk yüklenmede log konteynerlerini aşağı kaydır
            $("#log-container, #error-container").each(function() {
                $(this).scrollTop($(this)[0].scrollHeight);
            });
        });
    </script>
</body>
</html>
