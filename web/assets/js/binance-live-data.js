/**
 * BinanceLiveData.js
 * Binance API'sinden gerçek zamanlı veri alır ve tabloyu günceller
 */

class BinanceLiveData {
    constructor(options = {}) {
        // Seçenekleri varsayılan değerler ile birleştir
        this.options = Object.assign({
            tableId: 'coin-table',
            refreshInterval: 15000, // 15 saniye
            refreshButtonId: 'refresh-data',
            loadingOverlayId: 'loading-overlay',
            lastUpdateTimeId: 'last-update-time',
            customSymbols: [], // Özel sembol listesi
            onMessage: null, // Mesaj geldiğinde çalışacak geri çağırma fonksiyonu
            onConnectionChange: null // Bağlantı değiştiğinde çalışacak geri çağırma fonksiyonu
        }, options);
        
        // Gerekli öğelere referanslar
        this.table = document.getElementById(this.options.tableId);
        this.refreshButton = document.getElementById(this.options.refreshButtonId);
        this.loadingOverlay = document.getElementById(this.options.loadingOverlayId);
        this.lastUpdateTimeElement = document.getElementById(this.options.lastUpdateTimeId);
        
        // WebSocket bağlantısı
        this.websocket = null;
        
        // Veri önbelleği
        this.coinData = {};
        
        // EventListener'ları oluştur
        this.setupEventListeners();
        
        // Verileri başlangıçta yükle
        this.refreshData();
        
        // WebSocket bağlantısını başlat
        this.initWebSocketConnection();
        
        // Otomatik yenileme zamanlayıcısı
        if (this.options.refreshInterval > 0) {
            this.refreshTimer = setInterval(() => this.refreshData(), this.options.refreshInterval);
        }
    }
    
    /**
     * Olay dinleyicileri ayarlar
     */
    setupEventListeners() {
        if (this.refreshButton) {
            this.refreshButton.addEventListener('click', () => this.refreshData());
        }
        
        // Sayfa kapatıldığında WebSocket bağlantısını kapat
        window.addEventListener('beforeunload', () => {
            this.closeWebSocket();
            clearInterval(this.refreshTimer);
        });
    }
    
    /**
     * WebSocket bağlantısını başlatır
     */
    initWebSocketConnection() {
        // Önceki bağlantıyı kapat
        this.closeWebSocket();
        
        // İzlenen coinleri al
        let coins = [];
        
        // Özel semboller varsa onları kullan
        if (this.options.customSymbols && this.options.customSymbols.length > 0) {
            coins = this.options.customSymbols;
        } else {
            coins = this.getTrackedCoins();
        }
        
        if (coins.length === 0) {
            console.error("İzlenecek coin bulunamadı!");
            if (this.options.onConnectionChange) {
                this.options.onConnectionChange(false);
            }
            return;
        }
        
        // Stream adlarını oluştur
        const streams = coins.map(symbol => `${symbol.toLowerCase()}@ticker`);
        
        // WebSocket URL'sini oluştur
        const wsUrl = `wss://stream.binance.com:9443/stream?streams=${streams.join('/')}`;
        
        // WebSocket bağlantısını aç
        try {
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket bağlantısı kuruldu');
                if (this.options.onConnectionChange) {
                    this.options.onConnectionChange(true);
                }
            };
            
            this.websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                // Stream verilerini işle
                if (data.data) {
                    // Özel işleyici varsa onu kullan
                    if (this.options.onMessage) {
                        this.options.onMessage(data.data);
                    } else {
                        // Varsayılan işleyici
                        this.updateCoinFromWebSocket(data.data);
                    }
                }
            };
            
            this.websocket.onclose = (event) => {
                if (event.wasClean) {
                    console.log(`WebSocket bağlantısı düzgünce kapatıldı, code=${event.code}, reason=${event.reason}`);
                } else {
                    console.error('WebSocket bağlantısı koptu');
                    // 5 saniye sonra tekrar bağlan
                    setTimeout(() => this.initWebSocketConnection(), 5000);
                }
                
                if (this.options.onConnectionChange) {
                    this.options.onConnectionChange(false);
                }
            };
            
