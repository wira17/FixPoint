<?php
include 'security.php';
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = intval($_POST['id']);
    $judul = trim($_POST['judul']);
    $isi   = trim($_POST['isi']);

    $sql = "UPDATE catatan_kerja SET judul=?, isi=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $judul, $isi, $id);

    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=updated");
    } else {
        echo "Gagal memperbarui catatan!";
    }
}
