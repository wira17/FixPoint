<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// === Cek akses menu ===
$qAkses = "SELECT 1 FROM akses_menu 
           JOIN menu ON akses_menu.menu_id = menu.id 
           WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$rAkses = mysqli_query($conn, $qAkses);
if (mysqli_num_rows($rAkses) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// === Ambil daftar karyawan untuk dropdown ===
$karyawanList = mysqli_query($conn, "SELECT id, nama, nik, tempat_lahir, tgl_lahir, pendidikan FROM users ORDER BY nama ASC");

// === Proses simpan surat tugas ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $karyawan_id = intval($_POST['karyawan_id']);
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $ttl = mysqli_real_escape_string($conn, $_POST['ttl']);
    $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan']);
    $unit_penempatan = mysqli_real_escape_string($conn, $_POST['unit_penempatan']);

    if ($karyawan_id <= 0 || empty($nik) || empty($ttl) || empty($pendidikan) || empty($unit_penempatan)) {
        $_SESSION['flash_message'] = "Semua field wajib diisi!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $sql = "INSERT INTO surat_tugas 
                    (karyawan_id, nik, ttl, pendidikan, unit_penempatan, created_by, created_at)
                    VALUES ('$karyawan_id', '$nik', '$ttl', '$pendidikan', '$unit_penempatan', '$user_id', NOW())";
            mysqli_query($conn, $sql);
            mysqli_commit($conn);
            $_SESSION['flash_message'] = "Surat tugas berhasil disimpan.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['flash_message'] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }

    header("Location: surat_tugas.php");
    exit;
}

// === Ambil data surat tugas untuk tabel ===
$dataSurat = mysqli_query($conn, "
    SELECT s.*, u.nama AS nama_karyawan
    FROM surat_tugas s
    JOIN users u ON s.karyawan_id=u.id
    ORDER BY s.id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Tugas</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.flash-center {
  position: fixed; top: 20%; left: 50%; transform: translate(-50%, -50%);
  z-index: 1050; min-width: 300px; max-width: 90%; text-align: center;
  padding: 15px; border-radius: 8px; font-weight: 500;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.surat-table { font-size: 13px; white-space: nowrap; }
.surat-table th, .surat-table td { padding: 6px 10px; vertical-align: middle; }
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

        <?php if(isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-info flash-center" id="flashMsg"><?= $_SESSION['flash_message']; ?></div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <div class="card">
          <div class="card-header"><h4>Surat Tugas Penempatan Kerja</h4></div>
          <div class="card-body">
            <ul class="nav nav-tabs" id="suratTab" role="tablist">
              <li class="nav-item"><a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Surat Tugas</a></li>
              <li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Surat Tugas</a></li>
            </ul>

            <div class="tab-content mt-3">
              <!-- Form Input -->
              <div class="tab-pane fade show active" id="input" role="tabpanel">
                <form method="post">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama</label>
                        <select name="karyawan_id" id="karyawan_id" class="form-control" required>
                          <option value="">-- Pilih Karyawan --</option>
                          <?php
                          mysqli_data_seek($karyawanList,0);
                          while($k = mysqli_fetch_assoc($karyawanList)): ?>
                            <option value="<?= $k['id'] ?>" 
                                    data-nik="<?= htmlspecialchars($k['nik']) ?>"
                                    data-ttl="<?= htmlspecialchars($k['tempat_lahir'].', '.$k['tgl_lahir']) ?>"
                                    data-pendidikan="<?= htmlspecialchars($k['pendidikan']) ?>">
                              <?= htmlspecialchars($k['nama']) ?>
                            </option>
                          <?php endwhile; ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-id-card"></i> NIK</label>
                        <input type="text" name="nik" id="nik" class="form-control" readonly required>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Tempat / Tanggal Lahir</label>
                        <input type="text" name="ttl" id="ttl" class="form-control" readonly required>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Pendidikan</label>
                        <input type="text" name="pendidikan" id="pendidikan" class="form-control" readonly required>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-building"></i> Unit Penempatan</label>
                        <input type="text" name="unit_penempatan" class="form-control" required>
                      </div>

                      <button type="submit" name="simpan" class="btn btn-primary mt-2">
                        <i class="fas fa-paper-plane"></i> Simpan
                      </button>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Tabel Data -->
              <div class="tab-pane fade" id="data" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-striped table-bordered surat-table">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Nama Karyawan</th>
                        <th>NIK</th>
                        <th>TTL</th>
                        <th>Pendidikan</th>
                        <th>Unit Penempatan</th>
                        <th>Tanggal Input</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $no=1; while($row = mysqli_fetch_assoc($dataSurat)): ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                          <td><?= htmlspecialchars($row['nik']) ?></td>
                          <td><?= htmlspecialchars($row['ttl']) ?></td>
                          <td><?= htmlspecialchars($row['pendidikan']) ?></td>
                          <td><?= htmlspecialchars($row['unit_penempatan']) ?></td>
                          <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                          <td class="text-center">
                            <a href="cetak_surat_tugas.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-info btn-sm">
                              <i class="fas fa-print"></i>
                            </a>
                          </td>
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
  setTimeout(function(){ $("#flashMsg").fadeOut("slow"); },3000);

  // Auto isi NIK, TTL, Pendidikan ketika pilih karyawan
  $('#karyawan_id').change(function(){
    var selected = $(this).find(':selected');
    $('#nik').val(selected.data('nik') || '');
    $('#ttl').val(selected.data('ttl') || '');
    $('#pendidikan').val(selected.data('pendidikan') || '');
  });
});
</script>
</body>
</html>
