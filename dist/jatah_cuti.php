<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'is', $user_id, $current_file);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Ambil data master cuti & user untuk dropdown
$masterCuti = mysqli_query($conn, "SELECT * FROM master_cuti ORDER BY nama_cuti ASC");
$users      = mysqli_query($conn, "SELECT id, nama FROM users ORDER BY nama ASC");

// Proses Simpan Data (insert atau update jika sudah ada jatah untuk tahun/cuti/karyawan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
  $user_id_post      = intval($_POST['user_id']);
  $master_cuti_id    = intval($_POST['master_cuti_id']);
  $lama_hari         = intval($_POST['lama_hari']);
  $tahun             = intval($_POST['tahun']);

  if ($user_id_post <= 0 || $master_cuti_id <= 0 || $lama_hari <= 0 || $tahun <= 0) {
    $_SESSION['flash_message'] = "Semua field wajib diisi!";
  } else {
    // Cek apakah sudah ada jatah untuk karyawan+cuti+tahun
    $checkSql = "SELECT id, lama_hari AS old_lama, sisa_hari AS old_sisa FROM jatah_cuti 
                 WHERE karyawan_id = ? AND cuti_id = ? AND tahun = ? LIMIT 1";
    $chk = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($chk, 'iii', $user_id_post, $master_cuti_id, $tahun);
    mysqli_stmt_execute($chk);
    $resChk = mysqli_stmt_get_result($chk);

    if ($resChk && mysqli_num_rows($resChk) > 0) {
      // update existing: hitung used days dulu supaya tidak menimpa sisa yang sudah terpakai
      $row = mysqli_fetch_assoc($resChk);
      $old_lama = (int)$row['old_lama'];
      $old_sisa = (int)$row['old_sisa'];
      $used = max(0, $old_lama - $old_sisa); // hari yang sudah dipakai
      $new_sisa = $lama_hari - $used;
      if ($new_sisa < 0) $new_sisa = 0;

      $updateSql = "UPDATE jatah_cuti SET lama_hari = ?, sisa_hari = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?";
      $upd = mysqli_prepare($conn, $updateSql);
      mysqli_stmt_bind_param($upd, 'iii', $lama_hari, $new_sisa, $row['id']);
      if (mysqli_stmt_execute($upd)) {
        $_SESSION['flash_message'] = "Jatah cuti berhasil diupdate. Sisa hari sekarang: {$new_sisa} hari.";
      } else {
        $_SESSION['flash_message'] = "Gagal update jatah cuti: " . mysqli_error($conn);
      }
    } else {
      // insert baru: set sisa_hari = lama_hari
      $insertSql = "INSERT INTO jatah_cuti (karyawan_id, cuti_id, lama_hari, sisa_hari, tahun, created_at) 
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
      $ins = mysqli_prepare($conn, $insertSql);
      mysqli_stmt_bind_param($ins, 'iiiii', $user_id_post, $master_cuti_id, $lama_hari, $lama_hari, $tahun);
      if (mysqli_stmt_execute($ins)) {
        $_SESSION['flash_message'] = "Jatah cuti berhasil disimpan. Sisa hari: {$lama_hari} hari.";
      } else {
        $_SESSION['flash_message'] = "Gagal menyimpan data: " . mysqli_error($conn);
      }
    }
  }
  header("Location: jatah_cuti.php");
  exit;
}

// Ambil Data Jatah Cuti untuk Tabel (tampilkan sisa_hari)
$dataJatah = mysqli_query($conn, "SELECT jatah_cuti.*, users.nama, master_cuti.nama_cuti
                                  FROM jatah_cuti
                                  JOIN users ON jatah_cuti.karyawan_id = users.id
                                  JOIN master_cuti ON jatah_cuti.cuti_id = master_cuti.id
                                  ORDER BY jatah_cuti.id DESC");
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
      position: fixed; top: 20%; left: 50%; transform: translate(-50%, -50%);
      z-index: 1050; min-width: 300px; max-width: 90%; text-align: center;
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
              <h4 class="mb-0">Jatah Cuti Karyawan</h4>
            </div>

            <div class="card-body">
              <!-- Tab menu -->
              <ul class="nav nav-tabs" id="jatahCutiTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Data</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Jatah Cuti</a>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-3">
                <!-- Form Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                  <form method="post">
                    <div class="form-group">
                      <label for="user_id">Karyawan</label>
                      <select name="user_id" id="user_id" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php mysqli_data_seek($users, 0); while($u = mysqli_fetch_assoc($users)): ?>
                          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                        <?php endwhile; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="master_cuti_id">Jenis Cuti</label>
                      <select name="master_cuti_id" id="master_cuti_id" class="form-control" required>
                        <option value="">-- Pilih Jenis Cuti --</option>
                        <?php mysqli_data_seek($masterCuti, 0); while($mc = mysqli_fetch_assoc($masterCuti)): ?>
                          <option value="<?= $mc['id'] ?>" data-lama="<?= $mc['lama_hari'] ?>">
                            <?= htmlspecialchars($mc['nama_cuti']) ?>
                          </option>
                        <?php endwhile; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="lama_hari">Lama Hari</label>
                      <input type="number" name="lama_hari" id="lama_hari" class="form-control" readonly required>
                    </div>

                    <div class="form-group">
                      <label for="tahun">Tahun</label>
                      <input type="number" name="tahun" id="tahun" value="<?= date('Y') ?>" class="form-control" required>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-primary">
                      <i class="fas fa-save"></i> Simpan
                    </button>
                  </form>
                </div>

                <!-- Tabel Data -->
                <div class="tab-pane fade" id="data" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered cuti-table">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Karyawan</th>
                          <th>Jenis Cuti</th>
                          <th>Lama Hari</th>
                          <th>Sisa Hari</th>
                          <th>Tahun</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        $no=1;
                        while ($row = mysqli_fetch_assoc($dataJatah)): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['nama_cuti']) ?></td>
                            <td><?= (int)$row['lama_hari'] ?> hari</td>
                            <td><?= isset($row['sisa_hari']) ? (int)$row['sisa_hari'] . ' hari' : '-'; ?></td>
                            <td><?= htmlspecialchars($row['tahun']) ?></td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div> <!-- End Tab Content -->
            </div>
          </div>

        </div>
      </section>
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
    }, 3000);

    // otomatis isi lama_hari sesuai cuti
    $("#master_cuti_id").change(function() {
      var lama = $(this).find(':selected').data('lama') || '';
      $("#lama_hari").val(lama);
    });
  });
</script>

</body>
</html>
