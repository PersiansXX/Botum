document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM yüklendi, tab sistemi başlatılıyor...");
    
    // Tüm tab linklerini seç
    var tabLinks = document.querySelectorAll('#settingsTabs a[data-toggle="tab"]');
    
    // Her tab linkine tıklama olayı ekle
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('click', function(event) {
            event.preventDefault();
            console.log("Tab tıklandı: ", this.getAttribute('href'));
            
            var tabId = this.getAttribute('data-tab');
            if (tabId) {
                // URL'yi güncelle
                window.history.pushState({}, '', 'settings.php?tab=' + tabId);
            }
            
            // Aktif tab sınıfını tüm linklerden kaldır
            tabLinks.forEach(function(link) {
                link.classList.remove('active');
                link.setAttribute('aria-selected', 'false');
            });
            
            // Tıklanan tab linkini aktif yap
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            // Tüm tab içeriklerini gizle
            var tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            // İlgili tab içeriğini göster
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.classList.add('show', 'active');
                // Tab seçimini local storage'a kaydet
                localStorage.setItem('activeSettingsTab', this.getAttribute('href'));
                localStorage.setItem('activeSettingsTabId', tabId);
            } else {
                console.error("Hedef tab içeriği bulunamadı:", this.getAttribute('href'));
            }
        });
    });
    
    // Sayfa URL'sindeki tab'ı göster, yoksa son aktif sekmeyi veya ilk sekmeyi aç
    var urlParams = new URLSearchParams(window.location.search);
    var tabFromUrl = urlParams.get('tab');
        
    if (tabFromUrl) {
        var tabLinkFromUrl = document.querySelector('#settingsTabs a[data-tab="' + tabFromUrl + '"]');
        if (tabLinkFromUrl) {
            tabLinkFromUrl.click();
            return;
        }
    }
    
    // URL'de tab yoksa, local storage'dan yükle
    var activeTabId = localStorage.getItem('activeSettingsTabId');
    if (activeTabId) {
        var savedTabLink = document.querySelector('#settingsTabs a[data-tab="' + activeTabId + '"]');
        if (savedTabLink) {
            savedTabLink.click();
            return;
        }
    }
    
    // Hiçbir tab seçilmediyse ilk sekmeyi aç
    if (document.querySelector('#settingsTabs a:first-child')) {
        document.querySelector('#settingsTabs a:first-child').click();
    }
    
    // Form elemanlarının davranışlarını ayarla
    initFormBehaviors();
    
    // Disabled olsa bile seçilmiş değerlerini form gönderiminde include et
    enableDisabledInputsOnSubmit();
    
    // Range inputlarının değerlerini göster
    updateRangeInputs();
    
    // Ağırlık toplamlarını hesapla
    updateWeightTotal();
    
    // API anahtarları için güvenlik işlevlerini başlat
    initializeApiFields();
    
    console.log("Tab sistemi başlatıldı.");
});

function enableDisabledInputsOnSubmit() {
    document.querySelector('form.settings-form').addEventListener('submit', function() {
        // Form gönderilmeden önce, disabled olan tüm inputları geçici olarak enable et
        var disabledInputs = document.querySelectorAll('input:disabled, select:disabled, textarea:disabled');
        disabledInputs.forEach(function(input) {
            input.disabled = false;
        });
        return true;
    });
}

function initFormBehaviors() {
    // İndikatörlerin etkinliğine göre kart stilini güncelle
    var checkboxInputs = document.querySelectorAll('.custom-control-input');
    checkboxInputs.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var card = this.closest('.feature-card');
            if (card) {
                if (this.checked) {
                    card.classList.add('enabled');
                    card.classList.remove('disabled');
                } else {
                    card.classList.add('disabled');
                    card.classList.remove('enabled');
                }
            }
            
            // Özel işlevleri çağır
            var inputId = this.id;
            switch (inputId) {
                case 'use_smart_trend':
                    updateSmartTrendUI();
                    break;
                case 'advanced_risk_enabled':
                    updateAdvancedRiskUI();
                    break;
                case 'dynamic_position_sizing':
                    updateDynamicPositionSizingUI();
                    break;
                case 'adaptive_params_enabled':
                    updateAdaptiveParamsUI();
                    break;
                case 'api_optimization_enabled':
                    updateApiOptimizationUI();
                    break;
            }
        });
    });
    
    // İndikatör ağırlıkları değiştiğinde toplam ağırlığı güncelle
    var weightInputs = document.querySelectorAll('.weight-input');
    weightInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            updateWeightTotal();
        });
        
        input.addEventListener('input', function() {
            updateWeightTotal();
        });
    });
    
    // Form gönderildiğinde yükleniyor göstergesi
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            var submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
                submitButton.disabled = true;
            }
            return true;
        });
    });
}

