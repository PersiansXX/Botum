import mysql.connector
import json
import logging
import os

class ConfigManager:
    def __init__(self):
        """
        Konfigürasyon yöneticisi sınıfı
        MySQL veritabanından bot_settings tablosundan konfigürasyonları yükler
        """
        self.logger = logging.getLogger("trading_bot")
        self.config = {}
        self.db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': 'Efsane44.',
            'database': 'trading_bot_db'
        }
        
    def load_config(self):
        """
        MySQL veritabanından bot yapılandırmasını yükle - bot_settings tablosundan
        başarısız olursa yerel JSON dosyasından yükle
        """
        try:
            # Veritabanı bağlantısı kur
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            # Ayarları MySQL'den oku - bot_settings tablosundan
            cursor.execute("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1")
            result = cursor.fetchone()
            
            if result:
                settings_json = None
                
                # Önce settings_json alanını kontrol et
                if 'settings_json' in result and result['settings_json']:
                    try:
                        settings_json = json.loads(result['settings_json'])
                        self.logger.info("Bot yapılandırması bot_settings tablosunun settings_json alanından başarıyla yüklendi")
                    except json.JSONDecodeError:
                        self.logger.error("settings_json alanı JSON formatına dönüştürülemedi!")
                        settings_json = None
                
                # settings_json boşsa, settings alanını dene
                if not settings_json and 'settings' in result and result['settings']:
                    try:
                        settings_json = json.loads(result['settings'])
                        self.logger.info("Bot yapılandırması bot_settings tablosunun settings alanından başarıyla yüklendi")
                        
                        # settings_json alanını güncelle ki sonraki sefer doğru yerden çekilsin
                        try:
                            update_query = "UPDATE bot_settings SET settings_json = %s WHERE id = %s"
                            cursor.execute(update_query, (result['settings'], result['id']))
                            conn.commit()
                            self.logger.info("settings alanındaki veriler settings_json alanına kopyalandı.")
                        except Exception as copy_error:
                            self.logger.error(f"Ayarlar kopyalanırken hata: {str(copy_error)}")
                            
                    except json.JSONDecodeError:
                        self.logger.error("settings alanı JSON formatına dönüştürülemedi!")
                        settings_json = None
                
                if settings_json:
                    self.config = settings_json
                    
                    # YENİ AYARLARI DA YÜKLE
                    self.load_notification_settings()
                    self.load_logging_settings()
                    self.load_monitoring_settings()
                    
                    cursor.close()
                    conn.close()
                    return self.config
                else:
                    self.logger.warning("bot_settings tablosunda geçerli yapılandırma bulunamadı, JSON dosyası kontrol ediliyor")
            else:
                self.logger.warning("bot_settings tablosunda kayıt bulunamadı, JSON dosyası kontrol ediliyor")
                
            cursor.close()
            conn.close()
            
            # Veritabanında bulunamadıysa JSON dosyadan oku
            config_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), "config", "bot_config.json")
            if os.path.exists(config_file):
                with open(config_file, 'r') as f:
                    self.config = json.load(f)
                self.logger.info("Bot yapılandırması JSON dosyasından başarıyla yüklendi")
                # JSON'dan yüklenen yapılandırmayı veritabanına kaydet
                self.save_config()
                return self.config
            else:
                self.logger.error("Bot yapılandırma dosyası bulunamadı")
                # Varsayılan yapılandırma oluştur
                self.config = self.create_default_config()
                return self.config
        
        except Exception as e:
            self.logger.error(f"Bot yapılandırması yüklenirken hata: {str(e)}")
            # Hata durumunda varsayılan
            self.config = self.create_default_config()
            return self.config
    
    def create_default_config(self):
        """Varsayılan bir konfigürasyon oluştur ve veritabanına kaydet"""
        default_config = {
            'exchange': 'binance',
            'base_currency': 'USDT',
            'trade_mode': 'paper',  # 'paper' veya 'live' olmalı, futures değil!
            'position_size': 0.02,
            'trade_amount': 10.0,
            'max_open_trades': 3,
            'stop_loss_pct': 2.0,
            'take_profit_pct': 3.0,
            'use_telegram': False,
            'indicators': {
                'bollinger_bands': {
                    'enabled': True,
                    'window': 20,
                    'num_std': 2.0
                },
                'rsi': {
                    'enabled': True,
                    'window': 14
                },
                'macd': {
                    'enabled': True,
                    'fast_period': 12,
                    'slow_period': 26,
                    'signal_period': 9
                },
                'moving_average': {
                    'enabled': True,
                    'short_window': 9,
                    'long_window': 21
                }
            }
        }
        
        try:
            # MySQL'e kaydet
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # JSON string'e dönüştür
            json_data = json.dumps(default_config, indent=4)
            
            # Yeni bir kayıt ekle
            insert_query = """
            INSERT INTO bot_settings (settings, settings_json, created_at) 
            VALUES (%s, %s, NOW())
            """
            
            cursor.execute(insert_query, (json_data, json_data))
            conn.commit()
            cursor.close()
            conn.close()
            
            self.logger.info("Varsayılan bot yapılandırması oluşturuldu ve veritabanına kaydedildi.")
        except Exception as e:
            self.logger.error(f"Varsayılan yapılandırma kaydedilirken hata: {str(e)}")
            
            # JSON dosyasına yedekle
            try:
                config_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), "config", "bot_config.json")
                with open(config_file, 'w') as f:
                    json.dump(default_config, f, indent=4)
            except Exception as file_error:
                self.logger.error(f"Yapılandırma dosyasına kaydedilirken hata: {str(file_error)}")
        
        return default_config
    
    def save_config(self, config=None):
        """
        Yapılandırmayı MySQL'deki bot_settings tablosuna ve JSON dosyasına kaydet
        """
        if config:
            self.config = config
        
        try:
            # MySQL'e kaydet
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # JSON string'e dönüştür
            json_data = json.dumps(self.config, indent=4)
            
            # Yeni bir kayıt ekle
            insert_query = """
            INSERT INTO bot_settings (settings, settings_json, created_at) 
            VALUES (%s, %s, NOW())
            """
            cursor.execute(insert_query, (json_data, json_data))
            conn.commit()
            cursor.close()
            conn.close()
            
            # JSON dosyasına da yedekle
            config_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), "config", "bot_config.json")
            with open(config_file, 'w') as f:
                json.dump(self.config, f, indent=4)
            
            self.logger.info("Bot yapılandırması başarıyla kaydedildi (veritabanı ve JSON)")
            return True
        
        except Exception as e:
            self.logger.error(f"Bot yapılandırması kaydedilirken hata: {str(e)}")
            
            # Veritabanına kayıt başarısız olursa, JSON dosyasına yedeklemeyi dene
            try:
                config_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), "config", "bot_config.json")
                with open(config_file, 'w') as f:
                    json.dump(self.config, f, indent=4)
                self.logger.info("Bot yapılandırması JSON dosyasına kaydedildi (veritabanı hatası)")
            except Exception as file_error:
                self.logger.error(f"JSON dosyasına kaydedilirken hata: {str(file_error)}")
            
            return False
    
    def update_config(self, new_settings):
        """
        Mevcut yapılandırmayı günceller ve kaydeder
        """
        # Mevcut config ile yeni ayarları birleştir
        if isinstance(new_settings, dict):
            for key, value in new_settings.items():
                self.config[key] = value
            
            # Güncellenmiş yapılandırmayı kaydet
            self.save_config()
            return True
        else:
            self.logger.error("Geçersiz yapılandırma formatı")
            return False
    
    def get_indicator_params(self, indicator_name):
        """
        Belirli bir indikatörün parametrelerini döndürür
        """
        try:
            if 'indicators' in self.config and indicator_name in self.config['indicators']:
                return self.config['indicators'][indicator_name]
            else:
                # Varsayılan parametreleri döndür
                default_params = {
                    'bollinger_bands': {'enabled': True, 'window': 20, 'num_std': 2.0},
                    'rsi': {'enabled': True, 'window': 14},
                    'macd': {'enabled': True, 'fast_period': 12, 'slow_period': 26, 'signal_period': 9},
                    'moving_average': {'enabled': True, 'short_window': 9, 'long_window': 21},
                    'supertrend': {'enabled': True, 'period': 10, 'multiplier': 3.0},
                    'vwap': {'enabled': False, 'period': None},
                    'pivot_points': {'enabled': False, 'method': 'standard'},
                    'stochastic': {'enabled': False, 'k_period': 14, 'd_period': 3, 'slowing': 3}
                }
                
                if indicator_name in default_params:
                    return default_params[indicator_name]
                
                return {'enabled': False}
                
        except Exception as e:
            self.logger.error(f"{indicator_name} indikatör parametreleri alınırken hata: {str(e)}")
            return {'enabled': False}
    
    def load_notification_settings(self):
        """
        Bildirim ayarlarını config'den yükler ve bot'a aktarır
        """
        try:
            if 'notifications' in self.config:
                notifications = self.config['notifications']
                
                # Telegram bildirimleri
                if 'telegram' in notifications:
                    telegram_settings = notifications['telegram']
                    self.config['telegram_enabled'] = telegram_settings.get('enabled', False)
                    self.config['telegram_bot_token'] = telegram_settings.get('bot_token', '')
                    self.config['telegram_chat_id'] = telegram_settings.get('chat_id', '')
                    self.config['telegram_message_format'] = telegram_settings.get('message_format', 'simple')
                    self.config['telegram_rate_limit'] = telegram_settings.get('rate_limit', 1)
                    
                    # Bildirim türleri
                    if 'types' in telegram_settings:
                        types = telegram_settings['types']
                        self.config['telegram_trades'] = types.get('trades', False)
                        self.config['telegram_errors'] = types.get('errors', True)
                        self.config['telegram_profits'] = types.get('profits', True)
                        self.config['telegram_status'] = types.get('status', True)
                        self.config['telegram_discovered_coins'] = types.get('discovered_coins', False)
                
                # E-posta bildirimleri
                if 'email' in notifications:
                    email_settings = notifications['email']
                    self.config['email_enabled'] = email_settings.get('enabled', False)
                    self.config['email_smtp_host'] = email_settings.get('smtp_host', 'smtp.gmail.com')
                    self.config['email_smtp_port'] = email_settings.get('smtp_port', 587)
                    self.config['email_username'] = email_settings.get('username', '')
                    self.config['email_password'] = email_settings.get('password', '')
                    self.config['email_recipients'] = email_settings.get('recipients', [])
                    
                    # E-posta bildirim türleri
                    if 'types' in email_settings:
                        types = email_settings['types']
                        self.config['email_critical'] = types.get('critical', True)
                        self.config['email_daily_reports'] = types.get('daily_reports', False)
                        self.config['email_weekly_reports'] = types.get('weekly_reports', False)
                        self.config['email_system_status'] = types.get('system_status', True)
                
                self.logger.info("Bildirim ayarları başarıyla yüklendi")
            
        except Exception as e:
            self.logger.error(f"Bildirim ayarları yüklenirken hata: {str(e)}")
    
    def load_logging_settings(self):
        """
        Günlükleme ayarlarını config'den yükler ve bot'a aktarır
        """
        try:
            if 'logging' in self.config:
                logging_settings = self.config['logging']
                
                self.config['log_level'] = logging_settings.get('level', 'INFO')
                self.config['log_max_file_size'] = logging_settings.get('max_file_size', 10)
                self.config['log_retention_days'] = logging_settings.get('retention_days', 30)
                self.config['log_format'] = logging_settings.get('format', 'simple')
                self.config['log_backup_count'] = logging_settings.get('backup_count', 5)
                self.config['log_rotation'] = logging_settings.get('rotation', True)
                self.config['log_compression'] = logging_settings.get('compression', False)
                
                # Log kategorileri
                if 'categories' in logging_settings:
                    categories = logging_settings['categories']
                    self.config['log_trades'] = categories.get('trades', True)
                    self.config['log_indicators'] = categories.get('indicators', False)
                    self.config['log_api'] = categories.get('api', False)
                    self.config['log_errors'] = categories.get('errors', True)
                
                self.logger.info(f"Günlükleme ayarları yüklendi - Seviye: {self.config['log_level']}")
            
        except Exception as e:
            self.logger.error(f"Günlükleme ayarları yüklenirken hata: {str(e)}")
    
    def load_monitoring_settings(self):
        """
        Performans izleme ayarlarını config'den yükler ve bot'a aktarır
        """
        try:
            if 'monitoring' in self.config:
                monitoring_settings = self.config['monitoring']
                
                self.config['performance_interval'] = monitoring_settings.get('performance_interval', 60)
                self.config['memory_threshold'] = monitoring_settings.get('memory_threshold', 80)
                self.config['cpu_monitoring'] = monitoring_settings.get('cpu_monitoring', True)
                self.config['disk_monitoring'] = monitoring_settings.get('disk_monitoring', True)
                
                self.logger.info(f"Performans izleme ayarları yüklendi - Aralık: {self.config['performance_interval']}s")
            
        except Exception as e:
            self.logger.error(f"Performans izleme ayarları yüklenirken hata: {str(e)}")
    
    def get_notification_config(self, notification_type='telegram'):
        """
        Belirli bir bildirim türü için yapılandırmayı döndürür
        
        :param notification_type: 'telegram' veya 'email'
        :return: Bildirim yapılandırması
        """
        try:
            if notification_type == 'telegram':
                return {
                    'enabled': self.config.get('telegram_enabled', False),
                    'bot_token': self.config.get('telegram_bot_token', ''),
                    'chat_id': self.config.get('telegram_chat_id', ''),
                    'message_format': self.config.get('telegram_message_format', 'simple'),
                    'rate_limit': self.config.get('telegram_rate_limit', 1),
                    'types': {
                        'trades': self.config.get('telegram_trades', False),
                        'errors': self.config.get('telegram_errors', True),
                        'profits': self.config.get('telegram_profits', True),
                        'status': self.config.get('telegram_status', True),
                        'discovered_coins': self.config.get('telegram_discovered_coins', False)
                    }
                }
            elif notification_type == 'email':
                return {
                    'enabled': self.config.get('email_enabled', False),
                    'smtp_host': self.config.get('email_smtp_host', 'smtp.gmail.com'),
                    'smtp_port': self.config.get('email_smtp_port', 587),
                    'username': self.config.get('email_username', ''),
                    'password': self.config.get('email_password', ''),
                    'recipients': self.config.get('email_recipients', []),
                    'types': {
                        'critical': self.config.get('email_critical', True),
                        'daily_reports': self.config.get('email_daily_reports', False),
                        'weekly_reports': self.config.get('email_weekly_reports', False),
                        'system_status': self.config.get('email_system_status', True)
                    }
                }
            
        except Exception as e:
            self.logger.error(f"{notification_type} bildirim ayarları alınırken hata: {str(e)}")
            return {'enabled': False}
    
    def get_logging_config(self):
        """
        Günlükleme yapılandırmasını döndürür
        """
        try:
            return {
                'level': self.config.get('log_level', 'INFO'),
                'max_file_size': self.config.get('log_max_file_size', 10),
                'retention_days': self.config.get('log_retention_days', 30),
                'format': self.config.get('log_format', 'simple'),
                'backup_count': self.config.get('log_backup_count', 5),
                'rotation': self.config.get('log_rotation', True),
                'compression': self.config.get('log_compression', False),
                'categories': {
                    'trades': self.config.get('log_trades', True),
                    'indicators': self.config.get('log_indicators', False),
                    'api': self.config.get('log_api', False),
                    'errors': self.config.get('log_errors', True)
                }
            }
        except Exception as e:
            self.logger.error(f"Günlükleme ayarları alınırken hata: {str(e)}")
            return {'level': 'INFO', 'max_file_size': 10, 'retention_days': 30}
    
    def get_monitoring_config(self):
        """
        Performans izleme yapılandırmasını döndürür
        """
        try:
            return {
                'performance_interval': self.config.get('performance_interval', 60),
                'memory_threshold': self.config.get('memory_threshold', 80),
                'cpu_monitoring': self.config.get('cpu_monitoring', True),
                'disk_monitoring': self.config.get('disk_monitoring', True)
            }
        except Exception as e:
            self.logger.error(f"Performans izleme ayarları alınırken hata: {str(e)}")
            return {'performance_interval': 60, 'memory_threshold': 80}