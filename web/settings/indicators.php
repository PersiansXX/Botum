<?php
// İndikatörler Modülü
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">İndikatör Ayarları Hakkında</h6>
        <p class="mb-0">İndikatörler, fiyat verilerini analiz ederek alım-satım sinyalleri üretir. Etkinleştirdiğiniz indikatörler botun karar verme sürecinde kullanılacaktır. Her indikatör farklı piyasa koşullarında farklı performans gösterebilir.</p>
    </div>
</div>

<!-- Bollinger Bands -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['bollinger_bands']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-area"></i> Bollinger Bands</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="bb_enabled" name="bb_enabled"
                   <?php echo $settings['indicators']['bollinger_bands']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="bb_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Bollinger Bands, fiyatın volatilitesini ölçen ve aşırı alım/satım seviyelerini belirleyen teknik analiz aracıdır. Fiyat bantların dışına çıktığında güçlü sinyal verir.
        </p>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="bb_window">Hesaplama Penceresi</label>
                    <input type="number" name="bb_window" id="bb_window" class="form-control" 
                           value="<?php echo $settings['indicators']['bollinger_bands']['window']; ?>" min="5" max="100">
                    <small class="form-text text-muted">Bollinger Bands hesaplaması için kullanılacak periyot (varsayılan: 20)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="bb_num_std">Standart Sapma Katsayısı</label>
                    <input type="number" step="0.1" name="bb_num_std" id="bb_num_std" class="form-control" 
                           value="<?php echo $settings['indicators']['bollinger_bands']['num_std']; ?>" min="1" max="3">
                    <small class="form-text text-muted">Bantların genişliğini belirler (varsayılan: 2)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RSI -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['rsi']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-line"></i> RSI (Göreceli Güç İndeksi)</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="rsi_enabled" name="rsi_enabled"
                   <?php echo $settings['indicators']['rsi']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="rsi_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            RSI, fiyat hareketlerinin hızını ve değişimini ölçen momentum osilatörüdür. 0-100 arasında değer alır ve aşırı alım/satım durumlarını gösterir.
        </p>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="rsi_window">RSI Periyodu</label>
                    <input type="number" name="rsi_window" id="rsi_window" class="form-control" 
                           value="<?php echo $settings['indicators']['rsi']['window']; ?>" min="2" max="50">
                    <small class="form-text text-muted">RSI hesaplaması için kullanılacak periyot (varsayılan: 14)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MACD -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['macd']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-bar"></i> MACD (Hareketli Ortalama Yakınsama/Iraksama)</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="macd_enabled" name="macd_enabled"
                   <?php echo $settings['indicators']['macd']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="macd_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            MACD, trend yönünü ve momentum değişikliklerini tespit etmek için kullanılan popüler bir indikatördür. İki hareketli ortalama arasındaki farkı gösterir.
        </p>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="macd_fast">Hızlı Periyot</label>
                    <input type="number" name="macd_fast" id="macd_fast" class="form-control" 
                           value="<?php echo $settings['indicators']['macd']['fast_period']; ?>" min="5" max="50">
                    <small class="form-text text-muted">Hızlı EMA periyodu (varsayılan: 12)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="macd_slow">Yavaş Periyot</label>
                    <input type="number" name="macd_slow" id="macd_slow" class="form-control" 
                           value="<?php echo $settings['indicators']['macd']['slow_period']; ?>" min="10" max="100">
                    <small class="form-text text-muted">Yavaş EMA periyodu (varsayılan: 26)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="macd_signal">Sinyal Periyodu</label>
                    <input type="number" name="macd_signal" id="macd_signal" class="form-control" 
                           value="<?php echo $settings['indicators']['macd']['signal_period']; ?>" min="5" max="50">
                    <small class="form-text text-muted">Sinyal çizgisi periyodu (varsayılan: 9)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hareketli Ortalama -->
<div class="feature-card mb-4 <?php echo $settings['indicators']['moving_average']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-wave-square"></i> Hareketli Ortalamalar</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="ma_enabled" name="ma_enabled"
                   <?php echo $settings['indicators']['moving_average']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="ma_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Hareketli ortalamalar, fiyat trendlerini yumuşatarak genel yönü belirlemeye yardımcı olur. Kısa ve uzun vadeli ortalamalar karşılaştırılarak sinyal üretilir.
        </p>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="ma_short">Kısa Vadeli MA</label>
                    <input type="number" name="ma_short" id="ma_short" class="form-control" 
                           value="<?php echo $settings['indicators']['moving_average']['short_window']; ?>" min="5" max="100">
                    <small class="form-text text-muted">Kısa vadeli hareketli ortalama periyodu (varsayılan: 10)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="ma_long">Uzun Vadeli MA</label>
                    <input type="number" name="ma_long" id="ma_long" class="form-control" 
                           value="<?php echo $settings['indicators']['moving_average']['long_window']; ?>" min="10" max="200">
                    <small class="form-text text-muted">Uzun vadeli hareketli ortalama periyodu (varsayılan: 50)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Supertrend -->
