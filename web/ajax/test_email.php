<?php
header('Content-Type: application/json');

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

// POST verileri kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu']);
    exit;
}

$smtp_host = $_POST['smtp_host'] ?? '';
$smtp_port = $_POST['smtp_port'] ?? 587;
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$recipients = $_POST['recipients'] ?? '';
$subject = $_POST['subject'] ?? 'Test E-postası';
$message = $_POST['message'] ?? '';

if (empty($smtp_host) || empty($username) || empty($password) || empty($recipients)) {
    echo json_encode(['success' => false, 'error' => 'Eksik e-posta ayarları']);
    exit;
}

// Recipients'ı array'e çevir
$recipient_list = array_map('trim', explode(',', $recipients));
$recipient_list = array_filter($recipient_list); // Boş değerleri kaldır

if (empty($recipient_list)) {
    echo json_encode(['success' => false, 'error' => 'Geçerli alıcı e-posta adresi bulunamadı']);
    exit;
}

// E-posta gönderimi için basit SMTP fonksiyonu
function sendTestEmail($smtp_host, $smtp_port, $username, $password, $recipients, $subject, $message) {
    try {
        // PHPMailer kullanmadan basit mail() fonksiyonu ile test
        // Gerçek ortamda PHPMailer kullanılması önerilir
        
        $headers = [
            'From: ' . $username,
            'Reply-To: ' . $username,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        $email_body = "
        <html>
        <head>
            <title>{$subject}</title>
        </head>
        <body>
            <h2>🤖 Trading Bot Test E-postası</h2>
            <p><strong>Gönderim Zamanı:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>SMTP Sunucu:</strong> {$smtp_host}:{$smtp_port}</p>
            <p><strong>Mesaj:</strong> {$message}</p>
            <br>
            <p>✅ E-posta bildirimleri düzgün çalışıyor!</p>
            <hr>
            <small>Bu mesaj Trading Bot test sistemi tarafından gönderilmiştir.</small>
        </body>
        </html>";
        
        $success_count = 0;
        $errors = [];
        
        foreach ($recipients as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                // mail() fonksiyonu ile gönder (test amaçlı)
                if (mail($recipient, $subject, $email_body, implode("\r\n", $headers))) {
                    $success_count++;
                } else {
                    $errors[] = "E-posta gönderilemedi: {$recipient}";
                }
            } else {
                $errors[] = "Geçersiz e-posta adresi: {$recipient}";
            }
        }
        
        if ($success_count > 0) {
            return [
                'success' => true, 
                'message' => "{$success_count} e-posta başarıyla gönderildi",
                'errors' => $errors
            ];
        } else {
            return [
                'success' => false, 
                'error' => 'Hiçbir e-posta gönderilemedi. Hatalar: ' . implode(', ', $errors)
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'E-posta gönderim hatası: ' . $e->getMessage()];
    }
}

// SMTP test için alternatif olarak socket bağlantısı test edelim
function testSMTPConnection($smtp_host, $smtp_port) {
    $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$socket) {
        return ['success' => false, 'error' => "SMTP bağlantısı başarısız: {$errstr} ({$errno})"];
    }
    
    $response = fgets($socket, 515);
    fclose($socket);
    
    if (strpos($response, '220') === 0) {
        return ['success' => true, 'message' => 'SMTP sunucuya bağlantı başarılı'];
    } else {
        return ['success' => false, 'error' => 'SMTP sunucu yanıtı beklenen formatta değil'];
    }
}

// Önce SMTP bağlantısını test et
$smtp_test = testSMTPConnection($smtp_host, $smtp_port);
if (!$smtp_test['success']) {
    echo json_encode($smtp_test);
    exit;
}

// E-posta göndermeyi dene
$result = sendTestEmail($smtp_host, $smtp_port, $username, $password, $recipient_list, $subject, $message);
echo json_encode($result);
?>