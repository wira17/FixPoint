<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';

if (!isset($_GET['id'])) {
    die('ID izin tidak ditemukan.');
}

$id = intval($_GET['id']);

// Ambil data izin
$query = mysqli_query($conn, "
    SELECT i.*, u.nik, u.nama, u.jabatan, u.unit_kerja 
    FROM izin_keluar i
    JOIN users u ON i.user_id = u.id
    WHERE i.id = '$id'
");
$data = mysqli_fetch_assoc($query);
if (!$data) {
    die('Data izin tidak ditemukan.');
}

// Ambil data perusahaan
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// === Data Pemohon ===
$ttd_pemohon = '';
if (!empty($data['nik'])) {
    $q_pemohon = mysqli_query($conn, "SELECT ttd FROM users WHERE nik = '{$data['nik']}' LIMIT 1");
    if ($pemohon = mysqli_fetch_assoc($q_pemohon)) {
        $ttd_pemohon = !empty($pemohon['ttd']) ? 'ttd/' . $pemohon['ttd'] : '';
    }
}

// === Data SDM ===
$nama_sdm = '________________';
$nik_sdm = '';
$ttd_sdm = '';

if (!empty($data['acc_oleh_sdm'])) {
    $q_sdm = mysqli_query($conn, "SELECT nama, nik, ttd FROM users WHERE id = '{$data['acc_oleh_sdm']}' LIMIT 1");
    if ($sdm = mysqli_fetch_assoc($q_sdm)) {
        $nama_sdm = htmlspecialchars($sdm['nama']);
        $nik_sdm = htmlspecialchars($sdm['nik']);
        $ttd_sdm = !empty($sdm['ttd']) ? 'ttd/' . $sdm['ttd'] : '';
    }
}

// === Data Atasan ===
$nama_atasan = '________________';
$nik_atasan = '';
$ttd_atasan = '';

if (!empty($data['acc_oleh_atasan'])) {
    $q_atasan = mysqli_query($conn, "SELECT nama, nik, ttd FROM users WHERE id = '{$data['acc_oleh_atasan']}' LIMIT 1");
    if ($atasan = mysqli_fetch_assoc($q_atasan)) {
        $nama_atasan = htmlspecialchars($atasan['nama']);
        $nik_atasan = htmlspecialchars($atasan['nik']);
        $ttd_atasan = !empty($atasan['ttd']) ? 'ttd/' . $atasan['ttd'] : '';
    }
}

// === Format jam kembali real
$jamKembaliRealFormatted = '-';
if (!empty($data['jam_kembali_real'])) {
    $jamKembaliRealFormatted = date('d-m-Y / H:i:s', strtotime($data['jam_kembali_real'])) . ' WIB';
}

// === Hitung lama izin keluar
$lamaIzin = '-';
$warnaDurasi = '#000';
if (!empty($data['jam_keluar']) && !empty($data['jam_kembali_real'])) {
    $jamKeluar = strtotime($data['jam_keluar']);
    $jamKembaliReal = strtotime($data['jam_kembali_real']);
    $selisihMenit = round(($jamKembaliReal - $jamKeluar) / 60);
    $jam = floor($selisihMenit / 60);
    $menit = $selisihMenit % 60;
    $lamaIzin = sprintf('%02d jam %02d menit', $jam, $menit);
    if ($selisihMenit > 60) $warnaDurasi = 'red';
}

// === CSS dan konten utama
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
  .surat { border: 2px dashed #000; padding: 15px; }
  .header { text-align: center; margin-bottom: 10px; }
  .header .nama-perusahaan { font-size: 14px; font-weight: bold; text-transform: uppercase; }
  .header .alamat { font-size: 10px; }
  .title { text-align: center; font-size: 13px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
  .info { margin: 10px 0; }
  .info div { margin-bottom: 4px; }
  .label { display: inline-block; width: 130px; font-weight: bold; }
  .ttd-table { width: 100%; text-align: center; font-size: 10px; margin-top: 40px; }
  .nama { text-decoration: underline; font-weight: bold; }
  .space { height: 60px; }
  .ttd-img { width: 80px; height: auto; margin-bottom: -15px; }
</style>

<div class="surat">
  <div class="header">
    <div class="nama-perusahaan">' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</div>
    <div class="alamat">' . htmlspecialchars($perusahaan['alamat']) . ', ' . htmlspecialchars($perusahaan['kota']) . ', ' . htmlspecialchars($perusahaan['provinsi']) . '<br>
    Telp: ' . htmlspecialchars($perusahaan['kontak']) . ' | Email: ' . htmlspecialchars($perusahaan['email']) . '</div>
  </div>

  <div class="title">SURAT IZIN KELUAR PEGAWAI</div>

  <div class="info">
    <div><span class="label">Nomor Izin</span>: IZIN/' . date('Ymd', strtotime($data['tanggal'])) . '/' . str_pad($data['id'], 3, '0', STR_PAD_LEFT) . '</div>
    <div><span class="label">Tanggal Izin</span>: ' . date('d-m-Y', strtotime($data['tanggal'])) . '</div>
    <div><span class="label">NIK</span>: ' . htmlspecialchars($data['nik']) . '</div>
    <div><span class="label">Nama</span>: ' . htmlspecialchars($data['nama']) . '</div>
    <div><span class="label">Jabatan</span>: ' . htmlspecialchars($data['jabatan']) . '</div>
    <div><span class="label">Unit Kerja</span>: ' . htmlspecialchars($data['unit_kerja']) . '</div>
    <div><span class="label">Jam Keluar</span>: ' . htmlspecialchars($data['jam_keluar']) . ' WIB</div>
    <div><span class="label">Jam Kembali</span>: ' . $jamKembaliRealFormatted . '</div>
    <div><span class="label">Lama Izin</span>: <span style="color:' . $warnaDurasi . ';">' . $lamaIzin . '</span></div>
  </div>

  <div><strong>Keperluan:</strong><br>' . nl2br(htmlspecialchars($data['keperluan'])) . '</div>
  ' . (!empty($data['keterangan_kembali']) ? '<div><strong>Keterangan:</strong><br>' . nl2br(htmlspecialchars($data['keterangan_kembali'])) . '</div>' : '') . '

  <table class="ttd-table">
    <tr>
      <td>Pemohon</td>
      <td>Mengetahui,<br>Atasan Langsung</td>
      <td>Disetujui,<br>Bagian SDM</td>
    </tr>
    <tr class="space">
      <td>' . (!empty($ttd_pemohon) && file_exists($ttd_pemohon) ? '<img src="' . $ttd_pemohon . '" class="ttd-img">' : '') . '</td>
      <td>' . (!empty($ttd_atasan) && file_exists($ttd_atasan) ? '<img src="' . $ttd_atasan . '" class="ttd-img">' : '') . '</td>
      <td>' . (!empty($ttd_sdm) && file_exists($ttd_sdm) ? '<img src="' . $ttd_sdm . '" class="ttd-img">' : '') . '</td>
    </tr>
    <tr>
      <td><div class="nama">' . htmlspecialchars($data['nama']) . '</div><div>NIK: ' . htmlspecialchars($data['nik']) . '</div></td>
      <td><div class="nama">' . $nama_atasan . '</div><div>' . (!empty($nik_atasan) ? 'NIK: ' . $nik_atasan : '&nbsp;') . '</div></td>
      <td><div class="nama">' . $nama_sdm . '</div><div>' . (!empty($nik_sdm) ? 'NIK: ' . $nik_sdm : '&nbsp;') . '</div></td>
    </tr>
  </table>
</div>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();

// Watermark opsional
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(0.08);
$imagePath = 'assets/watermark.jpg';
if (file_exists($imagePath)) {
    $width = 400; $height = 200;
    $x = ($canvas->get_width() - $width) / 2;
    $y = ($canvas->get_height() - $height) / 2;
    $canvas->image($imagePath, $x, $y, $width, $height);
}

$dompdf->stream('izin_keluar_' . $data['nik'] . '.pdf', ['Attachment' => false]);
?>
