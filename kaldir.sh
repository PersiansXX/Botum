#!/bin/bash

# Bu script UNIX satır sonu karakterleri ile yazılmıştır
# TRADING_BOT güvenlik kontrolünü kaldırmak için kullanılır

# Çalıştırılan dizini kaydet
SCRIPT_DIR="$(pwd)"
echo "Trading Bot güvenlik kontrollerini kaldırma işlemi başlatılıyor..."
echo "Çalışma dizini: $SCRIPT_DIR"

# Toplam değiştirilen dosya sayısını takip etmek için sayaç
CHANGED_FILES=0

# Yedek dizini oluştur
BACKUP_DIR="$SCRIPT_DIR/security_backups_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
echo "Dosyaların yedekleri şu dizinde saklanacak: $BACKUP_DIR"

# Tüm PHP dosyalarını bul
find "$SCRIPT_DIR" -type f -name "*.php" | while read -r file
do
    # Dosyada TRADING_BOT kontrolü var mı kontrol et
    if grep -q "defined.*TRADING_BOT" "$file" || grep -q "!defined.*TRADING_BOT" "$file"; then
        # Dosyanın bir yedek kopyasını al
        cp "$file" "$BACKUP_DIR/$(basename "$file")"
        
        echo "Düzenleniyor: $file"
        
        # Farklı TRADING_BOT kontrol desenlerini arayıp değiştir
        
        # Desen 1: if (!defined('TRADING_BOT')) { ... }
        sed -i 's/if *(!defined(.TRADING_BOT.) *|| *!defined(.TRADING_BOT.) *=== *true) *{.*exit;.*}/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/if *(!defined(.TRADING_BOT.)) *{.*exit;.*}/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/if *(!defined(.TRADING_BOT.)) *{.*die(..*);.*}/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/if *(.*!defined(.TRADING_BOT.).*) *{.*exit.*}/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/if *(.*!defined(.TRADING_BOT.).*) *{.*die.*}/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        
        # Desen 2: defined('TRADING_BOT') or exit/die
        sed -i 's/defined(.TRADING_BOT.) *or *exit;/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/defined(.TRADING_BOT.) *or *die(..*);/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/defined(.TRADING_BOT.) *or *die;/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        
        # Desen 3: defined('TRADING_BOT') || exit/die
        sed -i 's/defined(.TRADING_BOT.) *|| *exit;/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/defined(.TRADING_BOT.) *|| *die(..*);/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        sed -i 's/defined(.TRADING_BOT.) *|| *die;/\/\/ Güvenlik kontrolü kaldırıldı/g' "$file"
        
        # TRADING_BOT tanımlamasını yorum satırına dönüştür veya kaldır
        sed -i "s/define('TRADING_BOT', *true);/\/\/ define('TRADING_BOT', true); \/\/ Güvenlik kontrolü kaldırıldı/g" "$file"
        
        # Değiştirilen dosya sayısını artır
        CHANGED_FILES=$((CHANGED_FILES+1))
    fi
done

echo "İşlem tamamlandı!"
echo "Toplam değiştirilen dosya sayısı: $CHANGED_FILES"
echo "Yedekler şurada bulunabilir: $BACKUP_DIR"
echo ""
echo "Not: Bazı karmaşık güvenlik kontrolleri manuel olarak düzenleme gerektirebilir."
echo "Beklenmedik bir davranış görürseniz yedekleri kullanarak orijinal dosyalara geri dönebilirsiniz."