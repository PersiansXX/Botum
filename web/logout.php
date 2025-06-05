<?php
// Trading Bot sabitini tanımla (güvenlik için)
define('TRADING_BOT', true);

// Session başlat
session_start();

// Session temizle
$_SESSION = array();

// Cookie'yi sil
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Remember me cookie'sini sil
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time()-42000, '/');
}

// Session'ı sonlandır
session_destroy();

// Login sayfasına yönlendir
header('Location: login.php');
exit;
?>
