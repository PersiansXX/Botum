/**
 * Performance Optimizer for Trading Bot UI
 * 
 * Bu dosya, trading bot arayüzündeki sayfaların performansını artırmak için
 * gerekli optimizasyonları içerir.
 */

// Performans izleme için başlangıç zamanı
const pageLoadStart = performance.now();

// Sayfa yüklendiğinde çalışacak optimizasyonlar
document.addEventListener('DOMContentLoaded', function() {
    // Sayfa yükleme süresini ölç ve konsola yaz
    const loadTime = performance.now() - pageLoadStart;
    console.log(`Sayfa yükleme süresi: ${loadTime.toFixed(2)}ms`);
    
    // Gereksiz AJAX isteklerini azaltmak için bir izleme mekanizması
    window.lastAjaxCalls = {};
    
    // TradingView widget'ları lazy-load etmek için
    lazyLoadTradingViewWidgets();
    
    // Log yenileme süresini uzat (10 saniyeden 30 saniyeye)
    optimizePollingIntervals();
    
    // AJAX isteklerini optimize et
    optimizeAjaxRequests();
});

/**
 * AJAX isteklerini optimize eder
 */
function optimizeAjaxRequests() {
    // jQuery AJAX ayarlarını değiştir
    if (typeof $ !== 'undefined') {
        $.ajaxSetup({
            cache: true, // Browser cache'i aktif et
            timeout: 30000, // Timeout süresini 30 saniyeye çıkar
            beforeSend: function(xhr, settings) {
                // Aynı URL'ye çok sık istek göndermeyi engelle
                if (!shouldMakeRequest(settings.url, 3000)) {
                    xhr.abort(); // İsteği iptal et
                    return false;
                }
                return true;
            }
        });
    }
}

/**
 * AJAX isteklerini optimize eder ve gereksiz çağrıları önler
 * @param {string} endpoint - API endpoint'i
 * @param {number} minInterval - Minimum çağrı aralığı (ms)
 * @returns {boolean} - İsteğin yapılıp yapılmayacağı
 */
function shouldMakeRequest(endpoint, minInterval = 5000) {
    if (!endpoint) return true;
    
    // URL parametrelerini temizle
    const baseEndpoint = endpoint.split('?')[0];
    const now = Date.now();
    const lastCall = window.lastAjaxCalls[baseEndpoint] || 0;
    
    // Son çağrıdan bu yana yeterli süre geçmiş mi?
    if (now - lastCall < minInterval) {
        console.log(`${baseEndpoint} için çok sık istek yapılıyor, atlanıyor (son istek: ${now - lastCall}ms önce)`);
        return false;
    }
    
    // Bu isteği kaydedelim
    window.lastAjaxCalls[baseEndpoint] = now;
    return true;
}

/**
 * Polling intervallerini optimize eder
 */
function optimizePollingIntervals() {
    // Mevcut setInterval'ları temizle
    const oldIntervals = window.pollingIntervals || [];
    oldIntervals.forEach(interval => clearInterval(interval));
    
    // Yeni optimized interval'ları ekle
    window.pollingIntervals = [];
    
    // Log yenileme - 10 saniyeden 30 saniyeye çıkar
    if (typeof refreshLogs === 'function') {
        const logInterval = setInterval(function() {
            if (shouldMakeRequest('api/get_logs.php', 15000)) {
                refreshLogs();
            }
        }, 30000);
        window.pollingIntervals.push(logInterval);
    }
    
    // Bot durumu kontrolü - 60 saniyeden 120 saniyeye çıkar
    if (typeof checkBotStatus === 'function') {
        const statusInterval = setInterval(function() {
            if (shouldMakeRequest('bot_control.php', 10000)) {
                checkBotStatus();
            }
        }, 120000);
        window.pollingIntervals.push(statusInterval);
    }
    
    // Bakiye güncellemesi - 30 saniyeden 60 saniyeye çıkar
    if (typeof refreshBalances === 'function') {
        const balancesInterval = setInterval(function() {
            if (shouldMakeRequest('api/get_balances.php', 20000)) {
                refreshBalances();
            }
        }, 60000);
        window.pollingIntervals.push(balancesInterval);
    }
}

/**
 * TradingView widget'larını lazy-load eder
 */
function lazyLoadTradingViewWidgets() {
    // Sayfada TradingView container var mı kontrol et
    const tvContainers = document.querySelectorAll('.tradingview-widget-container');
    
    if (tvContainers.length === 0) return;
    
    // Intersection Observer API'si var mı kontrol et
    if (!('IntersectionObserver' in window)) {
        // Intersection Observer yoksa normal yükle
        tvContainers.forEach(container => loadTradingViewWidget(container));
        return;
    }
    
    // Sayfa yüklendiğinde değil, kullanıcı görüntülediğinde yükle
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const container = entry.target;
                
                // Widget zaten yüklendi mi kontrol et
                if (container.dataset.loaded === 'true') return;
                
                // Widget'ı yükle
                loadTradingViewWidget(container);
                
                // Bu container'ı artık izleme
                observer.unobserve(container);
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Tüm TradingView container'larını izle
    tvContainers.forEach(container => {
        observer.observe(container);
    });
}

/**
 * TradingView widget'ını yükler
 * @param {HTMLElement} container - Widget container'ı
 */
function loadTradingViewWidget(container) {
    // Widget zaten yüklendi mi kontrol et
    if (container.dataset.loaded === 'true') return;
    
    const symbol = container.dataset.symbol || 'BTCUSDT';
    const interval = container.dataset.interval || '60';
    const widgetId = container.querySelector('div')?.id;
    
    if (!widgetId) return;
    
    console.log(`TradingView widget yükleniyor: ${symbol}`);
    
    try {
        if (typeof TradingView !== 'undefined') {
            new TradingView.widget({
                "width": "100%",
                "height": 500,
                "symbol": "BINANCE:" + symbol,
                "interval": interval,
                "timezone": "Europe/Istanbul",
                "theme": "light",
                "style": "1",
                "locale": "tr",
                "toolbar_bg": "#f1f3f6",
                "enable_publishing": false,
                "hide_side_toolbar": false,
                "allow_symbol_change": true,
                "container_id": widgetId
            });
            
            // Widget yüklendi olarak işaretle
            container.dataset.loaded = 'true';
        }
    } catch (e) {
        console.error('TradingView widget yüklenirken hata:', e);
    }
}

/**
 * DOM manipülasyonlarını optimize eder
 * @param {Function} updateFunc - DOM güncelleme fonksiyonu
 * @param {number} delay - Gecikme süresi (ms)
 * @returns {Function} - Optimize edilmiş fonksiyon
 */
function optimizeDomUpdates(updateFunc, delay = 200) {
    let timeout = null;
    
    return function(...args) {
        if (timeout) {
            clearTimeout(timeout);
        }
        
        timeout = setTimeout(() => {
            updateFunc.apply(this, args);
            timeout = null;
        }, delay);
    };
}

// Sayfa yüklenme süresi ölçümü
window.addEventListener('load', function() {
    const totalLoadTime = performance.now() - pageLoadStart;
    console.log(`Toplam sayfa yükleme süresi: ${totalLoadTime.toFixed(2)}ms`);
    
    // Sayfada var olan başlangıç fonksiyonlarını bul ve gerekirse optimize et
    if (typeof initializeCharts === 'function') {
        setTimeout(initializeCharts, 100);
    }
    
    if (typeof refreshLogs === 'function') {
        setTimeout(refreshLogs, 1000);
    }
});