<?php
// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantısı
$db_host = "localhost";
$db_user = "root";
$db_pass = "Efsane44.";
$db_name = "trading_bot_db";

// MySQLi bağlantısı oluşturma
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

/**
 * Bot settings tablosundan Binance API bilgilerini çeker
 * @param mysqli $db_conn Veritabanı bağlantısı
 * @return array|false API bilgileri veya hata durumunda false
 */
function getBinanceApiCredentials($db_conn) {
    $api_info = [
        'api_key' => null,
        'secret' => null,
        'status' => false,
        'message' => '',
        'source' => ''
    ];
    
    // bot_settings tablosundan API bilgilerini çek
    $sql = "SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1";
    $result = $db_conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        $api_info['message'] = "Bot ayarları bulunamadı.";
        return $api_info;
    }
    
    $row = $result->fetch_assoc();
    
    // Bot Settings içeriğini kontrol et
    if (!empty($row['settings'])) {
        $settings = json_decode($row['settings'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // İlk olarak api_keys yapısını kontrol et (verilerinizde bu yapı var)
            if (isset($settings['api_keys'])) {
                // API Key kontrolü
                if (isset($settings['api_keys']['binance_api_key']) && !empty($settings['api_keys']['binance_api_key'])) {
                    $api_info['api_key'] = $settings['api_keys']['binance_api_key'];
                    $api_info['source'] = 'bot_settings tablosu (settings.api_keys)';
                }
                
                // Secret kontrolü
                if (isset($settings['api_keys']['binance_api_secret']) && !empty($settings['api_keys']['binance_api_secret'])) {
                    $api_info['secret'] = $settings['api_keys']['binance_api_secret'];
                    if (empty($api_info['source'])) $api_info['source'] = 'bot_settings tablosu (settings.api_keys)';
                }
            }
            
            // Eğer api_keys içinde bulamazsak, api->binance yapısını kontrol et
            if (($api_info['api_key'] === null || $api_info['secret'] === null) && 
                isset($settings['api']) && isset($settings['api']['binance'])) {
                
                $binance = $settings['api']['binance'];
                
                // API Key kontrolü
                if ($api_info['api_key'] === null && isset($binance['api_key']) && !empty($binance['api_key'])) {
                    $api_info['api_key'] = $binance['api_key'];
                    $api_info['source'] = 'bot_settings tablosu (settings.api.binance)';
                }
                
                // Secret kontrolü - hem secret hem api_secret alanlarına bakalım
                if ($api_info['secret'] === null) {
                    if (isset($binance['secret']) && !empty($binance['secret'])) {
                        $api_info['secret'] = $binance['secret'];
                        if (empty($api_info['source'])) $api_info['source'] = 'bot_settings tablosu (settings.api.binance)';
                    } elseif (isset($binance['api_secret']) && !empty($binance['api_secret'])) {
                        $api_info['secret'] = $binance['api_secret'];
                        if (empty($api_info['source'])) $api_info['source'] = 'bot_settings tablosu (settings.api.binance)';
                    }
                }
            }
        }
    }
    
    // settings_json alanını kontrol et
    if (($api_info['api_key'] === null || $api_info['secret'] === null) && 
        !empty($row['settings_json'])) {
        $settings_json = json_decode($row['settings_json'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // İlk olarak api_keys yapısını kontrol et
            if (isset($settings_json['api_keys'])) {
                // API Key kontrolü
                if ($api_info['api_key'] === null && 
                    isset($settings_json['api_keys']['binance_api_key']) && 
                    !empty($settings_json['api_keys']['binance_api_key'])) {
                    $api_info['api_key'] = $settings_json['api_keys']['binance_api_key'];
                    $api_info['source'] = 'bot_settings tablosu (settings_json.api_keys)';
                }
                
                // Secret kontrolü
                if ($api_info['secret'] === null && 
                    isset($settings_json['api_keys']['binance_api_secret']) && 
                    !empty($settings_json['api_keys']['binance_api_secret'])) {
                    $api_info['secret'] = $settings_json['api_keys']['binance_api_secret'];
                    if (empty($api_info['source'])) $api_info['source'] = 'bot_settings tablosu (settings_json.api_keys)';
                }
            }
            
            // Eğer api_keys içinde bulamazsak, api->binance yapısını kontrol et
            if (($api_info['api_key'] === null || $api_info['secret'] === null) && 
                isset($settings_json['api']) && 
                isset($settings_json['api']['binance'])) {
                $binance = $settings_json['api']['binance'];
                
                // API Key kontrolü
                if ($api_info['api_key'] === null && isset($binance['api_key']) && !empty($binance['api_key'])) {
                    $api_info['api_key'] = $binance['api_key'];
                    $api_info['source'] = 'bot_settings tablosu (settings_json.api.binance)';
                }
                
                // Secret kontrolü - hem secret hem api_secret alanlarına bakalım
                if ($api_info['secret'] === null) {
                    if (isset($binance['secret']) && !empty($binance['secret'])) {
                        $api_info['secret'] = $binance['secret'];
                        if (empty($api_info['source'])) $api_info['source'] = 'bot_settings tablosu (settings_json.api.binance)';
                    } elseif (isset($binance['api_secret']) && !empty($binance['api_secret'])) {
                        $api_info['secret'] = $binance['api_secret'];
                        if (empty($api_info['source'])) $api_info['source'] = 'bot_settings tablosu (settings_json.api.binance)';
                    }
                }
            }
        }
    }
    
    // API bilgilerinin tam olup olmadığını kontrol et
    if ($api_info['api_key'] !== null && $api_info['secret'] !== null) {
        $api_info['status'] = true;
        $api_info['message'] = "API bilgileri başarıyla alındı.";
    } else {
        $api_info['message'] = "API bilgileri eksik veya hatalı format.";
    }
    
    return $api_info;
}

/**
 * Basitleştirilmiş API bilgileri alma fonksiyonu - diğer sayfalarda kullanmak için
 * Bu fonksiyon otomatik olarak veritabanı bağlantısı kurar ve API bilgilerini getirir
 * @return array API bilgileri ve başarı durumu
 */
function getBinanceApiKeys() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    // Veritabanı bağlantısı oluştur
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        return [
            'success' => false, 
            'message' => "Veritabanı bağlantısı başarısız: " . $conn->connect_error
        ];
    }
    
    // API bilgilerini al
    $api_credentials = getBinanceApiCredentials($conn);
    $conn->close();
    
    if ($api_credentials['status']) {
        return [
            'success' => true,
            'api_key' => $api_credentials['api_key'],
            'api_secret' => $api_credentials['secret'],
            'source' => $api_credentials['source']
        ];
    } else {
        return [
            'success' => false,
            'message' => $api_credentials['message']
        ];
    }
}

