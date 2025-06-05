/**
 * Theme Settings JavaScript
 * Tema ayarları sayfası için dinamik işlevsellik
 */

// Sayfa yüklendiğinde çalışacak işlevler
document.addEventListener('DOMContentLoaded', function() {
    initializeThemeSettings();
    setupEventListeners();
    updatePreviewArea();
});

/**
 * Tema ayarlarını başlat
 */
function initializeThemeSettings() {
    // Color input ve text input senkronizasyonu
    syncColorInputs();
    
    // Range input değerlerini göster
    updateRangeValues();
    
    // Tema modunu kontrol et
    checkThemeMode();
}

/**
 * Event listener'ları kur
 */
function setupEventListeners() {
    // Color input değişiklikleri
    document.querySelectorAll('input[type="color"]').forEach(input => {
        input.addEventListener('change', function() {
            const textInput = this.nextElementSibling;
            if (textInput && textInput.type === 'text') {
                textInput.value = this.value;
            }
            updatePreview(this.name, this.value);
        });
    });
    
    // Text input değişiklikleri (hex kodları için)
    document.querySelectorAll('input[type="text"][value^="#"]').forEach(input => {
        input.addEventListener('change', function() {
            const colorInput = this.previousElementSibling;
            if (colorInput && colorInput.type === 'color') {
                colorInput.value = this.value;
            }
            updatePreview(this.name, this.value);
        });
    });
    
    // Range input değişiklikleri
    document.querySelectorAll('input[type="range"]').forEach(input => {
        input.addEventListener('input', function() {
            updateRangeDisplay(this);
        });
    });
    
    // Select değişiklikleri
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', function() {
            updatePreview(this.name, this.value);
        });
    });
    
    // Checkbox değişiklikleri
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updatePreview(this.name, this.checked);
        });
    });
}

/**
 * Color input ve text input senkronizasyonu
 */
function syncColorInputs() {
    document.querySelectorAll('input[type="color"]').forEach(colorInput => {
        const textInput = colorInput.nextElementSibling;
        if (textInput && textInput.type === 'text') {
            // Text input'tan color input'a sync
            textInput.addEventListener('input', function() {
                if (this.value.match(/^#[0-9A-F]{6}$/i)) {
                    colorInput.value = this.value;
                }
            });
        }
    });
}

/**
 * Range input değerlerini güncelle
 */
function updateRangeValues() {
    document.querySelectorAll('input[type="range"]').forEach(range => {
        updateRangeDisplay(range);
    });
}

/**
 * Range input görüntüsünü güncelle
 */
function updateRangeDisplay(rangeInput) {
    const value = rangeInput.value;
    const name = rangeInput.name;
    
    if (name === 'sidebar_width_range') {
        const textInput = document.querySelector('input[name="sidebar_width"]');
        if (textInput) {
            textInput.value = value + 'px';
        }
        updatePreview('sidebar_width', value + 'px');
    } else if (name === 'navbar_height_range') {
        const textInput = document.querySelector('input[name="navbar_height"]');
        if (textInput) {
            textInput.value = value + 'px';
        }
        updatePreview('navbar_height', value + 'px');
    }
}

/**
 * Önizleme alanını güncelle
 */
function updatePreview(property, value) {
    const previewArea = document.getElementById('previewArea');
    if (!previewArea) return;
    
    switch (property) {
        case 'primary_color':
            updateElementStyle('.preview-btn', 'background-color', value);
            break;
            
        case 'navbar_bg':
            updateElementStyle('.preview-navbar', 'background-color', value);
            break;
            
        case 'sidebar_bg':
            updateElementStyle('.preview-sidebar', 'background-color', value);
            break;
            
        case 'body_bg':
            updateElementStyle('.preview-content', 'background-color', value);
            break;
            
        case 'card_bg':
            updateElementStyle('.preview-card', 'background-color', value);
            break;
            
        case 'border_radius':
            updateElementStyle('.preview-card', 'border-radius', value);
            updateElementStyle('.preview-btn', 'border-radius', value);
            break;
            
        case 'sidebar_width':
            updateElementStyle('.preview-sidebar', 'width', value);
            break;
            
        case 'theme_mode':
            toggleThemeMode(value);
            break;
    }
}

/**
 * Element stilini güncelle
 */
function updateElementStyle(selector, property, value) {
    const elements = document.querySelectorAll(selector);
    elements.forEach(el => {
        el.style[property] = value;
    });
}

/**
 * Tüm önizleme alanını güncelle
 */
function updatePreviewArea() {
    // Form değerlerini al ve önizlemeyi güncelle
    const form = document.getElementById('themeSettingsForm');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.forEach((value, key) => {
        updatePreview(key, value);
    });
}

/**
 * Sidebar genişliğini güncelle
 */
function updateSidebarWidth(value) {
    const textInput = document.querySelector('input[name="sidebar_width"]');
    if (textInput) {
        textInput.value = value + 'px';
    }
    updatePreview('sidebar_width', value + 'px');
}

/**
 * Navbar yüksekliğini güncelle
 */
function updateNavbarHeight(value) {
    const textInput = document.querySelector('input[name="navbar_height"]');
    if (textInput) {
        textInput.value = value + 'px';
    }
    updatePreview('navbar_height', value + 'px');
}

/**
 * Tema modunu değiştir
 */
function toggleThemeMode(mode) {
    const body = document.body;
    const previewArea = document.getElementById('previewArea');
    
    if (mode === 'dark') {
        body.classList.add('dark-mode');
        if (previewArea) {
            previewArea.style.backgroundColor = '#1a1a1a';
            previewArea.style.color = '#ffffff';
        }
    } else {
        body.classList.remove('dark-mode');
        if (previewArea) {
            previewArea.style.backgroundColor = '#ffffff';
            previewArea.style.color = '#212529';
        }
    }
}

/**
 * Tema modunu kontrol et
 */
function checkThemeMode() {
    const themeModeSelect = document.querySelector('select[name="theme_mode"]');
    if (themeModeSelect && themeModeSelect.value === 'auto') {
        // Sistem tema tercihini kontrol et
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            toggleThemeMode('dark');
        } else {
            toggleThemeMode('light');
        }
    }
}

