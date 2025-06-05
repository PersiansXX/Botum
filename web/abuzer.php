<?php
// Veritabanı şemasını düzelt - eksik sütunları ekle
$host = 'localhost';
$username = 'root';
$password = 'Efsane44.';
$database = 'trading_bot_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Veritabanı bağlantısı başarılı.\n";
    
    // open_positions tablosundaki sütunları kontrol et
    echo "open_positions tablosu kontrol ediliyor...\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM open_positions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Mevcut sütunlar: " . implode(', ', $columns) . "\n";
    
    // MariaDB TIMESTAMP kısıtlaması nedeniyle DATETIME kullanıyoruz
    // Sadece created_at sütunu TIMESTAMP olarak kalacak
    $required_columns = [
        'entry_time' => 'DATETIME DEFAULT NULL',
        'exit_time' => 'DATETIME DEFAULT NULL', 
        'exit_price' => 'DECIMAL(20, 8) NULL',
        'profit_loss_pct' => 'DECIMAL(10, 2) NULL',
        'close_reason' => 'VARCHAR(50) NULL',
        'last_updated' => 'DATETIME DEFAULT NULL'
    ];
    
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $columns)) {
            try {
                $sql = "ALTER TABLE open_positions ADD COLUMN $column $definition";
                echo "Ekleniyor: $column ($definition)\n";
                $pdo->exec($sql);
                echo "✓ $column sütunu eklendi.\n";
            } catch (PDOException $e) {
                echo "⚠ $column eklenirken hata: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ $column sütunu zaten var.\n";
        }
    }
    
    // Eksik sütunları mevcut verilerle güncelle
    echo "\nMevcut verileri güncelleniyor...\n";
    
    // trade_time'ı entry_time'a kopyala (eğer entry_time boşsa)
    if (in_array('entry_time', $columns) && in_array('trade_time', $columns)) {
        $pdo->exec("UPDATE open_positions SET entry_time = trade_time WHERE entry_time IS NULL");
        echo "✓ trade_time verileri entry_time'a kopyalandı.\n";
    }
    
    // close_time'ı exit_time'a kopyala (eğer exit_time boşsa)
    if (in_array('exit_time', $columns) && in_array('close_time', $columns)) {
        $pdo->exec("UPDATE open_positions SET exit_time = close_time WHERE exit_time IS NULL");
        echo "✓ close_time verileri exit_time'a kopyalandı.\n";
    }
    
    // close_price'ı exit_price'a kopyala (eğer exit_price boşsa) 
    if (in_array('exit_price', $columns) && in_array('close_price', $columns)) {
        $pdo->exec("UPDATE open_positions SET exit_price = close_price WHERE exit_price IS NULL");
        echo "✓ close_price verileri exit_price'a kopyalandı.\n";
    }
    
    // profit_loss_percent'ı profit_loss_pct'a kopyala (eğer profit_loss_pct boşsa)
    if (in_array('profit_loss_pct', $columns) && in_array('profit_loss_percent', $columns)) {
        $pdo->exec("UPDATE open_positions SET profit_loss_pct = profit_loss_percent WHERE profit_loss_pct IS NULL");
        echo "✓ profit_loss_percent verileri profit_loss_pct'a kopyalandı.\n";
    }
    
    // Eksik sütunları kontrol et ve yeniden ekle
    $stmt = $pdo->query("SHOW COLUMNS FROM open_positions");
    $updated_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // stop_loss ve take_profit sütunlarını kontrol et
    $additional_columns = [
        'stop_loss' => 'DECIMAL(20, 8) NULL',
        'take_profit' => 'DECIMAL(20, 8) NULL'
    ];
    
    foreach ($additional_columns as $column => $definition) {
        if (!in_array($column, $updated_columns)) {
            try {
                $sql = "ALTER TABLE open_positions ADD COLUMN $column $definition";
                echo "Ekleniyor: $column ($definition)\n";
                $pdo->exec($sql);
                echo "✓ $column sütunu eklendi.\n";
            } catch (PDOException $e) {
                echo "⚠ $column eklenirken hata: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ $column sütunu zaten var.\n";
        }
    }
    
    // Mevcut stop_loss_price ve take_profit_price verilerini yeni sütunlara kopyala
    if (in_array('stop_loss', $updated_columns) && in_array('stop_loss_price', $updated_columns)) {
        $pdo->exec("UPDATE open_positions SET stop_loss = stop_loss_price WHERE stop_loss IS NULL");
        echo "✓ stop_loss_price verileri stop_loss'a kopyalandı.\n";
    }
    
    if (in_array('take_profit', $updated_columns) && in_array('take_profit_price', $updated_columns)) {
        $pdo->exec("UPDATE open_positions SET take_profit = take_profit_price WHERE take_profit IS NULL");
        echo "✓ take_profit_price verileri take_profit'a kopyalandı.\n";
    }
    
    // price_analysis tablosunu kontrol et ve oluştur
    echo "\nprice_analysis tablosu kontrol ediliyor...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'price_analysis'");
    if ($stmt->rowCount() == 0) {
        echo "price_analysis tablosu oluşturuluyor...\n";
        $sql = "
        CREATE TABLE price_analysis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(20) NOT NULL,
            price DECIMAL(20, 8) NOT NULL,
            rsi_value DECIMAL(5, 2) NULL,
            macd_value DECIMAL(10, 6) NULL,
            macd_signal DECIMAL(10, 6) NULL,
            bb_upper DECIMAL(20, 8) NULL,
            bb_middle DECIMAL(20, 8) NULL,
            bb_lower DECIMAL(20, 8) NULL,
            ma20 DECIMAL(20, 8) NULL,
            ma50 DECIMAL(20, 8) NULL,
            ma100 DECIMAL(20, 8) NULL,
            ma200 DECIMAL(20, 8) NULL,
            trade_signal VARCHAR(10) DEFAULT 'NEUTRAL',
            buy_signals INT DEFAULT 0,
            sell_signals INT DEFAULT 0,
            neutral_signals INT DEFAULT 0,
            notes TEXT,
            analysis_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_symbol (symbol),
            INDEX idx_analysis_time (analysis_time)
        )";
        $pdo->exec($sql);
        echo "✓ price_analysis tablosu oluşturuldu.\n";
    } else {
        echo "✓ price_analysis tablosu zaten var.\n";
    }
    
    // trade_history tablosunu kontrol et ve oluştur
    echo "\ntrade_history tablosu kontrol ediliyor...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'trade_history'");
    if ($stmt->rowCount() == 0) {
        echo "trade_history tablosu oluşturuluyor...\n";
        $sql = "
        CREATE TABLE trade_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            position_id INT,
            symbol VARCHAR(20) NOT NULL,
            position_type VARCHAR(10) NOT NULL,
            entry_price DECIMAL(20, 8) NOT NULL,
            exit_price DECIMAL(20, 8) NOT NULL,
            amount DECIMAL(20, 8) NOT NULL,
            entry_time DATETIME NULL,
            exit_time DATETIME NULL,
            profit_loss_pct DECIMAL(10, 2),
            close_reason VARCHAR(20),
            strategy VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_symbol (symbol),
            INDEX idx_exit_time (exit_time)
        )";
        $pdo->exec($sql);
        echo "✓ trade_history tablosu oluşturuldu.\n";
    } else {
        echo "✓ trade_history tablosu zaten var.\n";
    }
    
    echo "\n🎉 Veritabanı şeması düzeltmeleri tamamlandı!\n";
    echo "📋 Özet:\n";
    echo "   - open_positions tablosuna eksik sütunlar eklendi\n";
    echo "   - Mevcut veriler yeni sütunlara kopyalandı\n";
    echo "   - price_analysis tablosu kontrol edildi\n";
    echo "   - trade_history tablosu kontrol edildi\n";
    echo "   - TIMESTAMP kısıtlaması DATETIME kullanarak çözüldü\n";
    
} catch (PDOException $e) {
    echo "❌ Veritabanı hatası: " . $e->getMessage() . "\n";
}

