<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';

if (!isset($_GET['id'])) {
    die('ID tiket tidak ditemukan.');
}

$id = intval($_GET['id']);

// Ambil data tiket Sarpras
$query = mysqli_query($conn, "SELECT t.*, u.nik, u.nama, u.jabatan, u.unit_kerja
                              FROM tiket_sarpras t
                              JOIN users u ON t.user_id = u.id
                              WHERE t.id = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die('Data tiket tidak ditemukan.');
}

// Ambil info perusahaan (untuk header)
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
  .ticket {
    border: 2px dashed #000;
    padding: 15px;
    width: 100%;
    box-sizing: border-box;
    position: relative;
  }
  .header { text-align: center; margin-bottom: 10px; }
  .header .nama-perusahaan { font-size: 14px; font-weight: bold; text-transform: uppercase; }
  .header .alamat { font-size: 10px; }
  .title { text-align: center; font-size: 13px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
  .info { margin: 10px 0; }
  .info div { margin-bottom: 4px; }
  .label { display: inline-block; width: 100px; font-weight: bold; }
  .kendala, .catatan { margin-top: 10px; font-size: 10px; }
  .footer { margin-top: 20px; text-align: right; font-size: 10px; }
  .footer .label { font-weight: bold; }
  .footer .value { margin-top: 30px; text-decoration: underline; }
</style>

<div class="ticket">
  <div class="header">
    <div class="nama-perusahaan">' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</div>
    <div class="alamat">' . htmlspecialchars($perusahaan['alamat']) . ', ' . htmlspecialchars($perusahaan['kota']) . ', ' . htmlspecialchars($perusahaan['provinsi']) . '<br>
    Telp: ' . htmlspecialchars($perusahaan['kontak']) . ' | Email: ' . htmlspecialchars($perusahaan['email']) . '</div>
  </div>

  <div class="title">TIKET SARPRAS</div>

  <div class="info">
    <div><span class="label">No. Tiket</span>: ' . $data['nomor_tiket'] . '</div>
    <div><span class="label">Tanggal</span>: ' . date('d-m-Y H:i', strtotime($data['tanggal_input'])) . '</div>
    <div><span class="label">NIK</span>: ' . $data['nik'] . '</div>
    <div><span class="label">Nama</span>: ' . $data['nama'] . '</div>
    <div><span class="label">Jabatan</span>: ' . $data['jabatan'] . '</div>
    <div><span class="label">Unit</span>: ' . $data['unit_kerja'] . '</div>
    <div><span class="label">Kategori</span>: ' . $data['kategori'] . '</div>
    <div><span class="label">Status</span>: ' . ucfirst($data['status']) . '</div>
    <div><span class="label">Validasi</span>: ' . $data['status_validasi'] . '</div>
  </div>

  <div class="kendala"><strong>Kendala:</strong><br>' . nl2br(htmlspecialchars($data['kendala'])) . '</div>

  <div class="catatan"><strong>Catatan Teknisi:</strong><br>' . (!empty($data['catatan_it']) ? nl2br(htmlspecialchars($data['catatan_it'])) : '-') . '</div>

  <div class="info">
    <div><span class="label">Teknisi</span>: ' . (!empty($data['teknisi_nama']) ? htmlspecialchars($data['teknisi_nama']) : '________________________') . '</div>
  </div>
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'landscape');
$dompdf->render();

// Tambahkan watermark (opsional)
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(0.07); 

$imagePath = 'assets/watermark.jpg';
if (file_exists($imagePath)) {
    $width = 700;  
    $height = 300;
    $x = ($canvas->get_width() - $width) / 2;
    $y = ($canvas->get_height() - $height) / 2;
    $canvas->image($imagePath, $x, $y, $width, $height);
}

$dompdf->stream('tiket_' . $data['nomor_tiket'] . '.pdf', ['Attachment' => false]);
?>