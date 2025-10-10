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

// === TAMBAH JENIS KREDENSIAL ===
if (isset($_POST['tambah'])) {
  $nama = mysqli_real_escape_string($conn, trim($_POST['nama_jenis']));
  if ($nama != '') {
    mysqli_query($conn, "INSERT INTO jenis_kredensial (nama_jenis) VALUES ('$nama')");
    $_SESSION['flash_message'] = "Jenis kredensial berhasil ditambahkan.";
    header("Location: jenis_kredensial.php");
    exit;
  }
}

// === HAPUS JENIS KREDENSIAL ===
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM jenis_kredensial WHERE id='$id'");
  $_SESSION['flash_message'] = "Jenis kredensial berhasil dihapus.";
  header("Location: jenis_kredensial.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Jenis Kredensial</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
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
            <div class="card-header">
              <h4><i class="fas fa-id-badge"></i> Data Jenis Kredensial</h4>
            </div>
            <div class="card-body">
              <form method="POST" class="form-inline mb-3">
                <input type="text" name="nama_jenis" class="form-control mr-2" placeholder="Nama Jenis Kredensial" required>
                <button type="submit" name="tambah" class="btn btn-success">
                  <i class="fas fa-plus"></i> Tambah
                </button>
              </form>

              <?php
              if (isset($_SESSION['flash_message'])) {
                echo "<div class='alert alert-info'>{$_SESSION['flash_message']}</div>";
                unset($_SESSION['flash_message']);
              }

              $q = mysqli_query($conn, "SELECT * FROM jenis_kredensial ORDER BY id ASC");
              ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                  <thead class="thead-dark">
                    <tr class="text-center">
                      <th width="5%">No</th>
                      <th>Nama Jenis Kredensial</th>
                      <th width="15%">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $no = 1; 
                    if (mysqli_num_rows($q) > 0) {
                      while($r = mysqli_fetch_assoc($q)): ?>
                        <tr>
                          <td class="text-center"><?= $no++ ?></td>
                          <td><?= htmlspecialchars($r['nama_jenis']) ?></td>
                          <td class="text-center">
                            <a href="?hapus=<?= $r['id'] ?>" 
                               onclick="return confirm('Yakin hapus jenis kredensial ini?')" 
                               class="btn btn-danger btn-sm">
                              <i class="fas fa-trash"></i> Hapus
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; 
                    } else {
                      echo "<tr><td colspan='3' class='text-center'>Belum ada data jenis kredensial.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <script src="assets/modules/jquery.min.js"></script>
    <script src="assets/modules/popper.js"></script>
    <script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
    <script src="assets/modules/moment.min.js"></script>
    <script src="assets/js/stisla.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/custom.js"></script>
  </div>
</div>
</body>
</html>
