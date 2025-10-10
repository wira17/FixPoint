<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';

// === Fungsi Tanggal Indonesia ===
function tgl_indo($tanggal, $jam = false) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
             'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    $waktu = $jam ? ' ' . date('H:i', strtotime($tanggal)) : '';
    return "$tgl $bln $thn$waktu";
}

// === Ambil filter tanggal ===
$dari = isset($_GET['dari']) ? $_GET['dari'] : '';
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : '';

if (!$dari || !$sampai) {
    die('Filter tanggal tidak lengkap.');
}

// === Ambil data perusahaan ===
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);
$logoPath = realpath('dist/images/logo/' . $perusahaan['logo']);
$logoBase64 = '';
if ($logoPath && file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
}

// === Ambil data maintenance sarpras ===
$query = mysqli_query($conn, "
    SELECT mrs.*, dba.kode_ac, dba.lokasi, dba.merk, dba.tipe, dba.kapasitas, dba.no_seri, dba.tahun_beli
    FROM maintanance_rutin_sarpras mrs
    JOIN data_barang_ac dba ON mrs.barang_id = dba.id
    WHERE DATE(mrs.waktu_input) BETWEEN '$dari' AND '$sampai'
    ORDER BY mrs.waktu_input DESC
");

// === Template HTML untuk PDF ===
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 10px; }
  table { border-collapse: collapse; width: 100%; margin-top: 10px; }
  th, td { border: 1px solid #000; padding: 4px; }
  th { background-color: #2f3640; color: #fff; text-align: center; }
  .kop { text-align:center; }
  .text-success { color: green; font-weight: bold; }
  .text-warning { color: orange; font-weight: bold; }
  .text-danger { color: red; font-weight: bold; }
</style>

<div class="kop">
  <img src="' . $logoBase64 . '" alt="Logo" style="width:60px;"><br>
  <div style="font-size:14px;font-weight:bold;">' . htmlspecialchars($perusahaan['nama_perusahaan']) . '</div>
  <div style="font-size:10px;">' . htmlspecialchars($perusahaan['alamat']) . ', ' . htmlspecialchars($perusahaan['kota']) . ', ' . htmlspecialchars($perusahaan['provinsi']) . '<br>
  Telp: ' . htmlspecialchars($perusahaan['kontak']) . ' | Email: ' . htmlspecialchars($perusahaan['email']) . '</div>
</div>

<hr>

<h3 style="text-align:center; margin:0;">Laporan Maintenance Sarpras (AC)</h3>
<p style="text-align:center; margin:0;">Periode: ' . tgl_indo($dari) . ' - ' . tgl_indo($sampai) . '</p>

<table>
<thead>
<tr>
  <th>No</th>
  <th>Kode AC</th>
  <th>Lokasi</th>
  <th>Merk / Tipe</th>
  <th>Kapasitas</th>
  <th>No Seri</th>
  <th>Tahun Beli</th>
  <th>Kondisi Fisik</th>
  <th>Fungsi Perangkat</th>
  <th>Catatan</th>
  <th>Teknisi</th>
  <th>Waktu Maintanance</th>
</tr>
</thead>
<tbody>
';

$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    // Penentuan status warna otomatis berdasarkan kondisi_fisik/fungsi_perangkat
    $status_class = 'text-success';
    if (stripos($row['kondisi_fisik'], 'kurang') !== false || stripos($row['fungsi_perangkat'], 'tidak') !== false) {
        $status_class = 'text-warning';
    }
    if (stripos($row['kondisi_fisik'], 'rusak') !== false || stripos($row['fungsi_perangkat'], 'mati') !== false) {
        $status_class = 'text-danger';
    }

    $html .= '
    <tr>
        <td style="text-align:center;">' . $no++ . '</td>
        <td>' . htmlspecialchars($row['kode_ac']) . '</td>
        <td>' . htmlspecialchars($row['lokasi']) . '</td>
        <td>' . htmlspecialchars($row['merk']) . ' / ' . htmlspecialchars($row['tipe']) . '</td>
        <td>' . htmlspecialchars($row['kapasitas']) . '</td>
        <td>' . htmlspecialchars($row['no_seri']) . '</td>
        <td style="text-align:center;">' . htmlspecialchars($row['tahun_beli']) . '</td>
        <td class="' . $status_class . '">' . htmlspecialchars($row['kondisi_fisik']) . '</td>
        <td>' . htmlspecialchars($row['fungsi_perangkat']) . '</td>
        <td>' . htmlspecialchars($row['catatan']) . '</td>
        <td>' . htmlspecialchars($row['nama_teknisi']) . '</td>
        <td style="text-align:center;">' . tgl_indo($row['waktu_input'], true) . '</td>
    </tr>';
}

$html .= '
</tbody>
</table>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// === Watermark opsional ===
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(0.05);
$watermarkPath = 'assets/watermark.jpg';
if (file_exists($watermarkPath)) {
    $width = 500;
    $height = 300;
    $x = ($canvas->get_width() - $width) / 2;
    $y = ($canvas->get_height() - $height) / 2;
    $canvas->image($watermarkPath, $x, $y, $width, $height);
}

$dompdf->stream('rekap_maintanance_sarpras.pdf', ['Attachment' => false]);
?>
