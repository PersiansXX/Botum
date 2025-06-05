import time
import json
import hmac
import hashlib
import requests
import os
from urllib.parse import urlencode

# İlk olarak mysql-connector modülünü yüklemeyi deneyeceğiz
try:
    import mysql.connector
    MYSQL_AVAILABLE = True
    print("MySQL-connector modülü başarıyla yüklendi")
except ImportError:
    MYSQL_AVAILABLE = False
    print("MySQL-connector modülü yüklü değil. Veritabanından API anahtarları çekilemeyecek.")
    print("Modülü yüklemek için: pip install mysql-connector-python")

class BinanceBalanceFetcher:
    def __init__(self, api_key, api_secret):
        self.api_key = api_key
        self.api_secret = api_secret
        self.base_url = "https://api.binance.com"
        self.fapi_url = "https://fapi.binance.com"  # Futures API
        self.sapi_url = "https://api.binance.com/sapi"  # SAPI for margin

    def _generate_signature(self, params):
        query_string = urlencode(params)
        signature = hmac.new(
            self.api_secret.encode('utf-8'),
            query_string.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()
        return signature

    def _make_request(self, endpoint, method='GET', params=None, api_type="spot"):
        if params is None:
            params = {}
        
        # Add timestamp to params
        timestamp = int(time.time() * 1000)
        params['timestamp'] = timestamp
        
        # Generate signature
        signature = self._generate_signature(params)
        params['signature'] = signature
        
        # Select base URL based on API type
        if api_type == "futures":
            base_url = self.fapi_url
        elif api_type == "margin" or api_type == "wallet":
            base_url = self.sapi_url
        else:
            base_url = self.base_url
        
        # Construct the full URL
        url = f"{base_url}{endpoint}"
        
        # Set up headers
        headers = {
            'X-MBX-APIKEY': self.api_key
        }
        
        # Make the request
        if method == 'GET':
            response = requests.get(url, headers=headers, params=params)
        elif method == 'POST':
            response = requests.post(url, headers=headers, params=params)
        else:
            raise ValueError(f"Unsupported HTTP method: {method}")
        
        # Handle the response
        if response.status_code == 200:
            return response.json()
        else:
            print(f"Error: {response.status_code}, {response.text}")
            return None

    def get_spot_balance(self):
        endpoint = "/api/v3/account"
        response = self._make_request(endpoint, params={})
        
        if response and 'balances' in response:
            # Filter out zero balances
            balances = [asset for asset in response['balances'] 
                      if float(asset['free']) > 0 or float(asset['locked']) > 0]
            
            # Sort by non-zero values first
            balances.sort(key=lambda x: float(x['free']) + float(x['locked']), reverse=True)
            
            return balances
        return []

    def get_margin_balance(self):
        endpoint = "/v1/margin/account"
        response = self._make_request(endpoint, params={}, api_type="margin")
        
        margin_balances = []
        if response and 'userAssets' in response:
            # Filter out zero balances
            balances = [asset for asset in response['userAssets'] 
                      if float(asset['free']) > 0 or float(asset['locked']) > 0]
            
            # Sort by non-zero values first
            balances.sort(key=lambda x: float(x['free']) + float(x['locked']), reverse=True)
            
            margin_balances = balances
        
        return margin_balances

    def get_isolated_margin_balance(self):
        endpoint = "/v1/margin/isolated/account"
        response = self._make_request(endpoint, params={}, api_type="margin")
        
        isolated_balances = []
        if response and 'assets' in response:
            for asset in response['assets']:
                for base_asset in asset['baseAssets']:
                    if float(base_asset['free']) > 0 or float(base_asset['locked']) > 0:
                        isolated_balances.append({
                            'asset': base_asset['asset'],
                            'free': base_asset['free'],
                            'locked': base_asset['locked'],
                            'pair': asset['symbol']
                        })
                        
                for quote_asset in asset['quoteAssets']:
                    if float(quote_asset['free']) > 0 or float(quote_asset['locked']) > 0:
                        isolated_balances.append({
                            'asset': quote_asset['asset'],
                            'free': quote_asset['free'],
                            'locked': quote_asset['locked'],
                            'pair': asset['symbol']
                        })
        
        return isolated_balances

    def get_futures_balance(self):
        endpoint = "/fapi/v2/balance"
        response = self._make_request(endpoint, params={}, api_type="futures")
        
        if response:
            # Filter out zero balances
            balances = [asset for asset in response 
                      if float(asset['balance']) > 0]
            
            # Sort by balance
            balances.sort(key=lambda x: float(x['balance']), reverse=True)
            
            return balances
        return []

    def get_all_coin_balances(self):
        endpoint = "/v1/capital/config/getall"
        response = self._make_request(endpoint, params={'includeEtf': 'true'}, api_type="wallet")
        
        if response:
            # Filter out zero balances
            balances = [coin for coin in response 
                      if float(coin.get('free', 0)) > 0 or float(coin.get('freeze', 0)) > 0]
            
            # Sort by non-zero values first
            balances.sort(key=lambda x: float(x.get('free', 0)) + float(x.get('freeze', 0)), reverse=True)
            
            return balances
        return []

    def get_wallet_balance(self):
        endpoint = "/v1/accountSnapshot"
        params = {
            "type": "SPOT"
        }
        response = self._make_request(endpoint, params=params, api_type="wallet")
        
        if response and response.get('code') == 200:
            data = response.get('snapshotVos', [])
            if data:
                latest = max(data, key=lambda x: x.get('updateTime', 0))
                return latest.get('data', {}).get('totalAssetOfBtc', 0)
        return 0

    def get_all_balances(self):
        results = {
            "spot": self.get_spot_balance(),
            "margin": self.get_margin_balance(),
            "isolated_margin": self.get_isolated_margin_balance(),
            "futures": self.get_futures_balance(),
            "wallet_btc_value": self.get_wallet_balance()
        }
        
        return results

    def print_balances(self):
        try:
            balances = self.get_all_balances()
            
            print("\n===== BINANCE BALANCES =====")
            
            # Spot balances
            print("\n--- SPOT BALANCES ---")
            for asset in balances['spot']:
                total = float(asset['free']) + float(asset['locked'])
                if total > 0:
                    print(f"{asset['asset']}: Free: {asset['free']}, Locked: {asset['locked']}, Total: {total}")
            
            # Cross margin balances
            if balances['margin']:
                print("\n--- CROSS MARGIN BALANCES ---")
                for asset in balances['margin']:
                    total = float(asset['free']) + float(asset['locked'])
                    if total > 0:
                        print(f"{asset['asset']}: Free: {asset['free']}, Locked: {asset['locked']}, Total: {total}")
            
            # Isolated margin balances
            if balances['isolated_margin']:
                print("\n--- ISOLATED MARGIN BALANCES ---")
                for asset in balances['isolated_margin']:
                    total = float(asset['free']) + float(asset['locked'])
                    print(f"{asset['asset']} ({asset['pair']}): Free: {asset['free']}, Locked: {asset['locked']}, Total: {total}")
            
            # Futures balances
            if balances['futures']:
                print("\n--- FUTURES BALANCES ---")
                for asset in balances['futures']:
                    print(f"{asset['asset']}: Balance: {asset['balance']}, Available: {asset['availableBalance']}")
            
            # Wallet total
            print(f"\nTotal Wallet Value (BTC): {balances['wallet_btc_value']}")
            
            # Save to JSON
            with open('binance_balances.json', 'w') as f:
                json.dump(balances, f, indent=2)
                print(f"\nBalances saved to binance_balances.json")
                
            # Save to PHP-accessible file with detailed information
            spot_balances = []
            for asset in balances.get('spot', []):
                total = float(asset['free']) + float(asset['locked'])
                if total > 0:
                    # API call to get current price in USDT
                    try:
                        if asset['asset'] != 'USDT':
                            # Try to get price from Binance
                            price_url = f"https://api.binance.com/api/v3/ticker/price?symbol={asset['asset']}USDT"
                            price_response = requests.get(price_url)
                            if price_response.status_code == 200:
                                price_data = price_response.json()
                                price = float(price_data['price'])
                            else:
                                # Fallback to BTC pair and convert
                                try:
                                    price_url = f"https://api.binance.com/api/v3/ticker/price?symbol={asset['asset']}BTC"
                                    price_response = requests.get(price_url)
                                    if price_response.status_code == 200:
                                        btc_price = float(price_response.json()['price'])
                                        
                                        # Get BTC/USDT price
                                        btc_usdt_url = "https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT"
                                        btc_usdt_response = requests.get(btc_usdt_url)
                                        if btc_usdt_response.status_code == 200:
                                            btc_usdt_price = float(btc_usdt_response.json()['price'])
                                            price = btc_price * btc_usdt_price
                                        else:
                                            price = 0
                                    else:
                                        price = 0
                                except Exception as e:
                                    print(f"BTC çevirme hatası: {e}")
                                    price = 0
                        else:
                            price = 1  # USDT is 1 USD
                            
                        value_usdt = total * price
                        
                        spot_balances.append({
                            'asset': asset['asset'],
                            'free': asset['free'],
                            'locked': asset['locked'],
                            'total': total,
                            'price_usdt': price,
                            'value_usdt': value_usdt
                        })
                    except Exception as e:
                        print(f"Error getting price for {asset['asset']}: {e}")
                        spot_balances.append({
                            'asset': asset['asset'],
                            'free': asset['free'],
                            'locked': asset['locked'],
                            'total': total,
                            'price_usdt': 0,
                            'value_usdt': 0
                        })
            
            # Spot balances'ı USDT değerlerine göre sırala (büyükten küçüğe)
            spot_balances.sort(key=lambda x: x['value_usdt'], reverse=True)
            
            # Calculate total spot value in USDT
            total_spot_value = sum(asset['value_usdt'] for asset in spot_balances)
            
            # web/api klasörünün varlığını kontrol et, yoksa oluştur
            os.makedirs('web/api', exist_ok=True)
                
            with open('web/api/binance_total_balances.json', 'w') as f:
                total_balances = {
                    "total_spot": total_spot_value,
                    "total_margin": sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('margin', [])]),
                    "total_isolated": sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('isolated_margin', [])]),
                    "total_futures": sum([float(asset['balance']) for asset in balances.get('futures', [])]),
                    "wallet_btc_value": balances.get('wallet_btc_value', 0),
                    "timestamp": int(time.time()),
                    "last_update": time.strftime("%Y-%m-%d %H:%M:%S"),
                    "balances": spot_balances
                }
                json.dump(total_balances, f, indent=2)
                print(f"Total balances saved to web/api/binance_total_balances.json")
                
            return balances
            
        except Exception as e:
            print(f"Error fetching balances: {e}")
            return None


