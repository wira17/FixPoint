<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// === CEK AKSES MENU ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// === Ambil nama user ===
$userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM users WHERE id = '$user_id'"));
$user_nama = $userData['nama'] ?? 'unknown';
$notif = '';

// === Daftar bulan & tahun ===
$bulan_list = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahun_list = range(2020, 2035);

// === Simpan data ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $bulan   = (int) $_POST['bulan'];
    $tahun   = (int) $_POST['tahun'];
    $progres = mysqli_real_escape_string($conn, $_POST['progres']);

    if ($bulan <= 0 || $tahun <= 0 || empty($progres)) {
        $notif = "Semua field wajib diisi!";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO progres_kerja (bulan, tahun, progres, petugas_input, tanggal_input)
            VALUES ($bulan, $tahun, '$progres', '$user_nama', NOW())");
        if ($insert) {
            $_SESSION['flash_message'] = "Data progres kerja berhasil disimpan.";
            header("Location: progres_kerja.php");
            exit;
        } else {
            $notif = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
}

// === Filter ===
$filter_bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : '';

$where = [];
if ($filter_bulan) $where[] = "bulan = $filter_bulan";
if ($filter_tahun) $where[] = "tahun = $filter_tahun";
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$data_query = mysqli_query($conn, "SELECT * FROM progres_kerja $where_sql ORDER BY tahun DESC, bulan DESC");

// === Tentukan tab aktif ===
$active_tab = 'input';
if (!empty($_GET['bulan']) || !empty($_GET['tahun'])) {
    $active_tab = 'data'; // jika user klik Filter
} elseif (!empty($_POST['simpan'])) {
    $active_tab = 'input'; // jika user menyimpan data
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Progres Kerja</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table thead th { background-color: #000 !important; color: #fff !important; }
#notif-toast { position: fixed; top: 20px; right: 20px; z-index: 9999; display: none; }
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
<div class="card-header"><h4>Input dan Monitoring Progres Kerja</h4></div>
<div class="card-body">

<ul class="nav nav-tabs" id="progresTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab == 'input' ? 'active' : '' ?>" id="input-tab" data-toggle="tab" href="#input" role="tab">
      <i class="fas fa-edit"></i> Input Progres
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab == 'data' ? 'active' : '' ?>" id="data-tab" data-toggle="tab" href="#data" role="tab">
      <i class="fas fa-table"></i> Data Tersimpan
    </a>
  </li>
</ul>

<div class="tab-content mt-4">

<!-- TAB INPUT -->
<div class="tab-pane fade <?= $active_tab == 'input' ? 'show active' : '' ?>" id="input" role="tabpanel">
<?php if ($notif): ?><div class="alert alert-danger"><?= $notif ?></div><?php endif; ?>
<?php if (isset($_SESSION['flash_message'])): ?>
<div id="notif-toast" class="alert alert-success text-center">
<?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
</div>
<?php endif; ?>

<form method="POST">
<div class="form-row">
  <div class="form-group col-md-3">
    <label><i class="fas fa-calendar-alt"></i> Bulan</label>
    <select name="bulan" class="form-control" required>
      <option value="">-- Pilih Bulan --</option>
      <?php foreach($bulan_list as $num => $nama): ?>
      <option value="<?= $num ?>"><?= $nama ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group col-md-3">
    <label><i class="fas fa-calendar"></i> Tahun</label>
    <select name="tahun" class="form-control" required>
      <option value="">-- Pilih Tahun --</option>
      <?php foreach($tahun_list as $th): ?>
      <option value="<?= $th ?>"><?= $th ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group col-md-6">
    <label><i class="fas fa-tasks"></i> Progres Kerja</label>
    <input type="text" name="progres" class="form-control" placeholder="Ketik progres kerja..." required>
  </div>
</div>

<div class="form-group">
  <label><i class="fas fa-user"></i> Petugas Input</label>
  <input type="text" class="form-control" value="<?= htmlspecialchars($user_nama) ?>" readonly>
</div>

<button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
</form>
</div>

<!-- TAB DATA -->
<div class="tab-pane fade <?= $active_tab == 'data' ? 'show active' : '' ?>" id="data" role="tabpanel">
<form class="form-inline mb-3" method="GET">
  <label class="mr-2"><i class="fas fa-calendar-alt"></i> Bulan:</label>
  <select name="bulan" class="form-control mr-2">
    <option value="">-- Semua Bulan --</option>
    <?php foreach($bulan_list as $num => $nama): ?>
    <option value="<?= $num ?>" <?= $num==$filter_bulan?'selected':'' ?>><?= $nama ?></option>
    <?php endforeach; ?>
  </select>

  <label class="mr-2"><i class="fas fa-calendar"></i> Tahun:</label>
  <select name="tahun" class="form-control mr-2">
    <option value="">-- Semua Tahun --</option>
    <?php foreach($tahun_list as $th): ?>
    <option value="<?= $th ?>" <?= $th==$filter_tahun?'selected':'' ?>><?= $th ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>No</th>
      <th>Bulan</th>
      <th>Tahun</th>
      <th>Progres</th>
    </tr>
  </thead>
  <tbody>
  <?php 
  $no=1;
  while($d=mysqli_fetch_assoc($data_query)):
    $bulan_nama = $bulan_list[$d['bulan']] ?? '-';
  ?>
  <tr>
    <td><?= $no++ ?></td>
    <td><?= $bulan_nama ?></td>
    <td><?= $d['tahun'] ?></td>
    <td><?= htmlspecialchars($d['progres']) ?></td>
  </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>
</div>

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

<script>
$(document).ready(function(){
  // Simpan tab terakhir yang dibuka
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    localStorage.setItem('activeTabProgres', $(e.target).attr('href'));
  });

  // Ambil tab terakhir dari localStorage
  var activeTab = localStorage.getItem('activeTabProgres');
  if (activeTab) {
    $('#progresTab a[href="' + activeTab + '"]').tab('show');
  }

  // Tampilkan notifikasi sukses
  var toast = $('#notif-toast');
  if(toast.length){
    toast.fadeIn(300).delay(2000).fadeOut(500);
  }
});
</script>

</body>
</html>
