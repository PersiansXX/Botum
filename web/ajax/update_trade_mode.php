<?php
session_start();
header('Content-Type: application/json');

// Giriş yapılmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

// Veritabanı bağlantısı
require_once '../includes/db_connect.php';

try {
    // POST verileri kontrol et
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || $input['action'] !== 'update_to_live') {
        throw new Exception('Geçersiz işlem');
    }
    
    // Tüm açık pozisyonların trade_mode'unu live yap
    $update_query = "UPDATE open_positions SET trade_mode = 'live' WHERE close_time IS NULL";
    
    if ($conn->query($update_query)) {
        $affected_rows = $conn->affected_rows;
        
        echo json_encode([
            'success' => true, 
            'message' => "Başarılı! $affected_rows pozisyonun trade_mode değeri 'live' olarak güncellendi."
        ]);
    } else {
        throw new Exception('Veritabanı güncelleme hatası: ' . $conn->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>