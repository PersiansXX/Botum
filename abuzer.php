<?php
// Veritabanı bağlantısı
$db = new mysqli('localhost', 'root', 'Efsane44.', 'trading_bot_db');  // veritabanı adınızı girin

if ($db->connect_error) {
    die("Veritabanı bağlantı hatası: " . $db->connect_error);
}

echo "MySQL Bağlantısı Başarılı!\n\n";

// Mevcut tabloları göster
$tables_query = "SHOW TABLES";
$tables_result = $db->query($tables_query);

if ($tables_result) {
    echo "Veritabanındaki Tablolar:\n";
    echo "------------------------\n";
    while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
        echo "- " . $table[0] . "\n";
    }
    echo "\n";
} else {
    echo "Tablo sorgusu başarısız: " . $db->error . "\n";
}

// Bot ayarları tablosunu bul ve göster
$settings_table_found = false;
$possible_tables = ['bot_settings', 'settings', 'trading_bot_settings', 'config'];
$settings_table = '';

$tables_result = $db->query($tables_query);
if ($tables_result) {
    while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
        if (in_array($table[0], $possible_tables)) {
            $settings_table = $table[0];
            $settings_table_found = true;
            echo "Ayarlar tablosu bulundu: " . $settings_table . "\n\n";
            break;
        }
    }
}

if (!$settings_table_found) {
    echo "Ayarlar tablosu bulunamadı! Lütfen doğru tablo adını girin:\n";
    // Bir liste sunalım
    $tables_result = $db->query($tables_query);
    if ($tables_result) {
        $i = 1;
        $table_options = [];
        while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
            echo $i . ") " . $table[0] . "\n";
            $table_options[$i] = $table[0];
            $i++;
        }
        echo "Seçiminiz (1-" . ($i-1) . "): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $settings_table = $table_options[(int)$line] ?? '';
        fclose($handle);
        
        if (!$settings_table) {
            die("Geçerli bir tablo seçilmedi. İşlem sonlandırılıyor.\n");
        }
    }
}

// Tablo yapısını göster
echo "Tablo Yapısı (" . $settings_table . "):\n";
echo "------------------------\n";
$structure_query = "DESCRIBE " . $settings_table;
$structure_result = $db->query($structure_query);

if ($structure_result) {
    while ($column = $structure_result->fetch_assoc()) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    echo "\n";
} else {
    echo "Tablo yapısı sorgusu başarısız: " . $db->error . "\n";
}

// Ayarları bul
$settings_query = "SELECT * FROM " . $settings_table . " LIMIT 1";
$settings_result = $db->query($settings_query);

if (!$settings_result) {
    die("Ayarlar sorgusu başarısız: " . $db->error);
}

if ($settings_result->num_rows === 0) {
    die("Hiç ayar bulunamadı!");
}

$settings_row = $settings_result->fetch_assoc();

// JSON alanını bulmaya çalış
$settings_column = null;
$possible_columns = ['settings', 'config', 'configuration', 'data', 'json_data', 'options'];

foreach ($settings_row as $column => $value) {
    // İlk JSON olma ihtimali olan sütunu bul
    if (isset($value[0]) && ($value[0] === '{' || $value[0] === '[')) {
        $settings_column = $column;
        break;
    }
    
    // Eğer adı olası bir ayar sütunu ise
    if (in_array(strtolower($column), $possible_columns)) {
        $settings_column = $column;
    }
}

if (!$settings_column) {
    echo "Ayarları içeren JSON sütunu tespit edilemedi. Lütfen sütunu seçin:\n";
    $i = 1;
    $column_options = [];
    foreach ($settings_row as $column => $value) {
        echo $i . ") " . $column . "\n";
        $column_options[$i] = $column;
        $i++;
    }
    echo "Seçiminiz (1-" . ($i-1) . "): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $settings_column = $column_options[(int)$line] ?? '';
    fclose($handle);
    
    if (!$settings_column) {
        die("Geçerli bir sütun seçilmedi. İşlem sonlandırılıyor.\n");
    }
}

echo "Ayarlar sütunu: " . $settings_column . "\n\n";

// JSON ayarlarını çözümle
$settings_json = $settings_row[$settings_column];
$settings = json_decode($settings_json, true);

if ($settings === null) {
    die("Ayarlar JSON olarak çözümlenemedi: " . json_last_error_msg());
}

