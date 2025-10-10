<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';
session_start();

// Ambil filter dari GET
$nama_filter = $_GET['nama'] ?? '';
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

// Build WHERE clause
$where = "WHERE 1 ";
if(!empty($nama_filter)){
    $where .= "AND u.nama LIKE '%".mysqli_real_escape_string($conn,$nama_filter)."%' ";
}
if(!empty($dari)){
    $where .= "AND a.tanggal >= '$dari' ";
}
if(!empty($sampai)){
    $where .= "AND a.tanggal <= '$sampai' ";
}

// Ambil data absensi
$sql = "SELECT a.*, u.nama 
        FROM absensi a 
        LEFT JOIN users u ON a.user_id = u.id 
        $where
        ORDER BY a.created_at DESC";
$result = mysqli_query($conn, $sql);

// Ambil data perusahaan (kop surat)
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// HTML untuk PDF
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { font-family: "Segoe UI", Tahoma, sans-serif; font-size: 11px; color: #333; }
.header { text-align: center; margin-bottom: 15px; }
.header .nama-perusahaan { font-size: 16px; font-weight: bold; text-transform: uppercase; }
.header .alamat { font-size: 10px; color: #555; }
.title { text-align: center; font-size: 14px; font-weight: bold; margin: 15px 0; text-transform: uppercase; color: #222; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
th, td { border: 1px solid #ccc; padding: 6px; vertical-align: top; font-size: 11px; }
th { background-color: #f2f2f2; text-align: left; }
tr:nth-child(even) { background-color: #fafafa; }
.signature { width:100%; margin-top:40px; text-align:center; }
.sig-col { width:45%; display:inline-block; vertical-align:top; }
.sig-space { height:70px; }
</style>

<div class="header">
  <div class="nama-perusahaan">'.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'PERUSAHAAN').'</div>
  <div class="alamat">'.htmlspecialchars($perusahaan['alamat'] ?? '').', '
  .htmlspecialchars($perusahaan['kota'] ?? '').', '
  .htmlspecialchars($perusahaan['provinsi'] ?? '').'<br>
  Telp: '.htmlspecialchars($perusahaan['kontak'] ?? '').' | Email: '.htmlspecialchars($perusahaan['email'] ?? '').'</div>
</div>

<div class="title">Rekap Data Absensi</div>

<table>
<tr>
<th>No</th>
<th>Nama</th>
<th>Tanggal</th>
<th>Jam Masuk</th>
<th>Jam Keluar</th>
<th>Istirahat Masuk</th>
<th>Istirahat Keluar</th>
<th>Status</th>
</tr>';

$no = 1;
while($row = mysqli_fetch_assoc($result)){
    $html .= '<tr>
        <td>'.$no++.'</td>
        <td>'.htmlspecialchars($row['nama']).'</td>
        <td>'.htmlspecialchars($row['tanggal']).'</td>
        <td>'.htmlspecialchars($row['jam_masuk'] ?? '-').'</td>
        <td>'.htmlspecialchars($row['jam_keluar'] ?? '-').'</td>
        <td>'.htmlspecialchars($row['istirahat_masuk'] ?? '-').'</td>
        <td>'.htmlspecialchars($row['istirahat_keluar'] ?? '-').'</td>
        <td>'.ucfirst(str_replace('_',' ',$row['status'])).'</td>
    </tr>';
}
$html .= '</table>

<div class="signature">
  <div class="sig-col">
    <strong>HRD/Kepegawaian</strong><br><br>
    <div class="sig-space"></div>
    <u>'.htmlspecialchars($_SESSION['nama'] ?? 'Admin').'</u>
  </div>
</div>
';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('rekap_absensi.pdf', ['Attachment'=>false]);
