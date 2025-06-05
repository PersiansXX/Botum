#!/bin/bash
# CentOS 7 için Python modüllerini yükleme betiği

echo "CentOS 7 için Python modülleri kuruluyor..."
echo "=========================================="

# Root yetkisi kontrolü
if [ "$(id -u)" != "0" ]; then
   echo "Bu betik root yetkisi ile çalıştırılmalıdır." 
   echo "Lütfen 'sudo bash install_python_modules_centos.sh' komutu ile tekrar deneyin."
   exit 1
fi

# Python3 kurulumu kontrol ve kurulumu
if ! command -v python3 &> /dev/null; then
    echo "Python3 kuruluyor..."
    yum install -y epel-release
    yum install -y python3 python3-pip python3-devel
else
    echo "Python3 zaten kurulu."
fi

# Pip güncellemesi
echo "Pip güncelleniyor..."
python3 -m pip install --upgrade pip

# Gerekli kütüphaneler için geliştirme araçları
echo "Geliştirme araçları kuruluyor..."
yum install -y gcc make openssl-devel

# Trading bot için gerekli Python modülleri
echo "Trading bot için gerekli Python modülleri yükleniyor..."
python3 -m pip install ccxt pandas numpy python-telegram-bot mysql-connector-python websocket-client requests

# tradingbot kullanıcısı kontrolü ve oluşturma
if ! id "tradingbot" &>/dev/null; then
    echo "tradingbot kullanıcısı oluşturuluyor..."
    useradd -m tradingbot
else
    echo "tradingbot kullanıcısı zaten mevcut."
fi

# Dizin ve dosya izinleri
echo "Dizin ve dosya izinleri düzenleniyor..."
BOT_DIR="/var/www/html/bot"
CONFIG_DIR="/var/www/html/config"
LOG_FILE="/var/www/html/bot.log"
ERROR_LOG="/var/www/html/bot_error.log"

# HTML dizini kontrolü ve oluşturma
if [ ! -d "/var/www/html" ]; then
    echo "/var/www/html dizini oluşturuluyor..."
    mkdir -p /var/www/html
fi

# Bot dizinleri için izinler
mkdir -p $BOT_DIR
mkdir -p $CONFIG_DIR
touch $LOG_FILE
touch $ERROR_LOG
touch "$BOT_DIR/bot.pid"
touch "/var/www/html/bot.pid"

# İzinleri ayarlama
chown -R tradingbot:tradingbot $BOT_DIR
chown -R tradingbot:tradingbot $CONFIG_DIR
chown tradingbot:tradingbot $LOG_FILE
chown tradingbot:tradingbot $ERROR_LOG
chown tradingbot:tradingbot "$BOT_DIR/bot.pid"
chown tradingbot:tradingbot "/var/www/html/bot.pid"

chmod -R 775 $BOT_DIR
chmod -R 775 $CONFIG_DIR
chmod 666 $LOG_FILE
chmod 666 $ERROR_LOG
chmod 666 "$BOT_DIR/bot.pid"
chmod 666 "/var/www/html/bot.pid"

# SELinux izinleri
if [ "$(getenforce)" != "Disabled" ]; then
    echo "SELinux izinleri ayarlanıyor..."
    yum install -y policycoreutils-python
    semanage fcontext -a -t httpd_sys_rw_content_t "$BOT_DIR(/.*)?"
    semanage fcontext -a -t httpd_sys_rw_content_t "$CONFIG_DIR(/.*)?"
    semanage fcontext -a -t httpd_sys_rw_content_t "$LOG_FILE"
    semanage fcontext -a -t httpd_sys_rw_content_t "$ERROR_LOG"
    semanage fcontext -a -t httpd_sys_rw_content_t "$BOT_DIR/bot.pid"
    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/bot.pid"
    restorecon -Rv /var/www/html
    
    # HTTP ile ilgili SELinux politikaları
    setsebool -P httpd_can_network_connect 1
    setsebool -P httpd_execmem 1
fi

# Sudo yapılandırması - Apache/nginx kullanıcılarının tradingbot kullanıcısı olarak komut çalıştırmasına izin ver
echo "Sudo yapılandırması hazırlanıyor..."
SUDOERS_FILE="/etc/sudoers.d/tradingbot"
echo "apache ALL=(tradingbot) NOPASSWD: ALL" > $SUDOERS_FILE
echo "nginx ALL=(tradingbot) NOPASSWD: ALL" >> $SUDOERS_FILE
echo "www-data ALL=(tradingbot) NOPASSWD: ALL" >> $SUDOERS_FILE
chmod 440 $SUDOERS_FILE

echo "=========================================="
echo "Kurulum tamamlandı!"
echo "Başlatma betiği: ./start_trading_bot.sh"
echo "Durdurma betiği: ./stop_trading_bot.sh"
echo "=========================================="