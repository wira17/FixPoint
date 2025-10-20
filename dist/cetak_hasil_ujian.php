<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

function tgl_indo($tanggal, $jam = false) {
    $bulan = [
        1 => 'Januari','Februari','Maret','April','Mei','Juni',
             'Juli','Agustus','September','Oktober','November','Desember'
    ];
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    $waktu = $jam ? ' ' . date('H:i', strtotime($tanggal)) : '';
    return "$tgl $bln $thn$waktu";
}

if (!isset($_GET['user_id']) || !isset($_GET['judul_soal_id'])) {
    die('<h3 style="color:red;text-align:center;">❌ Parameter tidak lengkap.</h3>');
}

$user_id = intval($_GET['user_id']);
$judul_soal_id = intval($_GET['judul_soal_id']);

// Ambil data peserta
$qUser = mysqli_query($conn, "SELECT nama, email FROM users WHERE id='$user_id'");
if (!$qUser || mysqli_num_rows($qUser) == 0) {
    die('<h3 style="color:red;text-align:center;">❌ Data user tidak ditemukan.</h3>');
}
$user = mysqli_fetch_assoc($qUser);

// Ambil judul soal
$qJudul = mysqli_query($conn, "SELECT judul_soal FROM judul_soal WHERE id='$judul_soal_id'");
if (!$qJudul || mysqli_num_rows($qJudul) == 0) {
    die('<h3 style="color:red;text-align:center;">❌ Judul soal tidak ditemukan.</h3>');
}
$judul = mysqli_fetch_assoc($qJudul);

// Ambil jawaban peserta
$qJawaban = mysqli_query($conn, "
    SELECT s.soal, s.pilihan_a, s.pilihan_b, s.pilihan_c, s.pilihan_d,
           s.jawaban_benar, j.jawaban, j.tanggal_ujian
    FROM jawaban_ujian j
    JOIN soal s ON j.soal_id = s.id
    WHERE j.user_id='$user_id' AND j.judul_soal_id='$judul_soal_id'
    ORDER BY j.id ASC
");
if (!$qJawaban || mysqli_num_rows($qJawaban) == 0) {
    die('<h3 style="color:orange;text-align:center;">⚠️ Belum ada jawaban tersimpan untuk ujian ini.</h3>');
}

// Data instansi
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

$logoPath = realpath('dist/images/logo/' . ($perusahaan['logo'] ?? ''));
$logoBase64 = '';
if ($logoPath && file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
}

// Hitung nilai
$total = 0;
$benar = 0;
$tanggalUjian = '';
$tbody = '';
$no = 1;

while ($row = mysqli_fetch_assoc($qJawaban)) {
    $total++;
    $tanggalUjian = $row['tanggal_ujian'];
    $status = (strtolower(trim($row['jawaban'])) == strtolower(trim($row['jawaban_benar']))) ? 'Benar' : 'Salah';
    if ($status == 'Benar') $benar++;

    // Highlight baris jawaban salah
    $bgcolor = ($status == 'Salah') ? 'background-color:#f8d7da;' : '';

    $tbody .= "
    <tr style='$bgcolor'>
        <td style='text-align:center;'>$no</td>
        <td>{$row['soal']}</td>
        <td style='text-align:center;'><b>" . strtoupper($row['jawaban']) . "</b></td>
        <td style='text-align:center;'>" . strtoupper($row['jawaban_benar']) . "</td>
        <td style='text-align:center;'>$status</td>
    </tr>";
    $no++;
}

$nilai = $total > 0 ? round(($benar / $total) * 100, 2) : 0;
$statusUjian = ($nilai >= 75) ? 'Lulus' : 'Remedial';
$statusColor = ($nilai >= 75) ? 'green' : 'red';
$tglUjian = tgl_indo($tanggalUjian, true);

// HTML
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
  .kop img { width: 80px; }
  .kop .nama-perusahaan { font-size: 16px; font-weight: bold; text-transform: uppercase; }
  .kop .alamat { font-size: 12px; }
  hr { border: 1px solid #000; margin: 10px 0; }
  .judul { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin-top: 20px; }
  .info-table td { vertical-align: top; padding: 4px; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid black; padding: 5px; }
  th { background-color: #f2f2f2; }
  .status-ujian { font-weight: bold; color: '.$statusColor.'; }
  .footer { margin-top: 30px; text-align: right; font-size: 11px; }
</style>

<div class="kop" style="text-align: center;">
  '.($logoBase64 ? '<img src="'.$logoBase64.'" alt="Logo"><br>' : '').'
  <div class="nama-perusahaan">'.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Instansi Anda').'</div>
  <div class="alamat">'.htmlspecialchars($perusahaan['alamat'] ?? '-').', '.htmlspecialchars($perusahaan['kota'] ?? '').', '.htmlspecialchars($perusahaan['provinsi'] ?? '').'<br>
  Telp: '.htmlspecialchars($perusahaan['kontak'] ?? '-').' | Email: '.htmlspecialchars($perusahaan['email'] ?? '-').'</div>
</div>

<hr>

<div class="judul">HASIL UJIAN TERTULIS</div>

<table class="info-table" style="margin-top: 15px;">
  <tr><td width="150">Nama Peserta</td><td>: '.htmlspecialchars($user['nama']).'</td></tr>
  <tr><td>Email</td><td>: '.htmlspecialchars($user['email']).'</td></tr>
  <tr><td>Judul Soal</td><td>: '.htmlspecialchars($judul['judul_soal']).'</td></tr>
  <tr><td>Tanggal Ujian</td><td>: '.$tglUjian.' WIB</td></tr>
  <tr><td>Jumlah Soal</td><td>: '.$total.'</td></tr>
  <tr><td>Benar</td><td>: '.$benar.'</td></tr>
  <tr><td>Nilai Akhir</td><td><b>'.$nilai.'%</b> <span class="status-ujian">('.$statusUjian.')</span></td></tr>
</table>

<h4 style="margin-top:20px;">Detail Jawaban:</h4>
<table>
  <thead>
    <tr>
      <th style="width:30px;">No</th>
      <th>Soal</th>
      <th style="width:90px;">Jawaban Peserta</th>
      <th style="width:90px;">Jawaban Benar</th>
      <th style="width:70px;">Hasil</th>
    </tr>
  </thead>
  <tbody>'.$tbody.'</tbody>
</table>

<div class="footer">
Dicetak pada: '.tgl_indo(date("Y-m-d H:i:s"), true).'
</div>
';

// Generate PDF
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

// Output ke browser
$dompdf->stream('hasil_ujian_' . $user['nama'] . '.pdf', ['Attachment' => false]);
?>
