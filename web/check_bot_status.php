<?php
header('Content-Type: text/plain');
echo "Bot Status Check\n";
echo "================\n\n";

// Bot dizinleri ve dosya yollarını tanımla
$bot_dir = '/var/www/html/bot';
$log_file = "$bot_dir/bot.log";
$error_log = "$bot_dir/bot_error.log";
$pid_file = "$bot_dir/bot.pid";

// 1. Process check with ps
echo "1. Process Check:\n";
exec("ps -ef | grep trading_bot.py | grep -v grep", $output, $return_var);
echo "Command return code: $return_var\n";
echo "Processes found: " . count($output) . "\n";
if (!empty($output)) {
    echo "Process details:\n";
    foreach ($output as $line) {
        echo "- $line\n";
    }
    
    // İlk sürecin PID'ini bul ve kaydet
    preg_match('/^\s*\S+\s+(\d+)/', $output[0], $matches);
    if (!empty($matches[1])) {
        $found_pid = $matches[1];
        echo "Found running process with PID: $found_pid\n";
        
        // PID dosyası yoksa veya içeriği farklıysa güncelle
        if (!file_exists($pid_file) || trim(file_get_contents($pid_file)) != $found_pid) {
            @file_put_contents($pid_file, $found_pid);
            @chmod($pid_file, 0666);
            echo "Updated PID file with found PID: $found_pid\n";
        }
    }
} else {
    echo "No bot processes found.\n";
    
    // Eğer süreç bulunamadıysa ve PID dosyası varsa sil
    if (file_exists($pid_file)) {
        @unlink($pid_file);
        echo "Removed stale PID file as no process was found.\n";
    }
}

// 2. PID file check
echo "\n2. PID File Check:\n";
$pid_locations = [
    '/var/www/html/bot/bot.pid',
    '/var/www/html/bot.pid'
];

foreach ($pid_locations as $pid_file) {
    echo "Checking $pid_file: ";
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        echo "EXISTS (PID: $pid)\n";
        
        // Check if this PID is valid
        exec("ps -p $pid", $pid_output, $pid_return);
        echo "  Process with this PID is " . ($pid_return === 0 ? "running" : "not running") . "\n";
        
        // Eğer PID geçerli değilse, PID dosyasını sil
        if ($pid_return !== 0) {
            @unlink($pid_file);
            echo "  Removed invalid PID file.\n";
        }
    } else {
        echo "NOT FOUND\n";
    }
}

// 3. Log file check
echo "\n3. Log File Check:\n";
$log_files = [
    'Main Log' => $log_file,
    'Error Log' => $error_log
];

foreach ($log_files as $label => $file) {
    echo "$label: ";
    if (file_exists($file)) {
        echo "EXISTS, size: " . filesize($file) . " bytes\n";
        echo "Last few lines:\n";
        exec("tail -10 $file", $log_output);
        foreach ($log_output as $line) {
            echo "  $line\n";
        }
    } else {
        echo "NOT FOUND\n";
        // Log dosyasını oluştur
        @touch($file);
        @chmod($file, 0666);
        echo "Created empty log file: $file\n";
    }
    echo "\n";
}

// 4. Direct check using file_get_contents for bot_control.php without session restriction
echo "\n4. bot_control.php Status Response:\n";
// Make internal request to bot_control.php
$ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . '/web/bot_control.php?action=status&no_auth_check=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 saniye timeout
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Status: " . $info['http_code'] . "\n";
echo "Response:\n";
if ($response) {
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "  running: " . ($json['running'] ? 'true' : 'false') . "\n";
        echo "  message: " . $json['message'] . "\n";
        if (isset($json['process_count'])) {
            echo "  process_count: " . $json['process_count'] . "\n";
        }
        if (isset($json['pid'])) {
            echo "  pid: " . $json['pid'] . "\n";
        }
        
        // Debug bilgilerini ekle
        if (isset($json['debug']) && is_array($json['debug'])) {
            echo "  debug: \n";
            foreach ($json['debug'] as $debug) {
                echo "    - $debug\n";
            }
        }
    } else {
        echo "Invalid JSON response: " . $response . "\n";
        
        // Alternatif URL dene
        $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . '/bot_control.php?action=status&no_auth_check=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        echo "\nTrying alternative URL...\n";
        echo "HTTP Status: " . $info['http_code'] . "\n";
        
        if ($response) {
            $json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "  running: " . ($json['running'] ? 'true' : 'false') . "\n";
                echo "  message: " . $json['message'] . "\n";
            } else {
                echo "Invalid JSON response from alternative URL: " . $response . "\n";
            }
        } else {
            echo "No response received from alternative URL\n";
        }
    }
} else {
    echo "No response received\n";
}

// 5. Direct check by reading PID and process status
echo "\n5. Direct Process Check:\n";
$bot_pid_files = glob('/var/www/html/bot*.pid');
$bot_pid_files = array_merge($bot_pid_files, glob('/var/www/html/bot/bot*.pid'));

$found_running = false;
foreach ($bot_pid_files as $pid_file) {
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        echo "PID file $pid_file contains: $pid\n";
        
        if (!empty($pid) && is_numeric($pid)) {
            exec("ps -p $pid -o cmd=", $cmd_output, $cmd_return);
            if ($cmd_return === 0 && !empty($cmd_output)) {
                echo "Process is running: " . implode("\n", $cmd_output) . "\n";
                $found_running = true;
                
                // Get process details
                exec("ps -p $pid -o pid,ppid,etime,pcpu,pmem", $detail_output);
                if (count($detail_output) > 1) {
                    echo "Process details:\n";
                    foreach ($detail_output as $line) {
                        echo "  $line\n";
                    }
                }
            } else {
                echo "Process with PID $pid is NOT running\n";
            }
        } else {
            echo "Invalid PID in file: '$pid'\n";
        }
    }
}

if (!$found_running) {
    echo "No running bot processes found with any PID file\n";
}

echo "\n6. Running PHP version: " . phpversion();
echo "\nScript completed at: " . date('Y-m-d H:i:s');
