<?php
require_once('/var/www/html/web/includes/db_connect.php');

try {
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS discovered_coins (
        id INT NOT NULL AUTO_INCREMENT,
        symbol VARCHAR(20) NOT NULL,
        discovery_time DATETIME NULL,
        price DECIMAL(20, 8) NOT NULL,
        volume_usd DECIMAL(20, 2),
        price_change_pct DECIMAL(10, 2),
        buy_signals INT,
        sell_signals INT,
        trade_signal VARCHAR(10),
        is_active TINYINT(1) DEFAULT 1,
        notes TEXT,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($create_table_sql)) {
        echo "discovered_coins tablosu başarıyla oluşturuldu veya zaten var.\n";
    } else {
        echo "Tablo oluşturulurken hata: " . $conn->error . "\n";
    }

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

// Tablonun varlığını kontrol et
$check_table = $conn->query("SHOW TABLES LIKE 'discovered_coins'");
if ($check_table->num_rows > 0) {
    echo "Tablo kontrolü: discovered_coins tablosu mevcut.\n";
} else {
    echo "Tablo kontrolü: discovered_coins tablosu bulunamadı!\n";
}

$conn->close();
?>