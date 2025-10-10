<?php
include 'koneksi.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id          = $_POST['id'];
  $kode_ac     = mysqli_real_escape_string($conn, $_POST['kode_ac']);
  $merk        = mysqli_real_escape_string($conn, $_POST['merk']);
  $tipe        = mysqli_real_escape_string($conn, $_POST['tipe']);
  $kapasitas   = mysqli_real_escape_string($conn, $_POST['kapasitas']);
  $no_seri     = mysqli_real_escape_string($conn, $_POST['no_seri']);
  $tahun_beli  = mysqli_real_escape_string($conn, $_POST['tahun_beli']);
  $lokasi      = mysqli_real_escape_string($conn, $_POST['lokasi']);
  $kondisi     = mysqli_real_escape_string($conn, $_POST['kondisi']);
  $status      = mysqli_real_escape_string($conn, $_POST['status']);

  $query = "UPDATE data_barang_ac SET 
              kode_ac='$kode_ac',
              merk='$merk',
              tipe='$tipe',
              kapasitas='$kapasitas',
              no_seri='$no_seri',
              tahun_beli='$tahun_beli',
              lokasi='$lokasi',
              kondisi='$kondisi',
              status='$status'
            WHERE id='$id'";

  if (mysqli_query($conn, $query)) {
    echo "✅ Data berhasil diperbarui!";
  } else {
    echo "❌ Gagal update data: " . mysqli_error($conn);
  }
}
?>
