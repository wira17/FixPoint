<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Aktifkan error reporting untuk debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['ubah_status'])) {
    $tiket_id   = mysqli_real_escape_string($conn, $_POST['tiket_id']);
    $status     = mysqli_real_escape_string($conn, $_POST['status']);
    $catatan_it = mysqli_real_escape_string($conn, $_POST['catatan_it']);
    $teknisi    = isset($_SESSION['nama']) ? mysqli_real_escape_string($conn, $_SESSION['nama']) : 'Tidak Dikenal';
    $now        = date('Y-m-d H:i:s');

    // Validasi input
    if (empty($tiket_id) || empty($status)) {
        echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
        exit;
    }

    // Tentukan kolom waktu yang akan diupdate
    $waktu_field = '';
    switch ($status) {
        case 'Diproses':
            $waktu_field = ", waktu_diproses = '$now'";
            break;
        case 'Selesai':
            $waktu_field = ", waktu_selesai = '$now', status_validasi = 'Belum Validasi'";
            break;
        case 'Tidak Bisa Diperbaiki':
            $waktu_field = ", waktu_tidak_bisa_diperbaiki = '$now'";
            break;
        case 'Ditolak':
            $waktu_field = ", waktu_ditolak = '$now'";
            break;
    }

    // Query update
    $query = "
        UPDATE tiket_sarpras 
        SET status = '$status',
            catatan_it = '$catatan_it',
            teknisi_nama = '$teknisi'
            $waktu_field
        WHERE id = '$tiket_id'
    ";

    // Eksekusi dan cek hasil
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Status tiket berhasil diperbarui.'); window.location.href='data_tiket_sarpras.php';</script>";
    } else {
        echo "<pre>Gagal update status:\n" . mysqli_error($conn) . "\nQuery:\n$query</pre>";
    }
} else {
    echo "<script>alert('Akses tidak valid.'); window.location.href='data_tiket_sarpras.php';</script>";
}
?>
