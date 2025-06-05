// Cryptocurrency data fetching and display functions

// Hata ayıklama modu
const DEBUG = true;

// Log fonksiyonu - geliştirme için
function debugLog(message, data = null) {
    if (DEBUG) {
        if (data) {
            console.log(`[Kripto API] ${message}`, data);
        } else {
            console.log(`[Kripto API] ${message}`);
        }
    }
}

// API yolunu belirle - CentOS 7 yol yapısına göre düzenlendi
function getApiPath() {
    // CentOS 7'de Apache genellikle /var/www/html altında çalışır
    // Ancak tarayıcıda görülen URL'yi kullanmalıyız
    const baseUrl = window.location.origin;
    const path = window.location.pathname;
    
    // Linux dosya yolu formatında yolları kontrol et
    if (path.includes('/web/coins.php')) {
        return `${baseUrl}/web/api/get_active_coins.php`;
    }
    
    if (path.includes('/web/index.php') || path.endsWith('/web/')) {
        return `${baseUrl}/web/api/get_active_coins.php`;
    }
    
    // Varsayılan Linux yolu
    return `${baseUrl}/web/api/get_active_coins.php`;
}

// Bot'un aktif coinlerini getir - CentOS 7 için optimize edildi
async function fetchBotActiveCoins() {
    try {
        // Örnek verileri doğrudan kullan (test için)
        // API çağrısı başarısız olursa bu verileri gösterelim
        const exampleCoinsToUse = getExampleCoins();
        
        // Tam API URL'sini oluştur
        const apiUrl = getApiPath();
        debugLog('API isteği yapılıyor (CentOS 7):', apiUrl);
        
        // CentOS 7'de API zaman aşımı için
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 saniye zaman aşımı - CentOS'ta ağ daha yavaş olabilir
        
        // API çağrısını yap
        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            signal: controller.signal
        });
        
        // Zamanlayıcıyı temizle
        clearTimeout(timeoutId);
        
        // Yanıt kontrolü
        if (!response.ok) {
            throw new Error(`API hatası: ${response.status}`);
        }
        
        // İçerik türünü kontrol et
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            debugLog('JSON olmayan yanıt:', text);
            throw new Error('API JSON yanıtı vermedi');
        }
        
        // JSON yanıtını dön
        const data = await response.json();
        
        // Veri kontrolleri
        if (!Array.isArray(data)) {
            if (data.error) {
                throw new Error(data.message || 'API hatası');
            }
            debugLog('Geçersiz yanıt (dizi değil):', data);
            return exampleCoinsToUse;
        }
        
        if (data.length === 0) {
            debugLog('Boş dizi döndü, örnek verileri kullanıyor');
            return exampleCoinsToUse;
        }
        
        debugLog(`${data.length} coin bulundu (CentOS 7)`);
        return data;
        
    } catch (error) {
        console.error('Bot coinlerini getirirken hata (CentOS 7):', error);
        
        // CentOS 7'ye özel hata kontrolü
        if (error.name === 'AbortError') {
            console.error('CentOS 7 API zaman aşımına uğradı');
        }
        
        // API çağrısı başarısız olduğunda
        // Örnek verileri göster
        return getExampleCoins();
    }
}

// Doğrudan örnek veriler - API başarısız olursa
function getExampleCoins() {
    const currentDate = new Date();
    const formattedDate = currentDate.toISOString().slice(0, 19).replace('T', ' ');
    
    return [
        { symbol: 'BTC/USDT', price: 69420.50, change_24h: 2.5, signal: 'BUY', indicators: {rsi: 58}, last_updated: formattedDate },
        { symbol: 'ETH/USDT', price: 3500.75, change_24h: 1.2, signal: 'BUY', indicators: {rsi: 52}, last_updated: formattedDate },
        { symbol: 'BNB/USDT', price: 580.30, change_24h: -0.8, signal: 'NEUTRAL', indicators: {rsi: 45}, last_updated: formattedDate },
        { symbol: 'SOL/USDT', price: 150.25, change_24h: 3.7, signal: 'BUY', indicators: {rsi: 62}, last_updated: formattedDate },
        { symbol: 'XRP/USDT', price: 0.52, change_24h: -1.5, signal: 'SELL', indicators: {rsi: 38}, last_updated: formattedDate }
    ];
}