<div class="feature-card mb-4 <?php echo isset($settings['indicators']['supertrend']['enabled']) && $settings['indicators']['supertrend']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-route"></i> Supertrend</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="supertrend_enabled" name="supertrend_enabled"
                   <?php echo isset($settings['indicators']['supertrend']['enabled']) && $settings['indicators']['supertrend']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="supertrend_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Supertrend, trend yönünü belirlemek için kullanılan güçlü bir indikatördür. Fiyatın üstünde veya altında görünerek net alım/satım sinyalleri verir.
        </p>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="supertrend_period">ATR Periyodu</label>
                    <input type="number" name="supertrend_period" id="supertrend_period" class="form-control" 
                           value="<?php echo isset($settings['indicators']['supertrend']['period']) ? $settings['indicators']['supertrend']['period'] : 10; ?>" min="5" max="50">
                    <small class="form-text text-muted">Average True Range hesaplama periyodu (varsayılan: 10)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="supertrend_multiplier">Çarpan</label>
                    <input type="number" step="0.1" name="supertrend_multiplier" id="supertrend_multiplier" class="form-control" 
                           value="<?php echo isset($settings['indicators']['supertrend']['multiplier']) ? $settings['indicators']['supertrend']['multiplier'] : 3.0; ?>" min="1" max="10">
                    <small class="form-text text-muted">ATR çarpanı (varsayılan: 3.0)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- VWAP -->
<div class="feature-card mb-4 <?php echo isset($settings['indicators']['vwap']['enabled']) && $settings['indicators']['vwap']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-balance-scale"></i> VWAP (Hacim Ağırlıklı Ortalama Fiyat)</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="vwap_enabled" name="vwap_enabled"
                   <?php echo isset($settings['indicators']['vwap']['enabled']) && $settings['indicators']['vwap']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="vwap_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            VWAP, hacim ağırlıklı ortalama fiyatı hesaplayarak kurumsal yatırımcıların ortalama giriş fiyatını gösterir. Güçlü destek/direnç seviyesi oluşturur.
        </p>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="vwap_period">VWAP Periyodu</label>
                    <input type="number" name="vwap_period" id="vwap_period" class="form-control" 
                           value="<?php echo isset($settings['indicators']['vwap']['period']) ? $settings['indicators']['vwap']['period'] : 14; ?>" min="5" max="100">
                    <small class="form-text text-muted">VWAP hesaplama periyodu (varsayılan: 14)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stochastic -->
<div class="feature-card mb-4 <?php echo isset($settings['indicators']['stochastic']['enabled']) && $settings['indicators']['stochastic']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-percent"></i> Stochastic Oscillator</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="stochastic_enabled" name="stochastic_enabled"
                   <?php echo isset($settings['indicators']['stochastic']['enabled']) && $settings['indicators']['stochastic']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="stochastic_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Stochastic Oscillator, mevcut kapanış fiyatının belirli bir periyottaki en yüksek ve en düşük fiyat aralığındaki konumunu gösterir. Aşırı alım/satım durumlarını belirler.
        </p>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="stochastic_k_period">%K Periyodu</label>
                    <input type="number" name="stochastic_k_period" id="stochastic_k_period" class="form-control" 
                           value="<?php echo isset($settings['indicators']['stochastic']['k_period']) ? $settings['indicators']['stochastic']['k_period'] : 14; ?>" min="5" max="50">
                    <small class="form-text text-muted">%K çizgisi periyodu (varsayılan: 14)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="stochastic_d_period">%D Periyodu</label>
                    <input type="number" name="stochastic_d_period" id="stochastic_d_period" class="form-control" 
                           value="<?php echo isset($settings['indicators']['stochastic']['d_period']) ? $settings['indicators']['stochastic']['d_period'] : 3; ?>" min="1" max="20">
                    <small class="form-text text-muted">%D çizgisi periyodu (varsayılan: 3)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="stochastic_slowing">Yavaşlatma</label>
                    <input type="number" name="stochastic_slowing" id="stochastic_slowing" class="form-control" 
                           value="<?php echo isset($settings['indicators']['stochastic']['slowing']) ? $settings['indicators']['stochastic']['slowing'] : 3; ?>" min="1" max="10">
                    <small class="form-text text-muted">%K çizgisi yavaşlatma periyodu (varsayılan: 3)</small>
                </div>
            </div>
        </div>
    </div>
</div>