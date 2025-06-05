$(document).ready(function() {
    // Bot durumunu kontrol et
    checkBotStatus();
    
    // Yeni toggle bot butonuna tıklandığında
    $(document).on('click', '.toggle-bot', function() {
        var action = $(this).data('action');
        if (action === 'start') {
            startBot();
        } else if (action === 'stop') {
            stopBot();
        }
    });
    
    // Eski start-bot ve stop-bot butonlarını da destekle
    $(document).on('click', '.start-bot', function() {
        startBot();
    });
    
    $(document).on('click', '.stop-bot', function() {
        stopBot();
    });
    
    // Restart bot butonuna tıklandığında
    $(document).on('click', '.restart-bot', function() {
        restartBot();
    });
    
    // Log yenileme butonuna tıklandığında
    $('.refresh-logs').click(function() {
        refreshLogs();
    });
    
    // Strateji değişikliklerini izle
    $('.strategy-toggle').change(function() {
        var strategy = $(this).data('strategy');
        var enabled = $(this).prop('checked');
        
        $.ajax({
            url: 'api/update_strategy.php',
            type: 'POST',
            data: {
                strategy: strategy,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Strateji güncellendi: ' + response.message);
                } else {
                    showAlert('danger', 'Hata: ' + response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Sunucu hatası');
            }
        });
    });
    
    // Her 60 saniyede bir bot durumunu kontrol et
    setInterval(function() {
        checkBotStatus();
    }, 60000);
    
    // Her 10 saniyede bir logları yenile
    setInterval(function() {
        refreshLogs();
    }, 10000);
});

/**
 * Bot durumunu kontrol eder
 */
function checkBotStatus() {
    $.ajax({
        url: 'bot_control.php', // Use bot_control.php instead of simple_bot_control.php
        type: 'GET',
        data: { action: 'status' },
        dataType: 'json',
        success: function(response) {
            console.log("Bot status response:", response); // Debugging
            // Gerçek çalışma durumunu kontrol et (process_count > 0 ise çalışıyordur)
            var isRunning = response.running === true || 
                           (response.process_count !== undefined && response.process_count > 0);
            updateStatusUI(isRunning);
        },
        error: function(xhr, status, error) {
            console.error("Bot status check error:", error, xhr.responseText);
            showAlert('danger', 'Bot durumu alınamadı! Sunucu hatası.');
        }
    });
}

/**
 * Botu başlatır
 */
function startBot() {
    // Kullanıcıya hemen geri bildirim ver
    showAlert('info', 'Bot başlatılıyor... Lütfen bekleyin.');
    updateStatusUI(true, 'Başlatılıyor...');
    
    // Düğmeyi devre dışı bırak
    $('.toggle-bot').prop('disabled', true);
    
    $.ajax({
        url: 'bot_control.php',
        type: 'GET',
        data: { action: 'start' },
        dataType: 'json',
        timeout: 10000, // 10 saniye timeout (bot başlatma uzun sürebilir)
        success: function(response) {
            // Düğmeyi tekrar etkinleştir
            $('.toggle-bot').prop('disabled', false);
            
            if (response.success) {
                showAlert('success', response.message);
                updateStatusUI(true); // Bot çalışıyor
                
                // Bot durumunu 3 saniye sonra tekrar kontrol et
                setTimeout(function() {
                    checkBotStatus();
                    refreshLogs(); // Logları da yenile
                }, 3000);
            } else {
                showAlert('danger', response.message + (response.last_error ? '<br>Hata: ' + response.last_error : ''));
                updateStatusUI(false); // Bot çalışmıyor
            }
        },
        error: function(xhr, status, error) {
            // Düğmeyi tekrar etkinleştir
            $('.toggle-bot').prop('disabled', false);
            
            // Zaman aşımı hatası değilse
            if (status !== 'timeout') {
                showAlert('danger', 'Bot başlatılamadı! Sunucu hatası.');
                updateStatusUI(false);
                console.error("Bot start error:", error, xhr.responseText);
            } else {
                // Zaman aşımı durumunda bunun normal olduğunu söyle
                showAlert('warning', 'Bot başlatılıyor, ancak işlem biraz zaman alabilir. Durum birkaç saniye içinde güncellenecek.');
                
                // 5 saniye sonra bot durumunu kontrol et
                setTimeout(function() {
                    checkBotStatus();
                    refreshLogs(); // Logları da yenile
                }, 5000);
            }
        }
    });
}

/**
 * Botu durdurur
 */
function stopBot() {
    $.ajax({
        url: 'bot_control.php', // Use bot_control.php instead of simple_bot_control.php
        type: 'GET',
        data: { action: 'stop' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateStatusUI(false);
                showAlert('success', response.message);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Bot durdurulamadı! Sunucu hatası.');
        }
    });
}

/**
 * Botu yeniden başlatır
 */
function restartBot() {
    // Kullanıcıya geri bildirim ver
    showAlert('info', 'Bot yeniden başlatılıyor... Lütfen bekleyin.');
    
    // Düğmeleri devre dışı bırak
    $('.toggle-bot, .restart-bot').prop('disabled', true);
    
    $.ajax({
        url: 'bot_control.php',
        type: 'GET',
        data: { action: 'restart' },
        dataType: 'json',
        timeout: 15000, // 15 saniye timeout
        success: function(response) {
            // Düğmeleri tekrar etkinleştir
            $('.toggle-bot, .restart-bot').prop('disabled', false);
            
            if (response.success) {
                updateStatusUI(true);
                showAlert('success', response.message || 'Bot başarıyla yeniden başlatıldı');
                
                // Bot durumunu 5 saniye sonra tekrar kontrol et
                setTimeout(function() {
                    checkBotStatus();
                    refreshLogs(); // Logları da yenile
                }, 5000);
            } else {
                showAlert('danger', response.message || 'Bot yeniden başlatılamadı');
                updateStatusUI(false);
            }
        },
        error: function(xhr, status, error) {
            // Düğmeleri tekrar etkinleştir
            $('.toggle-bot, .restart-bot').prop('disabled', false);
            
            if (status !== 'timeout') {
                showAlert('danger', 'Bot yeniden başlatılamadı! Sunucu hatası.');
                console.error("Bot restart error:", error, xhr.responseText);
            } else {
                // Zaman aşımı durumunda bunun normal olduğunu söyle
                showAlert('warning', 'Bot yeniden başlatılıyor, ancak işlem biraz zaman alabilir. Durum birkaç saniye içinde güncellenecek.');
                
                // 10 saniye sonra bot durumunu kontrol et
                setTimeout(function() {
                    checkBotStatus();
                    refreshLogs();
                }, 10000);
            }
        }
    });
}

/**
 * Logları yeniler
 */
function refreshLogs() {
    $.ajax({
        url: 'api/get_logs.php',
        type: 'GET',
        success: function(logs) {
            $('.log-container').html(logs);
            // Log container'ı en aşağıya kaydır
            var logContainer = $('.log-container');
            logContainer.scrollTop(logContainer.prop('scrollHeight'));
        }
    });
}

/**
 * Bot durum bilgisine göre UI'ı günceller
 */
function updateStatusUI(isRunning) {
    // Toggle butonunu güncelle
    if (isRunning) {
        // Bot çalışıyor
        $('.toggle-bot').removeClass('btn-success').addClass('btn-danger');
        $('.toggle-bot').data('action', 'stop');
        $('.toggle-bot').html('<i class="fas fa-stop-circle"></i> Botu Durdur');
        
        // Eski butonlar için
        $('.stop-bot').removeClass('d-none');
        $('.start-bot').addClass('d-none');
        
        // Status metinlerini güncelle
        $('h6:contains("Durum:")').html('Durum: <span class="text-success">Çalışıyor</span>');
        $('h3:has(.fa-play-circle, .fa-stop-circle)').html('<i class="fas fa-play-circle text-success"></i>');
        
        // Badge güncelle
        $('.card-footer .badge').removeClass('badge-danger').addClass('badge-success').text('Çalışıyor');
    } else {
        // Bot durdu
        $('.toggle-bot').removeClass('btn-danger').addClass('btn-success');
        $('.toggle-bot').data('action', 'start');
        $('.toggle-bot').html('<i class="fas fa-play-circle"></i> Botu Başlat');
        
        // Eski butonlar için
        $('.start-bot').removeClass('d-none');
        $('.stop-bot').addClass('d-none');
        
        // Status metinlerini güncelle
        $('h6:contains("Durum:")').html('Durum: <span class="text-danger">Durdu</span>');
        $('h3:has(.fa-play-circle, .fa-stop-circle)').html('<i class="fas fa-stop-circle text-danger"></i>');
        
        // Badge güncelle
        $('.card-footer .badge').removeClass('badge-success').addClass('badge-danger').text('Durdu');
    }
}

/**
 * Alert mesajı gösterir
 */
function showAlert(type, message) {
    var alert = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                  message +
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                  '<span aria-hidden="true">&times;</span></button></div>');
    
    // Önceki alert'ı kaldır ve yenisini ekle
    $('.alert').remove();
    $('.card-header').after(alert);
    
    // 5 saniye sonra alert'ı gizle
    setTimeout(function() {
        alert.alert('close');
    }, 5000);
}

