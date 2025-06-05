<?php
/**
 * Bot Zorla Başlatma ve Durum Kontrol Sayfası
 * Bu dosya, tradingbot'u zorla başlatmak ve durumunu kontrol etmek için kullanılır.
 */

// Maksimum çalışma süresi (saniye olarak)
ini_set('max_execution_time', 300);

// Oturum gerekli değil - direkt erişim için

// Başlatma işlemi yapılacak mı?
$action = isset($_GET['action']) ? $_GET['action'] : 'status';
$stdout = [];
$stderr = [];
$return_var = 0;
$output = [];
$bot_status = [];

// Çalıştırma fonksiyonu
function execCommand($command) {
    global $stdout, $stderr, $return_var, $output;
    $descriptors = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w']   // stderr
    ];
    
    $process = proc_open($command, $descriptors, $pipes);
    
    if (is_resource($process)) {
        // Çıktıları oku
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        // Pipe'ları kapat
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // Süreç sonucunu al
        $return_var = proc_close($process);
        
        $output = [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'return' => $return_var
        ];
        
        return $return_var === 0;
    }
    
    return false;
}

// Botun durumunu kontrol et
function checkBotStatus() {
    global $bot_status;
    
    // Bot PID dosyası
    $pid_file = "/var/www/html/bot/bot.pid";
    $log_file = "/var/www/html/bot/bot.log";
    $error_log = "/var/www/html/bot/bot_error.log";
    
    $bot_status = [
        'running' => false,
        'pid' => 0,
        'log_exists' => false,
        'error_log_exists' => false,
        'pid_file_exists' => false,
        'last_log' => '',
        'last_error' => '',
        'processes' => [],
        'real_processes' => []
    ];
    
    // PID dosyası kontrolü
    if (file_exists($pid_file)) {
        $bot_status['pid_file_exists'] = true;
        $pid = trim(file_get_contents($pid_file));
        $bot_status['pid'] = $pid;
        
        // PID'nin çalışıp çalışmadığını kontrol et
        exec("ps -p $pid", $ps_output, $ps_return);
        $bot_status['running'] = ($ps_return === 0);
    }
    
    // Log dosyası kontrolü
    if (file_exists($log_file)) {
        $bot_status['log_exists'] = true;
        $bot_status['last_log'] = shell_exec("tail -n 20 $log_file");
    }
    
    // Hata log dosyası kontrolü  
    if (file_exists($error_log)) {
        $bot_status['error_log_exists'] = true;
        $bot_status['last_error'] = shell_exec("tail -n 20 $error_log");
    }
    
    // Çalışan TÜM bot süreçlerini kontrol et
    exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $ps_output);
    $bot_status['processes'] = $ps_output;
    
    // Sadece tradingbot kullanıcısına ait süreçleri kontrol et
    exec("ps -U tradingbot -u tradingbot | grep 'trading_bot.py' | grep -v grep", $real_processes, $real_return);
    $bot_status['real_processes'] = $real_processes;
    
    // Gerçekten tradingbot kullanıcısıyla çalışan bir süreç varsa, durum çalışıyordur
    if (!empty($real_processes)) {
        $bot_status['running'] = true;
        
        // Eğer doğru PID yoksa, gerçek süreci PID olarak güncelle
        if (empty($bot_status['pid']) || $bot_status['pid'] == 0) {
            preg_match('/^\s*(\d+)/', $real_processes[0], $matches);
            if (!empty($matches[1])) {
                $real_pid = $matches[1];
                $bot_status['pid'] = $real_pid;
                // PID dosyasını güncelle
                @file_put_contents($pid_file, $real_pid);
            }
        }
    }
    
    return $bot_status;
}

