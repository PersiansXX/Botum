#!/bin/bash
# Line ending düzeltme script'i
dos2unix start_trading_bot_fixed.sh

# dos2unix yüklü değilse, alternatif yöntem
if [ $? -ne 0 ]; then
    echo "dos2unix yüklü değil, sed ile düzeltme yapılıyor..."
    sed -i 's/\r$//' start_trading_bot_fixed.sh
fi

# Çalıştırılabilir yap
chmod +x start_trading_bot_fixed.sh

echo "Script düzeltildi, şimdi çalıştırabilirsiniz."