/**
 * Ana sayfada bakiyeleri görüntülemek için kullanılan fonksiyon
 * @return string Bakiye HTML içeriği
 */
function getBinanceBalancesForIndex() {
    $html = '';
    $api_keys = getBinanceApiKeys();
    
    if (!$api_keys['success']) {
        return "<div class='alert alert-danger'>API bilgileri alınamadı: " . $api_keys['message'] . "</div>";
    }
    
    try {
        // Statik JSON dosyasını kontrol et (Python scriptinin kaydettiği)
        $json_file = __DIR__ . '/../api/binance_total_balances.json';
        
        // Önce statik dosyadan okuma yapmayı dene
        if (file_exists($json_file) && time() - filemtime($json_file) < 3600) { // 1 saat içinde güncellenmişse
            $static_data = @file_get_contents($json_file);
            if ($static_data) {
                $balances_data = json_decode($static_data, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($balances_data['timestamp'])) {
                    // Statik dosyadan başarıyla okundu, özet tablo göster
                    return renderBalanceSummary($balances_data);
                }
            }
        }
        
        // Statik dosya yoksa veya güncel değilse, API'den çek
        $spot_balances = getSpotBalancesFromAPI($api_keys);
        
        if (!$spot_balances['success']) {
            return "<div class='alert alert-warning'>" . $spot_balances['message'] . "</div>";
        }
        
        return renderBalanceTable($spot_balances['balances']);
        
    } catch (Exception $e) {
        return "<div class='alert alert-danger'>Bakiyeler yüklenirken hata oluştu: " . $e->getMessage() . "</div>";
    }
}

/**
 * API'den spot bakiyeleri çeker
 * @param array $api_keys API anahtarları
 * @return array Sonuç ve bakiyeler
 */
