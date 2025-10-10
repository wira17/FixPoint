<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';

function tgl_indo($tanggal, $jam = false) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
             'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    $waktu = $jam ? ' ' . date('H:i', strtotime($tanggal)) : '';
    return "$tgl $bln $thn$waktu";
}

if (!isset($_GET['tiket_id'])) {
    die('ID tiket tidak ditemukan.');
}

$tiket_id = intval($_GET['tiket_id']);

// Ambil data berita acara + tiket + user
$q = mysqli_query($conn, "
    SELECT 
        ba.nomor_ba,
        ba.teknisi,
        ba.catatan_teknisi,
        ba.tanggal_ba,
        ba.nomor_tiket,
        ba.kendala,
        ba.kategori,
        u.id AS user_id, 
        u.nik AS user_nik, 
        u.nama AS user_nama, 
        u.jabatan AS user_jabatan, 
        u.unit_kerja AS user_unit, 
        u.atasan_id
    FROM berita_acara ba
    JOIN tiket_it_hardware t ON ba.tiket_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = '$tiket_id'
");

if (!$q) {
    die('Query error: ' . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($q);

if (!$data) {
    die('Data tidak ditemukan.');
}

// Ambil data perusahaan
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// Path absolut logo (ubah ke base64 agar tampil di PDF)
$logoPath = realpath('dist/images/logo/' . $perusahaan['logo']);
$logoBase64 = '';

if ($logoPath && file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
}

// Ambil data teknisi (berdasarkan nama di tabel berita_acara)
$teknisi = ['nama' => $data['teknisi'], 'nik' => '-'];
$q_teknisi = mysqli_query($conn, "SELECT nik FROM users WHERE nama = '" . mysqli_real_escape_string($conn, $data['teknisi']) . "' LIMIT 1");
if ($q_teknisi && $row = mysqli_fetch_assoc($q_teknisi)) {
    $teknisi['nik'] = $row['nik'];
}

// Ambil data atasan
$atasan = ['nama' => '-', 'jabatan' => '-', 'nik' => '-'];
if (!empty($data['atasan_id'])) {
    $q_atasan = mysqli_query($conn, "SELECT nama, jabatan, nik FROM users WHERE id = " . intval($data['atasan_id']));
    if ($row = mysqli_fetch_assoc($q_atasan)) {
        $atasan = $row;
    }
}

$tgl_ba = tgl_indo($data['tanggal_ba']);
$tgl_tiket = isset($data['tanggal_input']) ? tgl_indo($data['tanggal_input'], true) : '-';

// HTML isi dokumen
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
  .kop img { width: 80px; }
  .kop .nama-perusahaan { font-size: 16px; font-weight: bold; text-transform: uppercase; }
  .kop .alamat { font-size: 12px; }
  hr { border: 1px solid #000; margin: 10px 0; }
  .judul { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin-top: 20px; }
  .nomor { text-align: center; margin-bottom: 20px; font-size: 12px; }
  .isi { text-align: justify; margin-top: 20px; }
  .info-table td { vertical-align: top; padding: 3px; }
  .signature { margin-top: 50px; width: 100%; text-align: center; }
</style>

<div class="kop" style="text-align: center;">
  <img src="' . $logoBase64 . '" alt="Logo"><br>
  <div class="nama-perusahaan">' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</div>
  <div class="alamat">' . htmlspecialchars($perusahaan['alamat']) . ', ' . htmlspecialchars($perusahaan['kota']) . ', ' . htmlspecialchars($perusahaan['provinsi']) . '<br>
  Telp: ' . htmlspecialchars($perusahaan['kontak']) . ' | Email: ' . htmlspecialchars($perusahaan['email']) . '</div>
</div>

<hr>

<div class="judul">BERITA ACARA PENANGANAN TIKET IT HARDWARE</div>
<div class="nomor">
  Nomor BA: <strong>' . htmlspecialchars($data['nomor_ba']) . '</strong><br>
</div>

<div class="isi">
  Pada hari ini, tanggal <strong>' . $tgl_ba . '</strong>, bertempat di lingkungan kerja <strong>' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</strong>, telah dilakukan penanganan atas kendala IT Hardware dengan rincian sebagai berikut:
</div>

<table class="info-table" style="margin-top: 15px;">
  <tr><td width="150">Nomor Tiket</td><td>: '. htmlspecialchars($data['nomor_tiket']) . '</td></tr>
  <tr><td>Tanggal Tiket</td><td>: ' . $tgl_tiket . ' WIB</td></tr>
  <tr><td>NIK / Nama</td><td>: ' . htmlspecialchars($data['user_nik']) . ' / ' . htmlspecialchars($data['user_nama']) . '</td></tr>
  <tr><td>Jabatan</td><td>: ' . htmlspecialchars($data['user_jabatan']) . '</td></tr>
  <tr><td>Unit Kerja</td><td>: ' . htmlspecialchars($data['user_unit']) . '</td></tr>
  <tr><td>Kategori</td><td>: ' . htmlspecialchars($data['kategori']) . '</td></tr>
</table>

<div class="isi" style="margin-top: 15px;">
  <strong>Deskripsi Kendala:</strong><br>' . nl2br(htmlspecialchars($data['kendala'])) . '
</div>

<div class="isi" style="margin-top: 15px;">
  <strong>Catatan Teknisi:</strong><br>' . nl2br(htmlspecialchars($data['catatan_teknisi'])) . '
</div>

<div class="isi" style="margin-top: 20px;">
  Demikian berita acara ini dibuat dengan sebenar-benarnya, untuk digunakan sebagaimana mestinya.
</div>

<table class="signature" style="width: 100%; text-align: center; margin-top: 50px;">
  <tr>
    <td style="width: 33%;">Teknisi</td>
    <td style="width: 33%;">Yang Melapor</td>
    <td style="width: 33%;">Mengetahui,<br>Atasan Langsung</td>
  </tr>
  <tr><td colspan="3" style="height: 60px;"></td></tr>

  <tr>
    <td>
      <strong><u>' . htmlspecialchars($teknisi['nama']) . '</u></strong><br>
      ' . htmlspecialchars($teknisi['nik']) . '
    </td>
    <td>
      <strong><u>' . htmlspecialchars($data['user_nama']) . '</u></strong><br>
      ' . htmlspecialchars($data['user_nik']) . '
    </td>
    <td>
      <strong><u>' . htmlspecialchars($atasan['nama']) . '</u></strong><br>
      ' . htmlspecialchars($atasan['nik']) . '
    </td>
  </tr>
</table>
';

// Inisialisasi Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Watermark
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(0.07);
$watermarkPath = 'assets/watermark.jpg';
if (file_exists($watermarkPath)) {
    $width = 500;
    $height = 300;
    $x = ($canvas->get_width() - $width) / 2;
    $y = ($canvas->get_height() - $height) / 4;
    $canvas->image($watermarkPath, $x, $y, $width, $height);
}

// Output file
$dompdf->stream('berita_acara_' . $data['nomor_tiket'] . '.pdf', ['Attachment' => false]);
?>
