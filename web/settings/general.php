<?php
// General Settings Tab Content
?>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="exchange">Borsa</label>
            <select name="exchange" id="exchange" class="form-control">
                <option value="binance" <?php echo isset($settings['exchange']) && $settings['exchange'] == 'binance' ? 'selected' : ''; ?>>Binance</option>
                <option value="kucoin" <?php echo isset($settings['exchange']) && $settings['exchange'] == 'kucoin' ? 'selected' : ''; ?>>KuCoin</option>
                <option value="bitget" <?php echo isset($settings['exchange']) && $settings['exchange'] == 'bitget' ? 'selected' : ''; ?>>Bitget</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="base_currency">Baz Para Birimi</label>
            <select name="base_currency" id="base_currency" class="form-control">
                <option value="USDT" <?php echo isset($settings['base_currency']) && $settings['base_currency'] == 'USDT' ? 'selected' : ''; ?>>USDT</option>
                <option value="BTC" <?php echo isset($settings['base_currency']) && $settings['base_currency'] == 'BTC' ? 'selected' : ''; ?>>BTC</option>
                <option value="ETH" <?php echo isset($settings['base_currency']) && $settings['base_currency'] == 'ETH' ? 'selected' : ''; ?>>ETH</option>
                <option value="BNB" <?php echo isset($settings['base_currency']) && $settings['base_currency'] == 'BNB' ? 'selected' : ''; ?>>BNB</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="min_volume">Minimum Hacim</label>
            <input type="number" step="0.01" name="min_volume" id="min_volume" class="form-control" 
                   value="<?php echo isset($settings['min_volume']) ? $settings['min_volume'] : ''; ?>">
            <small class="form-text text-muted">Coin seçimi için minimum günlük işlem hacmi</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="max_coins">Maksimum Coin Sayısı</label>
            <input type="number" name="max_coins" id="max_coins" class="form-control" 
                   value="<?php echo isset($settings['max_coins']) ? $settings['max_coins'] : ''; ?>">
            <small class="form-text text-muted">Aynı anda takip edilecek maksimum coin sayısı</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="min_trade_amount">Minimum İşlem Tutarı</label>
            <div class="input-group">
                <input type="number" step="0.01" name="min_trade_amount" id="min_trade_amount" class="form-control" 
                       value="<?php echo isset($settings['min_trade_amount']) ? $settings['min_trade_amount'] : ''; ?>">
                <div class="input-group-append">
                    <span class="input-group-text">USDT</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="max_trade_amount">Maksimum İşlem Tutarı</label>
            <div class="input-group">
                <input type="number" step="0.01" name="max_trade_amount" id="max_trade_amount" class="form-control" 
                       value="<?php echo isset($settings['max_trade_amount']) ? $settings['max_trade_amount'] : ''; ?>">
                <div class="input-group-append">
                    <span class="input-group-text">USDT</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="position_size">Pozisyon Büyüklüğü</label>
            <div class="input-group">
                <input type="number" step="0.01" name="position_size" id="position_size" class="form-control" 
                       value="<?php echo isset($settings['position_size']) ? $settings['position_size'] : ''; ?>" min="0.01" max="1">
                <div class="input-group-append">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <small class="form-text text-muted">Bakiyenin yüzde kaçını kullanacağı (0.01-1)</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="api_delay">API Gecikme Süresi</label>
            <div class="input-group">
                <input type="number" step="0.1" name="api_delay" id="api_delay" class="form-control" 
                       value="<?php echo isset($settings['api_delay']) ? $settings['api_delay'] : ''; ?>">
                <div class="input-group-append">
                    <span class="input-group-text">saniye</span>
                </div>
            </div>
            <small class="form-text text-muted">API istekleri arasındaki bekleme süresi</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="scan_interval">Tarama Aralığı</label>
            <div class="input-group">
                <input type="number" name="scan_interval" id="scan_interval" class="form-control" 
                       value="<?php echo isset($settings['scan_interval']) ? $settings['scan_interval'] : ''; ?>">
                <div class="input-group-append">
                    <span class="input-group-text">saniye</span>
                </div>
            </div>
            <small class="form-text text-muted">Tüm coinleri tarama aralığı</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>TradingView Entegrasyonu</label>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="use_tradingview" name="use_tradingview"
                       <?php echo isset($settings['use_tradingview']) && $settings['use_tradingview'] ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="use_tradingview">TradingView API kullan</label>
            </div>
            <small class="form-text text-muted">CCXT yerine TradingView API kullanarak veri çek</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="tradingview_exchange">TradingView Borsa Öneki</label>
            <select name="tradingview_exchange" id="tradingview_exchange" class="form-control">
                <option value="BINANCE" <?php echo isset($settings['tradingview_exchange']) && $settings['tradingview_exchange'] == 'BINANCE' ? 'selected' : ''; ?>>BINANCE</option>
                <option value="KUCOIN" <?php echo isset($settings['tradingview_exchange']) && $settings['tradingview_exchange'] == 'KUCOIN' ? 'selected' : ''; ?>>KUCOIN</option>
                <option value="BITGET" <?php echo isset($settings['tradingview_exchange']) && $settings['tradingview_exchange'] == 'BITGET' ? 'selected' : ''; ?>>BITGET</option>
            </select>
            <small class="form-text text-muted">TradingView'da kullanılacak borsa öneki</small>
        </div>
    </div>