function getSpotBalancesFromAPI($api_keys) {
    // Binance API ile bakiye sorgulama
    $timestamp = time() * 1000;
    $params = "timestamp=" . $timestamp;
    $signature = hash_hmac('sha256', $params, $api_keys['api_secret']);
    
    $ch = curl_init();
    
    // API isteği ayarları
    curl_setopt($ch, CURLOPT_URL, "https://api.binance.com/api/v3/account?" . $params . "&signature=" . $signature);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-MBX-APIKEY: " . $api_keys['api_key']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Zaman aşımını artırdık
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Trading Bot)');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // CURL hata kontrolü
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'API bağlantı hatası: ' . $error_msg
        ];
    }
    
    curl_close($ch);
    
    // HTTP hata kodu kontrolü
    if ($http_code != 200) {
        return [
            'success' => false,
            'message' => 'API yanıt kodu: ' . $http_code . '. Bu sunucu Binance API\'ye bağlanamıyor olabilir.'
        ];
    }
    
    // JSON parse
    $result = json_decode($response, true);
    
    // Parse hata kontrolü
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'API yanıtını ayrıştırma hatası: ' . json_last_error_msg()
        ];
    }
    
    // API hata kontrolü
    if (isset($result['code']) && isset($result['msg'])) {
        return [
            'success' => false,
            'message' => 'Binance API Hatası: ' . $result['msg'] . ' (Kod: ' . $result['code'] . ')'
        ];
    }
    
    // Bakiyelerin varlığını kontrol et
    if (!isset($result['balances']) || !is_array($result['balances'])) {
        return [
            'success' => false,
            'message' => 'API yanıtında bakiye bilgisi bulunamadı'
        ];
    }
    
    // Bakiyeleri topla
    $balances = [];
    
    foreach ($result['balances'] as $balance) {
        $free = floatval($balance['free']);
        $locked = floatval($balance['locked']);
        $total = $free + $locked;
        
        if ($total > 0) {
            $balances[] = [
                'asset' => $balance['asset'],
                'free' => $free,
                'locked' => $locked,
                'total' => $total
            ];
        }
    }
    
    // Toplam değere göre büyükten küçüğe sıralama
    usort($balances, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    
    return [
        'success' => true,
        'balances' => $balances,
        'count' => count($balances),
        'timestamp' => time()
    ];
}

/**
 * Bakiye tablosunu render eder
 * @param array $balances Bakiyeler
 * @return string HTML içerik
 */
function renderBalanceTable($balances) {
    if (empty($balances)) {
        return "<div class='alert alert-info'>Bakiye bulunamadı.</div>";
    }
    
    $html = "<div class='card mb-4'>";
    $html .= "<div class='card-header d-flex justify-content-between align-items-center'>";
    $html .= "<h5 class='mb-0'><i class='fas fa-wallet'></i> Bakiyeler</h5>";
    $html .= "<a href='binance_balances.php' class='btn btn-sm btn-primary'>Tümünü Gör</a>";
    $html .= "</div>";
    $html .= "<div class='card-body'>";
    
    $html .= "<div class='table-responsive'>";
    $html .= "<table class='table table-bordered table-hover'>";
    $html .= "<thead class='table-light'>";
    $html .= "<tr><th>Coin</th><th>Toplam</th></tr>";
    $html .= "</thead><tbody>";
    
    // İlk 5 bakiyeyi göster
    $show_count = min(count($balances), 5);
    
    for ($i = 0; $i < $show_count; $i++) {
        $balance = $balances[$i];
        $html .= "<tr>";
        $html .= "<td><strong>{$balance['asset']}</strong></td>";
        $html .= "<td>" . number_format($balance['total'], 8, '.', ',') . "</td>";
        $html .= "</tr>";
    }
    
    $html .= "</tbody></table>";
    $html .= "</div>";
    
    $html .= "<div class='text-muted small mt-2'>Son güncelleme: " . date("d.m.Y H:i:s") . " - Toplam " . count($balances) . " farklı coin</div>";
    $html .= "</div>"; // card-body
    $html .= "</div>"; // card
    
    return $html;
}

/**
 * Özet bakiye bilgisini render eder
 * @param array $balances_data Bakiye özeti
 * @return string HTML içerik
 */
