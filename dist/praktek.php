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

// === SIMPAN NILAI PRAKTEK ===
if (isset($_POST['simpan'])) {
    $perawat_id = (int)$_POST['perawat_id'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $keterampilan = (float)$_POST['keterampilan'];
    $pengetahuan = (float)$_POST['pengetahuan'];
    $komunikasi = (float)$_POST['komunikasi'];
    $keselamatan = (float)$_POST['keselamatan'];
    $sikap = (float)$_POST['sikap'];
    $dokumentasi = (float)$_POST['dokumentasi'];
    
    $total = $keterampilan + $pengetahuan + $komunikasi + $keselamatan + $sikap + $dokumentasi;
    $nilai_akhir = $total / 6;

    // Hilangkan .00 jika angka bulat, tetap tampilkan desimal jika ada
    $nilai_akhir = ($nilai_akhir == floor($nilai_akhir)) ? floor($nilai_akhir) : round($nilai_akhir, 2);
    $status = ($nilai_akhir >= 75) ? 'Lulus' : 'Remedial';

    $insert = mysqli_query($conn, "INSERT INTO nilai_praktek 
        (perawat_id, kategori, keterampilan, pengetahuan, komunikasi, keselamatan, sikap, dokumentasi, nilai_akhir, status, tanggal_input)
        VALUES ('$perawat_id','$kategori','$keterampilan','$pengetahuan','$komunikasi','$keselamatan','$sikap','$dokumentasi','$nilai_akhir','$status', NOW())");
    
    if ($insert) {
        $_SESSION['flash_message'] = "✅ Nilai praktek berhasil disimpan.";
        echo "<script>location.href='praktek.php';</script>";
        exit;
    } else {
        echo "<div class='alert alert-danger'>❌ Gagal menyimpan nilai praktek.</div>";
    }
}

// === PENCARIAN ===
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Input Nilai Praktek Perawat</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
#notif-toast {
  position: fixed;
  top: 10%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 9999;
  display: none;
  min-width: 320px;
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
             <h4>
  <i class="fas fa-user-nurse"></i> Nilai Praktek Perawat 
  <a href="#" data-toggle="modal" data-target="#modalPanduan" class="text-danger ml-2" title="Panduan Input">
    <i class="fas fa-question-circle"></i>
  </a>
</h4>

              <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus-circle"></i> Input Nilai
              </button>
            </div>

            <div class="card-body">
              <?php
              if (isset($_SESSION['flash_message'])) {
                  echo "<div id='notif-toast' class='alert alert-info text-center'>{$_SESSION['flash_message']}</div>";
                  unset($_SESSION['flash_message']);
              }
              ?>

            <form method="GET" class="form-inline mb-3">
  <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2 mb-2" placeholder="🔍 Cari nama perawat...">

  <select name="filter_kategori" class="form-control mr-2 mb-2">
    <option value="">-- Semua Kategori --</option>
    <option value="Pra Klinis" <?= (isset($_GET['filter_kategori']) && $_GET['filter_kategori']=='Pra Klinis')?'selected':'' ?>>Pra Klinis</option>
    <option value="PK I" <?= (isset($_GET['filter_kategori']) && $_GET['filter_kategori']=='PK I')?'selected':'' ?>>PK I</option>
    <option value="PK II" <?= (isset($_GET['filter_kategori']) && $_GET['filter_kategori']=='PK II')?'selected':'' ?>>PK II</option>
    <option value="PK III" <?= (isset($_GET['filter_kategori']) && $_GET['filter_kategori']=='PK III')?'selected':'' ?>>PK III</option>
  </select>

  <select name="filter_status" class="form-control mr-2 mb-2">
    <option value="">-- Semua Status --</option>
    <option value="Lulus" <?= (isset($_GET['filter_status']) && $_GET['filter_status']=='Lulus')?'selected':'' ?>>Lulus</option>
    <option value="Remedial" <?= (isset($_GET['filter_status']) && $_GET['filter_status']=='Remedial')?'selected':'' ?>>Remedial</option>
  </select>

  <input type="date" name="filter_tanggal" value="<?= isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : '' ?>" class="form-control mr-2 mb-2" placeholder="Tanggal">

  <button type="submit" class="btn btn-primary btn-sm mb-2"><i class="fas fa-filter"></i> Filter</button>
  <a href="praktek.php" class="btn btn-secondary btn-sm mb-2 ml-2"><i class="fas fa-sync-alt"></i> Reset</a>
</form>


              <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover">
                <thead class="thead-dark text-center">
  <tr>
    <th>No</th>
    <th>Nama Perawat</th>
    <th>Kategori</th>
    <th>Nilai Akhir</th>
    <th>Status</th>
    <th>Tanggal Ujian</th>
    <th>Aksi</th>
  </tr>
</thead>

                  <tbody>
                    <?php
                    $filter_kategori = isset($_GET['filter_kategori']) ? mysqli_real_escape_string($conn, $_GET['filter_kategori']) : '';
$filter_status   = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$filter_tanggal  = isset($_GET['filter_tanggal']) ? mysqli_real_escape_string($conn, $_GET['filter_tanggal']) : '';

$where_clauses = array();
if(!empty($keyword)) $where_clauses[] = "u.nama LIKE '%$keyword%'";
if(!empty($filter_kategori)) $where_clauses[] = "n.kategori='$filter_kategori'";
if(!empty($filter_status)) $where_clauses[] = "n.status='$filter_status'";
if(!empty($filter_tanggal)) $where_clauses[] = "DATE(n.tanggal_input)='$filter_tanggal'";

$where = '';
if(count($where_clauses) > 0){
    $where = 'WHERE '.implode(' AND ', $where_clauses);
}

$query = "SELECT n.*, u.nama FROM nilai_praktek n 
          JOIN users u ON n.perawat_id = u.id
          $where
          ORDER BY n.tanggal_input DESC";
$result = mysqli_query($conn, $query);

                    $no = 1;
                    if(mysqli_num_rows($result) > 0){
                        while($row = mysqli_fetch_assoc($result)){
                            $nilai_akhir_display = ($row['nilai_akhir'] == floor($row['nilai_akhir'])) ? floor($row['nilai_akhir']) : $row['nilai_akhir'];
                          echo "<tr>
        <td class='text-center'>{$no}</td>
        <td>".htmlspecialchars($row['nama'])."</td>
        <td class='text-center'>".htmlspecialchars($row['kategori'])."</td>
        <td class='text-center'>{$nilai_akhir_display}</td>
        <td class='text-center'>{$row['status']}</td>
        <td class='text-center'>".date('d-m-Y H:i', strtotime($row['tanggal_input']))."</td>
        <td class='text-center'>
          <a href='cetak_lembar_praktek.php?id={$row['id']}' target='_blank' class='btn btn-primary btn-sm'>
            <i class='fas fa-print'></i> Print
          </a>
        </td>
      </tr>";

                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Tidak ada data.</td></tr>";
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

<!-- === MODAL TAMBAH === -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Input Nilai Praktek</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><i class="fas fa-user"></i> Perawat</label>
              <select name="perawat_id" class="form-control" required>
                <option value="">-- Pilih Perawat --</option>
                <?php
                $qPerawat = mysqli_query($conn, "SELECT id, nama, jabatan FROM users WHERE status='active' ORDER BY nama ASC");
                while($p = mysqli_fetch_assoc($qPerawat)) {
                    echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['nama'].' / '.$p['jabatan']).'</option>';
                }
                ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label><i class="fas fa-tags"></i> Kategori</label>
              <select name="kategori" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Pra Klinis">Pra Klinis</option>
                <option value="PK I">PK I</option>
                <option value="PK II">PK II</option>
                <option value="PK III">PK III</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label><i class="fas fa-tools"></i> Keterampilan</label>
              <input type="number" step="0.1" name="keterampilan" class="form-control" min="0" max="100" required>
            </div>
            <div class="form-group col-md-4">
              <label><i class="fas fa-book"></i> Pengetahuan</label>
              <input type="number" step="0.1" name="pengetahuan" class="form-control" min="0" max="100" required>
            </div>
            <div class="form-group col-md-4">
              <label><i class="fas fa-comments"></i> Komunikasi</label>
              <input type="number" step="0.1" name="komunikasi" class="form-control" min="0" max="100" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-3">
              <label><i class="fas fa-shield-alt"></i> Keselamatan</label>
              <input type="number" step="0.1" name="keselamatan" class="form-control" min="0" max="100" required>
            </div>
            <div class="form-group col-md-3">
              <label><i class="fas fa-smile"></i> Sikap</label>
              <input type="number" step="0.1" name="sikap" class="form-control" min="0" max="100" required>
            </div>
            <div class="form-group col-md-3">
              <label><i class="fas fa-file-alt"></i> Dokumentasi</label>
              <input type="number" step="0.1" name="dokumentasi" class="form-control" min="0" max="100" required>
            </div>
            <div class="form-group col-md-3 d-flex align-items-end">
              <button type="submit" name="simpan" class="btn btn-success btn-block"><i class="fas fa-save"></i> Simpan Nilai</button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times-circle"></i> Tutup</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- === MODAL PANDUAN === -->
<div class="modal fade" id="modalPanduan" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-question-circle"></i> Panduan Input Nilai Praktek</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <ol>
          <li>Pilih nama perawat dari dropdown "Perawat".</li>
          <li>Pilih kategori praktek sesuai dengan levelnya (Pra Klinis, PK I, PK II, PK III).</li>
          <li>Isi nilai <b>Keterampilan</b>, <b>Pengetahuan</b>, <b>Komunikasi</b>, <b>Keselamatan</b>, <b>Sikap</b>, dan <b>Dokumentasi</b> dengan angka 0–100.</li>
          <li>Pastikan semua field diisi sebelum menekan tombol "Simpan Nilai".</li>
          <li>Nilai akhir akan dihitung otomatis dan status <b>Lulus</b> atau <b>Remedial</b> ditentukan berdasarkan nilai >= 75.</li>
          <li>Jika berhasil, notifikasi akan muncul dan tabel akan menampilkan data terbaru.</li>
        </ol>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times-circle"></i> Tutup</button>
      </div>
    </div>
  </div>
</div>


<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script>
$(document).ready(function(){
    var toast = $('#notif-toast');
    if(toast.length) toast.fadeIn(300).delay(2500).fadeOut(500);
});
</script>
</body>
</html>
