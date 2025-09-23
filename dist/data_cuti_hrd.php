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
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// === Proses ACC / Tolak HRD ===
if (isset($_GET['aksi'], $_GET['id'])) {
  $id   = intval($_GET['id']);
  $aksi = $_GET['aksi'];
  $acc_by = $_SESSION['nama'] ?? 'Sistem';

  if ($aksi === 'acc') {
    $status = "Disetujui HRD";
    $status_hrd = "Disetujui";

    // === Hitung lama cuti (jumlah tanggal di detail) ===
    $q = mysqli_query($conn, "
      SELECT p.karyawan_id, p.cuti_id, COUNT(pc.id) AS lama_hari
      FROM pengajuan_cuti p
      LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
      WHERE p.id='$id'
      GROUP BY p.id
    ") or die("Error ambil cuti: " . mysqli_error($conn));
    $cuti = mysqli_fetch_assoc($q);

    if ($cuti) {
      $karyawan_id = $cuti['karyawan_id'];
      $cuti_id     = $cuti['cuti_id'];
      $lama_hari   = $cuti['lama_hari'];
      $tahun       = date('Y');

      // cek jatah cuti
      $cek = mysqli_query($conn, "
        SELECT * FROM jatah_cuti 
        WHERE karyawan_id='$karyawan_id' AND cuti_id='$cuti_id' AND tahun='$tahun'
        LIMIT 1
      ") or die("Error cek jatah: " . mysqli_error($conn));
      $jatah = mysqli_fetch_assoc($cek);

      if ($jatah) {
        if ($jatah['sisa_hari'] >= $lama_hari) {
          $newSisa = $jatah['sisa_hari'] - $lama_hari;
          mysqli_query($conn, "UPDATE jatah_cuti 
                               SET sisa_hari='$newSisa' 
                               WHERE id='{$jatah['id']}'") 
                               or die("Error update jatah: " . mysqli_error($conn));
          $_SESSION['flash_message'] = "✅ Cuti disetujui HRD oleh <b>$acc_by</b>. Sisa cuti sekarang <b>$newSisa hari</b>.";
        } else {
          $_SESSION['flash_message'] = "❌ Sisa cuti hanya {$jatah['sisa_hari']} hari, tapi diajukan $lama_hari hari.";
          header("Location: data_cuti_hrd.php");
          exit;
        }
      } else {
        $_SESSION['flash_message'] = "❌ Jatah cuti untuk karyawan ini belum diinput.";
        header("Location: data_cuti_hrd.php");
        exit;
      }
    }
  } elseif ($aksi === 'tolak') {
    $status = "Ditolak HRD";
    $status_hrd = "Ditolak";
  }

  if (isset($status, $status_hrd)) {
    $sql = "UPDATE pengajuan_cuti 
            SET status='" . mysqli_real_escape_string($conn, $status) . "',
                status_hrd='" . mysqli_real_escape_string($conn, $status_hrd) . "',
                acc_hrd_by='" . mysqli_real_escape_string($conn, $acc_by) . "'
            WHERE id='$id'";
    $update = mysqli_query($conn, $sql);

    if (!$update) {
      $_SESSION['flash_message'] = "❌ Gagal update status HRD: " . mysqli_error($conn);
    } else {
      if (!isset($_SESSION['flash_message'])) {
        $_SESSION['flash_message'] = "✅ Pengajuan cuti diperbarui menjadi <b>$status</b> oleh <b>$acc_by</b>.";
      }
    }

    header("Location: data_cuti_hrd.php");
    exit;
  }
}

// === Ambil data pengajuan cuti ===
$sqlPengajuan = "
  SELECT p.*, u.nama AS nama_karyawan, mc.nama_cuti, d.nama AS nama_delegasi,
         p.acc_hrd_by,
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
    .flash-center {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
      z-index: 1050; min-width: 320px; max-width: 90%; text-align: center;
      padding: 15px; border-radius: 8px; font-weight: 500;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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

          <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-info flash-center" id="flashMsg">
              <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4 class="mb-0">Persetujuan Cuti (HRD)</h4>
            </div>

            <div class="card-body">
              <!-- Data Pengajuan -->
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
                      <th>Disetujui HRD Oleh</th>
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
                          <?php if ($row['status'] == "Menunggu HRD"): ?>
                            <span class="badge bg-warning text-dark"><?= $row['status'] ?></span>
                          <?php elseif (strpos($row['status'], "Disetujui") !== false): ?>
                            <span class="badge bg-success"><?= $row['status'] ?></span>
                          <?php elseif (strpos($row['status'], "Ditolak") !== false): ?>
                            <span class="badge bg-danger"><?= $row['status'] ?></span>
                          <?php else: ?>
                            <span class="badge bg-secondary"><?= $row['status'] ?></span>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['acc_hrd_by'] ?? '-') ?></td>
                        <td>
                          <?php if ($row['status'] == "Menunggu HRD"): ?>
                            <a href="data_cuti_hrd.php?aksi=acc&id=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-success"
                               onclick="return confirm('Yakin ACC cuti ini?')"><i class="fas fa-check"></i> ACC</a>
                            <a href="data_cuti_hrd.php?aksi=tolak&id=<?= $row['id'] ?>" 
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
    }, 3500);
  });
</script>

</body>
</html>
