<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Ambil filter dari form
$nama_filter = isset($_GET['nama']) ? trim($_GET['nama']) : '';
$dari = isset($_GET['dari']) ? $_GET['dari'] : '';
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "WHERE 1 ";
if(!empty($nama_filter)){
    $nama_esc = mysqli_real_escape_string($conn, $nama_filter);
    $where .= "AND u.nama LIKE '%$nama_esc%' ";
}
if(!empty($dari)){
    $where .= "AND a.tanggal >= '$dari' ";
}
if(!empty($sampai)){
    $where .= "AND a.tanggal <= '$sampai' ";
}

// Hitung total data
$countQuery = "SELECT COUNT(*) as total 
               FROM absensi a 
               LEFT JOIN users u ON a.user_id = u.id 
               $where";
$countResult = mysqli_query($conn, $countQuery);
$totalData = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalData / $limit);

// Ambil data absensi dengan filter dan pagination
$sql = "SELECT a.*, u.nama 
        FROM absensi a 
        LEFT JOIN users u ON a.user_id = u.id 
        $where
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset";
$dataResult = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Data Absensi</title>

<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">

<style>
.table-responsive { overflow-x: auto; }
.table td, .table th { white-space: nowrap; }
.img-thumb { width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 5px; }
#fotoModal .modal-dialog { max-width: 600px; }
</style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
      <section class="section">
        <div class="section-header">
          <h1>Data Absensi</h1>
        </div>
        <div class="section-body">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4>Rekap Data Absensi</h4>
              <form class="form-inline" method="GET">
                <input type="text" name="nama" placeholder="Cari Nama" class="form-control mr-2" value="<?= htmlspecialchars($nama_filter) ?>">
                <input type="date" name="dari" class="form-control mr-2" value="<?= $dari ?>">
                <input type="date" name="sampai" class="form-control mr-2" value="<?= $sampai ?>">
                <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-search"></i> Filter</button>
                <a href="cetak_absensi.php?nama=<?= urlencode($nama_filter) ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print"></i> Cetak PDF</a>
              </form>
            </div>

            <div class="card-body table-responsive">
              <table class="table table-bordered table-striped table-hover">
                <thead class="thead-dark text-center">
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Istirahat Masuk</th>
                    <th>Istirahat Keluar</th>
                    <th>Status</th>
                    <th>Foto</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = $offset + 1;
                  if(mysqli_num_rows($dataResult) > 0):
                      while($row = mysqli_fetch_assoc($dataResult)):
                  ?>
                  <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $row['nama'] ?? '-' ?></td>
                    <td><?= $row['tanggal'] ?></td>
                    <td><?= $row['jam_masuk'] ?? '-' ?></td>
                    <td><?= $row['jam_keluar'] ?? '-' ?></td>
                    <td><?= $row['istirahat_masuk'] ?? '-' ?></td>
                    <td><?= $row['istirahat_keluar'] ?? '-' ?></td>

                    
                    <td class="text-center"><?= ucfirst(str_replace('_',' ',$row['status'])) ?></td>
                    <td class="text-center">
                      <?php if($row['foto'] && file_exists('absen_foto/'.$row['foto'])): ?>
                        <img src="absen_foto/<?= $row['foto'] ?>" class="img-thumb" onclick="showFoto('absen_foto/<?= $row['foto'] ?>')">
                      <?php else: ?> - <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $row['latitude'] ?? '-' ?></td>
                    <td class="text-center"><?= $row['longitude'] ?? '-' ?></td>
                  </tr>
                  <?php
                      endwhile;
                  else:
                  ?>
                  <tr><td colspan="11" class="text-center">Tidak ada data absensi.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>

              <!-- Pagination -->
              <nav>
                <ul class="pagination justify-content-center mt-3">
                  <?php if($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?nama=<?= urlencode($nama_filter) ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&page=<?= $page-1 ?>">&laquo;</a></li>
                  <?php endif; ?>
                  <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <li class="page-item <?= $i==$page?'active':'' ?>">
                      <a class="page-link" href="?nama=<?= urlencode($nama_filter) ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                  <?php if($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?nama=<?= urlencode($nama_filter) ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&page=<?= $page+1 ?>">&raquo;</a></li>
                  <?php endif; ?>
                </ul>
              </nav>

            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Modal Foto -->
<div class="modal fade" id="fotoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-0">
        <img id="fotoModalImg" src="" style="width:100%; height:auto; border-radius:5px;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
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
function showFoto(src){
    $('#fotoModalImg').attr('src', src);
    $('#fotoModal').modal('show');
}
</script>
</body>
</html>
