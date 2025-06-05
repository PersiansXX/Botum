import websocket
import json
import threading
import logging
import time
import random
import ssl
import requests
from datetime import datetime, timedelta

logger = logging.getLogger("trading_bot")

class WebSocketManager:
    def __init__(self):
        """
        Binance WebSocket veri akışlarını yöneten sınıf
        """
        self.connections = {}
        self.symbol_data = {}
        self.last_update = {}
        self.running = True
        self.callbacks = {}
        self.reconnect_attempts = {}
        self.max_reconnect_delay = 300  # saniye (5 dakika)
        self.connection_status = {}
        self.stream_types = {
            'ticker': '@ticker',        # Fiyat özeti (24s değişim vb.)
            'kline': '@kline_',         # Mum verileri (OHLCV)
            'depth': '@depth',          # Emir defteri (Order book)
            'trades': '@aggTrade',      # İşlemler
            'bookTicker': '@bookTicker' # En iyi alış/satış fiyatı
        }
        self.kline_intervals = ['1m', '3m', '5m', '15m', '30m', '1h', '2h', '4h', '6h', '8h', '12h', '1d', '3d', '1w', '1M']
        self.rate_limit_windows = {
            'per_second': {
                'limit': 10,
                'count': 0,
                'last_reset': time.time(),
                'waiting': False
            },
            'per_minute': {
                'limit': 600,
                'count': 0,
                'last_reset': time.time(),
                'waiting': False
            }
        }
        self.last_ping_time = {}
        self.ping_interval = 30  # saniye
        # Sağlık kontrolcüsünü başlat
        self._start_health_monitor()
        
    def _start_health_monitor(self):
        """
        WebSocket bağlantıları için sağlık kontrolü yapan bir timer başlatır
        """
        self.health_thread = threading.Thread(target=self._health_monitor_loop)
        self.health_thread.daemon = True
        self.health_thread.start()
        logger.info("WebSocket sağlık kontrolcüsü başlatıldı")
        
    def _health_monitor_loop(self):
        """
        Periyodik olarak tüm bağlantıların sağlığını kontrol eden döngü
        """
        while self.running:
            try:
                self.check_connection_health()
                self._reset_rate_limits()
                time.sleep(10)  # Her 10 saniyede bir kontrol et
            except Exception as e:
                logger.error(f"WebSocket sağlık kontrolü sırasında hata: {str(e)}")
                time.sleep(30)  # Hata durumunda 30 saniye bekle ve tekrar dene
    
    def _reset_rate_limits(self):
        """
        Rate limit sayaçlarını periyodik olarak sıfırlar
        """
        current_time = time.time()
        # Saniye başına limit
        if current_time - self.rate_limit_windows['per_second']['last_reset'] >= 1:
            self.rate_limit_windows['per_second']['count'] = 0
            self.rate_limit_windows['per_second']['last_reset'] = current_time
            self.rate_limit_windows['per_second']['waiting'] = False
            
        # Dakika başına limit
        if current_time - self.rate_limit_windows['per_minute']['last_reset'] >= 60:
            self.rate_limit_windows['per_minute']['count'] = 0
            self.rate_limit_windows['per_minute']['last_reset'] = current_time
            self.rate_limit_windows['per_minute']['waiting'] = False
            
    def _check_rate_limit(self):
        """
        Rate limit'i kontrol eder ve aşılırsa bekler
        
        :return: True (devam edebilir) veya False (rate limit aşıldı)
        """
        if self.rate_limit_windows['per_second']['count'] >= self.rate_limit_windows['per_second']['limit']:
            self.rate_limit_windows['per_second']['waiting'] = True
            # İşlemi biraz geciktir
            logger.debug("Saniye başına rate limit'e ulaşıldı, bekliyor...")
            time.sleep(1.1)  # 1.1 saniye bekle
            self.rate_limit_windows['per_second']['count'] = 0
            self.rate_limit_windows['per_second']['waiting'] = False
            
        if self.rate_limit_windows['per_minute']['count'] >= self.rate_limit_windows['per_minute']['limit']:
            self.rate_limit_windows['per_minute']['waiting'] = True
            wait_time = 60 - (time.time() - self.rate_limit_windows['per_minute']['last_reset'])
            if wait_time > 0:
                logger.warning(f"Dakika başına rate limit'e ulaşıldı, {wait_time:.1f} saniye bekliyor...")
                time.sleep(wait_time)
            self.rate_limit_windows['per_minute']['count'] = 0
            self.rate_limit_windows['per_minute']['waiting'] = False
            
        # Rate limit sayaçlarını artır
        self.rate_limit_windows['per_second']['count'] += 1
        self.rate_limit_windows['per_minute']['count'] += 1
        
        return True
        
    def _get_conn_key(self, symbol, stream_type, interval=None):
        """
        Bağlantı anahtar değerini oluşturur
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :param stream_type: Veri akış türü (ticker, kline, depth, trades)
        :param interval: Kline için zaman dilimi (1m, 5m, 15m...)
        :return: Bağlantı anahtarı
        """
        formatted_symbol = symbol.replace('/', '').lower()
        if stream_type == 'kline' and interval:
            return f"{formatted_symbol}_{stream_type}_{interval}"
        else:
            return f"{formatted_symbol}_{stream_type}"
            
    def start_symbol_socket(self, symbol, stream_type='ticker', interval='1m', depth_level=20, callback=None):
        """
        Bir sembol için WebSocket bağlantısı başlatır
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :param stream_type: Veri akış türü (ticker, kline, depth, trades, bookTicker)
        :param interval: Kline için zaman dilimi (1m, 5m, 15m...)
        :param depth_level: Emir defteri derinliği (5, 10, 20)
        :param callback: Bu akış için özel callback fonksiyonu
        :return: Bağlantı ID'si veya None (hata durumunda)
        """
        try:
            # Rate limit kontrolü
            if not self._check_rate_limit():
                logger.warning(f"Rate limit aşıldığı için {symbol} bağlantısı ertelendi")
                return None
            
            # Sembol formatını Binance formatına dönüştür (BTC/USDT -> btcusdt)
            formatted_symbol = symbol.replace('/', '').lower()
            
            # Stream tipi geçerli mi kontrol et
            if stream_type not in self.stream_types:
                logger.error(f"Geçersiz stream tipi: {stream_type}. Geçerli tipler: {', '.join(self.stream_types.keys())}")
                return None
            
            # Kline için interval kontrolü
            if stream_type == 'kline' and interval not in self.kline_intervals:
                logger.error(f"Geçersiz kline aralığı: {interval}. Geçerli aralıklar: {', '.join(self.kline_intervals)}")
                return None
                
            # Bağlantı anahtarını oluştur
            conn_key = self._get_conn_key(symbol, stream_type, interval)
            
            # Zaten bağlantı varsa, yeniden başlatma
            if conn_key in self.connections and self.connections[conn_key]:
                logger.debug(f"{symbol} için {stream_type} WebSocket bağlantısı zaten var")
                # Callback güncellemesi
                if callback:
                    self.callbacks[conn_key] = callback
                return conn_key
                
            # WebSocket URL'i oluştur
            stream = self.stream_types[stream_type]
            if stream_type == 'kline':
                stream = f"{stream}{interval}"
            elif stream_type == 'depth':
                stream = f"{stream}{depth_level}"
                
            socket_url = f"wss://stream.binance.com:9443/ws/{formatted_symbol}{stream}"
            
            # WebSocket bağlantısını oluştur
            ws = websocket.WebSocketApp(
                socket_url,
                on_message=lambda ws, msg: self._on_message(ws, msg, symbol, stream_type, interval),
                on_error=lambda ws, err: self._on_error(ws, err, symbol, stream_type, interval),
                on_close=lambda ws, close_status_code, close_msg: self._on_close(ws, close_status_code, close_msg, symbol, stream_type, interval),
                on_open=lambda ws: self._on_open(ws, symbol, stream_type, interval)
            )
            
            # Bağlantıyı başlat
            wst = threading.Thread(target=ws.run_forever, kwargs={
                'sslopt': {"cert_reqs": ssl.CERT_NONE},
                'ping_interval': self.ping_interval,
                'ping_timeout': 10
            })
            wst.daemon = True
            wst.start()
            
            # Bağlantıyı kaydet
            self.connections[conn_key] = {
                'ws': ws,
                'thread': wst,
                'symbol': symbol,
                'stream_type': stream_type,
                'interval': interval if stream_type == 'kline' else None,
                'start_time': time.time(),
                'reconnect_count': 0,
                'status': 'connecting'
            }
            
            # Yeniden bağlanma sayacını sıfırla
            self.reconnect_attempts[conn_key] = 0
            
            # Özel callback varsa kaydet
            if callback:
                self.callbacks[conn_key] = callback
                
            logger.info(f"{symbol} için {stream_type} WebSocket bağlantısı başlatıldı" + 
                       (f" ({interval} aralığında)" if stream_type == 'kline' else ""))
            
            return conn_key
            
        except Exception as e:
            logger.error(f"{symbol} için {stream_type} WebSocket bağlantısı başlatılırken hata: {str(e)}")
            return None
    
    def stop_symbol_socket(self, symbol_or_conn_key, stream_type=None, interval=None):
        """
        Sembol için WebSocket bağlantısını durdurur
        
        :param symbol_or_conn_key: Coin sembolü (örn. BTC/USDT) veya bağlantı anahtarı
        :param stream_type: Veri akış türü (ticker, kline, depth, trades)
        :param interval: Kline için zaman dilimi (1m, 5m, 15m...)
        :return: Başarı durumu
        """
        try:
            # Bağlantı anahtarını belirle
            if stream_type:
                # Sembol ve akış tipi verilmiş, anahtarı hesapla
                conn_key = self._get_conn_key(symbol_or_conn_key, stream_type, interval)
            else:
                # Doğrudan bağlantı anahtarı verilmiş
                conn_key = symbol_or_conn_key
                
            # Bağlantı var mı kontrol et
            if conn_key in self.connections and self.connections[conn_key]:
                # Bağlantı bilgilerini al
                connection = self.connections[conn_key]
                symbol = connection['symbol']
                stream_type = connection['stream_type']
                interval_info = f" ({connection['interval']})" if connection['interval'] else ""
                
                # WebSocket'i kapat
                connection['ws'].close()
                
                # Son durumu kaydet
                connection['status'] = 'closed'
                
                logger.info(f"{symbol} için {stream_type}{interval_info} WebSocket bağlantısı kapatıldı")
                return True
            else:
                logger.warning(f"Kapatılacak {conn_key} bağlantısı bulunamadı")
                return False
                
        except Exception as e:
            logger.error(f"WebSocket bağlantısı kapatılırken hata: {str(e)}")
            return False
    
    def _on_message(self, ws, message, symbol, stream_type, interval=None):
        """
        WebSocket mesajı alındığında çağrılır
        
        :param ws: WebSocket nesnesi
        :param message: Gelen mesaj
        :param symbol: İlgili sembol
        :param stream_type: Veri akış türü
        :param interval: Kline için zaman dilimi
        """
        try:
            data = json.loads(message)
            conn_key = self._get_conn_key(symbol, stream_type, interval)
            
            # Bağlantı durumunu güncelle
            if conn_key in self.connections:
                self.connections[conn_key]['status'] = 'connected'
                self.reconnect_attempts[conn_key] = 0  # Başarılı bağlantı, sayacı sıfırla
            
            # Son güncelleme zamanını kaydet
            self.last_update[conn_key] = time.time()
            
            # Veri akış türüne göre işleme
            if stream_type == 'ticker':
                processed_data = {
                    'price': float(data['c']),
                    'high': float(data['h']),
                    'low': float(data['l']),
                    'volume': float(data['v']),
                    'quote_volume': float(data['q']),
                    'timestamp': int(data['E']),
                    'change_percent': float(data['P'])
                }
            elif stream_type == 'kline':
                k = data['k']
                processed_data = {
                    'open_time': int(k['t']),
                    'close_time': int(k['T']),
                    'interval': k['i'],
                    'open': float(k['o']),
                    'high': float(k['h']),
                    'low': float(k['l']),
                    'close': float(k['c']),
                    'volume': float(k['v']),
                    'quote_volume': float(k['q']),
                    'is_closed': k['x'],
                    'trades': int(k['n']),
                    'timestamp': int(data['E'])
                }
            elif stream_type == 'depth':
                processed_data = {
                    'bids': [[float(price), float(qty)] for price, qty in data['b']],
                    'asks': [[float(price), float(qty)] for price, qty in data['a']],
                    'timestamp': int(data['E'])
                }
            elif stream_type == 'trades':
                processed_data = {
                    'price': float(data['p']),
                    'quantity': float(data['q']),
                    'time': int(data['T']),
                    'is_buyer_maker': data['m'],
                    'timestamp': int(data['E'])
                }
            elif stream_type == 'bookTicker':
                processed_data = {
                    'bid_price': float(data['b']),
                    'bid_qty': float(data['B']),
                    'ask_price': float(data['a']),
                    'ask_qty': float(data['A']),
                    'timestamp': int(data['E'])
                }
            else:
                # Bilinmeyen akış türü, işlenmemiş veriyi kullan
                processed_data = data
            
            # Veriyi depola
            if symbol not in self.symbol_data:
                self.symbol_data[symbol] = {}
            self.symbol_data[symbol][stream_type] = processed_data
            
            # Akış için özel callback varsa çağır
            if conn_key in self.callbacks and self.callbacks[conn_key]:
                self.callbacks[conn_key](symbol, processed_data, stream_type, interval)
                
        except Exception as e:
            logger.error(f"{symbol} için {stream_type} WebSocket mesajı işlenirken hata: {str(e)}")
    
    def _on_error(self, ws, error, symbol, stream_type, interval=None):
        """
        WebSocket hatası alındığında çağrılır
        """
        conn_key = self._get_conn_key(symbol, stream_type, interval)
        
        # Bağlantı durumunu güncelle
        if conn_key in self.connections:
            self.connections[conn_key]['status'] = 'error'
            
        interval_info = f" ({interval})" if interval else ""
        logger.error(f"{symbol} için {stream_type}{interval_info} WebSocket hatası: {str(error)}")
    
    def _on_close(self, ws, close_status_code, close_msg, symbol, stream_type, interval=None):
        """
        WebSocket bağlantısı kapatıldığında çağrılır
        """
        conn_key = self._get_conn_key(symbol, stream_type, interval)
        
        # Bağlantı durumunu güncelle
        if conn_key in self.connections:
            self.connections[conn_key]['status'] = 'closed'
            
        interval_info = f" ({interval})" if interval else ""
        logger.info(f"{symbol} için {stream_type}{interval_info} WebSocket bağlantısı kapandı. Kod: {close_status_code}, Mesaj: {close_msg}")
        
        # Otomatik yeniden bağlan (eğer hala çalışıyorsa)
        if self.running and conn_key in self.connections:
            # Exponential backoff ile yeniden bağlanma zamanını hesapla
            if conn_key not in self.reconnect_attempts:
                self.reconnect_attempts[conn_key] = 0
                
            self.reconnect_attempts[conn_key] += 1
            retry_count = self.reconnect_attempts[conn_key]
            
            # Exponential backoff: 2^n ile sınırlı rastgele bekleme süresi
            retry_delay = min(2 ** retry_count + random.uniform(0, 1), self.max_reconnect_delay)
            
            logger.info(f"{symbol} için {stream_type}{interval_info} WebSocket bağlantısı {retry_delay:.1f} saniye içinde yeniden başlatılacak (deneme #{retry_count})")
            
            # Bağlantıyı yeniden başlat
            time.sleep(retry_delay)
            try:
                self.start_symbol_socket(
                    symbol, 
                    stream_type, 
                    interval=interval if stream_type == 'kline' else '1m',
                    callback=self.callbacks.get(conn_key)
                )
            except Exception as e:
                logger.error(f"Yeniden bağlanma başarısız: {str(e)}")
    
    def _on_open(self, ws, symbol, stream_type, interval=None):
        """
        WebSocket bağlantısı açıldığında çağrılır
        """
        conn_key = self._get_conn_key(symbol, stream_type, interval)
        
        # Bağlantı durumunu güncelle
        if conn_key in self.connections:
            self.connections[conn_key]['status'] = 'connected'
            
        interval_info = f" ({interval})" if interval else ""
        logger.info(f"{symbol} için {stream_type}{interval_info} WebSocket bağlantısı açıldı")
        
        # Son ping zamanını kaydet
        self.last_ping_time[conn_key] = time.time()
    
    def get_ticker(self, symbol):
        """
        Sembol için en son ticker verisini döndürür
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :return: Ticker verisi veya None
        """
        if symbol in self.symbol_data and 'ticker' in self.symbol_data[symbol]:
            return self.symbol_data[symbol]['ticker']
        return None
        
    def get_kline(self, symbol, interval='1m'):
        """
        Sembol için en son kline (mum) verisini döndürür
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :param interval: Zaman dilimi (1m, 5m, 15m...)
        :return: Kline verisi veya None
        """
        if symbol in self.symbol_data and 'kline' in self.symbol_data[symbol]:
            return self.symbol_data[symbol]['kline']
        return None
        
    def get_depth(self, symbol):
        """
        Sembol için en son emir defteri (order book) verisini döndürür
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :return: Depth verisi veya None
        """
        if symbol in self.symbol_data and 'depth' in self.symbol_data[symbol]:
            return self.symbol_data[symbol]['depth']
        return None
        
    def get_trades(self, symbol):
        """
        Sembol için en son işlem verisini döndürür
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :return: İşlem verisi veya None
        """
        if symbol in self.symbol_data and 'trades' in self.symbol_data[symbol]:
            return self.symbol_data[symbol]['trades']
        return None
        
    def get_book_ticker(self, symbol):
        """
        Sembol için en iyi alış/satış fiyatını döndürür
        
        :param symbol: Coin sembolü (örn. BTC/USDT)
        :return: BookTicker verisi veya None
        """
        if symbol in self.symbol_data and 'bookTicker' in self.symbol_data[symbol]:
            return self.symbol_data[symbol]['bookTicker']
        return None
    
    def stop_all(self):
        """
        Tüm WebSocket bağlantılarını kapatır
        """
        self.running = False
        for conn_key in list(self.connections.keys()):
            try:
                if conn_key in self.connections:
                    connection = self.connections[conn_key]
                    connection['ws'].close()
                    connection['status'] = 'closed'
            except Exception as e:
                logger.error(f"{conn_key} bağlantısı kapatılırken hata: {str(e)}")
                
        self.connections = {}
        logger.info("Tüm WebSocket bağlantıları kapatıldı")
    
    def set_callback(self, callback_func, symbol=None, stream_type=None, interval=None):
        """
        Belirli bir veri akışı için veya genel geri çağırma fonksiyonunu ayarlar
        
        :param callback_func: Geri çağırma fonksiyonu (symbol, data, stream_type, interval)
        :param symbol: Belirli bir sembol için (None ise tümü)
        :param stream_type: Belirli bir akış türü için (None ise tümü)
        :param interval: Kline için belirli bir zaman dilimi (None ise tümü)
        """
        if symbol and stream_type:
            # Belirli bir akış için callback'i ayarla
            conn_key = self._get_conn_key(symbol, stream_type, interval)
            self.callbacks[conn_key] = callback_func
        else:
            # Genel callback fonksiyonu - Artık desteklenmiyor
            logger.warning("Genel callback fonksiyonu desteklenmiyor. Lütfen her akış için ayrı callback belirtin.")

    def check_connection_health(self):
        """
        Tüm WebSocket bağlantılarının sağlığını kontrol eder ve
        sorun varsa yeniden bağlanır
        
        :return: Sağlıklı bağlantı sayısı
        """
        healthy_connections = 0
        total_connections = 0
        current_time = time.time()
        
        for conn_key, conn_info in list(self.connections.items()):
            total_connections += 1
            
            # Bağlantı durumu 'connected' değilse ve 30 saniyeden fazla süre geçtiyse
            time_since_start = current_time - conn_info.get('start_time', 0)
            if conn_info['status'] != 'connected' and time_since_start > 30:
                logger.warning(f"{conn_info['symbol']} için {conn_info['stream_type']} bağlantısı hala kurulmadı, yeniden başlatılıyor...")
                try:
                    self.stop_symbol_socket(conn_key)
                    time.sleep(1)
                    # Yeniden başlat
                    self.start_symbol_socket(
                        conn_info['symbol'],
                        conn_info['stream_type'],
                        interval=conn_info['interval'] if conn_info['interval'] else '1m',
                        callback=self.callbacks.get(conn_key)
                    )
                except Exception as e:
                    logger.error(f"Bağlantı yeniden başlatılırken hata: {str(e)}")
                continue
                
            # Son veri alımını kontrol et
            last_update_time = self.last_update.get(conn_key, 0)
            if last_update_time > 0:
                time_since_update = current_time - last_update_time
                
                # Veri türüne göre kontrol et - ticker, kline ve bookTicker için farklı süreler
                expected_interval = 60  # varsayılan 60 saniye (1 dakika)
                
                if conn_info['stream_type'] == 'ticker':
                    expected_interval = 10  # ticker verisi 2-3 saniyede bir gelir, 10 saniye tolerans
                elif conn_info['stream_type'] == 'kline':
                    # kline verisi her mum kapanışında veya tick olduğunda gelir
                    if conn_info['interval'] == '1m':
                        expected_interval = 70  # 1 dakikalık mum için 70 saniye tolerans
                    elif conn_info['interval'] == '5m':
                        expected_interval = 310  # 5 dakikalık mum için 310 saniye tolerans
                    else:
                        expected_interval = 120  # diğer mumlar için 2 dakika
                elif conn_info['stream_type'] == 'bookTicker':
                    expected_interval = 5  # bookTicker sık güncellenir, 5 saniye tolerans
                elif conn_info['stream_type'] == 'depth':
                    expected_interval = 30  # depth için 30 saniye tolerans
                    
                # Eğer belirtilen süreden fazla güncelleme olmadıysa yeniden bağlan
                if time_since_update > expected_interval:
                    logger.warning(
                        f"{conn_info['symbol']} için {conn_info['stream_type']} bağlantısı "
                        f"son {time_since_update:.1f} saniyedir güncellenmedi (beklenen: {expected_interval}s), "
                        f"yeniden başlatılıyor..."
                    )
                    try:
                        self.stop_symbol_socket(conn_key)
                        time.sleep(1)
                        # Yeniden başlat
                        self.start_symbol_socket(
                            conn_info['symbol'],
                            conn_info['stream_type'],
                            interval=conn_info['interval'] if conn_info['interval'] else '1m',
                            callback=self.callbacks.get(conn_key)
                        )
                    except Exception as e:
                        logger.error(f"Bağlantı yeniden başlatılırken hata: {str(e)}")
                else:
                    healthy_connections += 1
            else:
                # Son güncelleme zamanı kaydedilmemiş, henüz veri alınmamış olabilir
                # Eğer bağlantı 60 saniyeden eski ama hiç veri gelmemişse, yeniden bağlan
                if time_since_start > 60:
                    logger.warning(f"{conn_info['symbol']} için {conn_info['stream_type']} bağlantısından hiç veri alınmadı, yeniden başlatılıyor...")
                    try:
                        self.stop_symbol_socket(conn_key)
                        time.sleep(1)
                        self.start_symbol_socket(
                            conn_info['symbol'],
                            conn_info['stream_type'],
                            interval=conn_info['interval'] if conn_info['interval'] else '1m',
                            callback=self.callbacks.get(conn_key)
                        )
                    except Exception as e:
                        logger.error(f"Bağlantı yeniden başlatılırken hata: {str(e)}")
                        
        health_percentage = (healthy_connections / total_connections * 100) if total_connections > 0 else 0
        logger.debug(f"WebSocket bağlantı sağlığı: %{health_percentage:.1f} ({healthy_connections}/{total_connections} bağlantı sağlıklı)")
        
        return healthy_connections
        
    def start_multiple_symbols(self, symbols, stream_types=None, interval='1m', callback=None):
        """
        Birden fazla sembol için aynı anda WebSocket bağlantısı başlatır
        
        :param symbols: Coin sembolleri listesi (örn. ['BTC/USDT', 'ETH/USDT'])
        :param stream_types: Stream tipleri listesi (None ise sadece ticker)
        :param interval: Kline için zaman dilimi
        :param callback: Callback fonksiyonu
        :return: Başarılı bağlantı sayısı
        """
        if stream_types is None:
            stream_types = ['ticker']
            
        success_count = 0
        for symbol in symbols:
            for stream_type in stream_types:
                if self.start_symbol_socket(symbol, stream_type, interval, callback=callback):
                    success_count += 1
                    time.sleep(0.1)  # Rate limit aşımını önlemek için kısa bekleme
        
        return success_count
        
    def get_connection_status(self):
        """
        Tüm bağlantıların durumunu döndürür
        
        :return: Bağlantı durumları sözlüğü
        """
        status = {
            'total': len(self.connections),
            'connected': 0,
            'error': 0,
            'closed': 0,
            'connecting': 0,
            'connections': []
        }
        
        for conn_key, conn_info in self.connections.items():
            conn_status = conn_info['status']
            status[conn_status] = status.get(conn_status, 0) + 1
            
            # Son güncelleme zamanını hesapla
            last_update_time = self.last_update.get(conn_key, 0)
            time_since_update = time.time() - last_update_time if last_update_time > 0 else None
            
            # Bağlantı bilgilerini ekle
            status['connections'].append({
                'symbol': conn_info['symbol'],
                'stream_type': conn_info['stream_type'],
                'interval': conn_info['interval'],
                'status': conn_status,
                'start_time': datetime.fromtimestamp(conn_info['start_time']).strftime('%Y-%m-%d %H:%M:%S'),
                'uptime': time.time() - conn_info['start_time'],
                'reconnect_count': conn_info.get('reconnect_count', 0),
                'last_update': datetime.fromtimestamp(last_update_time).strftime('%Y-%m-%d %H:%M:%S') if last_update_time > 0 else None,
                'time_since_update': f"{time_since_update:.1f}s" if time_since_update is not None else "N/A"
            })
        
        return status

    def get_rate_limit_status(self):
        """
        Rate limit durumunu döndürür
        
        :return: Rate limit durumu
        """
        return {
            'per_second': {
                'limit': self.rate_limit_windows['per_second']['limit'],
                'current': self.rate_limit_windows['per_second']['count'],
                'waiting': self.rate_limit_windows['per_second']['waiting']
            },
            'per_minute': {
                'limit': self.rate_limit_windows['per_minute']['limit'],
                'current': self.rate_limit_windows['per_minute']['count'],
                'waiting': self.rate_limit_windows['per_minute']['waiting']
            }
        }
        
    def list_active_sockets(self):
        """
        Aktif WebSocket bağlantılarını listeler
        
        :return: Aktif bağlantılar listesi
        """
        active_sockets = []
        for conn_key, conn_info in self.connections.items():
            if conn_info['status'] == 'connected':
                socket_info = {
                    'symbol': conn_info['symbol'],
                    'stream_type': conn_info['stream_type'],
                    'interval': conn_info['interval'],
                    'uptime': time.time() - conn_info['start_time']
                }
                active_sockets.append(socket_info)
        return active_sockets

# Singleton nesnesi
websocket_manager = WebSocketManager()