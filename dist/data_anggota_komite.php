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

// === PENCARIAN ===
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// === TAMBAH ANGGOTA KOMITE ===
if (isset($_POST['simpan'])) {
  $user_id_input = mysqli_real_escape_string($conn, $_POST['user_id']);
  $jabatan_komite = mysqli_real_escape_string($conn, trim($_POST['jabatan_komite']));

  $check = mysqli_query($conn, "SELECT * FROM anggota_komite WHERE user_id = '$user_id_input'");
  if (mysqli_num_rows($check) > 0) {
    echo "<div class='alert alert-warning'>User ini sudah menjadi anggota komite.</div>";
  } else {
    $insert = mysqli_query($conn, "INSERT INTO anggota_komite (user_id, jabatan_komite) VALUES ('$user_id_input', '$jabatan_komite')");
    if ($insert) {
      $_SESSION['flash_message'] = "Anggota komite berhasil ditambahkan.";
      echo "<script>location.href='data_anggota_komite.php';</script>";
      exit;
    } else {
      echo "<div class='alert alert-danger'>Gagal menambahkan anggota komite.</div>";
    }
  }
}

// === HAPUS ANGGOTA KOMITE ===
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  mysqli_query($conn, "DELETE FROM anggota_komite WHERE id = $id");
  $_SESSION['flash_message'] = "Anggota komite berhasil dihapus.";
  echo "<script>location.href='data_anggota_komite.php';</script>";
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>KOMITE KEPERAWATAN</title>

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
                <h4><i class="fas fa-users"></i> Data Anggota Komite Keperawatan</h4>
                <form method="GET" class="form-inline">
                  <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2" placeholder="Cari nama...">
                  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
                  <a href="print_anggota_komite.php?keyword=<?= urlencode($keyword) ?>" target="_blank" class="btn btn-success btn-sm">
  <i class="fas fa-print"></i> Cetak
</a>

                </form>
              </div>

              <div class="card-body">

                <!-- Form tambah anggota -->
                <form method="POST" class="form-inline mb-3">
                  <div class="form-group mr-2" style="flex: 1;">
                    <select name="user_id" class="form-control w-100" required>
                      <option value="">-- Pilih User --</option>
                      <?php
                      $users = mysqli_query($conn, "SELECT id, nama, unit_kerja FROM users ORDER BY nama ASC");
                      while ($u = mysqli_fetch_assoc($users)) {
                        echo "<option value='{$u['id']}'>{$u['nama']} ({$u['unit_kerja']})</option>";
                      }
                      ?>
                    </select>
                  </div>

                 <div class="form-group mr-2" style="flex: 1;">
  <select name="jabatan_komite" class="form-control w-100" required>
    <option value="">-- Pilih Jabatan Komite --</option>
    <?php
    $jabatans = mysqli_query($conn, "SELECT * FROM jabatan_komite ORDER BY nama_jabatan ASC");
    while ($j = mysqli_fetch_assoc($jabatans)) {
      echo "<option value='{$j['nama_jabatan']}'>{$j['nama_jabatan']}</option>";
    }
    ?>
  </select>
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

                // === Pagination ===
                $limit = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                if ($page < 1) $page = 1;
                $offset = ($page - 1) * $limit;

                $where = "";
                if (!empty($keyword)) {
                  $keywordEscaped = mysqli_real_escape_string($conn, $keyword);
                  $where = "WHERE u.nama LIKE '%$keywordEscaped%' OR u.unit_kerja LIKE '%$keywordEscaped%'";
                }

                $totalQuery = "SELECT COUNT(*) as total FROM anggota_komite ak 
                               JOIN users u ON ak.user_id = u.id $where";
                $totalResult = mysqli_query($conn, $totalQuery);
                $totalRows = mysqli_fetch_assoc($totalResult)['total'];
                $totalPages = ceil($totalRows / $limit);

                $query = "SELECT ak.id, u.nama, u.nik, u.jabatan, u.unit_kerja, u.email, ak.jabatan_komite
                          FROM anggota_komite ak 
                          JOIN users u ON ak.user_id = u.id 
                          $where 
                          ORDER BY u.nama ASC 
                          LIMIT $offset, $limit";
                $result = mysqli_query($conn, $query);
                $no = $offset + 1;
                ?>

                <div class="table-responsive">
                  <table class="table table-bordered table-sm table-hover">
                    <thead class="thead-dark">
                      <tr class="text-center">
                        <th>No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Unit Kerja</th>
                        <th>Jabatan Komite</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                          <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nik']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['jabatan']); ?></td>
                            <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
                            <td><?= htmlspecialchars($row['jabatan_komite']); ?></td>
                            <td class="text-center">
                              <a href="data_anggota_komite.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus anggota komite ini?')">
                                <i class="fas fa-trash"></i> Hapus
                              </a>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr><td colspan="8" class="text-center">Tidak ada data ditemukan.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <?php if ($totalPages > 1): ?>
                  <nav>
                    <ul class="pagination justify-content-center mt-3">
                      <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=1&keyword=<?= urlencode($keyword) ?>">First</a>
                      </li>
                      <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= ($page-1) ?>&keyword=<?= urlencode($keyword) ?>">Prev</a>
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
                        <a class="page-link" href="?page=<?= ($page+1) ?>&keyword=<?= urlencode($keyword) ?>">Next</a>
                      </li>
                      <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $totalPages ?>&keyword=<?= urlencode($keyword) ?>">Last</a>
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
