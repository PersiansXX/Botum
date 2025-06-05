#!/bin/bash
# CentOS 7 için trading bot'u başlatma yardımcısı

echo "Trading Bot Başlatma Aracı"
echo "=========================="

# Root ile çalışıp çalışmadığını kontrol et
if [ "$(id -u)" -eq 0 ]; then
    echo "Bu script root olarak çalıştırılmamalıdır."
    echo "Lütfen normal kullanıcı hesabınızla çalıştırın."
    exit 1
fi

# Bot dizinleri ve dosyaları
BOT_DIR="/var/www/html/bot"
CONFIG_DIR="/var/www/html/config"
LOG_FILE="/var/www/html/bot.log"
ERROR_LOG="/var/www/html/bot_error.log"
PID_FILE="$BOT_DIR/bot.pid"
ALT_PID_FILE="/var/www/html/bot.pid"

# Bot kurulumunu kontrol et
if [ ! -d "$BOT_DIR" ] || [ ! -d "$CONFIG_DIR" ]; then
    echo "HATA: Bot dizinleri bulunamadı."
    echo "Lütfen önce kurulum scriptini çalıştırın:"
    echo "sudo bash install_python_modules_centos.sh"
    exit 1
fi

# Çalışan bot kontrolü
if [ -f "$PID_FILE" ] || [ -f "$ALT_PID_FILE" ]; then
    PID=""
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
    elif [ -f "$ALT_PID_FILE" ]; then
        PID=$(cat "$ALT_PID_FILE")
    fi
    
    if [ -n "$PID" ] && ps -p "$PID" > /dev/null; then
        echo "Bot zaten çalışıyor (PID: $PID)"
        echo "Önce durdurmak için: sudo bash stop_trading_bot.sh"
        exit 1
    else
        echo "Eski PID dosyası bulundu ama bot çalışmıyor. Temizleniyor..."
    fi
fi

# Hangi yöntemle çalıştırmak istediğini sor
echo 
echo "Bot çalıştırma yöntemini seçin:"
echo "1) Start script ile çalıştır (root gerekli)"
echo "2) Direkt bot scriptini çalıştır (tradingbot kullanıcısı olarak)"
echo "3) Web üzerinden çalıştır"
echo
read -p "Seçiminiz (1-3): " choice

case $choice in
    1)
        echo "Start script ile çalıştırılıyor..."
        sudo bash start_trading_bot.sh
        ;;
    2)
        echo "Direkt bot scripti çalıştırılıyor..."
        if [ -f "direct_start_bot.sh" ] && [ -x "direct_start_bot.sh" ]; then
            sudo -u tradingbot bash direct_start_bot.sh
        else
            echo "HATA: direct_start_bot.sh dosyası bulunamadı veya çalıştırılamıyor."
            echo "Dosyayı ayarlayın: sudo chmod 700 direct_start_bot.sh"
            echo "Kullanıcı ayarlayın: sudo chown tradingbot:tradingbot direct_start_bot.sh"
            exit 1
        fi
        ;;
    3)
        echo "Web adresi açılıyor..."
        echo "Tarayıcınızda açın: http://$(hostname -I | awk '{print $1}')/web"
        ;;
    *)
        echo "Geçersiz seçim. Çıkılıyor."
        exit 1
        ;;
esac

# Başlatma sonrası durum kontrolü
echo
echo "Bot Durumu:"
if [ -f "$PID_FILE" ] || [ -f "$ALT_PID_FILE" ]; then
    PID=""
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
    elif [ -f "$ALT_PID_FILE" ]; then
        PID=$(cat "$ALT_PID_FILE")
    fi
    
    if [ -n "$PID" ] && ps -p "$PID" > /dev/null; then
        echo "✅ Bot çalışıyor (PID: $PID)"
        echo "Son 5 log satırı:"
        tail -5 "$LOG_FILE"
    else
        echo "❌ Bot başlatılamadı veya durdu."
        echo "Hata loglarını kontrol edin:"
        tail -5 "$ERROR_LOG"
    fi
else
    echo "❌ Bot PID dosyası bulunamadı. Bot çalışmıyor olabilir."
fi

echo
echo "Yardımcı Komutlar:"
echo "- Botu durdurmak için: sudo bash stop_trading_bot.sh"
echo "- Bot durumunu kontrol etmek için: ps aux | grep trading_bot.py"
echo "- Logları izlemek için: tail -f $LOG_FILE"
echo "- Hata loglarını izlemek için: tail -f $ERROR_LOG"