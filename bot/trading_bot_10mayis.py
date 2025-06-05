import time
import json
import logging
import ccxt
import pandas as pd
import numpy as np
import threading
import os
import requests
import talib  # TA-Lib kütüphanesi direkt olarak import edildi
from datetime import datetime, timedelta
import telegram
import mysql.connector
from indicators import bollinger_bands, macd, rsi, moving_average
from strategies.short_term_strategy import analyze as short_term_strategy
from strategies.trend_following import analyze as trend_following
from strategies.breakout_detection import analyze as breakout_detection
# Yeni eklenen modüller
from db_manager import db_manager
from websocket_manager import websocket_manager

# Loglama yapılandırması dosyanın başka yerlerinde de yapıldığı için kaldırıldı
logger = logging.getLogger("trading_bot")

class TradingBot:
    def __init__(self):
        self.setup_logger()
        self.config = {}
        self.api_keys = {}
        self.open_positions = []
        self.trade_history = []
        self.stop_event = threading.Event()
        self.monitor_thread = None
        self.discovery_thread = None
        self.indicators_data = {}  # İndikatör hesaplama sonuçlarını önbelleğe alma
        self.trailing_stops = {}  # Trailing stop verileri
        self.discovered_coins = []  # Keşfedilmiş coinler
        self.active_coins = []  # Aktif olarak izlenen coinler
        self.use_tradingview = False  # TradingView verileri kullanılsın mı
        self.last_settings_check = 0  # Son ayar kontrolü zamanı

        # Konfigürasyon yükle
        self.load_config()
        
        # Veritabanından bot ayarlarını yükle
        self.load_settings_from_db()
        
        # API anahtarlarını yükle
        self.load_api_keys()
        
        # Açık pozisyonları yükle
        self.load_positions()
        
        # IndicatorsManager sınıfını başlat
        from indicators_manager import IndicatorsManager
        self.indicators_manager = IndicatorsManager(self.config)
        
        # CCXT exchange nesnesini oluştur
        try:
            exchange_name = self.config.get('exchange', 'binance')
            exchange_class = getattr(ccxt, exchange_name)
            self.exchange = exchange_class({
                'apiKey': self.api_keys.get('api_key', ''),
                'secret': self.api_keys.get('api_secret', ''),
                'enableRateLimit': True
            })
            logger.info(f"{exchange_name} borsası başlatıldı.")
        except Exception as e:
            logger.error(f"Exchange başlatılamadı: {str(e)}")
            self.exchange = None

    def setup_logger(self):
        """
        Logger ayarlarını yap - dosya yolları düzeltildi
        """
        # Log dosyasının tam yolu için script'in bulunduğu dizini kullan
        log_file_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bot.log")
        
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_file_path),
                logging.StreamHandler()
            ]
        )
        return logging.getLogger("trading_bot")

    def load_config(self):
        """
        MySQL veritabanından bot yapılandırmasını yükle - bot_settings tablosunu kullan
        """
        try:
            # Veritabanı bağlantısı oluştur
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor(dictionary=True)
            
            # Bot_settings tablosundan en son kaydı çek
            cursor.execute("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1")
            settings_data = cursor.fetchone()
            
            if settings_data:
                settings_json = None
                
                # Önce settings_json sütununu kontrol et
                if 'settings_json' in settings_data and settings_data['settings_json']:
                    try:
                        settings_json = json.loads(settings_data['settings_json'])
                        logger.info("Bot yapılandırması settings_json sütunundan yüklendi.")
                    except json.JSONDecodeError:
                        logger.error("settings_json sütunu JSON formatına dönüştürülemedi!")
                
                # Eğer settings_json yoksa settings sütununu dene
                if not settings_json and 'settings' in settings_data and settings_data['settings']:
                    try:
                        settings_json = json.loads(settings_data['settings'])
                        logger.info("Bot yapılandırması settings sütunundan yüklendi.")
                    except json.JSONDecodeError:
                        logger.error("settings sütunu JSON formatına dönüştürülemedi!")
                
                if settings_json:
                    # Tüm yapılandırma ayarlarını yükle
                    self.config = {
                        'exchange': settings_json.get('exchange', 'binance'),
                        'trade_mode': settings_json.get('trade_mode', 'paper'),
                        'base_currency': settings_json.get('base_currency', 'USDT'),
                        'trade_amount': float(settings_json.get('trade_amount', 10.0)),
                        'max_open_trades': int(settings_json.get('max_open_trades', 3)),
                        'stop_loss_pct': float(settings_json.get('stop_loss_pct', 2.0)),
                        'take_profit_pct': float(settings_json.get('take_profit_pct', 3.0)),
                        'use_telegram': bool(settings_json.get('use_telegram', False)),
                        'interval': settings_json.get('interval', '1h'),
                        'max_api_retries': int(settings_json.get('max_api_retries', 3)),
                        'retry_delay': int(settings_json.get('retry_delay', 5)),
                        'api_delay': float(settings_json.get('api_delay', 1.5)),
                        'scan_interval': int(settings_json.get('scan_interval', 60)),
                        'auto_trade': bool(settings_json.get('auto_trade', False)),
                        'use_tradingview': bool(settings_json.get('use_tradingview', False))
                    }
                    
                    # JSON formatındaki aktif coinler
                    if 'active_coins' in settings_json:
                        self.config['active_coins'] = settings_json['active_coins']
                    
                    # JSON formatındaki ticaret stratejileri
                    if 'trading_strategies' in settings_json:
                        self.config['trading_strategies'] = settings_json['trading_strategies']
                    
                    # JSON formatındaki indikatörler
                    if 'indicators_config' in settings_json:
                        self.config['indicators_config'] = settings_json['indicators_config']
                    elif 'indicators' in settings_json:  # Geriye dönük uyumluluk
                        self.config['indicators_config'] = settings_json['indicators']
                    
                    # Ek konfigürasyon alanları
                    for key, value in settings_json.items():
                        # Zaten işlediğimiz alanları atla
                        if key in ['exchange', 'trade_mode', 'base_currency', 'trade_amount', 
                                'max_open_trades', 'stop_loss_pct', 'take_profit_pct', 
                                'use_telegram', 'active_coins', 'trading_strategies', 
                                'indicators', 'indicators_config', 'interval', 'max_api_retries', 
                                'retry_delay', 'api_delay', 'scan_interval', 'auto_trade', 'use_tradingview']:
                            continue
                        
                        # Diğer tüm alanları da yapılandırmaya ekle
                        self.config[key] = value
                    
                    logger.info("Bot yapılandırması veritabanından başarıyla yüklendi.")
                    return self.config
                else:
                    logger.error("bot_settings tablosunda geçerli yapılandırma bulunamadı!")
                    # Varsayılan yapılandırma oluştur
                    self.create_default_config()
                    # Yapılandırmayı döndür
                    return self.config
            else:
                logger.error("bot_settings tablosunda kayıt bulunamadı!")
                # Varsayılan yapılandırma oluştur
                self.create_default_config()
                # Yapılandırmayı döndür
                return self.config
                
            cursor.close()
            conn.close()
                
        except Exception as e:
            logger.error(f"Bot yapılandırması yüklenirken hata: {str(e)}")
            # Varsayılan yapılandırma oluştur
            self.create_default_config()
            # Yapılandırmayı döndür
            return self.config

    def create_default_config(self):
        """
        Varsayılan yapılandırmayı oluştur ve veritabanına kaydet
        """
        try:
            default_config = {
                'exchange': 'binance',
                'trade_mode': 'paper',
                'base_currency': 'USDT',
                'trade_amount': 10.0,
                'max_open_trades': 3,
                'stop_loss_pct': 2.0,
                'take_profit_pct': 3.0,
                'use_telegram': False,
                'active_coins': ['BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'ADA/USDT', 'SOL/USDT'],
                'trading_strategies': ['moving_average', 'rsi'],
                'indicators': ['moving_average', 'rsi', 'macd', 'bollinger_bands'],
                'interval': '1h',
                'max_api_retries': 3,
                'retry_delay': 5,
                'api_delay': 5,
                'scan_interval': 60,
                'auto_trade': False,
                'use_tradingview': False
            }
            
            # Veritabanı bağlantısı oluştur
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # JSON'a dönüştür
            json_settings = json.dumps(default_config, indent=2)
            
            # Yeni bir bot_settings kaydı oluştur
            insert_query = """
            INSERT INTO bot_settings (settings, settings_json, created_at) 
            VALUES (%s, %s, NOW())
            """
            
            cursor.execute(insert_query, (json_settings, json_settings))
            conn.commit()
            cursor.close()
            conn.close()
            
            logger.info("Varsayılan bot yapılandırması oluşturuldu ve veritabanına kaydedildi.")
            
        except Exception as e:
            logger.error(f"Varsayılan yapılandırma oluşturulurken hata: {str(e)}")
            # En basit varsayılan yapılandırmayı belleğe yükle
            self.config = {
                'exchange': 'binance',
                'trade_mode': 'paper',
                'base_currency': 'USDT',
                'trade_amount': 10.0,
                'max_open_trades': 3,
                'stop_loss_pct': 2.0,
                'take_profit_pct': 3.0
            }

    def load_settings_from_db(self):
        """
        MySQL veritabanından bot_settings tablosundaki ayarları yükler ve mevcut ayarları günceller.
        """
        try:
            # Veritabanı bağlantı bilgilerini ayarla
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor(dictionary=True)
            
            # bot_settings tablosundan ayarları çek
            cursor.execute("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1")
            settings = cursor.fetchone()
            
            if not settings:
                logger.warning("bot_settings tablosunda ayar bulunamadı! Varsayılan ayarlar kullanılacak.")
                cursor.close()
                conn.close()
                return
            
            # Önce settings_json alanını kontrol et, boşsa settings alanını dene
            settings_data = {}
            
            # 1. settings_json alanını kontrol et
            if 'settings_json' in settings and settings['settings_json']:
                try:
                    settings_data = json.loads(settings['settings_json'])
                    logger.info("Bot ayarları settings_json alanından yüklendi.")
                except json.JSONDecodeError:
                    logger.error("settings_json alanı JSON formatına dönüştürülemedi!")
                    settings_data = {}
            
            # 2. settings_json boşsa, settings alanını dene 
            if not settings_data and 'settings' in settings and settings['settings']:
                try:
                    settings_data = json.loads(settings['settings'])
                    logger.info("Bot ayarları settings alanından yüklendi.")
                    
                    # settings_json alanını güncelle ki sonraki sefer doğru yerden çekilsin
                    try:
                        update_query = "UPDATE bot_settings SET settings_json = %s WHERE id = %s"
                        cursor.execute(update_query, (settings['settings'], settings['id']))
                        conn.commit()
                        logger.info("settings alanındaki veriler settings_json alanına kopyalandı.")
                    except Exception as copy_error:
                        logger.error(f"Ayarlar kopyalanırken hata: {str(copy_error)}")
                        
                except json.JSONDecodeError:
                    logger.error("settings alanı JSON formatına dönüştürülemedi!")
                    settings_data = {}
            
            if not settings_data:
                logger.warning("Hem settings_json hem de settings alanları boş veya geçersiz! Varsayılan ayarlar kullanılacak.")
                cursor.close()
                conn.close()
                return
            
            # Mevcut ayarları güncelle
            if settings_data:
                # Ana ayarlar
                self.config['exchange'] = settings_data.get('exchange', 'binance')
                self.config['base_currency'] = settings_data.get('base_currency', 'USDT')
                
                # ÖNEMLİ: İşlem miktarı ayarları - bu değer işlemler için kritik
                if 'trade_amount' in settings_data:
                    self.config['trade_amount'] = float(settings_data.get('trade_amount', 10.0))
                    logger.info(f"İşlem miktarı ayarlandı: {self.config['trade_amount']} {self.config['base_currency']}")
                
                # Min-Max ticaret miktarları
                self.config['min_trade_amount'] = float(settings_data.get('min_trade_amount', 11))
                self.config['max_trade_amount'] = float(settings_data.get('max_trade_amount', 1000))
                
                # Diğer ayarlar
                self.config['min_volume'] = float(settings_data.get('min_volume', 1000))
                self.config['max_coins'] = int(settings_data.get('max_coins', 50))
                self.config['position_size'] = float(settings_data.get('position_size', 0.1))
                self.config['api_delay'] = float(settings_data.get('api_delay', 1.5))
                self.config['scan_interval'] = int(settings_data.get('scan_interval', 15))
                self.config['use_tradingview'] = bool(settings_data.get('use_tradingview', False))
                self.config['tradingview_exchange'] = settings_data.get('tradingview_exchange', 'BINANCE')
                
                # YENİ: Kaldıraç ayarlarını ekle
                self.config['leverage'] = int(settings_data.get('leverage', 1))  # Varsayılan: 1x (kaldıraç yok)
                self.config['leverage_mode'] = settings_data.get('leverage_mode', 'cross')  # Varsayılan: cross
                
                # Auto discovery ayarları
                if 'auto_discovery' in settings_data:
                    auto_discovery = settings_data['auto_discovery']
                    self.config['auto_discovery'] = {
                        'enabled': bool(auto_discovery.get('enabled', True)),
                        'discovery_interval': int(auto_discovery.get('discovery_interval', 60)),
                        'min_volume_for_discovery': float(auto_discovery.get('min_volume_for_discovery', 1000)),
                        'min_price_change': float(auto_discovery.get('min_price_change', 5)),
                        'min_volume_change': float(auto_discovery.get('min_volume_change', 10)),
                        'max_coins_to_discover': int(auto_discovery.get('max_coins_to_discover', 50)),
                        'auto_add_to_watchlist': bool(auto_discovery.get('auto_add_to_watchlist', True))
                    }
                
                # İndikatör ayarları
                if 'indicators' in settings_data:
                    self.config['indicators'] = settings_data['indicators']
                
                # Strateji ayarları
                if 'strategies' in settings_data:
                    self.config['strategies'] = settings_data['strategies']
                
                # Risk yönetimi ayarları
                if 'risk_management' in settings_data:
                    risk_mgmt = settings_data['risk_management']
                    self.config['risk_management'] = risk_mgmt
                    
                    # Risk yönetimi alt ayarlarını da ana seviyeye alarak uyumluluk sağlayalım
                    self.config['stop_loss_pct'] = float(risk_mgmt.get('stop_loss', 5))
                    self.config['take_profit_pct'] = float(risk_mgmt.get('take_profit', 10))
                    self.config['trailing_stop'] = bool(risk_mgmt.get('trailing_stop', True))
                    self.config['trailing_stop_distance'] = float(risk_mgmt.get('trailing_stop_distance', 2))
                    self.config['trailing_stop_activation_pct'] = float(risk_mgmt.get('trailing_stop_activation_pct', 3))
                    self.config['trailing_stop_pct'] = float(risk_mgmt.get('trailing_stop_pct', 2))
                    self.config['max_open_trades'] = int(risk_mgmt.get('max_open_positions', 5))
                    self.config['max_risk_per_trade'] = float(risk_mgmt.get('max_risk_per_trade', 2))
                
                # Backtesting ayarları
                if 'backtesting' in settings_data:
                    self.config['backtesting'] = settings_data['backtesting']
                
                # Telegram ayarları
                if 'telegram' in settings_data:
                    telegram_settings = settings_data['telegram']
                    self.config['use_telegram'] = bool(telegram_settings.get('enabled', False))
                    self.config['telegram'] = {
                        'enabled': bool(telegram_settings.get('enabled', False)),
                        'trade_signals': bool(telegram_settings.get('trade_signals', True)),
                        'position_updates': bool(telegram_settings.get('position_updates', True)),
                        'performance_updates': bool(telegram_settings.get('performance_updates', True)),
                        'discovered_coins': bool(telegram_settings.get('discovered_coins', True))
                    }
                
                # Önemli işlem ayarları
                self.config['trade_mode'] = settings_data.get('trade_mode', 'live')
                self.config['auto_trade'] = bool(settings_data.get('auto_trade', True))
                self.config['trade_direction'] = settings_data.get('trade_direction', 'both')
                
                logger.info("Bot ayarları veritabanından başarıyla güncellendi.")
            
            # Ayrıca bakiye bilgisini kontrol et ve güncelle
            cursor.execute("SELECT * FROM account_balance ORDER BY update_time DESC LIMIT 1")
            balance_data = cursor.fetchone()
            
            if balance_data:
                self.config['account_balance'] = float(balance_data.get('available_balance', 0))
                self.config['last_balance_update'] = balance_data.get('update_time')
                logger.info(f"Hesap bakiyesi: {self.config['account_balance']} {self.config['base_currency']}")
                
                # Eğer bakiye, ayarlanan ticaret tutarından düşükse uyarı ver
                if self.config['account_balance'] < self.config['trade_amount']:
                    logger.warning(f"UYARI: Hesap bakiyesi ({self.config['account_balance']} {self.config['base_currency']}), " 
                                 f"işlem tutarından ({self.config['trade_amount']} {self.config['base_currency']}) düşük!")
            else:
                # Bakiye verisi bulunamadıysa, Binance'den alıp güncelleyelim
                try:
                    if hasattr(self, 'exchange') and self.exchange:
                        balance = self.exchange.fetch_balance()
                        base_currency = self.config.get('base_currency', 'USDT')
                        
                        if base_currency in balance and 'free' in balance[base_currency]:
                            available = float(balance[base_currency]['free'])
                            
                            # Veritabanına yeni bakiye bilgisini kaydet
                            insert_query = """
                            INSERT INTO account_balance (currency, total_balance, available_balance, update_time)
                            VALUES (%s, %s, %s, NOW())
                            """
                            cursor.execute(insert_query, (base_currency, available, available))
                            conn.commit()
                            
                            self.config['account_balance'] = available
                            logger.info(f"Binance'den alınan hesap bakiyesi: {available} {base_currency}")
                            
                            # Eğer bakiye, ayarlanan ticaret tutarından düşükse uyarı ver
                            if available < self.config['trade_amount']:
                                logger.warning(f"UYARI: Hesap bakiyesi ({available} {base_currency}), " 
                                             f"işlem tutarından ({self.config['trade_amount']} {base_currency}) düşük!")
                except Exception as e:
                    logger.error(f"Binance'den bakiye alınırken hata: {str(e)}")
                    self.config['account_balance'] = 0
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logger.error(f"Bot ayarları veritabanından yüklenirken hata: {str(e)}")
            # Hata durumunda, varsayılan ayarları kullanmaya devam et

    def load_api_keys(self):
        """
        MySQL veritabanından API anahtarlarını yükle
        """
        try:
            # Veritabanı bağlantısı oluştur
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor(dictionary=True)
            
            # API anahtarlarını çek
            cursor.execute("SELECT * FROM api_keys WHERE is_active = 1 LIMIT 1")
            api_data = cursor.fetchone()
            
            if api_data:
                self.api_keys = {
                    'exchange': api_data.get('exchange', 'binance'),
                    'api_key': api_data.get('api_key', ''),
                    'api_secret': api_data.get('api_secret', ''),
                    'telegram_token': api_data.get('telegram_token', ''),
                    'telegram_chat_id': api_data.get('telegram_chat_id', '')
                }
                logger.info("API anahtarları veritabanından başarıyla yüklendi.")
            else:
                logger.error("Veritabanında aktif API anahtarı bulunamadı!")
                # Varsayılan boş API anahtarlarını ayarla
                self.api_keys = {
                    'exchange': 'binance',
                    'api_key': '',
                    'api_secret': '',
                    'telegram_token': '',
                    'telegram_chat_id': ''
                }
                logger.warning("Varsayılan boş API anahtarları kullanılacak.")
                        
            cursor.close()
            conn.close()
            
            # API anahtarlarını döndür
            return self.api_keys
            
        except Exception as e:
            logger.error(f"API anahtarları yüklenirken hata: {str(e)}")
            # Hata durumunda boş API anahtarları döndür
            self.api_keys = {
                'exchange': 'binance',
                'api_key': '',
                'api_secret': '',
                'telegram_token': '',
                'telegram_chat_id': ''
            }
            logger.warning("Hata nedeniyle varsayılan boş API anahtarları kullanılacak.")
            return self.api_keys

    def load_positions(self):
        """
        MySQL veritabanından açık pozisyonları yükle
        """
        self.open_positions = []
        
        try:
            # Veritabanı bağlantı bilgileri
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor(dictionary=True)
            
            # Açık pozisyonları sorgulamak için SQL
            query = "SELECT * FROM open_positions WHERE status = 'OPEN'"
            cursor.execute(query)
            
            # Sonuçları yükle
            db_positions = cursor.fetchall()
            
            # Sonuçları formatlayıp listeye ekle
            for pos in db_positions:
                position = {
                    'symbol': pos['symbol'],
                    'type': pos['position_type'],
                    'entry_price': float(pos['entry_price']),
                    'amount': float(pos['amount']),
                    'entry_time': pos['entry_time'].strftime('%Y-%m-%d %H:%M:%S') if isinstance(pos['entry_time'], datetime) else pos['entry_time'],
                    'id': pos['id']
                }
                
                # Opsiyonel alanları ekle
                if pos['stop_loss']:
                    position['stop_loss'] = float(pos['stop_loss'])
                if pos['take_profit']:
                    position['take_profit'] = float(pos['take_profit'])
                if pos['strategy']:
                    position['strategy'] = pos['strategy']
                if pos['notes']:
                    position['notes'] = pos['notes']
                    
                self.open_positions.append(position)
            
            cursor.close()
            conn.close()
            
            logger.info(f"{len(self.open_positions)} açık pozisyon yüklendi")
            
        except Exception as e:
            logger.error(f"Pozisyonlar yüklenirken hata: {str(e)}")
            
        return self.open_positions

    def save_positions(self):
        """
        Açık pozisyonları MySQL veritabanına kaydet
        """
        try:
            # Veritabanı bağlantı bilgileri
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # Önce tüm pozisyonları 'CLOSED' olarak işaretle, sonra aktif olanları güncelle
            cursor.execute("UPDATE open_positions SET status = 'CLOSED' WHERE status = 'OPEN'")
            conn.commit()
            
            # Aktif pozisyonları ekle veya güncelle
            for position in self.open_positions:
                # Sembol için pozisyon var mı kontrol et
                cursor.execute("SELECT id FROM open_positions WHERE symbol = %s AND status = 'OPEN'", (position['symbol'],))
                existing = cursor.fetchone()
                
                if existing:
                    # Mevcut pozisyonu güncelle
                    cursor.execute("""
                    UPDATE open_positions SET 
                        position_type = %s, entry_price = %s, quantity = %s, 
                        stop_loss = %s, take_profit = %s, strategy = %s, status = 'OPEN',
                        notes = %s 
                    WHERE id = %s
                    """, (
                        position['type'], 
                        position['entry_price'], 
                        position['amount'],
                        position.get('stop_loss', None),
                        position.get('take_profit', None),
                        position.get('strategy', None),
                        position.get('notes', ''),
                        existing[0]
                    ))
                else:
                    # Yeni pozisyon ekle
                    cursor.execute("""
                    INSERT INTO open_positions (
                        symbol, position_type, entry_price, quantity, entry_time, 
                        stop_loss, take_profit, strategy, status, notes
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 'OPEN', %s)
                    """, (
                        position['symbol'],
                        position['type'],
                        position['entry_price'],
                        position['amount'],
                        position.get('entry_time', datetime.now().strftime('%Y-%m-%d %H:%M:%S')),
                        position.get('stop_loss', None),
                        position.get('take_profit', None),
                        position.get('strategy', None),
                        position.get('notes', '')
                    ))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            logger.info("Açık pozisyonlar başarıyla veritabanına kaydedildi")
            
        except Exception as e:
            logger.error(f"Açık pozisyonlar kaydedilirken hata: {str(e)}")

    def save_position(self, position):
        """
        Yeni pozisyonu MySQL veritabanına kaydet
        """
        try:
            # Veritabanı bağlantı bilgileri
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # Pozisyonu kaydetme SQL sorgusu
            insert_query = """
            INSERT INTO open_positions 
            (symbol, position_type, entry_price, quantity, entry_time, stop_loss, take_profit, strategy, notes, status)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 'OPEN')
            """
            
            # SQL için verileri hazırla
            entry_time = position.get('entry_time', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
            
            position_data = (
                position['symbol'],
                position['type'],
                float(position['entry_price']),
                float(position['amount']),  # amount alanı quantity sütununa kaydediliyor
                entry_time,
                position.get('stop_loss'),
                position.get('take_profit'),
                position.get('strategy'),
                position.get('notes')
            )
            
            cursor.execute(insert_query, position_data)
            conn.commit()
            
            # Mevcut açık pozisyonlar listesine ekle
            self.open_positions.append(position)
            
            logger.info(f"Yeni pozisyon kaydedildi: {position['symbol']} {position['type']} @ {position['entry_price']}")
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logger.error(f"Pozisyon kaydedilirken hata: {str(e)}")
            
        return True

    def fetch_ohlcv(self, symbol, timeframe=None):
        """
        Belirtilen sembol için OHLCV verilerini çeker
        
        :param symbol: Coin sembolü (örn. "BTC/USDT")
        :param timeframe: Zaman aralığı (örn. "1h", "15m", vb.)
        :return: OHLCV pandas DataFrame
        """
        try:
            # Şu anki zaman aralığını belirle (belirtilmediyse config'den al)
            if not timeframe:
                timeframe = self.config.get('primary_timeframe') or self.config.get('interval', '1h')
            
            logger.info(f"{symbol} için {timeframe} OHLCV verileri çekiliyor...")
            
            # Borsa API'sinden veri çek
            if self.use_tradingview:
                # TradingView'dan veri çek
                pass  # TradingView entegrasyonu burada olacak
            else:
                # CCXT aracılığıyla borsadan veri çek
                ohlcv = self.exchange.fetch_ohlcv(symbol, timeframe, limit=100)
                
                if not ohlcv or len(ohlcv) < 20:
                    logger.warning(f"{symbol} için {timeframe} zaman aralığında yeterli veri bulunamadı")
                    return pd.DataFrame()
                
                # OHLCV verilerini pandas DataFrame'e dönüştür
                df = pd.DataFrame(ohlcv, columns=['timestamp', 'open', 'high', 'low', 'close', 'volume'])
                df['timestamp'] = pd.to_datetime(df['timestamp'], unit='ms')
                df.set_index('timestamp', inplace=True)
                
                return df
                
        except Exception as e:
            logger.error(f"{symbol} için OHLCV verileri çekilirken hata: {str(e)}")
            return pd.DataFrame()

    def fetch_multi_timeframe_ohlcv(self, symbol):
        """
        Bir sembol için birden fazla zaman aralığında OHLCV verilerini çeker
        
        :param symbol: Coin sembolü (örn. "BTC/USDT")
        :return: Farklı zaman aralıklarındaki OHLCV verilerini içeren sözlük
        """
        multi_tf_data = {}
        
        # Ayarlardan seçilen zaman aralıklarını al, yoksa sadece primary_timeframe'i kullan
        timeframes = self.config.get('timeframes', [])
        
        # Hiç zaman aralığı seçilmemişse veya boşsa, varsayılan zaman aralığını kullan
        if not timeframes:
            primary_tf = self.config.get('primary_timeframe') or self.config.get('interval', '1h')
            timeframes = [primary_tf]
            
        logger.info(f"{symbol} için {len(timeframes)} farklı zaman aralığında veri çekiliyor: {timeframes}")
        
        # Her bir zaman aralığı için OHLCV verisi çek
        for tf in timeframes:
            try:
                ohlcv_data = self.fetch_ohlcv(symbol, tf)
                if not ohlcv_data.empty:
                    multi_tf_data[tf] = ohlcv_data
                else:
                    logger.warning(f"{symbol} için {tf} zaman aralığında veri çekilemedi")
            except Exception as e:
                logger.error(f"{symbol} için {tf} zaman aralığında veri çekerken hata: {str(e)}")
        
        return multi_tf_data

    def calculate_indicators(self, df, symbol):
        """
        OHLCV verileri için teknik göstergeleri hesaplar
        
        :param df: OHLCV verileri DataFrame
        :param symbol: Coin sembolü
        :return: Hesaplanan göstergeleri içeren dict
        """
        # İndikatör yöneticisi ile hesaplama yap
        return self.indicators_manager.calculate_indicators(df, symbol)

    def discover_potential_coins(self):
        """
        Potansiyel yüksek getirili coinleri otomatik olarak keşfeder
        
        :return: Keşfedilen yüksek potansiyelli coinler listesi
        """
        try:
            logger.info("Yeni potansiyel coinler keşfediliyor...")
            
            # CCXT ile mevcut tüm sembol listesini al
            if not hasattr(self, 'exchange') or self.exchange is None:
                exchange_name = self.config.get('exchange', 'binance')
                exchange_class = getattr(ccxt, exchange_name)
                self.exchange = exchange_class({
                    'apiKey': self.api_keys.get('api_key', ''),
                    'secret': self.api_keys.get('api_secret', ''),
                    'enableRateLimit': True
                })
            
            # Exchange'den tüm marketleri al
            try:
                markets = self.exchange.load_markets()
            except Exception as e:
                logger.error(f"Piyasa bilgileri alınırken hata: {str(e)}")
                return []
            
            # USDT çiftlerini filtrele
            base_currency = self.config.get('base_currency', 'USDT')
            usdt_pairs = [s for s in markets.keys() if s.endswith(f'/{base_currency}')]
            
            logger.info(f"{len(usdt_pairs)} adet {base_currency} çifti bulundu, analiz ediliyor...")
            
            # Çok fazla çift varsa, daha az sayıda işlemek için örnekle (rate limit sorunlarını önlemek için)
            if len(usdt_pairs) > 100:
                # En popüler coinleri öncelikle analiz et
                popular_coins = [f"{coin}/{base_currency}" for coin in ['BTC', 'ETH', 'BNB', 'SOL', 'XRP', 'ADA', 'DOT', 'AVAX', 'MATIC', 'LINK']]
                # Popüler coinleri çıkar ve kalan coinleri rastgele örnekle
                remaining_pairs = list(set(usdt_pairs) - set(popular_coins))
                import random
                sampled_pairs = random.sample(remaining_pairs, min(90, len(remaining_pairs)))
                all_pairs_to_analyze = popular_coins + sampled_pairs
            else:
                all_pairs_to_analyze = usdt_pairs
                
            # Her bir sembol için güncel veri al ve potansiyel olanları filtrele
            potential_coins = []
            
            # Rate limit aşımını önlemek için sembol listesini parçalara böl
            chunk_size = 20  # Her seferde 20 sembol işle
            symbol_chunks = [all_pairs_to_analyze[i:i + chunk_size] for i in range(0, len(all_pairs_to_analyze), chunk_size)]
            
            # Her bir sembol parçasını işle
            for chunk_index, symbol_chunk in enumerate(symbol_chunks):
                try:
                    logger.info(f"Coin keşfi: {chunk_index+1}/{len(symbol_chunks)} grup analiz ediliyor...")
                    
                    # Her bir sembol için veri al
                    for symbol in symbol_chunk:
                        try:
                            # Bu sembol zaten açık pozisyonlarda ise atla
                            if any(position['symbol'] == symbol for position in self.open_positions):
                                continue
                                
                            # Güncel fiyat verisi al
                            ticker = self.exchange.fetch_ticker(symbol)
                            
                            # Son fiyat ve işlem hacmini al
                            last_price = ticker['last'] if 'last' in ticker and ticker['last'] is not None else None
                            
                            # USD cinsinden hacim hesapla
                            # Bazı borsalar doğrudan USD hacmi döndürmüyor olabilir, bu nedenle hesaplamamız gerekebilir
                            volume_usd = ticker.get('quoteVolume', 0)
                            
                            # quoteVolume yoksa, son fiyat ile çarparak hesapla
                            if volume_usd is None or volume_usd == 0:
                                volume_usd = ticker.get('volume', 0) * (last_price or 0)
                                logger.debug(f"{symbol} için hacim verisi USD olarak hesaplandı: ${volume_usd:,.2f}")
                                
                            # Hala hacim değeri yoksa minimum bir değer kullan
                            if volume_usd is None or volume_usd == 0:
                                volume_usd = 1000  # Minimum varsayılan değer
                                logger.debug(f"{symbol} için tam hacim verisi yok, minimum değer kullanılıyor.")
                            
                            # 24 saatlik fiyat değişim yüzdesi - NULL değer kontrolü ile
                            if 'percentage' in ticker and ticker['percentage'] is not None:
                                price_change_pct = ticker['percentage'] * 100
                            elif 'change' in ticker and ticker['change'] is not None:
                                price_change_pct = ticker['change']
                            else:
                                # Hiç değişim verisi yoksa, yapay oran kullanma, sadece bilgi mesajı
                                price_change_pct = 0
                                logger.debug(f"{symbol} için fiyat değişim verisi yok.")
                            
                            # Filtreleme kriterleri - düşük işlem hacimli semboller için özel durum
                            min_volume = self.config.get('discovery_min_volume', 100000)  # Minimum USD hacim (default 100.000)
                            min_price_change = self.config.get('discovery_min_price_change', 5)  # Minimum %5 fiyat değişimi
                            
                            # Normal filtreleme (yüksek hacim ve fiyat değişimi olanlar)
                            if volume_usd > min_volume and price_change_pct >= min_price_change:
                                potential_coin = {
                                    'symbol': symbol,
                                    'last_price': last_price if last_price is not None else 0,
                                    'volume_usd': volume_usd,
                                    'price_change_pct': price_change_pct,
                                    'discovery_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                                }
                                potential_coins.append(potential_coin)
                                logger.debug(f"Potansiyel coin bulundu: {symbol} (${volume_usd:,.2f} hacim, %{price_change_pct:.2f} değişim)")
                            
                            # Düşük hacimli ama son günlerde dikkate değer fiyat artışı olanlar
                            elif volume_usd > 0 and price_change_pct >= min_price_change * 2:  # Daha yüksek fiyat artışı
                                potential_coin = {
                                    'symbol': symbol,
                                    'last_price': last_price if last_price is not None else 0,
                                    'volume_usd': volume_usd,
                                    'price_change_pct': price_change_pct,
                                    'discovery_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                                }
                                potential_coins.append(potential_coin)
                                logger.debug(f"Düşük hacimli ama yüksek fiyat artışlı coin: {symbol} (${volume_usd:,.2f} hacim, %{price_change_pct:.2f} değişim)")
                                
                        except Exception as coin_error:
                            logger.warning(f"{symbol} değerlendirilirken hata: {str(coin_error)}")
                            continue
                                        
                    # API rate limit sorunlarını önlemek için gruplar arasında bekle
                    time.sleep(3)  # Her grup arasında 3 saniye bekle
                    
                except Exception as chunk_error:
                    logger.error(f"Grup {chunk_index+1} işlenirken hata: {str(chunk_error)}")
                    time.sleep(5)  # Hata durumunda 5 saniye bekle ve devam et
                    continue
            
            # Hacme göre potansiyel coinleri sırala (en yüksek hacimli olanlar önce)
            potential_coins.sort(key=lambda x: x['volume_usd'], reverse=True)
            
            # En iyi 20 potansiyel coini al
            top_potential_coins = potential_coins[:20]
            
            logger.info(f"{len(top_potential_coins)} adet potansiyel yüksek potansiyelli coin keşfedildi.")
            
            # Bu coinlerin her birini analiz et ve çok iyi olanları aktif listeye ekle
            highly_potential_coins = []
            
            for coin in top_potential_coins:
                symbol = coin['symbol']
                
                # Coin'in teknik analizini yap
                analysis = self.analyze_combined_indicators(symbol)
                    
                if analysis and analysis['trade_signal'] == 'BUY':
                    logger.info(f"Yüksek potansiyelli coin keşfedildi: {symbol}, Fiyat Değişimi: {coin['price_change_pct']:.2f}%, Hacim: ${coin['volume_usd']:,.2f}")
                    
                    # Coini keşfedilmiş coinler listesine ekle
                    coin['analysis'] = {
                        'trade_signal': analysis['trade_signal'],
                        'buy_signals': analysis['signals']['buy_count'],
                        'sell_signals': analysis['signals']['sell_count'],
                        'price': analysis['price']
                    }
                             
                    highly_potential_coins.append(coin)
                    
                    # Bu coini otomatik olarak aktif izleme listesine ekle
                    self.add_coin_to_active_list(symbol)
                
                # API rate limit sorunlarını önlemek için her coin analizi arasında bekle
                time.sleep(2)
                   
            # Keşfedilen coinleri kaydet (daha sonra incelenmek üzere)
            self.discovered_coins = highly_potential_coins
            self.save_discovered_coins_to_db()
            
            return highly_potential_coins
            
        except Exception as e:
            logger.error(f"Coin keşfetme sırasında hata: {str(e)}")
            return []

    def add_coin_to_active_list(self, symbol):
        """
        Coin sembolünü aktif izleme listesine ekler
        
        :param symbol: Coin sembolü (ör. BTC/USDT)
        :return: Başarı durumu
        """
        try:
            # Veritabanı bağlantısı
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # active_coins tablosunun var olup olmadığını kontrol et
            try:
                cursor.execute("SELECT 1 FROM active_coins LIMIT 1")
                # ÖNEMLİ: Bu sonucu oku veya temizle, aksi takdirde "Unread result found" hatası alınabilir
                cursor.fetchall()
            except Exception:
                # Tablo yoksa oluştur
                create_table_query = """
                CREATE TABLE IF NOT EXISTS active_coins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(20) NOT NULL,
                    price DECIMAL(20, 8) DEFAULT 0,
                    `signal` VARCHAR(10) DEFAULT 'NEUTRAL',
                    is_active TINYINT(1) DEFAULT 1,
                    added_by VARCHAR(50) DEFAULT 'auto_discovery',
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                """
                cursor.execute(create_table_query)
                conn.commit()
                logger.info("active_coins tablosu oluşturuldu")
            
            # Güncel fiyat bilgisini al
            current_price = 0
            signal = "NEUTRAL"
            
            try:
                # Fiyat bilgisi al
                ticker = self.exchange.fetch_ticker(symbol)
                current_price = ticker['last'] if ticker and 'last' in ticker and ticker['last'] is not None else 0
                
                # Sinyal bilgisini al - analiz yap
                analysis = self.analyze_combined_indicators(symbol)
                if analysis and 'trade_signal' in analysis:
                    signal = analysis['trade_signal']
            except Exception as e:
                logger.error(f"Coin için fiyat veya analiz alınırken hata: {str(e)}")
            
            # Coin zaten var mı kontrol et
            cursor.execute("SELECT * FROM active_coins WHERE symbol = %s", (symbol,))
            existing_coin = cursor.fetchone()  # Önemli: Her sorgu sonucunu oku
            
            if existing_coin:
                # Coin zaten var, güncelle
                update_query = """
                UPDATE active_coins SET is_active = 1, price = %s, `signal` = %s, added_by = 'bot_update', last_updated = NOW()
                WHERE symbol = %s
                """
                cursor.execute(update_query, (current_price, signal, symbol))
                conn.commit()
                logger.info(f"{symbol} coin listesinde zaten var, güncellendi. Fiyat: {current_price}, Sinyal: {signal}")
            else:
                # Yeni coin ekle
                insert_query = """
                INSERT INTO active_coins (symbol, price, `signal`, is_active, added_by, last_updated, created_at)
                VALUES (%s, %s, %s, 1, 'bot_discovery', NOW(), NOW())
                """
                cursor.execute(insert_query, (symbol, current_price, signal))
                conn.commit()
                logger.info(f"{symbol} aktif izleme listesine eklendi. Fiyat: {current_price}, Sinyal: {signal}")
                
            cursor.close()
            conn.close()
            return True
            
        except Exception as e:
            logger.error(f"Coin izleme listesine eklenirken hata: {str(e)}")
            # Hata durumunda bağlantıları düzgünce kapat
            if 'cursor' in locals() and cursor:
                try:
                    cursor.close()
                except:
                    pass
            if 'conn' in locals() and conn:
                try:
                    conn.close()
                except:
                    pass
            return False

    def update_trailing_stops(self):
        """
        Açık pozisyonlar için trailing stop değerlerini günceller.
        Trailing stop, fiyat yükseldikçe stop-loss seviyesini yukarı çeker,
        böylece kârın bir kısmını korur.
        """
        try:
            for position in self.open_positions:
                symbol = position['symbol']
                
                # Bu sembol için trailing stop kaydı var mı kontrol et
                if symbol not in self.trailing_stops:
                    # Yeni trailing stop başlat
                    self.trailing_stops[symbol] = {
                        'initial_price': position['entry_price'],
                        'highest_price': position['entry_price'],
                        'current_stop': position.get('stop_loss', 0)
                    }
                
                # Mevcut fiyatı al
                try:
                    ticker = self.exchange.fetch_ticker(symbol)
                    current_price = ticker['last']
                    
                    # Şimdiye kadar görülen en yüksek fiyat
                    trailing_data = self.trailing_stops[symbol]
                    
                    # Fiyat yeni bir yüksek seviyeye ulaştı mı?
                    if current_price > trailing_data['highest_price']:
                        old_highest = trailing_data['highest_price']
                        trailing_data['highest_price'] = current_price
                        
                        # Fiyat artışını hesapla
                        price_increase_pct = (current_price / old_highest - 1) * 100
                                     
                        # Trailing stop değerini güncelle (kârın bir kısmını korumak için)
                        trailing_pct = self.config.get('trailing_stop_pct', 50)  # Yeni zirvenin %50'si kadar geride
                        price_increase = current_price - old_highest
                        new_stop = trailing_data['current_stop'] + (price_increase * trailing_pct / 100)
                                     
                        # Trailing stop güncelleme
                        trailing_data['current_stop'] = new_stop
                        
                        # Pozisyonun stop-loss değerini güncelle
                        position['stop_loss'] = new_stop
                        logger.info(f"Trailing stop güncellendi: {symbol} | Yeni stop: {new_stop:.4f} | Fiyat: {current_price:.4f}")
                        
                        # Pozisyonu veritabanında güncelle
                        self.update_position_in_db(position)
                        
                except Exception as e:
                    logger.error(f"Trailing stop güncellenirken hata: {symbol}, {str(e)}")
                
            logger.debug("Trailing stoplar güncellendi")
            
        except Exception as e:
            logger.error(f"Trailing stop fonksiyonunda hata: {str(e)}")

    def update_position_in_db(self, position):
        """
        Pozisyonu veritabanında günceller
        
        :param position: Güncellenecek pozisyon
        :return: Başarı durumu
        """
        try:
            # Veritabanı bağlantısı
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # ID varsa kullan, yoksa sembolden bul
            if 'id' in position:
                where_clause = "id = %s"
                where_value = position['id']
            else:
                where_clause = "symbol = %s AND status = 'OPEN'"
                where_value = position['symbol']
            
            # Pozisyonu güncelle
            update_query = f"""
            UPDATE open_positions SET 
                entry_price = %s,
                quantity = %s,
                stop_loss = %s,
                take_profit = %s,
                notes = %s,
                last_updated = NOW()
            WHERE {where_clause}
            """
            
            cursor.execute(update_query, (
                float(position['entry_price']),
                float(position['amount']),  # amount alanı quantity sütununa kaydediliyor
                position.get('stop_loss'),
                position.get('take_profit'),
                position.get('notes', 'Trailing stop güncellendi'),
                where_value
            ))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            return True
            
        except Exception as e:
            logger.error(f"Pozisyon güncellenirken hata: {str(e)}")
            return False

    def check_stop_loss_and_take_profit(self):
        """
        Açık pozisyonların stop-loss ve take-profit seviyelerine ulaşıp ulaşmadığını kontrol eder
        ve gerekirse pozisyonları kapatır.
        """
        try:
            # Açık pozisyon yoksa işlemi atla
            if not self.open_positions:
                return
                
            logger.debug(f"Stop-loss ve take-profit kontrolü yapılıyor ({len(self.open_positions)} pozisyon)")
            
            for position in self.open_positions[:]:  # Kopyasını kullan (silme işlemi sırasında değişecek)
                symbol = position['symbol']
                
                try:
                    # Mevcut fiyatı al
                    ticker = self.exchange.fetch_ticker(symbol)
                    current_price = ticker['last']
                    
                    # Stop-loss ve take-profit değerleri
                    stop_loss = position.get('stop_loss', 0)
                    take_profit = position.get('take_profit', float('inf'))  # Eğer yoksa sonsuz yap
                    entry_price = position['entry_price']
                    
                    # Kâr/zarar yüzdesi
                    profit_loss_pct = ((current_price / entry_price) - 1) * 100
                    
                    # Stop-loss kontrol (LONG pozisyonlar için)
                    if stop_loss and current_price <= stop_loss and position['type'] == 'LONG':
                        logger.info(f"Stop-loss tetiklendi: {symbol} @ {current_price:.4f} (Stop: {stop_loss:.4f}, Kâr/Zarar: {profit_loss_pct:.2f}%)")
                        
                        # Pozisyonu kapat
                        self.close_position(position, 'stop_loss', current_price)
                        continue  # Bir sonraki pozisyona geç
                            
                    # Take-profit kontrol (LONG pozisyonlar için)
                    if take_profit and current_price >= take_profit and position['type'] == 'LONG':
                        logger.info(f"Take-profit tetiklendi: {symbol} @ {current_price:.4f} (TP: {take_profit:.4f}, Kâr: {profit_loss_pct:.2f}%)")
                        
                        # Pozisyonu kapat
                        self.close_position(position, 'take_profit', current_price)
                        continue
                    
                    # Kârı korumak için kayan stop-loss ekle (pozisyon kârda ise)
                    if profit_loss_pct >= 5 and not symbol in self.trailing_stops:
                        # İlk kez %5 kâra ulaşıldı, trailing stop başlat
                        self.trailing_stops[symbol] = {
                            'initial_price': entry_price,
                            'highest_price': current_price,
                            'current_stop': entry_price * 1.01  # En azından maliyetin %1 üzerinde
                        }
                        position['stop_loss'] = self.trailing_stops[symbol]['current_stop']
                        logger.info(f"Kâr koruma için trailing stop başlatıldı: {symbol} @ {current_price:.4f} (Stop: {position['stop_loss']:.4f})")
                        
                        # Pozisyonu veritabanında güncelle
                        self.update_position_in_db(position)
                
                except Exception as e:
                    logger.error(f"Stop-loss/Take-profit kontrolünde hata: {symbol}, {str(e)}")
            
        except Exception as e:
            logger.error(f"Stop-loss ve take-profit kontrolü sırasında hata: {str(e)}")

    def close_position(self, position, close_reason, close_price=None):
        """
        Pozisyonu kapat (gerçek veya simülasyon modunda)
        
        :param position: Kapatılacak pozisyon
        :param close_reason: Kapama nedeni ('take_profit', 'stop_loss', 'manual', 'signal')
        :param close_price: Kapama fiyatı (belirtilmezse mevcut fiyat alınır)
        :return: Başarı durumu
        """
        try:
            symbol = position['symbol']
            
            # Kapama fiyatı belirtilmemişse mevcut fiyatı al
            if close_price is None:
                ticker = self.exchange.fetch_ticker(symbol)
                close_price = ticker['last']
            
            # Kâr/zarar hesapla
            entry_price = position['entry_price']
            profit_loss_pct = ((close_price / entry_price) - 1) * 100
            
            # İşlem modu kontrolü
            if self.config.get('trade_mode', 'paper') == 'paper':
                logger.info(f"TEST MOD: {symbol} pozisyon kapatıldı. Fiyat: {close_price:.4f}, Kâr/Zarar: {profit_loss_pct:.2f}%")
                
                # Pozisyonu listeden kaldır
                if position in self.open_positions:
                    self.open_positions.remove(position)
                
                # İşlem geçmişine ekle
                closed_position = {
                    'symbol': symbol,
                    'type': position['type'],
                    'entry_price': entry_price,
                    'close_price': close_price,
                    'amount': position['amount'],
                    'profit_loss_pct': profit_loss_pct,
                    'close_reason': close_reason,
                    'close_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                }
                self.trade_history.append(closed_position)
                
                # Veritabanında pozisyonu kapat
                self.close_position_in_db(position['id'] if 'id' in position else symbol, close_price, profit_loss_pct, close_reason)
                
                # Trailing stop verilerini temizle
                if symbol in self.trailing_stops:
                    del self.trailing_stops[symbol]
                
                # Telegram ile bildirim gönder (eğer etkinleştirilmişse)
                if self.config.get('use_telegram', False):
                    emoji = "🟢" if profit_loss_pct > 0 else "🔴"
                    message = f"{emoji} *Pozisyon Kapatıldı*\n"
                    message += f"Sembol: `{symbol}`\n"
                    message += f"Fiyat: `{close_price:.4f}`\n"
                    message += f"Kâr/Zarar: `{profit_loss_pct:+.2f}%`\n"
                    message += f"Neden: `{close_reason}`\n"
                    message += f"Mod: `TEST`"
                    
                    self.send_telegram_message(message)
                
                return True
            
            # Gerçek işlem modu (canlı)
            elif self.config.get('trade_mode') == 'live':
                logger.info(f"CANLI MOD: {symbol} pozisyon kapatılıyor. Fiyat: {close_price:.4f}, Kâr/Zarar: {profit_loss_pct:.2f}%")
                
                # Exchange API ile satış yap
                try:
                    # Satış işlemi (LONG pozisyonlar için)
                    if position['type'] == 'LONG':
                        amount = position['amount']
                        order = self.exchange.create_market_sell_order(symbol, amount)
                        logger.info(f"CANLI SATIŞ: {symbol} başarıyla satıldı. Miktar: {amount}, Fiyat: {close_price:.4f}")
                    
                    # Pozisyonu listeden kaldır
                    if position in self.open_positions:
                        self.open_positions.remove(position)
                            
                    # Veritabanında pozisyonu kapat
                    self.close_position_in_db(position['id'] if 'id' in position else symbol, close_price, profit_loss_pct, close_reason)
                    
                    # Trailing stop verilerini temizle
                    if symbol in self.trailing_stops:
                        del self.trailing_stops[symbol]
                    
                    # Telegram ile bildirim gönder (eğer etkinleştirilmişse)
                    if self.config.get('use_telegram', False):
                        emoji = "🟢" if profit_loss_pct > 0 else "🔴"
                        message = f"{emoji} *Pozisyon Kapatıldı*\n"
                        message += f"Sembol: `{symbol}`\n"
                        message += f"Fiyat: `{close_price:.4f}`\n"
                        message += f"Kâr/Zarar: `{profit_loss_pct:+.2f}%`\n"
                        message += f"Neden: `{close_reason}`\n"
                        message += f"Mod: `CANLI`"
                        
                        self.send_telegram_message(message)
                             
                    return True
                    
                except Exception as e:
                    logger.error(f"CANLI pozisyon kapatılırken hata: {symbol}, {str(e)}")
                    return False
            
            # Desteklenmeyen mod
            else:
                logger.warning(f"Desteklenmeyen işlem modu: {self.config.get('trade_mode')}")
                return False
                
        except Exception as e:
            logger.error(f"Pozisyon kapatılırken hata: {str(e)}")
            return False

    def close_position_in_db(self, position_id_or_symbol, close_price, profit_loss_pct, close_reason):
        """
        Veritabanında bir pozisyonu kapalı olarak işaretler
        
        :param position_id_or_symbol: Pozisyon ID'si veya sembolü
        :param close_price: Kapama fiyatı
        :param profit_loss_pct: Kâr/zarar yüzdesi
        :param close_reason: Kapama nedeni
        :return: Başarı durumu
        """
        try:
            # Veritabanı bağlantısı
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # ID ya da sembol kontrolü
            if isinstance(position_id_or_symbol, int) or position_id_or_symbol.isdigit():
                # ID ile güncelleme
                where_clause = "id = %s"
                where_value = int(position_id_or_symbol)
            else:
                # Sembol ile güncelleme
                where_clause = "symbol = %s AND status = 'OPEN'"
                where_value = position_id_or_symbol
            
            # Pozisyonu kapat
            update_query = f"""
            UPDATE open_positions SET 
                exit_price = %s,
                exit_time = NOW(),
                profit_loss_pct = %s,
                close_reason = %s,
                status = 'CLOSED',
                notes = CONCAT(IFNULL(notes, ''), ' | Kapatıldı: ', %s)
            WHERE {where_clause}
            """
            
            cursor.execute(update_query, (
                float(close_price),
                float(profit_loss_pct),
                close_reason,
                f"{close_reason} - {profit_loss_pct:+.2f}%",
                where_value
            ))
            
            # İşlem geçmişine ekle (trade_history tablosuna)
            # trade_history tablosu yoksa oluştur
            try:
                cursor.execute("""
                CREATE TABLE IF NOT EXISTS trade_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    position_id INT,
                    symbol VARCHAR(20) NOT NULL,
                    position_type VARCHAR(10) NOT NULL,
                    entry_price DECIMAL(20, 8) NOT NULL,
                    exit_price DECIMAL(20, 8) NOT NULL,
                    amount DECIMAL(20, 8) NOT NULL,
                    entry_time TIMESTAMP,
                    exit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    profit_loss_pct DECIMAL(10, 2),
                    close_reason VARCHAR(20),
                    strategy VARCHAR(50),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                """)
                conn.commit()
            except Exception as e:
                logger.error(f"trade_history tablosu oluşturma hatası: {str(e)}")
            
            # Kapanan pozisyonun verilerini al
            if isinstance(position_id_or_symbol, int) or position_id_or_symbol.isdigit():
                cursor.execute("SELECT * FROM open_positions WHERE id = %s", (int(position_id_or_symbol),))
            else:
                cursor.execute("SELECT * FROM open_positions WHERE symbol = %s AND status = 'CLOSED' ORDER BY exit_time DESC LIMIT 1", (position_id_or_symbol,))
                
            position_data = cursor.fetchone()
            
            # Geçmişe ekle
            if position_data:
                # Dictionary'ye dönüştür
                columns = [col[0] for col in cursor.description]
                position_dict = dict(zip(columns, position_data))
                
                # İşlem geçmişine ekle
                insert_query = """
                INSERT INTO trade_history (
                    position_id, symbol, position_type, entry_price, exit_price, 
                    amount, entry_time, exit_time, profit_loss_pct, close_reason, strategy, notes
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s)
                """
                                
                cursor.execute(insert_query, (
                    position_dict.get('id'),
                    position_dict.get('symbol'),
                    position_dict.get('position_type'),
                    float(position_dict.get('entry_price')),
                    float(close_price),
                    float(position_dict.get('amount')),
                    position_dict.get('entry_time'),
                    float(profit_loss_pct),
                    close_reason,
                    position_dict.get('strategy'),
                    f"Kapatıldı: {close_reason} - {profit_loss_pct:+.2f}%"
                ))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            logger.info(f"Pozisyon veritabanında kapalı olarak işaretlendi. ID/Sembol: {position_id_or_symbol}")
            return True
            
        except Exception as e:
            logger.error(f"Pozisyon veritabanında kapatılırken hata: {str(e)}")
            return False

    def monitor_coins(self):
        """
        Aktif coinleri izler ve analiz eder
        """
        try:
            logger.info("Coin izleme başlatıldı...")
            
            # API istekleri için dinamik gecikme yönetimi
            base_delay = self.config.get('api_delay', 5)  # Varsayılan değeri 5 saniyeye çıkardık
            error_count = 0
            last_settings_refresh = 0
            
            while not self.stop_event.is_set():
                try:
                    # Veritabanından ayarları yenile - her döngüde kontrol et
                    current_time = time.time()
                    # Her 60 saniyede bir ayarları yenile
                    if current_time - last_settings_refresh > 60:
                        self.load_settings_from_db()
                        logger.info("Bot ayarları veritabanından yenilendi: trade_amount=" + str(self.config.get('trade_amount')) + ", auto_trade=" + str(self.config.get('auto_trade')))
                        last_settings_refresh = current_time
                    
                    # Açık pozisyonların stop-loss ve take-profit kontrolü
                    self.check_stop_loss_and_take_profit()
                    
                    # Trailing stop değerlerini güncelle
                    self.update_trailing_stops()
                    
                    # Aktif coinleri al
                    self.active_coins = self.get_active_coins()
                    logger.info(f"İzlenecek {len(self.active_coins)} coin bulundu")
                    
                    # Coin sayısına göre gecikme süresini ayarlama
                    coin_count = len(self.active_coins)
                    dynamic_delay = max(base_delay, min(20, coin_count / 10))  # En az 5 sn, en fazla 20 sn
                    
                    # Paralelleştirme için threadpool oluştur (en fazla 5 thread)
                    from concurrent.futures import ThreadPoolExecutor
                    max_workers = min(5, coin_count)
                    
                    # Paralel işlemler güvenli olsun diye grup halinde yap
                    # Her grupta en fazla 5 coin analiz et
                    coin_groups = [self.active_coins[i:i + max_workers] for i in range(0, len(self.active_coins), max_workers)]
                    
                    for coin_group in coin_groups:
                        # ThreadPool ile birden fazla coini paralel analiz et
                        with ThreadPoolExecutor(max_workers=max_workers) as executor:
                            # Her bir coin için analyze_coin fonksiyonunu çağır
                            futures = {executor.submit(self.analyze_coin, coin['symbol']): coin['symbol'] for coin in coin_group}
                            
                            # Sonuçları topla
                            for future in futures:
                                try:
                                    symbol = futures[future]
                                    analysis = future.result()
                                    
                                    if analysis:
                                        # İşlem sinyali üretildiyse uygula (auto_trade açık ise)
                                        if analysis['trade_signal'] in ['BUY', 'SELL'] and self.config.get('auto_trade', False):
                                            self.execute_trade(symbol, analysis['trade_signal'], analysis)
                                except Exception as e:
                                    symbol = futures[future]
                                    logger.error(f"{symbol} analiz edilirken hata: {str(e)}")
                                        
                        # Rate limit aşımını önlemek için her grup sonrası bekle
                        time.sleep(dynamic_delay)
                             
                    # Tüm coinleri izledikten sonra tarama aralığı kadar bekle
                    scan_interval = self.config.get('scan_interval', 60)
                    logger.info(f"Tüm coinler tarandı. {scan_interval} saniye bekleniyor...")
                    
                    # Tarama aralığını parçalara bölerek bot'un daha hızlı durmasını sağla
                    for _ in range(max(1, int(scan_interval / 10))):
                        if self.stop_event.is_set():
                            break
                        time.sleep(min(10, scan_interval))
                    
                except Exception as e:
                    error_count += 1
                    logger.error(f"Coin izleme döngüsünde hata: {str(e)}")
                    time.sleep(60)  # Hata durumunda 1 dakika bekle
                    
        except Exception as e:
            logger.error(f"Coin izleme thread'i hata ile sonlandı: {str(e)}")

    def analyze_coin(self, symbol):
        """
        Tek bir coini analiz eder - threadpool için optimize edilmiş
        
        :param symbol: Coin sembolü
        :return: Analiz sonuçları
        """
        try:
            logger.info(f"{symbol} analiz ediliyor...")
             
            # Birleşik analiz yap (TradingView + klasik indikatörler)
            analysis = self.analyze_combined_indicators(symbol)
            
            if analysis:
                logger.info(f"{symbol} için analiz tamamlandı: {analysis['trade_signal']}")
                     
                # Alım sinyali ise ve Telegram bildirimleri açık ise bildirim gönder
                if analysis['trade_signal'] == 'BUY' and self.config.get('use_telegram', False):
                    self.send_trading_signal_alert(symbol, 'BUY', analysis)
                
                # Satış sinyali ise ve açık pozisyon varsa bildirim gönder
                if analysis['trade_signal'] == 'SELL' and self.config.get('use_telegram', False):
                    for position in self.open_positions:
                        if position['symbol'] == symbol:
                            self.send_trading_signal_alert(symbol, 'SELL', analysis)
                            break
            
            return analysis
            
        except Exception as e:
            logger.error(f"{symbol} analiz edilirken hata: {str(e)}")
            return None

    def send_trading_signal_alert(self, symbol, signal_type, analysis):
        """
        Alım-satım sinyalleri için Telegram bildirimi gönderir
        
        :param symbol: Coin sembolü
        :param signal_type: Sinyal türü ('BUY' veya 'SELL')
        :param analysis: Analiz verisi
        """
        try:
            if not self.config.get('use_telegram', False):
                return
                
            # Sinyal türüne göre emoji belirle
            emoji = "🟢" if signal_type == 'BUY' else "🔴"
            price = analysis['price']
            
            # İndikatör bilgilerini topla
            indicators = analysis['indicators']
            rsi = indicators['rsi']['value'] if 'rsi' in indicators else 'N/A'
            macd = indicators['macd']['value'] if 'macd' in indicators else 'N/A'
            
            # Mesaj oluştur
            message = f"{emoji} *{signal_type} Sinyali: {symbol}*\n\n"
            message += f"💲 Fiyat: `{price:.4f}`\n"
            message += f"📊 RSI: `{rsi:.2f}`\n"
            message += f"📈 MACD: `{macd:.4f}`\n"
            
            # Stratejilerden sinyal nedenlerini ekle
            if 'strategies' in analysis:
                message += "\n*Sinyal Nedenleri:*\n"
                for strategy_name, strategy_data in analysis['strategies'].items():
                    if strategy_data['signal'] == signal_type:
                        reason = strategy_data.get('reason', 'Belirtilmemiş')
                        message += f"• {strategy_name}: {reason}\n"
            
            # Sinyal gücünü ekle
            if 'signals' in analysis:
                buy_signals = analysis['signals']['buy_count']
                sell_signals = analysis['signals']['sell_count']
                neutral_signals = analysis['signals']['neutral_count']
                
                message += f"\n📊 Sinyal gücü: {buy_signals} alım, {sell_signals} satım, {neutral_signals} nötr\n"
            
            # Mesajı gönder
            self.send_telegram_message(message)
            
        except Exception as e:
            logger.error(f"Ticaret sinyali bildirimi gönderilirken hata: {str(e)}")

    def analyze_combined_indicators(self, symbol, ohlcv_data=None):
        """
        TradingView ve klasik teknik indikatörler birlikte değerlendirilir.
        Çoklu zaman aralığı desteği ile farklı zaman dilimlerindeki sinyaller birleştirilir.
        
        :param symbol: Coin sembolü
        :param ohlcv_data: OHLCV verileri (varsa)
        :return: Analiz sonuçları
        """
        try:
            # TradingView verilerini çek
            tradingview_data = None
            if self.use_tradingview:
                tradingview_data = self.fetch_tradingview_data(symbol)
            
            # Çoklu zaman aralığı verileri çek
            multi_tf_data = None
            if ohlcv_data is None:
                multi_tf_data = self.fetch_multi_timeframe_ohlcv(symbol)
            else:
                # Eğer tek bir OHLCV verisi sağlandıysa, onu birincil timeframe olarak kullan
                primary_tf = self.config.get('primary_timeframe', '1h')
                multi_tf_data = {primary_tf: ohlcv_data}
            
            # Veriler yoksa işlemi sonlandır
            if not multi_tf_data or len(multi_tf_data) == 0:
                logger.warning(f"{symbol} için OHLCV verileri alınamadı")
                return None
            
            # Her bir zaman aralığı için indikatörleri hesapla
            multi_tf_indicators = self.indicators_manager.calculate_multi_timeframe_indicators(multi_tf_data, symbol)
            
            # İlk timeframe'i belirle (genellikle birincil timeframe)
            first_tf = list(multi_tf_data.keys())[0]
            ohlcv_data = multi_tf_data[first_tf]  # İlk zaman aralığının OHLCV verilerini al
            
            # TradingView verileri varsa birleştir
            if tradingview_data is not None and not tradingview_data.empty:
                logger.info(f"{symbol} için TradingView verileri başarıyla alındı")
                
                # İlk timeframe için hesaplanan indikatörlere TradingView verilerini ekle
                if first_tf in multi_tf_indicators:
                    first_tf_indicators = multi_tf_indicators[first_tf]
                    
                    tradingview_indicators = {
                        'recommend_all': tradingview_data['recommend_all'].iloc[0],
                        'recommend_ma': tradingview_data['recommend_ma'].iloc[0],
                        'tv_rsi': tradingview_data['rsi'].iloc[0],
                        'tv_macd': tradingview_data['macd'].iloc[0],
                        'tv_macd_signal': tradingview_data['macd_signal'].iloc[0],
                        'tv_bb_upper': tradingview_data['bb_upper'].iloc[0],
                        'tv_bb_middle': tradingview_data['bb_middle'].iloc[0],
                        'tv_bb_lower': tradingview_data['bb_lower'].iloc[0]
                    }
                    
                    # TradingView sinyallerini ekle
                    first_tf_indicators['tradingview'] = {
                        'recommend_all': tradingview_indicators['recommend_all'],
                        'recommend_ma': tradingview_indicators['recommend_ma'],
                        'signal': 'BUY' if tradingview_indicators['recommend_all'] < -0.2 else ('SELL' if tradingview_indicators['recommend_all'] > 0.2 else 'NEUTRAL')
                    }
                    
                    # Her bir göstergeye TradingView verilerini ekle
                    if 'rsi' in first_tf_indicators:
                        first_tf_indicators['rsi']['tv_value'] = tradingview_indicators['tv_rsi']
                    if 'macd' in first_tf_indicators:
                        first_tf_indicators['macd']['tv_value'] = tradingview_indicators['tv_macd']
                        first_tf_indicators['macd']['tv_signal_line'] = tradingview_indicators['tv_macd_signal']
                    if 'bollinger' in first_tf_indicators:
                        first_tf_indicators['bollinger']['tv_upper'] = tradingview_indicators['tv_bb_upper']
                        first_tf_indicators['bollinger']['tv_middle'] = tradingview_indicators['tv_bb_middle']
                        first_tf_indicators['bollinger']['tv_lower'] = tradingview_indicators['tv_bb_lower']
            
            # Stratejileri uygula
            strategy_results = {}
            
            # 1. Trend takip stratejisi
            if self.config.get('strategies', {}).get('trend_following', {}).get('enabled', True):
                from strategies.trend_following import analyze as trend_following_analyze
                signal, reason = trend_following_analyze(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                strategy_results['trend_following'] = {
                    'signal': signal if signal else 'NEUTRAL',
                    'reason': reason
                }
            
            # 2. Kırılma tespiti stratejisi
            if self.config.get('strategies', {}).get('breakout_detection', {}).get('enabled', True):
                from strategies.breakout_detection import analyze as breakout_detection_analyze
                signal, reason = breakout_detection_analyze(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                strategy_results['breakout_detection'] = {
                    'signal': signal if signal else 'NEUTRAL',
                    'reason': reason
                }
            
            # 3. Kısa vadeli strateji
            if self.config.get('strategies', {}).get('short_term_strategy', {}).get('enabled', False):
                try:
                    from strategies.short_term_strategy import analyze as short_term_analyze
                    signal, reason = short_term_analyze(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                    strategy_results['short_term_strategy'] = {
                        'signal': signal if signal else 'NEUTRAL',
                        'reason': reason
                    }
                except Exception as e:
                    logger.error(f"Short term strategy hatası: {str(e)}")
            
            # 4. Volatilite Kırılma Stratejisi
            if self.config.get('strategies', {}).get('volatility_breakout', {}).get('enabled', True):
                try:
                    from strategies.volatility_breakout import analyze as volatility_breakout_analyze
                    signal, reason = volatility_breakout_analyze(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                    strategy_results['volatility_breakout'] = {
                        'signal': signal if signal else 'NEUTRAL',
                        'reason': reason
                    }
                except Exception as e:
                    logger.error(f"Volatilite kırılma stratejisi hatası: {str(e)}")
            
            # Çoklu zaman aralığı sinyallerini birleştir
            combined_signals = self.indicators_manager.combine_timeframe_signals(multi_tf_indicators, symbol)
            
            # Çoklu zaman aralığı konsensusunu strateji sinyalleriyle birleştir
            buy_signals = combined_signals.get('buy_count', 0) + sum(1 for s in strategy_results.values() if s.get('signal') == 'BUY')
            sell_signals = combined_signals.get('sell_count', 0) + sum(1 for s in strategy_results.values() if s.get('signal') == 'SELL')
            neutral_signals = combined_signals.get('neutral_count', 0) + sum(1 for s in strategy_results.values() if s.get('signal') == 'NEUTRAL')
            
            # Son fiyatı al (ilk timeframe'den)
            last_close = ohlcv_data['close'].iloc[-1]
            
            # Nihai sinyal kararı
            final_signal = combined_signals.get('trade_signal', 'NEUTRAL')
            
            # Eğer hem çoklu zaman aralığı sinyali hem de strateji sinyalleri güçlü bir yön gösteriyorsa
            if buy_signals > sell_signals * 2 and buy_signals > 0:
                final_signal = 'BUY'
            elif sell_signals > buy_signals * 2 and sell_signals > 0:
                final_signal = 'SELL'
            
            # Analiz sonucunu oluştur
            analysis_result = {
                'symbol': symbol,
                'price': last_close,
                'timestamp': pd.Timestamp.now().strftime('%Y-%m-%d %H:%M:%S'),
                'indicators': multi_tf_indicators.get(first_tf, {}),  # İlk timeframe'in indikatörlerini kullan
                'strategies': strategy_results,
                'signals': {
                    'buy_count': buy_signals,
                    'sell_count': sell_signals,
                    'neutral_count': neutral_signals
                },
                'trade_signal': final_signal,
                'multi_timeframe': {
                    'timeframes': list(multi_tf_data.keys()),
                    'combined_signal': combined_signals
                }
            }
            
            # Veritabanına kaydet
            self.save_analysis_to_db(analysis_result)
            
            return analysis_result
            
        except Exception as e:
            logger.error(f"{symbol} için analiz yapılırken hata: {str(e)}")
            return None

    def execute_trade(self, symbol, signal_type, analysis):
        """
        Alım veya satım sinyaline göre işlem gerçekleştirir
        
        :param symbol: Coin sembolü
        :param signal_type: Sinyal türü ('BUY' veya 'SELL')
        :param analysis: Analiz sonuçları
        :return: İşlem başarılı mı
        """
        try:
            # İşlem türünü (paper/live) kontrol et
            trade_mode = self.config.get('trade_mode', 'paper')
            if trade_mode not in ['paper', 'live']:
                logger.info(f"İşlem yapılmıyor: Geçerli olmayan işlem türü {trade_mode}. 'paper' veya 'live' olmalı.")
                return False
                
            # Auto-trade ayarını kontrol et
            if not self.config.get('auto_trade', False):
                logger.info(f"Otomatik işlem kapalı olduğu için {symbol} için {signal_type} işlemi yapılmıyor.")
                return False
                
            # Sembol için açık pozisyon var mı kontrol et
            open_position = next((p for p in self.open_positions if p['symbol'] == symbol), None)
            
            # ALIM SİNYALİ: Açık pozisyon yoksa ve BUY sinyali ise yeni pozisyon aç
            if signal_type == 'BUY' and open_position is None:
                return self.open_position(symbol, analysis)
                
            # SATIM SİNYALİ: Açık pozisyon varsa ve SELL sinyali ise pozisyonu kapat
            elif signal_type == 'SELL' and open_position is not None:
                current_price = analysis['price']
                return self.close_position(open_position, 'signal', current_price)
                
            # Diğer durumlar - işlem yapılmadı
            return False
            
        except Exception as e:
            logger.error(f"İşlem gerçekleştirilirken hata: {str(e)}")
            return False

    def open_position(self, symbol, analysis):
        """
        Yeni bir alım pozisyonu açar
        
        :param symbol: Coin sembolü
        :param analysis: Analiz sonuçları
        :return: İşlem başarılı mı
        """
        try:
            # Açık pozisyon sayısını kontrol et
            max_open_positions = self.config.get('max_open_trades', 3)
            if len(self.open_positions) >= max_open_positions:
                logger.warning(f"Maksimum açık pozisyon sayısına ulaşıldı ({max_open_positions}). {symbol} için alım yapılmıyor.")
                return False
                
            current_price = analysis['price']
            
            # Pozisyon büyüklüğünü hesapla
            trade_amount = self.config.get('trade_amount', 10.0)
            position_size = self.config.get('position_size', 0.1)  # Bakiyenin yüzdesi
            
            # İşlem modu kontrolü
            trade_mode = self.config.get('trade_mode', 'spot')
            
            # Kaldıraç kullan - varsayılan 1x (kaldıraç yok)
            leverage = self.config.get('leverage', 1)
            leverage_mode = self.config.get('leverage_mode', 'cross')
            
            # Bakiye kontrolü (yalnızca canlı modda)
            if self.config.get('trade_mode') == 'live':
                try:
                    # Futures modu için özel işlemler
                    if trade_mode == 'futures':
                        logger.info(f"Futures modunda işlem gerçekleştiriliyor. Symbol: {symbol}, Kaldıraç: {leverage}x")
                        
                        try:
                            # Futures API'sini yükle
                            if hasattr(self.exchange, 'fapiPrivatePostLeverage'):
                                # Kaldıracı ayarla (Binance için)
                                self.exchange.fapiPrivatePostLeverage({
                                    'symbol': symbol.replace('/', ''),  # Sembol formatını düzelt BTC/USDT -> BTCUSDT
                                    'leverage': leverage
                                })
                                logger.info(f"Kaldıraç {leverage}x olarak ayarlandı")
                                
                                # Pozisyon modunu ayarla (cross veya isolated)
                                self.exchange.fapiPrivatePostMarginType({
                                    'symbol': symbol.replace('/', ''),
                                    'marginType': leverage_mode.upper()
                                })
                                logger.info(f"Marjin tipi {leverage_mode} olarak ayarlandı")
                            else:
                                # Alternatif yöntem ile kaldıraç ayarlama 
                                try:
                                    self.exchange.set_leverage(leverage, symbol)
                                    self.exchange.set_margin_mode(leverage_mode, symbol)
                                    logger.info(f"Kaldıraç {leverage}x ve marjin tipi {leverage_mode} olarak ayarlandı")
                                except Exception as leverage_error:
                                    logger.error(f"Kaldıraç ayarlanırken hata: {str(leverage_error)}")
                        except Exception as futures_setup_error:
                            logger.error(f"Futures ayarları yapılırken hata: {str(futures_setup_error)}")
                    
                    # Bakiyeyi kontrol et (Futures/Spot/Marjin)
                    if trade_mode == 'futures':
                        # Futures hesabı bakiyesi
                        balance = self.exchange.fetch_balance({'type': 'future'})
                    elif trade_mode == 'margin':
                        # Marjin hesabı bakiyesi
                        balance = self.exchange.fetch_balance({'type': 'margin'})
                    else:
                        # Spot hesabı bakiyesi (varsayılan)
                        balance = self.exchange.fetch_balance()
                        
                    base_currency = self.config.get('base_currency', 'USDT')
                    available = float(balance[base_currency]['free']) if base_currency in balance else 0
                    
                    # Kullanılabilir bakiye yoksa uyarı ver
                    if available < trade_amount:
                        logger.warning(f"Yetersiz bakiye: {available} {base_currency} (gerekli: {trade_amount} {base_currency})")
                        
                        # Telegram bildirimi gönder
                        if self.config.get('use_telegram', False):
                            message = f"⚠️ *Yetersiz Bakiye Uyarısı*\n\n"
                            message += f"İşlem yapılamadı: {symbol}\n"
                            message += f"Mevcut bakiye: `{available:.2f} {base_currency}`\n"
                            message += f"Gerekli miktar: `{trade_amount:.2f} {base_currency}`\n"
                            self.send_telegram_message(message)
                            
                        return False
                    
                    # İşlem yapma kısmı (MARKET emri ile)
                    if trade_mode == 'futures':
                        # Futures işlemi
                        order_params = {
                            'symbol': symbol,
                            'type': 'MARKET',
                            'side': 'BUY',
                            'quantity': trade_amount / current_price * leverage  # Kaldıraçlı miktar
                        }
                        
                        # Futures için sipariş ver
                        try:
                            if hasattr(self.exchange, 'create_order'):
                                # Standart CCXT metodu
                                order = self.exchange.create_order(
                                    symbol=symbol,
                                    type='market',
                                    side='buy',
                                    amount=trade_amount / current_price * leverage,
                                    params={'leverage': leverage}
                                )
                            else:
                                # Alternatif Binance futures metodu
                                order = self.exchange.fapiPrivatePostOrder(order_params)
                                
                            logger.info(f"FUTURES ALIM: {symbol} başarıyla alındı. Miktar: {trade_amount / current_price * leverage}, Kaldıraç: {leverage}x")
                        except Exception as futures_order_error:
                            logger.error(f"Futures alım emri verilirken hata: {str(futures_order_error)}")
                            return False
                    elif trade_mode == 'margin':
                        # Marjin işlemi 
                        try:
                            order = self.exchange.create_market_buy_order(
                                symbol=symbol,
                                amount=trade_amount / current_price,
                                params={'type': 'margin'}
                            )
                            logger.info(f"MARJİN ALIM: {symbol} başarıyla alındı. Miktar: {trade_amount / current_price}")
                        except Exception as margin_order_error:
                            logger.error(f"Marjin alım emri verilirken hata: {str(margin_order_error)}")
                            return False
                    else:
                        # Normal spot işlemi
                        order = self.exchange.create_market_buy_order(
                            symbol=symbol,
                            amount=trade_amount / current_price  # Miktar = USDT miktarı / fiyat
                        )
                        logger.info(f"SPOT ALIM: {symbol} başarıyla alındı. Miktar: {trade_amount / current_price}, Fiyat: {current_price:.4f}")
                    
                except Exception as e:
                    logger.error(f"Alım işlemi sırasında hata: {str(e)}")
                    return False
            
            # Stop-loss ve take-profit hesapla
            stop_loss_pct = self.config.get('stop_loss_pct', 2.0)  # %2 stop-loss
            take_profit_pct = self.config.get('take_profit_pct', 3.0)  # %3 take-profit
            
            stop_loss = round(current_price * (1 - stop_loss_pct / 100), 8)
            take_profit = round(current_price * (1 + take_profit_pct / 100), 8)
            
            # Yeni pozisyon oluştur
            position = {
                'symbol': symbol,
                'type': 'LONG',
                'entry_price': current_price,
                'amount': trade_amount / current_price,
                'entry_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                'stop_loss': stop_loss,
                'take_profit': take_profit,
                'strategy': self.get_top_strategy_from_analysis(analysis),
                'leverage': leverage,
                'trade_mode': trade_mode,  # İşlem modunu kaydet
                'notes': f"Otomatik alım: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}"
            }
            
            # Pozisyonlar listesine ekle
            self.open_positions.append(position)
            
            # Veritabanına kaydet
            self.save_position(position)
            
            # Telegram ile bildirim gönder (eğer etkinleştirilmişse)
            if self.config.get('use_telegram', False):
                message = f"🟢 *Yeni Pozisyon Açıldı*\n"
                message += f"Sembol: `{symbol}`\n"
                message += f"İşlem Modu: `{trade_mode.upper()}`\n"
                message += f"Fiyat: `{current_price:.4f}`\n"
                message += f"Stop-Loss: `{stop_loss:.4f}`\n"
                message += f"Take-Profit: `{take_profit:.4f}`\n"
                message += f"Miktar: `{position['amount']:.6f}`\n"
                
                # Futures veya marjin ise kaldıraç bilgisini ekle
                if trade_mode in ['futures', 'margin']:
                    message += f"Kaldıraç: `{leverage}x`\n"
                    
                message += f"Mod: `{'TEST' if self.config.get('trade_mode') == 'paper' else 'CANLI'}`"
                
                self.send_telegram_message(message)
            
            return True
            
        except Exception as e:
            logger.error(f"Pozisyon açılırken hata: {str(e)}")
            return False

    def get_top_strategy_from_analysis(self, analysis):
        """
        Analizden en güçlü sinyal veren stratejiyi belirler
        
        :param analysis: Analiz sonuçları
        :return: En güçlü strateji adı
        """
        if 'strategies' not in analysis:
            return "unknown"
            
        buy_strategies = []
        for strategy_name, strategy_data in analysis.get('strategies', {}).items():
            if strategy_data.get('signal') == 'BUY':
                buy_strategies.append(strategy_name)
                
        return buy_strategies[0] if buy_strategies else "combined_signals"

    def get_active_coins(self):
        """
        İzlenecek aktif coinleri getir
        """
        try:
            # Konfigürasyondan coinleri al
            if 'coins' in self.config and self.config['coins']:
                logger.info(f"Konfigürasyondan {len(self.config['coins'])} coin alındı")
                return [{'symbol': symbol} for symbol in self.config['coins']]
            
            # Veritabanından aktif coinleri çek
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor(dictionary=True)
            
            # active_coins tablosunu kullan (coins tablosu yerine)
            query = """
            SELECT symbol, last_updated
            FROM active_coins
            ORDER BY last_updated DESC
            """
            
            try:
                cursor.execute(query)
                coins = cursor.fetchall()
                
                # Eğer coinler boşsa, otomatik olarak birkaç popüler coin ekleyelim
                if not coins:
                    logger.warning("Veritabanında aktif coin bulunamadı, varsayılan coinler kullanılacak")
                    default_coins = [
                        {"symbol": "BTC/USDT"}, 
                        {"symbol": "ETH/USDT"}, 
                        {"symbol": "BNB/USDT"},
                        {"symbol": "SOL/USDT"},
                        {"symbol": "ADA/USDT"}
                    ]
                    return default_coins
                
                logger.info(f"Veritabanından {len(coins)} aktif coin alındı")
                return coins
                
            except Exception as db_error:
                logger.error(f"Veritabanı sorgusu hatası: {str(db_error)}")
                
                # Tablo yoksa varsayılan coinleri döndür
                logger.info("Varsayılan coinler kullanılacak")
                default_coins = [
                    {"symbol": "BTC/USDT"}, 
                    {"symbol": "ETH/USDT"}, 
                    {"symbol": "BNB/USDT"},
                    {"symbol": "SOL/USDT"},
                    {"symbol": "ADA/USDT"}
                ]
                return default_coins
                
            finally:
                cursor.close()
                conn.close()
            
        except Exception as e:
            # Hata detayını günlüğe kaydet
            logger.error(f"Aktif coinler alınırken hata: {str(e)}")
            
            # Hata durumunda varsayılan coinleri döndür
            logger.info("Hata nedeniyle varsayılan coinler kullanılacak")
            default_coins = [
                {"symbol": "BTC/USDT"}, 
                {"symbol": "ETH/USDT"}, 
                {"symbol": "BNB/USDT"},
                {"symbol": "SOL/USDT"},
                {"symbol": "ADA/USDT"}
            ]
            return default_coins

    def start_coin_discovery(self):
        """
        Coin keşfetme işlemini başlatır (ayrı bir thread'de)
        """
        def discovery_loop():
            """Coin keşfetme işlemi için iç fonksiyon"""
            logger.info("Otomatik coin keşfetme sistemi başlatıldı...")
            last_settings_refresh = 0
            
            while not self.stop_event.is_set():
                try:
                    # Veritabanından ayarları yenile (60 saniyede bir)
                    current_time = time.time()
                    if current_time - last_settings_refresh > 60:
                        self.load_settings_from_db()
                        logger.info(f"Bot keşif ayarları yenilendi. Discovery Interval: {self.config.get('discovery_interval', 3600)} saniye")
                        last_settings_refresh = current_time
                    
                    # Keşif aralığını yenilenmiş config'den al
                    discovery_interval = 0
                    if 'auto_discovery' in self.config and 'discovery_interval' in self.config['auto_discovery']:
                        discovery_interval = self.config['auto_discovery']['discovery_interval']
                    else:  
                        discovery_interval = self.config.get('discovery_interval', 3600)  # Varsayılan: her saat
                    
                    # Potansiyel coinleri keşfet
                    discovered = self.discover_potential_coins()
                    logger.info(f"{len(discovered)} adet yüksek potansiyelli coin keşfedildi ve izlemeye alındı.")
                    
                    # Telegram ile bildirim gönder (eğer etkinleştirilmişse)
                    if discovered and self.config.get('use_telegram', False):
                        message = f"🔍 *Yeni Potansiyel Coinler Keşfedildi*\n\n"
                        for coin in discovered[:10]:  # En iyi 10 tanesini göster
                            symbol = coin['symbol']
                            price_change = coin['price_change_pct']
                            price = coin['last_price']
                            message += f"• {symbol}: ${price:.4f} ({price_change:+.2f}%)\n"
                        
                        self.send_telegram_message(message)
                    
                    # Bir sonraki keşfe kadar bekle
                    logger.info(f"Bir sonraki coin keşfine kadar {discovery_interval/60:.1f} dakika bekleniyor...")
                    
                    # Bot durma kontrolü için tarama aralığını parçalara böl
                    for _ in range(max(1, int(discovery_interval / 60))):
                        if self.stop_event.is_set():
                            break
                        time.sleep(60)  # 1 dakika bekle ve kontrol et
                        
                except Exception as e:
                    logger.error(f"Coin keşfetme döngüsünde hata: {str(e)}")
                    time.sleep(60)  # Hata durumunda 1 dakika bekle
            
        self.discovery_thread = threading.Thread(target=discovery_loop)
        self.discovery_thread.start()

    def save_discovered_coins_to_db(self):
        """
        Keşfedilmiş coinleri veritabanına kaydeder
        """
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
                            buy_signals = %s,
                            sell_signals = %s,
                            trade_signal = %s,
                            is_active = 1,
                            last_updated = NOW()
                        WHERE symbol = %s
                        """
                        cursor.execute(update_query, (
                            discovery_time,
                            float(coin['last_price']),
                            float(coin['volume_usd']),
                            float(coin['price_change_pct']),
                            coin['analysis'].get('buy_signals', 0) if 'analysis' in coin else 0,
                            coin['analysis'].get('sell_signals', 0) if 'analysis' in coin else 0,
                            coin['analysis'].get('trade_signal', 'NEUTRAL') if 'analysis' in coin else 'NEUTRAL',
                            symbol
                        ))
                    else:
                        # Yeni ekle
                        insert_query = """
                        INSERT INTO discovered_coins (
                            symbol, discovery_time, price, volume_usd, price_change_pct,
                            buy_signals, sell_signals, trade_signal, is_active
                        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1)
                        """
                        cursor.execute(insert_query, (
                            symbol,
                            discovery_time,
                            float(coin['last_price']),
                            float(coin['volume_usd']),
                            float(coin['price_change_pct']),
                            coin['analysis'].get('buy_signals', 0) if 'analysis' in coin else 0,
                            coin['analysis'].get('sell_signals', 0) if 'analysis' in coin else 0,
                            coin['analysis'].get('trade_signal', 'NEUTRAL') if 'analysis' in coin else 'NEUTRAL'
                        ))
                    
                    conn.commit()
                    coins_saved += 1
                    logger.debug("%s coini başarıyla işlendi" % symbol)
                    
                except Exception as coin_error:
                    logger.error("Coin işlenirken hata (%s): %s" % (str(symbol), str(coin_error)))
                    continue
                    
            logger.info("%d adet keşfedilmiş coin veritabanına kaydedildi." % coins_saved)
            
        except Exception as e:
            logger.error("Keşfedilen coinler veritabanına kaydedilirken hata: %s" % str(e))
            
        finally:
            try:
                cursor.close()
                conn.close()
            except:
                pass

    def send_telegram_message(self, message):
        """
        Telegram üzerinden mesaj gönderir
        
        :param message: Gönderilecek mesaj
        :return: Başarı durumu
        """
        try:
            if not self.config.get('use_telegram', False):
                logger.debug("Telegram bildirimi devre dışı.")
                return False
                
            token = self.api_keys.get('telegram_token', '')
            chat_id = self.api_keys.get('telegram_chat_id', '')
            
            if not token or not chat_id:
                logger.warning("Telegram token veya chat ID bulunamadı.")
                return False
                
            # Telegram bot nesnesi oluştur
            bot = telegram.Bot(token=token)
            
            # Mesajı gönder
            bot.send_message(
                chat_id=chat_id,
                text=message,
                parse_mode=telegram.ParseMode.MARKDOWN
            )
            
            return True
            
        except Exception as e:
            logger.error(f"Telegram mesajı gönderilirken hata: {str(e)}")
            return False

    def save_analysis_to_db(self, analysis):
        """
        Analiz sonuçlarını MySQL veritabanına kaydet
        
        :param analysis: Analiz sonuçları 
        :return: Başarı durumu
        """
        try:
            # Analiz yoksa işlem yapma
            if not analysis:
                logger.warning("Kaydedilecek analiz sonucu yok.")
                return False
                
            symbol = analysis['symbol']
            
            # Veritabanı bağlantısı
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # price_analysis tablosunun var olup olmadığını kontrol et
            try:
                cursor.execute("SELECT 1 FROM price_analysis LIMIT 1")
                # Sorgunun sonucunu oku - okunmamış sonuçları temizle
                cursor.fetchall()
            except Exception:
                # Tablo yoksa oluştur
                create_table_query = """
                CREATE TABLE IF NOT EXISTS price_analysis (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(20) NOT NULL,
                    analysis_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    price DECIMAL(20, 8) NOT NULL,
                    rsi DECIMAL(10, 2),
                    macd DECIMAL(20, 8),
                    macd_signal DECIMAL(20, 8),
                    bollinger_upper DECIMAL(20, 8),
                    bollinger_middle DECIMAL(20, 8),
                    bollinger_lower DECIMAL(20, 8),
                    ma20 DECIMAL(20, 8),
                    ma50 DECIMAL(20, 8),
                    ma100 DECIMAL(20, 8),
                    ma200 DECIMAL(20, 8),
                    trade_signal VARCHAR(10),
                    buy_signals INT,
                    sell_signals INT,
                    neutral_signals INT,
                    notes TEXT
                )
                """
                cursor.execute(create_table_query)
                conn.commit()
                logger.info("price_analysis tablosu oluşturuldu")
            
            # Analiz verilerini hazırla
            timestamp = analysis.get('timestamp', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
            price = analysis.get('price', 0)
            
            # İndikatör verilerini al
            indicators = analysis.get('indicators', {})
            
            # RSI
            rsi_value = indicators.get('rsi', {}).get('value', None)
            
            # MACD
            macd_value = indicators.get('macd', {}).get('value', None)
            macd_signal = indicators.get('macd', {}).get('signal_line', None)
            
            # Bollinger Bands
            bb_upper = indicators.get('bollinger', {}).get('upper', None)
            bb_middle = indicators.get('bollinger', {}).get('middle', None)
            bb_lower = indicators.get('bollinger', {}).get('lower', None)
            
            # Hareketli Ortalamalar
            ma20 = indicators.get('moving_averages', {}).get('ma20', None)
            ma50 = indicators.get('moving_averages', {}).get('ma50', None)
            ma100 = indicators.get('moving_averages', {}).get('ma100', None)
            ma200 = indicators.get('moving_averages', {}).get('ma200', None)
            
            # Sinyal istatistikleri
            trade_signal = analysis.get('trade_signal', 'NEUTRAL')
            buy_signals = analysis.get('signals', {}).get('buy_count', 0)
            sell_signals = analysis.get('signals', {}).get('sell_count', 0)
            neutral_signals = analysis.get('signals', {}).get('neutral_count', 0)
            
            # Strateji notları
            strategy_notes = []
            for strategy_name, strategy_data in analysis.get('strategies', {}).items():
                if strategy_data.get('signal') != 'NEUTRAL':
                    reason = strategy_data.get('reason', 'Belirtilmemiş')
                    strategy_notes.append(f"{strategy_name}: {strategy_data.get('signal')} - {reason}")
            
            notes = " | ".join(strategy_notes) if strategy_notes else "Strateji notu yok"
            
            # Veritabanına ekle veya güncelle
            # Son 1 saat içinde aynı sembol için analiz var mı kontrol et
            cursor.execute(
                "SELECT id FROM price_analysis WHERE symbol = %s AND analysis_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY analysis_time DESC LIMIT 1", 
                (symbol,)
            )
            existing = cursor.fetchone()
            
            if existing:
                # Son 1 saat içinde yapılan analizi güncelle
                update_query = """
                UPDATE price_analysis SET 
                    analysis_time = %s,
                    price = %s,
                    rsi = %s,
                    macd = %s,
                    macd_signal = %s,
                    bollinger_upper = %s,
                    bollinger_middle = %s,
                    bollinger_lower = %s,
                    ma20 = %s,
                    ma50 = %s,
                    ma100 = %s,
                    ma200 = %s,
                    trade_signal = %s,
                    buy_signals = %s,
                    sell_signals = %s,
                    neutral_signals = %s,
                    notes = %s
                WHERE id = %s
                """
                
                cursor.execute(update_query, (
                    timestamp,
                    price,
                    rsi_value,
                    macd_value,
                    macd_signal,
                    bb_upper,
                    bb_middle,
                    bb_lower,
                    ma20,
                    ma50,
                    ma100,
                    ma200,
                    trade_signal,
                    buy_signals,
                    sell_signals,
                    neutral_signals,
                    notes,
                    existing[0]
                ))
            else:
                # Yeni kayıt ekle
                insert_query = """
                INSERT INTO price_analysis (
                    symbol, analysis_time, price, rsi, macd, macd_signal,
                    bollinger_upper, bollinger_middle, bollinger_lower,
                    ma20, ma50, ma100, ma200,
                    trade_signal, buy_signals, sell_signals, neutral_signals, notes
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """
                
                cursor.execute(insert_query, (
                    symbol,
                    timestamp,
                    price,
                    rsi_value,
                    macd_value,
                    macd_signal,
                    bb_upper,
                    bb_middle,
                    bb_lower,
                    ma20,
                    ma50,
                    ma100,
                    ma200,
                    trade_signal,
                    buy_signals,
                    sell_signals,
                    neutral_signals,
                    notes
                ))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            logger.debug(f"{symbol} için analiz sonuçları başarıyla veritabanına kaydedildi.")
            return True
            
        except Exception as e:
            logger.error(f"Analiz sonuçları veritabanına kaydedilirken hata: {str(e)}")
            # Hata durumunda bağlantıyı kapatmayı dene
            try:
                if 'cursor' in locals() and cursor:
                    cursor.close()
                if 'conn' in locals() and conn:
                    conn.close()
            except:
                pass
            return False

    def start(self):
        """
        Bot'u başlatır
        """
        self.stop_event.clear()
        self.monitor_thread = threading.Thread(target=self.monitor_coins)
        self.monitor_thread.start()
        self.start_coin_discovery()
        logger.info("Bot başlatıldı ve izleme thread'i çalışıyor")

    def stop(self):
        """
        Bot'u durdurur
        """
        self.stop_event.set()
        self.monitor_thread.join()
        if self.discovery_thread:
            self.discovery_thread.join()
        logger.info("Bot durduruldu")

if __name__ == "__main__":
    bot = TradingBot()
    bot.start()
    
    # Ctrl+C ile durdurma
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        bot.stop()
        print("Bot durduruldu")
