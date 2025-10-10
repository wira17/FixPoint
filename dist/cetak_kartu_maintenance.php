<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';

if (!isset($_GET['id'])) {
    die('ID maintenance tidak ditemukan.');
}

$id = intval($_GET['id']);

// Ambil data maintenance + user + barang AC
$query = mysqli_query($conn, "
    SELECT m.*, u.nik, u.nama, u.jabatan, u.unit_kerja, 
           b.kode_ac, b.lokasi, b.merk, b.tipe, b.kapasitas, b.no_seri, b.tahun_beli
    FROM maintanance_rutin_sarpras m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN data_barang_ac b ON m.barang_id = b.id
    WHERE m.id = '$id'
");

if (!$query) {
    die('Query gagal: ' . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die('Data maintenance tidak ditemukan.');
}

// Ambil data perusahaan
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// Hitung tanggal maintenance berikutnya (3 bulan ke depan)
$tanggal_berikutnya = date('d-m-Y', strtotime($data['waktu_input'] . ' +3 months'));

$html = '
<style>
  body { 
    font-family: Arial, sans-serif; 
    font-size: 11px; 
    color: #000; 
    margin: 30px;
  }
  .ticket {
    border: 2px dashed #000;
    padding: 20px;
    width: 100%;
    box-sizing: border-box;
  }
  .header {
    text-align: center;
    margin-bottom: 10px;
  }
  .header .nama-perusahaan {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
  }
  .header .alamat {
    font-size: 10px;
  }
  .title {
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    margin: 10px 0 5px 0;
    text-transform: uppercase;
  }
  .info {
    margin: 8px 0;
    line-height: 1.4;
  }
  .info div {
    margin-bottom: 3px;
  }
  .label {
    display: inline-block;
    width: 130px;
    font-weight: bold;
  }
  .kendala, .catatan {
    margin-top: 8px;
    font-size: 10px;
    line-height: 1.4;
  }
  .box-merah {
    border: 2px solid red;
    color: red;
    background-color: #ffe6e6;
    font-weight: bold;
    text-align: center;
    padding: 10px;
    margin-top: 20px;
    border-radius: 6px;
    font-size: 11px;
  }
</style>

<div class="ticket">
  <div class="header">
    <div class="nama-perusahaan">' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</div>
    <div class="alamat">' . htmlspecialchars($perusahaan['alamat']) . ', ' . htmlspecialchars($perusahaan['kota']) . '<br>
    Telp: ' . htmlspecialchars($perusahaan['kontak']) . ' | Email: ' . htmlspecialchars($perusahaan['email']) . '</div>
  </div>

  <div class="title">KARTU MAINTENANCE RUTIN (AC)</div>

  <div class="info">
    <div><span class="label">Tanggal</span>: ' . date('d-m-Y', strtotime($data['waktu_input'])) . '</div>
    <div><span class="label">NIK</span>: ' . htmlspecialchars($data['nik']) . '</div>
    <div><span class="label">Nama Teknisi</span>: ' . htmlspecialchars($data['nama']) . '</div>
    <div><span class="label">Unit Kerja</span>: ' . htmlspecialchars($data['unit_kerja']) . '</div>
  </div>

  <div class="title" style="font-size:11px;">DETAIL UNIT AC</div>
  <div class="info">
    <div><span class="label">Kode AC</span>: ' . htmlspecialchars($data['kode_ac']) . '</div>
    <div><span class="label">Lokasi</span>: ' . htmlspecialchars($data['lokasi']) . '</div>
    <div><span class="label">Merk</span>: ' . htmlspecialchars($data['merk']) . '</div>
    <div><span class="label">Tipe</span>: ' . htmlspecialchars($data['tipe']) . '</div>
    <div><span class="label">Kapasitas</span>: ' . htmlspecialchars($data['kapasitas']) . '</div>
    <div><span class="label">No. Seri</span>: ' . htmlspecialchars($data['no_seri']) . '</div>
    <div><span class="label">Tahun Beli</span>: ' . htmlspecialchars($data['tahun_beli']) . '</div>
  </div>

  <div class="kendala"><strong>Kondisi Fisik:</strong><br>' . nl2br(htmlspecialchars($data['kondisi_fisik'])) . '</div>
  <div class="kendala"><strong>Fungsi Perangkat:</strong><br>' . nl2br(htmlspecialchars($data['fungsi_perangkat'])) . '</div>
  <div class="catatan"><strong>Catatan:</strong><br>' . (!empty($data['catatan']) ? nl2br(htmlspecialchars($data['catatan'])) : '-') . '</div>

  <div class="box-merah">
    🔧 MAINTENANCE RUTIN BERIKUTNYA: ' . $tanggal_berikutnya . '
  </div>
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// Ukuran A4 portrait, agar tidak terpotong dan tetap muat
$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

// Tambahkan watermark transparan
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(0.07);

$imagePath = 'assets/watermark.jpg';
if (file_exists($imagePath)) {
    $width = 400;
    $height = 200;
    $x = ($canvas->get_width() - $width) / 2;
    $y = ($canvas->get_height() - $height) / 2;
    $canvas->image($imagePath, $x, $y, $width, $height);
}

$dompdf->stream('kartu_maintenance_ac_' . $data['id'] . '.pdf', ['Attachment' => false]);
?>
