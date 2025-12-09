<?php
session_start();
include 'koneksi.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta');

// === Ambil data perusahaan ===
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// === Ambil filter tanggal ===
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-d');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// === Ambil data cuti ===
$sql = "
  SELECT p.*, u.nama AS nama_karyawan, mc.nama_cuti, d.nama AS nama_delegasi,
         GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti
  FROM pengajuan_cuti p
  JOIN users u ON p.karyawan_id = u.id
  JOIN master_cuti mc ON p.cuti_id = mc.id
  LEFT JOIN users d ON p.delegasi_id = d.id
  LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
  WHERE pc.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
  GROUP BY p.id
  ORDER BY p.id DESC
";
$q = mysqli_query($conn, $sql);

// === Konversi logo perusahaan ke base64 ===
$logoBase64 = '';
if (!empty($perusahaan['logo'])) {
    $logoPath = realpath('uploads/' . $perusahaan['logo']);
    if ($logoPath && file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
}

// === HTML Template ===
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
  .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
  .kop img { float:left; max-height:70px; margin-right:10px; }
  .kop .nama { font-size: 16px; font-weight:bold; text-transform:uppercase; }
  .kop .alamat { font-size: 11px; margin-top:2px; }
  h3 { text-align:center; margin-bottom:5px; clear:both; }
  p { text-align:center; margin-top:0; font-size:11px; }
  table { border-collapse: collapse; width: 100%; margin-top:10px; }
  table, th, td { border: 1px solid #000; }
  th, td { padding: 5px; }
  th { background: #f2f2f2; text-align:center; }
</style>

<div class="kop">';

if (!empty($logoBase64)) {
    $html .= '<img src="'.$logoBase64.'" alt="Logo">';
}

$html .= '
  <div class="nama">'.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'PERUSAHAAN').'</div>
  <div class="alamat">'
      .htmlspecialchars($perusahaan['alamat'] ?? '').', '
      .htmlspecialchars($perusahaan['kota'] ?? '').', '
      .htmlspecialchars($perusahaan['provinsi'] ?? '').'<br>
      Telp: '.htmlspecialchars($perusahaan['kontak'] ?? '').' | Email: '.htmlspecialchars($perusahaan['email'] ?? '').'
  </div>
</div>

<h3>LAPORAN PENGAJUAN CUTI KARYAWAN</h3>
<p>Periode: '.date("d-m-Y", strtotime($tgl_awal)).' s.d '.date("d-m-Y", strtotime($tgl_akhir)).'</p>
<p>Dicetak pada: '.date("d-m-Y H:i").'</p>

<table>
<thead>
<tr>
  <th>No</th>
  <th>Nama Karyawan</th>
  <th>Jenis Cuti</th>
  <th>Tanggal Cuti</th>
  <th>Delegasi</th>
  <th>Alasan</th>
  <th>Status</th>
</tr>
</thead>
<tbody>';

$no = 1;
if (mysqli_num_rows($q) > 0) {
    while($row = mysqli_fetch_assoc($q)) {
        $html .= "<tr>
          <td align='center'>".$no++."</td>
          <td>".htmlspecialchars($row['nama_karyawan'])."</td>
          <td>".htmlspecialchars($row['nama_cuti'])."</td>
          <td>".htmlspecialchars($row['tanggal_cuti'] ?? '-')."</td>
          <td>".htmlspecialchars($row['nama_delegasi'] ?? '-')."</td>
          <td>".nl2br(htmlspecialchars($row['alasan'] ?? '-'))."</td>
          <td align='center'>".htmlspecialchars($row['status'])."</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='7' align='center'>Tidak ada data cuti pada periode ini.</td></tr>";
}

$html .= '</tbody></table>';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Laporan_Cuti_".date('Ymd_His').".pdf", ["Attachment" => false]);
?>