/**
 * Değişiklikleri önizle
 */
function previewChanges() {
    updatePreviewArea();
    
    // Bildirim göster
    showNotification('Önizleme güncellendi!', 'info');
}

/**
 * Varsayılan ayarlara sıfırla
 */
function resetToDefaults() {
    if (confirm('Tüm ayarları varsayılan değerlere sıfırlamak istediğinizden emin misiniz?')) {
        // Form submit et
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="reset_settings">';
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Tema şablonu uygula
 */
function applyTemplate(templateName) {
    if (confirm(`${templateName} temasını uygulamak istediğinizden emin misiniz?`)) {
        const form = document.getElementById('templateForm');
        if (form) {
            // Template input'u ekle
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'template';
            input.value = templateName;
            form.appendChild(input);
            
            form.submit();
        }
    }
}

/**
 * Bildirim göster
 */
function showNotification(message, type = 'success') {
    // Bootstrap alert oluştur
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // 3 saniye sonra otomatik kaldır
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

/**
 * CSS export işlevi
 */
function exportThemeSettings() {
    const form = document.getElementById('themeSettingsForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const settings = {};
    
    formData.forEach((value, key) => {
        if (key !== 'action') {
            settings[key] = value;
        }
    });
    
    // JSON olarak indir
    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = 'theme-settings.json';
    link.click();
    
    showNotification('Tema ayarları başarıyla dışa aktarıldı!', 'success');
}

/**
 * CSS import işlevi
 */
function importThemeSettings() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                
                // Form alanlarını güncelle
                Object.keys(settings).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = settings[key];
                        } else {
                            input.value = settings[key];
                        }
                    }
                });
                
                updatePreviewArea();
                showNotification('Tema ayarları başarıyla içe aktarıldı!', 'success');
                
            } catch (error) {
                showNotification('Geçersiz dosya formatı!', 'error');
            }
        };
        
        reader.readAsText(file);
    };
    
    input.click();
}

// Global fonksiyonları window'a ekle
window.previewChanges = previewChanges;
window.resetToDefaults = resetToDefaults;
window.applyTemplate = applyTemplate;
window.updateSidebarWidth = updateSidebarWidth;
window.updateNavbarHeight = updateNavbarHeight;
window.updatePreview = updatePreview;
window.exportThemeSettings = exportThemeSettings;
window.importThemeSettings = importThemeSettings;