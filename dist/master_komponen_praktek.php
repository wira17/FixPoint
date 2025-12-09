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

// === TAMBAH MASTER KOMPOSISI PRAKTEK ===
if (isset($_POST['simpan'])) {
    $nama_set = mysqli_real_escape_string($conn, trim($_POST['nama_set']));
    $check = mysqli_query($conn, "SELECT * FROM master_komponen_praktek WHERE nama_set='$nama_set'");
    if (mysqli_num_rows($check) > 0) {
        echo "<div class='alert alert-warning'>Set komponen praktek ini sudah ada.</div>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO master_komponen_praktek (nama_set) VALUES ('$nama_set')");
        if ($insert) {
            $_SESSION['flash_message'] = "Set komponen praktek berhasil ditambahkan.";
            echo "<script>location.href='master_komponen_praktek.php';</script>";
            exit;
        } else {
            echo "<div class='alert alert-danger'>Gagal menambahkan set komponen praktek.</div>";
        }
    }
}

// === HAPUS MASTER KOMPOSISI PRAKTEK ===
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM master_komponen_praktek WHERE id=$id");
    mysqli_query($conn, "DELETE FROM komponen_praktek_detail WHERE master_id=$id");
    $_SESSION['flash_message'] = "Set komponen praktek berhasil dihapus.";
    echo "<script>location.href='master_komponen_praktek.php';</script>";
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
<title>Master Komponen Praktek</title>

<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">

<style>
#notif-toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
    display: none;
    min-width: 300px;
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
              <h4><i class="fas fa-list"></i> Master Komponen Praktek</h4>
              <form method="GET" class="form-inline">
                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2" placeholder="Cari nama set...">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
              </form>
            </div>

            <div class="card-body">

              <!-- Form tambah set komponen -->
              <form method="POST" class="form-inline mb-3">
                <div class="form-group mr-2" style="flex: 1;">
                  <input type="text" name="nama_set" class="form-control w-100" placeholder="Nama set komponen praktek" required>
                </div>
                <button type="submit" name="simpan" class="btn btn-success btn-sm">
                  <i class="fas fa-plus"></i> Tambah
                </button>
              </form>

              <?php
              if (isset($_SESSION['flash_message'])) {
                  echo "<div id='notif-toast' class='alert alert-info text-center'>{$_SESSION['flash_message']}</div>";
                  unset($_SESSION['flash_message']);
              }

              $where = "";
              if (!empty($keyword)) {
                  $keywordEscaped = mysqli_real_escape_string($conn, $keyword);
                  $where = "WHERE nama_set LIKE '%$keywordEscaped%'";
              }

              $query = "SELECT * FROM master_komponen_praktek $where ORDER BY id DESC";
              $result = mysqli_query($conn, $query);
              ?>

              <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="thead-dark">
                    <tr class="text-center">
                      <th>No</th>
                      <th>Nama Set Komponen</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                  if (mysqli_num_rows($result) > 0):
                      $no = 1;
                      while ($row = mysqli_fetch_assoc($result)):
                  ?>
                    <tr>
                      <td class="text-center"><?= $no++; ?></td>
                      <td><?= htmlspecialchars($row['nama_set']); ?></td>
                      <td class="text-center">
                        <a href="komponen_detail.php?master_id=<?= $row['id']; ?>" class="btn btn-info btn-sm">
                          <i class="fas fa-edit"></i> Detail
                        </a>
                        <a href="master_komponen_praktek.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus set komponen praktek ini?')">
                          <i class="fas fa-trash"></i> Hapus
                        </a>
                      </td>
                    </tr>
                  <?php
                      endwhile;
                  else:
                  ?>
                    <tr><td colspan="3" class="text-center">Tidak ada data ditemukan.</td></tr>
                  <?php endif; ?>
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
        toast.fadeIn(300).delay(2000).fadeOut(500);
    }
});
</script>
</body>
</html>
