<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// Cek akses
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Tambah jabatan
if (isset($_POST['tambah'])) {
  $nama = mysqli_real_escape_string($conn, trim($_POST['nama_jabatan']));
  if ($nama != '') {
    mysqli_query($conn, "INSERT INTO jabatan_komite (nama_jabatan) VALUES ('$nama')");
    $_SESSION['flash_message'] = "Jabatan berhasil ditambahkan.";
    header("Location: jabatan_komite.php");
    exit;
  }
}

// Hapus jabatan
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM jabatan_komite WHERE id='$id'");
  $_SESSION['flash_message'] = "Jabatan berhasil dihapus.";
  header("Location: jabatan_komite.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Jabatan Komite</title>
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
              <h4><i class="fas fa-briefcase-medical"></i> Data Jabatan Komite Keperawatan</h4>
            </div>
            <div class="card-body">
              <form method="POST" class="form-inline mb-3">
                <input type="text" name="nama_jabatan" class="form-control mr-2" placeholder="Nama Jabatan Komite" required>
                <button type="submit" name="tambah" class="btn btn-success"><i class="fas fa-plus"></i> Tambah</button>
              </form>

              <?php
              if (isset($_SESSION['flash_message'])) {
                echo "<div class='alert alert-info'>{$_SESSION['flash_message']}</div>";
                unset($_SESSION['flash_message']);
              }

              $q = mysqli_query($conn, "SELECT * FROM jabatan_komite ORDER BY id ASC");
              ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                  <thead class="thead-dark">
                    <tr class="text-center">
                      <th>No</th>
                      <th>Nama Jabatan</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no = 1; while($r = mysqli_fetch_assoc($q)): ?>
                    <tr>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($r['nama_jabatan']) ?></td>
                      <td class="text-center">
                        <a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Yakin hapus jabatan ini?')" class="btn btn-danger btn-sm">
                          <i class="fas fa-trash"></i> Hapus
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
