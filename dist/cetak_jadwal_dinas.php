<?php
session_start();
include 'koneksi.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta');

// Cek login
if (!isset($_SESSION['nama'])) {
    die("Anda belum login.");
}

// Ambil data perusahaan (kop surat)
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan   = mysqli_fetch_assoc($q_perusahaan);

// Ambil nama user login
$nama_login = $_SESSION['nama'] ?? 'User Login';

// Ambil filter bulan/tahun
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// Mapping nama bulan Indonesia
$bulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
    4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
    10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Mapping kode shift
$kodeShift = [
    'Pagi'  => 'P',
    'Siang' => 'S',
    'Malam' => 'M',
    'Libur' => 'L',
    'Tetap' => 'P'

];

// Ambil daftar jam kerja
$jamQuery = mysqli_query($conn, "SELECT * FROM jam_kerja ORDER BY id");
$jamList = [];
while ($j = mysqli_fetch_assoc($jamQuery)) {
    $jamList[$j['id']] = $j['nama_jam'];
}

// Ambil data jadwal tersimpan
$savedQuery = "SELECT jd.*, u.nama AS nama_karyawan, u.unit_kerja 
               FROM jadwal_dinas jd
               JOIN users u ON jd.user_id=u.id
               WHERE jd.bulan='$bulan' AND jd.tahun='$tahun'
               ORDER BY u.unit_kerja, u.nama, jd.tanggal";
$savedResult = mysqli_query($conn, $savedQuery);

$savedData = [];
$unitKaryawan = [];
while($row = mysqli_fetch_assoc($savedResult)){
    $tgl = (int)date('j', strtotime($row['tanggal']));
    $jamNama = $jamList[$row['jam_kerja_id']] ?? '-';
    $kode = $kodeShift[$jamNama] ?? '-';
    $savedData[$row['nama_karyawan']][$tgl] = $kode;
    $unitKaryawan[$row['nama_karyawan']] = $row['unit_kerja'];
}

// Ambil nama unit pertama untuk judul
$namaUnit = reset($unitKaryawan) ?: '-';

// Logo base64
$logoBase64 = '';
if (!empty($perusahaan['logo'])) {
    $logoPath = realpath('uploads/' . $perusahaan['logo']);
    if ($logoPath && file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
}

// HTML
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
  th, td { padding: 4px; font-size:9px; text-align:center; }
  th { background: #f2f2f2; }
  .libur { background-color:#f8d7da; color:#b71c1c; font-weight:bold; }
  .nama-karyawan { text-align:left; font-size:9px; } /* kolom nama rata kiri */
</style>

<div class="kop">';

if (!empty($logoBase64)) {
    $html .= '<img src="'.$logoBase64.'" alt="Logo">';
}

$html .= '
  <div class="nama">'.htmlspecialchars($perusahaan['nama_perusahaan']).'</div>
  <div class="alamat">'
      .htmlspecialchars($perusahaan['alamat']).', '
      .htmlspecialchars($perusahaan['kota']).', '
      .htmlspecialchars($perusahaan['provinsi']).'<br>
      Telp: '.htmlspecialchars($perusahaan['kontak']).' | Email: '.htmlspecialchars($perusahaan['email']).'
  </div>
</div>

<h3>JADWAL DINAS BULAN '.strtoupper($bulanIndo[$bulan]).' '.$tahun.' UNIT '.strtoupper($namaUnit).'</h3>
<p>Dicetak pada: '.date("d-m-Y H:i").'</p>

<table>
<thead>
<tr>
  <th>Karyawan</th>
  <th>Unit Kerja</th>';
for($d=1;$d<=$daysInMonth;$d++){
    $html .= '<th>'.$d.'</th>';
}
$html .= '</tr>
</thead>
<tbody>';

if(!empty($savedData)){
    foreach($savedData as $karyawan => $tglData){
        $html .= '<tr>';
        $html .= '<td class="nama-karyawan">'.htmlspecialchars($karyawan).'</td>'; // rata kiri
        $html .= '<td>'.htmlspecialchars($unitKaryawan[$karyawan] ?? '-').'</td>';
        for($d=1;$d<=$daysInMonth;$d++){
            $val = $tglData[$d] ?? '-';
            $cls = ($val == 'L') ? 'class="libur"' : '';
            $html .= "<td $cls>".$val."</td>";
        }
        $html .= '</tr>';
    }
}else{
    $html .= '<tr><td colspan="'.($daysInMonth+2).'" align="center">Tidak ada data</td></tr>';
}
$html .= '</tbody></table>';

// Tambah tanda tangan
$html .= '
<br><br>
<table width="100%" style="border:none">
  <tr>
    <td style="border:none" width="60%"></td>
    <td style="border:none" align="center">
      '.$perusahaan['kota'].', '.date("d").' '.$bulanIndo[(int)date("n")].' '.date("Y").'<br>
      Mengetahui,<br><br><br><br>
      (<b>'.htmlspecialchars($nama_login).'</b>)
    </td>
  </tr>
</table>';

// Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("jadwal_dinas.pdf", ["Attachment" => false]);