// Database connection details
$host = "localhost";
$user = "root";
$password = "Efsane44.";
$database = "trading_bot_db";

// Connect to the database
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.\n";

// Check if bot_settings_individual exists
$result = $conn->query("SHOW TABLES LIKE 'bot_settings_individual'");
if ($result->num_rows == 0) {
    echo "Creating bot_settings_individual table...\n";
    
    // Create the bot_settings_individual table
    // Fix: Changed last_updated to DATETIME to avoid having two TIMESTAMP columns with CURRENT_TIMESTAMP
    $sql = "CREATE TABLE bot_settings_individual (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_name VARCHAR(255) NOT NULL,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,
        last_updated DATETIME DEFAULT NULL
    )";
    
    if ($conn->query($sql) === TRUE) {
        // Create a trigger to update the last_updated column
        $triggerSql = "CREATE TRIGGER update_bot_settings_timestamp 
                       BEFORE UPDATE ON bot_settings_individual
                       FOR EACH ROW 
                       SET NEW.last_updated = NOW()";
        $conn->query($triggerSql);
        
        echo "Table bot_settings_individual created successfully.\n";
        
        // Migrate settings from bot_settings table if it exists
        $result = $conn->query("SHOW TABLES LIKE 'bot_settings'");
        if ($result->num_rows > 0) {
            echo "Migrating settings from bot_settings table...\n";
            
            // Get the structure of bot_settings table
            $result = $conn->query("DESCRIBE bot_settings");
            $hasSettingsColumn = false;
            $settingsColumnName = "";
            
            while ($row = $result->fetch_assoc()) {
                if (strpos(strtolower($row['Field']), 'settings') !== false) {
                    $hasSettingsColumn = true;
                    $settingsColumnName = $row['Field'];
                    break;
                }
            }
            
            if ($hasSettingsColumn) {
                $settings = $conn->query("SELECT * FROM bot_settings ORDER BY id DESC LIMIT 1");
                if ($settings->num_rows > 0) {
                    $settingsRow = $settings->fetch_assoc();
                    $settingsData = json_decode($settingsRow[$settingsColumnName], true);
                    
                    if (is_array($settingsData)) {
                        foreach ($settingsData as $key => $value) {
                            if (is_array($value) || is_object($value)) {
                                $value = json_encode($value);
                            }
                            $stmt = $conn->prepare("INSERT INTO bot_settings_individual (setting_name, setting_value) VALUES (?, ?)");
                            $stmt->bind_param("ss", $key, $value);
                            $stmt->execute();
                            $stmt->close();
                            echo "Migrated setting: $key\n";
                        }
                    }
                }
            }
        }
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "Table bot_settings_individual already exists.\n";
}

