<?php
// Smart Trend Tab Content
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Akıllı Trend Analizi</h6>
        <p class="mb-0">Akıllı trend analizi, gelişmiş algoritmalar kullanarak trend yönünü ve gücünü tespit eder. Çoklu zaman aralığı analizini kullanarak daha güvenilir trend sinyalleri üretir.</p>
    </div>
</div>

<div class="feature-card mb-4 <?php echo isset($settings['integration_settings']['use_smart_trend']) && $settings['integration_settings']['use_smart_trend'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-line"></i> Akıllı Trend Analizi</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="use_smart_trend" name="use_smart_trend"
                   <?php echo isset($settings['integration_settings']['use_smart_trend']) && $settings['integration_settings']['use_smart_trend'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="use_smart_trend">Akıllı trend analizini etkinleştir</label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Gelişmiş trend tespit algoritmaları kullanarak piyasa yönünü ve trend gücünü analiz eder.
        </p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="trend_detection_method">Trend Tespit Yöntemi</label>
                    <select name="trend_detection_method" id="trend_detection_method" class="form-control smart-trend-setting">
                        <option value="ema_cross" <?php echo isset($settings['integration_settings']['smart_trend_settings']['detection_method']) && $settings['integration_settings']['smart_trend_settings']['detection_method'] == 'ema_cross' ? 'selected' : ''; ?>>EMA Kesişimi</option>
                        <option value="adx_based" <?php echo isset($settings['integration_settings']['smart_trend_settings']['detection_method']) && $settings['integration_settings']['smart_trend_settings']['detection_method'] == 'adx_based' ? 'selected' : ''; ?>>ADX Bazlı</option>
                        <option value="price_action" <?php echo isset($settings['integration_settings']['smart_trend_settings']['detection_method']) && $settings['integration_settings']['smart_trend_settings']['detection_method'] == 'price_action' ? 'selected' : ''; ?>>Fiyat Hareketi</option>
                        <option value="composite" <?php echo isset($settings['integration_settings']['smart_trend_settings']['detection_method']) && $settings['integration_settings']['smart_trend_settings']['detection_method'] == 'composite' ? 'selected' : ''; ?>>Kompozit (Tümü)</option>
                    </select>
                    <small class="form-text text-muted">Trend tespiti için kullanılacak yöntem</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="trend_sensitivity">Trend Hassasiyeti</label>
                    <select name="trend_sensitivity" id="trend_sensitivity" class="form-control smart-trend-setting">
                        <option value="0.3" <?php echo isset($settings['integration_settings']['smart_trend_settings']['sensitivity']) && $settings['integration_settings']['smart_trend_settings']['sensitivity'] == 0.3 ? 'selected' : ''; ?>>Düşük (0.3)</option>
                        <option value="0.5" <?php echo isset($settings['integration_settings']['smart_trend_settings']['sensitivity']) && $settings['integration_settings']['smart_trend_settings']['sensitivity'] == 0.5 ? 'selected' : ''; ?>>Orta (0.5)</option>
                        <option value="0.7" <?php echo isset($settings['integration_settings']['smart_trend_settings']['sensitivity']) && $settings['integration_settings']['smart_trend_settings']['sensitivity'] == 0.7 ? 'selected' : ''; ?>>Yüksek (0.7)</option>
                        <option value="0.9" <?php echo isset($settings['integration_settings']['smart_trend_settings']['sensitivity']) && $settings['integration_settings']['smart_trend_settings']['sensitivity'] == 0.9 ? 'selected' : ''; ?>>Çok Yüksek (0.9)</option>
                    </select>
                    <small class="form-text text-muted">Düşük = daha az sinyal, Yüksek = daha fazla sinyal</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="trend_lookback_period">Geriye Bakış Periyodu</label>
                    <input type="number" name="trend_lookback_period" id="trend_lookback_period" class="form-control smart-trend-setting" 
                           value="<?php echo isset($settings['integration_settings']['smart_trend_settings']['lookback_period']) ? $settings['integration_settings']['smart_trend_settings']['lookback_period'] : '20'; ?>" min="5" max="100">
                    <small class="form-text text-muted">Trend analizi için kullanılacak mum sayısı</small>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="trend_confirmation_period">Onay Periyodu</label>
                    <input type="number" name="trend_confirmation_period" id="trend_confirmation_period" class="form-control smart-trend-setting" 
                           value="<?php echo isset($settings['integration_settings']['smart_trend_settings']['confirmation_period']) ? $settings['integration_settings']['smart_trend_settings']['confirmation_period'] : '3'; ?>" min="1" max="10">
                    <small class="form-text text-muted">Trend onayı için gereken mum sayısı</small>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="signal_quality_threshold">Sinyal Kalite Eşiği</label>
                    <input type="number" step="0.1" name="signal_quality_threshold" id="signal_quality_threshold" class="form-control smart-trend-setting" 
                           value="<?php echo isset($settings['integration_settings']['smart_trend_settings']['signal_quality_threshold']) ? $settings['integration_settings']['smart_trend_settings']['signal_quality_threshold'] : '0.6'; ?>" min="0.1" max="1.0">
                    <small class="form-text text-muted">Minimum sinyal kalite skoru (0.1-1.0)</small>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-lightbulb"></i>
            <strong>Öneriler:</strong>
            <ul class="mb-0 mt-2">
                <li><strong>Volatil piyasalarda:</strong> Düşük hassasiyet ve yüksek onay periyodu kullanın</li>
                <li><strong>Sakin piyasalarda:</strong> Yüksek hassasiyet ve düşük onay periyodu kullanın</li>
                <li><strong>Kompozit yöntem:</strong> En güvenilir ancak en yavaş sinyalleri verir</li>
            </ul>
        </div>
    </div>
</div>