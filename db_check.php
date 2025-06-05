<?php
// Veritabanı bağlantısı
include_once 'web/includes/db_connect.php';

echo "<h1>Veritabanı Tablo Kontrolü</h1>";

// Tabloları listele
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);

if ($tables_result) {
    echo "<h2>Mevcut Tablolar:</h2>";
    echo "<ul>";
    while ($row = $tables_result->fetch_row()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Tablolar listelenirken hata oluştu: " . $conn->error . "</p>";
}

// Ana tabloları kontrol et
$check_tables = [
    'bot_config' => 'Bot Yapılandırması',
    'api_keys' => 'API Anahtarları',
    'open_positions' => 'Açık Pozisyonlar'
];

foreach ($check_tables as $table => $description) {
    echo "<h2>$description Tablosu</h2>";
    
    // Tablonun varlığını kontrol et
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows == 0) {
        echo "<p>$table tablosu bulunamadı!</p>";
        continue;
    }
    
    // Tablo yapısını göster
    $structure_query = "DESCRIBE $table";
    $structure_result = $conn->query($structure_query);
    
    if ($structure_result) {
        echo "<h3>Tablo Yapısı:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Alan</th><th>Tip</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Ekstra</th></tr>";
        
        while ($row = $structure_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Tablo yapısı gösterilirken hata oluştu: " . $conn->error . "</p>";
    }
    
    // Verileri göster
    $data_query = "SELECT * FROM $table LIMIT 5";
    $data_result = $conn->query($data_query);
    
    if ($data_result && $data_result->num_rows > 0) {
        echo "<h3>Tablo Verileri (ilk 5):</h3>";
        echo "<table border='1'>";
        
        // Tablo başlıkları
        echo "<tr>";
        $fields = $data_result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Veriler
        $data_result->data_seek(0);
        while ($row = $data_result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                // JSON formatında olanları daha okunabilir yap
                if (strpos($value, '{') === 0 || strpos($value, '[') === 0) {
                    try {
                        $decoded = json_decode($value, true);
                        if ($decoded !== null) {
                            // JSON güzel bir formatta yazdır
                            $value = "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                        }
                    } catch (Exception $e) {
                        // JSON decode hatası, değeri olduğu gibi kullan
                    }
                }
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Tabloda veri bulunamadı veya sorguda hata oluştu.</p>";
    }
    
    // Kayıt sayısı
    $count_query = "SELECT COUNT(*) as count FROM $table";
    $count_result = $conn->query($count_query);
    
    if ($count_result) {
        $count = $count_result->fetch_assoc()['count'];
        echo "<p>Toplam kayıt sayısı: $count</p>";
    }
}

// JSON dosyalarından veritabanına karşılaştır
echo "<h2>JSON Dosyası ve Veritabanı Karşılaştırması</h2>";

// bot_config.json kontrolü
$json_path = "bot/config/bot_config.json";
if (file_exists($json_path)) {
    $json_content = file_get_contents($json_path);
    $json_data = json_decode($json_content, true);
    
    if ($json_data) {
        echo "<h3>bot_config.json İçeriği:</h3>";
        echo "<pre>" . json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        // Veritabanındaki config ile karşılaştır
        $config_query = "SELECT * FROM bot_config WHERE is_active = 1 LIMIT 1";
        $config_result = $conn->query($config_query);
        
        if ($config_result && $config_result->num_rows > 0) {
            $db_config = $config_result->fetch_assoc();
            echo "<h3>Veritabanı Bot Yapılandırması:</h3>";
            echo "<pre>";
            print_r($db_config);
            echo "</pre>";
        } else {
            echo "<p>Veritabanında aktif yapılandırma bulunamadı.</p>";
        }
    } else {
        echo "<p>bot_config.json dosyası geçerli bir JSON içermiyor.</p>";
    }
} else {
    echo "<p>bot_config.json dosyası bulunamadı.</p>";
}

// api_keys.json kontrolü
$json_path = "bot/config/api_keys.json";
if (file_exists($json_path)) {
    $json_content = file_get_contents($json_path);
    $json_data = json_decode($json_content, true);
    
    if ($json_data) {
        echo "<h3>api_keys.json İçeriği:</h3>";
        // API anahtarlarının tam değerini göstermeyelim, güvenlik için
        $masked_data = $json_data;
        foreach ($masked_data as $exchange => $keys) {
            if (isset($keys['api_key'])) {
                $masked_data[$exchange]['api_key'] = substr($keys['api_key'], 0, 4) . "..." . substr($keys['api_key'], -4);
            }
            if (isset($keys['api_secret'])) {
                $masked_data[$exchange]['api_secret'] = substr($keys['api_secret'], 0, 4) . "..." . substr($keys['api_secret'], -4);
            }
        }
        echo "<pre>" . json_encode($masked_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        // Veritabanındaki api_keys ile karşılaştır
        $keys_query = "SELECT * FROM api_keys WHERE is_active = 1 LIMIT 1";
        $keys_result = $conn->query($keys_query);
        
        if ($keys_result && $keys_result->num_rows > 0) {
            $db_keys = $keys_result->fetch_assoc();
            echo "<h3>Veritabanı API Anahtarları:</h3>";
            // API anahtarlarının tam değerini göstermeyelim, güvenlik için
            if (isset($db_keys['api_key'])) {
                $db_keys['api_key'] = substr($db_keys['api_key'], 0, 4) . "..." . substr($db_keys['api_key'], -4);
            }
            if (isset($db_keys['api_secret'])) {
                $db_keys['api_secret'] = substr($db_keys['api_secret'], 0, 4) . "..." . substr($db_keys['api_secret'], -4);
            }
            echo "<pre>";
            print_r($db_keys);
            echo "</pre>";
        } else {
            echo "<p>Veritabanında aktif API anahtarları bulunamadı.</p>";
        }
    } else {
        echo "<p>api_keys.json dosyası geçerli bir JSON içermiyor.</p>";
    }
} else {
    echo "<p>api_keys.json dosyası bulunamadı.</p>";
}

// open_positions.json kontrolü
$json_path = "bot/config/open_positions.json";
if (file_exists($json_path)) {
    $json_content = file_get_contents($json_path);
    $json_data = json_decode($json_content, true);
    
    if ($json_data) {
        echo "<h3>open_positions.json İçeriği:</h3>";
        echo "<pre>" . json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        // Veritabanındaki open_positions ile karşılaştır
        $positions_query = "SELECT * FROM open_positions WHERE status = 'OPEN'";
        $positions_result = $conn->query($positions_query);
        
        if ($positions_result && $positions_result->num_rows > 0) {
            $db_positions = [];
            while ($row = $positions_result->fetch_assoc()) {
                $db_positions[] = $row;
            }
            
            echo "<h3>Veritabanı Açık Pozisyonlar:</h3>";
            echo "<pre>";
            print_r($db_positions);
            echo "</pre>";
        } else {
            echo "<p>Veritabanında açık pozisyon bulunamadı.</p>";
        }
    } else {
        echo "<p>open_positions.json dosyası geçerli bir JSON içermiyor.</p>";
    }
} else {
    echo "<p>open_positions.json dosyası bulunamadı.</p>";
}

// Veritabanı bağlantısını kapat
$conn->close();
?>