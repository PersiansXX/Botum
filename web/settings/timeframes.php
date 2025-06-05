<?php
// Zaman Aralıkları Modülü
?>
<div class="info-box info mb-4">
    <i class="fas fa-info-circle"></i>
    <div>
        <h6 class="font-weight-bold mb-1">Çoklu Zaman Aralıkları</h6>
        <p class="mb-0">Botun analiz edeceği birden fazla zaman aralığını seçebilirsiniz. Bot, seçilen tüm zaman aralıklarında analiz yaparak en güvenilir sinyalleri üretecektir.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-clock"></i> Kullanılacak Zaman Aralıkları</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="font-weight-bold">Zaman Aralığı Seçenekleri</label>
                <small class="form-text text-muted mb-3">Bot tarafından analiz edilecek zaman aralıklarını seçin. Birden fazla seçim yapabilirsiniz.</small>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_1m" name="timeframes[]" value="1m"
                           <?php echo isset($settings['timeframes']) && in_array('1m', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_1m">1 Dakika</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_3m" name="timeframes[]" value="3m"
                           <?php echo isset($settings['timeframes']) && in_array('3m', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_3m">3 Dakika</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_5m" name="timeframes[]" value="5m"
                           <?php echo isset($settings['timeframes']) && in_array('5m', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_5m">5 Dakika</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_15m" name="timeframes[]" value="15m"
                           <?php echo isset($settings['timeframes']) && in_array('15m', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_15m">15 Dakika</label>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_30m" name="timeframes[]" value="30m"
                           <?php echo isset($settings['timeframes']) && in_array('30m', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_30m">30 Dakika</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_1h" name="timeframes[]" value="1h"
                           <?php echo isset($settings['timeframes']) && in_array('1h', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_1h">1 Saat</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_2h" name="timeframes[]" value="2h"
                           <?php echo isset($settings['timeframes']) && in_array('2h', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_2h">2 Saat</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_4h" name="timeframes[]" value="4h"
                           <?php echo isset($settings['timeframes']) && in_array('4h', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_4h">4 Saat</label>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_6h" name="timeframes[]" value="6h"
                           <?php echo isset($settings['timeframes']) && in_array('6h', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_6h">6 Saat</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_12h" name="timeframes[]" value="12h"
                           <?php echo isset($settings['timeframes']) && in_array('12h', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_12h">12 Saat</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_1d" name="timeframes[]" value="1d"
                           <?php echo isset($settings['timeframes']) && in_array('1d', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_1d">1 Gün</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="timeframe_1w" name="timeframes[]" value="1w"
                           <?php echo isset($settings['timeframes']) && in_array('1w', $settings['timeframes']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="timeframe_1w">1 Hafta</label>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="primary_timeframe">Ana Zaman Aralığı</label>
                    <select name="primary_timeframe" id="primary_timeframe" class="form-control">
                        <option value="1m" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '1m' ? 'selected' : ''; ?>>1 Dakika</option>
                        <option value="3m" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '3m' ? 'selected' : ''; ?>>3 Dakika</option>
                        <option value="5m" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '5m' ? 'selected' : ''; ?>>5 Dakika</option>
                        <option value="15m" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '15m' ? 'selected' : ''; ?>>15 Dakika</option>
                        <option value="30m" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '30m' ? 'selected' : ''; ?>>30 Dakika</option>
                        <option value="1h" <?php echo (!isset($settings['primary_timeframe']) || $settings['primary_timeframe'] == '1h') ? 'selected' : ''; ?>>1 Saat</option>
                        <option value="2h" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '2h' ? 'selected' : ''; ?>>2 Saat</option>
                        <option value="4h" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '4h' ? 'selected' : ''; ?>>4 Saat</option>
                        <option value="6h" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '6h' ? 'selected' : ''; ?>>6 Saat</option>
                        <option value="12h" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '12h' ? 'selected' : ''; ?>>12 Saat</option>
                        <option value="1d" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '1d' ? 'selected' : ''; ?>>1 Gün</option>
                        <option value="1w" <?php echo isset($settings['primary_timeframe']) && $settings['primary_timeframe'] == '1w' ? 'selected' : ''; ?>>1 Hafta</option>
                    </select>
                    <small class="form-text text-muted">En yüksek önceliğe sahip zaman aralığı</small>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="timeframe_consensus">Zaman Aralığı Uzlaşma Yöntemi</label>
                    <select name="timeframe_consensus" id="timeframe_consensus" class="form-control">
                        <option value="any" <?php echo isset($settings['timeframe_consensus']) && $settings['timeframe_consensus'] == 'any' ? 'selected' : ''; ?>>Herhangi bir zaman aralığı</option>
                        <option value="majority" <?php echo (!isset($settings['timeframe_consensus']) || $settings['timeframe_consensus'] == 'majority') ? 'selected' : ''; ?>>Çoğunluk (>50%)</option>
                        <option value="strong" <?php echo isset($settings['timeframe_consensus']) && $settings['timeframe_consensus'] == 'strong' ? 'selected' : ''; ?>>Güçlü çoğunluk (>75%)</option>
                        <option value="all" <?php echo isset($settings['timeframe_consensus']) && $settings['timeframe_consensus'] == 'all' ? 'selected' : ''; ?>>Tüm zaman aralıkları</option>
                    </select>
                    <small class="form-text text-muted">İşlem sinyali için gereken zaman aralığı uzlaşma seviyesi</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Çoklu Timeframe Ayarları</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="timeframe_weight_short">Kısa Vadeli Ağırlık (1m-15m)</label>
                    <input type="number" name="timeframe_weight_short" id="timeframe_weight_short" class="form-control" 
                           value="<?php echo isset($settings['timeframe_weight_short']) ? $settings['timeframe_weight_short'] : 30; ?>" min="0" max="100">
                    <small class="form-text text-muted">Kısa vadeli zaman aralıklarının karar vermedeki ağırlığı (%)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="timeframe_weight_medium">Orta Vadeli Ağırlık (30m-4h)</label>
                    <input type="number" name="timeframe_weight_medium" id="timeframe_weight_medium" class="form-control" 
                           value="<?php echo isset($settings['timeframe_weight_medium']) ? $settings['timeframe_weight_medium'] : 40; ?>" min="0" max="100">
                    <small class="form-text text-muted">Orta vadeli zaman aralıklarının karar vermedeki ağırlığı (%)</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="timeframe_weight_long">Uzun Vadeli Ağırlık (6h+)</label>
                    <input type="number" name="timeframe_weight_long" id="timeframe_weight_long" class="form-control" 
                           value="<?php echo isset($settings['timeframe_weight_long']) ? $settings['timeframe_weight_long'] : 30; ?>" min="0" max="100">
                    <small class="form-text text-muted">Uzun vadeli zaman aralıklarının karar vermedeki ağırlığı (%)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-info mt-4">
                    <small><i class="fas fa-info-circle"></i> Toplam ağırlık 100% olmalıdır</small>
                </div>
            </div>
        </div>
    </div>
</div>