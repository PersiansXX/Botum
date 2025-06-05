<?php
session_start();

// Giriş yapılmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

echo "<h2>Binance Pozisyon Senkronizasyonu</h2>";

// Bot settings'ten API bilgilerini al
$settings_query = "SELECT settings_json FROM bot_settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($settings_query);

if ($result && $result->num_rows > 0) {
    $settings = json_decode($result->fetch_assoc()['settings_json'], true);
    $api_key = $settings['api_keys']['binance_api_key'] ?? '';
    $api_secret = $settings['api_keys']['binance_api_secret'] ?? '';
    
    if (empty($api_key) || empty($api_secret)) {
        echo "<div class='alert alert-danger'>Binance API anahtarları bulunamadı!</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>Bot ayarları bulunamadı!</div>";
    exit;
}

// Binance'ten pozisyonları çek
function getBinancePositions($api_key, $api_secret) {
    $timestamp = time() * 1000;
    $query_string = "timestamp=" . $timestamp;
    $signature = hash_hmac('sha256', $query_string, $api_secret);
    
    $url = "https://fapi.binance.com/fapi/v2/positionRisk?" . $query_string . "&signature=" . $signature;
    
    $headers = [
        'X-MBX-APIKEY: ' . $api_key
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        return json_decode($response, true);
    } else {
        throw new Exception("Binance API hatası: " . $response);
    }
}

try {
    echo "<div class='alert alert-info'>Binance'ten pozisyonlar getiriliyor...</div>";
    
    $binance_positions = getBinancePositions($api_key, $api_secret);
    
    // Sadece sıfır olmayan pozisyonları filtrele
    $active_positions = [];
    foreach ($binance_positions as $position) {
        if (abs(floatval($position['positionAmt'])) > 0) {
            $active_positions[] = $position;
        }
    }
    
    echo "<h3>Binance'teki Aktif Pozisyonlar (" . count($active_positions) . " adet):</h3>";
    echo "<table class='table table-striped'>";
    echo "<tr><th>Symbol</th><th>Miktar</th><th>Giriş Fiyatı</th><th>PNL</th></tr>";
    
    $binance_symbols = [];
    foreach ($active_positions as $position) {
        echo "<tr>";
        echo "<td>" . $position['symbol'] . "</td>";
        echo "<td>" . number_format($position['positionAmt'], 6) . "</td>";
        echo "<td>" . number_format($position['entryPrice'], 6) . "</td>";
        echo "<td>" . number_format($position['unRealizedProfit'], 4) . "</td>";
        echo "</tr>";
        
        $binance_symbols[] = $position['symbol'];
    }
    echo "</table>";
    
    // Veritabanındaki açık pozisyonları getir
    echo "<h3>Veritabanındaki Açık Pozisyonlar:</h3>";
    $db_query = "SELECT symbol, id, entry_time FROM open_positions WHERE close_time IS NULL";
    $db_result = $conn->query($db_query);
    
    $db_positions = [];
    $db_symbols = [];
    
    echo "<table class='table table-striped'>";
    echo "<tr><th>Symbol</th><th>ID</th><th>Giriş Zamanı</th><th>Durum</th></tr>";
    
    while ($row = $db_result->fetch_assoc()) {
        $db_positions[] = $row;
        $db_symbols[] = $row['symbol'];
        
        $status = in_array($row['symbol'], $binance_symbols) ? 
                 "<span class='badge badge-success'>✓ Binance'te var</span>" : 
                 "<span class='badge badge-danger'>✗ Binance'te yok</span>";
        
        echo "<tr>";
        echo "<td>" . $row['symbol'] . "</td>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['entry_time'] . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Binance'te olmayan veritabanı pozisyonlarını bul
    $ghost_positions = array_diff($db_symbols, $binance_symbols);
    
    if (!empty($ghost_positions)) {
        echo "<h3>🔍 Temizlenecek Pozisyonlar (" . count($ghost_positions) . " adet):</h3>";
        echo "<div class='alert alert-warning'>";
        echo "Bu pozisyonlar veritabanında açık gözüküyor ama Binance'te yok:<br>";
        echo "<strong>" . implode(", ", $ghost_positions) . "</strong>";
        echo "</div>";
        
        echo "<button onclick='cleanGhostPositions()' class='btn btn-warning'>Bu Pozisyonları Temizle</button>";
        echo "<div id='clean-result' class='mt-3'></div>";
    } else {
        echo "<div class='alert alert-success'>✅ Tüm pozisyonlar senkronize!</div>";
    }
    
    // Binance'te olup veritabanında olmayan pozisyonlar
    $missing_positions = array_diff($binance_symbols, $db_symbols);
    
    if (!empty($missing_positions)) {
        echo "<h3>⚠️ Eksik Pozisyonlar (" . count($missing_positions) . " adet):</h3>";
        echo "<div class='alert alert-info'>";
        echo "Bu pozisyonlar Binance'te var ama veritabanında yok:<br>";
        echo "<strong>" . implode(", ", $missing_positions) . "</strong>";
        echo "</div>";
        echo "<p><small>Bu pozisyonlar muhtemelen bot dışında manuel açılmış.</small></p>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
}

$conn->close();
?>

<script>
function cleanGhostPositions() {
    if (confirm('Binance\'te olmayan pozisyonları veritabanında kapalı olarak işaretlemek istediğinizden emin misiniz?')) {
        fetch('ajax/clean_ghost_positions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'clean_ghost_positions'})
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('clean-result').innerHTML = 
                '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
            if (data.success) {
                setTimeout(() => location.reload(), 2000);
            }
        })
        .catch(error => {
            document.getElementById('clean-result').innerHTML = 
                '<div class="alert alert-danger">Hata: ' + error + '</div>';
        });
    }
}
</script>

<style>
.table { font-size: 0.9em; }
.badge { font-size: 0.8em; }
</style>