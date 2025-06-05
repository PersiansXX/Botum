<div class="settings-panel">
    <h5><i class="fas fa-key"></i> API Anahtarları</h5>
    
    <div class="info-box warning">
        <i class="fas fa-exclamation-triangle text-warning"></i>
        <div>
            <strong>Güvenlik Uyarısı:</strong>
            API anahtarlarınızı güvenli bir şekilde saklayın. Bu anahtarlar hesabınıza tam erişim sağlar.
        </div>
    </div>

    <div class="form-group-container">
        <div class="form-group-title">
            <i class="fab fa-bitcoin"></i> Binance API Ayarları
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="binance_api_key">Binance API Key</label>
                    <input type="text" class="form-control" id="binance_api_key" name="binance_api_key" 
                           value="<?php echo htmlspecialchars($settings['api_keys']['binance_api_key'] ?? ''); ?>" 
                           placeholder="Binance API Key'inizi girin">
                    <small class="form-text text-muted">Binance hesabınızdan alacağınız API anahtarı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="binance_api_secret">Binance API Secret</label>
                    <input type="password" class="form-control" id="binance_api_secret" name="binance_api_secret" 
                           value="<?php echo htmlspecialchars($settings['api_keys']['binance_api_secret'] ?? ''); ?>" 
                           placeholder="Binance API Secret'ınızı girin">
                    <small class="form-text text-muted">Binance hesabınızdan alacağınız gizli anahtar</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group-container">
        <div class="form-group-title">
            <i class="fas fa-coins"></i> KuCoin API Ayarları
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="kucoin_api_key">KuCoin API Key</label>
                    <input type="text" class="form-control" id="kucoin_api_key" name="kucoin_api_key" 
                           value="<?php echo htmlspecialchars($settings['api_keys']['kucoin_api_key'] ?? ''); ?>" 
                           placeholder="KuCoin API Key'inizi girin">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="kucoin_api_secret">KuCoin API Secret</label>
                    <input type="password" class="form-control" id="kucoin_api_secret" name="kucoin_api_secret" 
                           value="<?php echo htmlspecialchars($settings['api_keys']['kucoin_api_secret'] ?? ''); ?>" 
                           placeholder="KuCoin API Secret'ınızı girin">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="kucoin_api_passphrase">KuCoin API Passphrase</label>
                    <input type="password" class="form-control" id="kucoin_api_passphrase" name="kucoin_api_passphrase" 
                           value="<?php echo htmlspecialchars($settings['api_keys']['kucoin_api_passphrase'] ?? ''); ?>" 
                           placeholder="KuCoin API Passphrase'inizi girin">
                </div>
            </div>
        </div>
    </div>

    <div class="form-group-container">
        <div class="form-group-title">
            <i class="fab fa-telegram"></i> Telegram Bot Ayarları
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="custom-control custom-switch mb-3">
                    <input type="checkbox" class="custom-control-input" id="telegram_enabled" name="telegram_enabled" 
                           <?php echo isset($settings['telegram']['enabled']) && $settings['telegram']['enabled'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_enabled">Telegram Bildirimlerini Etkinleştir</label>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telegram_token">Telegram Bot Token</label>
                    <input type="text" class="form-control telegram-setting" id="telegram_token" name="telegram_token" 
                           value="<?php echo htmlspecialchars($settings['telegram']['token'] ?? ''); ?>" 
                           placeholder="Telegram Bot Token'ınızı girin">
                    <small class="form-text text-muted">@BotFather'dan alacağınız bot token'ı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telegram_chat_id">Telegram Chat ID</label>
                    <input type="text" class="form-control telegram-setting" id="telegram_chat_id" name="telegram_chat_id" 
                           value="<?php echo htmlspecialchars($settings['telegram']['chat_id'] ?? ''); ?>" 
                           placeholder="Telegram Chat ID'nizi girin">
                    <small class="form-text text-muted">Bildirim göndereceğiniz chat ID'si</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input telegram-setting" id="telegram_trade_signals" name="telegram_trade_signals" 
                           <?php echo isset($settings['telegram']['trade_signals']) && $settings['telegram']['trade_signals'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_trade_signals">Ticaret Sinyalleri Bildirimi</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input telegram-setting" id="telegram_discovered_coins" name="telegram_discovered_coins" 
                           <?php echo isset($settings['telegram']['discovered_coins']) && $settings['telegram']['discovered_coins'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_discovered_coins">Keşfedilen Coin Bildirimi</label>
                </div>
            </div>
        </div>
    </div>

    <div class="info-box tip">
        <i class="fas fa-lightbulb text-success"></i>
        <div>
            <strong>İpucu:</strong>
            API anahtarlarınızı güvenli tutmak için sadece gerekli izinleri verin. 
            Spot işlemler için "Spot Trading" iznini, futures işlemler için "Futures Trading" iznini etkinleştirin.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Telegram ayarlarının etkinliğini kontrol et
    function updateTelegramSettings() {
        var enabled = document.getElementById('telegram_enabled').checked;
        var telegramSettings = document.querySelectorAll('.telegram-setting');
        telegramSettings.forEach(function(element) {
            element.disabled = !enabled;
        });
    }
    
    // Sayfa yüklendiğinde çalıştır
    updateTelegramSettings();
    
    // Telegram checkbox değiştiğinde çalıştır
    document.getElementById('telegram_enabled').addEventListener('change', updateTelegramSettings);
});
</script>