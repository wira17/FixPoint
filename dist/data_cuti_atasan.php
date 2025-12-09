<?php
session_start();
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__); 

// === Cek akses menu ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query) or die("Error cek akses: " . mysqli_error($conn));
if (mysqli_num_rows($result) == 0) {
  $_SESSION['flash_message'] = [
    'type' => 'error',
    'text' => 'Anda tidak memiliki akses ke halaman ini.'
  ];
  header("Location: dashboard.php");
  exit;
}

// === Proses ACC / Tolak Atasan ===
if (isset($_GET['aksi'], $_GET['id'])) {
  $id   = intval($_GET['id']);
  $aksi = $_GET['aksi'];

  $status = null;
  $status_atasan = null;

  if ($aksi === 'acc') {
    $status_atasan = "Disetujui";
    $status = "Menunggu HRD"; 
  } elseif ($aksi === 'tolak') {
    $status_atasan = "Ditolak";
    $status = "Ditolak Atasan";
  }

  if ($status && $status_atasan) {
    $acc_by = $_SESSION['nama'] ?? 'Sistem';

    $sql = "UPDATE pengajuan_cuti 
            SET status='" . mysqli_real_escape_string($conn, $status) . "',
                status_atasan='" . mysqli_real_escape_string($conn, $status_atasan) . "',
                acc_atasan_by='" . mysqli_real_escape_string($conn, $acc_by) . "',
                acc_atasan_time=NOW()
            WHERE id='$id'";
    $update = mysqli_query($conn, $sql);

    if (!$update) {
      $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => '❌ Gagal memperbarui status: ' . mysqli_error($conn)
      ];
    } else {
      $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => "✅ Status atasan diperbarui menjadi <b>$status_atasan</b> oleh <b>$acc_by</b>."
      ];
    }

    header("Location: data_cuti_atasan.php");
    exit;
  }
}

// === Ambil data pengajuan cuti ===
$sqlPengajuan = "
  SELECT p.*, u.nama AS nama_karyawan, mc.nama_cuti, d.nama AS nama_delegasi,
         p.acc_atasan_by, p.acc_atasan_time,
         GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti
  FROM pengajuan_cuti p
  JOIN users u ON p.karyawan_id = u.id
  JOIN master_cuti mc ON p.cuti_id = mc.id
  LEFT JOIN users d ON p.delegasi_id = d.id
  LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
  GROUP BY p.id
  ORDER BY p.id DESC
";
$dataPengajuan = mysqli_query($conn, $sqlPengajuan) or die("Error ambil data: " . mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>f.i.x.p.o.i.n.t</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .cuti-table { font-size: 13px; white-space: nowrap; }
    .cuti-table th, .cuti-table td { padding: 6px 10px; vertical-align: middle; }

    /* === Notifikasi di tengah layar (center screen) === */
    .flash-center {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 2000;
      min-width: 320px;
      max-width: 90%;
      text-align: center;
      padding: 25px 25px;
      border-radius: 12px;
      font-weight: 500;
      color: #fff;
      box-shadow: 0 6px 20px rgba(0,0,0,0.25);
      animation: popIn 0.4s ease-out;
    }
    .flash-success { background-color: #28a745; }
    .flash-error { background-color: #dc3545; }

    .flash-center i {
      display: block;
      font-size: 45px;
      margin-bottom: 10px;
    }

    @keyframes popIn {
      from { opacity: 0; transform: translate(-50%, -60%) scale(0.9); }
      to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
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

          <!-- === Notifikasi tengah layar === -->
          <?php if (isset($_SESSION['flash_message'])): ?>
            <?php 
              $msg = $_SESSION['flash_message'];
              $class = $msg['type'] === 'success' ? 'flash-success' : 'flash-error';
              $icon = $msg['type'] === 'success' ? 'fa-check-circle' : 'fa-times-circle';
            ?>
            <div class="flash-center <?= $class ?>" id="flashMsg">
              <i class="fas <?= $icon ?>"></i>
              <div><?= $msg['text'] ?></div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4 class="mb-0"><i class="fas fa-user-check"></i> Persetujuan Cuti (Atasan)</h4>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-bordered cuti-table">
                  <thead class="thead-dark">
                    <tr>
                      <th>No</th>
                      <th>Karyawan</th>
                      <th>Jenis Cuti</th>
                      <th>Tanggal</th>
                      <th>Delegasi</th>
                      <th>Alasan</th>
                      <th>Status</th>
                      <th>Disetujui Oleh</th>
                      <th>Waktu ACC/Tolak</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no=1; while($row = mysqli_fetch_assoc($dataPengajuan)): ?>
                      <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                        <td><?= htmlspecialchars($row['nama_cuti']) ?></td>
                        <td><?= htmlspecialchars($row['tanggal_cuti']) ?></td>
                        <td><?= htmlspecialchars($row['nama_delegasi'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['alasan']) ?></td>
                        <td>
                          <?php if ($row['status'] == "Menunggu Atasan"): ?>
                            <span class="badge bg-warning text-dark"><?= $row['status'] ?></span>
                          <?php elseif (strpos($row['status'], "Disetujui") !== false): ?>
                            <span class="badge bg-success"><?= $row['status'] ?></span>
                          <?php elseif (strpos($row['status'], "Ditolak") !== false): ?>
                            <span class="badge bg-danger"><?= $row['status'] ?></span>
                          <?php else: ?>
                            <span class="badge bg-secondary"><?= $row['status'] ?></span>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['acc_atasan_by'] ?? '-') ?></td>
                        <td><?= $row['acc_atasan_time'] ? date('d-m-Y H:i', strtotime($row['acc_atasan_time'])) : '-' ?></td>
                        <td>
                          <?php if ($row['status'] == "Menunggu Atasan"): ?>
                            <a href="data_cuti_atasan.php?aksi=acc&id=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-success"
                               onclick="return confirm('Yakin ACC cuti ini?')"><i class="fas fa-check"></i> ACC</a>
                            <a href="data_cuti_atasan.php?aksi=tolak&id=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Yakin Tolak cuti ini?')"><i class="fas fa-times"></i> Tolak</a>
                          <?php else: ?>
                            <em>-</em>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endwhile; ?>
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

<!-- JS -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script>
  $(document).ready(function() {
    setTimeout(function() {
      $("#flashMsg").fadeOut("slow");
    }, 4000);
  });
</script>

</body>
</html>
