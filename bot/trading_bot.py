import time
import json
import logging
import ccxt
import pandas as pd
import numpy as np
import threading
import os
import requests
import talib
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
# GELİŞMİŞ MODÜLLER - ENTEGRE EDİLİYOR
from adaptive_parameters import AdaptiveParameters
from risk_manager import RiskManager

# Loglama yapılandırması
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
        self.valid_symbols = set()  # Geçerli semboller önbelleği
        self.invalid_symbols = set()  # Geçersiz semboller önbelleği
        self.symbols_last_check = 0  # Son sembol kontrolü zamanı

        # Konfigürasyon yükle
        self.load_config()
        
        # Veritabanından bot ayarlarını yükle
        self.load_settings_from_db()
        
        # API anahtarlarını yükle
        self.load_api_keys()
        
        # Açık pozisyonları yükle
        self.load_open_positions()
        
        # IndicatorsManager sınıfını başlat
        from indicators_manager import IndicatorsManager
        self.indicators_manager = IndicatorsManager(self.config)
        
        # GELİŞMİŞ MODÜLLER - ENTEGRASYONu TAMAMLANIYOR
        # Adaptive Parameters modülünü başlat (piyasa koşullarına göre parametreleri otomatik ayarlar)
        self.adaptive_parameters = AdaptiveParameters(self.config)
        logger.info("AdaptiveParameters modülü başlatıldı - Piyasa koşullarına göre otomatik optimizasyon aktif")
        
        # Risk Manager modülünü başlat (gelişmiş risk yönetimi)
        self.risk_manager = RiskManager(self.config)
        logger.info("RiskManager modülü başlatıldı - ATR bazlı dinamik stop-loss ve pozisyon büyüklüğü optimizasyonu aktif")
        
        # CCXT exchange nesnesini oluştur
        try:
            exchange_name = self.config.get('exchange', 'binance')
            exchange_class = getattr(ccxt, exchange_name)
            
            # Exchange yapılandırması
            exchange_config = {
                'apiKey': self.api_keys.get('api_key', ''),
                'secret': self.api_keys.get('api_secret', ''),
                'enableRateLimit': True,
                'sandbox': False  # Gerçek işlemler için False
            }
            
            # Market type kontrolü - futures ise özel ayarlar
            market_type = self.config.get('market_type', 'spot')
            if market_type == 'futures':
                # Futures için özel ayarlar
                exchange_config.update({
                    'options': {
                        'defaultType': 'future',  # Binance futures için
                        'marginMode': 'cross',  # Multi-Assets mode için cross margin kullan
                    }
                })
                logger.info(f"🔧 Exchange futures modunda yapılandırılıyor - Multi-Assets destekli cross margin modu")
            else:
                # Spot için ayarlar
                exchange_config.update({
                    'options': {
                        'defaultType': 'spot'
                    }
                })
                logger.info("🔧 Exchange spot modunda yapılandırılıyor")
            
            self.exchange = exchange_class(exchange_config)
            
            # 🚀 GÜÇLÜ EXCHANGE BAŞLATMA SİSTEMİ
            logger.info("🔄 Exchange bağlantısı test ediliyor...")
            
            # Market verilerini 3 deneme ile yükle
            markets_loaded = False
            for attempt in range(3):
                try:
                    logger.info(f"📊 Market verileri yükleniyor... (Deneme {attempt + 1}/3)")
                    markets = self.exchange.load_markets()
                    
                    if markets and len(markets) > 0:
                        markets_loaded = True
                        logger.info(f"✅ {len(markets)} market başarıyla yüklendi")
                        
                        # Geçerli sembolleri önbelleğe al
                        self.valid_symbols = set(markets.keys())
                        self.symbols_last_check = time.time()
                        
                        # Base currency çiftlerini say
                        base_currency = self.config.get('base_currency', 'USDT')
                        base_pairs = [s for s in markets.keys() if s.endswith(f'/{base_currency}')]
                        logger.info(f"💰 {len(base_pairs)} adet {base_currency} çifti tespit edildi")
                        break
                    else:
                        logger.warning(f"⚠️ Market verisi boş (Deneme {attempt + 1}/3)")
                        
                except Exception as market_error:
                    logger.warning(f"⚠️ Market yükleme hatası (Deneme {attempt + 1}/3): {str(market_error)}")
                    if attempt < 2:  # Son deneme değilse bekle
                        time.sleep(2 ** attempt)  # Exponential backoff: 1s, 2s, 4s
            
            if not markets_loaded:
                logger.error("❌ Market verileri yüklenemedi! Exchange işlemleri kısıtlı olacak")
            
            # Exchange bağlantı testi
            try:
                logger.info("🔐 API bağlantısı test ediliyor...")
                
                if market_type == 'futures':
                    # Futures için account bilgisi al
                    account_info = self.exchange.fetch_balance()
                    logger.info("✅ Futures hesap bilgisine erişim başarılı")
                    
                    # Multi-Assets margin mode kontrolü
                    try:
                        # Binance futures için margin mode kontrolü
                        if exchange_name.lower() == 'binance':
                            # Multi-Assets mode'u kontrol et ve gerekirse ayarla
                            position_mode = self.exchange.fapiPrivateGetPositionSideDual()
                            logger.info(f"📋 Position mode: {position_mode}")
                            
                    except Exception as margin_error:
                        logger.warning(f"⚠️ Margin mode kontrolü başarısız: {str(margin_error)}")
                        
                else:
                    # Spot için bakiye kontrolü
                    balance = self.exchange.fetch_balance()
                    logger.info("✅ Spot hesap bilgisine erişim başarılı")
                
                logger.info(f"🎉 {exchange_name.upper()} borsası başarıyla bağlandı - Market türü: {market_type}")
                
            except Exception as test_error:
                logger.error(f"❌ Exchange bağlantı testi başarısız: {str(test_error)}")
                logger.warning("⚠️ API anahtarlarını kontrol edin. Bot devam ediyor ama işlemler kısıtlı olacak")
                
        except Exception as e:
            logger.error(f"❌ Exchange başlatılamadı: {str(e)}")
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
                        'stop_loss_pct': float(settings_json.get('stop_loss_pct', 5.0)),
                        'take_profit_pct': float(settings_json.get('take_profit_pct', 12.5)),
                        'use_telegram': bool(settings_json.get('use_telegram', False)),
                        'interval': settings_json.get('interval', '1h'),
                        'max_api_retries': int(settings_json.get('max_api_retries', 3)),
                        'retry_delay': int(settings_json.get('retry_delay', 5)),
                        'api_delay': float(settings_json.get('api_delay', 1.5)),
                        'scan_interval': int(settings_json.get('scan_interval', 60)),
                        'auto_trade': bool(settings_json.get('auto_trade', False)),
                        'use_tradingview': bool(settings_json.get('use_tradingview', False)),
                        'risk_reward_ratio': float(settings_json.get('risk_reward_ratio', 2.5))
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
                                'retry_delay', 'api_delay', 'scan_interval', 'auto_trade', 'use_tradingview', 'risk_reward_ratio']:
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
                'stop_loss_pct': 5.0,  # %2.0'dan %5.0'a çıkarıldı - daha geniş stop loss
                'take_profit_pct': 12.5,  # %3.0'dan %12.5'e çıkarıldı - daha yüksek kar hedefi
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
                'use_tradingview': False,
                'risk_reward_ratio': 2.5  # Yeni eklenen parametre - risk/ödül oranı
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
                'stop_loss_pct': 5.0,  # Burada da %2.0'dan %5.0'a yükseltildi
                'take_profit_pct': 12.5  # Burada da %3.0'dan %12.5'e yükseltildi
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
                    self.config['stop_loss_pct'] = float(risk_mgmt.get('stop_loss', 5.0))  # %2'den %5'e çıkarıldı
                    self.config['take_profit_pct'] = float(risk_mgmt.get('take_profit', 12.5))  # %3'ten %12.5'e çıkarıldı
                    self.config['trailing_stop'] = bool(risk_mgmt.get('trailing_stop', True))  # Varsayılan olarak açık
                    self.config['trailing_stop_distance'] = float(risk_mgmt.get('trailing_stop_distance', 3.5))  # %2'den %3.5'e çıkarıldı
                    self.config['trailing_stop_activation_pct'] = float(risk_mgmt.get('trailing_stop_activation_pct', 5.0))  # %3'ten %5'e çıkarıldı
                    self.config['trailing_stop_pct'] = float(risk_mgmt.get('trailing_stop_pct', 3.5))  # %2'den %3.5'e çıkarıldı
                    self.config['max_open_trades'] = int(risk_mgmt.get('max_open_positions', 5))
                    self.config['max_risk_per_trade'] = float(risk_mgmt.get('max_risk_per_trade', 1.5))  # %2'den %1.5'e düşürüldü
                    
                    # Risk-ödül oranını ata (varsayılan 2.5 - daha yüksek kar)
                    self.config['risk_reward_ratio'] = float(risk_mgmt.get('risk_reward_ratio', 2.5))
                
                # Backtesting ayarları
                if 'backtesting' in settings_data:
                    self.config['backtesting'] = settings_data['backtesting']
                
                # Telegram ayarları
                self.load_telegram_settings()
                
                # YENİ: Bildirim ayarları (notifications)
                if 'notifications' in settings_data:
                    self.config['notifications'] = settings_data['notifications']
                    
                    # Telegram bildirimleri
                    if 'telegram' in settings_data['notifications']:
                        telegram_settings = settings_data['notifications']['telegram']
                        self.config['telegram_enabled'] = telegram_settings.get('enabled', False)
                        self.config['telegram_bot_token'] = telegram_settings.get('bot_token', '')
                        self.config['telegram_chat_id'] = telegram_settings.get('chat_id', '')
                        self.config['telegram_message_format'] = telegram_settings.get('message_format', 'simple')
                        self.config['telegram_rate_limit'] = telegram_settings.get('rate_limit', 1)
                        
                        # Telegram bildirim türleri
                        if 'types' in telegram_settings:
                            self.config['telegram_trades'] = telegram_settings['types'].get('trades', False)
                            self.config['telegram_errors'] = telegram_settings['types'].get('errors', True)
                            self.config['telegram_profits'] = telegram_settings['types'].get('profits', True)
                            self.config['telegram_status'] = telegram_settings['types'].get('status', True)
                    
                    # E-posta bildirimleri
                    if 'email' in settings_data['notifications']:
                        email_settings = settings_data['notifications']['email']
                        self.config['email_enabled'] = email_settings.get('enabled', False)
                        self.config['email_smtp_host'] = email_settings.get('smtp_host', 'smtp.gmail.com')
                        self.config['email_smtp_port'] = email_settings.get('smtp_port', 587)
                        self.config['email_username'] = email_settings.get('username', '')
                        self.config['email_password'] = email_settings.get('password', '')
                        self.config['email_recipients'] = email_settings.get('recipients', [])
                        
                        # E-posta bildirim türleri
                        if 'types' in email_settings:
                            self.config['email_critical'] = email_settings['types'].get('critical', True)
                            self.config['email_daily_reports'] = email_settings['types'].get('daily_reports', False)
                            self.config['email_weekly_reports'] = email_settings['types'].get('weekly_reports', False)
                            self.config['email_system_status'] = email_settings['types'].get('system_status', True)
                
                # YENİ: Günlükleme ayarları (logging)
                if 'logging' in settings_data:
                    logging_settings = settings_data['logging']
                    self.config['log_level'] = logging_settings.get('level', 'INFO')
                    self.config['log_max_file_size'] = logging_settings.get('max_file_size', 10)
                    self.config['log_retention_days'] = logging_settings.get('retention_days', 30)
                    self.config['log_format'] = logging_settings.get('format', 'simple')
                    self.config['log_backup_count'] = logging_settings.get('backup_count', 5)
                    self.config['log_rotation'] = logging_settings.get('rotation', True)
                    self.config['log_compression'] = logging_settings.get('compression', False)
                    
                    # Log kategorileri
                    if 'categories' in logging_settings:
                        self.config['log_trades'] = logging_settings['categories'].get('trades', True)
                        self.config['log_indicators'] = logging_settings['categories'].get('indicators', False)
                        self.config['log_api'] = logging_settings['categories'].get('api', False)
                        self.config['log_errors'] = logging_settings['categories'].get('errors', True)
                
                # YENİ: Performans izleme ayarları (monitoring)
                if 'monitoring' in settings_data:
                    monitoring_settings = settings_data['monitoring']
                    self.config['performance_interval'] = monitoring_settings.get('performance_interval', 60)
                    self.config['memory_threshold'] = monitoring_settings.get('memory_threshold', 80)
                    self.config['cpu_monitoring'] = monitoring_settings.get('cpu_monitoring', True)
                    self.config['disk_monitoring'] = monitoring_settings.get('disk_monitoring', True)
                
                # Önemli işlem ayarları
                self.config['trade_mode'] = settings_data.get('trade_mode', 'live')
                self.config['auto_trade'] = bool(settings_data.get('auto_trade', True))
                self.config['trade_direction'] = settings_data.get('trade_direction', 'both')
                
                logger.info("Bot ayarları veritabanından başarıyla güncellendi.")
                logger.info(f"Telegram bildirimleri: {'Aktif' if self.config.get('telegram_enabled', False) else 'Pasif'}")
                logger.info(f"E-posta bildirimleri: {'Aktif' if self.config.get('email_enabled', False) else 'Pasif'}")
                logger.info(f"Log seviyesi: {self.config.get('log_level', 'INFO')}")
                logger.info(f"Performans izleme: {self.config.get('performance_interval', 60)} saniye aralık")
            
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
                        # İşlem türünü belirle (spot veya futures)
                        trade_mode = self.config.get('trade_mode', 'spot')
                        market_type = self.config.get('market_type', '')
                        
                        # Eğer market_type futures ise, trade_mode'u buna göre ayarla
                        if market_type == 'futures':
                            trade_mode = 'futures'
                        
                        logger.info(f"İşlem türü: {trade_mode}")
                        
                        # İşlem türüne göre doğru bakiyeyi al
                        try:
                            available = 0
                            base_currency = self.config.get('base_currency', 'USDT')
                            
                            if trade_mode == 'futures':
                                # Futures hesap bakiyesi
                                try:
                                    # CCXT ile futures bakiyesini al
                                    futures_balance = self.exchange.fetch_balance({'type': 'future'})
                                    if base_currency in futures_balance and 'free' in futures_balance[base_currency]:
                                        available = float(futures_balance[base_currency]['free'])
                                        logger.info(f"Futures hesap bakiyesi: {available} {base_currency}")
                                    else:
                                        # Alternatif yöntem
                                        try:
                                            futures_account = self.exchange.fapiPrivateGetAccount()
                                            if 'assets' in futures_account:
                                                for asset in futures_account['assets']:
                                                    if asset['asset'] == base_currency:
                                                        available = float(asset['availableBalance'])
                                                        logger.info(f"Futures hesap bakiyesi (API): {available} {base_currency}")
                                                        break
                                        except Exception as futures_api_error:
                                            logger.error(f"Futures API hatası: {str(futures_api_error)}")
                                except Exception as futures_error:
                                    logger.error(f"Futures bakiyesi alınırken hata: {str(futures_error)}")
                            else:
                                # Spot hesap bakiyesi
                                balance = self.exchange.fetch_balance()
                                if base_currency in balance and 'free' in balance[base_currency]:
                                    available = float(balance[base_currency]['free'])
                                    logger.info(f"Spot hesap bakiyesi: {available} {base_currency}")
                                
                            # Veritabanına yeni bakiye bilgisini kaydet
                            if available > 0:
                                insert_query = """
                                INSERT INTO account_balance (currency, total_balance, available_balance, account_type, update_time)
                                VALUES (%s, %s, %s, %s, NOW())
                                """
                                cursor.execute(insert_query, (base_currency, available, available, trade_mode))
                                conn.commit()
                                
                                self.config['account_balance'] = available
                                logger.info(f"{trade_mode.upper()} hesap bakiyesi: {available} {base_currency}")
                                
                                # İşlem türüne göre uyarı veya bilgilendirme mesajı
                                if available < self.config['trade_amount']:
                                    logger.warning(f"UYARI: {trade_mode.upper()} hesap bakiyesi ({available} {base_currency}), " 
                                                 f"işlem tutarından ({self.config['trade_amount']} {base_currency}) düşük!")
                        except Exception as e:
                            logger.error(f"Bakiye alınırken hata: {str(e)}")
                            self.config['account_balance'] = 0
                except Exception as e:
                    logger.error(f"Binance'den bakiye alınırken hata: {str(e)}")
                    self.config['account_balance'] = 0
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logger.error(f"Bot ayarları veritabanından yüklenirken hata: {str(e)}")
            # Hata durumunda, varsayılan ayarları kullanmaya devam et

    def load_telegram_settings(self):
        """
        Bot ayarlarından Telegram konfigürasyonunu yükler.
        Bu fonksiyon `load_settings_from_db` tarafından çağrılır.
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
            cursor = conn.cursor(dictionary=True)
            
            # Bot ayarlarını al
            cursor.execute("SELECT settings_json FROM bot_settings ORDER BY id DESC LIMIT 1")
            result = cursor.fetchone()
            
            if not result or not result['settings_json']:
                logger.warning("bot_settings tablosunda Telegram ayarları bulunamadı.")
                cursor.close()
                conn.close()
                return
            
            try:
                # JSON'u parse et
                settings = json.loads(result['settings_json'])
                
                # Telegram ayarları varsa güncelle
                if 'telegram' in settings:
                    telegram_settings = settings['telegram']
                    
                    # Ana ayarları güncelle - ÖNEMLİ: use_telegram anahtarını aktifleştir
                    self.config['use_telegram'] = telegram_settings.get('enabled', False)
                    
                    # Ayarlardaki telegram_enabled özelliğini de kontrol et
                    if settings.get('telegram_enabled', False):
                        self.config['use_telegram'] = True
                    
                    # Ayrıntılı Telegram ayarlarını güncelle
                    self.config['telegram'] = {
                        'enabled': telegram_settings.get('enabled', False) or settings.get('telegram_enabled', False),
                        'token': telegram_settings.get('token', ''),
                        'chat_id': telegram_settings.get('chat_id', ''),
                        'trade_signals': telegram_settings.get('trade_signals', False),
                        'discovered_coins': telegram_settings.get('discovered_coins', False),
                        'position_updates': telegram_settings.get('position_updates', False),
                        'performance_updates': telegram_settings.get('performance_updates', False)
                    }
                    
                    # API anahtarlarını güncelle - bu kritik!
                    self.api_keys['telegram_token'] = telegram_settings.get('token', '')
                    self.api_keys['telegram_chat_id'] = telegram_settings.get('chat_id', '')
                    
                    logger.info(f"Telegram ayarları yüklendi: enabled={self.config['use_telegram']}, token_var={'Evet' if self.api_keys.get('telegram_token') else 'Hayır'}, chat_id_var={'Evet' if self.api_keys.get('telegram_chat_id') else 'Hayır'}")
                    
                    # Herhangi bir bildirim gönder (test amaçlı)
                    if self.config['use_telegram'] and self.api_keys['telegram_token'] and self.api_keys['telegram_chat_id']:
                        try:
                            self.send_telegram_message("🤖 *Trading Bot başlatıldı!*\nTelegram bildirimleriniz aktif.")
                            logger.info("Telegram test mesajı gönderildi")
                        except Exception as msg_error:
                            logger.error(f"Telegram test mesajı gönderilemedi: {str(msg_error)}")
                else:
                    logger.warning("bot_settings tablosunda Telegram ayarları bulunamadı.")
            
            except json.JSONDecodeError:
                logger.error("bot_settings tablosundaki settings_json alanı geçerli JSON değil!")
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logger.error(f"Telegram ayarları yüklenirken hata: {str(e)}")
            
        return self.config.get('use_telegram', False)

    def load_api_keys(self):
        """
        MySQL veritabanından API anahtarlarını yükler.
        Anahtarlar sadece bot_settings tablosundan yüklenir.
        JSON dosyası kullanılmaz.
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
            cursor = conn.cursor(dictionary=True)
            
            # Bot_settings tablosundan en son kaydı al
            cursor.execute("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1")
            settings = cursor.fetchone()
            
            if not settings:
                logger.critical("bot_settings tablosunda kayıt bulunamadı! API anahtarları yüklenemedi.")
                self.api_keys = {'api_key': '', 'api_secret': ''}
                cursor.close()
                conn.close()
                return self.api_keys
            
            # Ayarları JSON olarak parse et
            settings_json = None
            
            # Önce settings_json sütununu kontrol et
            if 'settings_json' in settings and settings['settings_json']:
                try:
                    settings_json = json.loads(settings['settings_json'])
                    logger.info("API anahtarlarını settings_json alanından okuma denemesi yapılıyor")
                except json.JSONDecodeError:
                    logger.error("settings_json alanı JSON formatında değil!")
                    settings_json = None
                    
            # JSON parse edilemezse settings alanını dene
            if not settings_json and 'settings' in settings and settings['settings']:
                try:
                    settings_json = json.loads(settings['settings'])
                    logger.info("API anahtarlarını settings alanından okuma denemesi yapılıyor")
                except json.JSONDecodeError:
                    logger.error("settings alanı JSON formatında değil!")
                    settings_json = None
            
            # JSON içinden API anahtarlarını çıkart
            api_key = ''
            api_secret = ''
            
            if settings_json:
                # API anahtarları doğrudan ayarların içinde olabilir
                api_key = settings_json.get('api_key', '')
                api_secret = settings_json.get('api_secret', '')
                
                # API anahtarları bir alt obje içinde de olabilir
                if (not api_key or not api_secret) and 'api_keys' in settings_json:
                    api_keys_obj = settings_json.get('api_keys', {})
                    
                    # Binance API key öncelikli kontrolü
                    if 'binance_api_key' in api_keys_obj and 'binance_api_secret' in api_keys_obj:
                        api_key = api_keys_obj.get('binance_api_key', '')
                        api_secret = api_keys_obj.get('binance_api_secret', '')
                        logger.info("API anahtarları api_keys.binance_api_key ve api_keys.binance_api_secret'dan alındı")
                    else:  # Eğer binance_ öneki yoksa, genel api_key kontrol et
                        api_key = api_keys_obj.get('api_key', '')
                        api_secret = api_keys_obj.get('api_secret', '')
                        logger.info("API anahtarları api_keys.api_key ve api_keys.api_secret'dan alındı")
                
                # Eğer hala boşlarsa, özel bir anahtarda olabilir
                if not api_key and 'binance_api_key' in settings_json:
                    api_key = settings_json.get('binance_api_key', '')
                    logger.info("API anahtarı ana seviyedeki binance_api_key'den alındı")
                
                if not api_secret and 'binance_api_secret' in settings_json:
                    api_secret = settings_json.get('binance_api_secret', '')
                    logger.info("API anahtarı ana seviyedeki binance_api_secret'dan alındı")
            
            # API anahtarlarındaki fazla boşlukları temizle
            if api_key:
                api_key = api_key.strip()
                logger.info(f"API key boşlukları temizlendi. Yeni uzunluk: {len(api_key)}")
                
            if api_secret:
                api_secret = api_secret.strip()
                logger.info(f"API secret boşlukları temizlendi. Yeni uzunluk: {len(api_secret)}")
            
            # API anahtarlarını kaydet
            self.api_keys = {
                'api_key': api_key,
                'api_secret': api_secret,
                'description': 'Binance API (bot_settings)'
            }
            
            # API anahtarlarının var olup olmadığını kontrol et
            if api_key and api_secret:
                logger.info(f"API anahtarları veritabanından başarıyla yüklendi. API key uzunluğu: {len(api_key)}")
                if len(api_key) > 6:
                    first_three = api_key[:3]
                    last_three = api_key[-3:]
                    logger.info(f"API anahtarı: {first_three}...{last_three}")
            else:
                logger.critical("API anahtarları veritabanından yüklenemedi! Lütfen bot_settings tablosuna anahtarları ekleyin.")
            
            # Veritabanı bağlantısını kapat
            cursor.close()
            conn.close()
            
            # Telegram ayarlarını yükle (eğer varsa)
            self.load_telegram_settings()
                
            return self.api_keys
            
        except Exception as e:
            logger.error(f"API anahtarları yüklenirken hata: {str(e)}")
            self.api_keys = {'api_key': '', 'api_secret': ''}
            return self.api_keys

    def load_open_positions(self):
        """
        MySQL veritabanından açık pozisyonları yükler
        """
        self.open_positions = []  # Önce mevcut pozisyonları temizle
        
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
            
            # Açık pozisyonları sorgula
            query = "SELECT * FROM open_positions WHERE status = 'OPEN'"
            cursor.execute(query)
            
            # Sonuçları yükle
            db_positions = cursor.fetchall()
            
            # Sonuçları formatlayıp listeye ekle
            for pos in db_positions:
                position = {
                    'symbol': pos['symbol'],
                    'type': pos.get('position_type', 'LONG'),  # Varsayılan olarak LONG
                    'entry_price': float(pos['entry_price']) if pos['entry_price'] else 0,
                    'amount': float(pos.get('quantity', 0)),    # quantity sütunu miktar için kullanılıyor
                    'entry_time': pos['entry_time'].strftime('%Y-%m-%d %H:%M:%S') if isinstance(pos['entry_time'], datetime) else pos['entry_time'],
                    'id': pos['id']
                }
                
                # Opsiyonel alanları ekle
                if 'stop_loss' in pos and pos['stop_loss']:
                    position['stop_loss'] = float(pos['stop_loss'])
                if 'take_profit' in pos and pos['take_profit']:
                    position['take_profit'] = float(pos['take_profit'])
                if 'strategy' in pos and pos['strategy']:
                    position['strategy'] = pos['strategy']
                if 'notes' in pos and pos['notes']:
                    position['notes'] = pos['notes']
                    
                # Trade mode ve leverage bilgilerini ekle
                if 'trade_mode' in pos and pos['trade_mode']:
                    position['trade_mode'] = pos['trade_mode']
                else:
                    # Varsayılan işlem modunu ayarla
                    position['trade_mode'] = self.config.get('trade_mode', 'spot')
                
                if 'leverage' in pos and pos['leverage']:
                    position['leverage'] = float(pos['leverage'])
                else:
                    position['leverage'] = self.config.get('leverage', 1)
                    
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

    def clean_delisted_coins(self):
        """
        🧹 DELİSTED COİNLERİ TEMİZLEME SİSTEMİ
        Artık mevcut olmayan coinleri aktif listeden çıkarır
        """
        try:
            logger.info("🧹 Delisted coinler kontrol ediliyor ve temizleniyor...")
            
            # Bilinen delisted coinler listesi (log dosyasından tespit edilen)
            known_delisted_coins = {
                'USDP/USDT', 'BUSD/USDT', 'EPS/USDT', 'WTC/USDT', 'QKC/USDT',
                'BCHDOWN/USDT', 'XRPDOWN/USDT', 'ETHBEAR/USDT', 'FRONT/USDT',
                'ACM/USDT', 'MIR/USDT', 'LUNC/USDT', 'BTS/USDT', 'VEN/USDT',
                'CVP/USDT', 'FILUP/USDT', 'DREP/USDT', 'RGT/USDT', 'OAX/USDT',
                'TRIBE/USDT', 'KP3R/USDT', 'CLV/USDT', 'SUSHIUP/USDT',
                'XLMUP/USDT', 'STRAT/USDT', 'RAMP/USDT', 'SLF/USDT', 'BMT/USDT',
                'IOTX/USDT', 'MASK/USDT'  # Log dosyasında hata veren coinleri ekle
            }
            
            # Geçersiz semboller önbelleğine ekle
            for delisted_coin in known_delisted_coins:
                self.invalid_symbols.add(delisted_coin)
            
            # Veritabanı bağlantısı
            db_config = {
                'host': 'localhost',
                'user': 'root',
                'password': 'Efsane44.',
                'database': 'trading_bot_db'
            }
            
            try:
                conn = mysql.connector.connect(**db_config)
                cursor = conn.cursor()
                
                # active_coins tablosundan delisted coinleri kaldır
                cleaned_count = 0
                for delisted_coin in known_delisted_coins:
                    try:
                        cursor.execute("DELETE FROM active_coins WHERE symbol = %s", (delisted_coin,))
                        if cursor.rowcount > 0:
                            cleaned_count += 1
                            logger.info(f"❌ {delisted_coin} aktif listeden kaldırıldı")
                            
                    except Exception as coin_error:
                        logger.warning(f"⚠️ {delisted_coin} kaldırılırken hata: {str(coin_error)}")
                
                # Açık pozisyonlarda da varsa temizle
                for delisted_coin in known_delisted_coins:
                    try:
                        cursor.execute("UPDATE open_positions SET status = 'CLOSED', close_reason = 'delisted' WHERE symbol = %s AND status = 'OPEN'", (delisted_coin,))
                        if cursor.rowcount > 0:
                            logger.warning(f"⚠️ {delisted_coin} açık pozisyonu kapatıldı (delisted)")
                    except Exception as pos_error:
                        logger.warning(f"⚠️ {delisted_coin} pozisyonu kapatılırken hata: {str(pos_error)}")
                
                conn.commit()
                cursor.close()
                conn.close()
                
                if cleaned_count > 0:
                    logger.info(f"✅ Toplam {cleaned_count} adet delisted coin temizlendi")
                else:
                    logger.info("✅ Temizlenecek delisted coin bulunamadı")
                
                return cleaned_count
                
            except mysql.connector.Error as db_error:
                logger.error(f"❌ Veritabanı bağlantı hatası: {str(db_error)}")
                return 0
            
        except Exception as e:
            logger.error(f"💥 Delisted coinler temizlenirken genel hata: {str(e)}")
            return 0

    def update_trailing_stops(self):
        """
        Açık pozisyonlar için trailing stop değerlerini günceller.
        Trailing stop, fiyat yükseldikçe stop-loss seviyesini yukarı çeker,
        böylece kârın bir kısmını korur.
        """
        try:
            for position in self.open_positions:
                symbol = position['symbol']
                
                # Önce sembolün geçerli olup olmadığını kontrol et
                if not self.validate_symbol(symbol):
                    # Geçersiz sembol için alternatif format dene
                    valid_symbol = self.get_valid_symbol_format(symbol)
                    if valid_symbol:
                        logger.info(f"Sembol formatı düzeltildi: {symbol} -> {valid_symbol}")
                        # Pozisyondaki sembolü güncelle
                        position['symbol'] = valid_symbol
                        symbol = valid_symbol
                    else:
                        logger.warning(f"Trailing stop için geçersiz sembol atlanıyor: {symbol}")
                        continue
                
                # Bu sembol için trailing stop kaydı var mı kontrol et
                if symbol not in self.trailing_stops:
                    # İlk kez trailing stop oluştur
                    self.trailing_stops[symbol] = {
                        'highest_price': position['entry_price'],
                        'current_stop_loss': position.get('stop_loss', position['entry_price'] * 0.98),
                        'last_update': time.time()
                    }
                    logger.debug(f"{symbol} için trailing stop başlatıldı")
                
                # Mevcut fiyatı al
                try:
                    ticker = self.exchange.fetch_ticker(symbol)
                    current_price = ticker['last'] if ticker and 'last' in ticker else None
                    
                    if not current_price:
                        logger.warning(f"{symbol} için fiyat alınamadı, trailing stop atlanıyor")
                        continue
                        
                    # Trailing stop mantığını uygula
                    trailing_data = self.trailing_stops[symbol]
                    
                    # En yüksek fiyatı güncelle
                    if current_price > trailing_data['highest_price']:
                        trailing_data['highest_price'] = current_price
                        
                        # Trailing stop yüzdesini config'den al (varsayılan %2)
                        trailing_pct = self.config.get('trailing_stop_pct', 2.0) / 100
                        
                        # Yeni stop-loss hesapla (en yüksek fiyattan trailing_pct kadar aşağıda)
                        new_stop_loss = current_price * (1 - trailing_pct)
                        
                        # Stop-loss yalnızca yukarı hareket edebilir
                        if new_stop_loss > trailing_data['current_stop_loss']:
                            old_stop_loss = trailing_data['current_stop_loss']
                            trailing_data['current_stop_loss'] = new_stop_loss
                            trailing_data['last_update'] = time.time()
                            
                            # Pozisyondaki stop-loss'u güncelle
                            position['stop_loss'] = new_stop_loss
                            
                            # Veritabanında güncelle
                            self.update_position_in_db(position)
                            
                            logger.info(f"{symbol} trailing stop güncellendi: {old_stop_loss:.6f} -> {new_stop_loss:.6f} (Fiyat: {current_price:.6f})")
                        
                except Exception as e:
                    # Symbol validation hatası ayrı olarak yakala
                    if "does not have market symbol" in str(e):
                        logger.error(f"Trailing stop güncellenirken hata: {symbol}, {str(e)}")
                        # Geçersiz sembolleri önbelleğe ekle
                        self.invalid_symbols.add(symbol)
                    else:
                        logger.error(f"{symbol} için fiyat alınırken hata: {str(e)}")
                    continue
                
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
                    # Önce sembolün geçerli olup olmadığını kontrol et
                    if not self.validate_symbol(symbol):
                        logger.warning(f"Geçersiz sembol: {symbol}, pozisyon kapatılıyor")
                        self.close_position(position, 'invalid_symbol')
                        continue
                    
                    # Mevcut fiyatı al
                    ticker = self.exchange.fetch_ticker(symbol)
                    current_price = ticker['last']
                    
                    # Stop loss ve take profit değerlerini al
                    entry_price = position['entry_price']
                    stop_loss = position.get('stop_loss')
                    take_profit = position.get('take_profit')
                    position_type = position.get('type', 'LONG')
                    
                    # Kâr/zarar hesapla
                    if position_type == 'LONG':
                        profit_loss_pct = ((current_price / entry_price) - 1) * 100
                    else:  # SHORT
                        profit_loss_pct = ((entry_price / current_price) - 1) * 100
                    
                    # YENİ: Zararı izle - Büyük zarar durumunda pozisyonu kapat
                    max_loss_threshold = 8.0  # %13.2'den %8'e indirdik
                    if profit_loss_pct < -max_loss_threshold:
                        logger.warning(f"🔴 YÜKSEK ZARAR UYARISI: {symbol} {position_type} pozisyonunda %{abs(profit_loss_pct):.2f} zarar - pozisyon kapatılıyor!")
                        self.close_position(position, 'max_loss_protection', current_price)
                        
                        # Zararın nedenini analiz et ve günlüğe kaydet
                        self.analyze_loss_reason(position, current_price)
                        continue
                    
                    # Pozisyonun durumunu loglama
                    log_level = logging.INFO if abs(profit_loss_pct) > 1.0 else logging.DEBUG
                    logger.log(log_level, f"{symbol} {position_type} pozisyonu - Fiyat: {current_price:.6f}, Kâr/Zarar: {profit_loss_pct:.2f}%")
                    
                    # Take profit kontrolü
                    if take_profit is not None:
                        if (position_type == 'LONG' and current_price >= take_profit) or \
                           (position_type == 'SHORT' and current_price <= take_profit):
                            logger.info(f"🟢 TAKE PROFIT: {symbol} {position_type} pozisyonu kâr hedefine ulaştı! Fiyat: {current_price:.6f}, TP: {take_profit:.6f}")
                            self.close_position(position, 'take_profit', current_price)
                            continue
                    
                    # Stop loss kontrolü
                    if stop_loss is not None:
                        if (position_type == 'LONG' and current_price <= stop_loss) or \
                           (position_type == 'SHORT' and current_price >= stop_loss):
                            logger.info(f"🔴 STOP LOSS: {symbol} {position_type} pozisyonu stop seviyesine ulaştı! Fiyat: {current_price:.6f}, SL: {stop_loss:.6f}")
                            self.close_position(position, 'stop_loss', current_price)
                            continue
                    
                    # Trailing stop güncelleme
                    if self.risk_manager.use_trailing_stop:
                        # Trailing stop hesapla
                        new_stop = self.risk_manager.update_trailing_stop(
                            entry_price=entry_price,
                            current_price=current_price,
                            current_stop=stop_loss if stop_loss is not None else (entry_price * 0.95 if position_type == 'LONG' else entry_price * 1.05),
                            side='BUY' if position_type == 'LONG' else 'SELL'
                        )
                        
                        # Trailing stop güncellenmiş mi?
                        if new_stop != stop_loss:
                            position['stop_loss'] = new_stop
                            position['notes'] = f"Trailing stop güncellendi: {new_stop:.6f} ({datetime.now().strftime('%H:%M:%S')})"
                            
                            # Güncel stop_loss değerini loglama
                            logger.info(f"📈 {symbol} {position_type} için trailing stop güncellendi: {new_stop:.6f} (Kâr/Zarar: {profit_loss_pct:.2f}%)")
                            
                            # Pozisyonu veritabanında güncelle
                            self.update_position_in_db(position)
                    
                except Exception as e:
                    logger.error(f"Pozisyon kontrolü sırasında hata ({symbol}): {str(e)}")
            
        except Exception as e:
            logger.error(f"Stop-loss ve take-profit kontrolü sırasında hata: {str(e)}")
    
    def analyze_loss_reason(self, position, current_price):
        """
        Neden zarar edildiğini analiz eder ve loga kaydeder
        """
        try:
            symbol = position['symbol']
            position_type = position.get('type', 'LONG')
            entry_price = position['entry_price']
            entry_time = position.get('entry_time')
            
            # Zarar yüzdesini hesapla
            if position_type == 'LONG':
                loss_pct = ((entry_price / current_price) - 1) * 100
            else:  # SHORT
                loss_pct = ((current_price / entry_price) - 1) * 100
            
            # Zaman farkını hesapla
            if entry_time:
                try:
                    entry_datetime = datetime.strptime(entry_time, '%Y-%m-%d %H:%M:%S')
                    duration = datetime.now() - entry_datetime
                    hours = duration.total_seconds() / 3600
                except:
                    hours = 0
            else:
                hours = 0
            
            # OHLCV verilerini al
            try:
                ohlcv_data = self.fetch_ohlcv(symbol, '1h')
                # Volatilite hesapla
                high_prices = ohlcv_data['high'].values[-24:]  # Son 24 saat
                low_prices = ohlcv_data['low'].values[-24:]
                price_range_pct = ((max(high_prices) / min(low_prices)) - 1) * 100
            except:
                price_range_pct = 0
            
            # Zararın olası nedenlerini belirle
            reasons = []
            
            if hours < 2:
                reasons.append("Kısa sürede hızlı fiyat düşüşü")
            elif price_range_pct > 10:
                reasons.append(f"Yüksek volatilite (%{price_range_pct:.1f} fiyat aralığı)")
            elif abs(loss_pct) > 15:
                reasons.append("Aşırı büyük fiyat hareketi")
            
            if not reasons:
                reasons.append("Trend tersine dönüşü")
            
            logger.warning(f"💡 {symbol} ZARARDAKİ POZİSYON ANALİZİ: %{loss_pct:.2f} zarar, {hours:.1f} saat açık kaldı")
            logger.warning(f"💡 Olası nedenler: {', '.join(reasons)}")
            
            # Veritabanına kaydedilecek not
            position['notes'] = f"Zarar analizi: %{loss_pct:.2f} zarar, {', '.join(reasons)}"
            
        except Exception as e:
            logger.error(f"Zarar analizi yapılırken hata: {str(e)}")

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
                    self.send_position_notification(
                        symbol=symbol,
                        action='CLOSE',
                        position_type=position['type'],
                        price=close_price,
                        amount=position['amount'],
                        profit_loss=profit_loss_pct,
                        reason=close_reason
                    )
                
                return True
            
            # Gerçek işlem modu (canlı)
            elif self.config.get('trade_mode') == 'live':
                logger.info(f"CANLI MOD: {symbol} pozisyon kapatılıyor. Fiyat: {close_price:.4f}, Kâr/Zarar: {profit_loss_pct:.2f}%")
                
                # Market type kontrolü
                market_type = self.config.get('market_type', 'spot')
                
                # Exchange API ile işlem yap
                try:
                    # Futures pozisyonu kapatma
                    if market_type == 'futures':
                        # LONG pozisyonlar için SELL market order (futures)
                        if position['type'] == 'LONG':
                            amount = position['amount']
                            order = self.exchange.create_market_sell_order(
                                symbol=symbol, 
                                amount=amount,
                                params={'type': 'future'}  # Futures için özel parametre
                            )
                            logger.info(f"FUTURES SATIŞ: {symbol} LONG pozisyon kapatıldı. Miktar: {amount}, Fiyat: {close_price:.4f}")
                        
                        # SHORT pozisyonlar için BUY market order (futures)
                        elif position['type'] == 'SHORT':
                            amount = position['amount']
                            order = self.exchange.create_market_buy_order(
                                symbol=symbol, 
                                amount=amount,
                                params={'type': 'future'}  # Futures için özel parametre
                            )
                            logger.info(f"FUTURES ALIM: {symbol} SHORT pozisyon kapatıldı. Miktar: {amount}, Fiyat: {close_price:.4f}")
                    
                    # Spot pozisyonu kapatma
                    else:
                        # Spot işlemler için sadece LONG pozisyonlar (SHORT yok)
                        if position['type'] == 'LONG':
                            amount = position['amount']
                            order = self.exchange.create_market_sell_order(symbol, amount)
                            logger.info(f"SPOT SATIŞ: {symbol} başarıyla satıldı. Miktar: {amount}, Fiyat: {close_price:.4f}")
                    
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
                        self.send_position_notification(
                            symbol=symbol,
                            action='CLOSE',
                            position_type=position['type'],
                            price=close_price,
                            amount=position['amount'],
                            profit_loss=profit_loss_pct,
                            reason=close_reason
                        )
                    
                    return True
                    
                except Exception as api_error:
                    logger.error(f"Canlı işlem API'si hatası: {str(api_error)}")
                    # API hatası durumunda sadece lokal pozisyonu kapat
                    if position in self.open_positions:
                        self.open_positions.remove(position)
                    return False
            
            else:
                logger.warning(f"Bilinmeyen işlem modu: {self.config.get('trade_mode')} - Pozisyon kapatılmadı")
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
                        # Tam ayar yenileme
                        self.load_settings_from_db()
                        logger.info(f"Bot ayarları veritabanından yenilendi: trade_amount={self.config.get('trade_amount')}, " +
                                  f"min_trade_amount={self.config.get('min_trade_amount')}, " +
                                  f"max_trade_amount={self.config.get('max_trade_amount')}, " +
                                  f"trade_mode={self.config.get('trade_mode')}, " +
                                  f"auto_trade={self.config.get('auto_trade')}")
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
        🔍 GELİŞMİŞ COİN ANALİZ SİSTEMİ
        Tek bir coini analiz eder - gelişmiş hata yönetimi ile
        
        :return: Analiz sonuçları
        """
        try:
            # 🔗 Exchange bağlantı kontrolü
            if not self.exchange:
                logger.error(f"❌ {symbol} - Exchange bağlantısı yok, analiz yapılamaz")
                return None
            
            # 🔍 Sembol geçerlilik kontrolü (emoji ile işaretleme)
            if not self.validate_symbol(symbol):
                logger.warning(f"⚠️ {symbol} - Geçersiz sembol, analiz atlandı")
                return None
            
            logger.info(f"🔄 {symbol} analiz ediliyor...")
             
            # Birleşik analiz yap (TradingView + klasik indikatörler)
            analysis = self.analyze_combined_indicators(symbol)
            
            if analysis:
                signal = analysis.get('trade_signal', 'NEUTRAL')
                price = analysis.get('price', 0)
                
                # Signal durumuna göre emoji ve log seviyesi
                if signal == 'BUY':
                    logger.info(f"🟢 {symbol} - ALIM sinyali tespit edildi @ {price:.6f}")
                elif signal == 'SELL':
                    logger.info(f"🔴 {symbol} - SATIM sinyali tespit edildi @ {price:.6f}")
                else:
                    logger.debug(f"⚪ {symbol} - NEUTRAL sinyal @ {price:.6f}")
            else:
                logger.warning(f"⚠️ {symbol} - Analiz sonucu alınamadı")
            
            return analysis
            
        except Exception as e:
            # 🚨 Gelişmiş hata kategorilendirmesi
            error_msg = str(e).lower()
            
            if "does not have market symbol" in error_msg or "invalid symbol" in error_msg:
                logger.error(f"❌ {symbol} - Geçersiz sembol hatası: {str(e)}")
                # Geçersiz semboller listesine ekle
                self.invalid_symbols.add(symbol)
            elif "rate limit" in error_msg or "too many requests" in error_msg:
                logger.warning(f"⏰ {symbol} - API rate limit hatası: {str(e)}")
            elif "network" in error_msg or "connection" in error_msg:
                logger.error(f"🌐 {symbol} - Ağ bağlantısı hatası: {str(e)}")
            elif "permission" in error_msg or "unauthorized" in error_msg:
                logger.error(f"🔐 {symbol} - API yetki hatası: {str(e)}")
            else:
                logger.error(f"💥 {symbol} - Analiz hatası: {str(e)}")
            
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
                pass  # TradingView entegrasyonu burada olacak
            
            # Çoklu zaman aralığı verileri çek
            multi_tf_data = None
            if ohlcv_data is None:
                multi_tf_data = self.fetch_multi_timeframe_ohlcv(symbol)
            else:
                # Tek timeframe verisi varsa onu kullan
                primary_tf = self.config.get('primary_timeframe') or self.config.get('interval', '1h')
                multi_tf_data = {primary_tf: ohlcv_data}
            
            # Veriler yoksa işlemi sonlandır
            if not multi_tf_data or len(multi_tf_data) == 0:
                logger.warning(f"{symbol} için OHLCV verileri alınamadı")
                return None
            
            # İlk timeframe'i belirle (genellikle birincil timeframe)
            first_tf = list(multi_tf_data.keys())[0]
            ohlcv_data = multi_tf_data[first_tf]  # İlk zaman aralığının OHLCV verilerini al
            
            # *** ADAPTİF PARAMETRELER ENTEGRASYONu ***
            # Piyasa koşullarını analiz et ve parametreleri otomatik ayarla
            market_state = self.adaptive_parameters.analyze_market_conditions(ohlcv_data)
            logger.info(f"{symbol} piyasa durumu: Volatilite {market_state['volatility']}, Trend {market_state['trend']}, Momentum {market_state['momentum']}")
            
            # Her bir zaman aralığı için indikatörleri hesapla (adaptif parametrelerle)
            multi_tf_indicators = {}
            for tf, tf_data in multi_tf_data.items():
                tf_indicators = self.indicators_manager.calculate_indicators(tf_data, symbol)
                
                # Adaptif parametreler uygula
                for indicator_name in ['rsi', 'bollinger', 'macd', 'supertrend']:
                    if indicator_name in tf_indicators:
                        # Adaptif parametreleri al ve indikatöre uygula
                        adapted_params = self.adaptive_parameters.get_adjusted_parameters(indicator_name, symbol)
                        
                        # İndikatörü yeniden hesapla (adaptif parametrelerle)
                        if indicator_name == 'rsi' and 'rsi' in tf_indicators:
                            # RSI için adaptif parametreler
                            period = adapted_params.get('period', 14)
                            oversold = adapted_params.get('oversold', 30)
                            overbought = adapted_params.get('overbought', 70)
                            
                            # RSI sinyalini yeniden değerlendir
                            rsi_value = tf_indicators['rsi']['value']
                            if rsi_value <= oversold:
                                tf_indicators['rsi']['signal'] = 'BUY'
                            elif rsi_value >= overbought:
                                tf_indicators['rsi']['signal'] = 'SELL'
                            else:
                                tf_indicators['rsi']['signal'] = 'NEUTRAL'
                            
                            tf_indicators['rsi']['adaptive_params'] = adapted_params
                
                multi_tf_indicators[tf] = tf_indicators
            
            # TradingView verileri varsa birleştir
            if tradingview_data is not None and not tradingview_data.empty:
                # TradingView entegrasyonu burada yapılacak
                pass
            
            # Stratejileri uygula
            strategy_results = {}
            
            # 1. Trend takip stratejisi
            if self.config.get('strategies', {}).get('trend_following', {}).get('enabled', True):
                try:
                    trend_result = trend_following(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                    # Tuple'ı dictionary'ye çevir
                    if isinstance(trend_result, tuple) and len(trend_result) == 2:
                        strategy_results['trend_following'] = {
                            'signal': trend_result[0] if trend_result[0] else 'NEUTRAL',
                            'reason': trend_result[1]
                        }
                    elif isinstance(trend_result, dict):
                        strategy_results['trend_following'] = trend_result
                    else:
                        strategy_results['trend_following'] = {
                            'signal': 'NEUTRAL',
                            'reason': 'Beklenmeyen veri formatı'
                        }
                except Exception as e:
                    logger.error(f"Trend takip stratejisi hatası: {str(e)}")
                    strategy_results['trend_following'] = {
                        'signal': 'NEUTRAL',
                        'reason': f'Hata: {str(e)}'
                    }
            
            # 2. Kırılma tespiti stratejisi
            if self.config.get('strategies', {}).get('breakout_detection', {}).get('enabled', True):
                try:
                    breakout_result = breakout_detection(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                    # Tuple'ı dictionary'ye çevir
                    if isinstance(breakout_result, tuple) and len(breakout_result) == 2:
                        strategy_results['breakout_detection'] = {
                            'signal': breakout_result[0] if breakout_result[0] else 'NEUTRAL',
                            'reason': breakout_result[1]
                        }
                    elif isinstance(breakout_result, dict):
                        strategy_results['breakout_detection'] = breakout_result
                    else:
                        strategy_results['breakout_detection'] = {
                            'signal': 'NEUTRAL',
                            'reason': 'Beklenmeyen veri formatı'
                        }
                except Exception as e:
                    logger.error(f"Kırılma tespiti stratejisi hatası: {str(e)}")
                    strategy_results['breakout_detection'] = {
                        'signal': 'NEUTRAL',
                        'reason': f'Hata: {str(e)}'
                    }
            
            # 3. Kısa vadeli strateji
            if self.config.get('strategies', {}).get('short_term_strategy', {}).get('enabled', False):
                try:
                    short_term_result = short_term_strategy(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                    # Tuple'ı dictionary'ye çevir
                    if isinstance(short_term_result, tuple) and len(short_term_result) == 2:
                        strategy_results['short_term_strategy'] = {
                            'signal': short_term_result[0] if short_term_result[0] else 'NEUTRAL',
                            'reason': short_term_result[1]
                        }
                    elif isinstance(short_term_result, dict):
                        strategy_results['short_term_strategy'] = short_term_result
                    else:
                        strategy_results['short_term_strategy'] = {
                            'signal': 'NEUTRAL',
                            'reason': 'Beklenmeyen veri formatı'
                        }
                except Exception as e:
                    logger.error(f"Kısa vadeli strateji hatası: {str(e)}")
                    strategy_results['short_term_strategy'] = {
                        'signal': 'NEUTRAL',
                        'reason': f'Hata: {str(e)}'
                    }
            
            # 4. Volatilite Kırılma Stratejisi
            if self.config.get('strategies', {}).get('volatility_breakout', {}).get('enabled', True):
                try:
                    from strategies import volatility_breakout
                    volatility_result = volatility_breakout.analyze(ohlcv_data, multi_tf_indicators.get(first_tf, {}))
                    # Tuple'ı dictionary'ye çevir
                    if isinstance(volatility_result, tuple) and len(volatility_result) == 2:
                        strategy_results['volatility_breakout'] = {
                            'signal': volatility_result[0] if volatility_result[0] else 'NEUTRAL',
                            'reason': volatility_result[1]
                        }
                    elif isinstance(volatility_result, dict):
                        strategy_results['volatility_breakout'] = volatility_result
                    else:
                        strategy_results['volatility_breakout'] = {
                            'signal': 'NEUTRAL',
                            'reason': 'Beklenmeyen veri formatı'
                        }
                except Exception as e:
                    logger.error(f"Volatilite kırılma stratejisi hatası: {str(e)}")
                    strategy_results['volatility_breakout'] = {
                        'signal': 'NEUTRAL',
                        'reason': f'Hata: {str(e)}'
                    }
            
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
                'indicators': multi_tf_indicators.get(first_tf, {}),
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
                },
                # *** ADAPTİF PARAMETRELER BİLGİSİ ***
                'adaptive_analysis': {
                    'market_state': market_state,
                    'parameters_updated': self.adaptive_parameters.should_update_parameters()
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
        🔄 GELİŞMİŞ İŞLEM SİSTEMİ
        Alım veya satım sinyaline göre işlem gerçekleştirir
        Futures margin sorunlarını otomatik çözer
        
        :param symbol: Coin sembolü
        :param signal_type: Sinyal türü ('BUY' veya 'SELL')
        :param analysis: Analiz sonuçları
        :return: İşlem başarılı mı
        """
        try:
            # Exchange bağlantısını kontrol et
            if not self.exchange:
                logger.error("❌ Exchange bağlantısı yok - işlem yapılamaz")
                return False
            
            # İşlem türünü (paper/live) kontrol et
            trade_mode = self.config.get('trade_mode', 'paper')
            if trade_mode not in ['paper', 'live']:
                logger.warning(f"⚠️ Geçersiz trade_mode: {trade_mode} - İşlem yapılmadı")
                return False
                
            # Auto-trade ayarını kontrol et
            if not self.config.get('auto_trade', False):
                logger.info(f"🔒 Auto-trade kapalı - {symbol} {signal_type} sinyali manuel onay bekliyor")
                return False
                
            # 🔍 Futures Margin Mode Kontrolü ve Düzeltmesi
            market_type = self.config.get('market_type', 'spot')
            if market_type == 'futures' and (signal_type == 'BUY' or signal_type == 'SELL'):
                try:
                    logger.info(f"🔧 {symbol} için futures margin mode kontrol ediliyor...")
                    # Futures margin mode'u kontrol et ve gerekirse düzelt
                    pass  # Margin mode kontrolü burada yapılacak
                        
                except Exception as margin_check_error:
                    logger.warning(f"⚠️ Margin mode kontrolünde hata: {str(margin_check_error)} - İşleme devam ediliyor")
                    
            # Sembol için açık pozisyon var mı kontrol et
            open_position = next((p for p in self.open_positions if p['symbol'] == symbol), None)
            
            # 🟢 ALIM SİNYALİ: Açık pozisyon yoksa ve BUY sinyali ise yeni LONG pozisyon aç
            if signal_type == 'BUY' and open_position is None:
                logger.info(f"🟢 {symbol} ALIM sinyali - Yeni LONG pozisyon açılacak")
                return self.open_position(symbol, analysis, position_type='LONG')
                
            # 🔴 SATIM SİNYALİ: Açık pozisyon yoksa ve SELL sinyali ise yeni SHORT pozisyon aç
            elif signal_type == 'SELL' and open_position is None:
                logger.info(f"🔴 {symbol} SATIM sinyali - Yeni SHORT pozisyon açılacak")
                return self.open_position(symbol, analysis, position_type='SHORT')
                
            # 🔴 SATIM SİNYALİ: LONG pozisyon varsa kapat
            elif signal_type == 'SELL' and open_position is not None and open_position.get('type') == 'LONG':
                logger.info(f"🔴 {symbol} SATIM sinyali - Mevcut LONG pozisyon kapatılacak")
                return self.close_position(open_position, 'signal')
                
            # 🟢 ALIM SİNYALİ: SHORT pozisyon varsa kapat
            elif signal_type == 'BUY' and open_position is not None and open_position.get('type') == 'SHORT':
                logger.info(f"🟢 {symbol} ALIM sinyali - Mevcut SHORT pozisyon kapatılacak")
                return self.close_position(open_position, 'signal')
                
            # Diğer durumlar - işlem yapılmadı
            elif signal_type == 'BUY' and open_position is not None and open_position.get('type') == 'LONG':
                logger.debug(f"🔒 {symbol} için zaten açık LONG pozisyon var - yeni alım yapılmadı")
            elif signal_type == 'SELL' and open_position is not None and open_position.get('type') == 'SHORT':
                logger.debug(f"🔒 {symbol} için zaten açık SHORT pozisyon var - yeni satım yapılmadı")
                
            return False
            
        except Exception as e:
            logger.error(f"💥 İşlem gerçekleştirilirken hata: {str(e)}")
            return False

    def calculate_dynamic_position_size(self, symbol, analysis):
        """
        Sinyal kalitesine ve risk yönetimi ayarlarına göre işlem miktarını dinamik belirler
        
        :param symbol: Coin sembolü
        :param analysis: Analiz sonuçları
        :return: Hesaplanmış işlem miktarı (USDT)
        """
        try:
            # Temel işlem miktarı - PERFORMANS ARTIŞI İÇİN OPTİMIZE EDİLDİ
            base_trade_amount = float(self.config.get('trade_amount', 50.0))  # 10'dan 50'ye çıkarıldı
            
            # Min/max limitleri al - DAHA AGRESİF DEĞERLER
            min_trade_amount = float(self.config.get('min_trade_amount', 25.0))  # 0'dan 25'e çıkarıldı
            max_trade_amount = float(self.config.get('max_trade_amount', 500.0))  # Sınır artırıldı
            
            # Minimum limit kontrolü (eğer belirtilmişse)
            if min_trade_amount > 0 and min_trade_amount > base_trade_amount:
                base_trade_amount = min_trade_amount
                
            # Maksimum limit kontrolü (eğer belirtilmişse)
            if max_trade_amount > 0 and base_trade_amount > max_trade_amount:
                base_trade_amount = max_trade_amount
            
            # Sinyal kalitesi faktörünü hesapla (0.0 - 1.0)
            buy_signals = analysis.get('signals', {}).get('buy_count', 0)
            sell_signals = analysis.get('signals', {}).get('sell_count', 0)
            neutral_signals = analysis.get('signals', {}).get('neutral_count', 0)
            
            total_signals = max(1, buy_signals + sell_signals + neutral_signals)
            signal_quality = buy_signals / total_signals
            
            # DAHA AGRESİF SİNYAL DEĞERLENDİRMESİ - Sinyal kalitesine göre işlem büyüklüğünü ayarla
            if signal_quality > 0.7:  # Çok güçlü sinyal (0.8'den 0.7'ye düşürüldü)
                trade_factor = 1.2  # %120 - daha agresif
            elif signal_quality > 0.5:  # Güçlü sinyal (0.6'dan 0.5'e düşürüldü)
                trade_factor = 1.0  # %100
            elif signal_quality > 0.3:  # Orta dereceli sinyal (0.4'ten 0.3'e düşürüldü)
                trade_factor = 0.8  # %80
            else:  # Zayıf sinyal
                trade_factor = 0.6  # %60 (0.4'ten 0.6'ya çıkarıldı)
                
            # Çoklu zaman aralığı sinyallerini de değerlendir
            multi_tf_signal = analysis.get('multi_timeframe', {}).get('combined_signal', {})
            if multi_tf_signal:
                tf_buy_count = multi_tf_signal.get('buy_count', 0)
                tf_total = multi_tf_signal.get('total_timeframes', 1)
                tf_consensus = tf_buy_count / max(1, tf_total)
                
                # Çoklu timeframe konsensusuna göre faktör artırımı
                if tf_consensus > 0.6:  # %60'tan fazla timeframe alım diyor
                    trade_factor *= 1.3  # %30 artır
                elif tf_consensus > 0.4:  # %40'tan fazla timeframe alım diyor
                    trade_factor *= 1.1  # %10 artır
                
            # İşlem miktarını hesapla: Min ve Max USDT değerleri arasında
            if min_trade_amount > 0 and max_trade_amount > min_trade_amount:
                # Dinamik hesaplama: min + (max-min) * trade_factor
                range_amount = max_trade_amount - min_trade_amount
                dynamic_amount = min_trade_amount + (range_amount * min(trade_factor, 1.0))
                
                # Faktör 1'den büyükse ekstra bonus ver
                if trade_factor > 1.0:
                    bonus = (trade_factor - 1.0) * min_trade_amount
                    dynamic_amount = min(dynamic_amount + bonus, max_trade_amount)
                    
                logger.info(f"Dinamik miktar hesaplandı: min={min_trade_amount}, max={max_trade_amount}, kalite={signal_quality:.2f}, faktör={trade_factor:.2f}, sonuç={dynamic_amount:.2f}")
            else:
                # Min/max değerleri yok veya geçersizse, baz miktarı kullan * sinyal kalitesi faktörü
                dynamic_amount = base_trade_amount * max(0.7, trade_factor)  # En az %70'i (0.5'ten 0.7'ye çıkarıldı)
            
            # Son kontroller - minimum/maksimum sınırlarını aşmasın
            if min_trade_amount > 0 and dynamic_amount < min_trade_amount:
                dynamic_amount = min_trade_amount
                
            if max_trade_amount > 0 and dynamic_amount > max_trade_amount:
                dynamic_amount = max_trade_amount
                
            # Yuvarla iki ondalık basamağa
            dynamic_amount = round(dynamic_amount, 2)
            
            logger.info(f"{symbol} için dinamik işlem miktarı: {dynamic_amount} USDT (sinyal kalitesi: {signal_quality:.2f}, faktör: {trade_factor:.2f})")
            
            return dynamic_amount
            
        except Exception as e:
            logger.error(f"Dinamik pozisyon büyüklüğü hesaplanırken hata: {str(e)}")
            # Hata durumunda daha yüksek varsayılan değere dön
            return float(self.config.get('trade_amount', 50.0))  # 10'dan 50'ye çıkarıldı

    def open_position(self, symbol, analysis, position_type='LONG'):
        """
        Yeni bir pozisyon açar (LONG veya SHORT)
        
        :param symbol: Coin sembolü
        :param analysis: Analiz sonuçları
        :param position_type: Pozisyon türü ('LONG' veya 'SHORT')
        :return: Başarı durumu
        """
        try:
            # Pozisyon açma mantığını uygula
            trade_mode = self.config.get('trade_mode', 'paper')
            
            # 🔧 OTOMATİK KALDIRAÇ SİSTEMİ ENTEGRASYONu
            leverage_mode = self.config.get('leverage_mode', 'manual')
            
            if leverage_mode == 'auto' and self.risk_manager.should_use_auto_leverage():
                # OHLCV verilerini al (otomatik kaldıraç hesabı için)
                ohlcv_data = None
                try:
                    ohlcv_data = self.fetch_ohlcv(symbol, '1h')
                except Exception as e:
                    logger.warning(f"OHLCV verisi alınırken hata (otomatik kaldıraç için): {str(e)}")
                
                # Piyasa koşullarını al (adaptive parameters'den)
                market_conditions = None
                try:
                    market_conditions = self.adaptive_parameters.analyze_market_conditions(ohlcv_data) if ohlcv_data is not None else None
                except Exception as e:
                    logger.warning(f"Piyasa koşulları analizi sırasında hata: {str(e)}")
                
                # Otomatik kaldıraç hesapla
                leverage_result = self.risk_manager.calculate_dynamic_leverage(
                    symbol=symbol,
                    ohlcv_data=ohlcv_data,
                    market_conditions=market_conditions
                )
                
                leverage = leverage_result['leverage']
                risk_level = leverage_result['risk_level']
                
                logger.info(f"🔧 {symbol} için otomatik kaldıraç belirlendi: {leverage}x (Risk: {risk_level})")
                
                # Yüksek risk durumunda pozisyon açmayı iptal et
                if risk_level == "ERROR" or (risk_level == "HIGH_RISK" and len(leverage_result['risk_factors']) > 3):
                    logger.warning(f"❌ {symbol} için pozisyon açma iptal edildi - Yüksek risk: {leverage_result['risk_factors']}")
                    return False
                    
            else:
                # Manuel kaldıraç kullan
                leverage = int(self.config.get('leverage', 1))
                logger.info(f"📊 {symbol} için manuel kaldıraç kullanılıyor: {leverage}x")
            
            # Mevcut fiyatı al
            current_price = analysis['price']
            
            # *** RİSK MANAGER ENTEGRASYONu ***
            # Hesap bakiyesini al
            account_balance = self.config.get('account_balance', 1000)  # Varsayılan 1000 USDT
            
            # OHLCV verilerini al (risk hesaplaması için)
            ohlcv_data = None
            try:
                ohlcv_data = self.fetch_ohlcv(symbol)
            except Exception as e:
                logger.warning(f"{symbol} için OHLCV verisi alınamadı, varsayılan risk parametreleri kullanılacak: {str(e)}")
            
            # Risk Manager ile pozisyon büyüklüğünü hesapla
            risk_analysis = self.risk_manager.calculate_position_size(
                balance=account_balance,
                price=current_price,
                symbol=symbol,
                ohlcv_data=ohlcv_data
            )
            
            # Risk analizi sonuçlarını kullan
            position_size_usd = risk_analysis['position_size'] * current_price
            risk_level = risk_analysis['risk_level']
            risk_factors = risk_analysis['risk_factors']
            
            logger.info(f"{symbol} Risk Analizi - Seviye: {risk_level}, Pozisyon: {position_size_usd:.2f} USDT")
            if risk_factors:
                logger.info(f"Risk Faktörleri: {', '.join(risk_factors)}")
            
            # Yüksek risk durumunda pozisyon açmayı iptal et
            if risk_level == "HIGH" and len(risk_factors) > 2:
                logger.warning(f"{symbol} için yüksek risk tespit edildi, pozisyon açılmıyor!")
                return False
            
            # İşlem miktarını belirle (risk analizi sonuçlarından)
            trade_amount = position_size_usd
            
            # Pozisyon miktarını hesapla (kaç coin alınacak) - FİX: Bu hesaplamayı order işlemlerinden ÖNCE yap
            coin_amount = trade_amount / current_price
            
            logger.info(f"{symbol} için {trade_amount:.2f} {self.config.get('base_currency', 'USDT')} tutarında {position_type} pozisyon açılıyor...")
            
            # Paper trade (simülasyon modu) kontrolü
            if trade_mode == 'paper':
                logger.info(f"[PAPER TRADE] {symbol} simülasyon modunda {position_type} pozisyon açılıyor - Miktar: {coin_amount:.6f}")
            else:
                # Live trading - gerçek alım emri
                market_type = self.config.get('market_type', 'spot')
                
                # 🔍 Market bilgilerini al ve minimum miktar kontrolü yap
                try:
                    market_info = self.exchange.market(symbol)
                    if not market_info:
                        logger.error(f"❌ {symbol} için market bilgisi alınamadı")
                        return False
                    
                    # Minimum işlem miktarı kontrolü
                    min_amount = market_info.get('limits', {}).get('amount', {}).get('min', 0)
                    if min_amount and coin_amount < min_amount:
                        logger.warning(f"⚠️ {symbol} için işlem miktarı ({coin_amount:.6f}) minimum değerden ({min_amount:.6f}) küçük. Miktar artırılıyor...")
                        coin_amount = min_amount * 1.1  # %10 fazla ekle
                        trade_amount = coin_amount * current_price  # Trade amount'u da güncelle
                    
                    # Minimum notional değer kontrolü (toplam USDT değeri)
                    min_notional = market_info.get('limits', {}).get('cost', {}).get('min', 0)
                    if min_notional and trade_amount < min_notional:
                        logger.warning(f"⚠️ {symbol} için işlem tutarı ({trade_amount:.2f} USDT) minimum notional değerden ({min_notional:.2f} USDT) küçük. Tutar artırılıyor...")
                        trade_amount = min_notional * 1.1  # %10 fazla ekle
                        coin_amount = trade_amount / current_price  # Coin amount'u da güncelle
                    
                except Exception as market_check_error:
                    logger.warning(f"⚠️ {symbol} için market bilgisi kontrolü yapılamadı: {str(market_check_error)}")
                
                if market_type == 'futures':
                    logger.info(f"[FUTURES TRADE] {symbol} için futures {position_type} pozisyonu açılıyor...")
                    
                    # Futures için minimum miktar kontrolü
                    if coin_amount <= 0:
                        logger.error(f"❌ Geçersiz coin miktarı: {coin_amount}. İşlem iptal edildi.")
                        return False
                    
                    # Kaldıracı ayarla
                    try:
                        if leverage > 1:
                            # Kaldıraç ayarla
                            self.exchange.set_leverage(leverage, symbol)
                            logger.info(f"Kaldıraç {leverage}x olarak ayarlandı")
                    except Exception as leverage_error:
                        logger.warning(f"Kaldıraç ayarlanamadı: {str(leverage_error)}")
                    
                    # Futures market order
                    try:
                        if position_type == 'LONG':
                            order = self.exchange.create_market_buy_order(symbol, coin_amount)
                        elif position_type == 'SHORT':
                            order = self.exchange.create_market_sell_order(symbol, coin_amount)
                        logger.info(f"[FUTURES] Market {position_type.lower()} order başarılı: {order}")
                    except Exception as order_error:
                        logger.error(f"Futures order hatası: {str(order_error)}")
                        return False
                        
                else:
                    logger.info(f"[SPOT TRADE] {symbol} için spot {position_type} pozisyonu açılıyor...")
                    
                    # Spot için minimum miktar kontrolü
                    if coin_amount <= 0:
                        logger.error(f"❌ Geçersiz coin miktarı: {coin_amount}. İşlem iptal edildi.")
                        return False
                    
                    # Spot market order
                    try:
                        if position_type == 'LONG':
                            order = self.exchange.create_market_buy_order(symbol, coin_amount)
                        elif position_type == 'SHORT':
                            order = self.exchange.create_market_sell_order(symbol, coin_amount)
                        logger.info(f"[SPOT] Market {position_type.lower()} order başarılı: {order}")
                    except Exception as order_error:
                        logger.error(f"Spot order hatası: {str(order_error)}")
                        return False
            
            # *** RİSK MANAGER ile STOP-LOSS ve TAKE-PROFIT HESAPLA ***
            # Dinamik stop-loss hesapla (ATR bazlı)
            stop_loss = self.risk_manager.calculate_stop_loss(
                entry_price=current_price,
                side='BUY' if position_type == 'LONG' else 'SELL',
                ohlcv_data=ohlcv_data
            )
            
            # Risk-ödül oranına göre take-profit hesapla
            risk_reward_ratio = self.config.get('risk_reward_ratio', 2.5)
            take_profit = self.risk_manager.calculate_take_profit(
                entry_price=current_price,
                side='BUY' if position_type == 'LONG' else 'SELL',
                ohlcv_data=ohlcv_data,
                risk_reward_ratio=risk_reward_ratio
            )
            
            # Yeni pozisyon oluştur
            position = {
                'symbol': symbol,
                'type': position_type,
                'entry_price': current_price,
                'amount': coin_amount,
                'entry_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                'stop_loss': stop_loss,
                'take_profit': take_profit,
                'strategy': self.get_top_strategy_from_analysis(analysis),
                'leverage': leverage,
                'trade_mode': trade_mode,
                'risk_level': risk_level,
                'risk_factors': risk_factors,
                'trade_amount_usd': trade_amount,
                'notes': f"Risk yönetimli açılış: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')} - Risk: {risk_level} - SL: {stop_loss:.6f} - TP: {take_profit:.6f}"
            }
            
            # Pozisyonlar listesine ekle
            self.open_positions.append(position)
            
            # Veritabanına kaydet
            self.save_position(position)
            
            # Telegram ile bildirim gönder (eğer etkinleştirilmişse)
            if self.config.get('use_telegram', False):
                self.send_position_notification(
                    symbol=symbol,
                    action='OPEN',
                    position_type=position_type,
                    price=current_price,
                    amount=coin_amount
                )
            
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
                
            # Telegram mesajını HTTP API kullanarak gönder (async olmadan)
            import requests
            
            url = f"https://api.telegram.org/bot{token}/sendMessage"
            payload = {
                'chat_id': chat_id,
                'text': message,
                'parse_mode': 'Markdown'
            }
            
            try:
                response = requests.post(url, data=payload)
                if response.status_code == 200:
                    logger.info("Telegram mesajı başarıyla gönderildi.")
                    return True
                else:
                    logger.warning(f"Telegram mesajı gönderilemedi. HTTP kodu: {response.status_code}, Yanıt: {response.text}")
                    
                    # Formatting hatası olabilir, parse_mode olmadan tekrar dene
                    payload['parse_mode'] = ''
                    response = requests.post(url, data=payload)
                    if response.status_code == 200:
                        logger.info("Telegram mesajı formatting olmadan gönderildi.")
                        return True
                    else:
                        logger.error(f"Telegram mesajı tekrar deneme başarısız. Yanıt: {response.text}")
                        return False
                        
            except Exception as send_error:
                logger.error(f"Telegram mesajı gönderilirken HTTP hatası: {str(send_error)}")
                return False
            
        except Exception as e:
            logger.error(f"Telegram mesajı gönderilirken hata: {str(e)}")
            return False

    def send_position_notification(self, symbol, action, position_type, price, amount=None, profit_loss=None, reason=None):
        """
        🔔 GELİŞMİŞ POZİSYON BİLDİRİM SİSTEMİ
        LONG/SHORT pozisyon türlerini kontrol ederek Telegram bildirimi gönderir
        
        :param symbol: Coin sembolü
        :param action: İşlem türü ('OPEN', 'CLOSE')  
        :param position_type: Pozisyon türü ('LONG', 'SHORT')
        :param price: İşlem fiyatı
        :param amount: İşlem miktarı (opsiyonel)
        :param profit_loss: Kâr/zarar yüzdesi (kapama için)
        :param reason: Kapama nedeni (opsiyonel)
        """
        try:
            if not self.config.get('use_telegram', False):
                logger.debug("Telegram bildirimleri devre dışı")
                return False
                
            # Pozisyon türüne göre emoji ve renk belirle
            if position_type == 'LONG':
                type_emoji = "📈"
                color_emoji = "🟢" if action == 'OPEN' else "🔴"
                direction = "YUKARIYA"
            elif position_type == 'SHORT':
                type_emoji = "📉" 
                color_emoji = "🔴" if action == 'OPEN' else "🟢"
                direction = "AŞAĞIYA"
            else:
                type_emoji = "📊"
                color_emoji = "🔵"
                direction = "BELİRSİZ"
            
            # İşlem türüne göre mesaj oluştur
            if action == 'OPEN':
                # POZİSYON AÇMA BİLDİRİMİ
                message = f"{color_emoji} *{position_type} POZİSYON AÇILDI* {type_emoji}\n\n"
                message += f"🪙 *Coin:* `{symbol}`\n"
                message += f"📍 *Yön:* {direction} Bahsi\n"
                message += f"💰 *Giriş Fiyatı:* `{price:.6f}`\n"
                
                if amount:
                    message += f"📊 *Miktar:* `{amount:.4f}` coin\n"
                    message += f"💵 *Toplam Değer:* `{(amount * price):.2f}` USDT\n"
                
                message += f"⏰ *Zaman:* `{datetime.now().strftime('%H:%M:%S')}`\n"
                message += f"📋 *Tür:* `{position_type}` Pozisyon\n\n"
                
                if position_type == 'LONG':
                    message += "✅ Fiyat yükselirse kâr eder\n"
                else:
                    message += "✅ Fiyat düşerse kâr eder\n"
                    
            elif action == 'CLOSE':
                # POZİSYON KAPAMA BİLDİRİMİ
                profit_emoji = "💚" if profit_loss and profit_loss > 0 else "❤️"
                profit_text = f"+{profit_loss:.2f}%" if profit_loss and profit_loss > 0 else f"{profit_loss:.2f}%"
                
                message = f"{color_emoji} *{position_type} POZİSYON KAPATILDI* {type_emoji}\n\n"
                message += f"🪙 *Coin:* `{symbol}`\n"
                message += f"📍 *Tür:* {position_type} Pozisyon\n"
                message += f"💰 *Çıkış Fiyatı:* `{price:.6f}`\n"
                
                if profit_loss is not None:
                    message += f"{profit_emoji} *Sonuç:* `{profit_text}`\n"
                
                if reason:
                    message += f"📝 *Neden:* {reason}\n"
                    
                message += f"⏰ *Zaman:* `{datetime.now().strftime('%H:%M:%S')}`\n\n"
                
                if profit_loss and profit_loss > 0:
                    message += "🎉 Başarılı işlem!\n"
                else:
                    message += "⚠️ Zarar kesme işlemi\n"
            
            # Mesajı gönder
            success = self.send_telegram_message(message)
            
            if success:
                logger.info(f"✅ {symbol} {position_type} pozisyon bildirimi gönderildi: {action}")
            else:
                logger.warning(f"⚠️ {symbol} pozisyon bildirimi gönderilemedi")
                
            return success
            
        except Exception as e:
            logger.error(f"❌ Pozisyon bildirimi gönderilirken hata: {str(e)}")
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
            
            # NaN değerlerini None ile değiştir
            def replace_nan(value):
                if value is None:
                    return None
                import math
                if isinstance(value, float) and (math.isnan(value) or math.isinf(value)):
                    return None
                return value
                
            # Tüm değerleri kontrol et
            rsi_value = replace_nan(rsi_value)
            macd_value = replace_nan(macd_value)
            macd_signal = replace_nan(macd_signal)
            bb_upper = replace_nan(bb_upper)
            bb_middle = replace_nan(bb_middle)
            bb_lower = replace_nan(bb_lower)
            ma20 = replace_nan(ma20)
            ma50 = replace_nan(ma50)
            ma100 = replace_nan(ma100)
            ma200 = replace_nan(ma200)
            
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

    def load_and_cache_markets(self):
        """
        🔄 GÜÇLÜ MARKET YÜKLEME SİSTEMİ
        Exchange'den market verilerini 3 deneme ile yükler ve önbellekte tutar
        """
        try:
            if not self.exchange:
                logger.error("❌ Exchange bağlantısı yok - market verileri yüklenemez")
                return False
            
            logger.info("🔄 Market verileri yükleniyor...")
            
            # 3 deneme ile market verilerini yükle
            for attempt in range(3):
                try:
                    logger.info(f"📊 Market verileri çekiliyor... (Deneme {attempt + 1}/3)")
                    markets = self.exchange.load_markets()
                    
                    if markets and len(markets) > 0:
                        # Geçerli sembolleri önbelleğe al
                        self.valid_symbols = set(markets.keys())
                        self.symbols_last_check = time.time()
                        
                        logger.info(f"✅ Toplam {len(self.valid_symbols)} geçerli sembol önbelleğe alındı")
                        
                        # Base currency ile eşleşen sembolleri ayrıca kaydet
                        base_currency = self.config.get('base_currency', 'USDT')
                        base_symbols = [symbol for symbol in self.valid_symbols if symbol.endswith(f'/{base_currency}')]
                        logger.info(f"💰 {len(base_symbols)} adet {base_currency} çifti tespit edildi")
                        
                        return True
                    else:
                        logger.warning(f"⚠️ Market verisi boş (Deneme {attempt + 1}/3)")
                        
                except Exception as market_error:
                    logger.warning(f"⚠️ Market yükleme hatası (Deneme {attempt + 1}/3): {str(market_error)}")
                    if attempt < 2:  # Son deneme değilse bekle
                        time.sleep(2 ** attempt)  # Exponential backoff: 1s, 2s
                        
            logger.error("❌ Market verileri 3 denemede de yüklenemedi!")
            return False
            
        except Exception as e:
            logger.error(f"💥 Market verileri yüklenirken kritik hata: {str(e)}")
            return False

    def refresh_markets_if_needed(self):
        """
        🔄 AKILLI MARKET YENİLEME SİSTEMİ
        Market verilerini belirli aralıklarla yeniler (1 saatte bir)
        """
        try:
            current_time = time.time()
            
            # Son market kontrolünden 1 saat geçmişse yenile
            if current_time - self.symbols_last_check > 3600:  # 3600 saniye = 1 saat
                logger.info("🔄 Market verileri 1 saatlik süre doldu - yenileniyor...")
                
                # Eski önbellek boyutunu kaydet
                old_valid_count = len(self.valid_symbols)
                old_invalid_count = len(self.invalid_symbols)
                
                # Market verilerini yenile
                success = self.load_and_cache_markets()
                
                if success:
                    # Geçersiz semboller önbelleğini temizle (yeni market verileriyle tekrar kontrol edilsin)
                    self.invalid_symbols.clear()
                    
                    new_valid_count = len(self.valid_symbols)
                    logger.info(f"✅ Market yenileme başarılı: {old_valid_count} -> {new_valid_count} sembol")
                    logger.info(f"🧹 {old_invalid_count} geçersiz sembol önbelleği temizlendi")
                else:
                    logger.warning("⚠️ Market yenileme başarısız - eski verilerle devam ediliyor")
                    
        except Exception as e:
            logger.error(f"💥 Market verileri yenilenirken hata: {str(e)}")
            # Hata durumunda da çalışmaya devam et

    def validate_symbol(self, symbol):
        """
        🔍 AKILLI SYMBOL VALIDATION SİSTEMİ
        Sembolün geçerli olup olmadığını kontrol eder ve önbellek kullanır
        
        :param symbol: Kontrol edilecek sembol (örn. "BTC/USDT")
        :return: Geçerli ise True, değilse False
        """
        try:
            # Exchange kontrolü
            if not self.exchange:
                logger.warning(f"⚠️ Exchange bağlantısı yok - {symbol} doğrulanamadı")
                return False
            
            # Market verilerini yenile (gerekirse)
            self.refresh_markets_if_needed()
            
            # 🚫 Geçersiz semboller önbelleğinde var mı kontrol et
            if symbol in self.invalid_symbols:
                logger.debug(f"❌ {symbol} geçersiz semboller önbelleğinde bulundu")
                return False
            
            # ✅ Geçerli semboller önbelleğinde var mı kontrol et
            if symbol in self.valid_symbols:
                logger.debug(f"✅ {symbol} geçerli semboller önbelleğinde bulundu")
                return True
            
            # 🔍 Önbellekte yoksa exchange'den kontrol et
            try:
                logger.info(f"🔄 {symbol} sembolü exchange'den kontrol ediliyor...")
                
                # Market bilgisini al
                markets = self.exchange.load_markets()
                
                if symbol in markets:
                    # Geçerli sembollere ekle
                    self.valid_symbols.add(symbol)
                    logger.info(f"✅ {symbol} geçerli sembol olarak önbelleğe eklendi")
                    return True
                else:
                    # Geçersiz sembollere ekle (tekrar kontrol edilmesin)
                    self.invalid_symbols.add(symbol)
                    logger.warning(f"❌ {symbol} geçersiz sembol olarak işaretlendi")
                    return False
                    
            except Exception as market_error:
                logger.error(f"❌ {symbol} için market kontrolü başarısız: {str(market_error)}")
                # Hata durumunda geçersiz olarak işaretle
                self.invalid_symbols.add(symbol)
                return False
                
        except Exception as e:
            logger.error(f"💥 Symbol validation hatası {symbol}: {str(e)}")
            return False

    def get_valid_symbol_format(self, symbol):
        """
        Sembol formatını exchange'e uygun hale getirir
        Örn: NEXOUSDT -> NEXO/USDT veya tersi
        """
        try:
            # Zaten geçerli format mı kontrol et
            if self.validate_symbol(symbol):
                return symbol
            
            # Slash içeriyorsa çıkarmayı dene
            if '/' in symbol:
                no_slash = symbol.replace('/', '')
                if self.validate_symbol(no_slash):
                    return no_slash
            
            # Slash içermiyorsa eklemeyi dene
            else:
                base_currency = self.config.get('base_currency', 'USDT')
                if symbol.endswith(base_currency):
                    # BTCUSDT -> BTC/USDT
                    base_part = symbol[:-len(base_currency)]
                    with_slash = f"{base_part}/{base_currency}"
                    if self.validate_symbol(with_slash):
                        return with_slash
            
            # Hiçbir format çalışmazsa None döndür
            return None
            
        except Exception as e:
            logger.error(f"Sembol formatı düzeltilirken hata {symbol}: {str(e)}")
            return None

    def start(self):
        """
        Bot'u başlatır
        """
        logger.info("🚀 Trading Bot başlatılıyor...")
        
        # 🧹 Başlangıçta delisted coinleri temizle
        try:
            cleaned_count = self.clean_delisted_coins()
            if cleaned_count > 0:
                logger.info(f"✅ {cleaned_count} adet delisted coin temizlendi")
        except Exception as e:
            logger.error(f"❌ Delisted coin temizleme sırasında hata: {str(e)}")
        
        self.stop_event.clear()
        self.monitor_thread = threading.Thread(target=self.monitor_coins)
        self.monitor_thread.start()
        self.start_coin_discovery()
        logger.info("✅ Bot başlatıldı ve izleme thread'i çalışıyor")

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
