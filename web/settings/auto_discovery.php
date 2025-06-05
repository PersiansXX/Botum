<?php
// Otomatik Keşif Modülü
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Otomatik Coin Keşfetme Ayarları</h6>
        <p class="mb-0">Otomatik coin keşfetme özelliği, belirli kriterlere göre yeni coinleri tarar ve keşfeder. Bu özellik, botun sürekli olarak yeni fırsatları değerlendirmesini sağlar.</p>
    </div>
</div>

<div class="feature-card mb-4 <?php echo isset($settings['auto_discovery']['enabled']) && $settings['auto_discovery']['enabled'] ? 'enabled' : 'disabled'; ?>">
    <div class="feature-card-header">
        <h5><i class="fas fa-search-dollar"></i> Otomatik Keşif</h5>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="auto_discovery_enabled" name="auto_discovery_enabled"
                   <?php echo isset($settings['auto_discovery']['enabled']) && $settings['auto_discovery']['enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="auto_discovery_enabled"></label>
        </div>
    </div>
    <div class="feature-card-body">
        <p class="feature-description">
            Bot, belirlenen kriterlere göre piyasadaki coinleri tarar ve potansiyel fırsatları otomatik olarak keşfeder. Bu özellik, trending coinleri ve ani yükselişleri yakalayabilir.
        </p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="discovery_interval">Keşif Aralığı</label>
                    <div class="input-group">
                        <input type="number" name="discovery_interval" id="discovery_interval" class="form-control" 
                               value="<?php echo isset($settings['auto_discovery']['discovery_interval']) ? $settings['auto_discovery']['discovery_interval'] : 300; ?>" min="60">
                        <div class="input-group-append">
                            <span class="input-group-text">saniye</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Coin keşfetme taramalarının aralığı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="min_volume_for_discovery">Minimum Hacim (Keşif)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="min_volume_for_discovery" id="min_volume_for_discovery" class="form-control" 
                               value="<?php echo isset($settings['auto_discovery']['min_volume_for_discovery']) ? $settings['auto_discovery']['min_volume_for_discovery'] : 1000000; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">USDT</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Keşfedilecek coinler için minimum 24s hacim</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="min_price_change">Minimum Fiyat Değişimi</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="min_price_change" id="min_price_change" class="form-control" 
                               value="<?php echo isset($settings['auto_discovery']['min_price_change']) ? $settings['auto_discovery']['min_price_change'] : 5.0; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">24 saatte minimum fiyat değişimi (yükseliş için)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="min_volume_change">Minimum Hacim Değişimi</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="min_volume_change" id="min_volume_change" class="form-control" 
                               value="<?php echo isset($settings['auto_discovery']['min_volume_change']) ? $settings['auto_discovery']['min_volume_change'] : 50.0; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">24 saatte minimum hacim artışı</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_coins_to_discover">Maksimum Keşif Sayısı</label>
                    <input type="number" name="max_coins_to_discover" id="max_coins_to_discover" class="form-control" 
                           value="<?php echo isset($settings['auto_discovery']['max_coins_to_discover']) ? $settings['auto_discovery']['max_coins_to_discover'] : 5; ?>" min="1" max="20">
                    <small class="form-text text-muted">Her taramada keşfedilecek maksimum coin sayısı</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Otomatik İzleme Listesine Ekle</label>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input" id="auto_add_to_watchlist" name="auto_add_to_watchlist"
                               <?php echo isset($settings['auto_discovery']['auto_add_to_watchlist']) && $settings['auto_discovery']['auto_add_to_watchlist'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="auto_add_to_watchlist">Keşfedilen coinleri otomatik ekle</label>
                    </div>
                    <small class="form-text text-muted">Keşfedilen coinler otomatik olarak izleme listesine eklenir</small>
                </div>
            </div>
        </div>
    </div>
</div>