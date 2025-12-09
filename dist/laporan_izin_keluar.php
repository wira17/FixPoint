<?php
session_start();
include 'koneksi.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta');

// === Fungsi format tanggal Indonesia ===
function tgl_indo($tanggal) {
    if (!$tanggal || $tanggal == "0000-00-00") return "-";
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// === Fungsi hitung lama izin (durasi) ===
function hitungLama($tanggal, $jam_keluar, $jam_kembali_real) {
    if (empty($jam_keluar) || empty($jam_kembali_real)) {
        return "-";
    }
    $mulai = strtotime("$tanggal $jam_keluar");
    $selesai = strtotime($jam_kembali_real);
    if ($selesai < $mulai) return "-";
    $selisih = $selesai - $mulai;
    $jam = floor($selisih / 3600);
    $menit = floor(($selisih % 3600) / 60);
    return ($jam > 0) ? "$jam jam $menit menit" : "$menit menit";
}

// === Ambil user login ===
$nama_user = $_SESSION['nama'] ?? 'Petugas';

// === Filter tanggal dari URL ===
$tgl_dari = $_GET['tgl_dari'] ?? '';
$tgl_sampai = $_GET['tgl_sampai'] ?? '';

$where = "WHERE 1=1";
if (!empty($tgl_dari) && !empty($tgl_sampai)) {
    $where .= " AND tanggal BETWEEN '$tgl_dari' AND '$tgl_sampai'";
} elseif (!empty($tgl_dari)) {
    $where .= " AND tanggal >= '$tgl_dari'";
} elseif (!empty($tgl_sampai)) {
    $where .= " AND tanggal <= '$tgl_sampai'";
}

// === Ambil data izin keluar ===
$query = mysqli_query($conn, "
    SELECT * FROM izin_keluar
    $where
    ORDER BY tanggal DESC, created_at DESC
");

// === Ambil data perusahaan ===
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// === Konversi logo ke base64 ===
$logoBase64 = '';
if (!empty($perusahaan['logo'])) {
    $logoPath = realpath('uploads/' . $perusahaan['logo']);
    if ($logoPath && file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
}

// === Template HTML laporan ===
$html = '
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
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
  th, td { padding: 5px; font-size:10px; }
  th { background: #f2f2f2; text-align:center; }
  td.left { text-align:left; }
  td.center { text-align:center; }
  .ttd { width:250px; text-align:center; margin-top:40px; float:right; }
</style>

<div class="kop">';
if (!empty($logoBase64)) {
    $html .= '<img src="'.$logoBase64.'" alt="Logo">';
}
$html .= '
  <div class="nama">'.htmlspecialchars($perusahaan['nama_perusahaan'] ?? '-').'</div>
  <div class="alamat">'
      .htmlspecialchars($perusahaan['alamat'] ?? '-').', '
      .htmlspecialchars($perusahaan['kota'] ?? '-').', '
      .htmlspecialchars($perusahaan['provinsi'] ?? '-').'<br>
      Telp: '.htmlspecialchars($perusahaan['kontak'] ?? '-').' | Email: '.htmlspecialchars($perusahaan['email'] ?? '-').'
  </div>
</div>

<h3>LAPORAN IZIN KELUAR PEGAWAI</h3>
<p>Periode: '.(!empty($tgl_dari) ? tgl_indo($tgl_dari) : '-') .' s/d '.(!empty($tgl_sampai) ? tgl_indo($tgl_sampai) : '-').'</p>

<table>
<thead>
<tr>
  <th>No</th>
  <th>Nama</th>
  <th>Bagian</th>
  <th>Tanggal</th>
  <th>Jam Keluar</th>
  <th>Jam Kembali</th>
  <th>Keperluan</th>
  <th>ACC Atasan</th>
  <th>ACC SDM</th>
  <th>Lama</th>
</tr>
</thead>
<tbody>';

$no = 1;
if (mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $lama = hitungLama($row['tanggal'], $row['jam_keluar'], $row['jam_kembali_real']);
        $html .= "
        <tr>
          <td class='center'>{$no}</td>
          <td class='left'>".htmlspecialchars($row['nama'])."</td>
          <td class='center'>".htmlspecialchars($row['bagian'])."</td>
          <td class='center'>".tgl_indo($row['tanggal'])."</td>
          <td class='center'>".htmlspecialchars($row['jam_keluar'])."</td>
          <td class='center'>".($row['jam_kembali_real'] ? htmlspecialchars($row['jam_kembali_real']) : '-')."</td>
          <td class='left'>".htmlspecialchars($row['keperluan'])."</td>
          <td class='center'>".ucfirst($row['status_atasan'])."</td>
          <td class='center'>".ucfirst($row['status_sdm'])."</td>
          <td class='center'>{$lama}</td>
        </tr>";
        $no++;
    }
} else {
    $html .= "<tr><td colspan='10' align='center'>Tidak ada data</td></tr>";
}
$html .= '
</tbody>
</table>

<div class="ttd">
  '.htmlspecialchars($perusahaan['kota'] ?? '-').', '.tgl_indo(date('Y-m-d')).'<br>
  <div style="text-align:center;">Hormat kami,</div><br><br><br>
  <strong>'.htmlspecialchars($nama_user).'</strong>
</div>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_izin_keluar.pdf", ["Attachment" => false]);
?>
