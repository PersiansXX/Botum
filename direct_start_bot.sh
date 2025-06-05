#!/bin/bash
# CentOS 7 için tradingbot kullanıcısı olarak direkt çalıştırma scripti
# Bu dosyaya 700 izni ver ve tradingbot kullanıcısına ait yapın
# chmod 700 direct_start_bot.sh
# chown tradingbot:tradingbot direct_start_bot.sh

# Log dosyaları ve dizinler
BOT_DIR="/var/www/html/bot"
LOG_FILE="/var/www/html/bot.log"
ERROR_LOG="/var/www/html/bot_error.log"
PID_FILE="$BOT_DIR/bot.pid"
ALT_PID_FILE="/var/www/html/bot.pid"
CONFIG_DIR="/var/www/html/config"

# Script başlangıç zaman damgası
START_TIME=$(date '+%Y-%m-%d %H:%M:%S')
echo "$START_TIME - Bot başlatma işlemi başlatıldı" >> $LOG_FILE

# Python yolunu otomatik tespit et
PYTHON_PATH=""
for python_cmd in python3 python3.8 python3.7 python3.6; do
    if command -v $python_cmd &> /dev/null; then
        PYTHON_PATH=$(command -v $python_cmd)
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Python yolu: $PYTHON_PATH" >> $LOG_FILE
        break
    fi
done

# Python yolu bulunamazsa çık
if [ -z "$PYTHON_PATH" ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - HATA: Python yürütülebilir dosyası bulunamadı!" >> $ERROR_LOG
    exit 1
fi

# Gerekli Python paketlerinin yüklü olup olmadığını kontrol et
echo "Python bağımlılıkları kontrol ediliyor..." >> $LOG_FILE
REQUIRED_PACKAGES=("ccxt" "pandas" "numpy" "python-telegram-bot" "mysql-connector-python")
MISSING_PACKAGES=()

for package in "${REQUIRED_PACKAGES[@]}"; do
    if ! $PYTHON_PATH -c "import $package" &> /dev/null; then
        MISSING_PACKAGES+=("$package")
    fi
done

# Eksik paketleri yükle
if [ ${#MISSING_PACKAGES[@]} -gt 0 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - UYARI: Eksik paketler var: ${MISSING_PACKAGES[*]}" >> $LOG_FILE
    echo "Lütfen 'sudo bash install_python_modules_centos.sh' komutunu çalıştırın." >> $LOG_FILE
    exit 1
fi

# Daha önce çalışan botları kontrol et
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if ps -p "$OLD_PID" > /dev/null; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') - UYARI: Bot zaten çalışıyor (PID: $OLD_PID). Önce durdurun." >> $LOG_FILE
        echo "UYARI: Bot zaten çalışıyor (PID: $OLD_PID). Önce durdurun."
        exit 1
    else
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Eski PID dosyası mevcut ama süreç aktif değil. Dosya güncelleniyor." >> $LOG_FILE
        rm -f "$PID_FILE"
        rm -f "$ALT_PID_FILE" 2>/dev/null
    fi
fi

# Botu doğrudan başlat (sudo olmadan)
cd "$BOT_DIR"
echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot başlatılıyor..." >> $LOG_FILE
nohup $PYTHON_PATH "$BOT_DIR/trading_bot.py" > /dev/null 2>> "$ERROR_LOG" &

# PID'i kaydet
BOT_PID=$!
echo $BOT_PID > "$PID_FILE"
# Alternatif PID dosyasını da güncelle
echo $BOT_PID > "$ALT_PID_FILE"

# Botun başarıyla başlatıldığını kontrol et
sleep 3
if ps -p "$BOT_PID" > /dev/null; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot başarıyla başlatıldı. PID: $BOT_PID" >> $LOG_FILE
    echo "Bot başarıyla başlatıldı. PID: $BOT_PID"
    
    # PID dosyalarını herkese okunabilir yap
    chmod 666 "$PID_FILE"
    chmod 666 "$ALT_PID_FILE"
    
    exit 0
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - HATA: Bot başlatılamadı!" >> $ERROR_LOG
    echo "HATA: Bot başlatılamadı! Lütfen hata log dosyasını kontrol edin: $ERROR_LOG"
    exit 1
fi