// Check if notifications table exists
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows == 0) {
    echo "Creating notifications table...\n";
    
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        channel VARCHAR(20) NOT NULL,
        status BOOLEAN DEFAULT TRUE,
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table notifications created successfully.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "Table notifications already exists.\n";
}

// Add missing columns to existing tables if needed
$tablesToCheck = ['open_positions', 'trade_history', 'bot_status'];

foreach ($tablesToCheck as $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows > 0) {
        // Add needed columns based on the code references
        if ($tableName == 'open_positions') {
            addColumnIfNotExists($conn, $tableName, 'position_type', 'VARCHAR(10) DEFAULT "LONG"');
            addColumnIfNotExists($conn, $tableName, 'status', 'VARCHAR(20) DEFAULT "OPEN"');
            addColumnIfNotExists($conn, $tableName, 'trade_mode', 'VARCHAR(20) DEFAULT "paper"');
            addColumnIfNotExists($conn, $tableName, 'exit_price', 'DECIMAL(18,8) NULL');
            addColumnIfNotExists($conn, $tableName, 'exit_time', 'DATETIME NULL');
            addColumnIfNotExists($conn, $tableName, 'profit_loss_pct', 'DECIMAL(10,2) NULL');
            addColumnIfNotExists($conn, $tableName, 'close_reason', 'VARCHAR(50) NULL');
        } else if ($tableName == 'trade_history') {
            addColumnIfNotExists($conn, $tableName, 'trade_type', 'VARCHAR(10) DEFAULT "LONG"');
            addColumnIfNotExists($conn, $tableName, 'close_reason', 'VARCHAR(50) NULL');
            addColumnIfNotExists($conn, $tableName, 'position_id', 'INT NULL');
            addColumnIfNotExists($conn, $tableName, 'trade_mode', 'VARCHAR(20) DEFAULT "paper"');
            addColumnIfNotExists($conn, $tableName, 'status', 'VARCHAR(20) DEFAULT "COMPLETED"');
            addColumnIfNotExists($conn, $tableName, 'notes', 'TEXT NULL');
        }
    }
}

// Helper function to add a column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql) === TRUE) {
            echo "Added column $column to $table.\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    }
}

echo "Database schema fix completed.\n";
$conn->close();
?>