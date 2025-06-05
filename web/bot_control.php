<?php
session_start();

// Giriş kontrolü - no_auth_check parametresi ile atlama seçeneği
$no_auth_check = isset($_GET['no_auth_check']) && $_GET['no_auth_check'] == '1';

if (!$no_auth_check && !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

// Action parametresini hem GET hem POST'tan alabiliriz
$action = '';
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$result = ['success' => false, 'message' => ''];
$debug = [];

// Override işletim sistemi kontrolü - CentOS 7 kullanılıyor
$is_windows = false;

if ($is_windows) {
    // Windows ortamı için ayarlar (kullanılmayacak)
    $python_path = 'python'; // Windows'da genelde Python PATH'te olur
    $script_dir = dirname(dirname(__FILE__)); // İki üst dizin
    $bot_dir = $script_dir . '/bot';
    $log_file = "$bot_dir/bot.log";
    $error_log = "$bot_dir/bot_error.log";
    $pid_file = "$bot_dir/bot.pid";
    $stop_script = "$script_dir/stop_trading_bot.sh";
    $start_script = "$script_dir/start_trading_bot_fixed.sh";
} else {
    // CentOS 7 için ayarlar
    $python_path = '/usr/bin/python3'; // CentOS 7'de python yolu
    $bot_script = 'trading_bot.py';
    $bot_dir = '/var/www/html/bot';
    $script_dir = dirname(dirname(__FILE__));
    $log_file = "$bot_dir/bot.log";
    $error_log = "$bot_dir/bot_error.log";
    $pid_file = "$bot_dir/bot.pid";
    $trading_user = 'apache'; // CentOS 7'de genellikle apache kullanıcısı
    $stop_script = "$script_dir/stop_trading_bot.sh";
    $start_script = "$script_dir/start_trading_bot_fixed.sh";
}

// Bot script adı her platformda aynı
$bot_script = 'trading_bot.py';

// Bot durumunun elle kapatıldığını işaretleyen dosya
$bot_manually_stopped_file = "$bot_dir/bot_manually_stopped";

// Botu durdururken elle kapatıldığını belirten bir dosya oluştur
function markBotManuallyStopped() {
    global $bot_manually_stopped_file;
    file_put_contents($bot_manually_stopped_file, date('Y-m-d H:i:s'));
    @chmod($bot_manually_stopped_file, 0666); // Hata engelleme için @ kullan
}

// Bot elle kapatıldı mı kontrol et
function wasBotManuallyStopped() {
    global $bot_manually_stopped_file;
    return file_exists($bot_manually_stopped_file);
}

// Direkt bot başlatma fonksiyonu - script kullanmadan
function directStartBot() {
    global $python_path, $bot_dir, $bot_script, $log_file, $error_log, $pid_file, $trading_user, $debug;
    
    // 1. Çalışan botu durdur
    killExistingBots();
    
    // 2. Log dosyalarını hazırla
    $current_time = date('Y-m-d H:i:s');
    file_put_contents($log_file, "=== Bot başlatılıyor: $current_time ===\n");
    file_put_contents($error_log, "=== Bot başlatılıyor: $current_time ===\n");
    
    // 3. Gerekli dizinlere ve dosyalara izin ver
    setPermissions();
    
    // 4. Botu başlatma komutu (Python script'i çalıştır)
    $start_cmd = "cd $bot_dir && $python_path $bot_dir/$bot_script >> $log_file 2>> $error_log & echo $! > $pid_file";
    
    exec($start_cmd, $output, $return_var);
    $debug[] = "Bot başlatma çıktısı: " . implode("\n", $output);
    $debug[] = "Bot başlatma dönüş kodu: $return_var";
    
    // Başlatma işleminden sonra kısa bir bekleme
    sleep(2);
    
    // PID'i kontrol et ve güçlendirilmiş kontrol
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        $debug[] = "PID dosyasından okunan: $pid";
        
        // PID var mı kontrol et
        if (!empty($pid)) {
            // Süreç hala çalışıyor mu kontrol et
            exec("ps -p $pid | grep -v PID", $ps_output);
            if (!empty($ps_output)) {
                chmod($pid_file, 0666); // PID dosyasının izinlerini ayarla
                $debug[] = "Bot PID: $pid çalışıyor ve doğrulandı.";
                return ['success' => true, 'pid' => $pid];
            }
        }
    }
    
    // PID dosyası yoksa veya geçerli bir PID içermiyorsa, çalışan trading_bot.py sürecini bulalım
    exec("ps -ef | grep '$bot_script' | grep -v grep | awk '{print $2}' | head -n 1", $pid_output);
    if (!empty($pid_output[0])) {
        $pid = trim($pid_output[0]);
        file_put_contents($pid_file, $pid);
        chmod($pid_file, 0666);
        $debug[] = "Bot PID: $pid bulundu ve kaydedildi.";
        return ['success' => true, 'pid' => $pid];
    }
    
    // Alternatif başlatma metodu - nohup kullanarak
    $nohup_cmd = "cd $bot_dir && nohup $python_path $bot_dir/$bot_script >> $log_file 2>> $error_log &";
    exec($nohup_cmd, $nohup_output, $nohup_return);
    $debug[] = "Nohup başlatma çıktısı: " . implode("\n", $nohup_output);
    $debug[] = "Nohup başlatma dönüş kodu: $nohup_return";
    
    sleep(2);
    
    // PID'i tekrar kontrol et
    exec("ps -ef | grep '$bot_script' | grep -v grep | awk '{print $2}' | head -n 1", $pid_output);
    if (!empty($pid_output[0])) {
        $pid = trim($pid_output[0]);
        file_put_contents($pid_file, $pid);
        chmod($pid_file, 0666);
        $debug[] = "Bot PID (nohup): $pid kaydedildi.";
        return ['success' => true, 'pid' => $pid];
    }
    
    // Son çare - screen ile başlatma dene
    $screen_cmd = "cd $bot_dir && screen -dmS trading_bot $python_path $bot_dir/$bot_script";
    exec($screen_cmd, $screen_output, $screen_return);
    $debug[] = "Screen başlatma çıktısı: " . implode("\n", $screen_output);
    $debug[] = "Screen başlatma dönüş kodu: $screen_return";
    
    sleep(2);
    
    // Son bir kez daha PID kontrolü
    exec("ps -ef | grep '$bot_script' | grep -v grep | awk '{print $2}' | head -n 1", $pid_output);
    if (!empty($pid_output[0])) {
        $pid = trim($pid_output[0]);
        file_put_contents($pid_file, $pid);
        chmod($pid_file, 0666);
        $debug[] = "Bot PID (screen): $pid kaydedildi.";
        return ['success' => true, 'pid' => $pid];
    }
    
    return ['success' => false];
}

