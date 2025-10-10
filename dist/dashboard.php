<?php
include 'security.php';
include 'check_integrity.php';
include 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// pastikan integer
$karyawan_id = intval($_SESSION['user_id'] ?? 0);
$nama_user   = $_SESSION['nama'] ?? 'Pengguna';
$tahun = date('Y');

// Data cuti
$sql = "SELECT mc.nama_cuti, mc.id as cuti_id, jc.lama_hari, jc.sisa_hari,
               (jc.lama_hari - jc.sisa_hari) AS terpakai
        FROM jatah_cuti jc
        JOIN master_cuti mc ON jc.cuti_id = mc.id
        WHERE jc.karyawan_id = ? AND jc.tahun = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $karyawan_id, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    $dataCuti = [];
    while($row = $result->fetch_assoc()){ $dataCuti[] = $row; }
    $stmt->close();
} else {
    $dataCuti = [];
    // optional: log error $conn->error
}

// Data tiket IT Hardware
$sqlHW = "SELECT id, nomor_tiket, kategori, kendala, status, tanggal_input, status_validasi, teknisi_nama
          FROM tiket_it_hardware ORDER BY tanggal_input DESC";
$resultHW = $conn->query($sqlHW);
$dataTiketHardware = [];
if ($resultHW) {
    while($row = $resultHW->fetch_assoc()){ $dataTiketHardware[] = $row; }
}

// Data tiket IT Software
$sqlSW = "SELECT id, nomor_tiket, kategori, kendala, status, tanggal_input, status_validasi, teknisi_nama
          FROM tiket_it_software ORDER BY tanggal_input DESC";
$resultSW = $conn->query($sqlSW);
$dataTiketSoftware = [];
if ($resultSW) {
    while($row = $resultSW->fetch_assoc()){ $dataTiketSoftware[] = $row; }
}

// Proses update logbook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_logbook'])) {
    $id     = intval($_POST['id']);
    $judul  = trim($_POST['judul']);
    $isi    = trim($_POST['isi']);

    if ($id > 0 && $karyawan_id > 0) {
        $sqlUpdate = "UPDATE catatan_kerja SET judul=?, isi=? WHERE id=? AND user_id=?";
        $stmtUp = $conn->prepare($sqlUpdate);
        if (!$stmtUp) {
            // debug: tampilkan pesan error (bisa diubah untuk production)
            $err = $conn->error;
            echo "<script>alert('Gagal menyiapkan query: ".addslashes($err)."');window.location='dashboard.php';</script>";
            exit;
        }
        $stmtUp->bind_param("ssii", $judul, $isi, $id, $karyawan_id);
        if($stmtUp->execute()){
            $stmtUp->close();
            echo "<script>alert('Log Book berhasil diperbarui');window.location='dashboard.php';</script>";
            exit;
        } else {
            $err = $stmtUp->error;
            $stmtUp->close();
            echo "<script>alert('Gagal update logbook: ".addslashes($err)."');window.location='dashboard.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Data tidak valid');window.location='dashboard.php';</script>";
        exit;
    }
}



// Data Dokumen
$sqlDoc = "SELECT id, judul, pokja_id, elemen_penilaian, file_path, file_name_original, petugas, waktu_input 
           FROM dokumen ORDER BY waktu_input DESC";
$resultDoc = $conn->query($sqlDoc);
$dataDokumen = [];
if ($resultDoc) {
    while($row = $resultDoc->fetch_assoc()){ $dataDokumen[] = $row; }
}


// Data Log Book (hanya untuk user yang login)
$sqlLB = "SELECT id, judul, isi, tanggal 
          FROM catatan_kerja 
          WHERE user_id = ? 
          ORDER BY tanggal DESC";
