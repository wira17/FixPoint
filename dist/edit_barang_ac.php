<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_GET['id'])) {
    echo "<script>alert('ID tidak ditemukan'); window.location='barang_ac.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM data_barang_ac WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='barang_ac.php';</script>";
    exit;
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_ac = $_POST['kode_ac'];
    $lokasi = $_POST['lokasi'];
    $merk = $_POST['merk'];
    $tipe = $_POST['tipe'];
    $kapasitas = $_POST['kapasitas'];
    $no_seri = $_POST['no_seri'];
    $tahun_beli = $_POST['tahun_beli'];
    $kondisi = $_POST['kondisi'];
    $status = $_POST['status'];

    $update = mysqli_query($conn, "UPDATE data_barang_ac SET 
        kode_ac='$kode_ac',
        lokasi='$lokasi',
        merk='$merk',
        tipe='$tipe',
        kapasitas='$kapasitas',
        no_seri='$no_seri',
        tahun_beli='$tahun_beli',
        kondisi='$kondisi',
        status='$status'
        WHERE id='$id'
    ");

    if ($update) {
        echo "<script>alert('Data berhasil diperbarui!'); window.location='barang_ac.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Data AC</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
  <h4>Edit Data Barang AC</h4>
  <form method="POST">
    <div class="form-group">
      <label>Kode AC</label>
      <input type="text" name="kode_ac" class="form-control" value="<?= $data['kode_ac']; ?>" required>
    </div>
    <div class="form-group">
      <label>Lokasi</label>
      <input type="text" name="lokasi" class="form-control" value="<?= $data['lokasi']; ?>" required>
    </div>
    <div class="form-group">
      <label>Merk</label>
      <input type="text" name="merk" class="form-control" value="<?= $data['merk']; ?>">
    </div>
    <div class="form-group">
      <label>Tipe</label>
      <input type="text" name="tipe" class="form-control" value="<?= $data['tipe']; ?>">
    </div>
    <div class="form-group">
      <label>Kapasitas</label>
      <input type="text" name="kapasitas" class="form-control" value="<?= $data['kapasitas']; ?>">
    </div>
    <div class="form-group">
      <label>No Seri</label>
      <input type="text" name="no_seri" class="form-control" value="<?= $data['no_seri']; ?>">
    </div>
    <div class="form-group">
      <label>Tahun Beli</label>
      <input type="number" name="tahun_beli" class="form-control" value="<?= $data['tahun_beli']; ?>">
    </div>
    <div class="form-group">
      <label>Kondisi</label>
      <select name="kondisi" class="form-control">
        <option value="Baik" <?= $data['kondisi']=='Baik'?'selected':''; ?>>Baik</option>
        <option value="Perlu Perbaikan" <?= $data['kondisi']=='Perlu Perbaikan'?'selected':''; ?>>Perlu Perbaikan</option>
        <option value="Rusak Berat" <?= $data['kondisi']=='Rusak Berat'?'selected':''; ?>>Rusak Berat</option>
      </select>
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="status" class="form-control">
        <option value="Aktif" <?= $data['status']=='Aktif'?'selected':''; ?>>Aktif</option>
        <option value="Nonaktif" <?= $data['status']=='Nonaktif'?'selected':''; ?>>Nonaktif</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="barang_ac.php" class="btn btn-secondary">Kembali</a>
  </form>
</div>
</body>
</html>
