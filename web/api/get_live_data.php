<?php
// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// API bağlantısı
require_once '../api/bot_api.php';
$bot_api = new BotAPI();

// Parametreleri al
$symbol = isset($_GET['symbol']) ? $_GET['symbol'] : null;

// Sonuç dizisi
$result = [];

try {
    // Hızlı güncelleme için sadece istenilen coini veya tümünü getir
    if ($symbol) {
        // Tek bir coin için veri getir
        $all_coins = $bot_api->getActiveCoins();
        foreach ($all_coins as $coin) {
            if ($coin['symbol'] === $symbol) {
                $result = $coin;
                break;
            }
        }
    } else {
        // Tüm coinleri getir
        $result = $bot_api->getActiveCoins();
    }

    // Zamanı ekle
    $result['timestamp'] = date('Y-m-d H:i:s');
    $result['success'] = true;
    
} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// JSON sonuç döndür
header('Content-Type: application/json');
echo json_encode($result);