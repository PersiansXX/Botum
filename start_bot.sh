#!/bin/bash

# Bot dizinine git
cd /var/www/html/bot

# Log hazırlığı
log_file="/var/www/html/bot/bot.log"
error_log="/var/www/html/bot/bot_error.log"
pid_file="/var/www/html/bot/bot.pid"

# Başlatma bilgisini logla
echo "--- $(date) - Bot başlatma denemesi ---" >> $error_log

# tradingbot kullanıcısının varlığını kontrol et
if ! id "tradingbot" &>/dev/null; then
    echo "tradingbot kullanıcısı bulunamadı! Oluşturuluyor..." >> $error_log
    useradd -m tradingbot
    echo "tradingbot kullanıcısı oluşturuldu." >> $error_log
fi

# Bot dizini ve dosyaları için izinleri ayarla
echo "Bot dizini ve dosya izinleri düzenleniyor..." >> $error_log
# Grup oluştur ve kullanıcıları ekle
groupadd -f apache 2>/dev/null || true
usermod -a -G apache tradingbot 2>/dev/null || true

# Apache/web sunucusu kullanıcısını tespit et
WEB_USER=$(ps aux | grep -E "apache|httpd|www-data" | grep -v 'root' | head -1 | awk '{print $1}')
if [ ! -z "$WEB_USER" ]; then
    usermod -a -G apache $WEB_USER 2>/dev/null || true
    echo "Web sunucusu kullanıcısı ($WEB_USER) apache grubuna eklendi." >> $error_log
fi

# Bot dizini izinlerini ayarla
chown -R tradingbot:apache "/var/www/html/bot"
chmod -R 775 "/var/www/html/bot"

# Log ve PID dosyalarını oluştur ve izinlerini ayarla
touch "$log_file" "$error_log" "$pid_file" 2>/dev/null
chown tradingbot:apache "$log_file" "$error_log" "$pid_file" 2>/dev/null
chmod 664 "$log_file" "$error_log" "$pid_file" 2>/dev/null

# Config dizinine yazma izni ver
if [ -d "/var/www/html/bot/config" ]; then
    chown -R tradingbot:apache "/var/www/html/bot/config" 2>/dev/null
    chmod -R 775 "/var/www/html/bot/config" 2>/dev/null
    chmod 664 /var/www/html/bot/config/*.json 2>/dev/null || true
    echo "Config dizini izinleri ayarlandı" >> $error_log
fi

# Gerekli paketleri yükle
su - tradingbot -c "python3.8 -m pip install --user ccxt pandas numpy matplotlib websocket-client python-telegram-bot mysql-connector-python >/dev/null 2>&1"

# Çalışan tüm bot süreçlerini durdur
pkill -f "trading_bot.py" 2>/dev/null || true
sleep 2

# Bot başlatma - kesinlikle tradingbot kullanıcısı ile
echo "Botu tradingbot kullanıcısı ile başlatmaya çalışıyorum..." >> $error_log
su - tradingbot -c "cd /var/www/html/bot && /usr/local/bin/python3.8 trading_bot.py >> $log_file 2>> $error_log &"

# PID'i al - tradingbot kullanıcısı ile çalışan süreci bul
sleep 2
pid=$(ps -ef | grep "trading_bot.py" | grep "tradingbot" | grep -v grep | awk '{print $2}' | head -n 1)

# PID kontrolü ve kayıt
if [[ ! -z "$pid" ]]; then
    echo $pid > $pid_file
    chown tradingbot:apache "$pid_file" 2>/dev/null
    chmod 664 "$pid_file" 2>/dev/null
    echo "Bot başlatıldı. PID: $pid" >> $error_log
    echo $pid  # PHP'ye PID'i döndür
else
    echo "Bot başlatılamadı!" >> $error_log
    echo "Hata kontrol ediliyor..." >> $error_log
    ls -la "/var/www/html/bot" >> $error_log 2>&1
    ls -la "$log_file" "$error_log" "$pid_file" >> $error_log 2>&1
    exit 1
fi