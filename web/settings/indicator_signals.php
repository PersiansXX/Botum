<?php
// İndikatör Sinyalleri Modülü
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">İndikatör Sinyal Ayarları</h6>
        <p class="mb-0">Bu bölümde her bir indikatörün sinyal üretme koşullarını ve karar verme sisteminde kullanılacak ağırlıklarını özelleştirebilirsiniz. Bu sayede stratejinize ve piyasa koşullarına en uygun sinyal sistemini oluşturabilirsiniz.</p>
    </div>
</div>

<!-- Genel Sinyal Ayarları -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-sliders-h"></i> Sinyal Sistemi Genel Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="signal_consensus_method">Sinyal Uzlaşma Yöntemi</label>
                    <select name="signal_consensus_method" id="signal_consensus_method" class="form-control">
                        <option value="weighted" <?php echo (!isset($settings['signal_consensus_method']) || $settings['signal_consensus_method'] == 'weighted') ? 'selected' : ''; ?>>Ağırlıklı Ortalama</option>
                        <option value="majority" <?php echo isset($settings['signal_consensus_method']) && $settings['signal_consensus_method'] == 'majority' ? 'selected' : ''; ?>>Çoğunluk Oyu</option>
                        <option value="unanimous" <?php echo isset($settings['signal_consensus_method']) && $settings['signal_consensus_method'] == 'unanimous' ? 'selected' : ''; ?>>Oybirliği</option>
                        <option value="threshold" <?php echo isset($settings['signal_consensus_method']) && $settings['signal_consensus_method'] == 'threshold' ? 'selected' : ''; ?>>Eşik Değeri</option>
                    </select>
                    <small class="form-text text-muted">İndikatör sinyallerinin nasıl birleştirileceğini belirler</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="signal_consensus_threshold">Sinyal Eşik Değeri: <span id="signal_threshold_value"><?php echo isset($settings['signal_consensus_threshold']) ? $settings['signal_consensus_threshold'] : 60; ?>%</span></label>
                    <input type="range" name="signal_consensus_threshold" id="signal_consensus_threshold" class="form-control-range" 
                           value="<?php echo isset($settings['signal_consensus_threshold']) ? $settings['signal_consensus_threshold'] : 60; ?>" min="50" max="100" step="5">
                    <small class="form-text text-muted">İşlem sinyali için gereken minimum ağırlık yüzdesi</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="signal_confirmation_count">Sinyal Doğrulama Sayısı</label>
                    <input type="number" name="signal_confirmation_count" id="signal_confirmation_count" class="form-control" 
                           value="<?php echo isset($settings['signal_confirmation_count']) ? $settings['signal_confirmation_count'] : 2; ?>" min="1" max="5">
                    <small class="form-text text-muted">İşlem yapmadan önce beklenecek ardışık sinyal sayısı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="signal_conflicting_action">Çelişkili Sinyal Durumu</label>
                    <select name="signal_conflicting_action" id="signal_conflicting_action" class="form-control">
                        <option value="wait" <?php echo (!isset($settings['signal_conflicting_action']) || $settings['signal_conflicting_action'] == 'wait') ? 'selected' : ''; ?>>Bekle</option>
                        <option value="strongest" <?php echo isset($settings['signal_conflicting_action']) && $settings['signal_conflicting_action'] == 'strongest' ? 'selected' : ''; ?>>En Güçlü Sinyali Takip Et</option>
                        <option value="close_position" <?php echo isset($settings['signal_conflicting_action']) && $settings['signal_conflicting_action'] == 'close_position' ? 'selected' : ''; ?>>Pozisyonu Kapat</option>
                    </select>
                    <small class="form-text text-muted">Çelişkili sinyaller alındığında yapılacak işlem</small>
                </div>
            </div>
        </div>
        <div class="alert alert-info mt-2">
            <small><i class="fas fa-info-circle"></i> Sinyal sistemi, tüm etkin indikatörlerden gelen sinyalleri birleştirerek nihai karar verir.</small>
        </div>
    </div>
