<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    echo "<script>alert('Anda belum login.'); window.location.href='login.php';</script>";
    exit;
}

$current_file = basename(__FILE__);

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Handle aksi edit
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $judul   = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi     = mysqli_real_escape_string($conn, $_POST['isi']);

    $sql = "UPDATE catatan_kerja SET judul=?, isi=? WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $judul, $isi, $edit_id, $user_id);
    $stmt->execute();
    echo "<script>alert('Catatan berhasil diperbarui.'); window.location.href='catatan_kerja.php';</script>";
    exit;
}

// Handle aksi hapus
if (isset($_GET['hapus_id'])) {
    $hapus_id = intval($_GET['hapus_id']);
    $sql = "DELETE FROM catatan_kerja WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $hapus_id, $user_id);
    $stmt->execute();
    echo "<script>alert('Catatan berhasil dihapus.'); window.location.href='catatan_kerja.php';</script>";
    exit;
}

// Ambil filter
$tgl_dari = $_GET['tgl_dari'] ?? '';
$tgl_sampai = $_GET['tgl_sampai'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($tgl_dari) && !empty($tgl_sampai)) {
    $where .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $params[] = $tgl_dari;
    $params[] = $tgl_sampai;
    $types .= "ss";
} elseif (!empty($tgl_dari)) {
    $where .= " AND DATE(tanggal) >= ?";
    $params[] = $tgl_dari;
    $types .= "s";
} elseif (!empty($tgl_sampai)) {
    $where .= " AND DATE(tanggal) <= ?";
    $params[] = $tgl_sampai;
    $types .= "s";
}

if (!empty($search)) {
    $where .= " AND (judul LIKE ? OR isi LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Hitung total data
$count_sql = "SELECT COUNT(*) as total FROM catatan_kerja $where";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data catatan kerja
$sql = "SELECT * FROM catatan_kerja $where ORDER BY tanggal DESC LIMIT ?, ?";
$params_page = $params;
$types_page = $types . "ii";
$params_page[] = $offset;
$params_page[] = $limit;

$stmt_data = $conn->prepare($sql);
$stmt_data->bind_param($types_page, ...$params_page);
$stmt_data->execute();
$data_catatan = $stmt_data->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Catatan Kerja Saya</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
 <style>
  .catatan-table { font-size: 13px; }
  .catatan-table th, .catatan-table td { padding: 8px 10px; vertical-align: middle; }

  /* Responsif penuh */
  @media (max-width: 992px) {
    .catatan-table th, .catatan-table td {
      white-space: normal;
    }
  }

  /* Kolom aksi biar tetap rapi */
  .catatan-table td .d-flex {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
  }

  .catatan-table td button,
  .catatan-table td a {
    min-width: 75px;
    font-size: 12px;
  }

  /* Agar isi teks panjang tidak melebar */
  .catatan-table td:nth-child(3) {
    max-width: 450px;
    word-wrap: break-word;
    white-space: pre-wrap;
  }

  .pagination { justify-content: center; }

  /* Scroll horizontal aktif bila tabel melebar di layar kecil */
  .table-responsive {
    overflow-x: auto;
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
            <div class="card-header">
              <h4>Catatan Kerja Saya</h4>
            </div>
            <div class="card-body">

              <!-- Filter Form -->
              <form method="GET" class="form-inline mb-3">
                <label class="mr-2">Tanggal Dari:</label>
                <input type="date" name="tgl_dari" value="<?= htmlspecialchars($tgl_dari) ?>" class="form-control mr-2">
                <label class="mr-2">Tanggal Sampai:</label>
                <input type="date" name="tgl_sampai" value="<?= htmlspecialchars($tgl_sampai) ?>" class="form-control mr-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul/isi catatan" class="form-control mr-2">
              <button type="submit" class="btn btn-primary">Filter</button>
<a href="catatan_kerja_saya.php" class="btn btn-secondary ml-2">Reset</a>
<a href="print_catatan.php?tgl_dari=<?= urlencode($tgl_dari) ?>&tgl_sampai=<?= urlencode($tgl_sampai) ?>&search=<?= urlencode($search) ?>" target="_blank" class="btn btn-success ml-2">
  <i class="fas fa-print"></i> Print
</a>

                <a href="catatan_kerja_saya.php" class="btn btn-secondary ml-2">Reset</a>
              </form>

             <!-- BAGIAN TABLE DALAM FILE YANG KAMU KIRIM -->
<div class="table-responsive">
  <table class="table table-bordered catatan-table">
    <thead class="thead-dark">
      <tr class="text-center">
        <th style="width:5%;">No</th>
        <th style="width:20%;">Judul</th>
        <th style="width:45%;">Isi</th>
        <th style="width:15%;">Tanggal</th>
        <th style="width:15%;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($data_catatan && $data_catatan->num_rows > 0): ?>
        <?php $no = $offset + 1; while ($catatan = $data_catatan->fetch_assoc()) : ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($catatan['judul']) ?></td>
            <td style="white-space: pre-wrap; word-break: break-word;"><?= nl2br(htmlspecialchars($catatan['isi'])) ?></td>
            <td class="text-center"><?= date('d-m-Y H:i', strtotime($catatan['tanggal'])) ?></td>
            <td class="text-center">
              <div class="d-flex justify-content-center flex-wrap gap-1">
                <button class="btn btn-sm btn-warning m-1"
                        data-toggle="modal"
                        data-target="#editModal"
                        data-id="<?= $catatan['id'] ?>"
                        data-judul="<?= htmlspecialchars($catatan['judul'], ENT_QUOTES) ?>"
                        data-isi="<?= htmlspecialchars($catatan['isi'], ENT_QUOTES) ?>">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <a href="?hapus_id=<?= $catatan['id'] ?>"
                   onclick="return confirm('Yakin ingin menghapus catatan ini?')"
                   class="btn btn-sm btn-danger m-1">
                  <i class="fas fa-trash"></i> Hapus
                </a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" class="text-center">Tidak ada catatan kerja.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>


              <!-- Pagination -->
              <nav>
                <ul class="pagination">
                  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                      <a class="page-link" href="?page=<?= $i ?>&tgl_dari=<?= urlencode($tgl_dari) ?>&tgl_sampai=<?= urlencode($tgl_sampai) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>

            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Edit Catatan Kerja</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="form-group">
            <label>Judul</label>
            <input type="text" name="judul" id="edit_judul" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Isi Catatan</label>
            <textarea name="isi" id="edit_isi" class="form-control" rows="5" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script>
  $('#editModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var id = button.data('id');
      var judul = button.data('judul');
      var isi = button.data('isi');

      var modal = $(this);
      modal.find('#edit_id').val(id);
      modal.find('#edit_judul').val(judul);
      modal.find('#edit_isi').val(isi);
  });
</script>
</body>
</html>