# MySQL'den API anahtarlarını çekmek için fonksiyon
def get_api_keys_from_database():
    if not MYSQL_AVAILABLE:
        print("MySQL bağlantısı için modül yüklü değil. API anahtarları veritabanından çekilemeyecek.")
        return None, None
    
    try:
        # DB bağlantı bilgileri - bu bilgileri kendi veritabanı yapılandırmanıza göre ayarlayın
        db_config = {
            'host': 'localhost',  # Veritabanı sunucusunun adresi
            'user': 'root',       # Veritabanı kullanıcı adı
            'password': 'Efsane44.',       # Veritabanı şifresi (varsayılan olarak boş)
            'database': 'trading_bot_db'  # Veritabanı adı
        }
        
        # db_config.json dosyası varsa, bağlantı bilgilerini oradan al
        config_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'config', 'db_config.json')
        if os.path.exists(config_file):
            try:
                with open(config_file, 'r') as f:
                    db_config = json.load(f)
                print("Veritabanı yapılandırması yüklendi: " + config_file)
            except Exception as e:
                print(f"Veritabanı yapılandırması yüklenemedi: {e}")
        
        print(f"Veritabanına bağlanılıyor: {db_config['host']}/{db_config['database']} ({db_config['user']})")
        
        # MySQL bağlantısı oluştur
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        
        # İlk olarak api_keys tablosundan kontrol et
        print("api_keys tablosundan API anahtarları kontrol ediliyor...")
        cursor.execute("SELECT * FROM api_keys WHERE is_active = 1 ORDER BY id DESC LIMIT 1")
        result = cursor.fetchone()
        
        if result:
            api_key = result['api_key']
            api_secret = result['api_secret']
            print(f"API anahtarları api_keys tablosundan başarıyla alındı. API Key: {api_key[:5]}...")
            
            cursor.close()
            conn.close()
            
            return api_key, api_secret
        
        # api_keys tablosunda bulunamazsa, bot_settings tablosunu kontrol et
        print("bot_settings tablosundan API anahtarları kontrol ediliyor...")
        cursor.execute("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1")
        result = cursor.fetchone()
        
        if result and 'settings' in result:
            # JSON formatındaki settings alanını parse et
            try:
                settings = json.loads(result['settings'])
                print("bot_settings tablosundan settings verisi alındı.")
                
                # Farklı olası yapıları kontrol et
                if 'api_keys' in settings and 'binance_api_key' in settings['api_keys'] and 'binance_api_secret' in settings['api_keys']:
                    api_key = settings['api_keys']['binance_api_key']
                    api_secret = settings['api_keys']['binance_api_secret']
                    print(f"API anahtarları settings['api_keys'] yolundan alındı. API Key: {api_key[:5]}...")
                elif 'api' in settings and 'binance' in settings['api']:
                    api_key = settings['api']['binance']['api_key']
                    api_secret = settings['api']['binance'].get('api_secret', settings['api']['binance'].get('secret', ''))
                    print(f"API anahtarları settings['api']['binance'] yolundan alındı. API Key: {api_key[:5]}...")
                else:
                    print("API anahtarları settings JSON yapısında bulunamadı.")
                    cursor.close()
                    conn.close()
                    return None, None
                
                cursor.close()
                conn.close()
                
                return api_key, api_secret
            except json.JSONDecodeError as e:
                print(f"bot_settings tablosundaki settings alanı geçerli bir JSON değil: {e}")
                cursor.close()
                conn.close()
                return None, None
        
        # Hiçbir yerde bulunamazsa
        print("API anahtarları veritabanında bulunamadı.")
        cursor.close()
        conn.close()
        return None, None
        
    except mysql.connector.Error as err:
        print(f"MySQL Bağlantı Hatası: {err}")
        return None, None
    except Exception as e:
        print(f"Veritabanından API anahtarları alınırken genel hata: {e}")
        return None, None


