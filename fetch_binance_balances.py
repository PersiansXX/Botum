import time
import json
import hmac
import hashlib
import requests
from urllib.parse import urlencode
import os
import traceback

class BinanceBalanceFetcher:
    def __init__(self, api_key, api_secret, debug=False):
        self.api_key = api_key
        self.api_secret = api_secret
        self.debug = debug
        self.base_url = "https://api.binance.com"
        self.fapi_url = "https://fapi.binance.com"  # Futures API
        self.sapi_url = "https://api.binance.com/sapi"  # SAPI for margin
        self.last_error = None

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
        
        # Debug log if enabled
        if self.debug:
            print(f"API istek: {url}")
            print(f"Metod: {method}")
            print(f"Headers: {headers}")
            print(f"Params: {params}")
        
        try:
            # Make the request
            if method == 'GET':
                response = requests.get(url, headers=headers, params=params)
            elif method == 'POST':
                response = requests.post(url, headers=headers, params=params)
            else:
                raise ValueError(f"Desteklenmeyen HTTP metodu: {method}")
            
            # Debug log response if enabled
            if self.debug:
                print(f"API yanıt kodu: {response.status_code}")
                print(f"API yanıt: {response.text[:200]}...")
            
            # Handle the response
            if response.status_code == 200:
                return response.json()
            else:
                error_text = f"Hata: {response.status_code}, {response.text}"
                self.last_error = error_text
                print(error_text)
                return None
        except Exception as e:
            error_text = f"API isteği sırasında hata: {str(e)}"
            self.last_error = error_text
            print(error_text)
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
    
    def get_btc_price(self):
        """BTC/USDT fiyatını al"""
        try:
            url = "https://api.binance.com/api/v3/ticker/price"
            params = {"symbol": "BTCUSDT"}
            response = requests.get(url, params=params)
            if response.status_code == 200:
                data = response.json()
                btc_price = float(data.get('price', 0))
                if btc_price > 0:
                    return btc_price
                else:
                    print("Geçersiz BTC fiyatı alındı")
            else:
                print(f"BTC fiyat alınamadı: {response.status_code}, {response.text}")
        except Exception as e:
            print(f"BTC fiyat alınırken hata: {str(e)}")
        
        # Varsayılan fiyat (yaklaşık olarak)
        return 68000.0  # Yaklaşık BTC fiyatı

    def get_all_balances(self):
        results = {
            "spot": self.get_spot_balance(),
            "margin": self.get_margin_balance(),
            "isolated_margin": self.get_isolated_margin_balance(),
            "futures": self.get_futures_balance(),
            "wallet_btc_value": self.get_wallet_balance(),
            "last_error": self.last_error
        }
        
        return results

    def print_balances(self):
        try:
            balances = self.get_all_balances()
            
            print("\n===== BINANCE BALANCES =====")
            
            # Güncel BTC fiyatını al
            btc_price = self.get_btc_price()
            print(f"\nGüncel BTC fiyatı: ${btc_price:.2f}")
            
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
            btc_value = float(balances.get('wallet_btc_value', 0))
            print(f"\nTotal Wallet Value (BTC): {btc_value}")
            print(f"Total Wallet Value (USD): ${btc_value * btc_price:.2f}")
            
            # Save to JSON
            with open('binance_balances.json', 'w') as f:
                json.dump(balances, f, indent=2)
                print(f"\nBalances saved to binance_balances.json")
                
            # PHP uygulaması için toplam hesaplama ve kaydetme
            # Veri dizisini oluştur
            total_spot = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('spot', [])])
            total_margin = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('margin', [])])
            total_isolated = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('isolated_margin', [])])
            total_futures = sum([float(asset['balance']) for asset in balances.get('futures', [])])
            total_btc = float(balances.get('wallet_btc_value', 0))
            
            # BTC cinsinden değeri USD'ye çevir
            total_usdt_value = total_btc * btc_price
            
            total_balances = {
                "total_spot": total_spot,
                "total_margin": total_margin,
                "total_isolated": total_isolated,
                "total_futures": total_futures,
                "wallet_btc_value": total_btc,
                "btc_price_usd": btc_price,
                "estimated_total_usd": total_usdt_value,
                "timestamp": int(time.time()),
                "error": balances.get('last_error', None)
            }
            
            # Web klasörünü kontrol et ve oluştur (gerekirse)
            web_api_dir = 'web/api'
            if not os.path.exists(web_api_dir):
                web_api_dir = '../web/api'  # Alternatif yol dene
                if not os.path.exists(web_api_dir):
                    os.makedirs(web_api_dir)  # Klasörü oluştur
                    
            # JSON dosyasını kaydet
            filepath = os.path.join(web_api_dir, 'binance_total_balances.json')
            with open(filepath, 'w') as f:
                json.dump(total_balances, f, indent=2)
                print(f"Total balances saved to {filepath}")
                
            return balances
            
        except Exception as e:
            print(f"Error fetching balances: {e}")
            print(f"Error details: {traceback.format_exc()}")
            return None


