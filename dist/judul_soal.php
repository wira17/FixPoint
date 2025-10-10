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

// === TAMBAH JUDUL SOAL ===
if (isset($_POST['simpan'])) {
  $judul_soal = mysqli_real_escape_string($conn, trim($_POST['judul_soal']));
  $durasi = (int)$_POST['durasi'];
  $tanggal_buat = mysqli_real_escape_string($conn, $_POST['tanggal_buat']);
  $tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
  $tanggal_selesai = mysqli_real_escape_string($conn, $_POST['tanggal_selesai']);

  $insert = mysqli_query($conn, "INSERT INTO judul_soal (user_id, judul_soal, durasi, tanggal_buat, tanggal_mulai, tanggal_selesai) 
                                 VALUES ('$user_id', '$judul_soal', '$durasi', '$tanggal_buat', '$tanggal_mulai', '$tanggal_selesai')");
  if ($insert) {
    $_SESSION['flash_message'] = "✅ Judul soal berhasil ditambahkan.";
    echo "<script>location.href='judul_soal.php';</script>";
    exit;
  } else {
    echo "<div class='alert alert-danger'>❌ Gagal menambahkan judul soal.</div>";
  }
}

// === UPDATE JUDUL SOAL ===
if (isset($_POST['update'])) {
  $id = (int)$_POST['id'];
  $judul_soal = mysqli_real_escape_string($conn, trim($_POST['judul_soal']));
  $durasi = (int)$_POST['durasi'];
  $tanggal_buat = mysqli_real_escape_string($conn, $_POST['tanggal_buat']);
  $tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
  $tanggal_selesai = mysqli_real_escape_string($conn, $_POST['tanggal_selesai']);

  $update = mysqli_query($conn, "UPDATE judul_soal 
                                 SET judul_soal='$judul_soal', durasi='$durasi', tanggal_buat='$tanggal_buat', 
                                     tanggal_mulai='$tanggal_mulai', tanggal_selesai='$tanggal_selesai' 
                                 WHERE id='$id'");
  if ($update) {
    $_SESSION['flash_message'] = "✏️ Judul soal berhasil diperbarui.";
    echo "<script>location.href='judul_soal.php';</script>";
    exit;
  } else {
    echo "<div class='alert alert-danger'>❌ Gagal memperbarui judul soal.</div>";
  }
}

// === HAPUS JUDUL SOAL ===
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM judul_soal WHERE id = $id");
  $_SESSION['flash_message'] = "🗑️ Judul soal berhasil dihapus.";
  echo "<script>location.href='judul_soal.php';</script>";
  exit;
}

// === PENCARIAN ===
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Judul Soal</title>
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
              <h4><i class="fas fa-book"></i> Data Judul Soal</h4>
              <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus-circle"></i> Tambah Judul Soal
              </button>
            </div>

            <div class="card-body">
              <form method="GET" class="form-inline mb-3">
                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2" placeholder="🔍 Cari judul soal...">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
              </form>

              <?php
              if (isset($_SESSION['flash_message'])) {
                echo "<div id='notif-toast' class='alert alert-info text-center'>{$_SESSION['flash_message']}</div>";
                unset($_SESSION['flash_message']);
              }

              $limit = 10;
              $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
              if ($page < 1) $page = 1;
              $offset = ($page - 1) * $limit;

              $where = "";
              if (!empty($keyword)) {
                $keywordEscaped = mysqli_real_escape_string($conn, $keyword);
                $where = "WHERE judul_soal LIKE '%$keywordEscaped%'";
              }

              $totalQuery = "SELECT COUNT(*) as total FROM judul_soal $where";
              $totalResult = mysqli_query($conn, $totalQuery);
              $totalRows = mysqli_fetch_assoc($totalResult)['total'];
              $totalPages = ceil($totalRows / $limit);

              $query = "SELECT * FROM judul_soal $where ORDER BY tanggal_buat DESC LIMIT $offset, $limit";
              $result = mysqli_query($conn, $query);
              $no = $offset + 1;
              ?>

              <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>Judul Soal</th>
                      <th>Durasi (Menit)</th>
                      <th>Tanggal Buat</th>
                      <th>Tanggal Mulai</th>
                      <th>Tanggal Selesai</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                      <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                          <td class="text-center"><?= $no++; ?></td>
                          <td><?= htmlspecialchars($row['judul_soal']); ?></td>
                          <td class="text-center"><?= htmlspecialchars($row['durasi']); ?></td>
                          <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal_buat'])); ?></td>
                          <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['tanggal_mulai'])); ?></td>
                          <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['tanggal_selesai'])); ?></td>
                          <td class="text-center">
                            <button class="btn btn-warning btn-sm btn-edit" 
                              data-id="<?= $row['id']; ?>" 
                              data-judul="<?= htmlspecialchars($row['judul_soal']); ?>"
                              data-durasi="<?= $row['durasi']; ?>"
                              data-tbuat="<?= $row['tanggal_buat']; ?>"
                              data-tmulai="<?= $row['tanggal_mulai']; ?>"
                              data-tselesai="<?= $row['tanggal_selesai']; ?>">
                              <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus judul soal ini?')">
                              <i class="fas fa-trash-alt"></i> Hapus
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="7" class="text-center">Tidak ada data judul soal.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($totalPages > 1): ?>
                <nav>
                  <ul class="pagination justify-content-center mt-3">
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=1&keyword=<?= urlencode($keyword) ?>">⏮️</a>
                    </li>
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=<?= ($page-1) ?>&keyword=<?= urlencode($keyword) ?>">⬅️</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): ?>
                      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=<?= ($page+1) ?>&keyword=<?= urlencode($keyword) ?>">➡️</a>
                    </li>
                    <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                      <a class="page-link" href="?page=<?= $totalPages ?>&keyword=<?= urlencode($keyword) ?>">⏭️</a>
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

<!-- === MODAL TAMBAH === -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Judul Soal</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Judul Soal</label>
            <input type="text" name="judul_soal" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Durasi (Menit)</label>
            <input type="number" name="durasi" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tanggal Buat</label>
            <input type="date" name="tanggal_buat" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tanggal Mulai Ujian</label>
            <input type="datetime-local" name="tanggal_mulai" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tanggal Selesai Ujian</label>
            <input type="datetime-local" name="tanggal_selesai" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- === MODAL EDIT === -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Judul Soal</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="form-group">
            <label>Judul Soal</label>
            <input type="text" name="judul_soal" id="edit_judul_soal" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Durasi (Menit)</label>
            <input type="number" name="durasi" id="edit_durasi" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tanggal Buat</label>
            <input type="date" name="tanggal_buat" id="edit_tbuat" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tanggal Mulai Ujian</label>
            <input type="datetime-local" name="tanggal_mulai" id="edit_tmulai" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tanggal Selesai Ujian</label>
            <input type="datetime-local" name="tanggal_selesai" id="edit_tselesai" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
$(document).ready(function(){
  // Notifikasi
  var toast = $('#notif-toast');
  if (toast.length) toast.fadeIn(300).delay(2500).fadeOut(500);

  // === Tampilkan data ke modal edit ===
  $('.btn-edit').on('click', function(){
    $('#edit_id').val($(this).data('id'));
    $('#edit_judul_soal').val($(this).data('judul'));
    $('#edit_durasi').val($(this).data('durasi'));
    $('#edit_tbuat').val($(this).data('tbuat'));
    $('#edit_tmulai').val($(this).data('tmulai').replace(' ', 'T'));
    $('#edit_tselesai').val($(this).data('tselesai').replace(' ', 'T'));
    $('#modalEdit').modal('show');
  });
});
</script>
</body>
</html>
