<?php
// Bildirimler ve Günlükleme Modülü
?>
<div class="info-box info mb-4">
    <i class="fas fa-bell"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Bildirimler ve Günlükleme</h6>
        <p class="mb-0">Bot aktivitelerini izlemek ve önemli olaylardan haberdar olmak için bildirim ve günlükleme ayarlarını yapılandırın. Telegram, e-posta ve SMS bildirimlerini özelleştirebilir, günlük seviyelerini ayarlayabilirsiniz.</p>
    </div>
</div>

<!-- Telegram Bildirimleri -->
<div class="feature-card mb-4 <?php echo isset($settings['notifications']['telegram']['enabled']) && $settings['notifications']['telegram']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="fab fa-telegram-plane"></i> Telegram Bildirimleri</h5>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="telegram_enabled" name="telegram_enabled"
                       <?php echo isset($settings['notifications']['telegram']['enabled']) && $settings['notifications']['telegram']['enabled'] ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="telegram_enabled"></label>
            </div>
        </div>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telegram_bot_token">Bot Token</label>
                    <input type="text" name="telegram_bot_token" id="telegram_bot_token" class="form-control" 
                           value="<?php echo isset($settings['notifications']['telegram']['bot_token']) ? $settings['notifications']['telegram']['bot_token'] : ''; ?>" 
                           placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
                    <small class="form-text text-muted">@BotFather'dan aldığınız bot token</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telegram_chat_id">Chat ID</label>
                    <input type="text" name="telegram_chat_id" id="telegram_chat_id" class="form-control" 
                           value="<?php echo isset($settings['notifications']['telegram']['chat_id']) ? $settings['notifications']['telegram']['chat_id'] : ''; ?>" 
                           placeholder="-1001234567890">
                    <small class="form-text text-muted">Bildirimlerin gönderileceği chat ID</small>
                </div>
            </div>
        </div>
        
        <!-- Telegram Bildirim Türleri -->
        <div class="row">
            <div class="col-12">
                <h6 class="font-weight-bold mb-3">Bildirim Türleri</h6>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="telegram_trades" name="telegram_trades"
                           <?php echo isset($settings['notifications']['telegram']['types']['trades']) && $settings['notifications']['telegram']['types']['trades'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_trades">İşlem Bildirimleri</label>
                    <small class="form-text text-muted">Alım/satım işlemleri</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="telegram_errors" name="telegram_errors"
                           <?php echo isset($settings['notifications']['telegram']['types']['errors']) && $settings['notifications']['telegram']['types']['errors'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_errors">Hata Bildirimleri</label>
                    <small class="form-text text-muted">Sistem hataları</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="telegram_profits" name="telegram_profits"
                           <?php echo isset($settings['notifications']['telegram']['types']['profits']) && $settings['notifications']['telegram']['types']['profits'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_profits">Kar Bildirimleri</label>
                    <small class="form-text text-muted">Kar/zarar raporları</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="telegram_status" name="telegram_status"
                           <?php echo isset($settings['notifications']['telegram']['types']['status']) && $settings['notifications']['telegram']['types']['status'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="telegram_status">Durum Bildirimleri</label>
                    <small class="form-text text-muted">Bot başlatma/durdurma</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telegram_message_format">Mesaj Formatı</label>
                    <select name="telegram_message_format" id="telegram_message_format" class="form-control">
                        <option value="simple" <?php echo (!isset($settings['notifications']['telegram']['message_format']) || $settings['notifications']['telegram']['message_format'] == 'simple') ? 'selected' : ''; ?>>Basit</option>
                        <option value="detailed" <?php echo isset($settings['notifications']['telegram']['message_format']) && $settings['notifications']['telegram']['message_format'] == 'detailed' ? 'selected' : ''; ?>>Detaylı</option>
                        <option value="custom" <?php echo isset($settings['notifications']['telegram']['message_format']) && $settings['notifications']['telegram']['message_format'] == 'custom' ? 'selected' : ''; ?>>Özel</option>
                    </select>
                    <small class="form-text text-muted">Telegram mesaj formatı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telegram_rate_limit">Mesaj Sınırı (dakika)</label>
                    <input type="number" name="telegram_rate_limit" id="telegram_rate_limit" class="form-control" 
                           value="<?php echo isset($settings['notifications']['telegram']['rate_limit']) ? $settings['notifications']['telegram']['rate_limit'] : 1; ?>" min="0" max="60">
                    <small class="form-text text-muted">Aynı türde mesajlar arası minimum süre</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <button type="button" class="btn btn-info btn-sm" id="test_telegram">
                    <i class="fas fa-paper-plane"></i> Test Mesajı Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- E-posta Bildirimleri -->
<div class="feature-card mb-4 <?php echo isset($settings['notifications']['email']['enabled']) && $settings['notifications']['email']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-envelope"></i> E-posta Bildirimleri</h5>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="email_enabled" name="email_enabled"
                       <?php echo isset($settings['notifications']['email']['enabled']) && $settings['notifications']['email']['enabled'] ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="email_enabled"></label>
            </div>
        </div>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email_smtp_host">SMTP Sunucu</label>
                    <input type="text" name="email_smtp_host" id="email_smtp_host" class="form-control" 
                           value="<?php echo isset($settings['notifications']['email']['smtp_host']) ? $settings['notifications']['email']['smtp_host'] : 'smtp.gmail.com'; ?>" 
                           placeholder="smtp.gmail.com">
                    <small class="form-text text-muted">SMTP sunucu adresi</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email_smtp_port">SMTP Port</label>
                    <input type="number" name="email_smtp_port" id="email_smtp_port" class="form-control" 
                           value="<?php echo isset($settings['notifications']['email']['smtp_port']) ? $settings['notifications']['email']['smtp_port'] : 587; ?>" 
                           placeholder="587">
                    <small class="form-text text-muted">SMTP port numarası</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email_username">E-posta Adresi</label>
                    <input type="email" name="email_username" id="email_username" class="form-control" 
                           value="<?php echo isset($settings['notifications']['email']['username']) ? $settings['notifications']['email']['username'] : ''; ?>" 
                           placeholder="bot@example.com">
                    <small class="form-text text-muted">Gönderen e-posta adresi</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email_password">E-posta Şifresi</label>
                    <input type="password" name="email_password" id="email_password" class="form-control" 
                           value="<?php echo isset($settings['notifications']['email']['password']) ? $settings['notifications']['email']['password'] : ''; ?>" 
                           placeholder="••••••••">
                    <small class="form-text text-muted">E-posta şifresi veya uygulama şifresi</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="email_recipients">Alıcı E-posta Adresleri</label>
                    <textarea name="email_recipients" id="email_recipients" class="form-control" rows="2" 
                              placeholder="admin@example.com, user@example.com"><?php echo isset($settings['notifications']['email']['recipients']) ? implode(', ', $settings['notifications']['email']['recipients']) : ''; ?></textarea>
                    <small class="form-text text-muted">Virgülle ayrılmış e-posta adresleri</small>
                </div>
            </div>
        </div>
        
        <!-- E-posta Bildirim Türleri -->
        <div class="row">
            <div class="col-12">
                <h6 class="font-weight-bold mb-3">E-posta Bildirim Türleri</h6>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="email_critical" name="email_critical"
                           <?php echo isset($settings['notifications']['email']['types']['critical']) && $settings['notifications']['email']['types']['critical'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="email_critical">Kritik Hatalar</label>
                    <small class="form-text text-muted">Acil müdahale gereken durumlar</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="email_daily_reports" name="email_daily_reports"
                           <?php echo isset($settings['notifications']['email']['types']['daily_reports']) && $settings['notifications']['email']['types']['daily_reports'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="email_daily_reports">Günlük Raporlar</label>
                    <small class="form-text text-muted">Günlük performans raporları</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="email_weekly_reports" name="email_weekly_reports"
                           <?php echo isset($settings['notifications']['email']['types']['weekly_reports']) && $settings['notifications']['email']['types']['weekly_reports'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="email_weekly_reports">Haftalık Raporlar</label>
                    <small class="form-text text-muted">Haftalık analiz raporları</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="email_system_status" name="email_system_status"
                           <?php echo isset($settings['notifications']['email']['types']['system_status']) && $settings['notifications']['email']['types']['system_status'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="email_system_status">Sistem Durumu</label>
                    <small class="form-text text-muted">Bot başlatma/durdurma bildirimleri</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <button type="button" class="btn btn-info btn-sm" id="test_email">
                    <i class="fas fa-envelope"></i> Test E-postası Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Günlükleme Ayarları -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-file-alt"></i> Günlükleme Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="log_level">Günlük Seviyesi</label>
                    <select name="log_level" id="log_level" class="form-control">
                        <option value="DEBUG" <?php echo isset($settings['logging']['level']) && $settings['logging']['level'] == 'DEBUG' ? 'selected' : ''; ?>>DEBUG (Tüm Detaylar)</option>
                        <option value="INFO" <?php echo (!isset($settings['logging']['level']) || $settings['logging']['level'] == 'INFO') ? 'selected' : ''; ?>>INFO (Genel Bilgiler)</option>
                        <option value="WARNING" <?php echo isset($settings['logging']['level']) && $settings['logging']['level'] == 'WARNING' ? 'selected' : ''; ?>>WARNING (Uyarılar)</option>
                        <option value="ERROR" <?php echo isset($settings['logging']['level']) && $settings['logging']['level'] == 'ERROR' ? 'selected' : ''; ?>>ERROR (Sadece Hatalar)</option>
                        <option value="CRITICAL" <?php echo isset($settings['logging']['level']) && $settings['logging']['level'] == 'CRITICAL' ? 'selected' : ''; ?>>CRITICAL (Kritik Hatalar)</option>
                    </select>
                    <small class="form-text text-muted">Kaydedilecek minimum günlük seviyesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="log_file_size">Maksimum Dosya Boyutu</label>
                    <div class="input-group">
                        <input type="number" name="log_file_size" id="log_file_size" class="form-control" 
                               value="<?php echo isset($settings['logging']['max_file_size']) ? $settings['logging']['max_file_size'] : 10; ?>" min="1" max="100">
                        <div class="input-group-append">
                            <span class="input-group-text">MB</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Log dosyası maksimum boyutu</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="log_retention_days">Saklama Süresi</label>
                    <div class="input-group">
                        <input type="number" name="log_retention_days" id="log_retention_days" class="form-control" 
                               value="<?php echo isset($settings['logging']['retention_days']) ? $settings['logging']['retention_days'] : 30; ?>" min="1" max="365">
                        <div class="input-group-append">
                            <span class="input-group-text">gün</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Log dosyalarının saklanma süresi</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="log_format">Log Formatı</label>
                    <select name="log_format" id="log_format" class="form-control">
                        <option value="simple" <?php echo (!isset($settings['logging']['format']) || $settings['logging']['format'] == 'simple') ? 'selected' : ''; ?>>Basit</option>
                        <option value="detailed" <?php echo isset($settings['logging']['format']) && $settings['logging']['format'] == 'detailed' ? 'selected' : ''; ?>>Detaylı</option>
                        <option value="json" <?php echo isset($settings['logging']['format']) && $settings['logging']['format'] == 'json' ? 'selected' : ''; ?>>JSON</option>
                        <option value="custom" <?php echo isset($settings['logging']['format']) && $settings['logging']['format'] == 'custom' ? 'selected' : ''; ?>>Özel</option>
                    </select>
                    <small class="form-text text-muted">Log çıktı formatı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="log_backup_count">Yedek Dosya Sayısı</label>
                    <input type="number" name="log_backup_count" id="log_backup_count" class="form-control" 
                           value="<?php echo isset($settings['logging']['backup_count']) ? $settings['logging']['backup_count'] : 5; ?>" min="1" max="20">
                    <small class="form-text text-muted">Saklanacak yedek log dosyası sayısı</small>
                </div>
            </div>
        </div>
        
        <!-- Log Kategorileri -->
        <div class="row">
            <div class="col-12">
                <h6 class="font-weight-bold mb-3">Log Kategorileri</h6>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="log_trades" name="log_trades"
                           <?php echo (!isset($settings['logging']['categories']['trades']) || $settings['logging']['categories']['trades']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="log_trades">İşlem Logları</label>
                    <small class="form-text text-muted">Alım/satım işlemleri</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="log_indicators" name="log_indicators"
                           <?php echo isset($settings['logging']['categories']['indicators']) && $settings['logging']['categories']['indicators'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="log_indicators">İndikatör Logları</label>
                    <small class="form-text text-muted">İndikatör hesaplamaları</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="log_api" name="log_api"
                           <?php echo isset($settings['logging']['categories']['api']) && $settings['logging']['categories']['api'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="log_api">API Logları</label>
                    <small class="form-text text-muted">API istekleri ve yanıtları</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="log_errors" name="log_errors"
                           <?php echo (!isset($settings['logging']['categories']['errors']) || $settings['logging']['categories']['errors']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="log_errors">Hata Logları</label>
                    <small class="form-text text-muted">Sistem hataları</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="log_rotation" name="log_rotation"
                           <?php echo (!isset($settings['logging']['rotation']) || $settings['logging']['rotation']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="log_rotation">Otomatik Log Rotasyonu</label>
                    <small class="form-text text-muted">Log dosyalarını otomatik olarak böl ve sıkıştır</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="log_compression" name="log_compression"
                           <?php echo isset($settings['logging']['compression']) && $settings['logging']['compression'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="log_compression">Log Sıkıştırma</label>
                    <small class="form-text text-muted">Eski log dosyalarını sıkıştır</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performans İzleme -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-tachometer-alt"></i> Performans İzleme</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="performance_monitoring_interval">İzleme Aralığı</label>
                    <div class="input-group">
                        <input type="number" name="performance_monitoring_interval" id="performance_monitoring_interval" class="form-control" 
                               value="<?php echo isset($settings['monitoring']['performance_interval']) ? $settings['monitoring']['performance_interval'] : 60; ?>" min="10" max="300">
                        <div class="input-group-append">
                            <span class="input-group-text">saniye</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Sistem performansı kontrol aralığı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="memory_usage_threshold">Bellek Kullanım Eşiği</label>
                    <div class="input-group">
                        <input type="number" name="memory_usage_threshold" id="memory_usage_threshold" class="form-control" 
                               value="<?php echo isset($settings['monitoring']['memory_threshold']) ? $settings['monitoring']['memory_threshold'] : 80; ?>" min="50" max="95">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Bellek kullanımı uyarı eşiği</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="cpu_monitoring" name="cpu_monitoring"
                           <?php echo (!isset($settings['monitoring']['cpu_monitoring']) || $settings['monitoring']['cpu_monitoring']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="cpu_monitoring">CPU İzleme</label>
                    <small class="form-text text-muted">CPU kullanımını izle ve raporla</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="disk_monitoring" name="disk_monitoring"
                           <?php echo (!isset($settings['monitoring']['disk_monitoring']) || $settings['monitoring']['disk_monitoring']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="disk_monitoring">Disk İzleme</label>
                    <small class="form-text text-muted">Disk kullanımını izle ve raporla</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Telegram test mesajı gönder
    $('#test_telegram').click(function() {
        var button = $(this);
        var originalText = button.html();
        
        // Form verilerini al
        var botToken = $('#telegram_bot_token').val();
        var chatId = $('#telegram_chat_id').val();
        
        if (!botToken || !chatId) {
            alert('Lütfen önce Telegram Bot Token ve Chat ID bilgilerini girin!');
            return;
        }
        
        button.html('<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/test_telegram.php',
            method: 'POST',
            data: {
                bot_token: botToken,
                chat_id: chatId,
                message: 'Bot ayarları test mesajı - ' + new Date().toLocaleString()
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Test mesajı başarıyla gönderildi!');
                } else {
                    alert('❌ Hata: ' + response.error);
                }
            },
            error: function() {
                alert('❌ Bağlantı hatası oluştu!');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // E-posta test mesajı gönder
    $('#test_email').click(function() {
        var button = $(this);
        var originalText = button.html();
        
        // Form verilerini al
        var smtpHost = $('#email_smtp_host').val();
        var smtpPort = $('#email_smtp_port').val();
        var username = $('#email_username').val();
        var password = $('#email_password').val();
        var recipients = $('#email_recipients').val();
        
        if (!smtpHost || !username || !password || !recipients) {
            alert('Lütfen önce e-posta ayarlarını eksiksiz doldurun!');
            return;
        }
        
        button.html('<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/test_email.php',
            method: 'POST',
            data: {
                smtp_host: smtpHost,
                smtp_port: smtpPort,
                username: username,
                password: password,
                recipients: recipients,
                subject: 'Bot Ayarları Test E-postası',
                message: 'Bu bir test e-postasıdır. Gönderim zamanı: ' + new Date().toLocaleString()
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Test e-postası başarıyla gönderildi!');
                } else {
                    alert('❌ Hata: ' + response.error);
                }
            },
            error: function() {
                alert('❌ Bağlantı hatası oluştu!');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>