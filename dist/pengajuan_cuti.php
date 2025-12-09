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

// === Ambil data user login ===
$qUser = mysqli_query($conn, "SELECT id, nama, unit_kerja FROM users WHERE id='$user_id'");
$userLogin = mysqli_fetch_assoc($qUser);

// === Dropdown Master Cuti & Delegasi ===
$masterCuti = mysqli_query($conn, "SELECT * FROM master_cuti ORDER BY nama_cuti ASC");
$delegasiList = mysqli_query($conn, "SELECT id, nama FROM users 
                                     WHERE unit_kerja = '".$userLogin['unit_kerja']."' 
                                     AND id <> '".$userLogin['id']."' 
                                     ORDER BY nama ASC");

// === Data jatah cuti user login (untuk validasi) ===
$tahun = date('Y');
$sqlCuti = "SELECT cuti_id, sisa_hari FROM jatah_cuti WHERE karyawan_id='$user_id' AND tahun='$tahun'";
$resCuti = mysqli_query($conn, $sqlCuti);
$sisaCuti = [];
while ($row = mysqli_fetch_assoc($resCuti)) {
    $sisaCuti[$row['cuti_id']] = $row['sisa_hari'];
}

// === Proses Simpan Pengajuan ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
  $karyawan_id    = $userLogin['id'];
  $master_cuti_id = intval($_POST['master_cuti_id']);
  $delegasi_id    = intval($_POST['delegasi_id']);
  $alasan         = mysqli_real_escape_string($conn, $_POST['alasan']);
  $tanggalArray   = $_POST['tanggal'] ?? [];

  // === Cek apakah masih ada cuti yang statusnya pending ===
  $cekPending = mysqli_query($conn, "
    SELECT id FROM pengajuan_cuti 
    WHERE karyawan_id='$karyawan_id' 
      AND (
          status_delegasi='Menunggu' 
          OR status_atasan='Menunggu' 
          OR status_hrd='Menunggu'
      )
    LIMIT 1
  ");

  if (mysqli_num_rows($cekPending) > 0) {
    $_SESSION['flash_message'] = "⚠️ Anda masih memiliki pengajuan cuti yang belum diproses. Tunggu sampai disetujui atau ditolak sebelum mengajukan lagi.";
    header("Location: pengajuan_cuti.php");
    exit;
  }

  // === Validasi field ===
  if ($master_cuti_id <= 0 || empty($tanggalArray)) {
    $_SESSION['flash_message'] = "❌ Semua field wajib diisi!";
  } else {
    sort($tanggalArray);
    $tanggal_mulai   = $tanggalArray[0];
    $tanggal_selesai = end($tanggalArray);
    $lama_hari       = count($tanggalArray);

    // === VALIDASI BARU: Lama cuti > sisa cuti ===
    $sisa = $sisaCuti[$master_cuti_id] ?? 0;
    if ($lama_hari > $sisa) {
      $_SESSION['flash_message'] = "❌ Gagal! Lama cuti ($lama_hari hari) melebihi sisa cuti ($sisa hari).";
      header("Location: pengajuan_cuti.php");
      exit;
    }

    // === Simpan ke DB jika valid ===
    mysqli_begin_transaction($conn);
    try {
      $sql = "INSERT INTO pengajuan_cuti 
                (karyawan_id, cuti_id, delegasi_id, tanggal_mulai, tanggal_selesai, lama_hari, alasan, 
                 status, status_delegasi, status_atasan, status_hrd,
                 acc_delegasi_by, acc_atasan_by, acc_hrd_by) 
              VALUES 
                ('$karyawan_id','$master_cuti_id','$delegasi_id','$tanggal_mulai','$tanggal_selesai',
                 '$lama_hari','$alasan',
                 'Menunggu Delegasi','Menunggu','Menunggu','Menunggu',
                 NULL,NULL,NULL)";
      mysqli_query($conn, $sql);
      $pengajuan_id = mysqli_insert_id($conn);

      foreach ($tanggalArray as $tgl) {
        $tgl = mysqli_real_escape_string($conn, $tgl);
        mysqli_query($conn, "INSERT INTO pengajuan_cuti_detail (pengajuan_id, tanggal) VALUES ('$pengajuan_id','$tgl')");
      }

      mysqli_commit($conn);
      $_SESSION['flash_message'] = "✅ Pengajuan cuti berhasil disimpan.";
    } catch (Exception $e) {
      mysqli_rollback($conn);
      $_SESSION['flash_message'] = "❌ Gagal menyimpan data: " . $e->getMessage();
    }
  }
  header("Location: pengajuan_cuti.php");
  exit;
}

// === Ambil data jatah cuti user login (untuk modal) ===
$sql = "SELECT mc.nama_cuti, mc.id as cuti_id, jc.lama_hari, jc.sisa_hari,
               (jc.lama_hari - jc.sisa_hari) AS terpakai
        FROM jatah_cuti jc
        JOIN master_cuti mc ON jc.cuti_id = mc.id
        WHERE jc.karyawan_id = ? AND jc.tahun = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    $dataCuti = [];
    while($row = $result->fetch_assoc()){ $dataCuti[] = $row; }
    $stmt->close();
} else {
    $dataCuti = [];
}