            this.websocket.onerror = (error) => {
                console.error('WebSocket hatası:', error);
                if (this.options.onConnectionChange) {
                    this.options.onConnectionChange(false);
                }
            };
            
        } catch (error) {
            console.error('WebSocket bağlantısı oluşturulurken hata:', error);
            if (this.options.onConnectionChange) {
                this.options.onConnectionChange(false);
            }
        }
    }
    
    /**
     * WebSocket bağlantısını kapatır
     */
    closeWebSocket() {
        if (this.websocket && this.websocket.readyState !== WebSocket.CLOSED) {
            this.websocket.close();
            console.log('WebSocket bağlantısı kapatıldı');
            
            if (this.options.onConnectionChange) {
                this.options.onConnectionChange(false);
            }
        }
    }
    
    /**
     * Tablodaki tüm coinlerin sembollerini alır
     */
    getTrackedCoins() {
        if (!this.table) return [];
        
        const rows = this.table.querySelectorAll('tbody tr');
        const coins = [];
        
        rows.forEach(row => {
            const symbol = row.dataset.symbol;
            if (symbol) {
                coins.push(symbol);
            }
        });
        
        return coins;
    }
    
    /**
     * AJAX ile tüm coin verilerini yeniler
     */
    refreshData() {
        if (!this.table) return;
        
        // Yükleniyor göstergesini göster
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'flex';
        }
        
        // İzlenen coinleri al
        let coins = [];
        
        // Özel semboller varsa onları kullan
        if (this.options.customSymbols && this.options.customSymbols.length > 0) {
            coins = this.options.customSymbols;
        } else {
            coins = this.getTrackedCoins();
        }
        
        if (coins.length === 0) {
            console.error("İzlenecek coin bulunamadı!");
            if (this.loadingOverlay) {
                this.loadingOverlay.style.display = 'none';
            }
            return;
        }
        
        // API isteği yap
        fetch(`api/get_binance_data.php?action=all_stats&symbols=${coins.join(',')}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fiyat verilerini güncelle
                    this.updateAllCoinPrices(data.data);
                    
                    // Son güncelleme zamanını göster
                    if (this.lastUpdateTimeElement) {
                        const now = new Date();
                        const timeStr = now.toTimeString().split(' ')[0];
                        this.lastUpdateTimeElement.textContent = timeStr;
                    }
                } else {
                    console.error('API hatası:', data.error);
                }
            })
            .catch(error => {
                console.error('Veri yenileme hatası:', error);
            })
            .finally(() => {
                // Yükleniyor göstergesini gizle
                if (this.loadingOverlay) {
                    this.loadingOverlay.style.display = 'none';
                }
            });
    }
    
    /**
     * WebSocket üzerinden gelen tek bir coin verisini günceller
     */
    updateCoinFromWebSocket(tickerData) {
        // Gelen veriyi işle
        const symbol = tickerData.s; // symbol
        if (!symbol) return;
        
        // İlgili satırı bul
        const row = this.table.querySelector(`tr[data-symbol="${symbol}"]`);
        if (!row) return;
        
        // Önceki fiyatı kaydet
        const priceCell = row.querySelector('.price-cell');
        const prevPrice = parseFloat(priceCell.dataset.price || '0');
        
        // Yeni fiyat
        const newPrice = parseFloat(tickerData.c || '0');
        
        // Fiyatı güncelle
        priceCell.textContent = this.formatPrice(newPrice);
        priceCell.dataset.price = newPrice.toString();
        
        // Fiyat değişimi için animasyon uygula
        if (prevPrice > 0 && prevPrice !== newPrice) {
            priceCell.classList.remove('price-up', 'price-down');
            void priceCell.offsetWidth; // CSS animasyonunu yeniden başlatmak için
            
            // Artış/azalış sınıfını ekle
            if (newPrice > prevPrice) {
                priceCell.classList.add('price-up');
            } else if (newPrice < prevPrice) {
                priceCell.classList.add('price-down');
            }
        }
        
        // Diğer hücreleri güncelle
        const changeCell = row.querySelector('.change-cell');
        if (changeCell) {
            const changePercent = parseFloat(tickerData.P || '0');
            changeCell.textContent = changePercent.toFixed(2) + '%';
            
            if (changePercent > 0) {
                changeCell.classList.add('text-success');
                changeCell.classList.remove('text-danger');
                changeCell.innerHTML = '<i class="fas fa-caret-up"></i> ' + changePercent.toFixed(2) + '%';
            } else if (changePercent < 0) {
                changeCell.classList.add('text-danger');
                changeCell.classList.remove('text-success');
                changeCell.innerHTML = '<i class="fas fa-caret-down"></i> ' + changePercent.toFixed(2) + '%';
            } else {
                changeCell.classList.remove('text-success', 'text-danger');
                changeCell.textContent = '0.00%';
            }
        }
        
        // Son güncelleme zamanını göster
        if (this.lastUpdateTimeElement) {
            const now = new Date();
            const timeStr = now.toTimeString().split(' ')[0];
            this.lastUpdateTimeElement.textContent = timeStr;
        }
    }
    
    /**
     * AJAX ile gelen tüm verileri tabloya uygular
     */
    updateAllCoinPrices(data) {
        if (!data || !this.table) return;
        
        // Tüm kayıtları dön
        Object.keys(data).forEach(symbol => {
            const coinData = data[symbol];
            if (!coinData) return;
            
            // İlgili satırı bul
            const row = this.table.querySelector(`tr[data-symbol="${symbol}"]`);
            if (!row) return;
            
            // Önceki fiyatı kaydet (animasyon için)
            const priceCell = row.querySelector('.price-cell');
            const prevPrice = parseFloat(priceCell.dataset.price || '0');
            
            // Yeni fiyat
            const newPrice = parseFloat(coinData.lastPrice || '0');
            
            // Fiyatı güncelle
            priceCell.textContent = this.formatPrice(newPrice);
            priceCell.dataset.price = newPrice.toString();
            
            // Fiyat değişimi için animasyon uygula
            if (prevPrice > 0 && prevPrice !== newPrice) {
                priceCell.classList.remove('price-up', 'price-down');
                void priceCell.offsetWidth; // CSS animasyonunu yeniden başlatmak için
                
                // Artış/azalış sınıfını ekle
                if (newPrice > prevPrice) {
                    priceCell.classList.add('price-up');
                } else if (newPrice < prevPrice) {
                    priceCell.classList.add('price-down');
                }
            }
            
            // Değişim yüzdesi
            const changeCell = row.querySelector('.change-cell');
            if (changeCell) {
                const changePercent = parseFloat(coinData.priceChangePercent || '0');
                
                if (changePercent > 0) {
                    changeCell.classList.add('text-success');
                    changeCell.classList.remove('text-danger');
                    changeCell.innerHTML = '<i class="fas fa-caret-up"></i> ' + changePercent.toFixed(2) + '%';
                } else if (changePercent < 0) {
                    changeCell.classList.add('text-danger');
                    changeCell.classList.remove('text-success');
                    changeCell.innerHTML = '<i class="fas fa-caret-down"></i> ' + changePercent.toFixed(2) + '%';
                } else {
                    changeCell.classList.remove('text-success', 'text-danger');
                    changeCell.textContent = '0.00%';
                }
            }
            
            // Yüksek/düşük fiyatlar
            const highCell = row.querySelector('.high-cell');
            if (highCell) {
                highCell.textContent = this.formatPrice(parseFloat(coinData.highPrice || '0'));
            }
            
            const lowCell = row.querySelector('.low-cell');
            if (lowCell) {
                lowCell.textContent = this.formatPrice(parseFloat(coinData.lowPrice || '0'));
            }
            
            // Hacim
            const volumeCell = row.querySelector('.volume-cell');
            if (volumeCell) {
                // Hacmi biçimlendir (örn. 1,234,567.89)
                const volume = parseFloat(coinData.quoteVolume || '0');
                volumeCell.textContent = this.formatVolume(volume);
            }
        });
    }
    
    /**
     * Fiyatı formatlı olarak döndürür
     */
    formatPrice(price) {
        if (typeof price !== 'number') {
            price = parseFloat(price) || 0;
        }
        
        // Fiyatı biçimlendir (örn. 10,000.00)
        if (price >= 1000) {
            return price.toLocaleString('en-US', { 
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else if (price >= 1) {
            return price.toLocaleString('en-US', { 
                minimumFractionDigits: 2,
                maximumFractionDigits: 4
            });
        } else if (price >= 0.01) {
            return price.toLocaleString('en-US', { 
                minimumFractionDigits: 4,
                maximumFractionDigits: 6
            });
        } else {
            return price.toLocaleString('en-US', { 
                minimumFractionDigits: 6,
                maximumFractionDigits: 8
            });
        }
    }
    
    /**
     * Hacmi formatlı olarak döndürür
     */
    formatVolume(volume) {
        if (typeof volume !== 'number') {
            volume = parseFloat(volume) || 0;
        }
        
        // Büyük sayılar için kısaltma (M, B)
        if (volume >= 1000000000) { // Milyar
            return (volume / 1000000000).toFixed(2) + 'B';
        } else if (volume >= 1000000) { // Milyon
            return (volume / 1000000).toFixed(2) + 'M';
        } else if (volume >= 1000) { // Bin
            return (volume / 1000).toFixed(2) + 'K';
        } else {
            return volume.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}