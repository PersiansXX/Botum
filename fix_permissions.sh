#!/bin/bash
# Bot için grup ve izin ayarları
WEB_USER=$(ps aux | grep -E "apache|httpd|www-data" | grep -v root | head -1 | awk '{print $1}')
echo "Web sunucusu kullanıcısı: $WEB_USER"

# Bot grup oluştur
groupadd -f botgroup

# Kullanıcıları gruba ekle
usermod -a -G botgroup tradingbot 2>/dev/null || true
usermod -a -G botgroup $WEB_USER 2>/dev/null || true

# Bot dizini izinleri
BOT_DIR="/var/www/html/bot"
chgrp -R botgroup $BOT_DIR
chmod -R 775 $BOT_DIR

# Log ve PID dosyaları
touch "$BOT_DIR/bot.log" "$BOT_DIR/bot_error.log" "$BOT_DIR/bot.pid"
chmod 666 "$BOT_DIR/bot.log" "$BOT_DIR/bot_error.log" "$BOT_DIR/bot.pid"

# Config dosyaları
find $BOT_DIR/config -type f -name "*.json" -exec chmod 664 {} \;
find $BOT_DIR/config -type f -name "*.json" -exec chgrp botgroup {} \;

echo "İzinler düzenlendi"
exit 0
