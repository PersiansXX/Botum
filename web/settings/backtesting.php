<?php
// Backtesting Tab Content
?>
<div class="card mb-3">
    <div class="card-header">
        <div class="custom-control custom-switch float-right">
            <input type="checkbox" class="custom-control-input" id="enable_visualization" name="enable_visualization"
                   <?php echo isset($settings['backtesting']['enable_visualization']) && $settings['backtesting']['enable_visualization'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="enable_visualization">Görselleştirmeyi Etkinleştir</label>
        </div>
        <h5 class="mb-0">Backtesting (Geriye Dönük Test)</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="default_start_date">Varsayılan Başlangıç Tarihi</label>
                    <input type="date" name="default_start_date" id="default_start_date" class="form-control" 
                           value="<?php echo isset($settings['backtesting']['default_start_date']) ? $settings['backtesting']['default_start_date'] : date('Y-m-d', strtotime('-30 days')); ?>">
                    <small class="form-text text-muted">Backtesting için varsayılan başlangıç tarihi</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="default_end_date">Varsayılan Bitiş Tarihi</label>
                    <input type="date" name="default_end_date" id="default_end_date" class="form-control" 
                           value="<?php echo isset($settings['backtesting']['default_end_date']) ? $settings['backtesting']['default_end_date'] : date('Y-m-d'); ?>">
                    <small class="form-text text-muted">Backtesting için varsayılan bitiş tarihi</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="initial_capital">Başlangıç Sermayesi</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="initial_capital" id="initial_capital" class="form-control" 
                               value="<?php echo isset($settings['backtesting']['initial_capital']) ? $settings['backtesting']['initial_capital'] : '1000'; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">USDT</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Backtesting için kullanılacak başlangıç sermayesi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="trading_fee">İşlem Ücreti</label>
                    <div class="input-group">
                        <input type="number" step="0.001" name="trading_fee" id="trading_fee" class="form-control" 
                               value="<?php echo isset($settings['backtesting']['trading_fee']) ? $settings['backtesting']['trading_fee'] : '0.001'; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Her işlem için uygulanacak ücreti (0.1% = 0.001)</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="slippage">Kayma (Slippage)</label>
                    <div class="input-group">
                        <input type="number" step="0.001" name="slippage" id="slippage" class="form-control" 
                               value="<?php echo isset($settings['backtesting']['slippage']) ? $settings['backtesting']['slippage'] : '0.001'; ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Beklenen fiyat ile gerçekleşen fiyat arasındaki fark</small>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Backtesting Hakkında:</strong> Backtesting, stratejilerinizi geçmiş veriler üzerinde test etmenizi sağlar. 
            Bu sayede bir stratejiyi gerçek parayla kullanmadan önce performansını değerlendirebilirsiniz. 
            Ancak geçmiş performans gelecekteki performansı garanti etmez.
        </div>
        
        <div class="mt-4">
            <h6 class="font-weight-bold">Backtesting Özellikleri:</h6>
            <ul class="list-unstyled">
                <li><i class="fas fa-check text-success"></i> Çoklu strateji testi</li>
                <li><i class="fas fa-check text-success"></i> Risk metriklerinin analizi</li>
                <li><i class="fas fa-check text-success"></i> Kar/zarar grafiklerinin oluşturulması</li>
                <li><i class="fas fa-check text-success"></i> İşlem geçmişinin detaylı incelenmesi</li>
                <li><i class="fas fa-check text-success"></i> Performans karşılaştırmaları</li>
            </ul>
        </div>
    </div>
</div>