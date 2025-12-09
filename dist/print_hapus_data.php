<?php
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
include 'koneksi.php';

if (!isset($_GET['id'])) {
    die('ID permintaan tidak ditemukan.');
}

$id = intval($_GET['id']);

// ================= AMBIL DATA PERMINTAAN =================
$query = $conn->query("
    SELECT p.*, u.nik, u.nama, u.jabatan, u.unit_kerja 
    FROM permintaan_hapus_data p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = '$id'
");

$data = $query->fetch_assoc();
if (!$data) {
    die('Data tidak ditemukan.');
}

// ================= KONVERSI STATUS =================
$status_text = [
    "Menunggu" => "MENUNGGU VERIFIKASI",
    "Diproses" => "SEDANG DIPROSES",
    "Ditolak"  => "DITOLAK",
    "Selesai"  => "DISETUJUI"
];
$stempel_status = $status_text[$data['status']] ?? "-";

// ================= DATA PERUSAHAAN =================
$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();

// ================= FORMAT TANGGAL =================
$tanggal = date('d-m-Y', strtotime($data['tanggal'] ?? 'now'));

// ================= VALIDATOR (PETUGAS IT) =================
$validator_nama = $data['updated_by'] ?: "_________________";

$validator_nik = "-";
if ($data['updated_by']) {
    $getNik = $conn->query("SELECT nik FROM users WHERE nama='".$data['updated_by']."' LIMIT 1");
    if ($getNik && $getNik->num_rows > 0) {
        $validator_nik = $getNik->fetch_assoc()['nik'];
    }
}

// ================= STYLE & HTML =================
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 11px; margin:22px; }

table.kop { width:100%; border-bottom:2px solid #000; margin-bottom:8px; }
.kop-text { text-align:center; font-size:15px; font-weight:bold; }
.subkop { text-align:center; font-size:10px; }

.title { text-align:center; font-size:14px; font-weight:bold; margin:12px 0; }

.info .label { width:160px; display:inline-block; font-weight:bold; }

.content-box {
    border:1px solid #000; padding:10px; min-height:100px; line-height:1.4; margin-top:8px;
}

.ttd-table { width:100%; margin-top:40px; text-align:center; }

.footer { margin-top:25px; font-size:10px; text-align:center; color:#555; }

</style>


<!-- KOP SURAT -->
<table class="kop">
<tr><td class="kop-text">'.strtoupper($perusahaan['nama_perusahaan']).'</td></tr>
<tr><td class="subkop">'.$perusahaan['alamat'].' - '.$perusahaan['kota'].', '.$perusahaan['provinsi'].'</td></tr>
<tr><td class="subkop">Telp: '.$perusahaan['kontak'].' | Email: '.$perusahaan['email'].'</td></tr>
</table>

<div class="title">FORM PERMOHONAN HAPUS DATA SIMRS</div>

<div class="info">
    <div><span class="label">Nomor Surat</span>: <b>'.$data['nomor_surat'].'</b></div>
    <div><span class="label">Tanggal Permintaan</span>: '.$tanggal.'</div>
    <div><span class="label">Status Permohonan</span>: <b>'.$stempel_status.'</b></div>
    <div><span class="label">NIK Pemohon</span>: '.$data['nik'].'</div>
    <div><span class="label">Nama Pemohon</span>: '.htmlspecialchars($data['nama']).'</div>
    <div><span class="label">Jabatan</span>: '.htmlspecialchars($data['jabatan']).'</div>
    <div><span class="label">Unit Kerja</span>: '.htmlspecialchars($data['unit_kerja']).'</div>
</div>

<b>Kronologi & Alasan Permohonan:</b>
<div class="content-box">'.nl2br(htmlspecialchars($data['kronologi'])).'</div>

<!-- TANDA TANGAN -->
<table class="ttd-table">
<tr>
<td>
    Pemohon,<br><br><br><br><br>
    <b><u>'.htmlspecialchars($data['nama']).'</u></b><br>
    NIK: '.$data['nik'].'
</td>

<td>
    '.$perusahaan['kota'].', '.$tanggal.'<br><br><br><br><br>
    <b><u>'.$validator_nama.'</u></b><br>
    NIK: '.$validator_nik.'<br>
    Petugas IT - SIMRS
</td>
</tr>
</table>

<div class="footer">
Dokumen ini dicetak otomatis melalui SIMRS — '.date('d/m/Y H:i').'
</div>
';

// ================= GENERATE PDF =================
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ================= WATERMARK OPSIONAL =================
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(0.05);

$img = 'assets/watermark.jpg';
if(file_exists($img)){
    $canvas->image($img, 150, 200, 300, 180);
}

$dompdf->stream("Permohonan_Hapus_Data_$id.pdf", ["Attachment" => false]);
?>
