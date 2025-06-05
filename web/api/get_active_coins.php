<?php
/**
 * Aktif coinleri getiren API
 * AJAX istekleri için kullanılır
 */

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Yetkisiz erişim.']);
    exit;
}

require_once 'bot_api.php';

// Bot API'sini başlat
$bot_api = new BotAPI();

// Zaman aralığı parametresini al
$interval = isset($_GET['interval']) ? $_GET['interval'] : '5m'; // Varsayılan 5 dakika

// Aktif coinleri belirlenen zaman aralığına göre al
$coins = $bot_api->getActiveCoins($interval);

// JSON yanıtı döndür
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
echo json_encode([
    'success' => true,
    'data' => $coins,
    'interval' => $interval,
    'last_update' => date('Y-m-d H:i:s')
]);
