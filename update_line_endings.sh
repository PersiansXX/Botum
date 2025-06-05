#!/bin/bash
# Windows'tan Linux'a dosya aktarırken satır sonu karakterlerini düzeltme

echo "Windows satır sonu karakterleri (CRLF) Linux satır sonu karakterlerine (LF) dönüştürülüyor..."

# dos2unix aracını kontrol et ve yükle
if ! command -v dos2unix &> /dev/null; then
    echo "dos2unix aracı kuruluyor..."
    yum install -y dos2unix
fi

# Tüm .sh dosyalarının satır sonlarını düzelt
echo "Shell scriptleri düzeltiliyor..."
find /var/www/html -name "*.sh" -type f -exec dos2unix {} \;

# Python dosyaları için de kontrol et
echo "Python dosyaları düzeltiliyor..."
find /var/www/html -name "*.py" -type f -exec dos2unix {} \;

# PHP dosyaları için de kontrol et
echo "PHP dosyaları düzeltiliyor..."
find /var/www/html -name "*.php" -type f -exec dos2unix {} \;

echo "Satır sonu karakterleri başarıyla düzeltildi."
echo "Şimdi kurulum scriptini çalıştırabilirsiniz:"
echo "sudo bash install_python_modules_centos.sh"