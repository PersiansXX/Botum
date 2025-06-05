<?php
session_start();

// Giriş yapılmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

header('Content-Type: application/json');

// Bot durumu için direkt kontrol fonksiyonu
function checkBotStatus() {
    $root_dir = realpath(__DIR__ . '/../../');
    $bot_dir = $root_dir . '/bot';
    $logs_dir = $root_dir . '/logs';

    $pid_file = $bot_dir . '/bot.pid';
    
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

try {
    $status = checkBotStatus();
    echo json_encode([
        'success' => true,
        'status' => $status
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bot durumu kontrol edilirken hata oluştu: ' . $e->getMessage()
    ]);
}
?>