// Dosya izinlerini ayarla
function setPermissions() {
    global $bot_dir, $log_file, $error_log, $pid_file, $debug;
    
    // Bot dizini izinlerini ayarla
    @chmod($bot_dir, 0755);
    
    // Log dosyalarının izinlerini ayarla
    @touch($log_file);
    @touch($error_log);
    @touch($pid_file);
    
    @chmod($log_file, 0666);
    @chmod($error_log, 0666);
    @chmod($pid_file, 0666);
    
    // Config dizini izinlerini ayarla
    $config_dir = "$bot_dir/config";
    if (is_dir($config_dir)) {
        @chmod($config_dir, 0755);
        $config_files = glob("$config_dir/*.json");
        foreach ($config_files as $file) {
            @chmod($file, 0644);
        }
    }
    
    $debug[] = "Dosya izinleri ayarlandı.";
}

// Mevcut botları kontrol et ve durdur
function killExistingBots() {
    global $debug, $stop_script;
    
    if (file_exists($stop_script) && is_executable($stop_script)) {
        exec($stop_script . " 2>&1", $script_output, $script_return);
        $debug[] = "Stop script çıktısı: " . implode("\n", $script_output);
        $debug[] = "Stop script dönüş kodu: $script_return";
        
        if ($script_return === 0) {
            return 1; // başarılı durdurma
        }
    }
    
    exec("ps -ef | grep trading_bot.py | grep -v grep", $output, $return_var);
    $debug[] = "Mevcut bot süreçleri:\n" . implode("\n", $output);
    
    $killed = 0;
    
    foreach ($output as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (isset($parts[1]) && is_numeric($parts[1])) {
            $pid = $parts[1];
            exec("kill $pid 2>/dev/null", $kill_output, $kill_return);
            $debug[] = "Süreç durduruldu: PID $pid, dönüş kodu: $kill_return";
            $killed++;
            
            sleep(1);
            exec("ps -p $pid > /dev/null 2>&1", $check_output, $still_running);
            if ($still_running == 0) {
                exec("kill -15 $pid 2>/dev/null");
                $debug[] = "SIGTERM gönderildi: PID $pid";
                
                sleep(1);
                exec("ps -p $pid > /dev/null 2>&1", $check_output, $still_running);
                if ($still_running == 0) {
                    exec("kill -9 $pid 2>/dev/null");
                    $debug[] = "SIGKILL gönderildi: PID $pid";
                }
            }
        }
    }
    
    return $killed;
}

