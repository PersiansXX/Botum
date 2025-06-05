#!/bin/bash

# Hata ayıklama modunu aç
set -e

# Çalışma dizini ayarla
cd /var/www/html/bot

# PID dosyalarını temizle
rm -f bot.pid
rm -f ../bot.pid

# Log dosyalarını yedekle
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
if [ -f "bot.log" ]; then
    cp bot.log "bot_backup_${TIMESTAMP}.log"
    echo "=== Bot yeniden başlatıldı: $(date) ===" > bot.log
fi

if [ -f "bot_error.log" ]; then
    cp bot_error.log "bot_error_backup_${TIMESTAMP}.log"
    echo "=== Bot yeniden başlatıldı: $(date) ===" > bot_error.log
fi

# Şu anki kullanıcıyı kontrol et
CURRENT_USER=$(whoami)
echo "Bot şu kullanıcı ile başlatılıyor: $CURRENT_USER"

# Python sürümünü kontrol et
PYTHON_BIN=$(which python3.8 2>/dev/null || which python3 2>/dev/null || which python 2>/dev/null)
echo "Python binary: $PYTHON_BIN"
$PYTHON_BIN --version

# Gerekli dosyaların varlığını kontrol et
if [ ! -f "trading_bot.py" ]; then
    echo "HATA: trading_bot.py bulunamadı!"
    exit 1
fi

if [ ! -d "config" ]; then
    echo "HATA: config dizini bulunamadı!"
    exit 1
fi

if [ ! -f "config/bot_config.json" ]; then
    echo "HATA: config/bot_config.json bulunamadı!"
    exit 1
fi

# İzinleri ayarla
chmod 755 .
chmod 755 config
chmod 644 config/*.json
chmod 644 *.py
chmod 666 bot.log bot_error.log 2>/dev/null || true

# Mevcut çalışan bot süreçlerini kontrol et ve durdur
BOT_PIDS=$(ps -ef | grep "trading_bot.py" | grep -v grep | awk '{print $2}')
if [ ! -z "$BOT_PIDS" ]; then
    echo "Çalışan bot süreçleri bulundu. Durduruluyor..."
    for pid in $BOT_PIDS; do
        echo "PID $pid durduruluyor..."
        kill -15 $pid 2>/dev/null || kill -9 $pid 2>/dev/null || true
    done
    sleep 2
fi

# Botu başlat (screen içinde)
echo "Bot başlatılıyor..."
if command -v screen >/dev/null 2>&1; then
    screen -dmS trading_bot $PYTHON_BIN trading_bot.py
    echo "Bot screen oturumunda başlatıldı. 'screen -r trading_bot' ile bağlanabilirsiniz."
    sleep 2
    
    # PID'i kontrol et ve kaydet
    BOT_PID=$(ps -ef | grep "trading_bot.py" | grep -v grep | awk '{print $2}' | head -n 1)
    if [ ! -z "$BOT_PID" ]; then
        echo $BOT_PID > bot.pid
        echo $BOT_PID > ../bot.pid
        chmod 666 bot.pid ../bot.pid
        echo "Bot başarıyla başlatıldı. PID: $BOT_PID"
    else
        echo "Bot başlatma başarısız olabilir. PID bulunamadı."
    fi
else
    # Screen yoksa nohup ile başlat
    echo "Screen bulunamadı. Nohup kullanılıyor..."
    nohup $PYTHON_BIN trading_bot.py > bot_nohup.log 2>&1 &
    BOT_PID=$!
    echo $BOT_PID > bot.pid
    echo $BOT_PID > ../bot.pid
    chmod 666 bot.pid ../bot.pid
    echo "Bot başarıyla başlatıldı. PID: $BOT_PID"
fi

# Bot durumunu kontrol et
sleep 3
if ps -p $BOT_PID >/dev/null 2>&1; then
    echo "Bot çalışıyor. Detaylı durum kontrolü:"
    ps -f -p $BOT_PID
    
    # Log dosyasının son satırlarını göster
    echo "Log dosyasından son çıktılar:"
    tail -10 bot.log
else
    echo "UYARI: Bot çalışmıyor olabilir!"
    
    # Hata günlüğünü kontrol et
    if [ -f "bot_error.log" ]; then
        echo "Hata günlüğünden son çıktılar:"
        tail -10 bot_error.log
    fi
fi

echo "Bot başlatma işlemi tamamlandı."