// === Ambil Data Pengajuan Cuti ===
$dataPengajuan = mysqli_query($conn, "
  SELECT p.*, u.nama AS nama_karyawan, mc.nama_cuti, d.nama AS nama_delegasi,
         GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti
  FROM pengajuan_cuti p
  JOIN users u ON p.karyawan_id = u.id
  JOIN master_cuti mc ON p.cuti_id = mc.id
  LEFT JOIN users d ON p.delegasi_id = d.id
  LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
  WHERE p.karyawan_id = '$user_id'
  GROUP BY p.id
  ORDER BY p.id DESC
");
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
  <h4 class="mb-0">
    Pengajuan Cuti 
    <a href="#" data-toggle="modal" data-target="#modalInfoCuti" class="ml-2 text-danger" title="Info Cuti">
      <i class="fas fa-question-circle"></i>
    </a>
  </h4>
</div>


            <div class="card-body">
              <!-- Tab menu -->
              <ul class="nav nav-tabs" id="pengajuanCutiTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Pengajuan</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Pengajuan</a>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-3">
                <!-- Form Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                 <form method="post">
  <div class="row">
    <!-- Kolom Kiri -->
    <div class="col-md-6">
      <div class="form-group">
        <label>Karyawan</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($userLogin['nama']) ?>" readonly>
      </div>
      <div class="form-group">
        <label for="master_cuti_id">Jenis Cuti</label>
        <select name="master_cuti_id" id="master_cuti_id" class="form-control" required>
          <option value="">-- Pilih Jenis Cuti --</option>
          <?php while($mc = mysqli_fetch_assoc($masterCuti)): ?>
            <option value="<?= $mc['id'] ?>"><?= htmlspecialchars($mc['nama_cuti']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="delegasi_id">Delegasi (Pengganti)</label>
        <select name="delegasi_id" id="delegasi_id" class="form-control" required>
          <option value="">-- Pilih Delegasi --</option>
          <?php while($d = mysqli_fetch_assoc($delegasiList)): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>

    <!-- Kolom Kanan -->
    <div class="col-md-6">
      <label for="tanggal">Tanggal Cuti</label>
      <div id="tanggal-wrapper">
        <div class="input-group mb-2">
          <input type="date" name="tanggal[]" class="form-control" required>
          <div class="input-group-append">
            <button type="button" class="btn btn-danger btn-remove-tanggal">&times;</button>
          </div>
        </div>
      </div>
      <button type="button" id="btnAddTanggal" class="btn btn-success btn-sm mb-3">
        + Tambah Tanggal
      </button>

      <div class="form-group">
        <label for="lama_hari">Jumlah Hari</label>
        <input type="number" name="lama_hari" id="lama_hari" class="form-control" readonly required>
      </div>
    </div>
  </div>

  <!-- Full width -->
  <div class="form-group">
    <label for="alasan">Alasan</label>
    <textarea name="alasan" id="alasan" class="form-control" required></textarea>
  </div>

  <button type="submit" name="simpan" class="btn btn-primary">
    <i class="fas fa-paper-plane"></i> Ajukan
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
                          <th>Delegasi</th>
                          <th>Tanggal</th>
                          <th>Lama</th>
                          <th>Alasan</th>
                          <th>Status Delegasi</th>
                          <th>Disetujui/Tolak Oleh</th>
                          <th>Status Atasan</th>
                          <th>Disetujui/Tolak Oleh</th>
                          <th>Status HRD</th>
                          <th>Disetujui/Tolak Oleh</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; while ($row = mysqli_fetch_assoc($dataPengajuan)): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_cuti']) ?></td>
                            <td><?= htmlspecialchars($row['nama_delegasi'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['tanggal_cuti']) ?></td>
                            <td><?= $row['lama_hari'] ?> hari</td>
                            <td><?= htmlspecialchars($row['alasan']) ?></td>

                            <!-- Status Delegasi -->
                            <td>
                              <span class="badge 
                                <?= $row['status_delegasi']=='Disetujui'?'badge-success':($row['status_delegasi']=='Ditolak'?'badge-danger':'badge-warning') ?>">
                                <?= $row['status_delegasi'] ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($row['acc_delegasi_by'] ?? '-') ?></td>

                            <!-- Status Atasan -->
                            <td>
                              <span class="badge 
                                <?= $row['status_atasan']=='Disetujui'?'badge-success':($row['status_atasan']=='Ditolak'?'badge-danger':'badge-warning') ?>">
                                <?= $row['status_atasan'] ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($row['acc_atasan_by'] ?? '-') ?></td>

                            <!-- Status HRD -->
                            <td>
                              <span class="badge 
                                <?= $row['status_hrd']=='Disetujui'?'badge-success':($row['status_hrd']=='Ditolak'?'badge-danger':'badge-warning') ?>">
                                <?= $row['status_hrd'] ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($row['acc_hrd_by'] ?? '-') ?></td>

                          <td class="text-center">
  <a href="cetak_cuti.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info" title="Cetak">
    <i class="fas fa-print"></i>
  </a>

  <?php if (
    $row['status_delegasi'] == 'Menunggu' && 
    $row['status_atasan'] == 'Menunggu' && 
    $row['status_hrd'] == 'Menunggu'
  ): ?>
    <a href="batal_cuti.php?id=<?= $row['id'] ?>" 
       class="btn btn-sm btn-danger" 
       title="Batalkan Pengajuan" 
       onclick="return confirm('Yakin ingin membatalkan pengajuan cuti ini?');">
       <i class="fas fa-times"></i>
    </a>
  <?php endif; ?>
</td>

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


<!-- Modal Info Cuti -->
<div class="modal fade" id="modalInfoCuti" tabindex="-1" role="dialog" aria-labelledby="modalInfoCutiLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalInfoCutiLabel"><i class="fas fa-info-circle"></i> Informasi Jatah Cuti <?= $tahun ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php if (!empty($dataCuti)): ?>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="thead-light">
                <tr>
                  <th>Jenis Cuti</th>
                  <th>Jatah (Hari)</th>
                  <th>Terpakai</th>
                  <th>Sisa</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dataCuti as $cuti): ?>
                  <tr>
                    <td><?= htmlspecialchars($cuti['nama_cuti']) ?></td>
                    <td><?= $cuti['lama_hari'] ?></td>
                    <td><?= $cuti['terpakai'] ?></td>
                    <td><?= $cuti['sisa_hari'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-warning mb-0">
            Data jatah cuti belum tersedia untuk tahun <?= $tahun ?>.
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
  }, 3000);

  // Tambah input tanggal baru
  $("#btnAddTanggal").click(function() {
    var html = `
      <div class="input-group mb-2">
        <input type="date" name="tanggal[]" class="form-control" required>
        <div class="input-group-append">
          <button type="button" class="btn btn-danger btn-remove-tanggal">&times;</button>
        </div>
      </div>`;
    $("#tanggal-wrapper").append(html);
    updateJumlahHari();
  });

  // Hapus input tanggal
  $(document).on("click", ".btn-remove-tanggal", function() {
    $(this).closest(".input-group").remove();
    updateJumlahHari();
  });

  // Hitung lama hari otomatis
  $(document).on("change", "input[name='tanggal[]']", function() {
    updateJumlahHari();
  });

  function updateJumlahHari() {
    var count = $("input[name='tanggal[]']").filter(function() {
      return $(this).val() !== "";
    }).length;
    $("#lama_hari").val(count);
  }
});
</script>
</body>
</html>
