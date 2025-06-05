<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    die('Lütfen önce giriş yapın.');
}

require_once 'api/bot_api.php';
$bot_api = new BotAPI();

echo "<h2>Bot Ayarları Debug Test</h2>";
echo "<hr>";

// Mevcut ayarları göster
echo "<h3>1. Mevcut Ayarlar:</h3>";
$current_settings = $bot_api->getSettings();
echo "<pre>" . json_encode($current_settings, JSON_PRETTY_PRINT) . "</pre>";
echo "<hr>";

// Test güncelleme yap
if (isset($_GET['test'])) {
    echo "<h3>2. Test Güncelleme Yapılıyor...</h3>";
    
    $test_settings = [
        'test_field' => 'Test değeri - ' . date('Y-m-d H:i:s'),
        'exchange' => $current_settings['exchange'] ?? 'binance',
        'base_currency' => 'USDT',
        'debug_timestamp' => time()
    ];
    
    echo "<p><strong>Güncellenecek test ayarları:</strong></p>";
    echo "<pre>" . json_encode($test_settings, JSON_PRETTY_PRINT) . "</pre>";
    
    $result = $bot_api->updateSettings($test_settings);
    
    echo "<p><strong>Güncelleme sonucu:</strong> " . ($result ? 'BAŞARILI ✅' : 'BAŞARISIZ ❌') . "</p>";
    
    if ($result) {
        echo "<p>Ayarları yeniden yükleniyor...</p>";
        $updated_settings = $bot_api->getSettings();
        
        echo "<h3>3. Güncellenmiş Ayarlar:</h3>";
        echo "<pre>" . json_encode($updated_settings, JSON_PRETTY_PRINT) . "</pre>";
        
        // Karşılaştırma
        if (isset($updated_settings['test_field'])) {
            echo "<p style='color: green;'>✅ Test alanı başarıyla güncellendi: " . $updated_settings['test_field'] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Test alanı güncellenemedi</p>";
        }
    }
    
    echo "<hr>";
}

echo "<h3>Test İşlemleri:</h3>";
echo "<a href='?test=1' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Test Güncellemesi Yap</a>";
echo " | ";
echo "<a href='debug_settings.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Sayfayı Yenile</a>";
echo " | ";
echo "<a href='settings.php' style='background: #6c757d; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Settings Sayfasına Dön</a>";

?>