</div>

<!-- İşlem Modu ve Yönü Ayarları -->
<div class="row mt-3">
    <div class="col-12">
        <h5 class="font-weight-bold mb-3"><i class="fas fa-exchange-alt"></i> İşlem Ayarları</h5>
    </div>
    
    <div class="col-md-3">
        <div class="form-group">
            <label for="trade_mode">İşlem Modu</label>
            <select name="trade_mode" id="trade_mode" class="form-control">
                <option value="paper" <?php echo isset($settings['trade_mode']) && $settings['trade_mode'] == 'paper' ? 'selected' : ''; ?>>Paper Trading</option>
                <option value="live" <?php echo isset($settings['trade_mode']) && $settings['trade_mode'] == 'live' ? 'selected' : ''; ?>>Live Trading</option>
            </select>
            <small class="form-text text-muted">Paper: simülasyon, Live: gerçek para ile işlem</small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="form-group">
            <label for="market_type">Piyasa Türü</label>
            <select name="market_type" id="market_type" class="form-control">
                <option value="spot" <?php echo isset($settings['market_type']) && $settings['market_type'] == 'spot' ? 'selected' : ''; ?>>Spot</option>
                <option value="futures" <?php echo isset($settings['market_type']) && $settings['market_type'] == 'futures' ? 'selected' : ''; ?>>Futures</option>
            </select>
            <small class="form-text text-muted">İşlemlerin yapılacağı piyasa türü</small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="form-group">
            <label for="trade_direction">İşlem Yönü</label>
            <select name="trade_direction" id="trade_direction" class="form-control">
                <option value="both" <?php echo isset($settings['trade_direction']) && $settings['trade_direction'] == 'both' ? 'selected' : ''; ?>>Alım & Satım</option>
                <option value="long_only" <?php echo isset($settings['trade_direction']) && $settings['trade_direction'] == 'long_only' ? 'selected' : ''; ?>>Sadece Alım</option>
                <option value="short_only" <?php echo isset($settings['trade_direction']) && $settings['trade_direction'] == 'short_only' ? 'selected' : ''; ?>>Sadece Satım</option>
            </select>
            <small class="form-text text-muted">Botun hangi yönde işlem yapacağını belirler</small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="form-group">
            <label>Otomatik İşlem</label>
            <div class="custom-control custom-switch mt-2">
                <input type="checkbox" class="custom-control-input" id="auto_trade" name="auto_trade"
                       <?php echo isset($settings['auto_trade']) && $settings['auto_trade'] ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="auto_trade">Otomatik işlem yap</label>
            </div>
            <small class="form-text text-muted">Bot, sinyallere göre otomatik işlem açıp kapatır</small>
        </div>
    </div>
</div>

<!-- Kaldıraç Ayarları (Sadece Futures için) -->
<div class="row mt-3" id="leverage-settings" style="display: none;">
    <div class="col-12">
        <h5 class="font-weight-bold mb-3"><i class="fas fa-chart-area"></i> Kaldıraç Ayarları</h5>
    </div>
    
    <div class="col-md-6">
        <div class="form-group">
            <label for="leverage">Kaldıraç Oranı</label>
            <select name="leverage" id="leverage" class="form-control">
                <?php for($i = 1; $i <= 20; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo isset($settings['leverage']) && $settings['leverage'] == $i ? 'selected' : ''; ?>><?php echo $i; ?>x</option>
                <?php endfor; ?>
            </select>
            <small class="form-text text-muted">Futures işlemleri için kaldıraç oranı</small>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="form-group">
            <label for="leverage_mode">Kaldıraç Modu</label>
            <select name="leverage_mode" id="leverage_mode" class="form-control">
                <option value="isolated" <?php echo isset($settings['leverage_mode']) && $settings['leverage_mode'] == 'isolated' ? 'selected' : ''; ?>>Isolated</option>
                <option value="cross" <?php echo isset($settings['leverage_mode']) && $settings['leverage_mode'] == 'cross' ? 'selected' : ''; ?>>Cross</option>
            </select>
            <small class="form-text text-muted">Kaldıraç marjin modu</small>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Market type değiştiğinde kaldıraç ayarlarını göster/gizle
    function toggleLeverageSettings() {
        if ($('#market_type').val() === 'futures') {
            $('#leverage-settings').show();
        } else {
            $('#leverage-settings').hide();
        }
    }
    
    // Sayfa yüklendiğinde kontrol et
    toggleLeverageSettings();
    
    // Market type değiştiğinde kontrol et
    $('#market_type').change(function() {
        toggleLeverageSettings();
    });
});
</script>