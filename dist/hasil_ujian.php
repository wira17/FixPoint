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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <title>Hasil Ujian Tertulis</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <style>
    .table td, .table th { vertical-align: middle; }
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
          <h1><i class="fas fa-poll"></i> Hasil Ujian Tertulis</h1>
        </div>

        <div class="section-body">

<?php
// === MODE 1: DAFTAR UJIAN ===
if (!isset($_GET['id'])) {
    $qJudul = mysqli_query($conn, "SELECT * FROM judul_soal ORDER BY tanggal_buat DESC");
?>
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h4><i class="fas fa-list"></i> Daftar Ujian</h4>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center">
          <thead class="thead-dark">
            <tr>
              <th>No</th>
              <th>Judul Soal</th>
              <th>Durasi</th>
              <th>Tanggal Ujian</th>
              <th>Peserta</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($qJudul)):
                $jumlahPeserta = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT user_id FROM jawaban_ujian WHERE judul_soal_id='{$row['id']}'"));
            ?>
            <tr>
              <td><?= $no++; ?></td>
              <td class="text-left"><?= htmlspecialchars($row['judul_soal']); ?></td>
              <td><?= $row['durasi']; ?> menit</td>
              <td><?= date('d-m-Y', strtotime($row['tanggal_mulai'])); ?></td>
              <td><?= $jumlahPeserta; ?> Peserta</td>
              <td>
                <a href="hasil_ujian.php?id=<?= $row['id']; ?>" class="btn btn-info btn-sm">
                  <i class="fas fa-eye"></i> Lihat Peserta
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php
}

// === MODE 2: LIHAT PESERTA ===
else {
    $judul_soal_id = (int)$_GET['id'];
    $qJudul = mysqli_query($conn, "SELECT * FROM judul_soal WHERE id='$judul_soal_id'");
    if (mysqli_num_rows($qJudul) == 0) {
        echo "<div class='alert alert-danger'>Judul soal tidak ditemukan.</div>";
        exit;
    }
    $judul = mysqli_fetch_assoc($qJudul);

    $qPeserta = mysqli_query($conn, "
        SELECT h.user_id AS id, 
               COALESCE(u.nama, 'Tidak Diketahui') AS nama,
               COALESCE(u.email, '-') AS email,
               h.nilai,
               h.tanggal_selesai AS tanggal_ujian
        FROM hasil_ujian h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.judul_soal_id = '$judul_soal_id'
        ORDER BY tanggal_ujian DESC
    ");
?>
  <div class="card">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
      <h4><i class="fas fa-file-alt"></i> Hasil Ujian: <?= htmlspecialchars($judul['judul_soal']); ?></h4>
      <a href="hasil_ujian.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center">
          <thead class="thead-dark">
            <tr>
              <th>No</th>
              <th>Nama Peserta</th>
              <th>Email</th>
              <th>Tanggal Ujian</th>
              <th>Nilai (%)</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; while ($p = mysqli_fetch_assoc($qPeserta)): ?>
              <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($p['nama']); ?></td>
                <td><?= htmlspecialchars($p['email']); ?></td>
                <td><?= date('d-m-Y H:i', strtotime($p['tanggal_ujian'])); ?></td>
                <td><?= $p['nilai']; ?></td>
                <td>
                  <a href="cetak_hasil_ujian.php?user_id=<?= $p['id']; ?>&judul_soal_id=<?= $judul_soal_id; ?>" 
                     target="_blank" 
                     class="btn btn-danger btn-sm" 
                     title="Cetak Hasil Ujian">
                     <i class="fas fa-file-pdf"></i> Cetak PDF
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php } ?>

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
</body>
</html>
