<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if(!isset($_GET['id'])){
    die('<h3 style="color:red;text-align:center;">❌ ID hasil ujian tidak ditemukan.</h3>');
}
$id_hasil = (int)$_GET['id'];

$qHasil = mysqli_query($conn, "SELECT * FROM hasil_ujian WHERE id='$id_hasil'");
if(mysqli_num_rows($qHasil) == 0){
    die('<h3 style="color:red;text-align:center;">❌ Hasil ujian tidak ditemukan.</h3>');
}
$hasil = mysqli_fetch_assoc($qHasil);

$qUser = mysqli_query($conn, "SELECT nama, email, nik, jabatan, unit_kerja, no_hp FROM users WHERE id='{$hasil['user_id']}'");
$user = mysqli_num_rows($qUser) > 0 ? mysqli_fetch_assoc($qUser) : ['nama'=>'Peserta','email'=>'-','nik'=>'-','jabatan'=>'-','unit_kerja'=>'-','no_hp'=>'-'];

$qJudul = mysqli_query($conn, "SELECT judul_soal FROM judul_soal WHERE id='{$hasil['judul_soal_id']}'");
$judul = mysqli_fetch_assoc($qJudul);

$qJawaban = mysqli_query($conn, "
    SELECT s.soal, s.jawaban_benar, j.jawaban
    FROM jawaban_ujian j
    JOIN soal s ON j.soal_id = s.id
    WHERE j.user_id='{$hasil['user_id']}' AND j.judul_soal_id='{$hasil['judul_soal_id']}'
    ORDER BY j.id ASC
");

$qPerusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($qPerusahaan);
$logoPath = realpath('dist/images/logo/' . ($perusahaan['logo'] ?? ''));
$logoBase64 = '';
if($logoPath && file_exists($logoPath)){
    $logoData = file_get_contents($logoPath);
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoBase64 = 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
}

function tgl_indo($tanggal, $jam=false){
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    $waktu = $jam ? ' ' . date('H:i', strtotime($tanggal)) : '';
    return "$tgl $bln $thn$waktu";
}

$total = $hasil['total_soal'];
$benar = $hasil['jumlah_benar'];
$nilai = $hasil['nilai'];
$tanggalUjian = $hasil['tanggal_selesai'];
$tbody = '';
$no=1;

while($row = mysqli_fetch_assoc($qJawaban)){
    $tbody .= "
    <tr>
        <td style='text-align:center;'>$no</td>
        <td>".nl2br(htmlspecialchars($row['soal']))."</td>
        <td style='text-align:center;'>".strtoupper($row['jawaban'])."</td>
    </tr>";
    $no++;
}

// Tentukan status lulus/remedial
$statusUjian = ($nilai >= 75) ? 'Lulus' : 'Remedial';
$statusColor = ($nilai >= 75) ? 'green' : 'red';

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

<div class="judul">Lembar hasil '.htmlspecialchars($judul['judul_soal']).'</div>

<table class="info-table" style="margin-top: 15px;">
<tr><td width="150">NIK</td><td>: '.htmlspecialchars($user['nik']).'</td></tr>
<tr><td>Nama Peserta</td><td>: '.htmlspecialchars($user['nama']).'</td></tr>
<tr><td>Jabatan</td><td>: '.htmlspecialchars($user['jabatan']).'</td></tr>
<tr><td>Unit Kerja</td><td>: '.htmlspecialchars($user['unit_kerja']).'</td></tr>
<tr><td>No. HP</td><td>: '.htmlspecialchars($user['no_hp']).'</td></tr>
<tr><td>Email</td><td>: '.htmlspecialchars($user['email']).'</td></tr>
<tr><td>Judul Soal</td><td>: '.htmlspecialchars($judul['judul_soal']).'</td></tr>
<tr><td>Tanggal Ujian</td><td>: '.tgl_indo($tanggalUjian,true).' WIB</td></tr>
<tr><td>Jumlah Soal</td><td>: '.$total.'</td></tr>
<tr><td>Benar</td><td>: '.$benar.'</td></tr>
<tr><td>Nilai Akhir</td><td><b>'.$nilai.'%</b> <span class="status-ujian">('.$statusUjian.')</span></td></tr>
</table>

<h4 style="margin-top:20px;">Detail Jawaban:</h4>
<table>
<thead>
<tr>
<th style="width:30px;">No</th>
<th>Soal</th>
<th style="width:90px;">Jawaban Peserta</th>
</tr>
</thead>
<tbody>'.$tbody.'</tbody>
</table>

<div class="footer">
Dicetak menggunakan aplikasi FixPoint - Smart Office Management System di '.htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Instansi Anda').', '.tgl_indo(date("Y-m-d H:i:s"), true).'
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream('hasil_ujian_'.$user['nama'].'.pdf',['Attachment'=>false]);
?>
