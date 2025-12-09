<?php
session_start();
include 'koneksi.php';
require 'dompdf/autoload.inc.php';
require '../phpqrcode/qrlib.php'; // pastikan folder phpqrcode sudah ada
use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    echo "<script>alert('Anda belum login.'); window.close();</script>";
    exit;
}

// Ambil data perusahaan
$q_perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan LIMIT 1");
$perusahaan = mysqli_fetch_assoc($q_perusahaan);

// Ambil data user login
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
$user = mysqli_fetch_assoc($q_user);

// Ambil filter
$tgl_dari   = $_GET['tgl_dari'] ?? '';
$tgl_sampai = $_GET['tgl_sampai'] ?? '';
$search     = $_GET['search'] ?? '';

// Build query
$where  = "WHERE c.user_id = '".intval($user_id)."'";
if (!empty($tgl_dari) && !empty($tgl_sampai)) {
    $where .= " AND DATE(c.tanggal) BETWEEN '".mysqli_real_escape_string($conn, $tgl_dari)."' AND '".mysqli_real_escape_string($conn, $tgl_sampai)."'";
} elseif (!empty($tgl_dari)) {
    $where .= " AND DATE(c.tanggal) >= '".mysqli_real_escape_string($conn, $tgl_dari)."'";
} elseif (!empty($tgl_sampai)) {
    $where .= " AND DATE(c.tanggal) <= '".mysqli_real_escape_string($conn, $tgl_sampai)."'";
}
if (!empty($search)) {
    $searchTerm = mysqli_real_escape_string($conn, $search);
    $where .= " AND (c.judul LIKE '%$searchTerm%' OR c.isi LIKE '%$searchTerm%')";
}

// Ambil data catatan kerja
$sql = "SELECT c.*, u.nama 
        FROM catatan_kerja c 
        JOIN users u ON c.user_id = u.id
        $where ORDER BY c.tanggal DESC";
$q = mysqli_query($conn, $sql);

// Konversi logo perusahaan ke base64 (untuk header PDF)
$logoBase64 = '';
$logoPath = '';
if (!empty($perusahaan['logo'])) {
    $logoPath = realpath('../uploads/' . $perusahaan['logo']);
    if ($logoPath && file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
}

// =============================
// === Buat QR Code Dengan Logo ===
// =============================

// Teks dalam QR
$qrText = "Dicetak oleh " . $user['nama'] . 
          " di " . $perusahaan['nama_perusahaan'] . 
          " pada tanggal " . date("d-m-Y");

// Buat QR code sementara di memori
$qrTempFile = tempnam(sys_get_temp_dir(), 'qr_');
QRcode::png($qrText, $qrTempFile, QR_ECLEVEL_H, 6);

// Jika ada logo, tempel di tengah QR
if ($logoPath && file_exists($logoPath)) {
    $qrImage = imagecreatefrompng($qrTempFile);
    $logoImage = null;

    // Baca logo tergantung format
    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    if ($logoExt == 'jpg' || $logoExt == 'jpeg') {
        $logoImage = imagecreatefromjpeg($logoPath);
    } elseif ($logoExt == 'png') {
        $logoImage = imagecreatefrompng($logoPath);
    }

    if ($qrImage && $logoImage) {
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        // Ukuran logo disesuaikan agar tidak menutupi QR
        $logoQRWidth = $qrWidth / 4;
        $scale = $logoWidth / $logoQRWidth;
        $logoQRHeight = $logoHeight / $scale;

        // Posisi tengah QR
        $logoX = ($qrWidth - $logoQRWidth) / 2;
        $logoY = ($qrHeight - $logoQRHeight) / 2;

        // Tempel logo di tengah QR
        imagecopyresampled($qrImage, $logoImage, $logoX, $logoY, 0, 0, 
                           $logoQRWidth, $logoQRHeight, $logoWidth, $logoHeight);

        ob_start();
        imagepng($qrImage);
        $qrImageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($qrImage);
        imagedestroy($logoImage);
    } else {
        $qrImageData = file_get_contents($qrTempFile);
    }
} else {
    $qrImageData = file_get_contents($qrTempFile);
}

unlink($qrTempFile); // hapus file sementara
$qrBase64 = 'data:image/png;base64,' . base64_encode($qrImageData);

// =============================
// === Akhir QR Code ===
// =============================

// Tentukan periode laporan
if ($tgl_dari && $tgl_sampai) {
    $periode = 'Periode: ' . date('d-m-Y', strtotime($tgl_dari)) . ' s/d ' . date('d-m-Y', strtotime($tgl_sampai));
} elseif ($tgl_dari) {
    $periode = 'Periode: Mulai ' . date('d-m-Y', strtotime($tgl_dari));
} elseif ($tgl_sampai) {
    $periode = 'Periode: Sampai ' . date('d-m-Y', strtotime($tgl_sampai));
} else {
    $periode = 'Periode: Semua Data';
}

// =============================
// === HTML untuk PDF ===
// =============================
$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
  .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
  .kop img { float:left; max-height:70px; margin-right:10px; }
  .kop .nama { font-size: 16px; font-weight:bold; text-transform:uppercase; }
  .kop .alamat { font-size: 11px; margin-top:2px; }
  h3 { text-align:center; margin-bottom:2px; clear:both; }
  p { text-align:center; margin:2px 0; font-size:11px; }
  table { border-collapse: collapse; width: 100%; margin-top:10px; }
  table, th, td { border: 1px solid #000; }
  th, td { padding: 5px; }
  th { background: #f2f2f2; text-align:center; }
  .ttd { width: 250px; float:right; text-align:center; margin-top:30px; }
  .qr { margin-top:10px; }
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

<h3>LAPORAN CATATAN KERJA</h3>
<p><b>'.$periode.'</b></p>
<p>Dicetak pada: '.date("d-m-Y H:i").'</p>';

if ($search) {
    $html .= '<p>Pencarian: '.$search.'</p>';
}

$html .= '
<table>
<thead>
<tr>
  <th>No</th>
  <th>Nama</th>
  <th>Tanggal</th>
  <th>Judul</th>
  <th>Catatan</th>
</tr>
</thead>
<tbody>';

$no = 1;
if (mysqli_num_rows($q) > 0) {
    while($row = mysqli_fetch_assoc($q)) {
        $html .= "<tr>
          <td align='center'>".$no++."</td>
          <td>".htmlspecialchars($row['nama'])."</td>
          <td>".date('d-m-Y H:i', strtotime($row['tanggal']))."</td>
          <td>".htmlspecialchars($row['judul'])."</td>
          <td>".nl2br(htmlspecialchars($row['isi']))."</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='5' align='center'>Tidak ada data</td></tr>";
}
$html .= '</tbody></table>';

// Tambahkan tanda tangan + QR Code
$html .= '
<div class="ttd">
  <p>'.htmlspecialchars($perusahaan['kota']).', '.date("d-m-Y").'</p>
  <p>Yang Membuat,</p>
  <div class="qr"><img src="'.$qrBase64.'" width="90" height="90"></div>
  <p><b>'.htmlspecialchars($user['nama']).'</b></p>
  <p>NIK: '.htmlspecialchars($user['nik']).'</p>
</div>';

// Render PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_catatan_kerja.pdf", ["Attachment" => false]);
?>