$stmtLB = $conn->prepare($sqlLB);
$dataLogBook = [];
if ($stmtLB) {
    $stmtLB->bind_param("i", $karyawan_id);
    $stmtLB->execute();
    $resultLB = $stmtLB->get_result();
    while($row = $resultLB->fetch_assoc()){ $dataLogBook[] = $row; }
    $stmtLB->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .card-statistic-1 { padding:5px; margin-bottom:5px; font-size:13px; cursor:pointer; }
    .card-statistic-1 .card-icon { font-size:14px; padding:4px; width:30px; height:30px; }
    .card-statistic-1 .card-header h4 { font-size:11px; margin-bottom:2px; }
    .card-statistic-1 .card-body { font-size:14px; font-weight:bold; }
    .card-statistic-1 .card-wrap { padding-left:8px; }
    .row > [class*='col-'] { padding-right:5px; padding-left:5px; margin-bottom:5px; }

    /* Custom modal extra wide */
    .modal-xxl { max-width: 95% !important; }

    /* Font modal hitam */
    .modal-body, 
    .modal-body table, 
    .modal-body table td, 
    .modal-body table th { color: #000 !important; }


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
      <h1>Dashboard</h1>
    </div>

    <!-- Baris Pertama -->
    <div class="row">
      <!-- HRD -->
      <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1" data-toggle="modal" data-target="#modalHRD">
          <div class="card-icon bg-info"><i class="fas fa-users-cog"></i></div>
          <div class="card-wrap">
            <div class="card-header"><h4>HRD / SDM</h4></div>
            <div class="card-body">Info Cuti</div>
          </div>
        </div>
      </div>

      <!-- IT Hardware -->
      <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1" data-toggle="modal" data-target="#modalTiketHardware">
          <div class="card-icon bg-warning"><i class="fas fa-desktop"></i></div>
          <div class="card-wrap">
            <div class="card-header"><h4>IT Hardware</h4></div>
            <div class="card-body">Tiket</div>
          </div>
        </div>
      </div>

      <!-- IT Software -->
      <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1" data-toggle="modal" data-target="#modalTiketSoftware">
          <div class="card-icon bg-primary"><i class="fas fa-laptop-code"></i></div>
          <div class="card-wrap">
            <div class="card-header"><h4>IT Software</h4></div>
            <div class="card-body">Tiket</div>
          </div>
        </div>
      </div>

      <!-- Log Book -->
      <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1" data-toggle="modal" data-target="#modalLogBook">
          <div class="card-icon bg-success"><i class="fas fa-book"></i></div>
          <div class="card-wrap">
            <div class="card-header"><h4>Log Book</h4></div>
            <div class="card-body">Catatan</div>
          </div>
        </div>
      </div>
    </div>

   <!-- Baris Kedua -->
<div class="row">

  <!-- Dokumen -->
  <div class="col-lg-3 col-md-6 col-sm-6 col-12">
    <div class="card card-statistic-1" data-toggle="modal" data-target="#modalDokumen">
      <div class="card-icon bg-dark"><i class="fas fa-file-alt"></i></div>
      <div class="card-wrap">
        <div class="card-header"><h4>Dokumen</h4></div>
        <div class="card-body">Akreditasi</div>
      </div>
    </div>
  </div>

  <!-- Tiket Sarpras -->
  <div class="col-lg-3 col-md-6 col-sm-6 col-12">
    <div class="card card-statistic-1" data-toggle="modal" data-target="#modalTiketSarpras">
      <div class="card-icon bg-primary"><i class="fas fa-ticket-alt"></i></div>
      <div class="card-wrap">
        <div class="card-header"><h4>Tiket Sarpras</h4></div>
        <div class="card-body">Monitoring</div>
      </div>
    </div>
  </div>

</div>


  </section>
</div>



<!-- Modal Log Book -->
<div class="modal fade" id="modalLogBook" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document"><div class="modal-content">
    <div class="modal-header bg-success text-white">
      <h5 class="modal-title"><i class="fas fa-book"></i> Log Book - <?= htmlspecialchars($nama_user); ?></h5>
      <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="thead-light">
          <tr>
            <th>Tanggal</th><th>Judul</th><th>Isi</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if($dataLogBook): foreach($dataLogBook as $lb): ?>
          <tr>
            <td><?=date('d-m-Y H:i', strtotime($lb['tanggal']));?></td>
            <td><?=htmlspecialchars($lb['judul']);?></td>
            <td><?=nl2br(htmlspecialchars($lb['isi']));?></td>
            <td>
              <!-- gunakan ENT_QUOTES untuk judul, isi disimpan base64 agar aman -->
              <button class="btn btn-sm btn-primary"
                data-toggle="modal"
                data-target="#modalEditLogBook"
                data-id="<?=intval($lb['id']);?>"
                data-judul="<?=htmlspecialchars($lb['judul'], ENT_QUOTES);?>"
                data-isi="<?=base64_encode($lb['isi']);?>">
                Edit
              </button>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4" class="text-center">Belum ada catatan kerja</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>

<!-- Modal Edit Log Book -->
<div class="modal fade" id="modalEditLogBook" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document" style="max-width: 50%; height: 50vh;"> <!-- ✅ besar dan tinggi -->
    <div class="modal-content" style="height: 100%; display: flex; flex-direction: column;">
      
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Log Book</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <form method="POST" action="dashboard.php" style="flex: 1; display: flex; flex-direction: column;">
        <div class="modal-body" style="flex: 1; overflow-y: auto; padding-bottom: 20px;"> <!-- ✅ isi tinggi penuh -->
          <input type="hidden" name="id" id="edit_id">

          <div class="form-group">
            <label>Judul</label>
            <input type="text" class="form-control" name="judul" id="edit_judul" required>
          </div>

          <div class="form-group">
            <label>Isi</label>
            <textarea class="form-control" name="isi" id="edit_isi" rows="25" required style="min-height: 60vh;"></textarea> <!-- ✅ textarea tinggi -->
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="edit_logbook" class="btn btn-success">
            <i class="fas fa-save"></i> Simpan Perubahan
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Modal HRD -->
<div class="modal fade" id="modalHRD" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document"><div class="modal-content">
    <div class="modal-header bg-info text-white">
      <h5 class="modal-title"><i class="fas fa-users-cog"></i> Informasi Cuti <?= $tahun; ?> - <?= htmlspecialchars($nama_user); ?></h5>
      <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="thead-light"><tr><th>Jenis Cuti</th><th>Jatah</th><th>Terpakai</th><th>Sisa</th></tr></thead>
        <tbody>
        <?php if($dataCuti): foreach($dataCuti as $c): ?>
          <tr><td><?=htmlspecialchars($c['nama_cuti']);?></td><td><?=$c['lama_hari'];?> Hari</td><td><?=$c['terpakai'];?> Hari</td><td><?=$c['sisa_hari'];?> Hari</td></tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4" class="text-center">Belum ada data cuti</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>

<!-- Modal IT Hardware -->
<div class="modal fade" id="modalTiketHardware" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-desktop"></i> Tiket IT Hardware - Semua Data</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" style="overflow-x:auto;">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:50px;" class="text-center">No</th>
              <th>No. Tiket</th>
              <th>Kategori</th>
              <th>Kendala</th>
              <th>Status</th>
              <th>Waktu Order</th>
              <th>Validasi</th>
              <th>Teknisi</th>
            </tr>
          </thead>
          <tbody>
          <?php if($dataTiketHardware): $no=1; foreach($dataTiketHardware as $t): ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><?= htmlspecialchars($t['nomor_tiket']); ?></td>
              <td><?= htmlspecialchars($t['kategori']); ?></td>
              <td><?= htmlspecialchars($t['kendala']); ?></td>
              <td><?= htmlspecialchars($t['status']); ?></td>
              <td><?= date('d-m-Y H:i', strtotime($t['tanggal_input'])); ?></td>
              <td><?= htmlspecialchars($t['status_validasi']); ?></td>
              <td><?= htmlspecialchars($t['teknisi_nama'] ?? '-'); ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="8" class="text-center">Belum ada tiket IT Hardware</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>




<!-- Modal Dokumen -->
<div class="modal fade" id="modalDokumen" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="fas fa-file-alt"></i> Data Dokumen</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:50px;">No</th>
              <th>Judul</th>
              <th>Elemen Penilaian</th>
              <th>File</th>
            </tr>
          </thead>
          <tbody>
          <?php if($dataDokumen): $no=1; foreach($dataDokumen as $d): ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><?= htmlspecialchars($d['judul']); ?></td>
              <td><?= htmlspecialchars($d['elemen_penilaian'] ?? '-'); ?></td>
              <td>
                <?php if(!empty($d['file_path'])): ?>
                  <a href="<?= htmlspecialchars($d['file_path']); ?>" target="_blank">
                    <?= htmlspecialchars($d['file_name_original'] ?? 'Lihat File'); ?>
                  </a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="4" class="text-center">Belum ada dokumen</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>



<!-- Modal Tiket Sarpras -->
<div class="modal fade" id="modalTiketSarpras" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="fas fa-ticket-alt"></i> Data Tiket Sarpras</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:50px;">No</th>
              <th>Nomor Tiket</th>
              <th>Tgl Order</th>
              <th>Kategori</th>
              <th>Kendala / Laporan</th>
              <th>Status</th>
              <th>Teknisi</th>
              <th>Catatan Teknisi</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $queryTiket = mysqli_query($conn, "SELECT * FROM tiket_sarpras ORDER BY tanggal_input DESC");
          if(mysqli_num_rows($queryTiket) > 0):
              $no = 1;
              while($row = mysqli_fetch_assoc($queryTiket)):
          ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nomor_tiket']); ?></td>
              <td><?= $row['tanggal_input'] ? date('d-m-Y H:i', strtotime($row['tanggal_input'])) : '-'; ?></td>
              <td><?= htmlspecialchars($row['kategori']); ?></td>
              <td><?= nl2br(htmlspecialchars($row['kendala'])); ?></td>
              <td><?= htmlspecialchars($row['status']); ?></td>
              <td><?= !empty($row['teknisi_nama']) ? htmlspecialchars($row['teknisi_nama']) : '-'; ?></td>
              <td><?= !empty($row['catatan_it']) ? nl2br(htmlspecialchars($row['catatan_it'])) : '-'; ?></td>
            </tr>
          <?php
              endwhile;
          else:
          ?>
            <tr><td colspan="11" class="text-center">Belum ada tiket</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>



<!-- Modal IT Software -->
<div class="modal fade" id="modalTiketSoftware" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-laptop-code"></i> Tiket IT Software - Semua Data</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" style="overflow-x:auto;">
        <table class="table table-bordered table-sm">
          <thead class="thead-light">
            <tr>
              <th style="width:50px;" class="text-center">No</th>
              <th>No. Tiket</th>
              <th>Kategori</th>
              <th>Kendala</th>
              <th>Status</th>
              <th>Waktu Order</th>
              <th>Validasi</th>
              <th>Teknisi</th>
            </tr>
          </thead>
          <tbody>
          <?php if($dataTiketSoftware): $no=1; foreach($dataTiketSoftware as $t): ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><?= htmlspecialchars($t['nomor_tiket']); ?></td>
              <td><?= htmlspecialchars($t['kategori']); ?></td>
              <td><?= htmlspecialchars($t['kendala']); ?></td>
              <td><?= htmlspecialchars($t['status']); ?></td>
              <td><?= date('d-m-Y H:i', strtotime($t['tanggal_input'])); ?></td>
              <td><?= htmlspecialchars($t['status_validasi']); ?></td>
              <td><?= htmlspecialchars($t['teknisi_nama'] ?? '-'); ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="8" class="text-center">Belum ada tiket IT Software</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dis>




  <script src="assets/modules/jquery.min.js"></script>
  <script src="assets/modules/popper.js"></script>
  <script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
  <script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
  <script src="assets/modules/moment.min.js"></script>
  <script src="assets/js/stisla.js"></script>
  <script src="assets/js/scripts.js"></script>
  <script src="assets/js/custom.js"></script>

<script>
  // isi modal edit saat dibuka
  $('#modalEditLogBook').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    // judul di-escape di server side dengan ENT_QUOTES, bisa ambil via attr agar tidak ada auto parsing
    var judul = button.attr('data-judul') || '';
    var isi_b64 = button.attr('data-isi') || '';

    var isi = '';
    if (isi_b64) {
      try {
        // decode base64 dan handle UTF-8
        isi = decodeURIComponent(escape(window.atob(isi_b64)));
      } catch (e) {
        try { isi = window.atob(isi_b64); } catch (er) { isi = ''; }
      }
    }

    var modal = $(this);
    modal.find('#edit_id').val(id);
    modal.find('#edit_judul').val(judul);
    modal.find('#edit_isi').val(isi);
  });
</script>

</body>
</html>