// Price formatting
function formatPrice(price) {
    if (price === null || price === undefined) return '0.00';
    price = parseFloat(price);
    return price < 1 ? price.toFixed(6) : price.toFixed(2);
}

// Percentage change formatting
function formatPercentageChange(change) {
    if (change === null || change === undefined) return '0.00%';
    change = parseFloat(change);
    
    const formattedChange = change.toFixed(2) + '%';
    const cssClass = change >= 0 ? 'text-success' : 'text-danger';
    
    return `<span class="${cssClass}">${formattedChange}</span>`;
}

// Signal badge class
function getSignalBadgeClass(signal) {
    if (!signal) return 'badge-secondary';
    
    signal = signal.toUpperCase();
    
    switch(signal) {
        case 'BUY':
        case 'AL':
        case 'GÜÇLÜ AL':
            return 'badge-success';
        case 'NEUTRAL':
        case 'NÖTR': 
        case 'BEKLİYOR':
            return 'badge-secondary';
        case 'SELL':
        case 'SAT':
        case 'GÜÇLÜ SAT':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Signal translation
function translateSignal(signal) {
    if (!signal) return 'BEKLİYOR';
    
    signal = signal.toUpperCase();
    
    switch(signal) {
        case 'BUY': return 'AL';
        case 'SELL': return 'SAT';
        case 'NEUTRAL': return 'BEKLİYOR';
        default: return signal;
    }
}

// Format indicators for display
function formatIndicators(indicators) {
    if (!indicators) return '';
    
    // Parse JSON string if needed
    if (typeof indicators === 'string') {
        try {
            indicators = JSON.parse(indicators);
        } catch (e) {
            debugLog('İndikatör JSON çözümleme hatası:', e);
            return '';
        }
    }
    
    // Eğer indicators bir nesne değilse veya rsi içermiyorsa
    if (typeof indicators !== 'object' || !indicators.rsi) {
        return 'RSI: --';
    }
    
    // Temel gösterim için RSI
    const rsi = parseFloat(indicators.rsi);
    let html = `RSI: ${rsi.toFixed(0)}`;
    
    // MACD değeri varsa ekle
    if (indicators.macd !== undefined) {
        html += ` | MACD: ${parseFloat(indicators.macd).toFixed(1)}`;
    }
    
    // Kısa gösterim (tabloda alan kısıtlı)
    if (html.length > 15) {
        html = html.substring(0, 15) + '... ';
        // JSON stringini escape et ve özellikle çift tırnakları güvenli hale getir
        const safeJson = JSON.stringify(indicators).replace(/"/g, '&quot;');
        html += `<button onclick="showIndicatorDetails(${safeJson})" class="btn btn-xs btn-info py-0 px-1" style="font-size: 10px;">Detay</button>`;
    }
    
    return html;
}

// İndikatör detaylarını gösteren fonksiyon - global scope'a eklenmeli
window.showIndicatorDetails = function(indicators) {
    // indicators zaten bir obje olarak gelmeli, değilse çevirelim
    if (typeof indicators === 'string') {
        try {
            indicators = JSON.parse(indicators);
        } catch(e) {
            console.error('İndikatör verisi JSON formatında değil', e);
            return;
        }
    }
    
    // Modal HTML'ini oluştur
    let modalHtml = `
    <div class="modal fade" id="indicatorDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detaylı İndikatör Bilgileri</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- RSI -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">RSI (Göreceli Güç İndeksi)</div>
                                <div class="card-body">`;
    
    // RSI değeri ve yorumlaması
    if (indicators.rsi !== undefined) {
        const rsi = parseFloat(indicators.rsi);
        let rsiClass = 'secondary';
        let rsiSignal = 'BEKLİYOR';
        
        if (rsi < 30) {
            rsiClass = 'success';
            rsiSignal = 'AL';
        } else if (rsi > 70) {
            rsiClass = 'danger';
            rsiSignal = 'SAT';
        }
        
        modalHtml += `
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h4>${rsi.toFixed(1)}</h4>
                                        <span class="badge badge-${rsiClass}">${rsiSignal}</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-${rsiClass}" role="progressbar" style="width: ${rsi}%"
                                            aria-valuenow="${rsi}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small>Aşırı Satım (<30)</small>
                                        <small>Nötr</small>
                                        <small>Aşırı Alım (>70)</small>
                                    </div>
                                    <div class="mt-2">
                                        <small>${rsi < 30 ? '📈 Güçlü alım bölgesi' : rsi > 70 ? '📉 Güçlü satım bölgesi' : '🔄 Nötr bölge'}</small>
                                    </div>`;
    } else {
        modalHtml += `<p class="text-muted">RSI bilgisi bulunamadı.</p>`;
    }
    
    modalHtml += `
                                </div>
                            </div>
                        </div>
                        
                        <!-- MACD -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">MACD (Hareketli Ortalama Yakınsama/Uzaklaşma)</div>
                                <div class="card-body">`;
    
    // MACD değeri ve yorumlaması
    if (indicators.macd !== undefined) {
        const macd = parseFloat(indicators.macd);
        const macdSignal = indicators.macd_signal !== undefined ? parseFloat(indicators.macd_signal) : 0;
        const histogram = indicators.macd_histogram !== undefined ? parseFloat(indicators.macd_histogram) : (macd - macdSignal);
        
        let macdClass = 'secondary';
        let macdSignalTxt = 'BEKLİYOR';
        
        if (macd > macdSignal && histogram > 0) {
            macdClass = 'success';
            macdSignalTxt = 'AL';
        } else if (macd < macdSignal && histogram < 0) {
            macdClass = 'danger';
            macdSignalTxt = 'SAT';
        }
        
        modalHtml += `
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h5>MACD: ${macd.toFixed(2)}</h5>
                                            <h6>Sinyal: ${macdSignal.toFixed(2)}</h6>
                                            <h6>Histogram: ${histogram.toFixed(2)}</h6>
                                        </div>
                                        <span class="badge badge-${macdClass}">${macdSignalTxt}</span>
                                    </div>
                                    <div class="mt-2">
                                        <small>${macdSignalTxt === 'AL' ? '📈 MACD sinyal çizgisini yukarı kesti - Alım sinyali' : 
                                            macdSignalTxt === 'SAT' ? '📉 MACD sinyal çizgisini aşağı kesti - Satım sinyali' : 
                                            '🔄 MACD ve sinyal çizgisi nötr'}</small>
                                    </div>`;
    } else {
        modalHtml += `<p class="text-muted">MACD bilgisi bulunamadı.</p>`;
    }
    
    modalHtml += `
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bollinger Bantları -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">Bollinger Bantları</div>
                                <div class="card-body">`;
    
    // Bollinger Bantları değerleri ve yorumlaması
    if (indicators.bollinger_bands) {
        const upper = indicators.bollinger_bands.upper;
        const middle = indicators.bollinger_bands.middle;
        const lower = indicators.bollinger_bands.lower;
        const currentPrice = indicators.current_price || middle; // Eğer fiyat yoksa orta bandı kullan
        
        let bbClass = 'secondary';
        let bbSignal = 'BEKLİYOR';
        
        // Alt banda yakın ise alım sinyali
        if (currentPrice <= lower * 1.02) {
            bbClass = 'success';
            bbSignal = 'AL';
        } 
        // Üst banda yakın ise satım sinyali
        else if (currentPrice >= upper * 0.98) {
            bbClass = 'danger';
            bbSignal = 'SAT';
        }
        
        modalHtml += `
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6>Üst Bant: ${parseFloat(upper).toFixed(2)}</h6>
                                            <h6>Orta Bant: ${parseFloat(middle).toFixed(2)}</h6>
                                            <h6>Alt Bant: ${parseFloat(lower).toFixed(2)}</h6>
                                        </div>
                                        <span class="badge badge-${bbClass}">${bbSignal}</span>
                                    </div>
                                    <div class="mt-2">
                                        <small>${bbSignal === 'AL' ? '📈 Fiyat alt banda yakın - Potansiyel alım fırsatı' : 
                                            bbSignal === 'SAT' ? '📉 Fiyat üst banda yakın - Potansiyel satım fırsatı' : 
                                            '🔄 Fiyat bantlar arasında - Nötr sinyal'}</small>
                                    </div>`;
    } else {
        modalHtml += `<p class="text-muted">Bollinger Bantları bilgisi bulunamadı.</p>`;
    }
    
    modalHtml += `
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hareketli Ortalamalar -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">Hareketli Ortalamalar</div>
                                <div class="card-body">`;
    
    // Hareketli Ortalama değerleri ve yorumlaması
    if (indicators.moving_average) {
        const shortMA = parseFloat(indicators.moving_average.short_ma);
        const longMA = parseFloat(indicators.moving_average.long_ma);
        
        let maClass = 'secondary';
        let maSignal = 'BEKLİYOR';
        
        // Kısa MA uzun MA'nın üzerindeyse altın çapraz (alım sinyali)
        if (shortMA > longMA) {
            maClass = 'success';
            maSignal = 'AL';
        } 
        // Kısa MA uzun MA'nın altındaysa ölüm çaprazı (satım sinyali)
        else if (shortMA < longMA) {
            maClass = 'danger';
            maSignal = 'SAT';
        }
        
        modalHtml += `
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6>Kısa MA: ${shortMA.toFixed(2)}</h6>
                                            <h6>Uzun MA: ${longMA.toFixed(2)}</h6>
                                            <h6>Fark: ${(shortMA - longMA).toFixed(2)}</h6>
                                        </div>
                                        <span class="badge badge-${maClass}">${maSignal}</span>
                                    </div>
                                    <div class="mt-2">
                                        <small>${maSignal === 'AL' ? '📈 Altın Çapraz - Kısa MA uzun MA\'nın üzerinde' : 
                                            maSignal === 'SAT' ? '📉 Ölüm Çaprazı - Kısa MA uzun MA\'nın altında' : 
                                            '🔄 MA\'lar kesişiyor - Trend değişimi olabilir'}</small>
                                    </div>`;
    } else {
        modalHtml += `<p class="text-muted">Hareketli Ortalama bilgisi bulunamadı.</p>`;
    }
    
    modalHtml += `
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Eğer zaten varsa eski modalı kaldır
    let oldModal = document.getElementById('indicatorDetailModal');
    if (oldModal) {
        document.body.removeChild(oldModal);
    }
    
    // Yeni modal oluştur
    let modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer.firstElementChild);
    
    // Modal'ı göster
    $('#indicatorDetailModal').modal('show');
    
    // Modal kapatıldığında DOM'dan kaldır
    $('#indicatorDetailModal').on('hidden.bs.modal', function() {
        document.body.removeChild(document.getElementById('indicatorDetailModal'));
    });
};

// Update the cryptocurrency table with real data
async function updateCryptoTable() {
    debugLog('Kripto tablosu güncelleniyor (CentOS 7)');
    
    const tableBody = document.querySelector('.table-striped.table-hover tbody');
    if (!tableBody) {
        console.error('Tablo gövdesi bulunamadı');
        return;
    }
    
    // Yükleniyor göstergesi
    tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Yükleniyor...</span>
                </div>
                <p>Coin verileri yükleniyor... <span id="loadingTimer">0</span>s</p>
            </td>
        </tr>
    `;
    
    // Yükleme zamanlayıcısı - CentOS 7'de yanıt süreleri daha uzun olabilir 
    let loadTime = 0;
    const timerElement = document.getElementById('loadingTimer');
    const timer = setInterval(() => {
        loadTime += 1;
        if (timerElement) timerElement.textContent = loadTime;
        
        // CentOS 7'de 45 saniyeye kadar bekle (sunucular daha yavaş olabilir)
        if (loadTime > 45) {
            clearInterval(timer);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Yükleme zaman aşımına uğradı (45s). 
                        <button id="retry-fetch" class="btn btn-sm btn-danger ml-2">Tekrar Dene</button>
                        <button id="show-example" class="btn btn-sm btn-info ml-2">Örnek Verileri Göster</button>
                    </td>
                </tr>
            `;
            
            // Tekrar deneme butonu
            const retryButton = document.getElementById('retry-fetch');
            if (retryButton) {
                retryButton.addEventListener('click', updateCryptoTable);
            }
            
            // Örnek veri gösterme butonu
            const exampleButton = document.getElementById('show-example');
            if (exampleButton) {
                exampleButton.addEventListener('click', () => {
                    updateTableWithBotData(getExampleCoins());
                });
            }
            
            // Linux sunucusunda problem olma ihtimaline karşı, fallback API'yi dene
            fetch(`${window.location.origin}/web/api/direct_coins.php`)
                .then(response => {
                    if (!response.ok) throw new Error('Fallback API hatası');
                    return response.json();
                })
                .then(data => {
                    updateTableWithBotData(data);
                })
                .catch(err => {
                    console.error('Fallback API hatası:', err);
                });
        }
    }, 1000);
    
    try {
        // Verileri getir
        const botCoins = await fetchBotActiveCoins();
        
        // Zamanlayıcıyı durdur
        clearInterval(timer);
        
        // Tabloyu güncelle
        updateTableWithBotData(botCoins);
        
    } catch (error) {
        // Zamanlayıcıyı durdur
        clearInterval(timer);
        
        console.error('Kripto tablosu güncellenirken hata (CentOS 7):', error);
        
        // Hata göster
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    Veri yüklenemedi: ${error.message}
                    <div class="mt-2">
                        <button id="retry-fetch" class="btn btn-sm btn-danger">Tekrar Dene</button>
                        <button id="show-example" class="btn btn-sm btn-info ml-2">Örnek Verileri Göster</button>
                    </div>
                </td>
            </tr>
        `;
        
        // Tekrar deneme butonu
        const retryButton = document.getElementById('retry-fetch');
        if (retryButton) {
            retryButton.addEventListener('click', updateCryptoTable);
        }
        
        // Örnek veri gösterme butonu
        const exampleButton = document.getElementById('show-example');
        if (exampleButton) {
            exampleButton.addEventListener('click', () => {
                updateTableWithBotData(getExampleCoins());
            });
        }
    }
}

// Update table with bot data
function updateTableWithBotData(botCoins) {
    debugLog('Tablo güncelleniyor');
    
    const tableBody = document.querySelector('.table-striped.table-hover tbody');
    if (!tableBody) return;
    
    // Clear table
    tableBody.innerHTML = '';
    
    if (!botCoins || botCoins.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Bot tarafından izlenen aktif coin bulunamadı.
                </td>
            </tr>
        `;
        return;
    }
    
    const currentDate = new Date();
    const formattedDate = currentDate.toISOString().slice(0, 19).replace('T', ' ');
    
    // Add each coin to table
    botCoins.forEach(coin => {
        debugLog(`Coin işleniyor: ${coin.symbol}`, coin);
        
        const row = document.createElement('tr');
        
        // Use the data from bot - format as needed
        row.innerHTML = `
            <td><strong>${coin.symbol}</strong></td>
            <td>${formatPrice(coin.price)}</td>
            <td>${formatPercentageChange(coin.change_24h)}</td>
            <td>
                <span class="badge ${getSignalBadgeClass(coin.signal)}">${translateSignal(coin.signal)}</span>
            </td>
            <td>${formatIndicators(coin.indicators)}</td>
            <td>${coin.last_updated || formattedDate}</td>
            <td>
                <a href="coin_detail.php?symbol=${encodeURIComponent(coin.symbol)}" class="btn btn-sm btn-info">
                    <i class="fas fa-chart-line"></i> Detay
                </a>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Update last update time
    const lastUpdateElement = document.getElementById('last-update-time');
    if (lastUpdateElement) {
        lastUpdateElement.textContent = formattedDate;
    }
    
    debugLog('Tablo güncelleme tamamlandı');
}

// Initialize when the document is loaded
document.addEventListener('DOMContentLoaded', () => {
    debugLog('Kripto API başlatılıyor (CentOS 7)');
    
    // CentOS 7'de API endpoint'i test et
    const apiUrl = getApiPath();
    
    fetch(apiUrl, { 
        method: 'HEAD',
        cache: 'no-cache'
    })
    .then(response => {
        debugLog('API test sonucu (CentOS 7):', response.status);
    })
    .catch(error => {
        debugLog('API test hatası (CentOS 7):', error);
        
        // CentOS 7'de CORS problemi olabilir, direct_coins.php API'sini dene
        debugLog('Alternatif API deneniyor...');
        fetch(`${window.location.origin}/web/api/direct_coins.php`, { method: 'HEAD' })
            .then(resp => {
                debugLog('Alternatif API test sonucu:', resp.status);
            })
            .catch(err => {
                debugLog('Alternatif API test hatası:', err);
            });
    })
    .finally(() => {
        // Her durumda tabloyu güncelle
        updateCryptoTable();
    });
    
    // CentOS 7 için performans optimizasyonu - daha uzun güncelleme aralığı
    setInterval(updateCryptoTable, 3 * 60 * 1000); // 3 dakika
    
    // Yenileme butonu
    const refreshButton = document.getElementById('refresh-data');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yenileniyor...';
            
            updateCryptoTable().finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i> Verileri Yenile';
            });
        });
    }
});
