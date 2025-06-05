<?php
// Adaptive Parameters Tab Content
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Adaptif Parametreler</h6>
        <p class="mb-0">Adaptif parametreler sistemi, botun performansını ve piyasa koşullarını sürekli analiz ederek indikatör ve strateji parametrelerini otomatik olarak optimize eder. Bu sistem, değişen piyasa koşullarına uyum sağlar.</p>
    </div>
</div>

<div class="feature-card mb-4 <?php echo isset($settings['adaptive_parameters']['enabled']) && $settings['adaptive_parameters']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-sliders-h"></i> Adaptif Parametreler</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="adaptive_params_enabled" name="adaptive_params_enabled"
                   <?php echo isset($settings['adaptive_parameters']['enabled']) && $settings['adaptive_parameters']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="adaptive_params_enabled">Adaptif parametreleri etkinleştir</label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Piyasa koşullarına göre bot parametrelerini otomatik olarak ayarlar ve optimize eder.
        </p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="adaptation_speed">Adaptasyon Hızı</label>
                    <select name="adaptation_speed" id="adaptation_speed" class="form-control adaptive-param-setting">
                        <option value="0.1" <?php echo isset($settings['adaptive_parameters']['adaptation_speed']) && $settings['adaptive_parameters']['adaptation_speed'] == 0.1 ? 'selected' : ''; ?>>Çok Yavaş (0.1)</option>
                        <option value="0.3" <?php echo isset($settings['adaptive_parameters']['adaptation_speed']) && $settings['adaptive_parameters']['adaptation_speed'] == 0.3 ? 'selected' : ''; ?>>Yavaş (0.3)</option>
                        <option value="0.5" <?php echo isset($settings['adaptive_parameters']['adaptation_speed']) && $settings['adaptive_parameters']['adaptation_speed'] == 0.5 ? 'selected' : ''; ?>>Orta (0.5)</option>
                        <option value="0.7" <?php echo isset($settings['adaptive_parameters']['adaptation_speed']) && $settings['adaptive_parameters']['adaptation_speed'] == 0.7 ? 'selected' : ''; ?>>Hızlı (0.7)</option>
                        <option value="0.9" <?php echo isset($settings['adaptive_parameters']['adaptation_speed']) && $settings['adaptive_parameters']['adaptation_speed'] == 0.9 ? 'selected' : ''; ?>>Çok Hızlı (0.9)</option>
                    </select>
                    <small class="form-text text-muted">Parametrelerin ne kadar hızlı değişeceğini belirler</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="learning_rate">Öğrenme Oranı</label>
                    <input type="number" step="0.01" name="learning_rate" id="learning_rate" class="form-control adaptive-param-setting" 
                           value="<?php echo isset($settings['adaptive_parameters']['learning_rate']) ? $settings['adaptive_parameters']['learning_rate'] : '0.01'; ?>" min="0.001" max="0.1">
                    <small class="form-text text-muted">Algoritmanın öğrenme hızı (0.001-0.1)</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="market_regime_detection">Piyasa Rejimi Tespiti</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input adaptive-param-setting" id="market_regime_detection" name="market_regime_detection"
                               <?php echo isset($settings['adaptive_parameters']['market_regime_detection']) && $settings['adaptive_parameters']['market_regime_detection'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="market_regime_detection">Piyasa rejimini otomatik tespit et</label>
                    </div>
                    <small class="form-text text-muted">Trending, ranging, volatile rejimlerini tespit eder</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="volatility_adjustment">Volatilite Ayarlaması</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input adaptive-param-setting" id="volatility_adjustment" name="volatility_adjustment"
                               <?php echo isset($settings['adaptive_parameters']['volatility_adjustment']) && $settings['adaptive_parameters']['volatility_adjustment'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="volatility_adjustment">Volatiliteye göre parametreleri ayarla</label>
                    </div>
                    <small class="form-text text-muted">Yüksek volatilitede daha konservatif ayarlar kullanır</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="trend_strength_adjustment">Trend Gücü Ayarlaması</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input adaptive-param-setting" id="trend_strength_adjustment" name="trend_strength_adjustment"
                               <?php echo isset($settings['adaptive_parameters']['trend_strength_adjustment']) && $settings['adaptive_parameters']['trend_strength_adjustment'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="trend_strength_adjustment">Trend gücüne göre parametreleri ayarla</label>
                    </div>
                    <small class="form-text text-muted">Güçlü trendlerde farklı parametreler kullanır</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reset_after_market_shift">Piyasa Değişiminde Sıfırla</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input adaptive-param-setting" id="reset_after_market_shift" name="reset_after_market_shift"
                               <?php echo isset($settings['adaptive_parameters']['reset_after_market_shift']) && $settings['adaptive_parameters']['reset_after_market_shift'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="reset_after_market_shift">Büyük piyasa değişimlerinde parametreleri sıfırla</label>
                    </div>
                    <small class="form-text text-muted">Ani piyasa değişimlerinde öğrenilen parametreleri sıfırlar</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="min_history_required">Minimum Geçmiş Verisi</label>
                    <input type="number" name="min_history_required" id="min_history_required" class="form-control adaptive-param-setting" 
                           value="<?php echo isset($settings['adaptive_parameters']['min_history_required']) ? $settings['adaptive_parameters']['min_history_required'] : '100'; ?>" min="50" max="1000">
                    <small class="form-text text-muted">Adaptasyon başlamadan önce gereken minimum işlem sayısı</small>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-lightbulb"></i>
            <strong>Kullanım Önerileri:</strong>
            <ul class="mb-0 mt-2">
                <li><strong>Başlangıç:</strong> Orta adaptasyon hızı ve düşük öğrenme oranı ile başlayın</li>
                <li><strong>Stabil piyasalar:</strong> Yavaş adaptasyon kullanın</li>
                <li><strong>Volatil piyasalar:</strong> Hızlı adaptasyon ve volatilite ayarlamasını aktif edin</li>
                <li><strong>Yeni hesaplar:</strong> Minimum geçmiş verisi değerini yüksek tutun</li>
            </ul>
        </div>
        
        <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Dikkat:</strong> Adaptif parametreler sistemi deneysel bir özelliktir. Canlı işlemlerden önce paper trading ile test etmeniz önerilir.
        </div>
    </div>
</div>