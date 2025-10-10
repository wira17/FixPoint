<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];

$current_file = basename(__FILE__); 

// Cek akses
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' 
          AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Ambil semua menu
$menu_result = mysqli_query($conn, "SELECT id, nama_menu FROM menu ORDER BY nama_menu ASC");
$menus = [];
while ($row = mysqli_fetch_assoc($menu_result)) {
  $menus[] = $row;
}

// Handle submit form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_id'])) {
  $form_user_id = (int) $_POST['user_id'];
  $menu_ids = $_POST['menu'] ?? [];

  mysqli_query($conn, "DELETE FROM akses_menu WHERE user_id = '$form_user_id'");

  foreach ($menu_ids as $menu_id) {
    $menu_id = (int) $menu_id;
    mysqli_query($conn, "INSERT INTO akses_menu (user_id, menu_id) VALUES ('$form_user_id', '$menu_id')");
  }

  $_SESSION['flash_message'] = "Hak akses berhasil diperbarui.";
  $page_redirect = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  header("Location: hak_akses.php?page=$page_redirect");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengaturan Hak Akses Menu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .modal-backdrop { z-index: 1040 !important; }
    .modal { z-index: 1050 !important; }
    #notif-toast {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 9999;
      display: none;
      min-width: 300px;
    }

    /* Lebarkan modal */
.modal-dialog {
  max-width: 900px; /* atur sesuai kebutuhan */
}

/* Scrollable modal-body */
.modal-body {
  max-height: 500px; /* atur tinggi maksimal modal */
  overflow-y: auto; /* scroll vertikal jika konten lebih tinggi */
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

          <?php if (isset($_SESSION['flash_message'])) : ?>
            <div id="notif-toast" class="alert alert-success text-center shadow">
              <?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4>Pengaturan Hak Akses Menu</h4>
            </div>
            <div class="card-body">

              <!-- Form Pencarian -->
              <form method="get" class="form-inline mb-3">
                <input type="text" name="keyword" class="form-control mr-2" placeholder="Cari Nama / NIK" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
              </form>

              <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>NIK</th>
                      <th>Nama</th>
                      <th>Jabatan</th>
                      <th>Unit Kerja</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Pagination setup
                    $limit = 10;
                    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                    $start = ($page > 1) ? ($page * $limit) - $limit : 0;

                    // Keyword pencarian
                    $keyword = $_GET['keyword'] ?? '';
                    $where = "";
                    if (!empty($keyword)) {
                      $keyword_safe = mysqli_real_escape_string($conn, $keyword);
                      $where = "WHERE nama LIKE '%$keyword_safe%' OR nik LIKE '%$keyword_safe%'";
                    }

                    // Hitung total data
                    $total_users_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users $where");
                    $total_users_row = mysqli_fetch_assoc($total_users_result);
                    $total_users = $total_users_row['total'];
                    $total_pages = ceil($total_users / $limit);

                    // Ambil data users
                    $queryAllUsers = mysqli_query($conn, "SELECT id, nik, nama, jabatan, unit_kerja 
                                                          FROM users $where 
                                                          ORDER BY nama ASC 
                                                          LIMIT $start, $limit");

                    $no = $start + 1;
                    $modals = '';

                    while ($row = mysqli_fetch_assoc($queryAllUsers)) :
                      $row_user_id = $row['id'];

                      // Ambil akses menu user ini
                      $akses_result = mysqli_query($conn, "SELECT menu_id FROM akses_menu WHERE user_id = '$row_user_id'");
                      $akses = [];
                      while ($ar = mysqli_fetch_assoc($akses_result)) {
                        $akses[] = $ar['menu_id'];
                      }
                    ?>
                    <tr>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($row['nik']) ?></td>
                      <td><?= htmlspecialchars($row['nama']) ?></td>
                      <td><?= htmlspecialchars($row['jabatan']) ?></td>
                      <td><?= htmlspecialchars($row['unit_kerja']) ?></td>
                      <td class="text-center">
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalAkses<?= $row_user_id ?>">
                          <i class="fas fa-edit"></i> Akses
                        </button>
                      </td>
                    </tr>

                    <?php ob_start(); ?>
                    <!-- Modal Akses -->
                    <div class="modal fade" id="modalAkses<?= $row_user_id ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $row_user_id ?>" aria-hidden="true">
                      <div class="modal-dialog" role="document">
                        <form method="post" class="modal-content" action="hak_akses.php?page=<?= $page ?>&keyword=<?= urlencode($keyword) ?>">
                          <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="modalLabel<?= $row_user_id ?>">Hak Akses: <?= htmlspecialchars($row['nama']) ?></h5>
                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                          </div>
                          <div class="modal-body">
                            <input type="hidden" name="user_id" value="<?= $row_user_id ?>">

                            <!-- Input pencarian menu -->
                            <div class="mb-2">
                              <input type="text" class="form-control" placeholder="Cari menu..." onkeyup="filterMenu(this, <?= $row_user_id ?>)">
                            </div>

                            <!-- Tombol checklist semua -->
                            <div class="mb-2">
                              <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllCheckboxes(<?= $row_user_id ?>, true)">Checklist Semua</button>
                              <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAllCheckboxes(<?= $row_user_id ?>, false)">Uncheck Semua</button>
                            </div>

                            <!-- Daftar menu -->
                            <div id="menu-list-<?= $row_user_id ?>" style="display: flex; flex-wrap: wrap;">
                              <?php foreach ($menus as $menu) : ?>
                                <div class="form-check menu-item-<?= $row_user_id ?>" style="width: 33%; padding-right: 10px;">
                                  <input class="form-check-input" type="checkbox" name="menu[]" value="<?= $menu['id'] ?>" 
                                         id="menu_<?= $row_user_id ?>_<?= $menu['id'] ?>" 
                                         <?= in_array($menu['id'], $akses) ? 'checked' : '' ?>>
                                  <label class="form-check-label" for="menu_<?= $row_user_id ?>_<?= $menu['id'] ?>">
                                    <?= htmlspecialchars($menu['nama_menu']) ?>
                                  </label>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                          </div>
                        </form>
                      </div>
                    </div>
                    <?php
                    $modals .= ob_get_clean();
                    endwhile;
                    ?>
                  </tbody>
                </table>

                <!-- Pagination (pindah ke bawah tabel) -->
                <nav aria-label="Page navigation">
                  <ul class="pagination justify-content-center mt-3">
                    <?php if ($page > 1): ?>
                      <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>">Sebelumnya</a>
                      </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                      <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>">Berikutnya</a>
                      </li>
                    <?php endif; ?>
                  </ul>
                </nav>

              </div>
            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

<?= $modals ?>

<!-- JS scripts -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script>
  function toggleAllCheckboxes(userId, checked) {
    document.querySelectorAll('.menu-item-' + userId + ' input[type="checkbox"]').forEach(cb => {
      cb.checked = checked;
    });
  }

  function filterMenu(input, userId) {
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll('.menu-item-' + userId);
    items.forEach(item => {
      const label = item.querySelector('label').innerText.toLowerCase();
      item.style.display = label.includes(filter) ? '' : 'none';
    });
  }

  $(document).ready(function () {
    $('#notif-toast').fadeIn(300).delay(2000).fadeOut(500);
  });
</script>
</body>
</html>
