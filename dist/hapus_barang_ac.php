<?php
include 'security.php';
include 'koneksi.php';

if (!isset($_GET['id'])) {
    echo "<script>alert('ID tidak ditemukan'); window.location='barang_ac.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$hapus = mysqli_query($conn, "DELETE FROM data_barang_ac WHERE id='$id'");

if ($hapus) {
    echo "<script>alert('Data berhasil dihapus'); window.location='barang_ac.php';</script>";
} else {
    echo "<script>alert('Gagal menghapus data'); window.location='barang_ac.php';</script>";
}
?>
