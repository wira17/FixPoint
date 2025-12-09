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

// === Daftar endpoint ===
$endpoints = [
    'Encounter', 'Condition', 'Observation', 'Procedure', 'Composition',
    'Medication', 'MedicationRequest', 'MedicationDispense', 'AllergyIntolerance',
    'ImagingStudy', 'ServiceRequest', 'ClinicalImpression', 'Immunization',
    'QuestionnaireResponse', 'MedicationStatement', 'CarePlan', 'Specimen',
    'DiagnosticReport', 'EpisodeOfCare'
];

// === Simpan data ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $bulan    = (int) $_POST['bulan'];
    $tahun    = (int) $_POST['tahun'];
    $endpoint = mysqli_real_escape_string($conn, $_POST['endpoint']);
    $jumlah   = (int) $_POST['jumlah'];

    if ($bulan <= 0 || $tahun <= 0 || empty($endpoint) || $jumlah < 0) {
        $notif = "Semua field wajib diisi dengan benar!";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO satu_sehat (bulan, tahun, endpoint, jumlah, petugas_input, tanggal_input)
            VALUES ($bulan, $tahun, '$endpoint', $jumlah, '$user_nama', NOW())");
        if ($insert) {
            $_SESSION['flash_message'] = "Data SATUSEHAT berhasil disimpan.";
            header("Location: satu_sehat.php");
            exit;
        } else {
            $notif = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
}

// === Filter ===
$filter_bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : '';
$filter_endpoint = isset($_GET['endpoint']) && $_GET['endpoint'] !== '' ? mysqli_real_escape_string($conn, $_GET['endpoint']) : '';

$where = [];
if ($filter_bulan) $where[] = "bulan = $filter_bulan";
if ($filter_tahun) $where[] = "tahun = $filter_tahun";
if ($filter_endpoint) $where[] = "endpoint = '$filter_endpoint'";
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$data_query = mysqli_query($conn, "SELECT * FROM satu_sehat $where_sql ORDER BY tahun DESC, bulan DESC, endpoint ASC");

// === Data untuk grafik ===
if ($filter_bulan && $filter_tahun) {
    // Jika filter bulan & tahun aktif → tampilkan semua endpoint
    $chart_query = mysqli_query($conn, "
        SELECT endpoint, jumlah 
        FROM satu_sehat 
        WHERE bulan = $filter_bulan AND tahun = $filter_tahun 
        ORDER BY endpoint ASC
    ");
    $chart_labels = [];
    $chart_values = [];
    while ($row = mysqli_fetch_assoc($chart_query)) {
        $chart_labels[] = $row['endpoint'];
        $chart_values[] = (int)$row['jumlah'];
    }
} else {
    // Jika tidak ada filter → tampilkan total per bulan
    $chart_query = mysqli_query($conn, "
        SELECT tahun, bulan, SUM(jumlah) AS total 
        FROM satu_sehat 
        GROUP BY tahun, bulan 
        ORDER BY tahun ASC, bulan ASC
    ");
    $chart_labels = [];
    $chart_values = [];
    while ($row = mysqli_fetch_assoc($chart_query)) {
        $chart_labels[] = $bulan_list[$row['bulan']] . ' ' . $row['tahun'];
        $chart_values[] = (int)$row['total'];
    }
}

// === Tentukan tab aktif ===
$active_tab = (isset($_GET['bulan']) || isset($_GET['tahun']) || isset($_GET['endpoint'])) ? 'data' : 'input';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Data SATUSEHAT</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table thead th { background-color: #000 !important; color: #fff !important; }
#notif-toast { position: fixed; top: 20px; right: 20px; z-index: 9999; display: none; }
</style>

<style>
.modal-xl {
  max-width: 95% !important; /* agar modal hampir full layar */
}
#grafikSatuSehat {
  width: 90% !important;
  height: 90% !important;
}
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
<div class="card-header"><h4>Pengiriman Data SATUSEHAT KEMENKES</h4></div>
<div class="card-body">

<ul class="nav nav-tabs" id="satuSehatTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='input'?'active':'' ?>" id="input-tab" data-toggle="tab" href="#input" role="tab">
      <i class="fas fa-edit"></i> Input Data
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab=='data'?'active':'' ?>" id="data-tab" data-toggle="tab" href="#data" role="tab">
      <i class="fas fa-table"></i> Data Tersimpan
    </a>
  </li>
</ul>

<div class="tab-content mt-4">

<!-- Tab Input -->
<div class="tab-pane fade <?= $active_tab=='input'?'show active':'' ?>" id="input" role="tabpanel">
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
  <div class="form-group col-md-3">
    <label><i class="fas fa-link"></i> Endpoint</label>
    <select name="endpoint" class="form-control" required>
      <option value="">-- Pilih Endpoint --</option>
      <?php foreach($endpoints as $ep): ?>
      <option value="<?= $ep ?>"><?= $ep ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group col-md-3">
    <label><i class="fas fa-sort-numeric-up"></i> Jumlah</label>
    <input type="number" name="jumlah" class="form-control" min="0" required>
  </div>
</div>

<div class="form-group">
  <label><i class="fas fa-user"></i> Petugas Input</label>
  <input type="text" class="form-control" value="<?= htmlspecialchars($user_nama) ?>" readonly>
</div>

<button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
</form>
</div>

<!-- Tab Data -->
<div class="tab-pane fade <?= $active_tab=='data'?'show active':'' ?>" id="data" role="tabpanel">
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

  <label class="mr-2"><i class="fas fa-link"></i> Endpoint:</label>
  <select name="endpoint" class="form-control mr-2">
    <option value="">-- Semua Endpoint --</option>
    <?php foreach($endpoints as $ep): ?>
    <option value="<?= $ep ?>" <?= $ep==$filter_endpoint?'selected':'' ?>><?= $ep ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter"></i> Filter</button>
  
  <!-- Tombol Grafik -->
  <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalGrafik">
    <i class="fas fa-chart-line"></i> Tampilkan Grafik
  </button>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>No</th>
      <th>Bulan</th>
      <th>Tahun</th>
      <th>Endpoint</th>
      <th>Jumlah</th>
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
    <td><?= htmlspecialchars($d['endpoint']) ?></td>
    <td><?= number_format($d['jumlah']) ?></td>
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

<!-- Modal Grafik -->
<div class="modal fade" id="modalGrafik" tabindex="-1" role="dialog" aria-labelledby="modalGrafikLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document"> <!-- ubah dari modal-lg ke modal-xl -->
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-chart-line"></i> Grafik Pengiriman SATUSEHAT</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" style="height: 80vh;"> <!-- tambahkan tinggi dinamis -->
        <canvas id="grafikSatuSehat" style="width:100%; height:100%;"></canvas>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(function(){
  var toast = $('#notif-toast');
  if(toast.length){
    toast.fadeIn(300).delay(2000).fadeOut(500);
  }

  // === Grafik Chart.js ===
  var ctx = document.getElementById('grafikSatuSehat').getContext('2d');
  var chartLabels = <?= json_encode($chart_labels) ?>;
  var chartValues = <?= json_encode($chart_values) ?>;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Jumlah Data',
        data: chartValues,
        borderColor: '#17a2b8',
        backgroundColor: 'rgba(23,162,184,0.2)',
        borderWidth: 3,
        fill: true,
        tension: 0.3,
        pointBackgroundColor: '#17a2b8'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: true, position: 'bottom' },
        title: {
          display: true,
          text: 'Grafik Pengiriman SATUSEHAT'
        }
      },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Jumlah' } },
        x: { title: { display: true, text: 'Endpoint / Bulan' } }
      }
    }
  });
});
</script>

</body>
</html>
