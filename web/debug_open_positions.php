<?php
// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

echo "<h2>Open Positions Tablosu Analizi</h2>";

// Toplam kayıt sayısı
$total_query = "SELECT COUNT(*) as total FROM open_positions";
$result = $conn->query($total_query);
$total = $result->fetch_assoc()['total'];
echo "<p><strong>Toplam kayıt sayısı:</strong> $total</p>";

// Status'lara göre dağılım
echo "<h3>Status Dağılımı:</h3>";
$status_query = "SELECT status, COUNT(*) as count FROM open_positions GROUP BY status";
$result = $conn->query($status_query);
echo "<table border='1'>";
echo "<tr><th>Status</th><th>Sayı</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . htmlspecialchars($row['status']) . "</td><td>" . $row['count'] . "</td></tr>";
}
echo "</table>";

// Trade Mode Dağılımı
echo "<h3>Trade Mode Dağılımı:</h3>";
$mode_query = "SELECT trade_mode, COUNT(*) as count FROM open_positions GROUP BY trade_mode";
$result = $conn->query($mode_query);
echo "<table border='1'>";
echo "<tr><th>Trade Mode</th><th>Sayı</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . htmlspecialchars($row['trade_mode'] ?: 'NULL') . "</td><td>" . $row['count'] . "</td></tr>";
}
echo "</table>";

// Close Time durumu
echo "<h3>Close Time Durumu:</h3>";
$close_time_query = "SELECT 
    SUM(CASE WHEN close_time IS NULL THEN 1 ELSE 0 END) as null_count,
    SUM(CASE WHEN close_time IS NOT NULL THEN 1 ELSE 0 END) as not_null_count,
    SUM(CASE WHEN close_time = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as zero_date_count
    FROM open_positions";
$result = $conn->query($close_time_query);
$close_stats = $result->fetch_assoc();
echo "<table border='1'>";
echo "<tr><th>Close Time Durumu</th><th>Sayı</th></tr>";
echo "<tr><td>NULL</td><td>" . $close_stats['null_count'] . "</td></tr>";
echo "<tr><td>Dolu</td><td>" . $close_stats['not_null_count'] . "</td></tr>";
echo "<tr><td>0000-00-00 00:00:00</td><td>" . $close_stats['zero_date_count'] . "</td></tr>";
echo "</table>";

// Gerçekten açık pozisyonlar
echo "<h3>Gerçekten Açık Pozisyonlar (Filtrelenmiş):</h3>";
$open_query = "SELECT COUNT(*) as open_count FROM open_positions 
               WHERE close_time IS NULL 
               AND (close_price IS NULL OR close_price = 0)
               AND (status != 'closed' AND status != 'CLOSED')";
$result = $conn->query($open_query);
$open_count = $result->fetch_assoc()['open_count'];
echo "<p><strong>Filtrelenmiş açık pozisyon sayısı:</strong> $open_count</p>";

// Son 15 kayıt - daha detaylı
echo "<h3>Son 15 Kayıt (Detaylı):</h3>";
$recent_query = "SELECT id, symbol, status, trade_mode, entry_time, close_time, close_price FROM open_positions ORDER BY id DESC LIMIT 15";
$result = $conn->query($recent_query);
echo "<table border='1' style='font-size: 12px;'>";
echo "<tr><th>ID</th><th>Symbol</th><th>Status</th><th>Trade Mode</th><th>Entry Time</th><th>Close Time</th><th>Close Price</th></tr>";
while ($row = $result->fetch_assoc()) {
    $row_class = '';
    if (empty($row['close_time']) && strtolower($row['status']) !== 'closed') {
        $row_class = 'style="background-color: #d4edda;"'; // Açık pozisyonlar yeşil
    }
    echo "<tr $row_class>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['symbol']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . htmlspecialchars($row['trade_mode'] ?: 'NULL') . "</td>";
    echo "<td>" . $row['entry_time'] . "</td>";
    echo "<td>" . ($row['close_time'] ?: 'NULL') . "</td>";
    echo "<td>" . ($row['close_price'] ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p><small>Yeşil satırlar = Açık pozisyonlar</small></p>";

// Trade mode'u live olmayan açık pozisyonlar
echo "<h3>Trade Mode 'live' Olmayan Açık Pozisyonlar:</h3>";
$non_live_query = "SELECT id, symbol, trade_mode, entry_time FROM open_positions 
                   WHERE close_time IS NULL 
                   AND (trade_mode != 'live' OR trade_mode IS NULL)
                   ORDER BY entry_time DESC";
$result = $conn->query($non_live_query);
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Symbol</th><th>Trade Mode</th><th>Entry Time</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['symbol']) . "</td>";
        echo "<td style='background-color: #f8d7da;'>" . htmlspecialchars($row['trade_mode'] ?: 'NULL') . "</td>";
        echo "<td>" . $row['entry_time'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tüm açık pozisyonlar 'live' modunda.</p>";
}

// Trade mode'u düzeltme önerisi
echo "<h3>Trade Mode Düzeltme İşlemi:</h3>";
echo "<p>Eğer tüm açık pozisyonların trade_mode'unu 'live' yapmak istiyorsanız:</p>";
echo "<div style='background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
echo "<code>UPDATE open_positions SET trade_mode = 'live' WHERE close_time IS NULL;</code>";
echo "</div>";
echo "<br>";
echo "<button onclick='updateTradeMode()' class='btn btn-warning'>Tüm Açık Pozisyonları LIVE Yap</button>";

echo "<script>
function updateTradeMode() {
    if (confirm('Tüm açık pozisyonların trade_mode değerini LIVE yapmak istediğinizden emin misiniz?')) {
        fetch('ajax/update_trade_mode.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update_to_live'})
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => {
            alert('Hata: ' + error);
        });
    }
}
</script>";

$conn->close();
?>