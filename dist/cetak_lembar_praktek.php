<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if(!isset($_GET['id'])){
    die('<h3 style="color:red;text-align:center;">❌ ID nilai praktek tidak ditemukan.</h3>');
}
$id_nilai = (int)$_GET['id'];

// Ambil data nilai praktek
$qNilai = mysqli_query($conn, "SELECT n.*, u.nama, u.nik, u.jabatan, u.unit_kerja, u.no_hp, u.email
                               FROM nilai_praktek n
                               JOIN users u ON n.perawat_id = u.id
                               WHERE n.id='$id_nilai'");
if(mysqli_num_rows($qNilai) == 0){
    die('<h3 style="color:red;text-align:center;">❌ Nilai praktek tidak ditemukan.</h3>');
}
$row = mysqli_fetch_assoc($qNilai);

// Ambil info perusahaan/instansi
$qPerusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($qPerusahaan);
$logoPath = realpath('dist/images/logo/' . ($perusahaan['logo'] ?? ''));
$logoBase64 = '';
if($logoPath && file_exists($logoPath)){
    $logoData = file_get_contents($logoPath);
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoBase64 = 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
}

// Fungsi format tanggal Indonesia
function tgl_indo($tanggal, $jam=false){
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    $waktu = $jam ? ' ' . date('H:i', strtotime($tanggal)) : '';
    return "$tgl $bln $thn$waktu";
}

// Fungsi format nilai: jika bulat tampil tanpa desimal, jika pecahan tetap ada
function format_nilai($angka){
    if(floor($angka) == $angka){
        return (string)floor($angka);
    } else {
        return rtrim(rtrim(number_format($angka,2,'.',''),'0'),'.');
    }
}

// Tentukan status Lulus/Remedial
$status = ($row['nilai_akhir'] >= 75) ? 'Lulus' : 'Remedial';
$statusColor = ($row['nilai_akhir'] >= 75) ? 'green' : 'red';

// HTML template
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 0; }
.kop img { width: 80px; }
.kop .nama-perusahaan { font-size: 16px; font-weight: bold; text-transform: uppercase; }
.kop .alamat { font-size: 12px; }
hr { border: 1px solid #000; margin: 10px 0; }
.judul { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin-top: 20px; }
.info-table td { vertical-align: top; padding: 4px; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid black; padding: 5px; }
th { background-color: #f2f2f2; }
.status-ujian { font-weight: bold; color: '.$statusColor.'; }
.footer { 
    position: fixed; 
    bottom: 10px; 
    width: 100%; 
    text-align: center; 
    font-size: 10px; 
    font-style: italic;
    color: #555;
}
</style>

<div class="kop" style="text-align: center;">
'.($logoBase64 ? '<img src="'.$logoBase64.'" alt="Logo"><br>' : '').'
<div class="nama-perusahaan">'.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Instansi Anda').'</div>
<div class="alamat">'.htmlspecialchars($perusahaan['alamat'] ?? '-').', '.htmlspecialchars($perusahaan['kota'] ?? '').', '.htmlspecialchars($perusahaan['provinsi'] ?? '').'<br>
Telp: '.htmlspecialchars($perusahaan['kontak'] ?? '-').' | Email: '.htmlspecialchars($perusahaan['email'] ?? '-').'</div>
</div>

<hr>

<div class="judul">Lembar Hasil Ujian Praktek</div>

<table class="info-table" style="margin-top: 15px;">
<tr><td width="150">NIK</td><td>: '.htmlspecialchars($row['nik']).'</td></tr>
<tr><td>Nama Peserta</td><td>: '.htmlspecialchars($row['nama']).'</td></tr>
<tr><td>Jabatan</td><td>: '.htmlspecialchars($row['jabatan']).'</td></tr>
<tr><td>Unit Kerja</td><td>: '.htmlspecialchars($row['unit_kerja']).'</td></tr>
<tr><td>No. HP</td><td>: '.htmlspecialchars($row['no_hp']).'</td></tr>
<tr><td>Email</td><td>: '.htmlspecialchars($row['email']).'</td></tr>
<tr><td>Kategori</td><td>: '.htmlspecialchars($row['kategori']).'</td></tr>
<tr><td>Nilai Akhir</td><td><b>: '.format_nilai($row['nilai_akhir']).'</b> <span class="status-ujian">('.$status.')</span></td></tr>
<tr><td>Tanggal Ujian</td><td>: '.tgl_indo($row['tanggal_input'], true).' WIB</td></tr>
</table>

<<h4 style="margin-top:20px;">Rincian Nilai:</h4>
<table>
<thead>
<tr>
<th>Keterampilan</th>
<th>Pengetahuan</th>
<th>Komunikasi</th>
<th>Keselamatan</th>
<th>Sikap</th>
<th>Dokumentasi</th>
</tr>
</thead>
<tbody>
<tr>
<td style="text-align:center;">'.format_nilai($row['keterampilan']).'</td>
<td style="text-align:center;">'.format_nilai($row['pengetahuan']).'</td>
<td style="text-align:center;">'.format_nilai($row['komunikasi']).'</td>
<td style="text-align:center;">'.format_nilai($row['keselamatan']).'</td>
<td style="text-align:center;">'.format_nilai($row['sikap']).'</td>
<td style="text-align:center;">'.format_nilai($row['dokumentasi']).'</td>
</tr>
</tbody>
</table>

<br><br><br>

<div style="width: 100%; margin-top:50px; text-align: center;">
Mengetahui,<br>
Penguji<br><br><br><br>
<b>______________________</b><br>
'.htmlspecialchars($row['penguji'] ?? 'Nama Penguji').'
</div>





<div class="footer">
Dicetak menggunakan aplikasi FixPoint - Smart Office Management System di '.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Instansi Anda').', '.tgl_indo(date("Y-m-d H:i:s"), true).'
</div>
';

// Render PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream('lembar_praktek_'.$row['nama'].'.pdf',['Attachment'=>false]);
?>
