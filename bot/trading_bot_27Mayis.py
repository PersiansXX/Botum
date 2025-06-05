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
import mysql.connector
import math
from datetime import datetime, timedelta
import telegram
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
        self.load_open_positions()
        
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
                self.load_telegram_settings()
                
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
                    'type': pos.get('position_type', 'LONG'),
                    'entry_price': float(pos['entry_price']) if pos['entry_price'] else 0,
                    'amount': float(pos.get('quantity', 0)),
                    'entry_time': pos['entry_time'].strftime('%Y-%m-%d %H:%M:%S') if isinstance(pos['entry_time'], datetime) else str(pos['entry_time']) if pos['entry_time'] else datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
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
                    position['trade_mode'] = 'paper'
                
                if 'leverage' in pos and pos['leverage']:
                    position['leverage'] = int(pos['leverage'])
                else:
                    position['leverage'] = 1
                    
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
            
            # Corrected loop to avoid unpacking error
            for symbol_chunk in symbol_chunks:
                try:
                    # Process each symbol in the chunk
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
                    logger.error(f"Error processing chunk: {str(chunk_error)}")
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
                        'buy_signals': analysis.get('signals', {}).get('buy_count', 0),
                        'sell_signals': analysis.get('signals', {}).get('sell_count', 0),
                        'neutral_signals': analysis.get('signals', {}).get('neutral_count', 0),
                        'price': analysis['price']
                    }
                         
                highly_potential_coins.append(coin)
                
                # Auto discovery ayarlarını kontrol et
                auto_add_to_watchlist = False  # Varsayılan değeri False olarak değiştiriyoruz
                
                # Auto discovery ayarlarını yapılandırmadan al (eğer mevcutsa)
                if 'auto_discovery' in self.config:
                    auto_add_to_watchlist = self.config['auto_discovery'].get('auto_add_to_watchlist', False)
                
                # Sadece auto_add_to_watchlist ayarı açıksa aktif listeye ekle
                if auto_add_to_watchlist:
                    logger.info(f"{symbol} otomatik olarak aktif izleme listesine ekleniyor (auto_add_to_watchlist=True)")
                    self.add_coin_to_active_list(symbol)
                else:
                    logger.info(f"{symbol} aktif izleme listesine otomatik eklenmedi (auto_add_to_watchlist=False)")

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
                UPDATE active_coINS SET is_active = 1, price = %s, `signal` = %s, added_by = 'bot_update', last_updated = NOW()
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
            # Veritabanı bağlantı bilgileri
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
            # Hata durumunda bağlantıları düzgünce kapat
            try:
                if 'cursor' in locals() and cursor:
                    cursor.close()
                if 'conn' in locals() and conn:
                    conn.close()
            except:
                pass
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
                    
                    # Ensure the message does not contain unescaped special characters
                    message = message.replace('_', '\_').replace('*', '\*').replace('[', '\[').replace(']', '\]').replace('`', '\`')
                    
                    self.send_telegram_message(message)
                
                return True
            
            # Gerçek işlem modu (canlı)
            elif self.config.get('trade_mode') == 'live':
                logger.info(f"CANLI MOD: {symbol} pozisyon kapatılıyor. Fiyat: {close_price:.4f}, Kâr/Zarar: {profit_loss_pct:.2f}%")
                
                # Exchange API ile satış yap
                try:
                    # Satış işlemi (LONG pozisyonlar için)
                    if position['type'] == 'LONG':
                        try:
                            # Satış miktarını al
                            amount = position['amount']
                            
                            # Satış emrini oluştur
                            market_type = position.get('market_type', 'spot')
                            
                            if market_type == 'futures':
                                # Futures pozisyonu kapatma
                                logger.info(f"Futures pozisyonu kapatılıyor: {symbol}, Miktar: {amount}")
                                order = self.exchange.create_market_sell_order(symbol, amount, {'type': 'future'})
                                logger.info(f"Futures satış emri başarıyla gönderildi: {order}")
                            elif market_type == 'margin':
                                # Margin pozisyonu kapatma
                                logger.info(f"Margin pozisyonu kapatılıyor: {symbol}, Miktar: {amount}")
                                order = self.exchange.create_market_sell_order(symbol, amount, {'type': 'margin'})
                                logger.info(f"Margin satış emri başarıyla gönderildi: {order}")
                            else:
                                # Spot pozisyonu kapatma
                                logger.info(f"Spot pozisyonu kapatılıyor: {symbol}, Miktar: {amount}")
                                order = self.exchange.create_market_sell_order(symbol, amount)
                                logger.info(f"Spot satış emri başarıyla gönderildi: {order}")
                            
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
                                
                                # Ensure the message does not contain unescaped special characters
                                message = message.replace('_', '\_').replace('*', '\*').replace('[', '\[').replace(']', '\]').replace('`', '\`')
                                
                                self.send_telegram_message(message)
                                
                            return True
                            
                        except ccxt.InsufficientFunds as e:
                            # Bakiye yetersizliği hatası - düzgün şekilde işle
                            logger.error(f"CANLI pozisyon kapatılırken yetersiz bakiye hatası: {symbol}, {str(e)}")
                            
                            # Veritabanındaki pozisyonu yine de kapat (manuel kapatma gerekecek)
                            logger.info(f"Pozisyon veritabanında kapatılıyor ancak manuel işlem gerekebilir: {symbol}")
                            self.close_position_in_db(position['id'] if 'id' in position else symbol, close_price, profit_loss_pct, "manual_required")
                            
                            # Pozisyonu listeden kaldır
                            if position in self.open_positions:
                                self.open_positions.remove(position)
                                
                            # Telegram uyarısı gönder
                            if self.config.get('use_telegram', False):
                                error_message = f"⚠️ *İşlem Hatası*\n"
                                error_message += f"Sembol: `{symbol}`\n"
                                error_message += f"Hata: `Yetersiz bakiye. Pozisyon manuel olarak kapatılmalı!`\n"
                                error_message += f"Detay: `{str(e)}`"
                                
                                error_message = error_message.replace('_', '\_').replace('*', '\*').replace('[', '\[').replace(']', '\]').replace('`', '\`')
                                self.send_telegram_message(error_message)
                                
                            return False
                    
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
            if isinstance(position_id_or_symbol, int) or (isinstance(position_id_or_symbol, str) and position_id_or_symbol.isdigit()):
                # ID ile güncelleme
                where_clause = "id = %s"
                where_value = int(position_id_or_symbol)
            else:
                # Sembol ile güncelleme
                where_clause = "symbol = %s AND status = 'OPEN'"
                where_value = position_id_or_symbol
            
            # Kapanış notu oluştur - CONCAT kullanımını kaldırıp string birleştirmeye değiştirdim
            close_note = f"{close_reason} - {profit_loss_pct:+.2f}%"
            
            # Pozisyonu kapat - CONCAT fonksiyonunu kaldırdım
            update_query = f"""
            UPDATE open_positions SET 
                exit_price = %s,
                exit_time = NOW(),
                profit_loss_pct = %s,
                close_reason = %s,
                status = 'CLOSED',
                notes = %s
            WHERE {where_clause}
            """
            
            cursor.execute(update_query, (
                float(close_price),
                float(profit_loss_pct),
                close_reason,
                close_note,
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
            if isinstance(position_id_or_symbol, int) or (isinstance(position_id_or_symbol, str) and position_id_or_symbol.isdigit()):
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
                    float(position_dict.get('quantity')),  # Veritabanından quantity alınıyor
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
            # Hata durumunda bağlantıları düzgünce kapat
            try:
                if 'cursor' in locals() and cursor:
                    cursor.close()
                if 'conn' in locals() and conn:
                    conn.close()
            except:
                pass
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
                                  f"max_trade_amount={self.config.get('max_trade_amount')}, "                                  f"trade_mode={self.config.get('trade_mode')}, " +
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

    def validate_symbol(self, symbol):
        """
        Sembolün geçerli olup olmadığını kontrol eder
        
        :param symbol: Kontrol edilecek sembol (örn. "BTC/USDT")
        :return: Geçerli ise True, değilse False
        """
        try:
            # Exchange'den market bilgilerini al
            if not hasattr(self, 'exchange') or self.exchange is None:
                return False
                
            # Market bilgisini kontrol et
            markets = self.exchange.markets
            if not markets:
                try:
                    markets = self.exchange.load_markets()
                except Exception as e:
                    if "Invalid API-key" in str(e):
                        logger.error(f"API anahtarı hatası: {str(e)}")
                    else:
                        logger.error(f"Market bilgileri alınamadı: {str(e)}")
                    return False
                
            # Sembol geçerli mi kontrol et
            if symbol in markets:
                # Ayrıca sembolün aktif olup olmadığını kontrol et
                market = markets[symbol]
                if market.get('active', True):
                    return True
                else:
                    logger.debug(f"{symbol} sembolü aktif değil")
                    return False
            else:
                logger.debug(f"{symbol} geçersiz sembol")
                return False
                
        except Exception as e:
            # API hatalarını özel olarak handle et
            if "Invalid symbol" in str(e):
                logger.debug(f"{symbol} geçersiz sembol: {str(e)}")
                return False
            elif "Invalid API-key" in str(e):
                logger.error(f"API anahtarı hatası {symbol}: {str(e)}")
                return False
            else:
                logger.error(f"Symbol validation hatası {symbol}: {str(e)}")
                return False

    def analyze_coin(self, symbol):
        """
        Tek bir coini analiz eder - threadpool için optimize edilmiş
        
        :param symbol: Coin sembolü
        :return: Analiz sonuçları
        """
        try:
            # Önce sembolün geçerli olup olmadığını kontrol et
            if not self.validate_symbol(symbol):
                logger.warning(f"{symbol} geçersiz sembol olduğu için analiz atlanıyor")
                return None
                
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
                    open_position = next((p for p in self.open_positions if p['symbol'] == symbol), None)
                    if open_position:
                        self.send_trading_signal_alert(symbol, 'SELL', analysis)
            
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
            
            # Ensure the message does not contain unescaped special characters
            message = message.replace('_', '\_').replace('*', '\*').replace('[', '\[').replace(']', '\]').replace('`', '\`')
            
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
            
            # combined_signals'ın türünü kontrol et ve gerekirse düzelt
            if isinstance(combined_signals, str):
                logger.warning(f"{symbol} için combined_signals string döndü: {combined_signals}")
                # String ise varsayılan dictionary yapısı oluştur
                combined_signals = {
                    'trade_signal': {
                        'BUY': 0,
                        'SELL': 0, 
                        'NEUTRAL': 1
                    }
                }
            elif combined_signals is None:
                logger.warning(f"{symbol} için combined_signals None döndü")
                combined_signals = {
                    'trade_signal': {
                        'BUY': 0,
                        'SELL': 0,
                        'NEUTRAL': 1
                    }
                }
            
            # Çoklu zaman aralığı konsensusunu strateji sinyalleriyle birleştir
            buy_signals = combined_signals.get('trade_signal', {}).get('BUY', 0) + sum(1 for s in strategy_results.values() if s.get('signal') == 'BUY')
            sell_signals = combined_signals.get('trade_signal', {}).get('SELL', 0) + sum(1 for s in strategy_results.values() if s.get('signal') == 'SELL')
            neutral_signals = combined_signals.get('trade_signal', {}).get('NEUTRAL', 0) + sum(1 for s in strategy_results.values() if s.get('signal') == 'NEUTRAL')
            
            # Son fiyatı al (ilk timeframe'den)
            last_close = ohlcv_data['close'].iloc[-1]
            
            # Nihai sinyal kararı
            if isinstance(combined_signals, dict):
                final_signal = combined_signals.get('trade_signal', 'NEUTRAL')
                # Eğer trade_signal da dict ise, en yüksek değerli sinyali seç
                if isinstance(final_signal, dict):
                    max_signal = max(final_signal, key=final_signal.get) if final_signal else 'NEUTRAL'
                    final_signal = max_signal
            else:
                final_signal = 'NEUTRAL'
            
            # Stratejilerden gelen sinyalleri değerlendir
            strategy_signals = {'BUY': 0, 'SELL': 0, 'NEUTRAL': 0}
            for strategy_name, strategy_data in strategy_results.items():
                signal = strategy_data.get('signal', 'NEUTRAL')
                strategy_signals[signal] += 1
            
            # Alım ve satım sinyallerini güçlendir
            # Eğer en az bir strateji alım sinyali veriyorsa ve hiçbir strateji satım sinyali vermiyorsa BUY'a yönlendir
            if strategy_signals['BUY'] > 0 and strategy_signals['SELL'] == 0:
                if buy_signals > sell_signals:
                    final_signal = 'BUY'
            
            # Eğer en az bir strateji satım sinyali veriyorsa ve hiçbir strateji alım sinyali vermiyorsa SELL'e yönlendir
            elif strategy_signals['SELL'] > 0 and strategy_signals['BUY'] == 0:
                if sell_signals > buy_signals:
                    final_signal = 'SELL'
            
            # Eğer buy_signals sell_signals'dan belirgin şekilde fazlaysa BUY sinyali ver
            if buy_signals > sell_signals * 2 and buy_signals >= 2:
                final_signal = 'BUY'
            
            # Eğer sell_signals buy_signals'dan belirgin şekilde fazlaysa SELL sinyali ver
            elif sell_signals > buy_signals * 2 and sell_signals >= 2:
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
                # İşlem miktarını analiz sonuçlarına göre dinamik belirle
                trade_amount = self.calculate_dynamic_position_size(symbol, analysis)
                logger.info(f"Dinamik işlem miktarı hesaplandı: {trade_amount} USDT")
                analysis['dynamic_trade_amount'] = trade_amount
                
                # Adjust trade amount to be within min and max limits
                trade_amount = max(self.config['min_trade_amount'], min(trade_amount, self.config['max_trade_amount']))
                if trade_amount < self.config['min_trade_amount']:
                    logger.error(f"Trade amount {trade_amount} is below the minimum notional requirement of {self.config['min_trade_amount']}.")
                    return False
                elif trade_amount > self.config['max_trade_amount']:
                    logger.warning(f"Trade amount {trade_amount} exceeds the maximum limit of {self.config['max_trade_amount']}. Adjusting to maximum.")
                
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

    def calculate_dynamic_position_size(self, symbol, analysis):
        """
        Sinyal kalitesine, risk yönetimi ayarlarına ve coin özelliklerine göre işlem miktarını TAMAMEN dinamik belirler
        
        :param symbol: Coin sembolü
        :param analysis: Analiz sonuçları
        :return: Hesaplanmış işlem miktarı (USDT)
        """
        try:
            # Minimum ve maksimum işlem tutarlarını al
            min_trade_amount = float(self.config.get('min_trade_amount', 10))
            max_trade_amount = float(self.config.get('max_trade_amount', 1000))
            
            # Hesap bakiyesini al
            account_balance = float(self.config.get('account_balance', 0))
            
            # Hesap bakiyesi yoksa veya çok küçükse minimum tutarı kullan
            if account_balance <= min_trade_amount:
                return min_trade_amount
                
            # Hesap bakiyesine göre maksimum işlem tutarını sınırla
            # Hesap bakiyesinin en fazla %20'sini kullan
            max_trade_by_balance = account_balance * 0.2
            
            # Eğer maksimum tutar bakiyenin %20'sinden az ise onu kullan
            if max_trade_amount > max_trade_by_balance:
                max_trade_amount = max_trade_by_balance
            
            # COIN BAZLI FAKTÖRLER
            
            # 1. Fiyat Volatilitesi - Yüksek volatilite, daha düşük işlem tutarı
            volatility_factor = 1.0
            try:
                if 'price_volatility' in analysis:
                    volatility = analysis['price_volatility']
                else:
                    # Son fiyatları al
                    ohlcv = self.exchange.fetch_ohlcv(symbol, '1d', limit=10)
                    if ohlcv and len(ohlcv) >= 7:
                        # Son 7 günün fiyatlarını al
                        closes = [item[4] for item in ohlcv[-7:]]
                        # Standart sapma hesapla
                        import numpy as np
                        std_dev = np.std(closes)
                        mean_price = np.mean(closes)
                        volatility = (std_dev / mean_price) * 100  # Yüzde olarak volatilite
                        
                        # Volatilite faktörü: Yüksek volatilite = Düşük faktör (0.6-1.0 arası)
                        # %5'in üzerindeki volatilite için kademeli azaltma
                        if volatility > 15:
                            volatility_factor = 0.6  # Çok yüksek volatilite
                        elif volatility > 10:
                            volatility_factor = 0.7  # Yüksek volatilite
                        elif volatility > 5:
                            volatility_factor = 0.8  # Orta volatilite
                        else:
                            volatility_factor = 1.0  # Düşük volatilite
                    
            except Exception as e:
                logger.error(f"Volatilite hesaplanırken hata: {str(e)}")
                volatility_factor = 0.8  # Hata durumunda orta değer kullan
            
            # 2. İşlem Hacmi - Yüksek hacim, daha yüksek işlem tutarı
            volume_factor = 1.0
            try:
                ticker = self.exchange.fetch_ticker(symbol)
                volume_usd = ticker['quoteVolume'] if 'quoteVolume' in ticker else ticker['volume'] * ticker['last']
                
                # Hacim faktörünü belirle
                if volume_usd > 100000000:  # 100M$ üzeri
                    volume_factor = 1.2      # %20 artış
                elif volume_usd > 50000000:  # 50M$ üzeri
                    volume_factor = 1.1      # %10 artış
                elif volume_usd > 10000000:  # 10M$ üzeri
                    volume_factor = 1.0      # Değişim yok
                elif volume_usd > 1000000:   # 1M$ üzeri
                    volume_factor = 0.9      # %10 azalış
                else:
                    volume_factor = 0.8      # %20 azalış
            except Exception as e:
                logger.error(f"Hacim faktörü hesaplanırken hata: {str(e)}")
                volume_factor = 1.0  # Hata durumunda değişim yapma
            
            # 3. Market derinliği - Yüksek derinlik, daha yüksek işlem tutarı
            liquidity_factor = 1.0
            try:
                # Eğer destekleniyorsa, emir defterini kontrol et
                if hasattr(self.exchange, 'fetch_order_book'):
                    order_book = self.exchange.fetch_order_book(symbol)
                    asks_depth = sum([ask[1] for ask in order_book['asks'][:5]]) # İlk 5 satış emri
                    bids_depth = sum([bid[1] for bid in order_book['bids'][:5]]) # İlk 5 alış emri
                    
                    # Toplam derinlik (hacim bazında)
                    total_depth = asks_depth + bids_depth
                    
                    # Derinlik faktörünü belirle
                    if total_depth > 1000:  # Çok yüksek derinlik
                        liquidity_factor = 1.2
                    elif total_depth > 500:  # Yüksek derinlik
                        liquidity_factor = 1.1
                    elif total_depth > 100:  # Orta derinlik
                        liquidity_factor = 1.0
                    else:
                        liquidity_factor = 0.9
            except Exception as e:
                logger.error(f"Likidite faktörü hesaplanırken hata: {str(e)}")
                liquidity_factor = 1.0  # Hata durumunda değişim yapma
            
            # SİNYAL KALİTESİ FAKTÖRÜ (mevcut koddan)
            buy_signals = analysis.get('signals', {}).get('buy_count', 0)
            sell_signals = analysis.get('signals', {}).get('sell_count', 0)
            neutral_signals = analysis.get('signals', {}).get('neutral_count', 0)
            
            total_signals = max(1, buy_signals + sell_signals + neutral_signals)
            signal_quality = buy_signals / total_signals
            
            # Sinyal kalitesi faktörünü belirle
            if signal_quality > 0.8:      # Çok güçlü sinyal
                signal_factor = 1.0       # Tam miktar
            elif signal_quality > 0.6:    # Güçlü sinyal
                signal_factor = 0.8       # %80 miktar
            elif signal_quality > 0.4:    # Orta sinyal
                signal_factor = 0.6       # %60 miktar
            else:                         # Zayıf sinyal
                signal_factor = 0.5       # %50 miktar
            
            # 4. Trend gücü - Güçlü trend, daha yüksek işlem tutarı
            trend_factor = 1.0
            try:
                if 'trend_strength' in analysis:
                    trend_strength = analysis['trend_strength']
                    if trend_strength > 0.8:
                        trend_factor = 1.2  # Çok güçlü trend
                    elif trend_strength > 0.6:
                        trend_factor = 1.1  # Güçlü trend
                    elif trend_strength > 0.4:
                        trend_factor = 1.0  # Normal trend
                    else:
                        trend_factor = 0.9  # Zayıf trend
            except Exception as e:
                logger.error(f"Trend faktörü hesaplanırken hata: {str(e)}")
                trend_factor = 1.0  # Hata durumunda değişim yapma
            
            # 5. Piyasa durumu - Genel piyasa durumunu kontrol et
            market_condition_factor = 1.0
            try:
                # BTC/USDT durumuna bakarak genel piyasayı değerlendir
                btc_analysis = self.analyze_combined_indicators("BTC/USDT")
                if btc_analysis:
                    btc_signal = btc_analysis.get('trade_signal', 'NEUTRAL')
                    if btc_signal == 'BUY':
                        market_condition_factor = 1.1  # Pozitif piyasa
                    elif btc_signal == 'SELL':
                        market_condition_factor = 0.8  # Negatif piyasa
            except Exception as e:
                logger.error(f"Piyasa durum faktörü hesaplanırken hata: {str(e)}")
                market_condition_factor = 1.0  # Hata durumunda değişim yapma
                
            # TÜM FAKTÖRLERİ BİRLEŞTİR
            
            # Baz tutarı min ve max arasında ortalama olarak al
            base_amount = (min_trade_amount + max_trade_amount) / 2
            
            # Tüm faktörleri çarp
            combined_factor = signal_factor * volatility_factor * volume_factor * liquidity_factor * trend_factor * market_condition_factor
            
            # Faktöre göre baz tutarı ayarla
            calculated_amount = base_amount * combined_factor
            
            # Son kontrol - minimum ve maksimum sınırları aşmasın
            if calculated_amount < min_trade_amount:
                calculated_amount = min_trade_amount
                
            if calculated_amount > max_trade_amount:
                calculated_amount = max_trade_amount
                
            # İki ondalık basamağa yuvarla
            dynamic_amount = round(calculated_amount, 2)
            
            logger.info(f"{symbol} için dinamik işlem tutarı hesaplandı: {dynamic_amount} USDT")
            logger.debug(f"İşlem faktörleri: Sinyal: {signal_factor:.2f}, Volatilite: {volatility_factor:.2f}, " 
                       f"Hacim: {volume_factor:.2f}, Likidite: {liquidity_factor:.2f}, "
                       f"Trend: {trend_factor:.2f}, Piyasa: {market_condition_factor:.2f}")
            
            return dynamic_amount
            
        except Exception as e:
            logger.error(f"Dinamik pozisyon büyüklüğü hesaplanırken hata: {str(e)}")
            # Hata durumunda varsayılan min değere dön
            return float(self.config.get('min_trade_amount', 10.0))

    def open_position(self, symbol, analysis):
        """
        Yeni bir alım pozisyonu açar
        
        :param symbol: Coin sembolü
        :param analysis: Analiz sonuçları
        :return: Başarı durumu
        """
        try:
            # Pozisyon açma mantığını uygula
            # ÖNEMLİ: trade_mode yerine market_type kullan!
            market_type = self.config.get('market_type', 'spot')
            leverage = int(self.config.get('leverage', 1))
            
            # Mevcut fiyatı al
            current_price = analysis['price']
            
            # İşlem miktarını belirle (analiz sonuçlarından veya varsayılan değerden)
            trade_amount = analysis.get('dynamic_trade_amount', float(self.config.get('trade_amount', 10.0)))
            
            logger.info(f"{symbol} için {trade_amount} {self.config.get('base_currency', 'USDT')} tutarında alım yapılıyor...")
            logger.info(f"İşlem türü: {market_type} (kaldıraç: {leverage}x)")
            
            # Paper trade (simülasyon modu) kontrolü
            if self.config.get('trade_mode', 'paper') == 'paper':
                logger.info(f"TEST MOD: {symbol} için {market_type} alım simüle ediliyor. Miktar: {trade_amount}")
                
                # Simülasyon - hiçbir gerçek işlem yapmadan pozisyonu ekle
                pass
            else:
                # CANLI işlem yapılıyor
                logger.info(f"CANLI MOD: {symbol} için {market_type} alım yapılıyor. Miktar: {trade_amount}")
                
                try:
                    # İşlem türüne göre ayarlar
                    if market_type == 'futures':
                        # Futures için gerekli ayarları yap
                        try:
                            # Önce market bilgisini al
                            market_info = self.exchange.market(symbol)
                            if not market_info:
                                logger.error(f"{symbol} için market bilgisi alınamadı")
                                return False
                            
                            # Minimum notional değerini kontrol et
                            min_notional = 0
                            if 'limits' in market_info and 'cost' in market_info['limits'] and 'min' in market_info['limits']['cost']:
                                min_notional = float(market_info['limits']['cost']['min'])
                                logger.info(f"{symbol} için minimum notional: {min_notional}")
                            
                            # Eğer işlem miktarı minimum notional'dan küçükse artır
                            if min_notional > 0 and trade_amount < min_notional:
                                old_amount = trade_amount
                                trade_amount = min_notional * 1.1  # %10 fazla ekle
                                logger.warning(f"{symbol} için işlem miktarı minimum notional'dan küçük. {old_amount} -> {trade_amount}")
                            
                            # Kaldıraç ayarlarını kontrol et (sadece linear contracts için)
                            if leverage > 1:
                                try:
                                    # Market türünü kontrol et
                                    if market_info.get('linear', False):
                                        # Sadece linear contracts için margin mode ayarla
                                        if hasattr(self.exchange, 'setMarginMode'):
                                            margin_mode = self.config.get('leverage_mode', 'cross')
                                            self.exchange.setMarginMode(margin_mode, symbol)
                                            logger.info(f"{symbol} için margin mode {margin_mode} olarak ayarlandı")
                                        
                                        # Kaldıraç ayarla
                                        if hasattr(self.exchange, 'setLeverage'):
                                            self.exchange.setLeverage(leverage, symbol)
                                            logger.info(f"{symbol} için kaldıraç {leverage}x olarak ayarlandı")
                                    else:
                                        logger.info(f"{symbol} linear contract değil, kaldıraç ayarları atlanıyor")
                                        leverage = 1  # Kaldıracı 1x yap
                                        
                                except Exception as leverage_error:
                                    logger.warning(f"Kaldıraç ayarlanırken hata: {str(leverage_error)}")
                                    # Kaldıraç hatası varsa 1x ile devam et
                                    leverage = 1
                            
                            # Futures işlemi - MARKET emri ile
                            quantity = trade_amount / current_price
                            logger.info(f"Futures alım emri: {symbol}, Miktar: {quantity:.6f}, Değer: {trade_amount}")
                            
                            # Gerçek futures alım emri
                            order = self.exchange.create_market_buy_order(symbol, quantity, {'type': 'future'})
                            logger.info(f"Futures alım emri başarıyla gönderildi: {order}")
                            
                        except Exception as setup_error:
                            logger.error(f"Futures setup hatası: {str(setup_error)}")
                            return False
                    
                    elif market_type == 'margin':
                        # Margin işlemi
                        quantity = trade_amount / current_price
                        logger.info(f"Margin alım emri: {symbol}, Miktar: {quantity:.6f}")
                        order = self.exchange.create_market_buy_order(symbol, quantity, {'type': 'margin'})
                        logger.info(f"Margin alım emri başarıyla gönderildi: {order}")
                        
                    else:
                        # Spot işlemi
                        quantity = trade_amount / current_price
                        logger.info(f"Spot alım emri: {symbol}, Miktar: {quantity:.6f}")
                        order = self.exchange.create_market_buy_order(symbol, quantity)
                        logger.info(f"Spot alım emri başarıyla gönderildi: {order}")
                    
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
                'market_type': market_type,  # trade_mode yerine market_type kaydet
                'notes': f"Otomatik alım: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')} - İşlem türü: {market_type}"
            }
            
            # Pozisyonlar listesine ekle
            self.open_positions.append(position)
            
            # Veritabanına kaydet
            self.save_position(position)
            
            # Telegram ile bildirim gönder (eğer etkinleştirilmişse)
            if self.config.get('use_telegram', False):
                message = f"🟢 *Yeni Pozisyon Açıldı*\n"
                message += f"Sembol: `{symbol}`\n"
                message += f"İşlem Türü: `{market_type.upper()}`\n"
                message += f"Fiyat: `{current_price:.4f}`\n"
                message += f"Stop-Loss: `{stop_loss:.4f}`\n"
                message += f"Take-Profit: `{take_profit:.4f}`\n"
                message += f"Miktar: `{position['amount']:.6f}`\n"
                
                # Futures veya marjin ise kaldıraç bilgisini ekle
                if market_type in ['futures', 'margin']:
                    message += f"Kaldıraç: `{leverage}x`\n"
                    
                message += f"Mod: `{'TEST' if self.config.get('trade_mode') == 'paper' else 'CANLI'}`"
                
                # Ensure the message does not contain unescaped special characters
                message = message.replace('_', '\_').replace('*', '\*').replace('[', '\[').replace(']', '\]').replace('`', '\`')
                
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
            last_discovery_attempt = 0
            retry_count = 0
            
            while not self.stop_event.is_set():
                try:
                    current_time = time.time()
                    
                    # Veritabanından ayarları yenile (60 saniyede bir)
                    if current_time - last_settings_refresh > 60:
                        self.load_settings_from_db()
                        last_settings_refresh = current_time
                        logger.info("Keşif sistemi için ayarlar veritabanından yenilendi")
                    
                    # Keşif aralığını yenilenmiş config'den al
                    discovery_enabled = True
                    if 'auto_discovery' in self.config:
                        discovery_enabled = self.config['auto_discovery'].get('enabled', True)
                        
                    if not discovery_enabled:
                        logger.info("Otomatik coin keşfi devre dışı, 5 dakika bekleniyor...")
                        time.sleep(300)  # 5 dakika bekle
                        continue
                    
                    # Keşif aralığını belirle
                    discovery_interval = 0
                    if 'auto_discovery' in self.config and 'discovery_interval' in self.config['auto_discovery']:
                        discovery_interval = int(self.config['auto_discovery'].get('discovery_interval', 60))
                    else:  
                        discovery_interval = 60  # Varsayılan değer: 60 dakika
                        
                    # Minimum 15 dakika (900 saniye) aralık olsun
                    discovery_interval = max(900, discovery_interval * 60)  # Dakikayı saniyeye çevir
                    
                    # Son keşif denemesinden yeterli zaman geçti mi?
                    if current_time - last_discovery_attempt > discovery_interval:
                        logger.info(f"Yeni coin keşif denemesi başlatılıyor... (Son keşiften {(current_time - last_discovery_attempt) / 60:.1f} dakika geçti)")
                        
                        # Yeni coinleri keşfet
                        discovered_coins = self.discover_potential_coins()
                        
                        # Keşif zamanını güncelle
                        last_discovery_attempt = current_time
                        
                        # Keşfedilen coinleri logla
                        if discovered_coins:
                            logger.info(f"{len(discovered_coins)} yeni potansiyel coin keşfedildi")
                            
                            # Telegram ile bildirim gönder (eğer ayarlandıysa)
                            if self.config.get('use_telegram', False):
                                coins_message = "🔍 *Yeni Keşfedilen Coinler*\n\n"
                                for i, coin in enumerate(discovered_coins[:10], 1):  # İlk 10 coini göster
                                    symbol = coin['symbol']
                                    price_change = coin.get('price_change_pct', 0)
                                    volume_usd = coin.get('volume_usd', 0)
                                    
                                    coins_message += f"{i}. `{symbol}` - Değişim: {price_change:+.2f}% - Hacim: ${volume_usd:,.0f}\n"
                                
                                # Mesajı gönder
                                self.send_telegram_message(coins_message)
                        else:
                            logger.info("Yeni potansiyel coin keşfedilemedi")
                            
                        # Hata sayacını sıfırla
                        retry_count = 0
                    else:
                        # Bir sonraki keşfe kalan süre
                        remaining_time = discovery_interval - (current_time - last_discovery_attempt)
                        logger.info(f"Bir sonraki coin keşfine {remaining_time / 60:.1f} dakika kaldı, bekleniyor...")
                        
                        # Uzun bekleme sürelerini parçalara böl (durma komutu geldiyse hemen dur)
                        for _ in range(min(30, int(remaining_time / 60))):
                            if self.stop_event.is_set():
                                break
                            time.sleep(60)  # 1 dakika bekle
                        
                        # Kalan süre 30 dakikadan azsa, kalan süre kadar bekle
                        if remaining_time % 60 > 0 and not self.stop_event.is_set():
                            time.sleep(remaining_time % 60)
                        
                except Exception as e:
                    retry_count += 1
                    logger.error(f"Coin keşfetme döngüsünde hata: {str(e)}")
                    
                    # Hatadan sonra bekleme süresi (retryCount ile artan)
                    wait_time = min(60 * retry_count, 900)  # En fazla 15 dakika bekle
                    logger.info(f"Hata nedeniyle {wait_time / 60:.1f} dakika bekleniyor... (Deneme {retry_count})")
                    time.sleep(wait_time)
            
            logger.info("Coin keşif thread'i sonlandırıldı.")
        
        self.discovery_thread = threading.Thread(target=discovery_loop)
        self.discovery_thread.daemon = True  # Ana program kapanırsa thread de kapansın
        self.discovery_thread.start()
        logger.info("Coin keşif sistemi başlatıldı")
    
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
                    buy_signals INT DEFAULT 0,
                    sell_signals INT DEFAULT 0,
                    neutral_signals INT DEFAULT 0,
                    trade_signal VARCHAR(10) DEFAULT 'NEUTRAL',
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
                    
                    # Analiz verilerini doğru şekilde al
                    buy_signals = 0
                    sell_signals = 0
                    neutral_signals = 0
                    trade_signal = 'NEUTRAL'
                    
                    if 'analysis' in coin and coin['analysis']:
                        buy_signals = coin['analysis'].get('buy_signals', 0)
                        sell_signals = coin['analysis'].get('sell_signals', 0)
                        neutral_signals = coin['analysis'].get('neutral_signals', 0)
                        trade_signal = coin['analysis'].get('trade_signal', 'NEUTRAL')
                    
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
                            neutral_signals = %s,
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
                            buy_signals,
                            sell_signals,
                            neutral_signals,
                            trade_signal,
                            symbol
                        ))
                    else:
                        # Yeni ekle
                        insert_query = """
                        INSERT INTO discovered_coins (
                            symbol, discovery_time, price, volume_usd, price_change_pct,
                            buy_signals, sell_signals, neutral_signals, trade_signal, is_active
                        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 1)
                        """
                        cursor.execute(insert_query, (
                            symbol,
                            discovery_time,
                            float(coin['last_price']),
                            float(coin['volume_usd']),
                            float(coin['price_change_pct']),
                            buy_signals,
                            sell_signals,
                            neutral_signals,
                            trade_signal
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
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
            
            logger.info(f"{symbol} için analiz sonuçları başarıyla veritabanına kaydedildi.")
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
