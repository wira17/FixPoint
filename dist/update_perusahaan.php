<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perusahaan.php');
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_message'] = "ID perusahaan tidak valid.";
    header("Location: perusahaan.php");
    exit;
}

$nama     = mysqli_real_escape_string($conn, $_POST['nama_perusahaan'] ?? '');
$alamat   = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
$kota     = mysqli_real_escape_string($conn, $_POST['kota'] ?? '');
$provinsi = mysqli_real_escape_string($conn, $_POST['provinsi'] ?? '');
$kontak   = mysqli_real_escape_string($conn, $_POST['kontak'] ?? '');
$email    = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$lat      = mysqli_real_escape_string($conn, $_POST['latitude'] ?? '');
$lng      = mysqli_real_escape_string($conn, $_POST['longitude'] ?? '');
$radius   = intval($_POST['radius'] ?? 0);

$logo_dir = __DIR__ . '/images/logo/';
if (!is_dir($logo_dir)) mkdir($logo_dir, 0777, true);

// Ambil nama logo lama supaya bisa dihapus jika diganti
$old_logo = '';
$stmtOld = $conn->prepare("SELECT logo FROM perusahaan WHERE id = ?");
$stmtOld->bind_param('i', $id);
$stmtOld->execute();
$resOld = $stmtOld->get_result();
if ($rowOld = $resOld->fetch_assoc()) {
    $old_logo = $rowOld['logo'];
}
$stmtOld->close();

// Proses upload logo baru (opsional)
$new_logo = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
    $logo_name = basename($_FILES['logo']['name']);
    $file_type = strtolower(pathinfo($logo_name, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($file_type, $allowed_types)) {
        $unique_name = uniqid('logo_', true) . '.' . $file_type;
        $final_path = $logo_dir . $unique_name;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $final_path)) {
            $new_logo = $unique_name;
            // hapus file lama bila ada
            if (!empty($old_logo) && file_exists($logo_dir . $old_logo)) {
                @unlink($logo_dir . $old_logo);
            }
        } else {
            $_SESSION['flash_message'] = "Gagal mengunggah logo.";
            header("Location: perusahaan.php");
            exit;
        }
    } else {
        $_SESSION['flash_message'] = "Tipe file logo tidak didukung.";
        header("Location: perusahaan.php");
        exit;
    }
}

// Build SQL update (dengan prepared statement)
if ($new_logo !== null) {
    $sql = "UPDATE perusahaan SET nama_perusahaan=?, alamat=?, kota=?, provinsi=?, kontak=?, email=?, latitude=?, longitude=?, radius=?, logo=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssissi', $nama, $alamat, $kota, $provinsi, $kontak, $email, $lat, $lng, $radius, $new_logo, $id);
} else {
    $sql = "UPDATE perusahaan SET nama_perusahaan=?, alamat=?, kota=?, provinsi=?, kontak=?, email=?, latitude=?, longitude=?, radius=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssssii', $nama, $alamat, $kota, $provinsi, $kontak, $email, $lat, $lng, $radius, $id);
}

if ($stmt->execute()) {
    $_SESSION['flash_message'] = "Data perusahaan berhasil diperbarui.";
} else {
    $_SESSION['flash_message'] = "Gagal memperbarui data perusahaan.";
}
$stmt->close();

header("Location: perusahaan.php");
exit;
