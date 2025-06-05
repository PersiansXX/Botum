import logging
import sys
import os
import traceback

# Log seviyesini debug'a ayarla
logging.basicConfig(level=logging.DEBUG)
logger = logging.getLogger()

try:
    print("Bot başlatılıyor...")
    print(f"Çalışma dizini: {os.getcwd()}")
    print(f"Python sürümü: {sys.version}")
    
    # PID dosyasının varlığını kontrol et
    if os.path.exists("bot.pid"):
        print("PID dosyası mevcut, siliniyor...")
        os.remove("bot.pid")
    
    # Modüllerin yüklü olduğunu kontrol et
    import_list = [
        "ccxt", "pandas", "numpy", "mysql.connector", 
        "requests", "talib", "dotenv"
    ]
    
    for module in import_list:
        try:
            __import__(module)
            print(f"✅ {module} modülü yüklü")
        except ImportError:
            print(f"❌ {module} modülü bulunamadı! pip install {module} ile yükleyin")
    
    # Bot modülünü içe aktar
    from trading_bot import TradingBot
    
    # Bot nesnesini oluştur ve başlat
    bot = TradingBot()
    print("Bot nesnesi başarıyla oluşturuldu, start() çağrılıyor...")
    bot.start()
    
except Exception as e:
    print(f"HATA: {str(e)}")
    print("Ayrıntılı hata:")
    traceback.print_exc()
