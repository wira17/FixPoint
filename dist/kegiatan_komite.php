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

// === TAMBAH KEGIATAN KOMITE ===
if (isset($_POST['simpan'])) {
  $nama_kegiatan = mysqli_real_escape_string($conn, trim($_POST['nama_kegiatan']));
  $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
  $file_path = '';

  // Upload dokumen (opsional)
  if (!empty($_FILES['dokumen']['name'])) {
    $target_dir = "uploads/kegiatan_komite/";
    if (!is_dir($target_dir)) {
      mkdir($target_dir, 0777, true);
    }
    $file_name = time() . "_" . basename($_FILES["dokumen"]["name"]);
    $target_file = $target_dir . $file_name;
    if (move_uploaded_file($_FILES["dokumen"]["tmp_name"], $target_file)) {
      $file_path = $target_file;
    }
  }

  // Simpan ke tabel kegiatan_komite
  $insert = mysqli_query($conn, "INSERT INTO kegiatan_komite (user_id, nik, nama, tanggal, nama_kegiatan, dokumen) 
                                 VALUES ('$user_id', '$nik_user', '$nama_user', '$tanggal', '$nama_kegiatan', '$file_path')");

  if ($insert) {
    // === Tambahkan juga ke tabel catatan_kerja ===
    $judul = mysqli_real_escape_string($conn, "Kegiatan Komite: " . $nama_kegiatan);
    $isi = mysqli_real_escape_string($conn, "Kegiatan komite yang dilaksanakan oleh $nama_user (NIK: $nik_user) pada tanggal " . date('d-m-Y', strtotime($tanggal)) . ".");
    $tanggalCatatan = date('Y-m-d H:i:s');

    mysqli_query($conn, "INSERT INTO catatan_kerja (user_id, judul, isi, tanggal) 
                         VALUES ('$user_id', '$judul', '$isi', '$tanggalCatatan')");

    $_SESSION['flash_message'] = "✅ Kegiatan komite berhasil ditambahkan dan tercatat di Catatan Kerja.";
    echo "<script>location.href='kegiatan_komite.php';</script>";
    exit;
  } else {
    echo "<div class='alert alert-danger'>❌ Gagal menambahkan kegiatan.</div>";
  }
}

// === HAPUS KEGIATAN ===
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM kegiatan_komite WHERE id = $id");
  $_SESSION['flash_message'] = "🗑️ Kegiatan berhasil dihapus.";
  echo "<script>location.href='kegiatan_komite.php';</script>";
  exit;
}

// === PENCARIAN ===
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Kegiatan Komite Keperawatan</title>

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
                <h4><i class="fas fa-list"></i> Data Kegiatan Komite</h4>
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
                  <i class="fas fa-plus-circle"></i> Tambah Kegiatan
                </button>
              </div>

              <div class="card-body">
                <form method="GET" class="form-inline mb-3">
                  <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2" placeholder="🔍 Cari kegiatan...">
                  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
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
                if (!empty($keyword)) {
                  $keywordEscaped = mysqli_real_escape_string($conn, $keyword);
                  $where = "WHERE nama_kegiatan LIKE '%$keywordEscaped%' OR nama LIKE '%$keywordEscaped%'";
                }

                $totalQuery = "SELECT COUNT(*) as total FROM kegiatan_komite $where";
                $totalResult = mysqli_query($conn, $totalQuery);
                $totalRows = mysqli_fetch_assoc($totalResult)['total'];
                $totalPages = ceil($totalRows / $limit);

                $query = "SELECT * FROM kegiatan_komite $where ORDER BY tanggal DESC LIMIT $offset, $limit";
                $result = mysqli_query($conn, $query);
                $no = $offset + 1;
                ?>

                <div class="table-responsive">
                  <table class="table table-bordered table-sm table-hover">
                    <thead class="thead-dark text-center">
                      <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Nama Kegiatan</th>
                        <th>Dokumen</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                          <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td><?= htmlspecialchars($row['nik']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['nama_kegiatan']); ?></td>
                            <td class="text-center">
                              <?php if (!empty($row['dokumen'])): ?>
                                <a href="<?= htmlspecialchars($row['dokumen']); ?>" target="_blank" class="btn btn-info btn-sm">
                                  <i class="fas fa-eye"></i> Lihat
                                </a>
                              <?php else: ?>
                                <span class="text-muted">Tidak ada</span>
                              <?php endif; ?>
                            </td>
                            <td class="text-center">
                              <a href="?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus kegiatan ini?')">
                                <i class="fas fa-trash-alt"></i> Hapus
                              </a>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr><td colspan="7" class="text-center">Tidak ada data kegiatan.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination -->
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

  <!-- MODAL TAMBAH -->
  <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Kegiatan Komite</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label><i class="fas fa-id-card"></i> NIK</label>
                <input type="text" name="nik" value="<?= htmlspecialchars($nik_user) ?>" class="form-control" readonly>
              </div>
              <div class="form-group col-md-4">
                <label><i class="fas fa-user"></i> Nama</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($nama_user) ?>" class="form-control" readonly>
              </div>
              <div class="form-group col-md-4">
                <label><i class="fas fa-calendar-day"></i> Tanggal</label>
                <input type="date" name="tanggal" class="form-control" required>
              </div>
              <div class="form-group col-md-12">
                <label><i class="fas fa-pen"></i> Nama Kegiatan</label>
                <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Rapat Komite Bulanan" required>
              </div>
              <div class="form-group col-md-12">
                <label><i class="fas fa-file-upload"></i> Dokumen Pendukung (PDF / JPG / PNG)</label>
                <input type="file" name="dokumen" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
              </div>
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
