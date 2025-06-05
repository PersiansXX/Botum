<?php
// API Optimization Tab Content
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">API Optimizasyonu</h6>
        <p class="mb-0">API optimizasyonu, borsa API'lerini daha verimli kullanarak rate limit sorunlarını önler ve bot performansını artırır. Akıllı API çağrı yönetimi ve cache sistemi ile daha stabil çalışma sağlar.</p>
    </div>
</div>

<div class="feature-card mb-4 <?php echo isset($settings['api_optimization']['enabled']) && $settings['api_optimization']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-tachometer-alt"></i> API Optimizasyonu</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="api_optimization_enabled" name="api_optimization_enabled"
                   <?php echo isset($settings['api_optimization']['enabled']) && $settings['api_optimization']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="api_optimization_enabled">API optimizasyonunu etkinleştir</label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            API çağrılarını optimize ederek daha hızlı ve güvenilir veri alımı sağlar.
        </p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="api_call_limit_per_minute">Dakika Başına API Çağrı Limiti</label>
                    <input type="number" name="api_call_limit_per_minute" id="api_call_limit_per_minute" class="form-control api-optimization-setting" 
                           value="<?php echo isset($settings['api_optimization']['api_call_limit_per_minute']) ? $settings['api_optimization']['api_call_limit_per_minute'] : '100'; ?>" min="10" max="1000">
                    <small class="form-text text-muted">Borsanın limit değerinin %80'i kadar ayarlayın</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="api_call_distribution">API Çağrı Dağılımı</label>
                    <select name="api_call_distribution" id="api_call_distribution" class="form-control api-optimization-setting">
                        <option value="even" <?php echo isset($settings['api_optimization']['api_call_distribution']) && $settings['api_optimization']['api_call_distribution'] == 'even' ? 'selected' : ''; ?>>Eşit Dağılım</option>
                        <option value="priority_based" <?php echo isset($settings['api_optimization']['api_call_distribution']) && $settings['api_optimization']['api_call_distribution'] == 'priority_based' ? 'selected' : ''; ?>>Öncelik Bazlı</option>
                        <option value="adaptive" <?php echo isset($settings['api_optimization']['api_call_distribution']) && $settings['api_optimization']['api_call_distribution'] == 'adaptive' ? 'selected' : ''; ?>>Adaptif</option>
                    </select>
                    <small class="form-text text-muted">API çağrılarının nasıl dağıtılacağını belirler</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="cache_duration">Cache Süresi</label>
                    <div class="input-group">
                        <input type="number" name="cache_duration" id="cache_duration" class="form-control api-optimization-setting" 
                               value="<?php echo isset($settings['api_optimization']['cache_duration']) ? $settings['api_optimization']['cache_duration'] : '30'; ?>" min="5" max="300">
                        <div class="input-group-append">
                            <span class="input-group-text">saniye</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Verilerin cache'de tutulma süresi</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="prioritize_active_trades">Aktif İşlemleri Önceliklendir</label>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input api-optimization-setting" id="prioritize_active_trades" name="prioritize_active_trades"
                               <?php echo isset($settings['api_optimization']['prioritize_active_trades']) && $settings['api_optimization']['prioritize_active_trades'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="prioritize_active_trades">Açık pozisyonlara öncelik ver</label>
                    </div>
                    <small class="form-text text-muted">Açık pozisyonların verilerini daha sık günceller</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reduce_timeframes_count">Zaman Aralığı Optimizasyonu</label>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input api-optimization-setting" id="reduce_timeframes_count" name="reduce_timeframes_count"
                               <?php echo isset($settings['api_optimization']['reduce_timeframes_count']) && $settings['api_optimization']['reduce_timeframes_count'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="reduce_timeframes_count">API yoğunluğunda zaman aralığı sayısını azalt</label>
                    </div>
                    <small class="form-text text-muted">Yoğun dönemlerde sadece ana zaman aralığını kullanır</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="optimize_indicator_calculations">İndikatör Hesaplama Optimizasyonu</label>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input api-optimization-setting" id="optimize_indicator_calculations" name="optimize_indicator_calculations"
                               <?php echo isset($settings['api_optimization']['optimize_indicator_calculations']) && $settings['api_optimization']['optimize_indicator_calculations'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="optimize_indicator_calculations">İndikatör hesaplamalarını optimize et</label>
                    </div>
                    <small class="form-text text-muted">Gereksiz indikatör hesaplamalarını önler</small>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-lightbulb"></i>
            <strong>Optimizasyon İpuçları:</strong>
            <ul class="mb-0 mt-2">
                <li><strong>Binance:</strong> Dakikada 1200 çağrı limiti - 900-1000 arası ayarlayın</li>
                <li><strong>KuCoin:</strong> Dakikada 600 çağrı limiti - 400-500 arası ayarlayın</li>
                <li><strong>Cache süresi:</strong> Düşük volatilitede artırın, yüksek volatilitede azaltın</li>
                <li><strong>Öncelik sistemi:</strong> Çok coin takip ediyorsanız mutlaka aktif edin</li>
            </ul>
        </div>
        
        <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Uyarı:</strong> API limitlerini aşarsanız geçici olarak banlanabilirsiniz. Conservative değerler kullanın.
        </div>
    </div>
</div>