// dashboard.js - Trading bot dashboard fonksiyonları
$(document).ready(function() {
    // Sayfa yüklendiğinde gelen analiz verilerini yükle
    loadAnalysisData();
    
    // Her 60 saniyede bir verileri güncelle
    setInterval(loadAnalysisData, 60000);
    
    // Analizleri yenile butonuna tıklama
    $('.refresh-analysis').click(function() {
        loadAnalysisData();
    });
    
    // Coin detay butonuna tıklama
    $(document).on('click', '.coin-detail', function() {
        const symbol = $(this).data('symbol');
        showCoinDetails(symbol);
    });
    
    // Manuel işlem butonlarına tıklama
    $(document).on('click', '.manual-trade', function() {
        const symbol = $(this).data('symbol');
        const action = $(this).data('action');
        
        if (confirm(`${symbol} için ${action === 'buy' ? 'ALIM' : 'SATIM'} işlemi yapmak istediğinizden emin misiniz?`)) {
            executeManualTrade(symbol, action);
        }
    });
});

// Coin analiz verilerini yükle
function loadAnalysisData() {
    // Ajax isteğinden önce bir yükleme göstergesi ekle
    $('#analysis-table tbody').html('<tr><td colspan="8" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Analiz verileri yükleniyor...</p></td></tr>');
    
    // Cache'den kaçınmak için timestamp ekle
    $.ajax({
        url: 'api/bot_api.php',
        method: 'GET',
        data: { 
            action: 'getCoinAnalyses',
            _: new Date().getTime() // Cache engellemek için timestamp ekle
        },
        dataType: 'json',
        timeout: 15000, // 15 saniye timeout
        success: function(response) {
            if (response && response.success && response.data) {
                updateAnalysisTable(response.data);
                $('#last-update').text(new Date().toLocaleTimeString());
                console.log("Analiz verileri başarıyla güncellendi");
            } else {
                console.error('Analiz verileri alınamadı:', response ? response.message : 'Yanıt alınamadı');
                showAlert('warning', 'Coin analiz verileri alınamadı. Detaylar için konsolu kontrol edin.');
                // Hata durumunda tabloyu temizle
                $('#analysis-table tbody').html('<tr><td colspan="8" class="text-center text-danger">Analiz verileri alınamadı. Lütfen sayfayı yenileyin.</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Analiz verileri yüklenirken hata:', error, xhr ? xhr.responseText : 'Yanıt yok');
            showAlert('danger', 'Analiz verileri alınamadı: ' + (status === 'timeout' ? 'Zaman aşımı' : 'Sunucu hatası.'));
            // Hata durumunda tabloyu temizle
            $('#analysis-table tbody').html('<tr><td colspan="8" class="text-center text-danger">API hatası: ' + error + '. Lütfen sayfayı yenileyin.</td></tr>');
        }
    });
}

// Analiz tablosunu güncelle
function updateAnalysisTable(analyses) {
    const tableBody = $('#analysis-table tbody');
    tableBody.empty();
    
    // Tablo bulunamadıysa fonksiyonu sessizce sonlandır
    if (tableBody.length === 0) {
        console.log("Analiz tablosu bulunamadı, sayfada analiz tablosu olmayabilir");
        return;
    }
    
    // Coinleri sembol sırasına göre sırala
    const sortedCoins = Object.keys(analyses).sort();
    
    for (const symbol of sortedCoins) {
        const analysis = analyses[symbol];
        
        if (!analysis) continue;
        
        // Price değerinin sayı olup olmadığını kontrol et
        const priceValue = typeof analysis.price === 'number' ? 
            analysis.price : (parseFloat(analysis.price) || 0);
            
        // RSI değerinin bir sayı olduğundan emin ol
        const rsiValue = typeof analysis.indicators?.rsi?.value === 'number' ? 
            analysis.indicators.rsi.value.toFixed(2) : 
            (parseFloat(analysis.indicators?.rsi?.value) || 0).toFixed(2);
            
        const rsiSignal = analysis.indicators?.rsi?.signal || 'NEUTRAL';
        const rsiClass = rsiSignal === 'BUY' ? 'text-success' : (rsiSignal === 'SELL' ? 'text-danger' : 'text-secondary');
        
        const macdSignal = analysis.indicators?.macd?.signal || 'NEUTRAL';
        const macdClass = macdSignal === 'BUY' ? 'text-success' : (macdSignal === 'SELL' ? 'text-danger' : 'text-secondary');
        
        const bbSignal = analysis.indicators?.bollinger?.signal || 'NEUTRAL';
        const bbClass = bbSignal === 'BUY' ? 'text-success' : (bbSignal === 'SELL' ? 'text-danger' : 'text-secondary');
        
        // TradingView null kontrolü yap
        const tvExists = analysis.indicators?.tradingview != null;
        const tvRecommend = tvExists ? (parseFloat(analysis.indicators.tradingview.recommend_all) || 0).toFixed(2) : '0.00';
        const tvSignal = tvExists ? analysis.indicators.tradingview.signal || 'NEUTRAL' : 'NEUTRAL';
        const tvClass = tvSignal === 'BUY' ? 'text-success' : (tvSignal === 'SELL' ? 'text-danger' : 'text-secondary');
        
        const tradeSignal = analysis.trade_signal || 'NEUTRAL';
        const signalClass = tradeSignal === 'BUY' ? 'badge-success' : (tradeSignal === 'SELL' ? 'badge-danger' : 'badge-secondary');
        const signalText = tradeSignal === 'BUY' ? 'ALIM' : (tradeSignal === 'SELL' ? 'SATIM' : 'BEKLE');
        
        const row = `
            <tr>
                <td><strong>${symbol}</strong></td>
                <td>${priceValue < 10 ? priceValue.toFixed(6) : priceValue.toFixed(2)}</td>
                <td class="${rsiClass}">
                    ${rsiValue}
                    <i class="fas ${rsiSignal === 'BUY' ? 'fa-arrow-up' : (rsiSignal === 'SELL' ? 'fa-arrow-down' : 'fa-minus')}"></i>
                </td>
                <td class="${macdClass}">
                    <i class="fas ${macdSignal === 'BUY' ? 'fa-arrow-up' : (macdSignal === 'SELL' ? 'fa-arrow-down' : 'fa-minus')}"></i>
                    ${macdSignal}
                </td>
                <td class="${bbClass}">
                    <i class="fas ${bbSignal === 'BUY' ? 'fa-arrow-up' : (bbSignal === 'SELL' ? 'fa-arrow-down' : 'fa-minus')}"></i>
                    ${bbSignal}
                </td>
                <td class="${tvClass}">
                    <i class="fas ${tvSignal === 'BUY' ? 'fa-arrow-up' : (tvSignal === 'SELL' ? 'fa-arrow-down' : 'fa-minus')}"></i>
                    ${tvRecommend}
                </td>
                <td>
                    <span class="badge ${signalClass}">
                        ${signalText}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info coin-detail" data-symbol="${symbol}">
                            <i class="fas fa-chart-line"></i>
                        </button>
                        <button class="btn btn-success manual-trade" data-symbol="${symbol}" data-action="buy">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                        <button class="btn btn-danger manual-trade" data-symbol="${symbol}" data-action="sell">
                            <i class="fas fa-cash-register"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        
        tableBody.append(row);
    }
}

// Coin detay modalını göster
function showCoinDetails(symbol) {
    // TradingView grafiğini yükle
    const formattedSymbol = symbol.replace('/', '');
    
    // Modal içeriğini oluştur
    const modalContent = `
        <div class="modal fade" id="coinDetailModal" tabindex="-1" role="dialog" aria-labelledby="coinDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="coinDetailModalLabel">${symbol} Analizi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="coinDetailTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="chart-tab" data-toggle="tab" href="#chart" role="tab" aria-controls="chart" aria-selected="true">Grafik</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="indicators-tab" data-toggle="tab" href="#indicators" role="tab" aria-controls="indicators" aria-selected="false">İndikatörler</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="trades-tab" data-toggle="tab" href="#trades" role="tab" aria-controls="trades" aria-selected="false">Son İşlemler</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="coinDetailTabContent">
                            <div class="tab-pane fade show active" id="chart" role="tabpanel" aria-labelledby="chart-tab">
                                <div class="tradingview-widget-container">
                                    <div id="tradingview_widget" style="height:500px;"></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="indicators" role="tabpanel" aria-labelledby="indicators-tab">
                                <div class="row mt-3" id="indicator-details">
                                    <div class="col-12">
                                        <h6>İndikatör verileri yükleniyor...</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="trades" role="tabpanel" aria-labelledby="trades-tab">
                                <div class="table-responsive mt-3">
                                    <table class="table table-striped" id="coin-trades-table">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>İşlem</th>
                                                <th>Fiyat</th>
                                                <th>Miktar</th>
                                                <th>Toplam</th>
                                                <th>K/Z</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="6" class="text-center">İşlemler yükleniyor...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Önceki modalı kaldır ve yeni modalı ekle
    $('#coinDetailModal').remove();
    $('body').append(modalContent);
    
    // Modalı göster
    $('#coinDetailModal').modal('show');
    
    // Modal gösterildikten sonra TradingView grafiğini yükle
    $('#coinDetailModal').on('shown.bs.modal', function() {
        loadTradingViewChart(formattedSymbol);
        loadCoinIndicatorDetails(symbol);
        loadCoinTradeHistory(symbol);
    });
}

// TradingView grafiğini yükle
function loadTradingViewChart(symbol) {
    new TradingView.widget({
        "width": "100%",
        "height": 500,
        "symbol": "BINANCE:" + symbol,
        "interval": "60",
        "timezone": "Europe/Istanbul",
        "theme": "light",
        "style": "1",
        "locale": "tr",
        "toolbar_bg": "#f1f3f6",
        "enable_publishing": false,
        "hide_side_toolbar": false,
        "allow_symbol_change": true,
        "container_id": "tradingview_widget"
    });
}

// Coin indikatör detaylarını yükle
function loadCoinIndicatorDetails(symbol) {
    $.ajax({
        url: 'api/bot_api.php?action=getCoinAnalysis',
        method: 'GET',
        data: { symbol: symbol },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const analysis = response.data;
                
                // İndikatör detaylarını göster
                const indicatorHtml = `
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">RSI (Göreceli Güç İndeksi)</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Değer:</span>
                                    <strong>${analysis.indicators.rsi.value.toFixed(2)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sinyal:</span>
                                    <strong class="${analysis.indicators.rsi.signal === 'BUY' ? 'text-success' : (analysis.indicators.rsi.signal === 'SELL' ? 'text-danger' : 'text-secondary')}">
                                        ${analysis.indicators.rsi.signal}
                                    </strong>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">Aşırı Satım</div>
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">Nötr</div>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">Aşırı Alım</div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small>0</small>
                                    <small>30</small>
                                    <small>70</small>
                                    <small>100</small>
                                </div>
                                <div class="position-relative mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="position-absolute" style="top: -10px; left: calc(${analysis.indicators.rsi.value}% - 5px);">
                                        <i class="fas fa-caret-down text-dark"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">MACD (Hareketli Ortalama Yakınsaklık/Iraksaklık)</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>MACD:</span>
                                    <strong>${analysis.indicators.macd.value.toFixed(6)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sinyal Çizgisi:</span>
                                    <strong>${analysis.indicators.macd.signal_line.toFixed(6)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Fark:</span>
                                    <strong class="${(analysis.indicators.macd.value - analysis.indicators.macd.signal_line) > 0 ? 'text-success' : 'text-danger'}">
                                        ${(analysis.indicators.macd.value - analysis.indicators.macd.signal_line).toFixed(6)}
                                    </strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sinyal:</span>
                                    <strong class="${analysis.indicators.macd.signal === 'BUY' ? 'text-success' : (analysis.indicators.macd.signal === 'SELL' ? 'text-danger' : 'text-secondary')}">
                                        ${analysis.indicators.macd.signal}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Bollinger Bantları</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Üst Bant:</span>
                                    <strong>${analysis.indicators.bollinger.upper.toFixed(2)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Orta Bant:</span>
                                    <strong>${analysis.indicators.bollinger.middle.toFixed(2)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Alt Bant:</span>
                                    <strong>${analysis.indicators.bollinger.lower.toFixed(2)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Mevcut Fiyat:</span>
                                    <strong>${analysis.price.toFixed(2)}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sinyal:</span>
                                    <strong class="${analysis.indicators.bollinger.signal === 'BUY' ? 'text-success' : (analysis.indicators.bollinger.signal === 'SELL' ? 'text-danger' : 'text-secondary')}">
                                        ${analysis.indicators.bollinger.signal}
                                    </strong>
                                </div>
                                <div class="position-relative mt-3" style="height: 40px;">
                                    <div class="position-absolute bg-light w-100" style="height: 20px; border: 1px solid #ddd; top: 10px;"></div>
                                    <div class="position-absolute" style="top: 0; left: calc(50% - 5px);">
                                        <i class="fas fa-caret-down text-dark"></i>
                                    </div>
                                    <div class="position-absolute" style="top: 0; left: 0;">
                                        <small class="text-dark">Alt Bant</small>
                                    </div>
                                    <div class="position-absolute" style="top: 0; right: 0;">
                                        <small class="text-dark">Üst Bant</small>
                                    </div>
                                    
                                    <div class="position-absolute bg-primary" style="height: 10px; width: 10px; border-radius: 50%; top: 15px; left: ${((analysis.price - analysis.indicators.bollinger.lower) / (analysis.indicators.bollinger.upper - analysis.indicators.bollinger.lower)) * 100}%;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">TradingView Analizi</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Genel Öneri:</span>
                                    <strong class="${analysis.indicators.tradingview?.recommend_all < 0 ? 'text-success' : (analysis.indicators.tradingview?.recommend_all > 0 ? 'text-danger' : 'text-secondary')}">
                                        ${analysis.indicators.tradingview?.recommend_all.toFixed(2) ?? 'N/A'}
                                    </strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>MA Öneri:</span>
                                    <strong class="${analysis.indicators.tradingview?.recommend_ma < 0 ? 'text-success' : (analysis.indicators.tradingview?.recommend_ma > 0 ? 'text-danger' : 'text-secondary')}">
                                        ${analysis.indicators.tradingview?.recommend_ma.toFixed(2) ?? 'N/A'}
                                    </strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sinyal:</span>
                                    <strong class="${analysis.indicators.tradingview?.signal === 'BUY' ? 'text-success' : (analysis.indicators.tradingview?.signal === 'SELL' ? 'text-danger' : 'text-secondary')}">
                                        ${analysis.indicators.tradingview?.signal ?? 'N/A'}
                                    </strong>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Güçlü Al</div>
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 34%" aria-valuenow="34" aria-valuemin="0" aria-valuemax="100">Nötr</div>
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Güçlü Sat</div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small>-1.0</small>
                                    <small>-0.2</small>
                                    <small>0</small>
                                    <small>0.2</small>
                                    <small>1.0</small>
                                </div>
                                <div class="position-relative mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="position-absolute" style="top: -10px; left: calc(50% + ${analysis.indicators.tradingview?.recommend_all * 50}% - 5px);">
                                        <i class="fas fa-caret-down text-dark"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Genel İşlem Sinyali</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>
                                    <span class="badge ${analysis.trade_signal === 'BUY' ? 'badge-success' : (analysis.trade_signal === 'SELL' ? 'badge-danger' : 'badge-secondary')}">
                                        ${analysis.trade_signal === 'BUY' ? 'ALIM' : (analysis.trade_signal === 'SELL' ? 'SATIM' : 'BEKLE')}
                                    </span>
                                </h3>
                                <div class="mt-3">
                                    <p><strong>Sinyal Dağılımı:</strong> ${analysis.signals?.buy_count ?? 0} Alım, ${analysis.signals?.sell_count ?? 0} Satım, ${analysis.signals?.neutral_count ?? 0} Nötr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#indicator-details').html(indicatorHtml);
            } else {
                $('#indicator-details').html('<div class="col-12"><div class="alert alert-warning">İndikatör detayları alınamadı.</div></div>');
            }
        },
        error: function() {
            $('#indicator-details').html('<div class="col-12"><div class="alert alert-danger">İndikatör detayları alınırken bir hata oluştu.</div></div>');
        }
    });
}

// Coin işlem geçmişini yükle
function loadCoinTradeHistory(symbol) {
    $.ajax({
        url: 'api/bot_api.php?action=getCoinTrades',
        method: 'GET',
        data: { symbol: symbol },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                const trades = response.data;
                let tableRows = '';
                
                trades.forEach(trade => {
                    const tradeType = trade.type;
                    const typeClass = tradeType === 'BUY' ? 'success' : 'danger';
                    const typeText = tradeType === 'BUY' ? 'ALIM' : 'SATIM';
                    const profitLossClass = trade.profit_loss >= 0 ? 'text-success' : 'text-danger';
                    
                    tableRows += `
                        <tr>
                            <td>${trade.timestamp}</td>
                            <td><span class="badge badge-${typeClass}">${typeText}</span></td>
                            <td>${trade.price.toFixed(trade.price < 10 ? 6 : 2)}</td>
                            <td>${trade.amount.toFixed(4)}</td>
                            <td>${trade.total.toFixed(2)} USDT</td>
                            <td class="${profitLossClass}">${trade.profit_loss ? (trade.profit_loss >= 0 ? '+' : '') + trade.profit_loss.toFixed(2) : '-'}</td>
                        </tr>
                    `;
                });
                
                $('#coin-trades-table tbody').html(tableRows);
            } else {
                $('#coin-trades-table tbody').html('<tr><td colspan="6" class="text-center">Bu coin için işlem kaydı bulunamadı.</td></tr>');
            }
        },
        error: function() {
            $('#coin-trades-table tbody').html('<tr><td colspan="6" class="text-center">İşlem verileri alınırken bir hata oluştu.</td></tr>');
        }
    });
}

// Manuel işlem gerçekleştir
function executeManualTrade(symbol, action) {
    $.ajax({
        url: 'api/bot_api.php?action=manualTrade',
        method: 'POST',
        data: {
            symbol: symbol,
            action: action
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(`${symbol} için ${action === 'buy' ? 'ALIM' : 'SATIM'} işlemi başarıyla gerçekleştirildi.`);
                // Tabloyu güncelle
                loadAnalysisData();
            } else {
                alert(`İşlem sırasında bir hata oluştu: ${response.message}`);
            }
        },
        error: function() {
            alert('İşlem gerçekleştirilirken bir hata oluştu.');
        }
    });
}