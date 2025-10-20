<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// === CEK AKSES MENU ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' 
          AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// === Ambil nama user ===
$userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM users WHERE id = '$user_id'"));
$user_nama = $userData['nama'] ?? 'unknown';
$notif = '';

// === Ambil daftar unit kerja ===
$unit_kerja = mysqli_query($conn, "SELECT * FROM unit_kerja ORDER BY nama_unit ASC");

// === Daftar bulan & tahun ===
$bulan_list = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahun_list = range(2020, 2035);

// === Proses simpan data ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id_unit  = (int) $_POST['id_unit'];
    $bulan    = (int) $_POST['bulan'];
    $tahun    = (int) $_POST['tahun'];
    $menu_erm = trim($_POST['menu_erm']);

    if (empty($id_unit) || empty($bulan) || empty($tahun) || empty($menu_erm)) {
        $notif = "Semua field wajib diisi!";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO data_erm (id_unit, bulan, tahun, menu_erm, petugas_input, tanggal_input)
                                       VALUES ($id_unit, $bulan, $tahun, '$menu_erm', '$user_nama', NOW())");
        if ($insert) {
            $_SESSION['flash_message'] = "Data e-RM berhasil disimpan.";
            header("Location: erm.php");
            exit;
        } else {
            $notif = "Gagal menyimpan data.";
        }
    }
}

// === Filter ===
$filter_bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : '';
$filter_unit  = isset($_GET['id_unit']) && $_GET['id_unit'] !== '' ? (int)$_GET['id_unit'] : '';

$where = [];
if ($filter_bulan) $where[] = "de.bulan = $filter_bulan";
if ($filter_tahun) $where[] = "de.tahun = $filter_tahun";
if ($filter_unit)  $where[] = "de.id_unit = $filter_unit";
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// === Ambil data untuk tabel ===
$data_query = mysqli_query($conn, "SELECT 
    de.*, uk.nama_unit 
    FROM data_erm de 
    JOIN unit_kerja uk ON de.id_unit = uk.id 
    $where_sql 
    ORDER BY de.tahun DESC, de.bulan DESC, uk.nama_unit ASC");

$data_rows = [];
while ($r = mysqli_fetch_assoc($data_query)) {
    $data_rows[] = $r;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Data e-RM per Unit</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table thead th { background-color: #000; color: #fff; }
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
<div class="card-header"><h4>Manajemen e-RM per Unit</h4></div>
<div class="card-body">

<ul class="nav nav-tabs" id="ermTab" role="tablist">
<li class="nav-item"><a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab"><i class="fas fa-edit"></i> Input Data</a></li>
<li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab"><i class="fas fa-table"></i> Data Tersimpan</a></li>
</ul>

<div class="tab-content mt-4">

<!-- === Tab Input Data === -->
<div class="tab-pane fade show active" id="input" role="tabpanel">
<?php if ($notif): ?><div class="alert alert-danger"><?= $notif ?></div><?php endif; ?>
<?php if (isset($_SESSION['flash_message'])): ?>
<div id="notif-toast" class="alert alert-success text-center">
<?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
</div>
<?php endif; ?>

<form method="POST">
<div class="form-row">
<div class="form-group col-md-4">
<label>Unit Kerja</label>
<select name="id_unit" class="form-control" required>
<option value="">-- Pilih Unit Kerja --</option>
<?php mysqli_data_seek($unit_kerja, 0); while($row = mysqli_fetch_assoc($unit_kerja)): ?>
<option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_unit']) ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="form-group col-md-4">
<label>Bulan</label>
<select name="bulan" class="form-control" required>
<option value="">-- Pilih Bulan --</option>
<?php foreach($bulan_list as $num => $nama): ?>
<option value="<?= $num ?>"><?= $nama ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group col-md-4">
<label>Tahun</label>
<select name="tahun" class="form-control" required>
<option value="">-- Pilih Tahun --</option>
<?php foreach($tahun_list as $th): ?>
<option value="<?= $th ?>"><?= $th ?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="form-group">
<label>Menu e-RM yang digunakan</label>
<textarea name="menu_erm" class="form-control" rows="3" placeholder="Contoh: - RM Gizi&#10;- Skrining nutrisi pasien dewasa" required></textarea>
</div>

<div class="form-group">
<label>Petugas Input</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($user_nama) ?>" readonly>
</div>

<button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
</form>
</div>

<!-- === Tab Data Tersimpan === -->
<div class="tab-pane fade" id="data" role="tabpanel">
<form class="form-inline mb-3" method="GET">
<label class="mr-2">Unit:</label>
<select name="id_unit" class="form-control mr-2">
<option value="">-- Semua Unit --</option>
<?php mysqli_data_seek($unit_kerja, 0); while($row = mysqli_fetch_assoc($unit_kerja)): ?>
<option value="<?= $row['id'] ?>" <?= $row['id']==$filter_unit?'selected':'' ?>><?= htmlspecialchars($row['nama_unit']) ?></option>
<?php endwhile; ?>
</select>

<label class="mr-2">Bulan:</label>
<select name="bulan" class="form-control mr-2">
<option value="">-- Semua Bulan --</option>
<?php foreach($bulan_list as $num => $nama): ?>
<option value="<?= $num ?>" <?= $num==$filter_bulan?'selected':'' ?>><?= $nama ?></option>
<?php endforeach; ?>
</select>

<label class="mr-2">Tahun:</label>
<select name="tahun" class="form-control mr-2">
<option value="">-- Semua Tahun --</option>
<?php foreach($tahun_list as $th): ?>
<option value="<?= $th ?>" <?= $th==$filter_tahun?'selected':'' ?>><?= $th ?></option>
<?php endforeach; ?>
</select>

<button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter"></i> Filter</button>

<?php if ($filter_bulan || $filter_tahun || $filter_unit): ?>
<button type="button" class="btn btn-info" data-toggle="modal" data-target="#dataModal"><i class="fas fa-eye"></i> Lihat Data</button>
<?php endif; ?>
</form>

<div class="table-responsive">
<table class="table table-striped table-bordered nowrap">
<thead>
<tr>
<th>No</th>
<th>Unit Kerja</th>
<th>Bulan</th>
<th>Tahun</th>
<th>Menu e-RM</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
foreach ($data_rows as $row){
    $nama_bulan = $bulan_list[$row['bulan']] ?? '-';
    echo "<tr>
            <td>{$no}</td>
            <td>".htmlspecialchars($row['nama_unit'])."</td>
            <td>{$nama_bulan}</td>
            <td>{$row['tahun']}</td>
            <td>".nl2br(htmlspecialchars($row['menu_erm']))."</td>
          </tr>";
    $no++;
}
?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</section>
</div>
</div>
</div>

<!-- === Modal Data === -->
<div class="modal fade" id="dataModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Data e-RM Berdasarkan Filter</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>Unit Kerja</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Menu e-RM</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              foreach ($data_rows as $row) {
                  $nama_bulan = $bulan_list[$row['bulan']] ?? '-';
                  echo "<tr>
                          <td>{$no}</td>
                          <td>".htmlspecialchars($row['nama_unit'])."</td>
                          <td>{$nama_bulan}</td>
                          <td>{$row['tahun']}</td>
                          <td>".nl2br(htmlspecialchars($row['menu_erm']))."</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
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
