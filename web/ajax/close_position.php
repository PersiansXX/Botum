<?php
session_start();

// Giriş yapılmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

// Veritabanı bağlantısı
require_once '../includes/db_connect.php';

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

// Gerekli parametreler
$position_id = isset($_POST['position_id']) ? (int)$_POST['position_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($position_id <= 0 || $action !== 'close') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit;
}

try {
    // Pozisyonu bul - GÜVENLİ VERSİYON
    $query = "SELECT * FROM open_positions WHERE id = ? AND close_time IS NULL";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Sorgu hazırlama hatası: " . $conn->error);
    }
    
    $stmt->bind_param("i", $position_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Pozisyon bulunamadı veya zaten kapalı']);
        exit;
    }
    
    $position = $result->fetch_assoc();
    $stmt->close();
    
    // Güncel fiyatı al (basit örnek - gerçekte API'den alınmalı)
    $current_price = $position['entry_price']; // Geçici olarak giriş fiyatı
    
    // Kar/zarar hesapla
    $pnl = ($current_price - $position['entry_price']) * $position['amount'];
    $pnl_pct = (($current_price / $position['entry_price']) - 1) * 100;
    
    // Pozisyonu kapat - GÜVENLİ VERSİYON
    $update_query = "UPDATE open_positions SET 
                     close_time = NOW(),
                     close_price = ?,
                     pnl = ?,
                     pnl_percentage = ?,
                     status = 'closed'
                     WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    
    if (!$stmt) {
        throw new Exception("Güncelleme sorgusu hazırlama hatası: " . $conn->error);
    }
    
    $stmt->bind_param("dddi", $current_price, $pnl, $pnl_pct, $position_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Trades tablosuna da ekle (eğer yoksa) - GÜVENLİ VERSİYON
        $trade_query = "INSERT INTO trades (
            symbol, type, amount, entry_price, close_price, 
            entry_time, close_time, pnl, pnl_percentage, 
            stop_loss, take_profit, leverage, trade_mode, status
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'closed')
        ON DUPLICATE KEY UPDATE 
            close_price = VALUES(close_price),
            close_time = VALUES(close_time),
            pnl = VALUES(pnl),
            pnl_percentage = VALUES(pnl_percentage),
            status = 'closed'";
        
        $stmt2 = $conn->prepare($trade_query);
        
        if ($stmt2) {
            $stmt2->bind_param("ssdddssddsis", 
                $position['symbol'],
                $position['type'],
                $position['amount'],
                $position['entry_price'],
                $current_price,
                $position['entry_time'],
                $pnl,
                $pnl_pct,
                $position['stop_loss'],
                $position['take_profit'],
                $position['leverage'],
                $position['trade_mode']
            );
            
            $stmt2->execute();
            $stmt2->close();
        } else {
            error_log("Trade ekleme sorgu hatası: " . $conn->error);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pozisyon başarıyla kapatıldı',
            'pnl' => $pnl,
            'pnl_pct' => $pnl_pct,
            'close_price' => $current_price
        ]);
    } else {
        throw new Exception("Pozisyon kapatma işlemi başarısız: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Position close hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>