<?php
// Trading Bot sabitini tanımla (güvenlik için)
define('TRADING_BOT', true);

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum açılmamış']);
    exit;
}

// JSON yanıt başlığı
header('Content-Type: application/json');

// Hata yakalama
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Performans ölçümü başlat
    $start_time = microtime(true);
    
    // Gerekli dosyaları dahil et
    require_once '../includes/db_connect.php';
    require_once 'bot_api.php';
    
    // Bot API'yi başlat
    $bot_api = new BotAPI();
    
    // Önce JSON dosyasından bakiye bilgilerini al
    $json_file = __DIR__ . '/binance_total_balances.json';
    $json_exists = file_exists($json_file);
    
    // Bakiye bilgileri
    $total_balance = 0;
    $spot_balance = 0;
    $futures_balance = 0;
    $margin_balance = 0;
    
    // JSON dosyasından bakiyeleri oku
    if ($json_exists) {
        try {
            $balances_json = file_get_contents($json_file);
            $balances_data = json_decode($balances_json, true);
            
            if ($balances_data && json_last_error() === JSON_ERROR_NONE) {
                $spot_balance = floatval($balances_data['total_spot'] ?? 0);
                $futures_balance = floatval($balances_data['total_futures'] ?? 0);
                $margin_balance = floatval($balances_data['total_margin'] ?? 0) + 
                               floatval($balances_data['total_isolated'] ?? 0);
                $total_balance = $spot_balance + $futures_balance + $margin_balance;
            }
        } catch (Exception $e) {
            error_log("JSON dosyasından bakiye okunamadı: " . $e->getMessage());
        }
    }
    
    // JSON dosyasından bakiye alınamadıysa API'den dene
    if ($total_balance <= 0) {
        try {
            // Toplam bakiye
            $api_total_balance = $bot_api->getBalance(true);
            
            if ($api_total_balance !== "BAKİYE HATA") {
                $total_balance = $api_total_balance;
                
                // Spot bakiye
                $spot_balance = $bot_api->getBalance(false);
                if ($spot_balance === "BAKİYE HATA") {
                    $spot_balance = 0;
                }
                
                // Futures bakiye
                $futures_balance = $bot_api->getFuturesBalance();
                if ($futures_balance === "BAKİYE HATA") {
                    $futures_balance = 0;
                }
                
                // Margin bakiyesi hesapla
                $margin_balance = $total_balance - $spot_balance - $futures_balance;
                $margin_balance = max(0, $margin_balance); // Negatif olmamasını sağla
            } else {
                error_log("API'den bakiye alınamadı, JSON'dan gelen değerler kullanılacak.");
            }
        } catch (Exception $e) {
            error_log("API'den bakiye alınırken hata: " . $e->getMessage());
        }
    }
    
    // Son güncelleme zamanı
    $update_time = date('Y-m-d H:i:s');
    
    // Yanıt verilerini oluştur
    $response = [
        'success' => true,
        'total_balance' => $total_balance,
        'spot_balance' => $spot_balance,
        'futures_balance' => $futures_balance,
        'margin_balance' => $margin_balance,
        'updated_at' => $update_time,
        'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . ' ms'  // Milisaniye cinsinden işlem süresi
    ];
    
    // JSON yanıtı gönder
    echo json_encode($response);
    
} catch (Exception $e) {
    // Hata durumunda JSON yanıt
    error_log("get_balances.php hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Bakiye bilgileri alınırken hata oluştu: ' . $e->getMessage()
    ]);
}

// Hata yakalayıcıyı eski haline getir
restore_error_handler();
?>