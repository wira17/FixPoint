<?php
session_start();
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__); 

// === Cek akses menu ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query) or die("Error cek akses: " . mysqli_error($conn));
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// === Proses ACC / Tolak ===
if (isset($_GET['aksi'], $_GET['id'])) {
  $id   = intval($_GET['id']);
  $aksi = $_GET['aksi'];
  $status = null;

  if ($aksi === 'acc') {
    $status = "Disetujui Atasan";
  } elseif ($aksi === 'tolak') {
    $status = "Ditolak Atasan";
  }

  if ($status) {
    $sql = "UPDATE pengajuan_cuti SET status='" . mysqli_real_escape_string($conn, $status) . "' WHERE id='$id'";
    $update = mysqli_query($conn, $sql);

    if (!$update) {
      $_SESSION['flash_message'] = "❌ Gagal update status: " . mysqli_error($conn);
    } else {
      $_SESSION['flash_message'] = "✅ Pengajuan cuti berhasil diperbarui menjadi <b>$status</b>.";
    }

    header("Location: data_cuti.php");
    exit;
  }
}

// === Ambil data pengajuan cuti (gunakan GROUP_CONCAT untuk daftar tanggal) ===
$sqlPengajuan = "
  SELECT p.*, u.nama AS nama_karyawan, mc.nama_cuti, d.nama AS nama_delegasi,
         GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti
  FROM pengajuan_cuti p
  JOIN users u ON p.karyawan_id = u.id
  JOIN master_cuti mc ON p.cuti_id = mc.id
  LEFT JOIN users d ON p.delegasi_id = d.id
  LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
  GROUP BY p.id
  ORDER BY p.id DESC
";
$dataPengajuan = mysqli_query($conn, $sqlPengajuan) or die("Error ambil data: " . mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>f.i.x.p.o.i.n.t</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .cuti-table { font-size: 13px; white-space: nowrap; }
    .cuti-table th, .cuti-table td { padding: 6px 10px; vertical-align: middle; }
    .flash-center {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
      z-index: 1050; min-width: 320px; max-width: 90%; text-align: center;
      padding: 15px; border-radius: 8px; font-weight: 500;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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

          <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-info flash-center" id="flashMsg">
              <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4 class="mb-0">Persetujuan Cuti</h4>
            </div>

            <div class="card-body">
              <!-- Tab menu -->
              <ul class="nav nav-tabs" id="cutiTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="pengajuan-tab" data-toggle="tab" href="#pengajuan" role="tab">Data Pengajuan</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="riwayat-tab" data-toggle="tab" href="#riwayat" role="tab">Riwayat Persetujuan</a>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-3">
                <!-- Data Pengajuan -->
                <div class="tab-pane fade show active" id="pengajuan" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered cuti-table">
                      <thead class="thead-dark">
                        <tr>
                          <th>No</th>
                          <th>Karyawan</th>
                          <th>Jenis Cuti</th>
                          <th>Tanggal</th>
                          <th>Delegasi</th>
                          <th>Alasan</th>
                          <th>Status</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; while($row = mysqli_fetch_assoc($dataPengajuan)): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_cuti']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal_cuti'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_delegasi'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['alasan']) ?></td>
                            <td>
                              <?php if ($row['status'] == "Menunggu Atasan"): ?>
                                <span class="badge bg-warning text-dark"><?= $row['status'] ?></span>
                              <?php elseif ($row['status'] == "Disetujui Atasan"): ?>
                                <span class="badge bg-success"><?= $row['status'] ?></span>
                              <?php elseif ($row['status'] == "Ditolak Atasan"): ?>
                                <span class="badge bg-danger"><?= $row['status'] ?></span>
                              <?php else: ?>
                                <span class="badge bg-secondary"><?= $row['status'] ?></span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <!-- Tombol Lihat -->
                              <button class="btn btn-sm btn-info lihatDetail" 
                                      data-id="<?= $row['id'] ?>">
                                <i class="fas fa-eye"></i>
                              </button>
                              <?php if ($row['status'] == "Menunggu Atasan"): ?>
                                <a href="data_cuti.php?aksi=acc&id=<?= $row['id'] ?>" 
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Yakin ACC cuti ini?')"><i class="fas fa-check"></i></a>
                                <a href="data_cuti.php?aksi=tolak&id=<?= $row['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Yakin Tolak cuti ini?')"><i class="fas fa-times"></i></a>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Riwayat -->
                <div class="tab-pane fade" id="riwayat" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-bordered cuti-table">
                      <thead class="thead-light">
                        <tr>
                          <th>No</th>
                          <th>Karyawan</th>
                          <th>Jenis Cuti</th>
                          <th>Tanggal</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        mysqli_data_seek($dataPengajuan, 0); // reset pointer result
                        $no=1;
                        while($row = mysqli_fetch_assoc($dataPengajuan)):
                          if ($row['status'] != "Menunggu Atasan"): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_cuti']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal_cuti'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                          </tr>
                        <?php endif; endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Detail Pengajuan Cuti</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="detailContent">
        <p class="text-center">Memuat data...</p>
      </div>
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
  $(document).ready(function() {
    setTimeout(function() {
      $("#flashMsg").fadeOut("slow");
    }, 3500);

    // Klik lihat detail
    $(".lihatDetail").on("click", function(){
      var id = $(this).data("id");
      $("#detailContent").html("<p class='text-center'>⏳ Memuat data...</p>");
      $("#modalDetail").modal("show");

      $.get("detail_cuti.php", {id:id}, function(data){
        $("#detailContent").html(data);
      }).fail(function(){
        $("#detailContent").html("<p class='text-danger text-center'>Gagal memuat data.</p>");
      });
    });
  });
</script>

</body>
</html>
