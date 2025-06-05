<?php
// Strategies Tab Content
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Strateji Ayarları</h6>
        <p class="mb-0">Botun kullanacağı alım-satım stratejilerini bu bölümden etkinleştirebilir ve özelleştirebilirsiniz.</p>
    </div>
</div>

<!-- Kısa Vadeli Strateji -->
<div class="feature-card mb-4 <?php echo isset($settings['strategies']['short_term']['enabled']) && $settings['strategies']['short_term']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-bolt"></i> Kısa Vadeli Strateji</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="short_term_enabled" name="short_term_enabled"
                   <?php echo isset($settings['strategies']['short_term']['enabled']) && $settings['strategies']['short_term']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="short_term_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="status-indicator status-info mr-3">
                <i class="fas fa-bolt"></i>
            </div>
            <div>
                <h6 class="mb-1">Hızlı İşlemler</h6>
                <small class="text-muted">Kısa vadeli fırsatları değerlendiren strateji</small>
            </div>
        </div>
        <p class="feature-description">
            Bu strateji, kısa vadeli fiyat hareketlerini takip ederek hızlı alım-satım işlemleri gerçekleştirir. 
            Yüksek volatilite dönemlerinde daha etkili olabilir ancak işlem sayısı da artar.
        </p>
        <div class="mt-3">
            <span class="badge badge-info">Tavsiye: Deneyimli kullanıcılar için</span>
        </div>
    </div>
</div>

<!-- Trend Takip Stratejisi -->
<div class="feature-card mb-4 <?php echo isset($settings['strategies']['trend_following']['enabled']) && $settings['strategies']['trend_following']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-line"></i> Trend Takip Stratejisi</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="trend_following_enabled" name="trend_following_enabled"
                   <?php echo isset($settings['strategies']['trend_following']['enabled']) && $settings['strategies']['trend_following']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="trend_following_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="status-indicator status-success mr-3">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h6 class="mb-1">Trend Takibi</h6>
                <small class="text-muted">Güçlü trendleri takip eden strateji</small>
            </div>
        </div>
        <p class="feature-description">
            Bu strateji, belirgin trendleri tespit ederek trend yönünde pozisyon alır. 
            Daha güvenli bir yaklaşım sunar ve uzun vadeli trendlerden faydalanır.
        </p>
        <div class="mt-3">
            <span class="badge badge-success">Tavsiye: Yeni başlayanlar için uygun</span>
        </div>
    </div>
</div>

<!-- Kırılma Stratejisi -->
<div class="feature-card mb-4 <?php echo isset($settings['strategies']['breakout']['enabled']) && $settings['strategies']['breakout']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-expand-arrows-alt"></i> Kırılma Stratejisi</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="breakout_enabled" name="breakout_enabled"
                   <?php echo isset($settings['strategies']['breakout']['enabled']) && $settings['strategies']['breakout']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="breakout_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="status-indicator status-warning mr-3">
                <i class="fas fa-expand-arrows-alt"></i>
            </div>
            <div>
                <h6 class="mb-1">Seviye Kırılmaları</h6>
                <small class="text-muted">Destek/direnç kırılmalarını değerlendiren strateji</small>
            </div>
        </div>
        <p class="feature-description">
            Bu strateji, önemli destek ve direnç seviyelerinin kırılmasını bekler. 
            Kırılma gerçekleştiğinde güçlü momentum yakalayabilir.
        </p>
        <div class="mt-3">
            <span class="badge badge-warning">Tavsiye: Orta seviye kullanıcılar için</span>
        </div>
    </div>
</div>

<!-- Volatilite Kırılma Stratejisi -->
<div class="feature-card mb-4 <?php echo isset($settings['strategies']['volatility_breakout']['enabled']) && $settings['strategies']['volatility_breakout']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-chart-pie"></i> Volatilite Kırılma Stratejisi</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="volatility_breakout_enabled" name="volatility_breakout_enabled"
                   <?php echo isset($settings['strategies']['volatility_breakout']['enabled']) && $settings['strategies']['volatility_breakout']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="volatility_breakout_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="status-indicator status-danger mr-3">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div>
                <h6 class="mb-1">Volatilite Patlamaları</h6>
                <small class="text-muted">Volatilite artışlarını değerlendiren strateji</small>
            </div>
        </div>
        <p class="feature-description">
            Bu strateji, düşük volatilite dönemlerinden sonra gelen yüksek volatilite patlamalarını değerlendirir. 
            Bollinger Bands sıkışması gibi durumları takip eder.
        </p>
        <div class="mt-3">
            <span class="badge badge-danger">Tavsiye: İleri seviye kullanıcılar için</span>
        </div>
    </div>
</div>

<div class="info-box warning mt-3">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Dikkat!</h6>
        <p class="mb-0">Her strateji farklı piyasa koşullarında farklı performans gösterir. Mevcut piyasa koşullarına göre en uygun stratejiyi seçmek veya birden fazla stratejiyi birlikte kullanmak daha iyi sonuçlar verebilir. Gerçek parayla işlem yapmadan önce stratejilerinizi backtesting ile test etmeniz önerilir.</p>
    </div>
</div>