// İndikatör ağırlıklarının toplamını güncelle
function updateWeightTotal() {
    var weightInputs = document.querySelectorAll('.weight-input');
    var totalWeight = 0;
    
    weightInputs.forEach(function(input) {
        totalWeight += parseInt(input.value) || 0;
    });
    
    var progressBar = document.getElementById('weight-progress-bar');
    var totalWeightDisplay = document.getElementById('total-weight');
    var weightAlert = document.getElementById('weight-alert');
    
    if (progressBar && totalWeightDisplay && weightAlert) {
        progressBar.style.width = totalWeight + '%';
        totalWeightDisplay.textContent = totalWeight + '%';
        
        if (totalWeight < 100) {
            weightAlert.className = 'alert alert-warning';
            weightAlert.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Toplam ağırlık 100% olmalıdır. Şu anki toplam: ' + totalWeight + '%';
        } else if (totalWeight > 100) {
            weightAlert.className = 'alert alert-danger';
            weightAlert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Toplam ağırlık 100%\'ü geçmemelidir. Şu anki toplam: ' + totalWeight + '%';
        } else {
            weightAlert.className = 'alert alert-success';
            weightAlert.innerHTML = '<i class="fas fa-check-circle"></i> Toplam ağırlık 100%. İdeal!';
        }
    }
}

// Yardımcı fonksiyonlar
function updateSmartTrendUI() {
    var enabled = document.getElementById('use_smart_trend') && document.getElementById('use_smart_trend').checked;
    var smartTrendSettings = document.querySelectorAll('.smart-trend-setting');
    smartTrendSettings.forEach(function(element) {
        element.disabled = !enabled;
    });
}

function updateAdvancedRiskUI() {
    var enabled = document.getElementById('advanced_risk_enabled') && document.getElementById('advanced_risk_enabled').checked;
    var advancedRiskSettings = document.querySelectorAll('.advanced-risk-setting');
    advancedRiskSettings.forEach(function(element) {
        element.disabled = !enabled;
    });
}

function updateDynamicPositionSizingUI() {
    var enabled = document.getElementById('dynamic_position_sizing') && document.getElementById('dynamic_position_sizing').checked;
    var dynamicPositionSettings = document.querySelectorAll('.dynamic-position-setting');
    dynamicPositionSettings.forEach(function(element) {
        element.disabled = !enabled;
    });
}

function updateAdaptiveParamsUI() {
    var enabled = document.getElementById('adaptive_params_enabled') && document.getElementById('adaptive_params_enabled').checked;
    var adaptiveParamSettings = document.querySelectorAll('.adaptive-param-setting');
    adaptiveParamSettings.forEach(function(element) {
        element.disabled = !enabled;
    });
}

function updateApiOptimizationUI() {
    var enabled = document.getElementById('api_optimization_enabled') && document.getElementById('api_optimization_enabled').checked;
    var apiOptimizationSettings = document.querySelectorAll('.api-optimization-setting');
    apiOptimizationSettings.forEach(function(element) {
        element.disabled = !enabled;
    });
}

// Özelleştirilmiş range input değerleri görüntüleme
function updateRangeInputs() {
    document.querySelectorAll('input[type="range"]').forEach(function(range) {
        const valueDisplay = document.getElementById(range.id + '_value');
        if (valueDisplay) {
            valueDisplay.textContent = range.value;
            
            range.addEventListener('input', function() {
                valueDisplay.textContent = this.value;
            });
        }
    });
}

