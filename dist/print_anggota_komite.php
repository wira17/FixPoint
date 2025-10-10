<?php
session_start();
include 'koneksi.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta');

// Ambil data perusahaan (kop surat)
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// Ambil data anggota komite
$sql = "SELECT ak.id, u.nik, u.nama, u.jabatan, u.unit_kerja, ak.jabatan_komite 
        FROM anggota_komite ak 
        JOIN users u ON ak.user_id = u.id 
        ORDER BY u.nama ASC";
$q = mysqli_query($conn, $sql);

// Konversi logo ke base64 agar tampil di PDF
$logoBase64 = '';
if (!empty($perusahaan['logo'])) {
    $logoPath = realpath('uploads/' . $perusahaan['logo']);
    if ($logoPath && file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
}

// Buat HTML laporan
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
  .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; position: relative; }
  .kop img { position: absolute; left: 0; top: 0; max-height: 70px; }
  .kop .nama { font-size: 16px; font-weight:bold; text-transform:uppercase; }
  .kop .alamat { font-size: 11px; margin-top:2px; }
  h3 { text-align:center; margin-bottom:5px; clear:both; }
  p { text-align:center; margin-top:0; font-size:11px; }
  table { border-collapse: collapse; width: 100%; margin-top:10px; }
  table, th, td { border: 1px solid #000; }
  th, td { padding: 5px; }
  th { background: #f2f2f2; text-align:center; }
  .ttd { width: 100%; margin-top: 40px; }
  .ttd td { border: none; text-align: center; vertical-align: top; }
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

<h3><u>DATA ANGGOTA KOMITE KEPERAWATAN</u></h3>

<p style="text-align:justify; margin-top:20px;">
Berdasarkan hasil rapat dan keputusan manajemen rumah sakit, dengan ini menetapkan daftar nama-nama perawat yang tergabung sebagai <b>Anggota Komite Keperawatan</b> pada periode berjalan. Daftar anggota berikut telah melalui proses seleksi dan penilaian sesuai dengan ketentuan yang berlaku di rumah sakit.
</p>

<table>
<thead>
<tr>
  <th>No</th>
  <th>NIK</th>
  <th>Nama</th>
  <th>Jabatan</th>
  <th>Unit Kerja</th>
  <th>Jabatan Komite</th>
</tr>
</thead>
<tbody>';

$no = 1;
if (mysqli_num_rows($q) > 0) {
    while($row = mysqli_fetch_assoc($q)) {
        $html .= "<tr>
          <td align='center'>".$no++."</td>
          <td>".htmlspecialchars($row['nik'])."</td>
          <td>".htmlspecialchars($row['nama'])."</td>
          <td>".htmlspecialchars($row['jabatan'])."</td>
          <td>".htmlspecialchars($row['unit_kerja'])."</td>
          <td>".htmlspecialchars($row['jabatan_komite'])."</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='6' align='center'>Tidak ada data anggota komite</td></tr>";
}
$html .= '</tbody></table>

<p style="text-align:justify; margin-top:15px;">
Demikian surat keputusan ini dibuat untuk digunakan sebagaimana mestinya. 
Kami berharap para anggota Komite Keperawatan dapat menjalankan tugas dan tanggung jawabnya dengan penuh dedikasi, integritas, dan profesionalisme demi peningkatan mutu pelayanan keperawatan di rumah sakit.
</p>

<table class="ttd">
<tr>
  <td width="60%"></td>
  <td>
    <p>'.htmlspecialchars($perusahaan['kota']).', '.date("d F Y").'</p>
    <p><b>Direktur '.htmlspecialchars($perusahaan['nama_perusahaan']).'</b></p>
    <br><br><br>
    <p><u>__________________________</u></p>
  </td>
</tr>
</table>
';

// Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF langsung di browser
$dompdf->stream("surat_anggota_komite.pdf", ["Attachment" => false]);
?>