// Botun başlatma izinlerini düzenle
function fixBotPermissions() {
    global $output;
    
    $bot_dir = "/var/www/html/bot";
    $log_file = "$bot_dir/bot.log";
    $error_log = "$bot_dir/bot_error.log";
    $pid_file = "$bot_dir/bot.pid";
    $web_user = getWebServerUser();
    
    // Apache/web sunucusu kullanıcısını al
    execCommand("echo 'Web sunucusu kullanıcısı: $web_user' >> $error_log");

    // Web kullanıcısını ve tradingbot kullanıcısını aynı gruba ekle
    execCommand("groupadd -f botgroup 2>/dev/null || true");
    execCommand("usermod -a -G botgroup tradingbot 2>/dev/null || true");
    execCommand("usermod -a -G botgroup $web_user 2>/dev/null || true");
    
    // İlgili dizinler için grup izinlerini ayarla
    execCommand("chgrp -R botgroup $bot_dir 2>/dev/null || true");
    execCommand("chmod -R 775 $bot_dir 2>/dev/null || true");
    
    // Herkesin yazabileceği log dosyaları
    execCommand("touch $log_file $error_log $pid_file 2>/dev/null");
    execCommand("chmod 666 $log_file $error_log $pid_file 2>/dev/null || true");
    
    // Config dizinini düzenle
    if (is_dir("$bot_dir/config")) {
        execCommand("chgrp -R botgroup $bot_dir/config 2>/dev/null || true");
        execCommand("chmod -R 775 $bot_dir/config 2>/dev/null || true");
        execCommand("chmod 664 $bot_dir/config/*.json 2>/dev/null || true");
        execCommand("find $bot_dir/config -type f -name '*.json' -exec chmod 664 {} \\; 2>/dev/null || true");
    }
    
    // Tüm üst dizinlerin izinlerini ayarla (bu sayede apache erişebilir)
    execCommand("chmod 755 /var/www/html 2>/dev/null || true");
    
    // Sonuçları kaydet
    return $output;
}

// Web sunucusu kullanıcısını tespit et
function getWebServerUser() {
    // Önce yaygın kullanıcıları kontrol et
    $common_users = ['www-data', 'apache', 'httpd', 'nginx', 'nobody', 'webserver'];
    
    foreach ($common_users as $user) {
        exec("id $user 2>/dev/null", $output, $return);
        if ($return === 0) {
            return $user;
        }
    }
    
    // Süreç bilgisinden çıkarmaya çalış
    exec("ps aux | grep -E 'apache|httpd|nginx' | grep -v 'root' | head -1 | awk '{print $1}'", $output);
    if (!empty($output[0]) && $output[0] !== 'root') {
        return trim($output[0]);
    }
    
    // Son çare olarak www-data dön
    return 'www-data';
}

