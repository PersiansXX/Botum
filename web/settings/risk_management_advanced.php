<?php
// Risk Management Advanced Tab Content
?>
<div class="info-box warning mb-4">
    <i class="fas fa-shield-alt"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Gelişmiş Risk Yönetimi</h6>
        <p class="mb-0">Bu bölüm, dinamik pozisyon boyutlandırma, volatilite temelli stop-loss ayarları ve adaptif kar alma stratejileri içerir. Bu özellikler, risk yönetiminizi piyasa koşullarına göre otomatik olarak ayarlar.</p>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <strong>İpucu:</strong> Bu ayarları aktif etmeden önce, temel risk yönetimi stratejilerinizi belirlemiş olduğunuzdan emin olun.
</div>

<div class="feature-card mb-4 <?php echo isset($settings['advanced_risk_management']['enabled']) && $settings['advanced_risk_management']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-user-shield"></i> Gelişmiş Risk Yönetimi</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="advanced_risk_enabled" name="advanced_risk_enabled"
                   <?php echo isset($settings['advanced_risk_management']['enabled']) && $settings['advanced_risk_management']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="advanced_risk_enabled">Gelişmiş risk yönetimini etkinleştir</label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Gelişmiş risk yönetimi, piyasa volatilitesine ve performansınıza göre otomatik risk ayarlamaları yapar.
        </p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="dynamic_position_sizing">Dinamik Pozisyon Boyutlandırma</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input advanced-risk-setting" id="dynamic_position_sizing" name="dynamic_position_sizing"
                               <?php echo isset($settings['advanced_risk_management']['dynamic_position_sizing']) && $settings['advanced_risk_management']['dynamic_position_sizing'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="dynamic_position_sizing">Piyasa koşullarına göre pozisyon boyutunu ayarla</label>
                    </div>
                    <small class="form-text text-muted">Volatilite arttığında pozisyon boyutunu küçültür</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="position_size_method">Pozisyon Boyutu Metodü</label>
                    <select name="position_size_method" id="position_size_method" class="form-control advanced-risk-setting">
                        <option value="fixed" <?php echo isset($settings['advanced_risk_management']['position_size_method']) && $settings['advanced_risk_management']['position_size_method'] == 'fixed' ? 'selected' : ''; ?>>Sabit</option>
                        <option value="kelly" <?php echo isset($settings['advanced_risk_management']['position_size_method']) && $settings['advanced_risk_management']['position_size_method'] == 'kelly' ? 'selected' : ''; ?>>Kelly Criterion</option>
                        <option value="volatility_adjusted" <?php echo isset($settings['advanced_risk_management']['position_size_method']) && $settings['advanced_risk_management']['position_size_method'] == 'volatility_adjusted' ? 'selected' : ''; ?>>Volatilite Ayarlı</option>
                    </select>
                    <small class="form-text text-muted">Pozisyon boyutunu hesaplama yöntemi</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="volatility_based_stops">Volatilite Temelli Stop-Loss</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input advanced-risk-setting" id="volatility_based_stops" name="volatility_based_stops"
                               <?php echo isset($settings['advanced_risk_management']['volatility_based_stops']) && $settings['advanced_risk_management']['volatility_based_stops'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="volatility_based_stops">ATR bazlı stop-loss kullan</label>
                    </div>
                    <small class="form-text text-muted">Stop-loss seviyesini volatiliteye göre ayarlar</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="adaptive_take_profit">Adaptif Kar Alma</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input advanced-risk-setting" id="adaptive_take_profit" name="adaptive_take_profit"
                               <?php echo isset($settings['advanced_risk_management']['adaptive_take_profit']) && $settings['advanced_risk_management']['adaptive_take_profit'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="adaptive_take_profit">Trend gücüne göre kar alma seviyesini ayarla</label>
                    </div>
                    <small class="form-text text-muted">Güçlü trendlerde kar alma seviyesini artırır</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="auto_adjust_risk">Otomatik Risk Ayarlama</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input advanced-risk-setting" id="auto_adjust_risk" name="auto_adjust_risk"
                               <?php echo isset($settings['advanced_risk_management']['auto_adjust_risk']) && $settings['advanced_risk_management']['auto_adjust_risk'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="auto_adjust_risk">Performansa göre risk seviyesini otomatik ayarla</label>
                    </div>
                    <small class="form-text text-muted">Kayıp serilerde riski azaltır, kazanç serilerde artırır</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_risk_per_trade_advanced">İşlem Başına Maksimum Risk</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="max_risk_per_trade" id="max_risk_per_trade_advanced" class="form-control advanced-risk-setting" 
                               value="<?php echo isset($settings['advanced_risk_management']['max_risk_per_trade']) ? $settings['advanced_risk_management']['max_risk_per_trade'] : '2'; ?>" min="0.1" max="10">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Sermayenin yüzde kaçını tek işlemde riske atabilirsiniz</small>
                </div>
            </div>
        </div>
        
        <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Uyarı:</strong> Gelişmiş risk yönetimi özellikleri, temel risk yönetimi kurallarınızla birlikte çalışır. Bu özellikleri aktif etmeden önce backtesting yapmanız önerilir.
        </div>
    </div>
</div>