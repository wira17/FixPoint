<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';

// === Ambil ID pengajuan ===
if (!isset($_GET['id'])) die('ID pengajuan tidak ditemukan.');
$id = intval($_GET['id']);

// === Data perusahaan (kop surat) ===
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// === Query data pengajuan ganti jadwal ===
$sql = "SELECT p.*, u.nik, u.nama, u.unit_kerja, u.jabatan,
               d.nama AS nama_pengganti,
               j.nama_jam,
               DATE_FORMAT(p.tanggal,'%d-%m-%Y') AS tanggal_ganti
        FROM pengajuan_ganti_jadwal p
        JOIN users u ON p.karyawan_id = u.id
        LEFT JOIN users d ON p.pengganti_id = d.id
        LEFT JOIN jam_kerja j ON p.jam_kerja_id = j.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data tidak ditemukan!");

// === Siapkan nama tanda tangan ===
$pemohon    = $data['nama'] ?: '........................';
$pengganti  = $data['nama_pengganti'] ?: '........................';
$atasan     = $data['acc_atasan_by'] ?? '........................';
$hrd        = $data['acc_hrd_by'] ?? '........................';

// === HTML untuk PDF berbentuk surat ===
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.6; color: #000; }
.header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 20px; }
.header .nama-perusahaan { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
.header .alamat { font-size: 10pt; color: #333; }
.content { margin-top: 20px; text-align: justify; }
.ttd { margin-top: 50px; width: 100%; border-collapse: collapse; }
.ttd td { text-align: center; vertical-align: bottom; height: 80px; width: 25%; }
</style>

<div class="header">
  <div class="nama-perusahaan">'.htmlspecialchars($perusahaan['nama_perusahaan']).'</div>
  <div class="alamat">'.htmlspecialchars($perusahaan['alamat']).', '.htmlspecialchars($perusahaan['kota']).', '.htmlspecialchars($perusahaan['provinsi']).'<br>
  Telp: '.htmlspecialchars($perusahaan['kontak']).' | Email: '.htmlspecialchars($perusahaan['email']).'</div>
</div>

<div style="text-align:right; margin-bottom:20px;">
'.htmlspecialchars($perusahaan['kota']).', '.date("d F Y").'
</div>

<div class="content">
Kepada Yth,<br>
HRD '.htmlspecialchars($perusahaan['nama_perusahaan']).'<br>
di Tempat
<br><br>

Dengan hormat,<br>
Saya yang bertanda tangan di bawah ini:<br><br>

Nama&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.htmlspecialchars($data['nama']).'<br>
NIK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.htmlspecialchars($data['nik']).'<br>
Jabatan&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.htmlspecialchars($data['jabatan'] ?? "-").'<br>
Unit Kerja&nbsp;&nbsp;: '.htmlspecialchars($data['unit_kerja']).'<br><br>

Mengajukan penggantian jadwal kerja pada tanggal <b>'.htmlspecialchars($data['tanggal_ganti']).'</b> 
pada jam kerja <b>'.htmlspecialchars($data['nama_jam']).'</b>.<br><br>

Alasan: '.htmlspecialchars($data['alasan']).'.<br><br>

Tugas/gantian dijalankan oleh: <b>'.htmlspecialchars($pengganti).'</b>.<br><br>

Demikian permohonan ini saya ajukan, atas perhatian dan persetujuannya saya ucapkan terima kasih.
</div>

<table class="ttd">
<tr>
  <td>Pemohon,<br><br><br><br><u>'.htmlspecialchars($pemohon).'</u></td>
  <td>Pengganti,<br><br><br><br><u>'.htmlspecialchars($pengganti).'</u></td>
</tr>
</table>

<div style="margin-top:30px; font-size:10pt; color:#555;">
Dicetak pada: '.date("d-m-Y H:i").'
</div>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('ganti_jadwal_'.$data['id'].'.pdf', ['Attachment' => false]);
?>