// Botu zorla başlat
function forceBotStart() {
    global $output;
    
    // Önce izinleri düzelt
    fixBotPermissions();
    
    // Çalışan bot varsa durdur
    execCommand("pkill -f 'trading_bot.py' 2>/dev/null || true");
    sleep(1);
    
    // Bot dizini
    $bot_dir = "/var/www/html/bot";
    $log_file = "$bot_dir/bot.log";
    $error_log = "$bot_dir/bot_error.log";
    $pid_file = "$bot_dir/bot.pid";
    
    // Log dosyasını temizle
    execCommand("echo '=== Bot başlatılıyor: " . date('Y-m-d H:i:s') . " ===' > $log_file");
    execCommand("echo '=== Bot başlatılıyor: " . date('Y-m-d H:i:s') . " ===' > $error_log");
    
    // Python yolu
    $python_path = "/usr/local/bin/python3.8";
    
    // Bot başlatma denemesi: Üç farklı yöntem kullanacağız ve en az biri çalışacak
    
    // YÖNTEM 1 - Basit bir betik ile doğrudan başlatma
    $simple_script = <<<EOT
#!/bin/bash
cd $bot_dir
$python_path trading_bot.py >> $log_file 2>> $error_log &
echo \$! > $pid_file
chmod 666 $pid_file
EOT;
    
    file_put_contents("/tmp/start_bot_simple.sh", $simple_script);
    execCommand("chmod +x /tmp/start_bot_simple.sh");
    execCommand("chown tradingbot:tradingbot /tmp/start_bot_simple.sh");
    
    // YÖNTEM 2 - Nohup ile başlatma
    $nohup_script = <<<EOT
#!/bin/bash
cd $bot_dir
nohup $python_path trading_bot.py >> $log_file 2>> $error_log &
echo \$! > $pid_file
chmod 666 $pid_file
EOT;
    
    file_put_contents("/tmp/start_bot_nohup.sh", $nohup_script);
    execCommand("chmod +x /tmp/start_bot_nohup.sh");
    execCommand("chown tradingbot:tradingbot /tmp/start_bot_nohup.sh");
    
    // YÖNTEM 3 - Screen ile başlatma (daha güvenilir)
    $screen_script = <<<EOT
#!/bin/bash
cd $bot_dir
screen -dmS trading_bot $python_path trading_bot.py
PID=\$(screen -ls | grep trading_bot | grep -o '[0-9]*' | head -n1)
echo \$PID > $pid_file
chmod 666 $pid_file
EOT;
    
    file_put_contents("/tmp/start_bot_screen.sh", $screen_script);
    execCommand("chmod +x /tmp/start_bot_screen.sh");
    execCommand("chown tradingbot:tradingbot /tmp/start_bot_screen.sh");
    
    // Yöntem 1'i dene
    execCommand("su - tradingbot -c '/tmp/start_bot_simple.sh'");
    sleep(2);
    
    // Bot çalışıyor mu kontrol et
    exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $check1);
    $output['method1_result'] = !empty($check1);
    
    if (empty($check1)) {
        // Yöntem 2'yi dene
        execCommand("su - tradingbot -c '/tmp/start_bot_nohup.sh'");
        sleep(2);
        
        // Bot çalışıyor mu kontrol et
        exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $check2);
        $output['method2_result'] = !empty($check2);
        
        if (empty($check2)) {
            // Screen yüklü mü kontrol et ve gerekirse kur
            execCommand("which screen || apt-get install -y screen");
            
            // Yöntem 3'ü dene
            execCommand("su - tradingbot -c '/tmp/start_bot_screen.sh'");
            sleep(2);
            
            // Bot çalışıyor mu kontrol et
            exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $check3);
            $output['method3_result'] = !empty($check3);
        }
    }
    
    // Son bir kontrol daha yap
    sleep(2);
    exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $final_check);
    $running = !empty($final_check);
    
    // PID tespit et
    $pid = 0;
    if ($running && !empty($final_check[0])) {
        preg_match('/^\s*\S+\s+(\d+)/', $final_check[0], $matches);
        if (!empty($matches[1])) {
            $pid = $matches[1];
            @file_put_contents($pid_file, $pid);
            execCommand("chmod 666 $pid_file 2>/dev/null || true");
        }
    } else {
        // Acil durum çözümü - bot.py kullan
        execCommand("echo '=== Acil durum çözümü - bot.py kullanılıyor: " . date('Y-m-d H:i:s') . " ===' >> $error_log");
        execCommand("cd $bot_dir && $python_path bot.py >> $log_file 2>> $error_log &");
        sleep(2);
        
        // bot.py çalışıyor mu kontrol et
        exec("ps -ef | grep 'bot.py' | grep -v grep", $bot_py_check);
        $output['bot_py_result'] = !empty($bot_py_check);
        
        if (!empty($bot_py_check) && !empty($bot_py_check[0])) {
            $running = true;
            preg_match('/^\s*\S+\s+(\d+)/', $bot_py_check[0], $matches);
            if (!empty($matches[1])) {
                $pid = $matches[1];
                @file_put_contents($pid_file, $pid);
                execCommand("chmod 666 $pid_file 2>/dev/null || true");
            }
        }
    }
    
    // Son durum kontrolleri
    $output['final_check'] = $final_check;
    $output['pid'] = $pid;
    $output['running'] = $running;
    
    // Log dosyalarını kontrol et
    $last_log = shell_exec("tail -n 20 $log_file 2>/dev/null");
    $last_error = shell_exec("tail -n 20 $error_log 2>/dev/null");
    $output['last_log'] = $last_log;
    $output['last_error'] = $last_error;
    
    return [
        'running' => $running,
        'pid' => $pid,
        'output' => $output,
        'processes' => $final_check
    ];
}

// Botu durdur
function stopBot() {
    global $output;
    
    // Çalışan tüm bot süreçlerini bul ve durdur
    execCommand("pkill -f 'trading_bot.py' 2>/dev/null || true");
    
    // PID dosyasını temizle
    $pid_file = "/var/www/html/bot/bot.pid";
    if (file_exists($pid_file)) {
        @unlink($pid_file);
    }
    
    return $output;
}

