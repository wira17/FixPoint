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

// Pencarian
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport" />
  <title>Jam Kerja</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />

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
                <h4>Daftar Jam Kerja</h4>
                <form method="GET" class="form-inline">
                  <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2" placeholder="Cari nama jam kerja" />
                  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
                </form>
              </div>

              <div class="card-body">

                <!-- Form tambah jam kerja -->
                <form method="POST" class="form-inline mb-3">
                  <div class="form-group mr-2" style="flex: 1;">
                    <input type="text" name="nama_jam" class="form-control w-100" placeholder="Nama jam kerja" required />
                  </div>
                  <div class="form-group mr-2">
                    <input type="time" name="jam_mulai" class="form-control" required />
                  </div>
                  <div class="form-group mr-2">
                    <input type="time" name="jam_selesai" class="form-control" required />
                  </div>
                  <button type="submit" name="simpan" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Tambah
                  </button>
                </form>

                <?php
                // Simpan jam kerja baru
                if (isset($_POST['simpan'])) {
                  $nama_jam   = mysqli_real_escape_string($conn, trim($_POST['nama_jam']));
                  $jam_mulai  = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
                  $jam_selesai= mysqli_real_escape_string($conn, $_POST['jam_selesai']);

                  $insert = mysqli_query($conn, "INSERT INTO jam_kerja (nama_jam, jam_mulai, jam_selesai) VALUES ('$nama_jam','$jam_mulai','$jam_selesai')");
                  if ($insert) {
                    $_SESSION['flash_message'] = "Jam kerja berhasil ditambahkan.";
                    echo "<script>location.href='jam_kerja.php';</script>";
                    exit;
                  } else {
                    echo "<div class='alert alert-danger'>Gagal menambahkan jam kerja.</div>";
                  }
                }

                // Notifikasi
                if (isset($_SESSION['flash_message'])) {
                  echo "<div id='notif-toast' class='alert alert-info text-center'>{$_SESSION['flash_message']}</div>";
                  unset($_SESSION['flash_message']);
                }

                // Pagination
                $limit = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $limit;

                $totalQuery = "SELECT COUNT(*) as total FROM jam_kerja";
                if (!empty($keyword)) {
                  $keywordEscaped = mysqli_real_escape_string($conn, $keyword);
                  $totalQuery .= " WHERE nama_jam LIKE '%$keywordEscaped%'";
                }
                $totalResult = mysqli_query($conn, $totalQuery);
                $totalRows = mysqli_fetch_assoc($totalResult)['total'];
                $totalPages = ceil($totalRows / $limit);

                $query = "SELECT * FROM jam_kerja";
                if (!empty($keyword)) {
                  $query .= " WHERE nama_jam LIKE '%$keywordEscaped%'";
                }
                $query .= " ORDER BY id ASC LIMIT $offset, $limit";
                $result = mysqli_query($conn, $query);
                $no = $offset + 1;
                ?>

                <div class="table-responsive">
                  <table class="table table-bordered table-sm table-hover">
                    <thead class="thead-dark">
                      <tr class="text-center">
                        <th>No</th>
                        <th>Nama Jam Kerja</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                          echo "<tr>";
                          echo "<td class='text-center'>{$no}</td>";
                          echo "<td>{$row['nama_jam']}</td>";
                          echo "<td class='text-center'>{$row['jam_mulai']}</td>";
                          echo "<td class='text-center'>{$row['jam_selesai']}</td>";
                          echo "<td class='text-center'>
                                  <a href='jam_kerja.php?hapus={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Hapus jam kerja ini?\")'>
                                    <i class='fas fa-trash'></i> Hapus
                                  </a>
                                </td>";
                          echo "</tr>";
                          $no++;
                        }
                      } else {
                        echo "<tr><td colspan='5' class='text-center'>Tidak ada data ditemukan.</td></tr>";
                      }

                      // Hapus
                      if (isset($_GET['hapus'])) {
                        $id = (int)$_GET['hapus'];
                        mysqli_query($conn, "DELETE FROM jam_kerja WHERE id = $id");
                        $_SESSION['flash_message'] = "Jam kerja berhasil dihapus.";
                        echo "<script>location.href='jam_kerja.php';</script>";
                        exit;
                      }
                      ?>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                  <nav>
                    <ul class="pagination justify-content-center mt-3">
                      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                          <a class="page-link" href="?page=<?= $i; ?>&keyword=<?= urlencode($keyword); ?>"><?= $i; ?></a>
                        </li>
                      <?php endfor; ?>
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
