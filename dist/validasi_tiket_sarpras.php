<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tiket_id = intval($_POST['tiket_id']);
    $now = date('Y-m-d H:i:s');

    if (isset($_POST['validasi'])) {
        $status = 'Diterima';
    } elseif (isset($_POST['tolak'])) {
        $status = 'Ditolak';
    } else {
        echo "<script>alert('Aksi tidak dikenali.'); window.history.back();</script>";
        exit;
    }

    $update = mysqli_query($conn, "UPDATE tiket_sarpras 
        SET status_validasi = '$status', waktu_validasi = '$now' 
        WHERE id = '$tiket_id'");

    if ($update) {
        echo "<script>alert('Tiket berhasil divalidasi ($status).'); window.location.href='order_tiket_sarpras.php';</script>";
    } else {
        echo "<script>alert('Gagal validasi tiket: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Akses tidak valid.'); window.location.href='order_tiket_sarpras.php';</script>";
}
?>
