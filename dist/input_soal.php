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

// === Ambil data user login ===
$userData = mysqli_query($conn, "SELECT nama, nik FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($userData);
$nama_user = $user['nama'] ?? '';
$nik_user = $user['nik'] ?? '';

// === TAMBAH SOAL ===
if (isset($_POST['simpan'])) {
  $judul_soal_id = mysqli_real_escape_string($conn, $_POST['judul_soal_id']);
  $soal = mysqli_real_escape_string($conn, trim($_POST['soal']));
  $pilihan_a = mysqli_real_escape_string($conn, trim($_POST['pilihan_a']));
  $pilihan_b = mysqli_real_escape_string($conn, trim($_POST['pilihan_b']));
  $pilihan_c = mysqli_real_escape_string($conn, trim($_POST['pilihan_c']));
  $pilihan_d = mysqli_real_escape_string($conn, trim($_POST['pilihan_d']));
  $jawaban_benar = mysqli_real_escape_string($conn, $_POST['jawaban_benar']);

  $insert = mysqli_query($conn, "INSERT INTO soal (
      judul_soal_id, soal, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar
    ) VALUES (
      '$judul_soal_id', '$soal', '$pilihan_a', '$pilihan_b', '$pilihan_c', '$pilihan_d', '$jawaban_benar'
    )");

  if ($insert) {
    $_SESSION['flash_message'] = "✅ Soal berhasil ditambahkan.";
    echo "<script>location.href='input_soal.php';</script>";
    exit;
  } else {
    echo "<div class='alert alert-danger'>❌ Gagal menambahkan soal.</div>";
  }
}

// === HAPUS SOAL ===
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM soal WHERE id = $id");
  $_SESSION['flash_message'] = "🗑️ Soal berhasil dihapus.";
  echo "<script>location.href='input_soal.php';</script>";
  exit;
}

// === FILTER BERDASARKAN JUDUL SOAL ===
$filter_judul = isset($_GET['judul_filter']) ? (int)$_GET['judul_filter'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Data Soal</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <style>
    #notif-toast {
      position: fixed;
      top: 15%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 9999;
      display: none;
      min-width: 320px;
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
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4><i class="fas fa-list"></i> Data Soal</h4>
              <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus-circle"></i> Tambah Soal
              </button>
            </div>

            <div class="card-body">

              <!-- Dropdown Filter Berdasarkan Judul Soal -->
              <form method="GET" class="form-inline mb-3">
                <label class="mr-2"><i class="fas fa-filter"></i> Filter Judul Soal:</label>
                <select name="judul_filter" class="form-control mr-2" onchange="this.form.submit()">
                  <option value="0">-- Semua Judul Soal --</option>
                  <?php
                  $judulList = mysqli_query($conn, "SELECT id, judul_soal FROM judul_soal ORDER BY judul_soal ASC");
                  while ($judul = mysqli_fetch_assoc($judulList)):
                  ?>
                    <option value="<?= $judul['id']; ?>" <?= ($filter_judul == $judul['id']) ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($judul['judul_soal']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </form>

              <?php
              if (isset($_SESSION['flash_message'])) {
                echo "<div id='notif-toast' class='alert alert-info text-center'>{$_SESSION['flash_message']}</div>";
                unset($_SESSION['flash_message']);
              }

              // === Pagination ===
              $limit = 10;
              $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
              if ($page < 1) $page = 1;
              $offset = ($page - 1) * $limit;

              $where = "";
              if ($filter_judul > 0) {
                $where = "WHERE soal.judul_soal_id = '$filter_judul'";
              }

              $totalQuery = "SELECT COUNT(*) as total FROM soal $where";
              $totalResult = mysqli_query($conn, $totalQuery);
              $totalRows = mysqli_fetch_assoc($totalResult)['total'];
              $totalPages = ceil($totalRows / $limit);

              $query = "SELECT soal.*, judul_soal.judul_soal 
                        FROM soal 
                        JOIN judul_soal ON soal.judul_soal_id = judul_soal.id 
                        $where 
                        ORDER BY soal.id DESC 
                        LIMIT $offset, $limit";
              $result = mysqli_query($conn, $query);
              $no = $offset + 1;
              ?>

              <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>Judul Soal</th>
                      <th>Soal</th>
                      <th>Pilihan A</th>
                      <th>Pilihan B</th>
                      <th>Pilihan C</th>
                      <th>Pilihan D</th>
                      <th>Jawaban Benar</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                      <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                          <td class="text-center"><?= $no++; ?></td>
                          <td><?= htmlspecialchars($row['judul_soal']); ?></td>
                          <td><?= htmlspecialchars($row['soal']); ?></td>
                          <td><?= htmlspecialchars($row['pilihan_a']); ?></td>
                          <td><?= htmlspecialchars($row['pilihan_b']); ?></td>
                          <td><?= htmlspecialchars($row['pilihan_c']); ?></td>
                          <td><?= htmlspecialchars($row['pilihan_d']); ?></td>
                          <td class="text-center font-weight-bold"><?= strtoupper(htmlspecialchars($row['jawaban_benar'])); ?></td>
                          <td class="text-center">
                            <a href="?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus soal ini?')">
                              <i class="fas fa-trash-alt"></i> Hapus
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="9" class="text-center">Tidak ada data soal.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <?php if ($totalPages > 1): ?>
                <nav>
                  <ul class="pagination justify-content-center mt-3">
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=1&judul_filter=<?= $filter_judul ?>">⏮️</a>
                    </li>
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=<?= ($page-1) ?>&judul_filter=<?= $filter_judul ?>">⬅️</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): ?>
                      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&judul_filter=<?= $filter_judul ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=<?= ($page+1) ?>&judul_filter=<?= $filter_judul ?>">➡️</a>
                    </li>
                    <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=<?= $totalPages ?>&judul_filter=<?= $filter_judul ?>">⏭️</a>
                    </li>
                  </ul>
                </nav>
              <?php endif; ?>

            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Soal</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><i class="fas fa-heading"></i> Judul Soal</label>
            <select name="judul_soal_id" class="form-control" required>
              <option value="">-- Pilih Judul Soal --</option>
              <?php
              $judulQuery = mysqli_query($conn, "SELECT id, judul_soal FROM judul_soal ORDER BY judul_soal ASC");
              while ($judul = mysqli_fetch_assoc($judulQuery)): ?>
                <option value="<?= $judul['id']; ?>"><?= htmlspecialchars($judul['judul_soal']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label><i class="fas fa-question-circle"></i> Soal</label>
            <textarea name="soal" class="form-control" rows="3" placeholder="Tulis pertanyaan di sini..." required></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Pilihan A</label>
              <input type="text" name="pilihan_a" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
              <label>Pilihan B</label>
              <input type="text" name="pilihan_b" class="form-control" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Pilihan C</label>
              <input type="text" name="pilihan_c" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
              <label>Pilihan D</label>
              <input type="text" name="pilihan_d" class="form-control" required>
            </div>
          </div>
          <div class="form-group">
            <label><i class="fas fa-check-circle"></i> Jawaban Benar</label>
            <select name="jawaban_benar" class="form-control" required>
              <option value="">-- Pilih Jawaban Benar --</option>
              <option value="a">A</option>
              <option value="b">B</option>
              <option value="c">C</option>
              <option value="d">D</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Tutup</button>
        </div>
      </form>
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
$(document).ready(function () {
  var toast = $('#notif-toast');
  if (toast.length) {
    toast.fadeIn(300).delay(2500).fadeOut(500);
  }
});
</script>
</body>
</html>
