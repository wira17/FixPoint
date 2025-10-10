<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Ambil filter dari GET
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

// Format tanggal ke Y-m-d
if ($dari_tanggal) $dari_tanggal = date('Y-m-d', strtotime($dari_tanggal));
if ($sampai_tanggal) $sampai_tanggal = date('Y-m-d', strtotime($sampai_tanggal));

// Ambil data perusahaan (kop surat)
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// Query data tiket
$query = "SELECT * FROM tiket_it_software WHERE 1=1";
if (!empty($keyword)) {
  $kw = mysqli_real_escape_string($conn, $keyword);
  $query .= " AND (nik LIKE '%$kw%' OR nama LIKE '%$kw%' OR nomor_tiket LIKE '%$kw%')";
}
if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
  $query .= " AND DATE(tanggal_input) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
}
$query .= " ORDER BY tanggal_input DESC";
$result = $conn->query($query);

// Fungsi bantu
function formatTanggal($tanggal) {
  return $tanggal ? date('d-m-Y H:i', strtotime($tanggal)) : '-';
}
function hitungDurasi($mulai, $selesai) {
  if (!$mulai || !$selesai) return '-';
  $start = new DateTime($mulai);
  $end = new DateTime($selesai);
  $interval = $start->diff($end);
  $jam = $interval->h + ($interval->days * 24);
  $menit = $interval->i;
  return "{$jam}j {$menit}m";
}

// HTML untuk PDF
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { font-family: "Segoe UI", Tahoma, sans-serif; font-size: 11px; color: #333; }
.header { text-align: center; margin-bottom: 10px; }
.header .nama { font-size: 16px; font-weight: bold; text-transform: uppercase; }
.header .alamat { font-size: 10px; color: #555; }
.title { text-align: center; font-size: 14px; font-weight: bold; margin: 15px 0; text-transform: uppercase; color: #222; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 5px; font-size: 10px; vertical-align: top; }
th { background-color: #f2f2f2; text-align: center; }
tr:nth-child(even) { background-color: #fafafa; }
</style>

<div class="header">
  <div class="nama">'.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'RS Permata Hati Bungo').'</div>
  <div class="alamat">'.htmlspecialchars($perusahaan['alamat'] ?? 'Alamat tidak tersedia').'<br>
  Telp: '.htmlspecialchars($perusahaan['kontak'] ?? '-').' | Email: '.htmlspecialchars($perusahaan['email'] ?? '-').'</div>
</div>

<div class="title">Laporan Handling Time IT Software</div>

<table>
<tr>
  <th>No</th>
  <th>Nomor Tiket</th>
  <th>NIK</th>
  <th>Nama</th>
  <th>Unit Kerja</th>
  <th>Kategori</th>
  <th>Kendala</th>
  <th>Status</th>
  <th>IT</th>
  <th>Order</th>
  <th>Selesai</th>
  <th>Lama</th>
</tr>';

$no = 1;
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
      <td align="center">'.$no.'</td>
      <td>'.htmlspecialchars($row['nomor_tiket']).'</td>
      <td>'.htmlspecialchars($row['nik']).'</td>
      <td>'.htmlspecialchars($row['nama']).'</td>
      <td>'.htmlspecialchars($row['unit_kerja']).'</td>
      <td>'.htmlspecialchars($row['kategori']).'</td>
      <td>'.htmlspecialchars($row['kendala']).'</td>
      <td align="center">'.htmlspecialchars($row['status']).'</td>
      <td>'.htmlspecialchars($row['teknisi_nama']).'</td>
      <td>'.formatTanggal($row['tanggal_input']).'</td>
      <td>'.formatTanggal($row['waktu_selesai']).'</td>
      <td>'.hitungDurasi($row['tanggal_input'], $row['waktu_selesai']).'</td>
    </tr>';
    $no++;
  }
} else {
  $html .= '<tr><td colspan="18" align="center">Tidak ada data ditemukan.</td></tr>';
}

$html .= '</table>
<br><br>
<small><i>Dicetak pada: '.date('d-m-Y H:i').'</i></small>';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // landscape agar tabel muat
$dompdf->render();
$dompdf->stream('laporan_handling_time_software.pdf', ['Attachment' => false]);
?>
