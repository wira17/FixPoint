<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// === CEK AKSES MENU ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' 
          AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// === AMBIL DATA USER LOGIN ===
$q_user = mysqli_query($conn, "SELECT nama, unit_kerja FROM users WHERE id='$user_id'");
$data_user = mysqli_fetch_assoc($q_user);
$nama_user = $data_user['nama'] ?? 'Tidak Diketahui';
$unit_kerja_user = $data_user['unit_kerja'] ?? 'Tidak Diketahui';

// === SIMPAN DATA KPI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'simpan') {
    $kode = mysqli_real_escape_string($conn, $_POST['kode_indikator']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_indikator']);
    $target = mysqli_real_escape_string($conn, $_POST['target']);
    $realisasi = mysqli_real_escape_string($conn, $_POST['realisasi']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $bulan = mysqli_real_escape_string($conn, $_POST['periode_bulan']);
    $tahun = mysqli_real_escape_string($conn, $_POST['periode_tahun']);

    $capaian = ($target > 0) ? round(($realisasi / $target) * 100, 2) : 0;

    $sql = "INSERT INTO input_kpi 
            (unit_kerja, kode_indikator, nama_indikator, target, realisasi, capaian, periode_bulan, periode_tahun, keterangan, dibuat_oleh, dibuat_pada)
            VALUES ('$unit_kerja_user', '$kode', '$nama', '$target', '$realisasi', '$capaian', '$bulan', '$tahun', '$keterangan', '$nama_user', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data KPI berhasil disimpan'); window.location.href='input_kpi.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data: " . mysqli_error($conn) . "');</script>";
    }
}

// === FILTER PERIODE ===
$bulan = $_POST['filter_bulan'] ?? date('F');
$tahun = $_POST['filter_tahun'] ?? date('Y');

// === AMBIL DATA KPI BERDASARKAN PERIODE ===
$query_data = "SELECT * FROM input_kpi 
               WHERE unit_kerja='$unit_kerja_user' 
               AND periode_bulan='$bulan' 
               AND periode_tahun='$tahun' 
               ORDER BY id DESC";
$result_data = mysqli_query($conn, $query_data);

// === AMBIL MASTER KPI UNTUK PILIHAN INDIKATOR ===
$query_master = "SELECT * FROM master_indikator_kpi WHERE unit_kerja='$unit_kerja_user' ORDER BY nama_indikator ASC";
$master_list = mysqli_query($conn, $query_master);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Input KPI</title>
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
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4>Input KPI - <?= htmlspecialchars($unit_kerja_user); ?></h4>
              <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus-circle"></i> Tambah Data KPI
              </button>
            </div>

            <div class="card-body">

              <!-- Filter Periode -->
              <form method="POST" class="form-inline mb-3">
                <label class="mr-2">Periode:</label>
                <select name="filter_bulan" class="form-control mr-2">
                  <?php
                  $bulan_list = ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
                                 "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                  foreach ($bulan_list as $b) {
                      $sel = ($bulan == $b) ? "selected" : "";
                      echo "<option $sel>$b</option>";
                  }
                  ?>
                </select>
                <select name="filter_tahun" class="form-control mr-2">
                  <?php
                  $tahun_now = date('Y');
                  for ($i = $tahun_now - 3; $i <= $tahun_now + 2; $i++) {
                      $sel = ($tahun == $i) ? "selected" : "";
                      echo "<option $sel>$i</option>";
                  }
                  ?>
                </select>
                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-search"></i> Tampilkan</button>
              </form>

              <!-- Tabel Data -->
              <div class="table-responsive">
                <table class="table table-bordered table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>Kode</th>
                      <th>Nama Indikator</th>
                      <th>Target</th>
                      <th>Realisasi</th>
                      <th>Capaian (%)</th>
                      <th>Keterangan</th>
                      <th>Periode</th>
                      <th>Dibuat Oleh</th>
                      <th>Tanggal Input</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (mysqli_num_rows($result_data) > 0) {
                      $no = 1;
                      while ($row = mysqli_fetch_assoc($result_data)) {
                        echo "
                        <tr>
                          <td class='text-center'>$no</td>
                          <td>{$row['kode_indikator']}</td>
                          <td>{$row['nama_indikator']}</td>
                          <td>{$row['target']}</td>
                          <td>{$row['realisasi']}</td>
                          <td class='text-center'>{$row['capaian']}</td>
                          <td>{$row['keterangan']}</td>
                          <td>{$row['periode_bulan']} {$row['periode_tahun']}</td>
                          <td>{$row['dibuat_oleh']}</td>
                          <td>" . date('d-m-Y H:i', strtotime($row['dibuat_pada'])) . "</td>
                        </tr>";
                        $no++;
                      }
                    } else {
                      echo "<tr><td colspan='10' class='text-center text-muted'>Belum ada data untuk periode ini.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </section>
    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST">
      <input type="hidden" name="aksi" value="simpan">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalTambahLabel"><i class="fas fa-plus-circle"></i> Tambah Input KPI</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">

          <div class="form-group">
            <label>Indikator</label>
            <select name="kode_indikator" class="form-control" required>
              <option value="">-- Pilih Indikator --</option>
              <?php while ($m = mysqli_fetch_assoc($master_list)) { ?>
                <option value="<?= $m['kode_indikator'] ?>"><?= $m['kode_indikator'] ?> - <?= $m['nama_indikator'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label>Nama Indikator</label>
            <input type="text" name="nama_indikator" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Target</label>
            <input type="number" name="target" class="form-control" step="0.01" required>
          </div>

          <div class="form-group">
            <label>Realisasi</label>
            <input type="number" name="realisasi" class="form-control" step="0.01" required>
          </div>

          <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan, kendala, atau penjelasan tambahan"></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label>Periode Bulan</label>
              <select name="periode_bulan" class="form-control" required>
                <?php
                foreach ($bulan_list as $b) {
                  $sel = ($b == date('F')) ? "selected" : "";
                  echo "<option $sel>$b</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-md-6">
              <label>Periode Tahun</label>
              <select name="periode_tahun" class="form-control" required>
                <?php
                for ($i = $tahun_now - 3; $i <= $tahun_now + 2; $i++) {
                  $sel = ($i == date('Y')) ? "selected" : "";
                  echo "<option $sel>$i</option>";
                }
                ?>
              </select>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
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