// Beklenen ayar kategorilerini kontrol et
$expected_categories = [
    'exchange' => 'Borsa',
    'base_currency' => 'Temel Para Birimi',
    'indicators' => 'İndikatörler',
    'strategies' => 'Stratejiler',
    'risk_management' => 'Risk Yönetimi',
    'advanced_risk_management' => 'Gelişmiş Risk Yönetimi',
    'backtesting' => 'Backtesting',
    'integration_settings' => 'Entegrasyon Ayarları',
    'adaptive_parameters' => 'Adaptif Parametreler', 
    'api_optimization' => 'API Optimizasyonu',
    'api_keys' => 'API Anahtarları',
    'telegram' => 'Telegram',
    'auto_discovery' => 'Otomatik Keşif',
    'indicator_weights' => 'İndikatör Ağırlıkları',
    'timeframes' => 'Zaman Aralıkları'
];

echo "Ayar Kategorileri Kontrolü:\n";
echo "------------------------\n";
$missing_categories = [];

foreach ($expected_categories as $cat_key => $cat_name) {
    if (is_array($settings) && array_key_exists($cat_key, $settings)) {
        echo "✅ " . $cat_name . " ayarları mevcut\n";
        
        // Kategori içeriklerini detaylı listele
        if ($cat_key === 'api_keys') {
            echo "   ├── Binance API: " . (isset($settings['api_keys']['binance_api_key']) && !empty($settings['api_keys']['binance_api_key']) ? "Ayarlanmış" : "Boş") . "\n";
            echo "   └── KuCoin API: " . (isset($settings['api_keys']['kucoin_api_key']) && !empty($settings['api_keys']['kucoin_api_key']) ? "Ayarlanmış" : "Boş") . "\n";
        } elseif ($cat_key === 'telegram') {
            echo "   ├── Durum: " . (isset($settings['telegram']['enabled']) && $settings['telegram']['enabled'] ? "Aktif" : "Pasif") . "\n";
            echo "   ├── Token: " . (isset($settings['telegram']['token']) && !empty($settings['telegram']['token']) ? "Ayarlanmış" : "Boş") . "\n";
            echo "   └── Chat ID: " . (isset($settings['telegram']['chat_id']) && !empty($settings['telegram']['chat_id']) ? "Ayarlanmış" : "Boş") . "\n";
        } elseif ($cat_key === 'indicators') {
            $enabled_indicators = 0;
            foreach ($settings['indicators'] as $ind_key => $ind_value) {
                if (isset($ind_value['enabled']) && $ind_value['enabled']) {
                    $enabled_indicators++;
                }
            }
            echo "   └── Aktif İndikatör Sayısı: " . $enabled_indicators . "/" . count($settings['indicators']) . "\n";
        }
    } else {
        echo "❌ " . $cat_name . " ayarları eksik\n";
        $missing_categories[] = $cat_key;
    }
}

if (!empty($missing_categories)) {
    echo "\nEksik Kategori Uyarısı!\n";
    echo "------------------------\n";
    echo "Aşağıdaki ayar kategorileri MySQL'de bulunamadı. Bu ayarlar bot başlangıcında varsayılan değerlerle oluşturulacaktır:\n";
    foreach ($missing_categories as $cat) {
        echo "- " . $expected_categories[$cat] . "\n";
    }
}

// Temel ayarların özeti
echo "\nTemel Ayarlar Özeti:\n";
echo "------------------------\n";
$simple_settings = [
    'exchange' => 'Borsa',
    'base_currency' => 'Temel Para Birimi',
    'min_volume' => 'Minimum Hacim',
    'max_coins' => 'Maksimum Coin Sayısı',
    'leverage' => 'Kaldıraç',
    'trade_mode' => 'İşlem Modu',
    'market_type' => 'Piyasa Tipi'
];

foreach ($simple_settings as $key => $label) {
    echo $label . ": " . (isset($settings[$key]) ? $settings[$key] : "Tanımlanmamış") . "\n";
}

// Ayarların tam JSON çıktısı (isteğe bağlı)
echo "\nTüm Ayarlar (JSON)? (e/h): ";
$handle = fopen("php://stdin", "r");
$show_json = trim(fgets($handle));
fclose($handle);

if (strtolower($show_json) === 'e') {
    echo "\nTüm Ayarlar (JSON):\n";
    echo "------------------------\n";
    echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

$db->close();
echo "\nBağlantı kapatıldı.\n";
?>