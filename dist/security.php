<?php
// Cegah akses langsung ke file ini
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    exit('Akses langsung tidak diizinkan.');
}

session_start();

// Waktu idle maksimum (dalam detik)
$timeout = 1200; 

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Cek apakah sesi user sudah idle melebihi batas
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Perbarui waktu aktivitas terakhir
$_SESSION['LAST_ACTIVITY'] = time();
?>