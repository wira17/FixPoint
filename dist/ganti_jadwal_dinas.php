<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// === Cek akses menu ===
$qAkses = "SELECT 1 FROM akses_menu 
           JOIN menu ON akses_menu.menu_id = menu.id 
           WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$rAkses = mysqli_query($conn, $qAkses);
if (mysqli_num_rows($rAkses) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// === Data user login ===
$qUser = mysqli_query($conn, "SELECT id, nama, unit_kerja FROM users WHERE id='$user_id'");
$userLogin = mysqli_fetch_assoc($qUser);

// === Dropdown karyawan pengganti (unit kerja sama, kecuali diri sendiri) ===
$delegasiList = mysqli_query($conn, "SELECT id, nama FROM users 
                                     WHERE unit_kerja = '".$userLogin['unit_kerja']."' 
                                     AND id <> '".$userLogin['id']."' 
                                     ORDER BY nama ASC");

// === Ambil jadwal dinas user login ===
$jadwalQuery = mysqli_query($conn, "SELECT tanggal, jam_kerja_id FROM jadwal_dinas 
                                    WHERE user_id='$user_id' 
                                    ORDER BY tanggal ASC");

// Mapping jam kerja
$jamQuery = mysqli_query($conn, "SELECT * FROM jam_kerja ORDER BY jam_mulai");
$jamList = [];
while($j = mysqli_fetch_assoc($jamQuery)){
    $jamList[$j['id']] = $j['nama_jam'] . " ({$j['jam_mulai']}-{$j['jam_selesai']})";
}

// === Proses simpan pengajuan ganti jadwal ===
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['simpan'])){
    $karyawan_id = $userLogin['id'];
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_kerja_id = intval($_POST['jam_kerja_id']);
    $pengganti_id = intval($_POST['pengganti_id']);
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);

    if(empty($tanggal) || $pengganti_id<=0 || $jam_kerja_id<=0 || empty($alasan)){
        $_SESSION['flash_message'] = "Semua field wajib diisi!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $sql = "INSERT INTO pengajuan_ganti_jadwal 
                    (karyawan_id, pengganti_id, tanggal, jam_kerja_id, alasan, 
                     status, created_by, created_at)
                    VALUES 
                    ('$karyawan_id','$pengganti_id','$tanggal','$jam_kerja_id','$alasan',
                     'Menunggu','{$userLogin['nama']}',NOW())";
            mysqli_query($conn, $sql);
            mysqli_commit($conn);
            $_SESSION['flash_message'] = "Pengajuan ganti jadwal berhasil disimpan.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['flash_message'] = "Gagal menyimpan data: ".$e->getMessage();
        }
    }

    header("Location: ganti_jadwal_dinas.php");
    exit;
}

// === Ambil data pengajuan untuk tabel ===
$dataPengajuan = mysqli_query($conn, "
    SELECT p.*, u.nama AS nama_karyawan, d.nama AS nama_pengganti, j.nama_jam
    FROM pengajuan_ganti_jadwal p
    JOIN users u ON p.karyawan_id=u.id
    JOIN users d ON p.pengganti_id=d.id
    JOIN jam_kerja j ON p.jam_kerja_id=j.id
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pengajuan Ganti Jadwal Dinas</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.flash-center {
  position: fixed; top: 20%; left: 50%; transform: translate(-50%, -50%);
  z-index: 1050; min-width: 300px; max-width: 90%; text-align: center;
  padding: 15px; border-radius: 8px; font-weight: 500;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.ganti-table { font-size: 13px; white-space: nowrap; }
.ganti-table th, .ganti-table td { padding: 6px 10px; vertical-align: middle; }
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

        <?php if(isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-info flash-center" id="flashMsg"><?= $_SESSION['flash_message']; ?></div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <div class="card">
          <div class="card-header"><h4>Pengajuan Ganti Jadwal Dinas</h4></div>
          <div class="card-body">
            <ul class="nav nav-tabs" id="gantiTab" role="tablist">
              <li class="nav-item"><a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Pengajuan</a></li>
              <li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Pengajuan</a></li>
            </ul>

            <div class="tab-content mt-3">
              <!-- Form Input Kiri-Kanan -->
              <div class="tab-pane fade show active" id="input" role="tabpanel">
                <form method="post">
                  <div class="row">
                    <!-- Kolom Kiri -->
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><i class="fas fa-user"></i> Karyawan</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($userLogin['nama']) ?>" readonly>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Kerja</label>
                        <select name="jam_kerja_id" class="form-control" required>
                          <option value="">-- Pilih Jam --</option>
                          <?php foreach($jamList as $id=>$jam): ?>
                            <option value="<?= $id ?>"><?= $jam ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><i class="fas fa-user-check"></i> Pengganti</label>
                        <select name="pengganti_id" class="form-control" required>
                          <option value="">-- Pilih Pengganti --</option>
                          <?php
                          mysqli_data_seek($delegasiList,0);
                          while($d = mysqli_fetch_assoc($delegasiList)): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
                          <?php endwhile; ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> Alasan</label>
                        <textarea name="alasan" class="form-control" required></textarea>
                      </div>

                      <button type="submit" name="simpan" class="btn btn-primary mt-2">
                        <i class="fas fa-paper-plane"></i> Ajukan
                      </button>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Tabel Data -->
              <div class="tab-pane fade" id="data" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-striped table-bordered ganti-table">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Karyawan</th>
                        <th>Pengganti</th>
                        <th>Tanggal</th>
                        <th>Jam Kerja</th>
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
                          <td><?= htmlspecialchars($row['nama_pengganti']) ?></td>
                          <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                          <td><?= htmlspecialchars($row['nama_jam']) ?></td>
                          <td><?= htmlspecialchars($row['alasan']) ?></td>
                          <td><?= htmlspecialchars($row['status']) ?></td>
                          <td class="text-center">
                            <a href="cetak_ganti_jadwal.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-info btn-sm">
                              <i class="fas fa-print"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
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

<script>
$(document).ready(function(){
  setTimeout(function(){ $("#flashMsg").fadeOut("slow"); },3000);
});
</script>
</body>
</html>