// API anahtarları için güvenlik işlevleri
function initializeApiFields() {
    // Şifrelerin görünürlüğünü değiştiren butonlar
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            var input = this.closest('.input-group').querySelector('input');
            var type = input.getAttribute('type');
            
            if (type === 'password') {
                input.setAttribute('type', 'text');
                this.querySelector('i').classList.remove('fa-eye');
                this.querySelector('i').classList.add('fa-eye-slash');
            } else {
                input.setAttribute('type', 'password');
                this.querySelector('i').classList.remove('fa-eye-slash');
                this.querySelector('i').classList.add('fa-eye');
            }
        });
    });
    
    // API anahtarı alanları için maskelenmiş değerleri temizleme
    var apiFields = document.querySelectorAll('input[name="binance_api_key"], input[name="binance_api_secret"], input[name="kucoin_api_key"], input[name="kucoin_api_secret"], input[name="kucoin_api_passphrase"], input[name="telegram_token"]');
    
    apiFields.forEach(function(field) {
        field.addEventListener('focus', function() {
            if (this.value === '••••••••••••••••••••••') {
                this.value = '';
                this.classList.add('border-warning');
                
                // 2 saniye sonra uyarı kenarını kaldır
                var self = this;
                setTimeout(function() {
                    self.classList.remove('border-warning');
                }, 2000);
            }
        });
        
        // İpucu ekle
        if (field.value === '••••••••••••••••••••••') {
            var tipElement = document.createElement('small');
            tipElement.className = 'form-text text-info mt-1';
            tipElement.innerHTML = '<i class="fas fa-info-circle"></i> Değiştirmek için tıklayın, boş bırakırsanız mevcut değer korunacaktır.';
            field.parentNode.appendChild(tipElement);
        }
    });
}

// Sayfa yüklendiğinde UI güncelleme
document.addEventListener('DOMContentLoaded', function() {
    // UI güncellemeleri - biraz gecikme ile çalıştır
    setTimeout(function() {
        if (document.getElementById('use_smart_trend')) updateSmartTrendUI();
        if (document.getElementById('advanced_risk_enabled')) updateAdvancedRiskUI();
        if (document.getElementById('adaptive_params_enabled')) updateAdaptiveParamsUI();
        if (document.getElementById('api_optimization_enabled')) updateApiOptimizationUI();
        
        // Risk yönetimi özel kontrolü
        var riskEnabled = document.getElementById('risk_enabled');
        if (riskEnabled && riskEnabled.checked) {
            var riskCard = riskEnabled.closest('.feature-card');
            if (riskCard) {
                riskCard.classList.add('enabled');
                riskCard.classList.remove('disabled');
            }
        }
        
        // Gelişmiş risk yönetimi özel kontrolü
        var advancedRiskEnabled = document.getElementById('advanced_risk_enabled');
        if (advancedRiskEnabled && advancedRiskEnabled.checked) {
            var advancedRiskCard = advancedRiskEnabled.closest('.feature-card');
            if (advancedRiskCard) {
                advancedRiskCard.classList.add('enabled');
                advancedRiskCard.classList.remove('disabled');
            }
            // Alt ayarları da aktif et
            var advancedRiskSettings = document.querySelectorAll('.advanced-risk-setting');
            advancedRiskSettings.forEach(function(element) {
                element.disabled = false;
            });
        }
    }, 100); // 100ms gecikme ile çalıştır
    
    // jQuery varsa tooltipleri etkinleştir
    if (typeof jQuery !== 'undefined') {
        $(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    }
});

// jQuery fonksiyonları için fallback
if (typeof jQuery !== 'undefined') {
    $(document).ready(function() {
        // Bootstrap tooltip'leri etkinleştir
        $('[data-toggle="tooltip"]').tooltip();
        
        // Range input değerlerini güncelle
        updateRangeInputs();
        
        // Ağırlıkları güncelle
        updateWeightTotal();
    });
} else {
    console.warn("jQuery yüklenmedi! Temel tab işlevselliği için kendi fonksiyonlarımızı kullanıyoruz.");
}
