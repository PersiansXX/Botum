<?php
/**
 * Active_coins tablosundaki eksik sütunu ekleyen düzeltme betiği
 * Sorun: "Unknown column 'added_by' in 'field list"
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantısı - doğru yol kullan
require_once 'includes/db_connect.php';

// Başarı/hata mesajları
$messages = [];

// Tablo var mı kontrol et
$table_check = "SHOW TABLES LIKE 'active_coins'";
$result = $conn->query($table_check);

if ($result->num_rows == 0) {
    $messages[] = [
        'type' => 'error', 
        'message' => "active_coins tablosu bulunamadı! Önce tabloyu oluşturmalısınız."
    ];
} else {
    // Sütun var mı kontrol et
    $column_check = "SHOW COLUMNS FROM active_coins LIKE 'added_by'";
    $result = $conn->query($column_check);
    
    if ($result->num_rows > 0) {
        $messages[] = [
            'type' => 'info', 
            'message' => "added_by sütunu zaten mevcut."
        ];
    } else {
        // Sütunu ekle - added_at sütununu uyumlu bir şekilde tanımlıyorum
        $alter_query = "ALTER TABLE active_coins 
                        ADD COLUMN added_by VARCHAR(50) DEFAULT 'system' AFTER symbol, 
                        ADD COLUMN added_at DATETIME DEFAULT NULL AFTER added_by";
        
        if ($conn->query($alter_query)) {
            // Mevcut kayıtlar için added_at değerini güncelle
            $update_query = "UPDATE active_coins SET added_at = NOW()";
            $conn->query($update_query);
            
            $messages[] = [
                'type' => 'success', 
                'message' => "added_by ve added_at sütunları başarıyla eklendi!"
            ];
        } else {
            $messages[] = [
                'type' => 'error', 
                'message' => "Sütun eklenirken hata: " . $conn->error
            ];
        }
    }
    
    // Mevcut tablo yapısını göster
    $structure_query = "DESCRIBE active_coins";
    $structure_result = $conn->query($structure_query);
    $table_structure = [];
    
    if ($structure_result) {
        while ($row = $structure_result->fetch_assoc()) {
            $table_structure[] = $row;
        }
    }
}

// HTML çıktısını oluştur
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Coins Tablo Düzeltmesi</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .message-container { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Active Coins Tablosu Düzeltmesi</h4>
                    </div>
                    <div class="card-body">
                        <div class="message-container">
                            <h5>İşlem Sonuçları:</h5>
                            <?php if (empty($messages)): ?>
                                <div class="alert alert-warning">Hiçbir işlem gerçekleştirilmedi.</div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="alert alert-<?php 
                                        echo $msg['type'] === 'success' ? 'success' : 
                                             ($msg['type'] === 'error' ? 'danger' : 'info'); 
                                    ?>">
                                        <?php echo $msg['message']; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($table_structure)): ?>
                            <h5>Güncel Tablo Yapısı:</h5>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Alan</th>
                                        <th>Tip</th>
                                        <th>Null</th>
                                        <th>Anahtar</th>
                                        <th>Varsayılan</th>
                                        <th>Ekstra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table_structure as $column): ?>
                                        <tr>
                                            <td><?php echo $column['Field']; ?></td>
                                            <td><?php echo $column['Type']; ?></td>
                                            <td><?php echo $column['Null']; ?></td>
                                            <td><?php echo $column['Key']; ?></td>
                                            <td><?php echo $column['Default']; ?></td>
                                            <td><?php echo $column['Extra']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="coins.php" class="btn btn-primary">Coinler Sayfasına Dön</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>