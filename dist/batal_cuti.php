<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
  $_SESSION['flash_message'] = "❌ ID pengajuan tidak valid.";
  header("Location: pengajuan_cuti.php");
  exit;
}

// Cek apakah pengajuan ini milik user dan masih bisa dibatalkan
$qCek = mysqli_query($conn, "
  SELECT id FROM pengajuan_cuti 
  WHERE id='$id' 
    AND karyawan_id='$user_id'
    AND status_delegasi='Menunggu' 
    AND status_atasan='Menunggu' 
    AND status_hrd='Menunggu'
  LIMIT 1
");

if (mysqli_num_rows($qCek) == 0) {
  $_SESSION['flash_message'] = "⚠️ Pengajuan ini tidak dapat dibatalkan (sudah diproses atau bukan milik Anda).";
  header("Location: pengajuan_cuti.php");
  exit;
}

// Hapus data
mysqli_query($conn, "DELETE FROM pengajuan_cuti_detail WHERE pengajuan_id='$id'");
mysqli_query($conn, "DELETE FROM pengajuan_cuti WHERE id='$id'");

$_SESSION['flash_message'] = "✅ Pengajuan cuti berhasil dibatalkan.";
header("Location: pengajuan_cuti.php");
exit;
?>
