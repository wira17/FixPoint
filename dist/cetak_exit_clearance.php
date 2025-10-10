<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';
session_start();

// Ambil ID exit clearance
if (!isset($_GET['id'])) die('ID Exit Clearance tidak ditemukan.');
$id = intval($_GET['id']);

// Query data exit clearance
$stmt = $conn->prepare("SELECT * FROM exit_clearance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die('Data tidak ditemukan.');

// Decode JSON
$aset = json_decode($data['aset'], true) ?? [];
$serah_terima = json_decode($data['serah_terima'], true) ?? [];

// Ambil data perusahaan (kop surat)
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// Nama pembuat surat (user login)
$pembuat = $_SESSION['nama'] ?? ($data['created_by'] ?? 'Admin HR');

// HTML untuk PDF
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { font-family: "Segoe UI", Tahoma, sans-serif; font-size: 11px; color: #333; }
.header { text-align: center; margin-bottom: 15px; }
.header .nama-perusahaan { font-size: 16px; font-weight: bold; text-transform: uppercase; }
.header .alamat { font-size: 10px; color: #555; }
.title { text-align: center; font-size: 14px; font-weight: bold; margin: 15px 0; text-transform: uppercase; color: #222; }
.table-container { margin-top: 10px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; font-size: 11px; }
th { background-color: #f2f2f2; text-align: left; }
tr:nth-child(even) { background-color: #fafafa; }
h3 { background-color: #007BFF; color: #fff; padding: 6px 10px; font-size: 12px; margin-bottom: 5px; border-radius: 4px; }
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

<div class="title">Exit Clearance Karyawan</div>

<div class="table-container">
<table>
<tr>
<th>NIK</th><td>'.htmlspecialchars($data['nik']).'</td>
<th>Nama</th><td>'.htmlspecialchars($data['nama']).'</td>
</tr>
<tr>
<th>Jabatan</th><td>'.htmlspecialchars($data['jabatan']).'</td>
<th>Unit Kerja</th><td>'.htmlspecialchars($data['unit_kerja']).'</td>
</tr>
<tr>
<th>Tanggal Efektif Resign</th><td colspan="3">'.htmlspecialchars($data['tgl_resign']).'</td>
</tr>
</table>
</div>

<h3>Pengembalian Aset</h3>
<table>
<tr><th>No</th><th>Jenis Aset</th><th>Keterangan</th><th>Status</th><th>Penerima</th></tr>';
$no = 1;
foreach($aset as $a){
    $html .= '<tr>
        <td>'.$no.'</td>
        <td>'.htmlspecialchars($a['jenis']).'</td>
        <td>'.htmlspecialchars($a['keterangan']).'</td>
        <td>'.htmlspecialchars($a['status']).'</td>
        <td>'.htmlspecialchars($a['penerima']).'</td>
    </tr>';
    $no++;
}
$html .= '</table>';

$html .= '<h3>Serah Terima</h3>
<table>
<tr><th>Checklist</th><td>'.htmlspecialchars($serah_terima['checklist'] ?? '-').'</td></tr>
<tr><th>Dokumen</th><td>'.htmlspecialchars($serah_terima['dokumen'] ?? '-').'</td></tr>
<tr><th>Penerima</th><td>'.htmlspecialchars($serah_terima['penerima'] ?? '-').'</td></tr>
<tr><th>Tanggal Serah</th><td>'.htmlspecialchars($serah_terima['tgl_serah'] ?? '-').'</td></tr>
</table>

<div class="signature">
  <div class="sig-col">
    <strong>Pegawai Resign</strong><br><br>
    <div class="sig-space"></div>
    <u>'.htmlspecialchars($data['nama']).'</u>
  </div>
  <div class="sig-col">
    <strong>HRD/SDM/Kepegawaian</strong><br><br>
    <div class="sig-space"></div>
    <u>'.htmlspecialchars($pembuat).'</u>
  </div>
</div>
';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('exit_clearance_'.$data['id'].'.pdf', ['Attachment' => false]);
