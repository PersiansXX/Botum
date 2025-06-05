<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PID dosya yolları
$pid_file = '/var/www/html/bot/bot.pid';
$alt_pid_file = '/var/www/html/bot.pid';

$results = [];
$killed_processes = 0;

// Çalışan tüm bot süreçlerini bul
exec("ps aux | grep trading_bot.py | grep -v grep", $processes);
$results[] = "Tespit edilen bot süreçleri: " . count($processes);

// Her süreci sonlandır
foreach ($processes as $process) {
    $parts = preg_split('/\s+/', trim($process));
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $pid = $parts[1];
        $results[] = "Bulunan PID: $pid - $process";
        
        // Önce normal sonlandırma
        exec("kill $pid 2>&1", $kill_output, $kill_return);
        $killed_processes++;
        
        $results[] = "Kill sonucu (PID $pid): dönüş kodu = $kill_return";
        if (!empty($kill_output)) {
            $results[] = "Kill çıktısı: " . implode("\n", $kill_output);
        }
    }
}

// 2 saniye bekle ve hala çalışan süreçleri kontrol et
sleep(2);
exec("ps aux | grep trading_bot.py | grep -v grep", $remaining_processes);

// Hala çalışan süreçleri zorla sonlandır
if (count($remaining_processes) > 0) {
    $results[] = "Hala çalışan süreç sayısı: " . count($remaining_processes);
    
    foreach ($remaining_processes as $process) {
        $parts = preg_split('/\s+/', trim($process));
        if (isset($parts[1]) && is_numeric($parts[1])) {
            $pid = $parts[1];
            $results[] = "SIGKILL gönderiliyor: PID $pid";
            
            // SIGKILL ile zorla sonlandır
            exec("kill -9 $pid 2>&1", $kill_output, $kill_return);
            
            $results[] = "Kill -9 sonucu: dönüş kodu = $kill_return";
            if (!empty($kill_output)) {
                $results[] = "Kill -9 çıktısı: " . implode("\n", $kill_output);
            }
        }
    }
}

// PID dosyalarını temizle
if (file_exists($pid_file)) {
    unlink($pid_file);
    $results[] = "PID dosyası silindi: $pid_file";
}

if (file_exists($alt_pid_file)) {
    unlink($alt_pid_file);
    $results[] = "Alternatif PID dosyası silindi: $alt_pid_file";
}

// Son durumu kontrol et
sleep(1);
exec("ps aux | grep trading_bot.py | grep -v grep", $final_check);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bot Temizleme Aracı</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Trading Bot Temizleme Aracı</h1>
    
    <h2>Temizleme Sonuçları</h2>
    
    <div class="<?php echo $killed_processes > 0 ? 'success' : 'warning'; ?>">
        <?php echo $killed_processes > 0 
            ? "$killed_processes adet süreç sonlandırıldı." 
            : "Sonlandırılacak süreç bulunamadı."; ?>
    </div>
    
    <h3>İşlem Detayları:</h3>
    <pre><?php foreach($results as $result) { echo htmlspecialchars($result) . "\n"; } ?></pre>
    
    <h3>Mevcut Durum:</h3>
    <?php if(empty($final_check)): ?>
        <div class="success">Tüm bot süreçleri temizlendi. Sistem temiz.</div>
    <?php else: ?>
        <div class="error">DİKKAT: Hala çalışan bot süreçleri var!</div>
        <pre><?php foreach($final_check as $process) { echo htmlspecialchars($process) . "\n"; } ?></pre>
    <?php endif; ?>
    
    <h2>Sonraki Adımlar</h2>
    <ul>
        <li><a href="simple_bot_control.php?action=status">Bot Durumunu Kontrol Et</a></li>
        <li><a href="simple_bot_control.php?action=start">Yeni Bot Başlat</a></li>
        <li><a href="test_bot_api.php">API Test Sayfasına Git</a></li>
    </ul>
</body>
</html>