# Ana program
if __name__ == "__main__":
    try:
        print("Binance Bakiye Çekme Aracı v1.1")
        print("===============================")
        
        # Debug modunu kontrol et
        debug_mode = input("Debug modunu etkinleştirmek ister misiniz? (E/H): ").lower() == 'e'
        
        # API anahtarlarını dosyadan okumayı dene
        api_key = None
        api_secret = None
        
        config_paths = [
            'config/api_keys.json',
            '../config/api_keys.json',
            'config/bot_config.json',
            '../config/bot_config.json'
        ]
        
        for config_path in config_paths:
            try:
                print(f"{config_path} dosyasından API anahtarları okunuyor...")
                if os.path.exists(config_path):
                    with open(config_path, 'r') as f:
                        config_data = json.load(f)
                        
                    # api_keys.json formatını kontrol et
                    if 'binance' in config_data and 'api_key' in config_data['binance'] and 'api_secret' in config_data['binance']:
                        api_key = config_data['binance']['api_key'].strip()  # Boşlukları temizle
                        api_secret = config_data['binance']['api_secret'].strip()  # Boşlukları temizle
                        print("API anahtarları başarıyla yüklendi!")
                        break
                    # bot_config.json formatını kontrol et
                    elif 'api_key' in config_data and 'api_secret' in config_data:
                        api_key = config_data['api_key'].strip()  # Boşlukları temizle
                        api_secret = config_data['api_secret'].strip()  # Boşlukları temizle
                        print("API anahtarları başarıyla yüklendi!")
                        break
            except Exception as e:
                print(f"{config_path} dosyasından API anahtarları yüklenirken hata: {str(e)}")
        
        # Anahtarlar hala None ise manuel giriş iste
        if api_key is None or api_secret is None:
            print("API anahtarları yüklenemedi. Lütfen manuel giriş yapın.")
            api_key = input("Binance API anahtarınızı girin: ").strip()  # Boşlukları temizle
            api_secret = input("Binance API gizli anahtarınızı girin: ").strip()  # Boşlukları temizle
        else:
            print(f"API anahtarı: {api_key[:5]}...{api_key[-5:]}")
            # Anahtarlar yüklüyse onaylama iste
            confirm = input("Yüklenen API anahtarlarını kullanmak istiyor musunuz? (E/H): ").lower()
            if confirm != 'e':
                api_key = input("Binance API anahtarınızı girin: ").strip()  # Boşlukları temizle
                api_secret = input("Binance API gizli anahtarınızı girin: ").strip()  # Boşlukları temizle
        
        # Bakiye çekicisini oluştur ve çalıştır
        print("Binance bakiyeleri getiriliyor...")
        fetcher = BinanceBalanceFetcher(api_key, api_secret, debug=debug_mode)
        balances = fetcher.print_balances()
        
        if balances:
            print("İşlem başarılı!")
            
            # PHP web uygulaması için özet bakiye bilgisini oluştur
            total_spot = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('spot', [])])
            total_margin = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('margin', [])])
            total_isolated = sum([float(asset['free']) + float(asset['locked']) for asset in balances.get('isolated_margin', [])])
            total_futures = sum([float(asset['balance']) for asset in balances.get('futures', [])])
            wallet_btc = balances.get('wallet_btc_value', 0)
            btc_price = fetcher.get_btc_price()
            
            print("\n=== ÖZET BAKIYE BİLGİSİ ===")
            print(f"Toplam Spot: {total_spot:.2f} USDT")
            print(f"Toplam Cross Margin: {total_margin:.2f} USDT")
            print(f"Toplam Isolated Margin: {total_isolated:.2f} USDT")
            print(f"Toplam Futures: {total_futures:.2f} USDT")
            print(f"Toplam Portföy Değeri (BTC): {wallet_btc}")
            print(f"Tahmini Toplam Değer (USD): ${float(wallet_btc) * btc_price:.2f}")
            print(f"Toplam Bakiye (USDT): {total_spot + total_margin + total_isolated + total_futures:.2f} USDT")
            print("\nBu bilgiler web/api/binance_total_balances.json dosyasına kaydedildi.")
        else:
            print("Bakiyeler getirilemedi!")
            
    except Exception as e:
        print(f"Hata oluştu: {e}")
        print(f"Hata detayları: {traceback.format_exc()}")
        
    print("\nİşlem tamamlandı. Çıkmak için Enter tuşuna basın...")
    input()