</div>

<!-- İndikatör Sinyal Ağırlıkları -->
<div class="feature-card mb-4 enabled">
    <div class="feature-card-header">
        <h5><i class="fas fa-balance-scale"></i> İndikatör Ağırlıkları</h5>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Her indikatörün karar verme sürecindeki etkisini belirleyin. Toplam ağırlık 100% olmalıdır.
        </p>
        
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="rsi_weight">RSI Ağırlığı</label>
                    <input type="number" name="rsi_weight" id="rsi_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['rsi']) ? $settings['indicator_weights']['rsi'] : 20; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="macd_weight">MACD Ağırlığı</label>
                    <input type="number" name="macd_weight" id="macd_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['macd']) ? $settings['indicator_weights']['macd'] : 25; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="bollinger_weight">Bollinger Bands Ağırlığı</label>
                    <input type="number" name="bollinger_weight" id="bollinger_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['bollinger_bands']) ? $settings['indicator_weights']['bollinger_bands'] : 20; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="ma_weight">Hareketli Ortalama Ağırlığı</label>
                    <input type="number" name="ma_weight" id="ma_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['moving_average']) ? $settings['indicator_weights']['moving_average'] : 15; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="supertrend_weight">Supertrend Ağırlığı</label>
                    <input type="number" name="supertrend_weight" id="supertrend_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['supertrend']) ? $settings['indicator_weights']['supertrend'] : 10; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="stochastic_weight">Stochastic Ağırlığı</label>
                    <input type="number" name="stochastic_weight" id="stochastic_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['stochastic']) ? $settings['indicator_weights']['stochastic'] : 5; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="adx_weight">ADX Ağırlığı</label>
                    <input type="number" name="adx_weight" id="adx_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['adx']) ? $settings['indicator_weights']['adx'] : 5; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="other_weight">Diğer İndikatörler</label>
                    <input type="number" name="other_weight" id="other_weight" class="form-control weight-input" 
                           value="<?php echo isset($settings['indicator_weights']['other']) ? $settings['indicator_weights']['other'] : 0; ?>" min="0" max="100">
                    <small class="form-text text-muted">%</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="progress mb-2">
                    <div id="weight-progress" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">100%</div>
                </div>
                <small class="text-muted">Toplam Ağırlık: <span id="total-weight">100</span>%</small>
            </div>
        </div>
        
        <div class="row mt-2">
            <div class="col-md-12">
                <div class="alert alert-warning" id="weight-warning" style="display: none;">
                    <small><i class="fas fa-exclamation-triangle"></i> Toplam ağırlık 100% olmalıdır!</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RSI Sinyalleri -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['rsi']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-line"></i> RSI Sinyal Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="rsi_oversold">Aşırı Satım Seviyesi: <span id="rsi_oversold_value"><?php echo isset($settings['indicator_signals']['rsi']['oversold']) ? $settings['indicator_signals']['rsi']['oversold'] : 30; ?></span></label>
                    <input type="range" name="rsi_oversold" id="rsi_oversold" class="form-control-range" 
                           value="<?php echo isset($settings['indicator_signals']['rsi']['oversold']) ? $settings['indicator_signals']['rsi']['oversold'] : 30; ?>" min="10" max="40" step="1">
                    <small class="form-text text-muted">RSI bu değerin altında olduğunda alım sinyali</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="rsi_overbought">Aşırı Alım Seviyesi: <span id="rsi_overbought_value"><?php echo isset($settings['indicator_signals']['rsi']['overbought']) ? $settings['indicator_signals']['rsi']['overbought'] : 70; ?></span></label>
                    <input type="range" name="rsi_overbought" id="rsi_overbought" class="form-control-range" 
                           value="<?php echo isset($settings['indicator_signals']['rsi']['overbought']) ? $settings['indicator_signals']['rsi']['overbought'] : 70; ?>" min="60" max="90" step="1">
                    <small class="form-text text-muted">RSI bu değerin üstünde olduğunda satım sinyali</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="rsi_center_line_cross" name="rsi_center_line_cross"
                           <?php echo isset($settings['indicator_signals']['rsi']['center_line_cross']) && $settings['indicator_signals']['rsi']['center_line_cross'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="rsi_center_line_cross">50 Çizgisi Geçişi</label>
                    <small class="form-text text-muted">RSI 50 seviyesini geçtiğinde sinyal üret</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="rsi_divergence" name="rsi_divergence"
                           <?php echo isset($settings['indicator_signals']['rsi']['divergence']) && $settings['indicator_signals']['rsi']['divergence'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="rsi_divergence">Divergence Sinyali</label>
                    <small class="form-text text-muted">RSI ve fiyat arasındaki farklılığı tespit et</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bollinger Bands Sinyalleri -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['bollinger_bands']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-area"></i> Bollinger Bands Sinyal Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="bb_squeeze_threshold">Squeeze Eşik Değeri: <span id="bb_squeeze_threshold_value"><?php echo isset($settings['indicator_signals']['bollinger_bands']['squeeze_threshold']) ? $settings['indicator_signals']['bollinger_bands']['squeeze_threshold'] : 0.1; ?></span></label>
                    <input type="range" name="bb_squeeze_threshold" id="bb_squeeze_threshold" class="form-control-range" 
                           value="<?php echo isset($settings['indicator_signals']['bollinger_bands']['squeeze_threshold']) ? $settings['indicator_signals']['bollinger_bands']['squeeze_threshold'] : 0.1; ?>" min="0.05" max="0.5" step="0.01">
                    <small class="form-text text-muted">Bantlar bu kadar yaklaştığında volatilite patlaması beklenir</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="bb_breakout_confirmation_candles">Kırılma Doğrulama Mum Sayısı</label>
                    <input type="number" name="bb_breakout_confirmation_candles" id="bb_breakout_confirmation_candles" class="form-control" 
                           value="<?php echo isset($settings['indicator_signals']['bollinger_bands']['breakout_confirmation_candles']) ? $settings['indicator_signals']['bollinger_bands']['breakout_confirmation_candles'] : 2; ?>" min="1" max="5">
                    <small class="form-text text-muted">Kırılma için gereken ardışık mum sayısı</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="bb_percentage_b" name="bb_percentage_b"
                           <?php echo isset($settings['indicator_signals']['bollinger_bands']['use_percentage_b']) && $settings['indicator_signals']['bollinger_bands']['use_percentage_b'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="bb_percentage_b">%B Kullan</label>
                    <small class="form-text text-muted">Bollinger %B değerini sinyal üretmede kullan</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="bb_mean_reversion" name="bb_mean_reversion"
                           <?php echo isset($settings['indicator_signals']['bollinger_bands']['mean_reversion']) && $settings['indicator_signals']['bollinger_bands']['mean_reversion'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="bb_mean_reversion">Ortalamaya Dönüş</label>
                    <small class="form-text text-muted">Bantlardan geri dönüş sinyalleri kullan</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MACD Sinyalleri -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['macd']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-bar"></i> MACD Sinyal Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="macd_signal_strength">Minimum Sinyal Gücü: <span id="macd_signal_strength_value"><?php echo isset($settings['indicator_signals']['macd']['signal_strength']) ? $settings['indicator_signals']['macd']['signal_strength'] : 0.001; ?></span></label>
                    <input type="range" name="macd_signal_strength" id="macd_signal_strength" class="form-control-range" 
                           value="<?php echo isset($settings['indicator_signals']['macd']['signal_strength']) ? $settings['indicator_signals']['macd']['signal_strength'] : 0.001; ?>" min="0.0001" max="0.01" step="0.0001">
                    <small class="form-text text-muted">MACD sinyal geçişi için minimum değer</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="macd_trigger_type">Tetikleme Türü</label>
                    <select name="macd_trigger_type" id="macd_trigger_type" class="form-control">
                        <option value="signal_line" <?php echo (!isset($settings['indicator_signals']['macd']['trigger_type']) || $settings['indicator_signals']['macd']['trigger_type'] == 'signal_line') ? 'selected' : ''; ?>>Sinyal Çizgisi Geçişi</option>
                        <option value="zero_line" <?php echo isset($settings['indicator_signals']['macd']['trigger_type']) && $settings['indicator_signals']['macd']['trigger_type'] == 'zero_line' ? 'selected' : ''; ?>>Sıfır Çizgisi Geçişi</option>
                        <option value="both" <?php echo isset($settings['indicator_signals']['macd']['trigger_type']) && $settings['indicator_signals']['macd']['trigger_type'] == 'both' ? 'selected' : ''; ?>>Her İkisi</option>
                    </select>
                    <small class="form-text text-muted">MACD sinyal türü</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="macd_zero_line_cross" name="macd_zero_line_cross"
                           <?php echo isset($settings['indicator_signals']['macd']['zero_line_cross']) && $settings['indicator_signals']['macd']['zero_line_cross'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="macd_zero_line_cross">Sıfır Çizgisi Geçişi</label>
                    <small class="form-text text-muted">MACD sıfır çizgisini geçtiğinde sinyal üret</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="macd_histogram_divergence" name="macd_histogram_divergence"
                           <?php echo isset($settings['indicator_signals']['macd']['histogram_divergence']) && $settings['indicator_signals']['macd']['histogram_divergence'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="macd_histogram_divergence">Histogram Divergence</label>
                    <small class="form-text text-muted">MACD histogram divergence sinyali kullan</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Supertrend Sinyalleri -->
<div class="feature-card mb-4 <?php echo isset($settings['indicators']['supertrend']['enabled']) && $settings['indicators']['supertrend']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-route"></i> Supertrend Sinyal Ayarları</h5>
    </div>
    <div class="feature-card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="supertrend_confirmation_candles">Doğrulama Mum Sayısı</label>
                    <input type="number" name="supertrend_confirmation_candles" id="supertrend_confirmation_candles" class="form-control" 
                           value="<?php echo isset($settings['indicator_signals']['supertrend']['confirmation_candles']) ? $settings['indicator_signals']['supertrend']['confirmation_candles'] : 1; ?>" min="1" max="5">
                    <small class="form-text text-muted">Supertrend sinyal doğrulaması için gereken mum sayısı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="supertrend_adx_threshold">ADX Eşik Değeri: <span id="supertrend_adx_threshold_value"><?php echo isset($settings['indicator_signals']['supertrend']['adx_threshold']) ? $settings['indicator_signals']['supertrend']['adx_threshold'] : 25; ?></span></label>
                    <input type="range" name="supertrend_adx_threshold" id="supertrend_adx_threshold" class="form-control-range" 
                           value="<?php echo isset($settings['indicator_signals']['supertrend']['adx_threshold']) ? $settings['indicator_signals']['supertrend']['adx_threshold'] : 25; ?>" min="15" max="40" step="1"
                           <?php echo !(isset($settings['indicator_signals']['supertrend']['filter_adx']) && $settings['indicator_signals']['supertrend']['filter_adx']) ? 'disabled' : ''; ?>>
                    <small class="form-text text-muted">ADX filtresi için minimum trend gücü</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="supertrend_filter_adx" name="supertrend_filter_adx"
                           <?php echo isset($settings['indicator_signals']['supertrend']['filter_adx']) && $settings['indicator_signals']['supertrend']['filter_adx'] ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="supertrend_filter_adx">ADX Filtresi Kullan</label>
                    <small class="form-text text-muted">Güçlü trendlerde Supertrend sinyallerini filtrele</small>
                </div>
            </div>
        </div>
    </div>
</div>