// Config dosyaları için alternatif yaklaşım
function createConfigCopies() {
    global $output;
    
    $bot_dir = "/var/www/html/bot";
    $config_dir = "$bot_dir/config";
    $web_user = getWebServerUser();
    
    // Config dizininin varlığını kontrol et ve yoksa oluştur
    if (!is_dir($config_dir)) {
        execCommand("mkdir -p $config_dir 2>/dev/null");
        execCommand("chgrp botgroup $config_dir 2>/dev/null || true");
        execCommand("chmod 775 $config_dir 2>/dev/null || true");
    }
    
    // Bot config dosyalarını temp dosyalar olarak oluştur
    if (!file_exists("$config_dir/api_keys.json")) {
        $api_keys = [
            "binance" => [
                "api_key" => "YOUR_BINANCE_API_KEY",
                "secret" => "YOUR_BINANCE_SECRET"
            ]
        ];
        file_put_contents("/tmp/api_keys.json", json_encode($api_keys, JSON_PRETTY_PRINT));
        execCommand("cp -f /tmp/api_keys.json $config_dir/api_keys.json 2>/dev/null || true");
    }
    
    if (!file_exists("$config_dir/bot_config.json")) {
        $bot_config = [
            "exchange" => "binance",
            "base_currency" => "USDT",
            "min_volume" => 1000000,
            "max_coins" => 10,
            "min_trade_amount" => 10,
            "max_trade_amount" => 100,
            "position_size" => 0.1,
            "api_delay" => 1,
            "scan_interval" => 60,
            "indicators" => [
                "bollinger_bands" => [
                    "enabled" => true,
                    "window" => 20,
                    "num_std" => 2
                ],
                "rsi" => [
                    "enabled" => true,
                    "window" => 14
                ],
                "macd" => [
                    "enabled" => true,
                    "fast_period" => 12,
                    "slow_period" => 26,
                    "signal_period" => 9
                ],
                "moving_average" => [
                    "enabled" => true,
                    "short_window" => 9,
                    "long_window" => 21
                ]
            ],
            "strategies" => [
                "short_term" => [
                    "enabled" => true
                ],
                "trend_following" => [
                    "enabled" => true
                ],
                "breakout" => [
                    "enabled" => true
                ]
            ]
        ];
        file_put_contents("/tmp/bot_config.json", json_encode($bot_config, JSON_PRETTY_PRINT));
        execCommand("cp -f /tmp/bot_config.json $config_dir/bot_config.json 2>/dev/null || true");
    }
    
    if (!file_exists("$config_dir/telegram_config.json")) {
        $telegram_config = [
            "enabled" => false,
            "token" => "YOUR_TELEGRAM_BOT_TOKEN",
            "chat_id" => "YOUR_TELEGRAM_CHAT_ID"
        ];
        file_put_contents("/tmp/telegram_config.json", json_encode($telegram_config, JSON_PRETTY_PRINT));
        execCommand("cp -f /tmp/telegram_config.json $config_dir/telegram_config.json 2>/dev/null || true");
    }
    
    // Tüm config dosyalarına grup yazma erişimi ver 
    execCommand("chmod 664 $config_dir/*.json 2>/dev/null || true");
    execCommand("chgrp botgroup $config_dir/*.json 2>/dev/null || true");
    
    return $output;
}

// Modified Python script with local configs
function createModifiedPythonScript() {
    global $output;
    
    $bot_dir = "/var/www/html/bot";
    $temp_script = "/tmp/trading_bot_temp.py";
    
    // Original script'i oku
    $original_script = @file_get_contents("$bot_dir/trading_bot.py");
    if (!$original_script) {
        return ["error" => "Original bot script could not be read"];
    }
    
    // Config dosya yollarını değiştir - doğrudan yollar yerine相対パス
    $modified_script = str_replace(
        'config_file="../config/bot_config.json"', 
        'config_file="config/bot_config.json"', 
        $original_script
    );
    
    // Düzenlenmiş scripti kaydet
    file_put_contents($temp_script, $modified_script);
    execCommand("cp -f $temp_script $bot_dir/trading_bot.py 2>/dev/null");
    execCommand("chmod 775 $bot_dir/trading_bot.py 2>/dev/null || true");
    
    return $output;
}

