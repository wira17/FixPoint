<?php
include 'security.php';
include 'check_integrity.php';
include 'koneksi.php';

// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil ID dan nama karyawan dari session
$karyawan_id = $_SESSION['user_id'] ?? 0;
$nama_user   = $_SESSION['nama'] ?? 'Pengguna';

// Tahun berjalan
$tahun = date('Y');

// Ambil data cuti per kategori
$sql = "SELECT mc.nama_cuti, mc.id as cuti_id, jc.lama_hari, jc.sisa_hari,
               (jc.lama_hari - jc.sisa_hari) AS terpakai
        FROM jatah_cuti jc
        JOIN master_cuti mc ON jc.cuti_id = mc.id
        WHERE jc.karyawan_id = ? AND jc.tahun = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $karyawan_id, $tahun);
$stmt->execute();
$result = $stmt->get_result();

// Simpan ke array
$dataCuti = [];
while($row = $result->fetch_assoc()){
  $dataCuti[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .card-statistic-1 { padding: 5px; margin-bottom: 5px; font-size: 13px; cursor: pointer; }
    .card-statistic-1 .card-icon { font-size: 14px; padding: 4px; width: 30px; height: 30px; }
    .card-statistic-1 .card-header h4 { font-size: 11px; margin-bottom: 2px; }
    .card-statistic-1 .card-body { font-size: 14px; font-weight: bold; }
    .card-statistic-1 .card-wrap { padding-left: 8px; }
    .row > [class*='col-'] { padding-right: 5px; padding-left: 5px; margin-bottom: 5px; }
    .icon-cuti { color: #17a2b8; margin-right: 5px; }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">

    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
      <section class="section">
        <div class="section-header">
          <h1>Dashboard</h1>
        </div>

        <div class="row">
          <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <!-- Card HRD/SDM -->
            <div class="card card-statistic-1" data-toggle="modal" data-target="#modalHRD">
              <div class="card-icon bg-info"><i class="fas fa-users-cog"></i></div>
              <div class="card-wrap">
                <div class="card-header"><h4>HRD / SDM</h4></div>
                <div class="card-body">Info Cuti</div>
              </div>
            </div>
          </div>
        </div>

      </section>
    </div>

  </div>
</div>

<!-- Modal HRD -->
<div class="modal fade" id="modalHRD" tabindex="-1" role="dialog" aria-labelledby="modalHRDLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalHRDLabel">
          <i class="fas fa-users-cog"></i> Informasi Cuti <?= $tahun; ?> - <?= htmlspecialchars($nama_user); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th><i class="fas fa-list icon-cuti"></i> Jenis Cuti</th>
              <th><i class="fas fa-calendar-plus icon-cuti"></i> Jatah</th>
              <th><i class="fas fa-calendar-check icon-cuti"></i> Terpakai</th>
              <th><i class="fas fa-calendar-minus icon-cuti"></i> Sisa</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($dataCuti)): ?>
              <?php foreach($dataCuti as $cuti): ?>
              <tr>
                <td><?= htmlspecialchars($cuti['nama_cuti']); ?></td>
                <td><?= $cuti['lama_hari']; ?> Hari</td>
                <td><?= $cuti['terpakai']; ?> Hari</td>
                <td><?= $cuti['sisa_hari']; ?> Hari</td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center">Belum ada data cuti</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- JS Scripts -->
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