function renderBalanceSummary($balances_data) {
    $html = "<div class='card mb-4'>";
    $html .= "<div class='card-header d-flex justify-content-between align-items-center'>";
    $html .= "<h5 class='mb-0'><i class='fas fa-wallet'></i> Bakiyeler</h5>";
    $html .= "<a href='binance_balances.php' class='btn btn-sm btn-primary'>Tümünü Gör</a>";
    $html .= "</div>";
    $html .= "<div class='card-body'>";
    
    $html .= "<div class='row'>";
    
    // Spot bakiye
    $html .= "<div class='col-md-6 mb-3'>";
    $html .= "<div class='card bg-light'>";
    $html .= "<div class='card-body py-2 px-3'>";
    $html .= "<h6 class='card-title mb-1'>Spot Toplam</h6>";
    $html .= "<p class='card-text font-weight-bold'>" . number_format($balances_data['total_spot'] ?? 0, 2) . " USDT</p>";
    $html .= "</div></div></div>";
    
    // Margin bakiye
    $html .= "<div class='col-md-6 mb-3'>";
    $html .= "<div class='card bg-light'>";
    $html .= "<div class='card-body py-2 px-3'>";
    $html .= "<h6 class='card-title mb-1'>Margin Toplam</h6>";
    $html .= "<p class='card-text font-weight-bold'>" . number_format(($balances_data['total_margin'] ?? 0) + ($balances_data['total_isolated'] ?? 0), 2) . " USDT</p>";
    $html .= "</div></div></div>";
    
    // Futures bakiye
    $html .= "<div class='col-md-6 mb-3'>";
    $html .= "<div class='card bg-light'>";
    $html .= "<div class='card-body py-2 px-3'>";
    $html .= "<h6 class='card-title mb-1'>Futures Toplam</h6>";
    $html .= "<p class='card-text font-weight-bold'>" . number_format($balances_data['total_futures'] ?? 0, 2) . " USDT</p>";
    $html .= "</div></div></div>";
    
    // Toplam değer
    $html .= "<div class='col-md-6 mb-3'>";
    $html .= "<div class='card bg-success text-white'>";
    $html .= "<div class='card-body py-2 px-3'>";
    $html .= "<h6 class='card-title mb-1'>Toplam Değer</h6>";
    $grand_total = ($balances_data['total_spot'] ?? 0) + 
                  ($balances_data['total_margin'] ?? 0) + 
                  ($balances_data['total_isolated'] ?? 0) + 
                  ($balances_data['total_futures'] ?? 0);
    $html .= "<p class='card-text font-weight-bold'>" . number_format($grand_total, 2) . " USDT</p>";
    $html .= "</div></div></div>";
    
    $html .= "</div>"; // row
    
    // Güncelleme bilgisi
    if (isset($balances_data['timestamp'])) {
        $update_time = date("d.m.Y H:i:s", $balances_data['timestamp']);
        $html .= "<div class='text-muted small mt-2'>Son güncelleme: " . $update_time . "</div>";
    } else {
        $html .= "<div class='text-muted small mt-2'>Son güncelleme: Bilinmiyor</div>";
    }
    
    $html .= "</div>"; // card-body
    $html .= "</div>"; // card
    
    return $html;
}

