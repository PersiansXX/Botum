/**
 * Binance WebSocket API entegrasyonu
 * Bu script, Binance fiyat akışını gerçek zamanlı olarak takip eder
 */

class BinanceWebSocket {
    constructor() {
        this.socketUrl = "wss://stream.binance.com:9443/ws";
        this.socket = null;
        this.subscribedSymbols = {};
        this.callbacks = {};
        this.reconnectInterval = 5000; // 5 saniye sonra yeniden bağlan
        this.isConnected = false;
    }

    /**
     * WebSocket bağlantısını başlatır
     */
    init() {
        return new Promise((resolve, reject) => {
            try {
                console.log("Binance WebSocket bağlantısı kuruluyor...");
                this.socket = new WebSocket(this.socketUrl);
                
                this.socket.onopen = () => {
                    console.log("Binance WebSocket bağlantısı başarıyla kuruldu");
                    this.isConnected = true;
                    
                    // Daha önce abone olunmuş sembolleri tekrar abone et
                    const symbols = Object.keys(this.subscribedSymbols);
                    if (symbols.length > 0) {
                        this.subscribeSockets(symbols);
                    }
                    
                    resolve();
                };
                
                this.socket.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        
                        // Tick verisi (fiyat güncellemesi)
                        if (data.e === "trade") {
                            const symbol = data.s;
                            const price = parseFloat(data.p);
                            const time = data.T;
                            const quantity = parseFloat(data.q);
                            
                            // Kayıtlı callback fonksiyonlarını çağır
                            if (this.callbacks[symbol]) {
                                this.callbacks[symbol].forEach(callback => {
                                    callback({
                                        symbol: symbol,
                                        price: price,
                                        time: time,
                                        quantity: quantity
                                    });
                                });
                            }
                        }
                        
                        // 24 saatlik değişim verisi 
                        if (data.e === "24hrTicker") {
                            const symbol = data.s;
                            const priceChange = parseFloat(data.P); // Yüzde değişim
                            const lastPrice = parseFloat(data.c);
                            
                            // 24 saatlik değişim için kayıtlı callbackleri çağır
                            if (this.callbacks[symbol + "_ticker"]) {
                                this.callbacks[symbol + "_ticker"].forEach(callback => {
                                    callback({
                                        symbol: symbol,
                                        priceChange: priceChange,
                                        lastPrice: lastPrice
                                    });
                                });
                            }
                        }
                    } catch (err) {
                        console.error("WebSocket veri işleme hatası:", err);
                    }
                };
                
                this.socket.onclose = (event) => {
                    console.log("Binance WebSocket bağlantısı kapandı. Kod:", event.code, "Sebep:", event.reason);
                    this.isConnected = false;
                    
                    // Otomatik olarak yeniden bağlan
                    setTimeout(() => {
                        console.log("Binance WebSocket bağlantısı yeniden kuruluyor...");
                        this.init();
                    }, this.reconnectInterval);
                };
                
                this.socket.onerror = (error) => {
                    console.error("Binance WebSocket hatası:", error);
                    reject(error);
                };
                
            } catch (error) {
                console.error("Binance WebSocket bağlantısı kurulamadı:", error);
                reject(error);
            }
        });
    }
    
    /**
     * Belirli sembolleri WebSocket üzerinden abone ol
     * @param {Array} symbols - Takip edilecek sembollerin dizisi (örn. ['BTCUSDT', 'ETHUSDT'])
     */
    subscribeSockets(symbols = []) {
        if (!this.isConnected || !this.socket || this.socket.readyState !== WebSocket.OPEN) {
            console.log("WebSocket bağlantısı henüz kurulmadı. Bağlantı kurulduktan sonra abonelik tekrar denenecek.");
            // Sembolleri kaydedip, bağlantı açıldığında tekrar denerim
            symbols.forEach(symbol => {
                this.subscribedSymbols[symbol] = true;
            });
            return;
        }
        
        // Küçük harfle sembolleri normalize et
        const normalizedSymbols = symbols.map(symbol => {
            // BTCUSDT formatına dönüştür (BTCUSDT, BTC/USDT -> btcusdt)
            return symbol.replace('/', '').toLowerCase();
        });
        
        // Ticaret (trade) stream'lerine abone ol (fiyat güncellemesi)
        const tradeStreams = normalizedSymbols.map(symbol => `${symbol}@trade`);
        
        // 24 saatlik ticker stream'lerine abone ol (24 saatlik değişim için)
        const tickerStreams = normalizedSymbols.map(symbol => `${symbol}@ticker`);
        
        // Tüm stream'lere abone ol
        const streams = [...tradeStreams, ...tickerStreams];
        
        // Abonelik mesajı gönder
        const subscribeMsg = {
            method: "SUBSCRIBE",
            params: streams,
            id: new Date().getTime()
        };
        
        // Sembolleri kaydet
        symbols.forEach(symbol => {
            this.subscribedSymbols[symbol] = true;
        });
        
        // WebSocket'e gönder
        this.socket.send(JSON.stringify(subscribeMsg));
        console.log(`${symbols.join(', ')} sembolleri için WebSocket aboneliği başlatıldı.`);
    }
    
    /**
     * Bir sembol için fiyat güncellemelerine abone ol
     * @param {String} symbol - Sembol (örn. 'BTCUSDT')
     * @param {Function} callback - Fiyat güncellendiğinde çağrılacak fonksiyon
     */
    subscribeToTicker(symbol, callback) {
        // BTCUSDT formatına dönüştür
        const formattedSymbol = symbol.replace('/', '').toUpperCase();
        
        // Callback'i kaydet
        if (!this.callbacks[formattedSymbol]) {
            this.callbacks[formattedSymbol] = [];
        }
        this.callbacks[formattedSymbol].push(callback);
        
        // WebSocket'e abone ol
        this.subscribeSockets([formattedSymbol]);
        console.log(`${formattedSymbol} sembolü için fiyat güncellemesi takibi başlatıldı.`);
    }
    
    /**
     * Bir sembol için 24 saatlik değişime abone ol
     * @param {String} symbol - Sembol (örn. 'BTCUSDT')
     * @param {Function} callback - 24 saat değişimi güncellendiğinde çağrılacak fonksiyon
     */
    subscribeToTickerChange(symbol, callback) {
        // BTCUSDT formatına dönüştür
        const formattedSymbol = symbol.replace('/', '').toUpperCase();
        
        // Callback'i kaydet (ticker için özel bir key kullan)
        const callbackKey = formattedSymbol + "_ticker";
        if (!this.callbacks[callbackKey]) {
            this.callbacks[callbackKey] = [];
        }
        this.callbacks[callbackKey].push(callback);
        
        // WebSocket'e abone ol (zaten abone olunmuşsa tekrar abone olmaz)
        this.subscribeSockets([formattedSymbol]);
        console.log(`${formattedSymbol} sembolü için 24 saatlik değişim takibi başlatıldı.`);
    }
    
    /**
     * Bağlantıyı kapat
     */
    close() {
        if (this.socket && this.isConnected) {
            this.socket.close();
            this.isConnected = false;
            console.log("Binance WebSocket bağlantısı kapatıldı.");
        }
    }
}

// Global nesne olarak dışa aktar
window.BinanceWebSocket = new BinanceWebSocket();