// Bot durumunu kontrol et
function checkBotStatus() {
    global $pid_file, $debug, $bot_manually_stopped_file;
    
    $status = [
        'running' => false,
        'pid' => 0,
        'uptime' => '',
        'cpu' => '',
        'memory' => ''
    ];
    
    // Eğer bot elle durdurulduysa ve otomatik olarak tekrar başlatılmışsa, durumu doğru şekilde yansıt
    if (file_exists($bot_manually_stopped_file)) {
        $debug[] = "Bot elle durdurulmuş durumda, otomatik başlatmayı engelle.";
        
        // Çalışan bot süreçlerini durdur
        killExistingBots();
        
        // PID dosyasını sil
        if (file_exists($pid_file)) {
            @unlink($pid_file);
            $debug[] = "PID dosyası silindi (otomatik başlatma engellendi).";
        }
        
        return $status; // Bot çalışmıyor olarak işaretle
    }
    
    // 1. PID dosyası kontrolü
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        if (!empty($pid) && is_numeric($pid)) {
            $status['pid'] = $pid;
            
            // PID süreci çalışıyor mu?
            exec("ps -p $pid > /dev/null 2>&1", $output, $return_var);
            if ($return_var === 0) {
                $status['running'] = true;
                $debug[] = "PID $pid çalışıyor.";
            } else {
                $debug[] = "PID $pid çalışmıyor.";
            }
        } else {
            $debug[] = "PID dosyası boş veya geçersiz: '$pid'";
        }
    } else {
        $debug[] = "PID dosyası mevcut değil: $pid_file";
    }
    
    // 2. PID dosyası yoksa veya PID çalışmıyorsa tüm trading_bot.py süreçlerini kontrol et
    if (!$status['running']) {
        exec("ps -ef | grep 'trading_bot.py' | grep -v grep", $process_output, $process_return);
        if (!empty($process_output)) {
            $status['running'] = true;
            $debug[] = "trading_bot.py süreci bulundu.";
            
            // İlk sürecin PID'ini al
            preg_match('/^\s*\S+\s+(\d+)/', $process_output[0], $matches);
            if (!empty($matches[1])) {
                $pid = $matches[1];
                $status['pid'] = $pid;
                // PID dosyasını güncelle
                @file_put_contents($pid_file, $pid);
                @chmod($pid_file, 0666);
                $debug[] = "PID dosyası güncellendi: $pid";
            }
        } else {
            $debug[] = "Hiçbir trading_bot.py süreci bulunamadı.";
        }
    }
    
    // 3. Eğer çalışıyorsa, kaynak kullanımı bilgilerini al
    if ($status['running'] && !empty($status['pid'])) {
        exec("ps -p {$status['pid']} -o etimes= -o pcpu= -o pmem=", $resource_output);
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
                $status['cpu'] = floatval($resources[1]) . '%';
                $status['memory'] = floatval($resources[2]) . '%';
                
                $debug[] = "Kaynak kullanımı bilgileri alındı.";
            }
        }
    }
    
    return $status;
}

