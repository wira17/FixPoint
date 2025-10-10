<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';

// Ambil filter
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

if ($dari_tanggal) $dari_tanggal = date('Y-m-d', strtotime($dari_tanggal));
if ($sampai_tanggal) $sampai_tanggal = date('Y-m-d', strtotime($sampai_tanggal));

// Ambil data perusahaan (kop surat)
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// Query data
$query = "SELECT * FROM tiket_it_hardware WHERE 1=1";
if (!empty($keyword)) {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $query .= " AND (nik LIKE '%$kw%' OR nama LIKE '%$kw%' OR nomor_tiket LIKE '%$kw%')";
}
if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
    $query .= " AND DATE(tanggal_input) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
}
$query .= " ORDER BY tanggal_input DESC";
$result = $conn->query($query);

// Fungsi format tanggal & durasi
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

// === HTML + CSS ===
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { font-family: "Segoe UI", Tahoma, sans-serif; font-size: 11px; color: #333; }
.header { text-align: center; margin-bottom: 15px; }
.header .nama-perusahaan { font-size: 16px; font-weight: bold; text-transform: uppercase; }
.header .alamat { font-size: 10px; color: #555; }
.title { text-align: center; font-size: 13px; font-weight: bold; margin: 15px 0; text-transform: uppercase; color: #222; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
th, td { border: 1px solid #ccc; padding: 6px; vertical-align: top; font-size: 10px; }
th { background-color: #f2f2f2; text-align: center; }
tr:nth-child(even) { background-color: #fafafa; }
</style>

<div class="header">
  <div class="nama-perusahaan">'.htmlspecialchars($perusahaan['nama_perusahaan']).'</div>
  <div class="alamat">'.htmlspecialchars($perusahaan['alamat']).', '.htmlspecialchars($perusahaan['kota']).', '.htmlspecialchars($perusahaan['provinsi']).'<br>
  Telp: '.htmlspecialchars($perusahaan['kontak']).' | Email: '.htmlspecialchars($perusahaan['email']).'</div>
</div>

<div class="title">Laporan Handling Time Tiket IT Hardware</div>

<table>
<thead>
<tr>
  <th>No</th>
  <th>No Tiket</th>
  <th>NIK</th>
  <th>Nama</th>
  <th>Unit Kerja</th>
  <th>Kategori</th>
  <th>Kendala</th>
  <th>Status</th>
  <th>Petugas</th>
  <th>Order</th>
  <th>Selesai</th>
  <th>Lama</th>
</tr>
</thead>
<tbody>';

$no = 1;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
            <td class="text-center">'.$no.'</td>
            <td>'.htmlspecialchars($row['nomor_tiket']).'</td>
            <td>'.htmlspecialchars($row['nik']).'</td>
            <td>'.htmlspecialchars($row['nama']).'</td>
            <td>'.htmlspecialchars($row['unit_kerja']).'</td>
            <td>'.htmlspecialchars($row['kategori']).'</td>
            <td>'.htmlspecialchars($row['kendala']).'</td>
            <td>'.htmlspecialchars(ucfirst($row['status'])).'</td>
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
$html .= '</tbody></table>';

// === Buat PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('handling_time_hardware.pdf', ['Attachment' => false]);
?>
