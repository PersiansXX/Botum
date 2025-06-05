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

$bot_token = $_POST['bot_token'] ?? '';
$chat_id = $_POST['chat_id'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($bot_token) || empty($chat_id) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Eksik parametre']);
    exit;
}

// Telegram API URL
$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

// Mesaj verisi
$data = [
    'chat_id' => $chat_id,
    'text' => "🤖 *Trading Bot Test Mesajı*\n\n" . $message . "\n\n✅ Telegram bildirimleri çalışıyor!",
    'parse_mode' => 'Markdown'
];

// cURL ile Telegram API'ye istek gönder
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Hata kontrolü
if ($curl_error) {
    echo json_encode(['success' => false, 'error' => 'Bağlantı hatası: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['success' => false, 'error' => 'HTTP Hatası: ' . $http_code]);
    exit;
}

// Telegram API yanıtını kontrol et
$telegram_response = json_decode($response, true);

if (!$telegram_response || !$telegram_response['ok']) {
    $error_msg = $telegram_response['description'] ?? 'Bilinmeyen hata';
    echo json_encode(['success' => false, 'error' => 'Telegram API Hatası: ' . $error_msg]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Test mesajı başarıyla gönderildi']);
?>