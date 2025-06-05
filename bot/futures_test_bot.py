import ccxt
import time
from datetime import datetime
import logging
import json
import mysql.connector

# Loglama ayarları
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('futures_test_bot.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class FuturesTestBot:
    def __init__(self):
        self.config = {
            'api_key': '',  # Binance API key
            'api_secret': '',  # Binance API secret
            'symbol': '1000CHEEMS/USDT',  # İşlem yapılacak sembol
            'leverage': 5,  # Kaldıraç oranı
            'quantity': 5.0,  # Minimum işlem tutarı (USDT) - Binance minimum 5 USDT
            'position_side': 'BOTH',  # LONG veya SHORT pozisyonlar için
            'stop_loss_percent': 1,  # Stop loss yüzdesi
            'take_profit_percent': 2,  # Take profit yüzdesi
            'test_mode': True  # Test modu (True/False)
        }
        self.exchange = None
        self.load_api_keys_from_db()
        self.setup_exchange()

    def load_api_keys_from_db(self):
        """MySQL'den API anahtarlarını yükle"""
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
            
            # Bot ayarlarından API bilgilerini al
            cursor.execute("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1")
            settings = cursor.fetchone()
            
            if not settings:
                logger.error("API bilgileri veritabanından alınamadı!")
                return

            # API anahtarlarını settings_json'dan al
            if settings.get('settings_json'):
                try:
                    settings_data = json.loads(settings['settings_json'])
                    
                    # api_keys altındaki Binance API bilgilerini kontrol et
                    if 'api_keys' in settings_data:
                        api_keys = settings_data['api_keys']
                        self.config['api_key'] = api_keys.get('binance_api_key', '').strip()
                        self.config['api_secret'] = api_keys.get('binance_api_secret', '').strip()
                        
                        if self.config['api_key'] and self.config['api_secret']:
                            logger.info("API bilgileri veritabanından başarıyla yüklendi")
                            return
                    
                    # Doğrudan kök seviyede API bilgilerini kontrol et
                    self.config['api_key'] = settings_data.get('api_key', '').strip()
                    self.config['api_secret'] = settings_data.get('api_secret', '').strip()
                    
                    if self.config['api_key'] and self.config['api_secret']:
                        logger.info("API bilgileri veritabanından başarıyla yüklendi")
                        return
                        
                except json.JSONDecodeError:
                    logger.error("settings_json parse edilemedi!")
            
            # API bilgileri bulunamadıysa hata mesajı
            if not self.config['api_key'] or not self.config['api_secret']:
                logger.error("API bilgileri bulunamadı!")
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logger.error(f"API bilgileri veritabanından yüklenirken hata: {str(e)}")

    def setup_exchange(self):
        """Binance Futures bağlantısını kurma"""
        try:
            if not self.config['api_key'] or not self.config['api_secret']:
                raise ValueError("API bilgileri eksik!")

            self.exchange = ccxt.binance({
                'apiKey': self.config['api_key'],
                'secret': self.config['api_secret'],
                'enableRateLimit': True,
                'options': {
                    'defaultType': 'future',
                    'adjustForTimeDifference': True,
                }
            })
            
            # Margin mode'u isolated olarak ayarla - sadece desteklenen semboller için
            try:
                symbol_clean = self.config['symbol'].replace('/', '')
                self.exchange.fapiPrivate_post_margintype({
                    'symbol': symbol_clean,
                    'marginType': 'ISOLATED'
                })
                logger.info(f"Margin mode ISOLATED olarak ayarlandı - {self.config['symbol']}")
            except Exception as e:
                if 'supports linear and inverse contracts only' in str(e):
                    logger.info(f"Margin mode ayarı bu sembol için desteklenmiyor: {self.config['symbol']}")
                elif 'Unknown parameter' not in str(e):
                    logger.error(f"Margin mode ayarlanırken hata: {str(e)}")
            
            logger.info("Binance Futures bağlantısı kuruldu")
        except Exception as e:
            logger.error(f"Exchange bağlantısı kurulamadı: {str(e)}")
            raise

    def set_leverage(self, symbol, leverage):
        """Kaldıraç oranını ayarlama"""
        try:
            self.exchange.set_leverage(leverage, symbol)
            logger.info(f"Kaldıraç {leverage}x olarak ayarlandı - {symbol}")
            return True
        except Exception as e:
            logger.error(f"Kaldıraç ayarlanırken hata: {str(e)}")
            return False

    def get_position(self, symbol):
        """Mevcut pozisyonu kontrol etme"""
        try:
            positions = self.exchange.fetch_positions([symbol])
            for position in positions:
                if position['symbol'] == symbol and float(position['contracts']) > 0:
                    return position
            return None
        except Exception as e:
            logger.error(f"Pozisyon bilgisi alınamadı: {str(e)}")
            return None

    def create_market_order(self, symbol, side, amount):
        """Market emri oluşturma"""
        try:
            order = self.exchange.create_order(
                symbol=symbol,
                type='market',
                side=side,
                amount=amount
            )
            logger.info(f"{side} emri oluşturuldu: {order}")
            return order
        except Exception as e:
            logger.error(f"Market emri oluşturulurken hata: {str(e)}")
            return None

    def calculate_position_size(self, symbol):
        """İşlem büyüklüğünü hesaplama"""
        try:
            ticker = self.exchange.fetch_ticker(symbol)
            current_price = ticker['last']
            
            # Minimum notional value 10 USDT (daha güvenli margin)
            min_notional = 10.0
            
            # Kaldıraç dikkate alınarak pozisyon büyüklüğünü hesapla
            # Notional value = position_size * current_price >= 10 USDT
            position_size = (min_notional * 1.2) / current_price  # %20 margin ekle
            
            # 6 decimal'e yuvarla
            position_size = round(position_size, 6)
            
            # Notional value'yu kontrol et
            notional_value = position_size * current_price
            
            logger.info(f"Hesaplanan pozisyon büyüklüğü: {position_size} {symbol.split('/')[0]}")
            logger.info(f"Notional Value: {notional_value} USDT")
            logger.info(f"Current Price: {current_price} USDT")
            
            return position_size
            
        except Exception as e:
            logger.error(f"Pozisyon büyüklüğü hesaplanamadı: {str(e)}")
            return None

    def open_long_position(self, symbol):
        """LONG pozisyon açma"""
        try:
            # Kaldıraç ayarla
            self.set_leverage(symbol, self.config['leverage'])
            
            # Pozisyon büyüklüğünü hesapla
            amount = self.calculate_position_size(symbol)
            if not amount:
                return False
            
            # Market emri oluştur
            order = self.create_market_order(symbol, 'buy', amount)
            if order:
                logger.info(f"LONG pozisyon açıldı - Miktar: {amount} {symbol}")
                return True
            return False
        except Exception as e:
            logger.error(f"LONG pozisyon açılırken hata: {str(e)}")
            return False

    def open_short_position(self, symbol):
        """SHORT pozisyon açma"""
        try:
            # Kaldıraç ayarla
            self.set_leverage(symbol, self.config['leverage'])
            
            # Pozisyon büyüklüğünü hesapla
            amount = self.calculate_position_size(symbol)
            if not amount:
                return False
            
            # Market emri oluştur
            order = self.create_market_order(symbol, 'sell', amount)
            if order:
                logger.info(f"SHORT pozisyon açıldı - Miktar: {amount} {symbol}")
                return True
            return False
        except Exception as e:
            logger.error(f"SHORT pozisyon açılırken hata: {str(e)}")
            return False

    def close_position(self, symbol):
        """Mevcut pozisyonu kapatma"""
        try:
            position = self.get_position(symbol)
            if not position:
                logger.info(f"{symbol} için açık pozisyon bulunamadı")
                return False
            
            # Pozisyon yönüne göre tersine işlem yapma
            side = 'sell' if position['side'] == 'long' else 'buy'
            amount = abs(float(position['contracts']))
            
            order = self.create_market_order(symbol, side, amount)
            if order:
                logger.info(f"Pozisyon kapatıldı - {symbol}")
                return True
            return False
        except Exception as e:
            logger.error(f"Pozisyon kapatılırken hata: {str(e)}")
            return False

    def run(self):
        """Test bot çalıştırma"""
        logger.info("Test bot başlatılıyor...")
        
        # API bilgilerini kontrol et
        logger.info(f"API Key uzunluğu: {len(self.config['api_key'])}")
        logger.info(f"API Secret uzunluğu: {len(self.config['api_secret'])}")
        
        symbol = self.config['symbol']

        try:
            # Test için önce market durumunu kontrol et
            try:
                ticker = self.exchange.fetch_ticker(symbol)
                logger.info(f"Market durumu kontrol edildi. {symbol} fiyatı: {ticker['last']}")
            except Exception as e:
                logger.error(f"Market bilgisi alınamadı: {str(e)}")
                return

            # LONG pozisyon testi
            logger.info("LONG pozisyon testi başlıyor...")
            if self.open_long_position(symbol):
                time.sleep(5)  # 5 saniye bekle
                self.close_position(symbol)
            
            time.sleep(2)  # İşlemler arası bekle
            
            # SHORT pozisyon testi
            logger.info("SHORT pozisyon testi başlıyor...")
            if self.open_short_position(symbol):
                time.sleep(5)  # 5 saniye bekle
                self.close_position(symbol)

        except Exception as e:
            logger.error(f"Test sırasında hata: {str(e)}")
        finally:
            logger.info("Test tamamlandı")

if __name__ == "__main__":
    # Bot oluştur ve çalıştır
    bot = FuturesTestBot()
    bot.run()