# Ana program
if __name__ == "__main__":
    try:
        print("Binance bakiye bilgilerini getirme işlemi başlatılıyor...")
        print(f"Çalışma dizini: {os.getcwd()}")
        
        # API anahtarlarını öncelikle veritabanından almayı dene
        print("API anahtarları veritabanından alınmaya çalışılıyor...")
        api_key, api_secret = get_api_keys_from_database()
        
        # Veritabanından alınamadıysa dosyadan okumayı dene
        if not api_key or not api_secret:
            print("Veritabanından API anahtarları alınamadı. Dosyadan okumaya çalışılıyor...")
            try:
                # config klasörünün varlığını kontrol et
                if not os.path.exists('config'):
                    os.makedirs('config', exist_ok=True)
                    print("config klasörü oluşturuldu.")
                
                api_keys_file = os.path.join('config', 'api_keys.json')
                if os.path.exists(api_keys_file):
                    with open(api_keys_file, 'r') as f:
                        api_keys = json.load(f)
                    
                    if 'binance' in api_keys and 'api_key' in api_keys['binance'] and 'api_secret' in api_keys['binance']:
                        api_key = api_keys['binance']['api_key']
                        api_secret = api_keys['binance']['api_secret']
                        print(f"API anahtarları config/api_keys.json dosyasından başarıyla yüklendi. API Key: {api_key[:5]}...")
                    else:
                        print("api_keys.json dosyasında Binance API anahtarları bulunamadı.")
                        api_key = None
                        api_secret = None
                else:
                    print(f"API anahtarları dosyası bulunamadı: {api_keys_file}")
                    api_key = None
                    api_secret = None
            except (FileNotFoundError, KeyError, json.JSONDecodeError) as e:
                print(f"API anahtarları dosyadan yüklenemedi: {e}.")
                api_key = None
                api_secret = None
                
            # Dosyadan da alınamadıysa manuel giriş iste
            if not api_key or not api_secret:
                print("\n" + "="*50)
                print("API ANAHTARLARINI MANUEL GİRMENİZ GEREKİYOR")
                print("="*50)
                api_key = input("Binance API anahtarınızı girin: ")
                api_secret = input("Binance API gizli anahtarınızı girin: ")
                
                # API anahtarlarını config/api_keys.json dosyasına kaydet
                try:
                    os.makedirs('config', exist_ok=True)
                    with open('config/api_keys.json', 'w') as f:
                        json.dump({
                            'binance': {
                                'api_key': api_key,
                                'api_secret': api_secret
                            }
                        }, f, indent=2)
                    print("API anahtarları config/api_keys.json dosyasına kaydedildi.")
                except Exception as e:
                    print(f"API anahtarları dosyaya kaydedilemedi: {e}")
        
        # API anahtarlarının boş olmadığından emin ol
        if not api_key or not api_secret:
            raise Exception("Geçerli API anahtarları bulunamadı. Program sonlandırılıyor.")
        
        # Bakiye çekicisini oluştur ve çalıştır
        fetcher = BinanceBalanceFetcher(api_key, api_secret)
        print("Binance bakiyeleri getiriliyor...")
        balances = fetcher.print_balances()
        
        if balances:
            print("İşlem başarılı!")
            
            # PHP web uygulaması için özet bakiye bilgisini oluştur
            total_spot = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('spot', [])])
            total_margin = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('margin', [])])
            total_isolated = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('isolated_margin', [])])
            total_futures = sum([float(asset['balance']) for asset in balances.get('futures', [])])
            wallet_btc = balances.get('wallet_btc_value', 0)
            
            print("\n=== ÖZET BAKIYE BİLGİSİ ===")
            print(f"Toplam Spot: {total_spot:.8f} USDT")
            print(f"Toplam Cross Margin: {total_margin:.8f} USDT")
            print(f"Toplam Isolated Margin: {total_isolated:.8f} USDT")
            print(f"Toplam Futures: {total_futures:.8f} USDT")
            print(f"Toplam Portföy Değeri (BTC): {wallet_btc}")
            print(f"Toplam Portföy Değeri (USD): {total_spot + total_margin + total_isolated + total_futures:.8f} USDT")
            print("\nBu bilgiler web/api/binance_total_balances.json dosyasına kaydedildi.")
        else:
            print("Bakiyeler getirilemedi!")
            
    except Exception as e:
        print(f"Hata oluştu: {e}")