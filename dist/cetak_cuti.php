<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';

// === Ambil ID pengajuan cuti ===
if (!isset($_GET['id'])) die('ID pengajuan tidak ditemukan.');
$id = intval($_GET['id']);

// === Data perusahaan (kop surat) ===
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// === Query data pengajuan cuti ===
$sql = "SELECT p.*, u.nik, u.nama, u.unit_kerja, u.jabatan,
               mc.nama_cuti, d.nama AS nama_delegasi,
               COUNT(pc.id) AS lama_hari,
               GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti
        FROM pengajuan_cuti p
        JOIN users u ON p.karyawan_id = u.id
        JOIN master_cuti mc ON p.cuti_id = mc.id
        LEFT JOIN users d ON p.delegasi_id = d.id
        LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
        WHERE p.id = ?
        GROUP BY p.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data tidak ditemukan!");

// === Siapkan nama tanda tangan ===
$pemohon  = $data['nama'] ?: '........................';
$delegasi = $data['nama_delegasi'] ?: '........................';
$atasan   = $data['acc_atasan_by'] ?: '........................';
$hrd      = $data['acc_hrd_by'] ?: '........................';

// === HTML untuk PDF berbentuk surat ===
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body { 
  font-family: "Times New Roman", serif; 
  font-size: 12pt; 
  line-height: 1.6; 
  color: #000; 
}
.header { 
  text-align: center; 
  border-bottom: 2px solid #000; 
  padding-bottom: 5px; 
  margin-bottom: 20px; 
}
.header .nama-perusahaan { 
  font-size: 16pt; 
  font-weight: bold; 
  text-transform: uppercase; 
}
.header .alamat { 
  font-size: 10pt; 
  color: #333; 
}
.content { 
  margin-top: 20px; 
  text-align: justify; 
}
.ttd {
  width: 100%;
  margin-top: 40px;
  text-align: center;
  border-collapse: collapse;
}
.ttd td {
  width: 25%;
  vertical-align: top;
  padding: 5px;
}
.ttd .jabatan {
  font-style: italic;
  font-size: 11pt;
  color: #333;
  margin-bottom: 30px; /* lebih rapat */
}
.ttd .title {
  font-weight: bold;
  text-decoration: underline;
  display: inline-block;
  margin-top: 40px; /* sebelumnya 80px */
}

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

Mengajukan permohonan cuti <b>'.htmlspecialchars($data['nama_cuti']).'</b> selama <b>'.$data['lama_hari'].' hari</b>, 
pada tanggal <b>'.htmlspecialchars($data['tanggal_cuti']).'</b>.<br><br>

Alasan cuti: '.htmlspecialchars($data['alasan']).'.<br><br>

Delegasi tugas selama cuti kepada: <b>'.htmlspecialchars($delegasi).'</b>.<br><br>

Demikian permohonan ini saya ajukan, atas perhatian dan persetujuannya saya ucapkan terima kasih.
</div>

<table class="ttd">
  <tr>
    <td>
      <div class="jabatan">Pemohon</div>
      <div class="title">'.htmlspecialchars($pemohon).'</div>
    </td>
    <td>
      <div class="jabatan">Delegasi</div>
      <div class="title">'.htmlspecialchars($delegasi).'</div>
    </td>
    <td>
      <div class="jabatan">Atasan</div>
      <div class="title">'.htmlspecialchars($atasan).'</div>
    </td>
    <td>
      <div class="jabatan">HRD</div>
      <div class="title">'.htmlspecialchars($hrd).'</div>
    </td>
  </tr>
</table>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('surat_cuti_'.$data['id'].'.pdf', ['Attachment' => false]);
