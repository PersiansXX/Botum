#!/bin/bash

# Linux için sabit yapılandırma
ROOT_DIR="/var/www/html"

# Log ve PID dosyalarının konumları
BOT_DIR="$ROOT_DIR/bot"
ERROR_LOG="$BOT_DIR/bot_error.log"
PID_FILE="$BOT_DIR/bot.pid"
ALT_PID_FILE="$ROOT_DIR/bot.pid"

# Hata logunu başlat
echo "$(date '+%Y-%m-%d %H:%M:%S') - Durdurma betiği çalıştırıldı" >> "$ERROR_LOG"

# Çalışan tüm bot süreçlerini bul
BOT_PROCESSES=$(ps aux | grep "python3\|python\|python2" | grep "trading_bot.py" | grep -v grep | awk '{print $2}')
PROCESS_COUNT=$(echo "$BOT_PROCESSES" | wc -l)

echo "$(date '+%Y-%m-%d %H:%M:%S') - Bulunan bot süreç sayısı: $PROCESS_COUNT" >> "$ERROR_LOG"
echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot süreçleri: $BOT_PROCESSES" >> "$ERROR_LOG"

# PID dosyasından da mevcut botu al
PID_FROM_FILE=""
if [ -f "$PID_FILE" ]; then
    PID_FROM_FILE=$(cat "$PID_FILE")
    echo "$(date '+%Y-%m-%d %H:%M:%S') - PID dosyasından ($PID_FILE) okunan PID: $PID_FROM_FILE" >> "$ERROR_LOG"
fi

# Alternatif PID dosyasından da mevcut botu al
if [ -f "$ALT_PID_FILE" ]; then
    ALT_PID=$(cat "$ALT_PID_FILE")
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Alternatif PID dosyasından ($ALT_PID_FILE) okunan PID: $ALT_PID" >> "$ERROR_LOG"
    # PID değişkenini ayarla veya ekle
    if [ -z "$PID_FROM_FILE" ]; then
        PID_FROM_FILE="$ALT_PID"
    else
        PID_FROM_FILE="$PID_FROM_FILE $ALT_PID"
    fi
fi

# PID dosyası boş olsa bile bulunan süreçleri kullan
if [ -z "$BOT_PROCESSES" ]; then
    if [ -z "$PID_FROM_FILE" ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Çalışan bot süreci bulunamadı" >> "$ERROR_LOG"
        echo "Bot çalışmıyor"
        # Her ihtimale karşı PID dosyalarını temizle
        rm -f "$PID_FILE" 2>/dev/null
        rm -f "$ALT_PID_FILE" 2>/dev/null
        exit 0
    else
        BOT_PROCESSES="$PID_FROM_FILE"
    fi
fi

# PID değerlerini birleştir ve tekrarlananları kaldır
ALL_PIDS=$(echo "$BOT_PROCESSES $PID_FROM_FILE" | tr ' ' '\n' | sort -u | grep -v '^$')
TOTAL_COUNT=$(echo "$ALL_PIDS" | wc -l)

echo "$(date '+%Y-%m-%d %H:%M:%S') - Toplam durdurulacak süreç sayısı: $TOTAL_COUNT" >> "$ERROR_LOG"
echo "$(date '+%Y-%m-%d %H:%M:%S') - Durdurulacak süreçler: $ALL_PIDS" >> "$ERROR_LOG"

# Her birini sonlandır
STOPPED_COUNT=0
for PID in $ALL_PIDS; do
    if [ -n "$PID" ] && [ "$PID" -gt 0 ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') - PID: $PID sonlandırılıyor" >> "$ERROR_LOG"
        
        # Linux için proses kontrolü
        if ps -p "$PID" > /dev/null 2>&1; then
            # Önce normal sonlandırma dene
            kill "$PID" >> "$ERROR_LOG" 2>&1
            
            # Biraz bekle
            sleep 2
            
            # Hala çalışıyor mu kontrol et
            if ps -p "$PID" > /dev/null 2>&1; then
                echo "$(date '+%Y-%m-%d %H:%M:%S') - Normal sonlandırma başarısız, SIGTERM deniyor" >> "$ERROR_LOG"
                kill -15 "$PID" >> "$ERROR_LOG" 2>&1
                
                sleep 2
                
                # Son çare olarak SIGKILL (kill -9) kullan
                if ps -p "$PID" > /dev/null 2>&1; then
                    echo "$(date '+%Y-%m-%d %H:%M:%S') - SIGTERM başarısız, SIGKILL ile zorunlu sonlandırma" >> "$ERROR_LOG"
                    kill -9 "$PID" >> "$ERROR_LOG" 2>&1
                fi
            fi
            
            STOPPED_COUNT=$((STOPPED_COUNT + 1))
        else
            echo "$(date '+%Y-%m-%d %H:%M:%S') - PID: $PID bulunamadı veya çalışmıyor" >> "$ERROR_LOG"
        fi
    else
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Geçersiz PID: $PID" >> "$ERROR_LOG"
    fi
done

# En son bir kez daha pkill ile kontrol et
RUNNING_BOTS=$(ps aux | grep "python3\|python\|python2" | grep "trading_bot.py" | grep -v grep | wc -l)
if [ $RUNNING_BOTS -gt 0 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Hala $RUNNING_BOTS adet bot süreci çalışıyor. pkill kullanılıyor" >> "$ERROR_LOG"
    pkill -f "trading_bot.py" >> "$ERROR_LOG" 2>&1
    sleep 1
    
    # Yine de kaldıysa SIGKILL ile zorunlu sonlandır
    STILL_RUNNING=$(ps aux | grep "python3\|python\|python2" | grep "trading_bot.py" | grep -v grep | wc -l)
    if [ $STILL_RUNNING -gt 0 ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') - $STILL_RUNNING adet süreç hala direniyor. SIGKILL kullanılıyor" >> "$ERROR_LOG"
        pkill -9 -f "trading_bot.py" >> "$ERROR_LOG" 2>&1
    fi
fi

# PID dosyalarını temizle
rm -f "$PID_FILE" 2>/dev/null
rm -f "$ALT_PID_FILE" 2>/dev/null

echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot durdurma işlemi tamamlandı - $STOPPED_COUNT süreç durduruldu" >> "$ERROR_LOG"

# Sonuç bildirimi
FINAL_CHECK=$(ps aux | grep "python3\|python\|python2" | grep "trading_bot.py" | grep -v grep | wc -l)
if [ $FINAL_CHECK -eq 0 ]; then
    echo "Bot(lar) başarıyla durduruldu ($STOPPED_COUNT süreç sonlandırıldı)"
    exit 0
else
    echo "UYARI: $FINAL_CHECK adet bot süreci hala çalışıyor olabilir"
    echo "Manuel kontrol için: ps aux | grep trading_bot.py | grep -v grep"
    exit 1
fi