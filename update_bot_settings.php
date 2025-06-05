<?php
// Bot settings tablosunu eksik ayarlarla güncelleme scripti
require_once 'web/includes/db_connect.php';

echo "Bot settings güncelleme scripti başlatılıyor...\n";

try {
    // Mevcut ayarları al
    $result = $conn->query("SELECT * FROM bot_settings WHERE id = 1");
    if (!$result || $result->num_rows == 0) {
        die("Bot settings bulunamadı!\n");
    }
    
    $row = $result->fetch_assoc();
    $current_settings = json_decode($row['settings_json'], true);
    
    if (!$current_settings) {
        die("JSON decode hatası!\n");
    }
    
    echo "Mevcut ayarlar yüklendi. Eksik ayarlar ekleniyor...\n";
    
    // Eksik ayarları ekle
    
    // Bildirimler ayarları güncelle
    if (!isset($current_settings['notifications'])) {
        $current_settings['notifications'] = array();
    }
    
    // Telegram bildirimlerini yeniden yapılandır
    $current_settings['notifications']['telegram'] = array(
        'enabled' => isset($current_settings['telegram']['enabled']) ? $current_settings['telegram']['enabled'] : true,
        'bot_token' => isset($current_settings['telegram']['token']) ? $current_settings['telegram']['token'] : '',
        'chat_id' => isset($current_settings['telegram']['chat_id']) ? $current_settings['telegram']['chat_id'] : '',
        'message_format' => 'simple',
        'rate_limit' => 1,
        'types' => array(
            'trades' => isset($current_settings['telegram']['trade_signals']) ? $current_settings['telegram']['trade_signals'] : false,
            'errors' => true,
            'profits' => true,
            'status' => true,
            'discovered_coins' => isset($current_settings['telegram']['discovered_coins']) ? $current_settings['telegram']['discovered_coins'] : false
        )
    );
    
    // E-posta bildirimleri ekle
    $current_settings['notifications']['email'] = array(
        'enabled' => false,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'username' => '',
        'password' => '',
        'recipients' => array(),
        'types' => array(
            'critical' => true,
            'daily_reports' => false,
            'weekly_reports' => false,
            'system_status' => true
        )
    );
    
    // Günlükleme ayarları ekle
    if (!isset($current_settings['logging'])) {
        $current_settings['logging'] = array(
            'level' => 'INFO',
            'max_file_size' => 10,
            'retention_days' => 30,
            'format' => 'simple',
            'backup_count' => 5,
            'rotation' => true,
            'compression' => false,
            'categories' => array(
                'trades' => true,
                'indicators' => false,
                'api' => false,
                'errors' => true
            )
        );
    }
    
    // Performans izleme ayarları ekle
    if (!isset($current_settings['monitoring'])) {
        $current_settings['monitoring'] = array(
            'performance_interval' => 60,
            'memory_threshold' => 80,
            'cpu_monitoring' => true,
            'disk_monitoring' => true
        );
    }
    
    // Eksik ayarları düzenle/ekle
    if (!isset($current_settings['telegram_enabled'])) {
        $current_settings['telegram_enabled'] = isset($current_settings['telegram']['enabled']) ? $current_settings['telegram']['enabled'] : true;
    }
    
    if (!isset($current_settings['telegram_trade_signals'])) {
        $current_settings['telegram_trade_signals'] = isset($current_settings['telegram']['trade_signals']) ? $current_settings['telegram']['trade_signals'] : false;
    }
    
    if (!isset($current_settings['telegram_discovered_coins'])) {
        $current_settings['telegram_discovered_coins'] = isset($current_settings['telegram']['discovered_coins']) ? $current_settings['telegram']['discovered_coins'] : false;
    }
    
    if (!isset($current_settings['telegram_position_updates'])) {
        $current_settings['telegram_position_updates'] = false;
    }
    
    if (!isset($current_settings['telegram_performance_updates'])) {
        $current_settings['telegram_performance_updates'] = false;
    }
    
    if (!isset($current_settings['enable_visualization'])) {
        $current_settings['enable_visualization'] = isset($current_settings['backtesting']['enable_visualization']) ? $current_settings['backtesting']['enable_visualization'] : false;
    }
    
    if (!isset($current_settings['auto_discovery_enabled'])) {
        $current_settings['auto_discovery_enabled'] = isset($current_settings['auto_discovery']['enabled']) ? $current_settings['auto_discovery']['enabled'] : true;
    }
    
    if (!isset($current_settings['auto_add_to_watchlist'])) {
        $current_settings['auto_add_to_watchlist'] = isset($current_settings['auto_discovery']['auto_add_to_watchlist']) ? $current_settings['auto_discovery']['auto_add_to_watchlist'] : false;
    }
    
    // JSON'ı yeniden encode et
    $updated_json = json_encode($current_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (!$updated_json) {
        die("JSON encode hatası!\n");
    }
    
    // Veritabanını güncelle
    $stmt = $conn->prepare("UPDATE bot_settings SET settings_json = ?, settings = ?, last_updated = NOW() WHERE id = 1");
    $stmt->bind_param("ss", $updated_json, $updated_json);
    
    if ($stmt->execute()) {
        echo "✅ Bot settings başarıyla güncellendi!\n";
        echo "📧 E-posta bildirimleri eklendi\n";
        echo "📝 Günlükleme ayarları eklendi\n";
        echo "📊 Performans izleme ayarları eklendi\n";
        echo "🔧 Telegram ayarları yeniden yapılandırıldı\n";
    } else {
        echo "❌ Güncelleme hatası: " . $stmt->error . "\n";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Script tamamlandı.\n";
?>