// Direct shell script to check and fix permissions
function createPermissionsScript() {
    global $output;
    
    $script_content = '#!/bin/bash
# Bot için grup ve izin ayarları
WEB_USER=$(ps aux | grep -E "apache|httpd|www-data" | grep -v root | head -1 | awk \'{print $1}\')
echo "Web sunucusu kullanıcısı: $WEB_USER"

# Bot grup oluştur
groupadd -f botgroup

# Kullanıcıları gruba ekle
usermod -a -G botgroup tradingbot 2>/dev/null || true
usermod -a -G botgroup $WEB_USER 2>/dev/null || true

# Bot dizini izinleri
BOT_DIR="/var/www/html/bot"
chgrp -R botgroup $BOT_DIR
chmod -R 775 $BOT_DIR

# Log ve PID dosyaları
touch "$BOT_DIR/bot.log" "$BOT_DIR/bot_error.log" "$BOT_DIR/bot.pid"
chmod 666 "$BOT_DIR/bot.log" "$BOT_DIR/bot_error.log" "$BOT_DIR/bot.pid"

# Config dosyaları
find $BOT_DIR/config -type f -name "*.json" -exec chmod 664 {} \;
find $BOT_DIR/config -type f -name "*.json" -exec chgrp botgroup {} \;

echo "İzinler düzenlendi"
exit 0
';
    
    $script_path = "/var/www/html/fix_permissions.sh";
    file_put_contents("/tmp/fix_permissions.sh", $script_content);
    execCommand("cp -f /tmp/fix_permissions.sh $script_path 2>/dev/null");
    execCommand("chmod +x $script_path 2>/dev/null");
    
    // Script'i çalıştırmayı dene
    execCommand("bash $script_path");
    
    return $output;
}

// İşlem yap
switch ($action) {
    case 'start':
        // Config dosyalarını hazırla
        createConfigCopies();
        // Düzenlenmiş Python script
        createModifiedPythonScript();
        // Botu başlat
        $result = forceBotStart();
        sleep(3);
        $bot_status = checkBotStatus();
        break;
        
    case 'stop':
        $result = stopBot();
        sleep(2);
        $bot_status = checkBotStatus();
        break;
        
    case 'fix':
        createConfigCopies();
        createModifiedPythonScript();
        createPermissionsScript(); // Yeni eklenen izin script'ini çalıştır
        $result = fixBotPermissions();
        $bot_status = checkBotStatus();
        break;
        
    case 'status':
    default:
        $bot_status = checkBotStatus();
        break;
}

// HTML çıktısı
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading Bot Kontrol Paneli</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .status-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-running {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-stopped {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 14px;
            border: 1px solid #ddd;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .tab.active {
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background-color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .action-buttons {
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }
        .btn-refresh {
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Trading Bot Kontrol Paneli</h1>
        
        <div class="status-box <?php echo $bot_status['running'] ? 'status-running' : 'status-stopped'; ?>">
            <h2>Bot Durumu: <?php echo $bot_status['running'] ? 'ÇALIŞIYOR' : 'ÇALIŞMIYOR'; ?></h2>
            <?php if ($bot_status['running']): ?>
                <p>PID: <?php echo $bot_status['pid']; ?></p>
            <?php else: ?>
                <p>Bot şu anda çalışmıyor.</p>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <a href="?action=start" class="btn">Bot'u Başlat</a>
            <a href="?action=stop" class="btn btn-danger">Bot'u Durdur</a>
            <a href="?action=fix" class="btn btn-warning">İzinleri Düzelt</a>
            <a href="?action=status" class="btn btn-refresh">Durumu Yenile</a>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="processes">Süreçler</div>
            <div class="tab" data-tab="logs">Log Dosyası</div>
            <div class="tab" data-tab="errors">Hata Logu</div>
            <div class="tab" data-tab="output">Son İşlem Çıktısı</div>
        </div>
        
        <div class="tab-content active" id="processes-tab">
            <h3>Çalışan Bot Süreçleri</h3>
            <?php if (empty($bot_status['processes'])): ?>
                <p>Çalışan bot süreci bulunamadı.</p>
            <?php else: ?>
                <pre><?php echo implode("\n", $bot_status['processes']); ?></pre>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="logs-tab">
            <h3>Son Log Kayıtları</h3>
            <?php if ($bot_status['log_exists']): ?>
                <pre><?php echo htmlspecialchars($bot_status['last_log']); ?></pre>
            <?php else: ?>
                <p>Log dosyası bulunamadı.</p>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="errors-tab">
            <h3>Son Hata Kayıtları</h3>
            <?php if ($bot_status['error_log_exists']): ?>
                <pre><?php echo htmlspecialchars($bot_status['last_error']); ?></pre>
            <?php else: ?>
                <p>Hata log dosyası bulunamadı.</p>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="output-tab">
            <h3>Son İşlem Çıktısı</h3>
            <?php if (!empty($output)): ?>
                <pre><?php print_r($output); ?></pre>
            <?php else: ?>
                <p>Herhangi bir işlem çıktısı yok.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Tab kontrolü
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Tüm tabları ve içerikleri inaktif yap
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Seçilen tabı ve içeriği aktif yap
                    this.classList.add('active');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
        });
    </script>
</body>
</html>