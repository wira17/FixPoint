<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');


$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); // 

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}




$user_id = $_SESSION['user_id'];
$queryUser = mysqli_query($conn, "SELECT nik, nama, jabatan, unit_kerja FROM users WHERE id = '$user_id'");
$userData = mysqli_fetch_assoc($queryUser);

$berita_acara = [];
if (isset($_GET['tiket_id'])) {
  $tiket_id = intval($_GET['tiket_id']);
  $query = mysqli_query($conn, "SELECT t.*, u.nik, u.nama, u.jabatan, u.unit_kerja 
                                FROM tiket_it_hardware t 
                                JOIN users u ON t.user_id = u.id 
                                WHERE t.id = $tiket_id");
  if ($query && mysqli_num_rows($query) > 0) {
    $berita_acara = mysqli_fetch_assoc($query);
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Berita Acara IT Hardware &mdash; SICONIC</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
    <style>
    .table-responsive-custom {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .table-responsive-custom table {
      width: 100%;
      min-width: 1200px;
      white-space: nowrap;
    }

    .d-flex.gap-1 > form {
      margin-right: 5px;
    }

    .table thead th {
  background-color: #000 !important;
  color: #fff !important;
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
                <h4>Berita Acara IT Hardware</h4>
              </div>
              <div class="card-body">

                <ul class="nav nav-tabs" id="baTab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="buatba-tab" data-toggle="tab" href="#buatba" role="tab">Buat BA</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="databa-tab" data-toggle="tab" href="#databa" role="tab">Data BA</a>
                  </li>
                </ul>

                <div class="tab-content mt-4" id="baTabContent">

                  <!-- Form Buat Berita Acara -->
                  <div class="tab-pane fade show active" id="buatba" role="tabpanel">
                    <?php if (!empty($berita_acara)): ?>
                      <form method="POST" action="simpan_berita_acara.php">

                        <input type="hidden" name="tiket_id" value="<?= $berita_acara['id']; ?>">
                        <div class="row">
                          <div class="form-group col-md-4">
                            <label>Nomor Tiket</label>
                            <input type="text" class="form-control" name="nomor_tiket" value="<?= $berita_acara['nomor_tiket']; ?>" readonly>
                          </div>
                          <div class="form-group col-md-4">
                            <label>Tanggal</label>
                            <input type="text" class="form-control" value="<?= date('d-m-Y H:i', strtotime($berita_acara['tanggal_input'])); ?>" readonly>
                          </div>
                          <div class="form-group col-md-4">
                            <label>NIK</label>
                            <input type="text" class="form-control" value="<?= $berita_acara['nik']; ?>" readonly>
                          </div>
                          <div class="form-group col-md-4">
                            <label>Nama Pelapor</label>
                            <input type="text" class="form-control" value="<?= $berita_acara['nama']; ?>" readonly>
                          </div>
                          <div class="form-group col-md-4">
                            <label>Jabatan</label>
                            <input type="text" class="form-control" value="<?= $berita_acara['jabatan']; ?>" readonly>
                          </div>
                          <div class="form-group col-md-4">
                            <label>Unit Kerja</label>
                            <input type="text" class="form-control" value="<?= $berita_acara['unit_kerja']; ?>" readonly>
                          </div>
<div class="form-group col-md-6">
  <label>Nomor Berita Acara (otomatis)</label>
  <input type="text" class="form-control" value="Akan digenerate otomatis" readonly>
</div>


                          <div class="form-group col-md-6">
                            <label>Kendala</label>
                            <textarea class="form-control" rows="3" readonly><?= $berita_acara['kendala']; ?></textarea>
                          </div>

     

                          <div class="form-group col-md-12">
                            <label>Catatan Teknisi</label>
                            <textarea class="form-control" name="catatan_teknisi" rows="4" required><?= htmlspecialchars($berita_acara['catatan_it'] ?? '') ?></textarea>
                          </div>
                        </div>
<div class="d-flex">
  <button type="submit" name="simpan" class="btn btn-success me-2">Simpan</button>
  <a href="data_tiket_it_hardware.php" class="btn btn-secondary">Kembali</a>
</div>



                      </form>
                    <?php else: ?>
                      <div class="alert alert-info">Silakan pilih tiket dari daftar untuk membuat Berita Acara.</div>
                    <?php endif; ?>
                  </div>

                  <!-- Data BA -->
                  <div class="tab-pane fade" id="databa" role="tabpanel">
                    <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">

                      <table class="table table-striped table-bordered">
                      <thead>
  <tr>
    <th>No</th>
    <th>Nomor BA</th>
    <th>Nomor Tiket</th>
    <th>Tanggal</th>
    <th>Kategori</th>
    <th>Kendala</th>
    <th>Status</th>
    <th>Aksi</th> <!-- kolom baru -->
  </tr>
</thead>

                      <tbody>
<?php
$no = 1;
$queryBA = mysqli_query($conn, "SELECT ba.*, t.kategori, t.kendala 
                                FROM berita_acara ba
                                JOIN tiket_it_hardware t ON ba.tiket_id = t.id
                                ORDER BY ba.tanggal_ba DESC");

while ($row = mysqli_fetch_assoc($queryBA)) {
    echo "<tr>
            <td>{$no}</td>
            <td>{$row['nomor_ba']}</td>
            <td><a href='berita_acara.php?tiket_id={$row['tiket_id']}'>{$row['nomor_tiket']}</a></td>
            <td>" . date('d-m-Y H:i', strtotime($row['tanggal_ba'])) . "</td>
            <td>{$row['kategori']}</td>
            <td>{$row['kendala']}</td>
            <td><span class='badge badge-success'>Tersimpan</span></td>
            <td>
              <a href='cetak_berita_acara.php?tiket_id={$row['tiket_id']}' target='_blank' class='btn btn-sm btn-primary'>
                Cetak
              </a>
            </td>
          </tr>";
    $no++;
}
?>
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

   <script src="assets/modules/jquery.min.js"></script>
  <script src="assets/modules/popper.js"></script>
  <script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
  <script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
  <script src="assets/modules/moment.min.js"></script>
  <script src="assets/js/stisla.js"></script>
  <script src="assets/js/scripts.js"></script>
  <script src="assets/js/custom.js"></script>

  </body>
  </html>
