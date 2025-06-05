import time
import logging
import ccxt
import pandas as pd
from datetime import datetime
import threading
import numpy as np
import mysql.connector

logger = logging.getLogger("discovery_manager")

class DiscoveryManager:
    def __init__(self, config, api_keys, exchange=None):
        self.config = config
        self.api_keys = api_keys
        self.exchange = exchange
        self.discovered_coins = []
        self.stop_event = threading.Event()
        self.discovery_thread = None
        
        if not exchange:
            self.init_exchange()
            
    def init_exchange(self):
        """Exchange bağlantısını başlat"""
        try:
            exchange_name = self.config.get('exchange', 'binance')
            exchange_class = getattr(ccxt, exchange_name)
            self.exchange = exchange_class({
                'apiKey': self.api_keys.get('api_key', ''),
                'secret': self.api_keys.get('api_secret', ''),
                'enableRateLimit': True
            })
            logger.info(f"{exchange_name} bağlantısı başlatıldı")
        except Exception as e:
            logger.error(f"Exchange başlatılamadı: {str(e)}")
            self.exchange = None

    def discover_potential_coins(self):
        """Potansiyel yüksek getirili coinleri otomatik olarak keşfeder"""
        try:
            logger.info("Yeni potansiyel coinler keşfediliyor...")
            
            if not self.exchange:
                self.init_exchange()
            
            # Exchange'den tüm marketleri al
            try:
                markets = self.exchange.load_markets()
                logger.info(f"Toplam {len(markets)} market bulundu.")
            except Exception as e:
                logger.error(f"Piyasa bilgileri alınırken hata: {str(e)}")
                return []
            
            # USDT çiftlerini filtrele
            base_currency = self.config.get('base_currency', 'USDT')
            usdt_pairs = [s for s in markets.keys() if s.endswith(f'/{base_currency}')]
            
            logger.info(f"{len(usdt_pairs)} adet {base_currency} çifti bulundu.")
            
            # Keşif için filtreleme kriterleri
            min_volume = float(self.config.get('auto_discovery', {}).get('min_volume_for_discovery', 50000))  # Minimum hacim 50k USDT
            min_price_change = float(self.config.get('auto_discovery', {}).get('min_price_change', 1.0))  # Minimum %1 fiyat değişimi
            
            logger.info(f"Filtreleme kriterleri: Min Hacim: ${min_volume:,.2f}, Min Fiyat Değişimi: %{min_price_change}")
            
            # Potansiyel coinleri sakla
            potential_coins = []
            
            # Her bir sembol için veri topla
            for symbol in usdt_pairs:
                try:
                    # Rate limit aşımını önlemek için kısa bekleme
                    time.sleep(self.config.get('api_delay', 0.2))
                    
                    # Ticker bilgisini al
                    ticker = self.exchange.fetch_ticker(symbol)
                    
                    if not ticker:
                        continue
                    
                    # Fiyat değişimi ve hacim hesapla
                    last_price = ticker.get('last', 0)
                    volume_usd = ticker.get('quoteVolume', 0) or (ticker.get('volume', 0) * last_price)
                    price_change_pct = ticker.get('percentage', 0)
                    
                    if isinstance(price_change_pct, float):
                        price_change_pct = price_change_pct * 100 if abs(price_change_pct) < 100 else price_change_pct
                    
                    # Filtreleme kriterleri
                    if volume_usd > min_volume or abs(price_change_pct) > min_price_change:
                        potential_coin = {
                            'symbol': symbol,
                            'last_price': last_price,
                            'volume_usd': volume_usd,
                            'price_change_pct': price_change_pct,
                            'discovery_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                        }
                        
                        # OHLCV verileri al
                        try:
                            ohlcv = self.exchange.fetch_ohlcv(symbol, '1h', limit=24)
                            if ohlcv and len(ohlcv) > 0:
                                # Son 24 saatlik verileri analiz et
                                closes = [x[4] for x in ohlcv]
                                highs = [x[2] for x in ohlcv]
                                lows = [x[3] for x in ohlcv]
                                
                                # Volatilite hesapla
                                volatility = (max(highs) - min(lows)) / min(lows) * 100
                                potential_coin['volatility'] = volatility
                                
                                # Momentum hesapla
                                momentum = (closes[-1] - closes[0]) / closes[0] * 100
                                potential_coin['momentum'] = momentum
                                
                        except Exception as ohlcv_error:
                            logger.error(f"{symbol} OHLCV verisi alınırken hata: {str(ohlcv_error)}")
                        
                        potential_coins.append(potential_coin)
                        logger.info(f"Potansiyel coin bulundu: {symbol} (${volume_usd:,.2f} hacim, %{price_change_pct:.2f} değişim)")
                    
                except Exception as e:
                    logger.error(f"{symbol} analiz edilirken hata: {str(e)}")
                    continue
            
            # Coinleri potansiyel skoruna göre sırala
            def calculate_potential_score(coin):
                volume_score = min(coin['volume_usd'] / min_volume, 10)  # Maksimum 10 puan
                change_score = abs(coin['price_change_pct']) / 2  # Her %2 için 1 puan
                volatility_score = coin.get('volatility', 0) / 5  # Her %5 için 1 puan
                momentum_score = coin.get('momentum', 0) / 2  # Her %2 için 1 puan
                return volume_score + change_score + volatility_score + momentum_score
            
            # Potansiyel skoruna göre sırala
            potential_coins.sort(key=calculate_potential_score, reverse=True)
            
            # En iyi coinleri seç
            max_coins = int(self.config.get('auto_discovery', {}).get('max_coins_to_discover', 10))
            top_potential_coins = potential_coins[:max_coins]
            
            logger.info(f"Toplam {len(potential_coins)} coin arasından en iyi {len(top_potential_coins)} tanesi seçildi.")
            
            # Keşfedilen coinleri güncelle ve kaydet
            self.discovered_coins = top_potential_coins
            self.save_discovered_coins_to_db()
            
            return top_potential_coins
            
        except Exception as e:
            logger.error(f"Coin keşfetme sırasında hata: {str(e)}")
            return []

    def save_discovered_coins_to_db(self):
        """Keşfedilmiş coinleri veritabanına kaydeder"""
        try:
            if not self.discovered_coins:
                logger.info("Kaydedilecek keşfedilmiş coin yok.")
                return
                
            # Veritabanı bağlantısı
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()

            # discovered_coins tablosunun var olup olmadığını kontrol et
            try:
                cursor.execute("SELECT 1 FROM discovered_coins LIMIT 1")
                cursor.fetchall()  # Sonucu temizle
            except Exception as e:
                # Tablo yoksa oluştur
                create_table_query = """
                CREATE TABLE IF NOT EXISTS discovered_coins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(20) NOT NULL,
                    discovery_time TIMESTAMP,
                    price DECIMAL(20, 8),
                    volume_usd DECIMAL(20, 2),
                    price_change_pct DECIMAL(10, 2),
                    volatility DECIMAL(10, 2),
                    momentum DECIMAL(10, 2),
                    is_active TINYINT(1) DEFAULT 1,
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                """
                cursor.execute(create_table_query)
                conn.commit()
                logger.info("discovered_coins tablosu oluşturuldu")

            # Her bir coini kaydet
            coins_saved = 0
            for coin in self.discovered_coins:
                try:
                    symbol = coin['symbol']
                    discovery_time = coin.get('discovery_time', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
                    
                    # Coin zaten var mı kontrol et
                    check_query = "SELECT id FROM discovered_coins WHERE symbol = %s"
                    cursor.execute(check_query, (symbol,))
                    existing = cursor.fetchone()
                    
                    if existing:
                        # Güncelle
                        update_query = """
                        UPDATE discovered_coins SET 
                            discovery_time = %s,
                            price = %s,
                            volume_usd = %s,
                            price_change_pct = %s,
                            volatility = %s,
                            momentum = %s,
                            is_active = 1,
                            last_updated = NOW()
                        WHERE symbol = %s
                        """
                        cursor.execute(update_query, (
                            discovery_time,
                            float(coin['last_price']),
                            float(coin['volume_usd']),
                            float(coin['price_change_pct']),
                            float(coin.get('volatility', 0)),
                            float(coin.get('momentum', 0)),
                            symbol
                        ))
                    else:
                        # Yeni ekle
                        insert_query = """
                        INSERT INTO discovered_coins (
                            symbol, discovery_time, price, volume_usd, 
                            price_change_pct, volatility, momentum, is_active
                        ) VALUES (%s, %s, %s, %s, %s, %s, %s, 1)
                        """
                        cursor.execute(insert_query, (
                            symbol,
                            discovery_time,
                            float(coin['last_price']),
                            float(coin['volume_usd']),
                            float(coin['price_change_pct']),
                            float(coin.get('volatility', 0)),
                            float(coin.get('momentum', 0))
                        ))
                    
                    conn.commit()
                    coins_saved += 1
                    logger.debug(f"{symbol} coini başarıyla veritabanına kaydedildi")
                    
                except Exception as coin_error:
                    logger.error(f"Coin işlenirken hata ({symbol}): {str(coin_error)}")
                    continue
                    
            logger.info(f"{coins_saved} adet keşfedilmiş coin veritabanına kaydedildi.")
            
        except Exception as e:
            logger.error(f"Keşfedilen coinler veritabanına kaydedilirken hata: {str(e)}")
            
        finally:
            try:
                cursor.close()
                conn.close()
            except:
                pass

    def start_discovery(self):
        """Keşif sistemini başlatır"""
        def discovery_loop():
            logger.info("Otomatik coin keşfetme sistemi başlatıldı...")
            last_settings_refresh = 0
            
            while not self.stop_event.is_set():
                try:
                    # Keşif aralığını al
                    discovery_interval = self.config.get('auto_discovery', {}).get('discovery_interval', 3600)
                    
                    # Potansiyel coinleri keşfet
                    discovered = self.discover_potential_coins()
                    logger.info(f"{len(discovered)} adet yüksek potansiyelli coin keşfedildi.")
                    
                    # Bir sonraki keşfe kadar bekle
                    logger.info(f"Bir sonraki coin keşfine kadar {discovery_interval/60:.1f} dakika bekleniyor...")
                    
                    # Bot durma kontrolü için tarama aralığını parçalara böl
                    for _ in range(max(1, int(discovery_interval / 60))):
                        if self.stop_event.is_set():
                            break
                        time.sleep(60)
                        
                except Exception as e:
                    logger.error(f"Coin keşfetme döngüsünde hata: {str(e)}")
                    time.sleep(60)
            
        self.discovery_thread = threading.Thread(target=discovery_loop)
        self.discovery_thread.start()

    def stop_discovery(self):
        """Keşif sistemini durdurur"""
        self.stop_event.set()
        if self.discovery_thread:
            self.discovery_thread.join()
        logger.info("Keşif sistemi durduruldu")