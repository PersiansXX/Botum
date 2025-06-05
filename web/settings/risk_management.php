<?php
// Risk Yönetimi Modülü
?>
<div class="info-box warning mb-4">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Risk Yönetimi Hakkında</h6>
        <p class="mb-0">Risk yönetimi, trading botunun en kritik bileşenlerinden biridir. Bu ayarlar, sermayenizi korumak ve uzun vadeli başarı için gereklidir. Ayarları yaparken dikkatli olun ve test modunda çalıştırmayı unutmayın.</p>
    </div>
</div>

<!-- Ana Risk Yönetimi Toggle -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-shield-alt"></i> Risk Yönetimi Etkinleştir</h5>
    </div>
    <div class="feature-card-body">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="risk_enabled" name="risk_enabled"
                   <?php echo isset($settings['risk_management']['enabled']) && $settings['risk_management']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="risk_enabled">Risk Yönetimini Etkinleştir</label>
            <small class="form-text text-muted">Tüm risk yönetimi özelliklerini etkinleştirir</small>
        </div>
    </div>
</div>

<!-- Genel Risk Ayarları -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-shield-alt"></i> Genel Risk Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="max_portfolio_risk">Maksimum Portföy Riski</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="max_portfolio_risk" id="max_portfolio_risk" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['max_portfolio_risk']) ? $settings['risk_management']['max_portfolio_risk'] : 5.0; ?>" min="0.5" max="20">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Toplam portföyün maksimum risk yüzdesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="max_position_size">Maksimum Pozisyon Büyüklüğü</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="max_position_size" id="max_position_size" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['max_position_size']) ? $settings['risk_management']['max_position_size'] : 10.0; ?>" min="1" max="50">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Tek bir pozisyon için maksimum bakiye yüzdesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="max_daily_loss">Günlük Maksimum Kayıp</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="max_daily_loss" id="max_daily_loss" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['max_daily_loss']) ? $settings['risk_management']['max_daily_loss'] : 3.0; ?>" min="0.5" max="10">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Günlük maksimum kayıp yüzdesi (botu durdurur)</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_consecutive_losses">Ardışık Maksimum Kayıp</label>
                    <input type="number" name="max_consecutive_losses" id="max_consecutive_losses" class="form-control" 
                           value="<?php echo isset($settings['risk_management']['max_consecutive_losses']) ? $settings['risk_management']['max_consecutive_losses'] : 5; ?>" min="2" max="20">
                    <small class="form-text text-muted">Ardışık kayıp işlem sayısı (botu durdurur)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="recovery_mode_threshold">İyileşme Modu Eşiği</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="recovery_mode_threshold" id="recovery_mode_threshold" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['recovery_mode_threshold']) ? $settings['risk_management']['recovery_mode_threshold'] : 2.0; ?>" min="0.5" max="5">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Bu kayıp sonrası iyileşme moduna geçer</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="enable_drawdown_protection" name="enable_drawdown_protection"
                           <?php echo isset($settings['risk_management']['enable_drawdown_protection']) && $settings['risk_management']['enable_drawdown_protection'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="enable_drawdown_protection">Drawdown Koruması</label>
                    <small class="form-text text-muted">Aşırı kayıplarda otomatik koruma devreye girer</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="emergency_stop" name="emergency_stop"
                           <?php echo isset($settings['risk_management']['emergency_stop']) && $settings['risk_management']['emergency_stop'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="emergency_stop">Acil Durum Durdurma</label>
                    <small class="form-text text-muted">Kritik kayıplarda tüm pozisyonları kapatır</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stop Loss Ayarları -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-hand-paper"></i> Stop Loss Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="default_stop_loss">Varsayılan Stop Loss</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="default_stop_loss" id="default_stop_loss" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['stop_loss']['default_percentage']) ? $settings['risk_management']['stop_loss']['default_percentage'] : 2.0; ?>" min="0.5" max="10">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Giriş fiyatından aşağı stop loss yüzdesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="max_stop_loss">Maksimum Stop Loss</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="max_stop_loss" id="max_stop_loss" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['stop_loss']['max_percentage']) ? $settings['risk_management']['stop_loss']['max_percentage'] : 5.0; ?>" min="1" max="15">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">İzin verilen maksimum stop loss yüzdesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="stop_loss_type">Stop Loss Türü</label>
                    <select name="stop_loss_type" id="stop_loss_type" class="form-control">
                        <option value="percentage" <?php echo (!isset($settings['risk_management']['stop_loss']['type']) || $settings['risk_management']['stop_loss']['type'] == 'percentage') ? 'selected' : ''; ?>>Yüzde Bazlı</option>
                        <option value="atr" <?php echo isset($settings['risk_management']['stop_loss']['type']) && $settings['risk_management']['stop_loss']['type'] == 'atr' ? 'selected' : ''; ?>>ATR Bazlı</option>
                        <option value="support_resistance" <?php echo isset($settings['risk_management']['stop_loss']['type']) && $settings['risk_management']['stop_loss']['type'] == 'support_resistance' ? 'selected' : ''; ?>>Destek/Direnç</option>
                        <option value="indicator" <?php echo isset($settings['risk_management']['stop_loss']['type']) && $settings['risk_management']['stop_loss']['type'] == 'indicator' ? 'selected' : ''; ?>>İndikatör Bazlı</option>
                    </select>
                    <small class="form-text text-muted">Stop loss hesaplama yöntemi</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="trailing_stop" name="trailing_stop"
                           <?php echo isset($settings['risk_management']['stop_loss']['trailing_stop']) && $settings['risk_management']['stop_loss']['trailing_stop'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="trailing_stop">Trailing Stop Loss</label>
                    <small class="form-text text-muted">Kar artarken stop loss seviyesini yukarı çeker</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group" id="trailing_distance_group" style="<?php echo (!isset($settings['risk_management']['stop_loss']['trailing_stop']) || !$settings['risk_management']['stop_loss']['trailing_stop']) ? 'display:none;' : ''; ?>">
                    <label for="trailing_distance">Trailing Mesafesi</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="trailing_distance" id="trailing_distance" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['stop_loss']['trailing_distance']) ? $settings['risk_management']['stop_loss']['trailing_distance'] : 1.0; ?>" min="0.1" max="5">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">En yüksek fiyattan aşağı trailing mesafesi</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Take Profit Ayarları -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-bullseye"></i> Take Profit Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="default_take_profit">Varsayılan Take Profit</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="default_take_profit" id="default_take_profit" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['take_profit']['default_percentage']) ? $settings['risk_management']['take_profit']['default_percentage'] : 4.0; ?>" min="1" max="20">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Giriş fiyatından yukarı take profit yüzdesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="risk_reward_ratio">Risk/Ödül Oranı</label>
                    <input type="number" step="0.1" name="risk_reward_ratio" id="risk_reward_ratio" class="form-control" 
                           value="<?php echo isset($settings['risk_management']['take_profit']['risk_reward_ratio']) ? $settings['risk_management']['take_profit']['risk_reward_ratio'] : 2.0; ?>" min="1" max="5">
                    <small class="form-text text-muted">1:X risk/ödül oranı (2 = 1:2)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="partial_take_profit">Kısmi Kar Alma</label>
                    <div class="input-group">
                        <input type="number" name="partial_take_profit" id="partial_take_profit" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['take_profit']['partial_percentage']) ? $settings['risk_management']['take_profit']['partial_percentage'] : 50; ?>" min="10" max="90">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">İlk hedefte satılacak pozisyon yüzdesi</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="dynamic_take_profit" name="dynamic_take_profit"
                           <?php echo isset($settings['risk_management']['take_profit']['dynamic']) && $settings['risk_management']['take_profit']['dynamic'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="dynamic_take_profit">Dinamik Take Profit</label>
                    <small class="form-text text-muted">Volatiliteye göre take profit seviyesini ayarlar</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="scale_out" name="scale_out"
                           <?php echo isset($settings['risk_management']['take_profit']['scale_out']) && $settings['risk_management']['take_profit']['scale_out'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="scale_out">Kademeli Çıkış</label>
                    <small class="form-text text-muted">Farklı seviyelerde kademeli kar alma</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pozisyon Yönetimi -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-layer-group"></i> Pozisyon Yönetimi</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_open_positions">Maksimum Açık Pozisyon</label>
                    <input type="number" name="max_open_positions" id="max_open_positions" class="form-control" 
                           value="<?php echo isset($settings['risk_management']['position_management']['max_open_positions']) ? $settings['risk_management']['position_management']['max_open_positions'] : 5; ?>" min="1" max="20">
                    <small class="form-text text-muted">Aynı anda açık olabilecek maksimum pozisyon sayısı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_positions_per_coin">Coin Başına Maksimum Pozisyon</label>
                    <input type="number" name="max_positions_per_coin" id="max_positions_per_coin" class="form-control" 
                           value="<?php echo isset($settings['risk_management']['position_management']['max_positions_per_coin']) ? $settings['risk_management']['position_management']['max_positions_per_coin'] : 1; ?>" min="1" max="5">
                    <small class="form-text text-muted">Aynı coin için maksimum pozisyon sayısı</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="position_sizing_method">Pozisyon Büyüklüğü Yöntemi</label>
                    <select name="position_sizing_method" id="position_sizing_method" class="form-control">
                        <option value="fixed_percentage" <?php echo (!isset($settings['risk_management']['position_management']['sizing_method']) || $settings['risk_management']['position_management']['sizing_method'] == 'fixed_percentage') ? 'selected' : ''; ?>>Sabit Yüzde</option>
                        <option value="kelly_criterion" <?php echo isset($settings['risk_management']['position_management']['sizing_method']) && $settings['risk_management']['position_management']['sizing_method'] == 'kelly_criterion' ? 'selected' : ''; ?>>Kelly Kriteri</option>
                        <option value="risk_parity" <?php echo isset($settings['risk_management']['position_management']['sizing_method']) && $settings['risk_management']['position_management']['sizing_method'] == 'risk_parity' ? 'selected' : ''; ?>>Risk Paritesi</option>
                        <option value="volatility_adjusted" <?php echo isset($settings['risk_management']['position_management']['sizing_method']) && $settings['risk_management']['position_management']['sizing_method'] == 'volatility_adjusted' ? 'selected' : ''; ?>>Volatilite Ayarlı</option>
                    </select>
                    <small class="form-text text-muted">Pozisyon büyüklüğü hesaplama yöntemi</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="correlation_limit">Korelasyon Limiti</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="correlation_limit" id="correlation_limit" class="form-control" 
                               value="<?php echo isset($settings['risk_management']['position_management']['correlation_limit']) ? $settings['risk_management']['position_management']['correlation_limit'] : 0.7; ?>" min="0.1" max="1.0">
                        <div class="input-group-append">
                            <span class="input-group-text">ρ</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Yüksek korelasyonlu coinler için limit</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="diversification_check" name="diversification_check"
                           <?php echo isset($settings['risk_management']['position_management']['diversification_check']) && $settings['risk_management']['position_management']['diversification_check'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="diversification_check">Çeşitlendirme Kontrolü</label>
                    <small class="form-text text-muted">Portföy çeşitlendirmesini kontrol eder</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="sector_allocation" name="sector_allocation"
                           <?php echo isset($settings['risk_management']['position_management']['sector_allocation']) && $settings['risk_management']['position_management']['sector_allocation'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="sector_allocation">Sektör Dağılımı</label>
                    <small class="form-text text-muted">Sektör bazında pozisyon dağılımını kontrol eder</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Trailing stop checkbox kontrolü
    const trailingStopCheckbox = document.getElementById('trailing_stop');
    const trailingDistanceGroup = document.getElementById('trailing_distance_group');
    
    function toggleTrailingStopFields() {
        if (trailingStopCheckbox && trailingDistanceGroup) {
            const isEnabled = trailingStopCheckbox.checked;
            trailingDistanceGroup.style.display = isEnabled ? 'block' : 'none';
            
            const trailingDistanceInput = document.getElementById('trailing_distance');
            if (trailingDistanceInput) {
                trailingDistanceInput.disabled = !isEnabled;
                trailingDistanceInput.style.opacity = isEnabled ? '1' : '0.5';
            }
        }
    }
    
    if (trailingStopCheckbox) {
        trailingStopCheckbox.addEventListener('change', toggleTrailingStopFields);
        toggleTrailingStopFields(); // Initial state
    }
    
    // Risk enabled checkbox kontrolü - tüm risk ayarlarını etkinleştir/devre dışı bırak
    const riskEnabledCheckbox = document.getElementById('risk_enabled');
    const riskFields = document.querySelectorAll('.feature-card:not(:first-child) input, .feature-card:not(:first-child) select');
    
    function toggleRiskFields() {
        if (riskEnabledCheckbox) {
            const isEnabled = riskEnabledCheckbox.checked;
            riskFields.forEach(function(field) {
                field.disabled = !isEnabled;
                field.style.opacity = isEnabled ? '1' : '0.5';
                
                // Parent container styling
                const parentCard = field.closest('.feature-card');
                if (parentCard && !parentCard.querySelector('#risk_enabled')) {
                    parentCard.style.opacity = isEnabled ? '1' : '0.6';
                    parentCard.style.pointerEvents = isEnabled ? 'auto' : 'none';
                }
            });
        }
    }
    
    if (riskEnabledCheckbox) {
        riskEnabledCheckbox.addEventListener('change', toggleRiskFields);
        toggleRiskFields(); // Initial state
    }
    
    // Form validation
    const form = document.querySelector('.settings-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('input[required]');
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Number field validation
            const numberFields = form.querySelectorAll('input[type="number"]');
            numberFields.forEach(function(field) {
                const value = parseFloat(field.value);
                const min = parseFloat(field.getAttribute('min'));
                const max = parseFloat(field.getAttribute('max'));
                
                if (isNaN(value) || (min !== null && value < min) || (max !== null && value > max)) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Lütfen tüm alanları doğru şekilde doldurun!');
            }
        });
    }
    
    // Input formatters
    const percentageInputs = document.querySelectorAll('input[step="0.1"]');
    percentageInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(1);
            }
        });
    });
    
    // Real-time feedback
    const stopLossInput = document.getElementById('default_stop_loss');
    const takeProfitInput = document.getElementById('default_take_profit');
    const riskRewardInput = document.getElementById('risk_reward_ratio');
    
    function updateRiskRewardRatio() {
        if (stopLossInput && takeProfitInput && riskRewardInput) {
            const stopLoss = parseFloat(stopLossInput.value);
            const takeProfit = parseFloat(takeProfitInput.value);
            
            if (!isNaN(stopLoss) && !isNaN(takeProfit) && stopLoss > 0) {
                const ratio = takeProfit / stopLoss;
                riskRewardInput.value = ratio.toFixed(1);
            }
        }
    }
    
    if (stopLossInput && takeProfitInput) {
        stopLossInput.addEventListener('input', updateRiskRewardRatio);
        takeProfitInput.addEventListener('input', updateRiskRewardRatio);
    }
});
</script>