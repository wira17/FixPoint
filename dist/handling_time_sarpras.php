<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Pencarian & Filter tanggal
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

if ($dari_tanggal) $dari_tanggal = date('Y-m-d', strtotime($dari_tanggal));
if ($sampai_tanggal) $sampai_tanggal = date('Y-m-d', strtotime($sampai_tanggal));

// Fungsi format tanggal & durasi
function formatTanggal($tanggal) {
    return $tanggal ? date('d-m-Y H:i', strtotime($tanggal)) : '-';
}
function hitungDurasi($mulai, $selesai) {
    if (!$mulai || !$selesai) return '-';
    $start = new DateTime($mulai);
    $end = new DateTime($selesai);
    $interval = $start->diff($end);
    $jam = $interval->h + ($interval->days * 24);
    $menit = $interval->i;
    return "{$jam}j {$menit}m";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
<title>Handling Time Sarpras</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css"/>
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css"/>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="assets/css/components.css"/>
<style>
.table-responsive-custom { width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table-responsive-custom table { min-width:1500px; white-space:nowrap; }
</style>
</head>
<body>
<div id="app">
<div class="main-wrapper main-wrapper-1">
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
<section class="section">
<div class="section-body">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h4><i class="fas fa-clock me-2"></i> Data Handling Time Tiket Sarpras</h4>
</div>
<div class="card-body">

<!-- Form Filter -->
<form method="GET" class="form-inline mb-3 align-items-end">
  <div class="form-group mr-2">
    <label class="mr-2">Dari</label>
    <input type="date" name="dari_tanggal" class="form-control" value="<?= htmlspecialchars($dari_tanggal) ?>">
  </div>
  <div class="form-group mr-2">
    <label class="mr-2">Sampai</label>
    <input type="date" name="sampai_tanggal" class="form-control" value="<?= htmlspecialchars($sampai_tanggal) ?>">
  </div>
  <div class="form-group mr-2">
    <input type="text" name="keyword" class="form-control" placeholder="Cari NIK / Nama / No Tiket" value="<?= htmlspecialchars($keyword) ?>">
  </div>
  <button type="submit" class="btn btn-primary mr-2">Filter</button>
  <a href="handling_time_sarpras.php" class="btn btn-secondary mr-2">Reset</a>
  <a href="handling_time_sarpras_pdf.php?dari_tanggal=<?= $dari_tanggal ?>&sampai_tanggal=<?= $sampai_tanggal ?>&keyword=<?= urlencode($keyword) ?>" target="_blank" class="btn btn-danger">
    <i class="fas fa-file-pdf"></i> Cetak PDF
  </a>
</form>

<!-- Tabel -->
<div class="table-responsive-custom">
<table class="table table-bordered table-sm table-hover">
<thead class="thead-dark">
<tr class="text-center">
<th>No</th>
<th>Nomor Tiket</th>
<th>NIK</th>
<th>Nama</th>
<th>Jabatan</th>
<th>Unit Kerja</th>
<th>Kategori</th>
<th>Kendala</th>
<th>Status</th>
<th>Teknisi</th>
<th>Tgl Input</th>
<th>Diproses</th>
<th>Selesai</th>
<th>Validasi</th>
<th>Waktu Validasi</th>
<th>Respon Time</th>
<th>Selesai Time</th>
<th>Validasi Time</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
$q = "SELECT * FROM tiket_sarpras WHERE 1=1";
if (!empty($keyword)) {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $q .= " AND (nik LIKE '%$kw%' OR nama LIKE '%$kw%' OR nomor_tiket LIKE '%$kw%' OR kategori LIKE '%$kw%')";
}
if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
    $q .= " AND DATE(tanggal_input) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
}
$q .= " ORDER BY tanggal_input DESC";
$res = mysqli_query($conn, $q);
if(mysqli_num_rows($res) > 0){
  while($row = mysqli_fetch_assoc($res)){
    echo "<tr>";
    echo "<td class='text-center'>{$no}</td>";
    echo "<td>{$row['nomor_tiket']}</td>";
    echo "<td>{$row['nik']}</td>";
    echo "<td>{$row['nama']}</td>";
    echo "<td>{$row['jabatan']}</td>";
    echo "<td>{$row['unit_kerja']}</td>";
    echo "<td>{$row['kategori']}</td>";
    echo "<td>{$row['kendala']}</td>";
    
    $status = $row['status'];
    $badge = match(strtolower($status)){
        'menunggu' => 'warning',
        'diproses' => 'info',
        'selesai' => 'success',
        'tidak bisa diperbaiki' => 'danger',
        default => 'secondary'
    };
    echo "<td class='text-center'><span class='badge badge-{$badge}'>{$status}</span></td>";

    echo "<td>{$row['teknisi_nama']}</td>";
    echo "<td>".formatTanggal($row['tanggal_input'])."</td>";
    echo "<td>".formatTanggal($row['waktu_diproses'])."</td>";
    echo "<td>".formatTanggal($row['waktu_selesai'])."</td>";
    echo "<td>{$row['status_validasi']}</td>";
    echo "<td>".formatTanggal($row['waktu_validasi'])."</td>";
    echo "<td>".hitungDurasi($row['tanggal_input'], $row['waktu_diproses'])."</td>";
    echo "<td>".hitungDurasi($row['tanggal_input'], $row['waktu_selesai'])."</td>";
    echo "<td>".hitungDurasi($row['tanggal_input'], $row['waktu_validasi'])."</td>";
    echo "</tr>";
    $no++;
  }
}else{
  echo "<tr><td colspan='18' class='text-center'>Tidak ada data ditemukan.</td></tr>";
}
?>
</tbody>
</table>
</div>

</div>
</div>
</div>
</section>
</div>
</div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
