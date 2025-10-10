<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

session_start(); // Pastikan session aktif
include 'koneksi.php';

// Ambil data user login
$user_id = $_SESSION['user_id'] ?? 0;
$q_user = $conn->query("SELECT nama FROM users WHERE id='$user_id' LIMIT 1");
$user_data = $q_user->fetch_assoc();
$nama_user = $user_data['nama'] ?? 'Administrator';

// Ambil data perusahaan (kop surat)
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// Ambil semua data AC
$data_ac = $conn->query("SELECT * FROM data_barang_ac ORDER BY waktu_input DESC");

// === HTML + CSS ===
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { font-family: "Segoe UI", Tahoma, sans-serif; font-size: 10px; color: #333; }
.header { text-align: center; margin-bottom: 15px; }
.header .nama-perusahaan { font-size: 14px; font-weight: bold; text-transform: uppercase; }
.header .alamat { font-size: 9px; color: #555; }
.title { text-align: center; font-size: 12px; font-weight: bold; margin: 10px 0; text-transform: uppercase; color: #222; }
table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
th, td { border: 1px solid #ccc; padding: 4px; vertical-align: top; font-size: 9px; }
th { background-color: #f2f2f2; text-align: center; }
tr:nth-child(even) { background-color: #fafafa; }
.text-center { text-align: center; }
.footer { margin-top: 50px; text-align: center; font-size: 10px; }
</style>

<div class="header">
  <div class="nama-perusahaan">'.htmlspecialchars($perusahaan['nama_perusahaan']).'</div>
  <div class="alamat">'.htmlspecialchars($perusahaan['alamat']).', '.htmlspecialchars($perusahaan['kota']).', '.htmlspecialchars($perusahaan['provinsi']).'<br>
  Telp: '.htmlspecialchars($perusahaan['kontak']).' | Email: '.htmlspecialchars($perusahaan['email']).'</div>
</div>

<div class="title">Laporan Data Barang AC Ruangan</div>

<table>
<thead>
<tr>
  <th>No</th>
  <th>Kode AC</th>
  <th>Merk</th>
  <th>Tipe</th>
  <th>Kapasitas</th>
  <th>No Seri</th>
  <th>Tahun Beli</th>
  <th>Lokasi</th>
  <th>Kondisi</th>
  <th>Status</th>
</tr>
</thead>
<tbody>';

$no = 1;
if ($data_ac->num_rows > 0) {
    while ($row = $data_ac->fetch_assoc()) {
        $html .= '<tr>
            <td class="text-center">'.$no.'</td>
            <td>'.htmlspecialchars($row['kode_ac']).'</td>
            <td>'.htmlspecialchars($row['merk']).'</td>
            <td>'.htmlspecialchars($row['tipe']).'</td>
            <td>'.htmlspecialchars($row['kapasitas']).'</td>
            <td>'.htmlspecialchars($row['no_seri']).'</td>
            <td>'.htmlspecialchars($row['tahun_beli']).'</td>
            <td>'.htmlspecialchars($row['lokasi']).'</td>
            <td>'.htmlspecialchars($row['kondisi']).'</td>
            <td>'.htmlspecialchars($row['status']).'</td>
        </tr>';
        $no++;
    }
} else {
    $html .= '<tr><td colspan="11" class="text-center">Tidak ada data ditemukan.</td></tr>';
}

$html .= '</tbody></table>';

// Footer / tanda tangan di tengah
$html .= '
<div class="footer">
Dibuat oleh:<br><br><br><br>
<u>'.htmlspecialchars($nama_user).'</u><br>
'.date("d-m-Y H:i").'
</div>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('laporan_barang_ac.pdf', ['Attachment' => false]);
?>
