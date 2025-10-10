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

// === FILTER PENCARIAN ===
$keyword    = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$tgl_dari   = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : '';
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
  <title>Data Tiket Sarpras</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    /* Header tabel */
    .table thead th {
      background-color: #000;
      color: #fff;
      text-align: center;
      white-space: nowrap;
    }

    /* Sel tabel */
    .table td {
      white-space: nowrap;      /* Tetap satu baris */
      vertical-align: middle;
    }

    /* Bungkus tabel agar bisa discroll ke kanan */
    .table-responsive-custom {
      width: 100%;
      overflow-x: auto;
      overflow-y: hidden;
    }

    /* Scrollbar tampak halus */
    .table-responsive-custom::-webkit-scrollbar {
      height: 10px;
    }
    .table-responsive-custom::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }
    .table-responsive-custom::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    .badge {
      font-size: 12px;
      padding: 6px 10px;
      white-space: nowrap;
    }

    /* Modal agar tampil di depan */
    .modal-backdrop {
      z-index: 1040 !important;
    }
    .modal {
      z-index: 1050 !important;
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
            <div class="card-header d-flex justify-content-between">
              <h4><i class="fas fa-tools text-primary mr-2"></i>Data Tiket Sarpras</h4>
            </div>
            <div class="card-body">

              <!-- FILTER -->
              <form method="GET" class="form-inline mb-3">
                <div class="row w-100 align-items-end">
                  <div class="col-md-3">
                    <input type="text" name="keyword" class="form-control w-100" placeholder="Cari nama/kategori/kendala" value="<?= htmlspecialchars($keyword) ?>">
                  </div>
                  <div class="col-md-2">
                    <input type="date" name="tgl_dari" class="form-control w-100" value="<?= htmlspecialchars($tgl_dari) ?>">
                  </div>
                  <div class="col-md-2">
                    <input type="date" name="tgl_sampai" class="form-control w-100" value="<?= htmlspecialchars($tgl_sampai) ?>">
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                  </div>
                  <div class="col-md-2">
                    <a href="data_tiket_sarpras.php" class="btn btn-secondary w-100">Reset</a>
                  </div>
                </div>
              </form>

              <?php
              // === PAGINATION ===
              $limit = 10;
              $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
              $offset = ($page - 1) * $limit;

              $whereClauses = [];
              if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $whereClauses[] = "DATE(t.tanggal_input) BETWEEN '$tgl_dari' AND '$tgl_sampai'";
              }
              if (!empty($keyword)) {
                $keywordEsc = mysqli_real_escape_string($conn, $keyword);
                $whereClauses[] = "(u.nama LIKE '%$keywordEsc%' OR t.kategori LIKE '%$keywordEsc%' OR t.kendala LIKE '%$keywordEsc%')";
              }
              $where = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

              $totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM tiket_sarpras t JOIN users u ON t.user_id = u.id $where");
              $totalRows = mysqli_fetch_assoc($totalQuery)['total'];
              $totalPages = ceil($totalRows / $limit);

              $query = "SELECT t.*, u.nik, u.nama, u.jabatan, u.unit_kerja 
                        FROM tiket_sarpras t 
                        JOIN users u ON t.user_id = u.id 
                        $where 
                        ORDER BY t.tanggal_input DESC 
                        LIMIT $offset, $limit";
              $result = mysqli_query($conn, $query);
              $no = $offset + 1;

              function badgeStatus($status) {
                switch (strtolower($status)) {
                  case 'menunggu': return '<span class="badge badge-warning">Menunggu</span>';
                  case 'diproses': return '<span class="badge badge-info">Diproses</span>';
                  case 'selesai': return '<span class="badge badge-success">Selesai</span>';
                  case 'tidak bisa diperbaiki': return '<span class="badge badge-danger">Tidak Bisa Diperbaiki</span>';
                  case 'ditolak': return '<span class="badge badge-dark">Ditolak</span>';
                  default: return '<span class="badge badge-secondary">'.htmlspecialchars($status).'</span>';
                }
              }

              function badgeValidasi($v) {
                switch (strtolower($v)) {
                  case 'belum validasi': return '<span class="badge badge-secondary">Belum Validasi</span>';
                  case 'diterima': return '<span class="badge badge-success">Diterima</span>';
                  case 'ditolak': return '<span class="badge badge-danger">Ditolak</span>';
                  default: return '<span class="badge badge-light">'.htmlspecialchars($v).'</span>';
                }
              }

              $modals = "";
              ?>

              <!-- TABLE -->
              <div class="table-responsive-custom">
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Nomor Tiket</th>
                      <th>Tanggal Input</th>
                      <th>NIK</th>
                      <th>Nama</th>
                      <th>Jabatan</th>
                      <th>Unit Kerja</th>
                      <th>Kategori</th>
                      <th>Kendala</th>
                      <th>Teknisi</th>
                      <th>Status</th>
                      <th>Validasi</th>
                      <th>Catatan</th>
                      <th>Aksi</th>
                      <th>Cetak</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                      <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                          <td class="text-center"><?= $no++; ?></td>
                          <td><b><?= htmlspecialchars($row['nomor_tiket']); ?></b></td>
                          <td><?= !empty($row['tanggal_input']) ? date('d-m-Y H:i', strtotime($row['tanggal_input'])) : '-'; ?></td>
                          <td><?= htmlspecialchars($row['nik']); ?></td>
                          <td><?= htmlspecialchars($row['nama']); ?></td>
                          <td><?= htmlspecialchars($row['jabatan']); ?></td>
                          <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
                          <td><?= htmlspecialchars($row['kategori']); ?></td>
                          <td><?= nl2br(htmlspecialchars($row['kendala'])); ?></td>
                          <td><?= htmlspecialchars($row['teknisi_nama'] ?: '-'); ?></td>
                          <td><?= badgeStatus($row['status']); ?></td>
                          <td><?= badgeValidasi($row['status_validasi']); ?></td>
                          <td><?= nl2br(htmlspecialchars($row['catatan_it'] ?: '-')); ?></td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#ubahStatus<?= $row['id']; ?>">
                              <i class="fas fa-edit"></i>
                            </button>
                          </td>
                          <td class="text-center">
                            <a href="cetak_tiket_sarpras.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-sm btn-success">
                              <i class="fas fa-print"></i>
                            </a>
                          </td>
                        </tr>
                        <?php
                        // simpan modal
                        $idModal = (int)$row['id'];
                        $catatan_escaped = htmlspecialchars($row['catatan_it']);
                        $modals .= '
                        <div class="modal fade" id="ubahStatus'.$idModal.'" tabindex="-1" role="dialog" aria-labelledby="ubahStatusLabel'.$idModal.'" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                              <form action="ubah_status_sarpras.php" method="POST">
                                <div class="modal-header">
                                  <h5 class="modal-title">Ubah Status Tiket</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <div class="modal-body">
                                  <input type="hidden" name="tiket_id" value="'.$idModal.'">
                                  <div class="form-group">
                                    <label>Status Baru:</label>
                                    <select name="status" class="form-control" required>
                                      <option value="">-- Pilih --</option>
                                      <option value="Menunggu">Menunggu</option>
                                      <option value="Diproses">Diproses</option>
                                      <option value="Selesai">Selesai</option>
                                      <option value="Tidak Bisa Diperbaiki">Tidak Bisa Diperbaiki</option>
                                      <option value="Ditolak">Ditolak</option>
                                    </select>
                                  </div>
                                  <div class="form-group">
                                    <label>Catatan Teknisi:</label>
                                    <textarea name="catatan_it" class="form-control" rows="3" placeholder="Tambahkan catatan...">'.$catatan_escaped.'</textarea>
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                  <button type="submit" name="ubah_status" class="btn btn-primary btn-sm">Simpan</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>';
                        ?>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="15" class="text-center">Tidak ada data ditemukan.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- PAGINATION -->
              <?php if ($totalPages > 1): ?>
              <nav>
                <ul class="pagination justify-content-center mt-3">
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                      <a class="page-link" href="?page=<?= $i; ?>&keyword=<?= urlencode($keyword); ?>&tgl_dari=<?= htmlspecialchars($tgl_dari); ?>&tgl_sampai=<?= htmlspecialchars($tgl_sampai); ?>"><?= $i; ?></a>
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

<!-- MODALS -->
<?= $modals; ?>

<!-- SCRIPTS -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
