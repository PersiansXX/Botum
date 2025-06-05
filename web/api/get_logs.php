<?php
/**
 * Bot log dosyalarını okuma ve düzenleme API'si
 * AJAX çağrısı için kullanılır
 */

// Bellek limitini arttır (geçici çözüm)
ini_set('memory_limit', '256M');

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Yetkisiz erişim';
    exit;
}

// Log dosya yolları
$log_files = [
    __DIR__ . '/../../bot.log',
    __DIR__ . '/../../bot/bot.log'
];

// İstenen satır sayısı
$line_count = isset($_GET['lines']) ? intval($_GET['lines']) : 100;
$line_count = min(max($line_count, 10), 500); // Min 10, max 500 satır

// Logları oku (her dosyadan sadece son kısmını oku)
$logs = [];
foreach ($log_files as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        try {
            // Dosya boyutunu kontrol et
            $filesize = filesize($log_file);
            if ($filesize > 1024 * 1024 * 5) { // 5MB'dan büyükse
                // Son 1MB'ı oku (yaklaşık son 10,000 satır)
                $handle = fopen($log_file, 'r');
                fseek($handle, -1024 * 1024, SEEK_END);
                // İlk satırı atla (kırık olabilir)
                fgets($handle);
                // Geri kalan satırları oku
                $content = '';
                while (!feof($handle)) {
                    $content .= fgets($handle);
                }
                fclose($handle);
                $file_logs = explode("\n", $content);
            } else {
                // Dosya küçükse normal oku
                $file_logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            
            if (is_array($file_logs)) {
                $logs = array_merge($logs, $file_logs);
            }
        } catch (Exception $e) {
            // Log hatasını yoksay
        }
    }
}

// Logları ters sırala (en son loglar üstte)
$logs = array_reverse($logs);

// İstenen sayıda son logu al
$logs = array_slice($logs, 0, $line_count);

// Log varsa göster, yoksa boş bir mesaj göster
if (empty($logs)) {
    echo '<div class="text-center text-muted py-4">Log dosyası bulunamadı veya boş.</div>';
} else {
    foreach ($logs as $log) {
        $log_class = 'log-normal';
        
        // Log tipine göre renklendir
        if (stripos($log, 'ERROR') !== false) {
            $log_class = 'log-error';
        } elseif (stripos($log, 'WARNING') !== false) {
            $log_class = 'log-warning';
        } elseif (stripos($log, 'SUCCESS') !== false || stripos($log, 'PROFIT') !== false) {
            $log_class = 'log-success';
        }
        
        echo '<div class="log-entry ' . $log_class . '">' . htmlspecialchars($log) . '</div>';
    }
}
?>