<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Ambil nama user (opsional, bisa dipakai untuk log)
$nama_user = $_SESSION['nama_user'] ?? $_SESSION['nama'] ?? $_SESSION['username'] ?? '';
if ($nama_user === '' && $user_id > 0) {
    $qUser = mysqli_query($conn, "SELECT nama FROM users WHERE id = $user_id LIMIT 1");
    if ($qUser && mysqli_num_rows($qUser) === 1) $nama_user = mysqli_fetch_assoc($qUser)['nama'];
}
if ($nama_user === '') $nama_user = 'User ID #' . $user_id;



// ==== PROSES HAPUS ====
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Ambil data file
    $q = mysqli_query($conn, "SELECT file_path, file_name_original FROM dokumen WHERE id = $id LIMIT 1");
    if ($q && mysqli_num_rows($q) == 1) {
        $row = mysqli_fetch_assoc($q);
        $file_path = $row['file_path'];

        // Hapus data dari database
        $del = mysqli_query($conn, "DELETE FROM dokumen WHERE id = $id");
        if ($del) {
            // Hapus file fisik jika ada
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }

            $_SESSION['flash_message'] = "Dokumen berhasil dihapus.";
        } else {
            $_SESSION['flash_message'] = "Gagal menghapus dokumen: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['flash_message'] = "Data dokumen tidak ditemukan.";
    }
} else {
    $_SESSION['flash_message'] = "Parameter ID tidak valid.";
}

header("Location: input_dokumen.php?tab=data");
exit;