// Eğer bu dosya doğrudan çağrıldıysa (include ile değil)
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    echo "<h1>Bot Settings Tablosu Detaylı İnceleme</h1>";

    // Tablo yapısını kontrol et
    $structure_sql = "DESCRIBE bot_settings";
    $structure_result = $conn->query($structure_sql);

    if (!$structure_result) {
        echo "<p style='color:red'>❌ Tablo yapısı sorgusu başarısız: " . $conn->error . "</p>";
        exit;
    }

    echo "<h2>Tablo Yapısı (bot_settings)</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Alan</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    while ($field = $structure_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$field['Field']}</td>";
        echo "<td>{$field['Type']}</td>";
        echo "<td>{$field['Null']}</td>";
        echo "<td>{$field['Key']}</td>";
        echo "<td>{$field['Default']}</td>";
        echo "<td>{$field['Extra']}</td>";
        echo "</tr>";
    }

    echo "</table>";
    
    // Tablo içeriğini kontrol et
    $content_sql = "SELECT * FROM bot_settings ORDER BY id DESC";
    $content_result = $conn->query($content_sql);

    if (!$content_result) {
        echo "<p style='color:red'>❌ Tablo içeriği sorgusu başarısız: " . $conn->error . "</p>";
    } else {
        echo "<h2>Tablo İçeriği ({$content_result->num_rows} kayıt)</h2>";

        if ($content_result->num_rows > 0) {
            while ($row = $content_result->fetch_assoc()) {
                echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ddd;'>";
                echo "<h3>Kayıt ID: {$row['id']}</h3>";
                
                // Tüm alanları göster
                foreach ($row as $key => $value) {
                    if ($key !== 'settings' && $key !== 'settings_json') {
                        echo "<p><strong>{$key}:</strong> {$value}</p>";
                    }
                }
                
                // Settings alanını JSON olarak çözümle ve göster
                if (isset($row['settings']) && !empty($row['settings'])) {
                    echo "<h4>Settings İçeriği:</h4>";
                    $settings = json_decode($row['settings'], true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        echo "<pre style='max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
                        print_r($settings);
                        echo "</pre>";
                        
                        // API bilgilerini kontrol et
                        echo "<h4>API Bilgileri:</h4>";
                        if (isset($settings['api']) && isset($settings['api']['binance'])) {
                            $binance_api = $settings['api']['binance'];
                            echo "<p style='color:green'>✅ Binance API bilgileri bulundu!</p>";
                            
                            // API Key kontrolü
                            if (isset($binance_api['api_key'])) {
                                $api_key = $binance_api['api_key'];
                                $masked_key = substr($api_key, 0, 5) . "..." . substr($api_key, -5);
                                echo "<p><strong>API Key:</strong> {$masked_key}</p>";
                            } else {
                                echo "<p style='color:orange'>⚠️ API Key bulunamadı!</p>";
                            }
                            
                            // API Secret kontrolü
                            if (isset($binance_api['secret'])) {
                                $api_secret = $binance_api['secret'];
                                $masked_secret = substr($api_secret, 0, 5) . "..." . substr($api_secret, -5);
                                echo "<p><strong>API Secret:</strong> {$masked_secret}</p>";
                            } elseif (isset($binance_api['api_secret'])) {
                                $api_secret = $binance_api['api_secret'];
                                $masked_secret = substr($api_secret, 0, 5) . "..." . substr($api_secret, -5);
                                echo "<p><strong>API Secret:</strong> {$masked_secret} (api_secret alanında)</p>";
                            } else {
                                echo "<p style='color:orange'>⚠️ API Secret bulunamadı!</p>";
                            }
                        } else {
                            echo "<p style='color:orange'>⚠️ Settings içinde API bilgileri bulunamadı!</p>";
                        }
                    } else {
                        echo "<p style='color:red'>❌ Settings JSON çözümlenemedi: " . json_last_error_msg() . "</p>";
                        echo "<div style='max-height: 200px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
                        echo htmlspecialchars($row['settings']);
                        echo "</div>";
                    }
                } else {
                    echo "<p style='color:orange'>⚠️ Settings alanı boş!</p>";
                }
                
                // settings_json alanını kontrol et
                if (isset($row['settings_json']) && !empty($row['settings_json'])) {
                    $settings_json = json_decode($row['settings_json'], true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        echo "<h4>Settings JSON İçeriği:</h4>";
                        echo "<pre style='max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
                        print_r($settings_json);
                        echo "</pre>";
                    } else {
                        echo "<p style='color:red'>❌ Settings JSON çözümlenemedi: " . json_last_error_msg() . "</p>";
                        echo "<div style='max-height: 200px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
                        echo htmlspecialchars($row['settings_json']);
                        echo "</div>";
                    }
                }
                
                echo "</div>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ Tabloda hiç kayıt bulunamadı!</p>";
        }
    }

    echo "<h2>API Bilgileri Test</h2>";
    
    $api_credentials = getBinanceApiCredentials($conn);
    
    echo "<div style='padding: 10px; border: 1px solid #ddd; margin-top: 20px;'>";
    echo "<h3>getBinanceApiCredentials() Sonucu:</h3>";
    
    if ($api_credentials['status']) {
        echo "<p style='color:green'>✅ API bilgileri başarıyla alındı!</p>";
        echo "<p><strong>Kaynak:</strong> " . ($api_credentials['source'] ?? 'Belirtilmemiş') . "</p>";
        
        $masked_key = substr($api_credentials['api_key'], 0, 5) . "..." . substr($api_credentials['api_key'], -5);
        $masked_secret = substr($api_credentials['secret'], 0, 5) . "..." . substr($api_credentials['secret'], -5);
        
        echo "<p><strong>API Key:</strong> {$masked_key}</p>";
        echo "<p><strong>Secret:</strong> {$masked_secret}</p>";
        
        // Binance API test kodu örneği
        echo "<h3>Binance API Kullanım Örneği:</h3>";
        echo "<p>Index.php veya diğer sayfalarda aşağıdaki kodu kullanarak bakiyeleri getirebilirsiniz:</p>";
        
        echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd;'>";
        echo "&lt;?php
// API bilgilerini al - otomatik veritabanı bağlantısı ile
require_once \"check_bot_settings_structure.php\";

// Basitleştirilmiş fonksiyon kullanımı
\$api_keys = getBinanceApiKeys();

if (\$api_keys['success']) {
    // API bilgileri başarıyla alındı
    \$api_key = \$api_keys['api_key'];
    \$api_secret = \$api_keys['api_secret'];
    
    // Binance API ile bakiye sorgulama
    \$timestamp = time() * 1000;
    \$params = \"timestamp=\" . \$timestamp;
    \$signature = hash_hmac('sha256', \$params, \$api_secret);
    
    \$ch = curl_init();
    curl_setopt(\$ch, CURLOPT_URL, \"https://api.binance.com/api/v3/account?\" . \$params . \"&signature=\" . \$signature);
    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt(\$ch, CURLOPT_HTTPHEADER, [\"X-MBX-APIKEY: \" . \$api_key]);
    
    \$result = curl_exec(\$ch);
    curl_close(\$ch);
    
    \$result_array = json_decode(\$result, true);
    
    if (isset(\$result_array['code']) && isset(\$result_array['msg'])) {
        echo \"API Hatası: \" . \$result_array['msg'];
    } else {
        // Bakiyeleri göster
        echo \"&lt;h2&gt;Bakiyeler&lt;/h2&gt;\";
        echo \"&lt;table border='1' cellpadding='5'&gt;\";
        echo \"&lt;tr&gt;&lt;th&gt;Coin&lt;/th&gt;&lt;th&gt;Miktar&lt;/th&gt;&lt;/tr&gt;\";
        
        foreach (\$result_array['balances'] as \$balance) {
            if (floatval(\$balance['free']) > 0 || floatval(\$balance['locked']) > 0) {
                echo \"&lt;tr&gt;\";
                echo \"&lt;td&gt;{\$balance['asset']}&lt;/td&gt;\";
                echo \"&lt;td&gt;\" . (floatval(\$balance['free']) + floatval(\$balance['locked'])) . \"&lt;/td&gt;\";
                echo \"&lt;/tr&gt;\";
            }
        }
        
        echo \"&lt;/table&gt;\";
    }
} else {
    echo \"API bilgileri alınamadı: \" . \$api_keys['message'];
}
?&gt;";
        echo "</pre>";
        
    } else {
        echo "<p style='color:red'>❌ API bilgileri alınamadı: " . $api_credentials['message'] . "</p>";
        echo "<p>Bot settings tablosundaki ayarlarda API bilgilerinin olup olmadığını kontrol edin.</p>";
        echo "<p>API bilgileriniz şu yapılarda olabilir:</p>";
        echo "<pre>
// 1. Düzen (sizin mevcut yapınız):
{
    \"api_keys\": {
        \"binance_api_key\": \"YOUR_API_KEY\",
        \"binance_api_secret\": \"YOUR_API_SECRET\"
    }
}

// 2. Düzen:
{
    \"api\": {
        \"binance\": {
            \"api_key\": \"YOUR_API_KEY\",
            \"secret\": \"YOUR_API_SECRET\"
        }
    }
}
        </pre>";
    }
    echo "</div>";
    
    // API anahtarlarını test için script örneği
    echo "<h2>API Test Kodu Örneği</h2>";
    echo "<p>Aşağıdaki kodu index.php veya api_test.php dosyanızda deneyebilirsiniz:</p>";
    
    echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd;'>";
    echo "&lt;?php
// API bilgilerini al
require_once \"check_bot_settings_structure.php\";

// Veritabanı bağlantısı
\$conn = new mysqli(\"localhost\", \"root\", \"Efsane44.\", \"trading_bot_db\");

// API bilgilerini al
\$api_credentials = getBinanceApiCredentials(\$conn);

if (\$api_credentials['status']) {
    // API bilgileri başarıyla alındı
    \$api_key = \$api_credentials['api_key'];
    \$api_secret = \$api_credentials['secret'];
    
    echo \"API bilgileri alındı: \" . substr(\$api_key, 0, 5) . \"...\";
    
    // Binance API işlemleri burada yapılabilir
} else {
    echo \"API bilgileri alınamadı: \" . \$api_credentials['message'];
}
?&gt;";
    echo "</pre>";
}

// İstenmeyen bağlantıları kaldırmak için, doğrudan çağrıldığında bile çıktı vermiyoruz
if (basename($_SERVER['SCRIPT_FILENAME']) != basename(__FILE__)) {
    // Bu dosya include edildiğinde hiçbir şey yazdırma
    return;
}
?>

<p><a href="api_test.php">API Tester Sayfasına Dön</a></p>
<p><a href="index.php">Ana Sayfaya Dön</a></p>
