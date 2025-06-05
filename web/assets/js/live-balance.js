document.addEventListener('DOMContentLoaded', function() {
    // Bakiye güncelleme aralığı (ms)
    const UPDATE_INTERVAL = 30000; // 30 saniye
    const baseCurrency = '<?php echo $bot_api->getSettings()["base_currency"] ?? "USDT"; ?>';
    
    // Bakiye DOM elemanları
    const totalBalanceElement = document.getElementById('total-balance');
    const spotBalanceElement = document.getElementById('spot-balance');
    const futuresBalanceElement = document.getElementById('futures-balance');
    const marginBalanceElement = document.getElementById('margin-balance');
    
    const lastBalanceValue = localStorage.getItem('last_balance_value') || '<?php echo number_format($total_balance, 2); ?>';
    const lastSpotBalanceValue = localStorage.getItem('last_spot_balance_value') || '<?php echo number_format($spot_total_balance, 2); ?>';
    const lastFuturesBalanceValue = localStorage.getItem('last_futures_balance_value') || '<?php echo number_format($futures_total_balance, 2); ?>';
    const lastMarginBalanceValue = localStorage.getItem('last_margin_balance_value') || '<?php echo number_format($margin_total_balance, 2); ?>';
    
    // İlk yüklemede localStorage'dan değerleri ayarla
    if (totalBalanceElement && lastBalanceValue) {
        totalBalanceElement.textContent = lastBalanceValue;
    }
    if (spotBalanceElement && lastSpotBalanceValue) {
        spotBalanceElement.textContent = lastSpotBalanceValue;
    }
    if (futuresBalanceElement && lastFuturesBalanceValue) {
        futuresBalanceElement.textContent = lastFuturesBalanceValue;
    }
    if (marginBalanceElement && lastMarginBalanceValue) {
        marginBalanceElement.textContent = lastMarginBalanceValue;
    }
    
    // API'den bakiye bilgilerini güncelleme fonksiyonu
    function updateBalance() {
        // Başlangıçta yükleniyor göster
        if (totalBalanceElement) {
            totalBalanceElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        if (spotBalanceElement) {
            spotBalanceElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        if (futuresBalanceElement) {
            futuresBalanceElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        if (marginBalanceElement) {
            marginBalanceElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        // Zaman aşımı ayarla
        const timeoutPromise = new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Zaman aşımı')), 10000)
        );
        
        // Doğrudan Binance API'ye erişen yeni endpoint'imizi çağır
        Promise.race([
            fetch('api/live_balance_api.php?type=all&nocache=' + new Date().getTime()),
            timeoutPromise
        ])
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const formatter = new Intl.NumberFormat('tr-TR', { 
                        minimumFractionDigits: 2, 
                        maximumFractionDigits: 2 
                    });
                    
                    // Toplam bakiye güncelleme
                    if (totalBalanceElement) {
                        const formattedBalance = formatter.format(data.total);
                        totalBalanceElement.textContent = formattedBalance;
                        localStorage.setItem('last_balance_value', formattedBalance);
                        flashElement(totalBalanceElement);
                    }
                    
                    // Spot bakiye
                    if (spotBalanceElement) {
                        const formattedSpotBalance = formatter.format(data.spot_total);
                        spotBalanceElement.textContent = formattedSpotBalance;
                        localStorage.setItem('last_spot_balance_value', formattedSpotBalance);
                        flashElement(spotBalanceElement);
                    }
                    
                    // Futures bakiye
                    if (futuresBalanceElement) {
                        const formattedFuturesBalance = formatter.format(data.futures_total);
                        futuresBalanceElement.textContent = formattedFuturesBalance;
                        localStorage.setItem('last_futures_balance_value', formattedFuturesBalance);
                        flashElement(futuresBalanceElement);
                    }
                    
                    // Margin bakiye
                    if (marginBalanceElement) {
                        const formattedMarginBalance = formatter.format(data.margin_total);
                        marginBalanceElement.textContent = formattedMarginBalance;
                        localStorage.setItem('last_margin_balance_value', formattedMarginBalance);
                        flashElement(marginBalanceElement);
                    }
                } else {
                    console.warn('Bakiye güncellenemedi:', data.message);
                    showBalanceErrorAlert("API yanıtı başarısız: " + data.message);
                    
                    // Hata durumunda tüm bakiyeleri hata olarak göster
                    if (totalBalanceElement) {
                        totalBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                    }
                    if (spotBalanceElement) {
                        spotBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                    }
                    if (futuresBalanceElement) {
                        futuresBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                    }
                    if (marginBalanceElement) {
                        marginBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                    }
                }
            })
            .catch(error => {
                console.error('API erişim hatası:', error);
                showBalanceErrorAlert("API'ye erişilemedi: " + error.message);
                
                // Hata durumunda tüm bakiyeleri hata olarak göster
                if (totalBalanceElement) {
                    totalBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                }
                if (spotBalanceElement) {
                    spotBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                }
                if (futuresBalanceElement) {
                    futuresBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                }
                if (marginBalanceElement) {
                    marginBalanceElement.innerHTML = '<span class="text-danger">BAKİYE HATA</span>';
                }
            });
    }
    
    // Bakiye hatası durumunda uyarı göster
    function showBalanceErrorAlert(message) {
        // Sayfada zaten uyarı varsa, tekrar oluşturma
        if (document.getElementById('balance-error-alert')) {
            return;
        }
        
        // Uyarı oluştur
        const alertDiv = document.createElement('div');
        alertDiv.id = 'balance-error-alert';
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            <strong>Bakiye alınamadı!</strong> ${message || 'Binance API bağlantısında sorun oluştu. Lütfen API anahtarlarınızı kontrol edin.'}
            <a href="?refresh=1" class="btn btn-sm btn-warning ml-2">Bakiyeleri Yenilemeyi Dene</a>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        // Sayfanın üstüne ekle
        const container = document.querySelector('.container-fluid');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        }
    }
    
    // İlk bakiye güncellemesini yap
    setTimeout(updateBalance, 3000); // Sayfa yüklendikten 3 saniye sonra ilk güncellemeyi yap
    
    // Belirli aralıklarla bakiye bilgilerini güncelle
    setInterval(updateBalance, UPDATE_INTERVAL);
});

// Element güncellendiğinde geçici vurgu animasyonu ekleyen fonksiyon
function flashElement(element) {
    if (element) {
        element.classList.add('balance-updated');
        setTimeout(() => {
            element.classList.remove('balance-updated');
        }, 1000);
    }
}

// Hata vurgulama fonksiyonu
function flashElementError(element) {
    if (element) {
        element.classList.add('balance-error');
        setTimeout(() => {
            element.classList.remove('balance-error');
        }, 1000);
    }
}