switch ($action) {
    case 'start':
        // Eğer bot manuel olarak durdurulduysa, işareti kaldır
        global $bot_manually_stopped_file;
        if (file_exists($bot_manually_stopped_file)) {
            @unlink($bot_manually_stopped_file);
            $debug[] = "Manuel durdurma işareti kaldırıldı.";
        }
        
        // Çalışan botu önce durdur
        killExistingBots();
        
        // Bot'u başlatmanın en basit yolu
        $start_script_path = '/var/www/html/start_trading_bot_fixed.sh';
        
        // Script'e çalıştırma izni ver
        exec("chmod +x {$start_script_path}");
        
        // Bot'u doğrudan bash ile çalıştır
        $cmd = "bash {$start_script_path} >> {$log_file} 2>> {$error_log}";
        exec($cmd, $output, $return_var);
        $debug[] = "Bot başlatma komutu çıktısı: " . implode("\n", $output);
        $debug[] = "Bot başlatma dönüş kodu: $return_var";
        
        // Bot başlatma durumunu kontrol et - daha uzun süre ve daha sık kontrol
        $max_attempts = 10; // Maksimum 10 deneme
        $attempt = 0;
        $status = null;
        
        while ($attempt < $max_attempts) {
            $status = checkBotStatus();
            if ($status['running']) {
                break;
            }
            $attempt++;
            usleep(200000); // 200ms bekle
        }
        
        if ($status['running']) {
            $result = [
                'success' => true,
                'message' => "Bot başarıyla başlatıldı (PID: {$status['pid']})",
                'running' => true,
                'pid' => $status['pid'],
                'status' => $status,
                'debug' => $debug
            ];
        } else {
            // Python sürecini doğrudan başlatmayı dene
            $alt_cmd = "cd /var/www/html/bot && nohup python3.8 trading_bot.py >> {$log_file} 2>> {$error_log} &";
            exec($alt_cmd);
            
            // Tekrar kontrol et
            $attempt = 0;
            while ($attempt < $max_attempts) {
                $status = checkBotStatus();
                if ($status['running']) {
                    break;
                }
                $attempt++;
                usleep(200000); // 200ms bekle
            }
            
            if ($status['running']) {
                $result = [
                    'success' => true,
                    'message' => "Bot alternatif yöntemle başlatıldı (PID: {$status['pid']})",
                    'running' => true,
                    'pid' => $status['pid'],
                    'status' => $status,
                    'debug' => $debug
                ];
            } else {
                // Tüm yöntemler başarısız oldu
                $result = [
                    'success' => false,
                    'message' => "Bot başlatılamadı. Log dosyalarını kontrol edin.",
                    'running' => false,
                    'debug' => $debug,
                    'last_error' => file_exists($error_log) ? shell_exec("tail -n 10 $error_log") : "Log dosyası bulunamadı"
                ];
            }
        }
        break;

    case 'stop':
        if (file_exists($stop_script) && is_executable($stop_script)) {
            exec($stop_script . " 2>&1", $output, $return_var);
            $debug[] = "Stop script çıktısı: " . implode("\n", $output);
            $debug[] = "Stop script dönüş kodu: $return_var";
            
            if ($return_var === 0) {
                markBotManuallyStopped();
                $result = [
                    'success' => true,
                    'message' => "Bot başarıyla durduruldu",
                    'running' => false,
                    'debug' => $debug
                ];
                
                // PID dosyasını sil
                if (file_exists($pid_file)) {
                    @unlink($pid_file);
                    $debug[] = "PID dosyası silindi.";
                }
            } else {
                $killed = killExistingBots();
                
                if (file_exists($pid_file)) {
                    @unlink($pid_file);
                    $debug[] = "PID dosyası silindi.";
                }
                
                if ($killed > 0) {
                    markBotManuallyStopped();
                    $result = [
                        'success' => true,
                        'message' => "$killed adet bot süreci manuel olarak durduruldu.",
                        'running' => false,
                        'debug' => $debug
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => "Çalışan bot süreci bulunamadı.",
                        'running' => false,
                        'debug' => $debug
                    ];
                }
            }
        } else {
            $killed = killExistingBots();
            
            if (file_exists($pid_file)) {
                @unlink($pid_file);
                $debug[] = "PID dosyası silindi.";
            }
            
            if ($killed > 0) {
                markBotManuallyStopped();
                $result = [
                    'success' => true,
                    'message' => "$killed adet bot süreci durduruldu.",
                    'running' => false,
                    'debug' => $debug
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => "Çalışan bot süreci bulunamadı.",
                    'running' => false,
                    'debug' => $debug
                ];
            }
        }
        break;

    case 'restart':
        // Önce botu durdur
        if (file_exists($stop_script) && is_executable($stop_script)) {
            exec($stop_script . " 2>&1", $output, $return_var);
            $debug[] = "Stop script çıktısı: " . implode("\n", $output);
            $debug[] = "Stop script dönüş kodu: $return_var";
        } else {
            $killed = killExistingBots();
            $debug[] = "$killed adet bot süreci durduruldu.";
        }
        
        // PID dosyası varsa sil
        if (file_exists($pid_file)) {
            @unlink($pid_file);
            $debug[] = "PID dosyası silindi.";
        }
        
        // Manuel durdurma işaretini kaldır
        if (file_exists($bot_manually_stopped_file)) {
            @unlink($bot_manually_stopped_file);
            $debug[] = "Manuel durdurma işareti kaldırıldı.";
        }
        
        // 2 saniye bekleyerek sistemin düzgün kapanmasını sağla
        sleep(2);
        
        // Bot'u yeniden başlat
        $start_script_path = '/var/www/html/start_trading_bot_fixed.sh';
        
        // Script'e çalıştırma izni ver
        exec("chmod +x {$start_script_path}");
        
        // Bot'u doğrudan bash ile çalıştır
        $cmd = "bash {$start_script_path} >> {$log_file} 2>> {$error_log}";
        exec($cmd, $output, $return_var);
        $debug[] = "Bot başlatma komutu çıktısı: " . implode("\n", $output);
        $debug[] = "Bot başlatma dönüş kodu: $return_var";
        
        // Bot'un başlatılıp başlatılmadığını kontrol et
        sleep(3); // Botun başlaması için 3 saniye bekle
        $status = checkBotStatus();
        
        if ($status['running']) {
            $result = [
                'success' => true,
                'message' => "Bot başarıyla yeniden başlatıldı (PID: {$status['pid']})",
                'running' => true,
                'pid' => $status['pid'],
                'debug' => $debug
            ];
        } else {
            // Alternatif başlatma yöntemi dene
            $alt_cmd = "cd /var/www/html/bot && python3.8 trading_bot.py >> {$log_file} 2>> {$error_log} &";
            exec($alt_cmd, $alt_output, $alt_return_var);
            $debug[] = "Alternatif başlatma komutu çıktısı: " . implode("\n", $alt_output);
            $debug[] = "Alternatif başlatma dönüş kodu: $alt_return_var";
            
            sleep(2);
            $status = checkBotStatus();
            
            if ($status['running']) {
                $result = [
                    'success' => true,
                    'message' => "Bot alternatif yöntemle yeniden başlatıldı (PID: {$status['pid']})",
                    'running' => true,
                    'pid' => $status['pid'], 
                    'debug' => $debug
                ];
            } else {
                // Tüm yöntemler başarısız oldu
                $result = [
                    'success' => false,
                    'message' => "Bot yeniden başlatılamadı. Log dosyalarını kontrol edin.",
                    'running' => false,
                    'debug' => $debug
                ];
                
                // Son log satırlarını ekle
                $result['last_error'] = file_exists($error_log) ? shell_exec("tail -n 10 $error_log") : "Log dosyası bulunamadı";
            }
        }
        break;

    case 'status':
        $status = checkBotStatus();
        
        exec("ps -ef | grep trading_bot.py | grep -v grep", $output);
        $running_processes = count($output);
        
        $result = [
            'success' => true,
            'message' => $status['running'] 
                ? "Bot çalışıyor (PID: {$status['pid']})" 
                : "Bot çalışmıyor",
            'running' => $status['running'],
            'pid' => $status['pid'],
            'uptime' => isset($status['uptime']) ? $status['uptime'] : '',
            'cpu' => isset($status['cpu']) ? $status['cpu'] : '',
            'memory' => isset($status['memory']) ? $status['memory'] : '',
            'process_count' => $running_processes,
            'processes' => $output,
            'manually_stopped' => wasBotManuallyStopped(),
            'debug' => $debug
        ];
        break;

    case 'cleanup':
        $killed = killExistingBots();
        
        if (file_exists($pid_file)) {
            @unlink($pid_file);
            $debug[] = "PID dosyası silindi.";
        }
        
        if (file_exists($log_file) && filesize($log_file) > 5000000) {
            file_put_contents($log_file, "Log temizlendi: " . date('Y-m-d H:i:s') . "\n");
            $debug[] = "Log dosyası temizlendi.";
        }
        
        $result = [
            'success' => true,
            'message' => "$killed adet bot süreci temizlendi.",
            'running' => false,
            'debug' => $debug
        ];
        break;
        
    default:
        $result = [
            'success' => false,
            'message' => "Geçersiz işlem: $action",
        ];
}

header('Content-Type: application/json');
echo json_encode($result);
exit;
?>