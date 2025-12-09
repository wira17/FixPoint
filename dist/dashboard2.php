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


// Data Maintenance Rutin
$sqlMaintenance = "SELECT mr.id, mr.barang_id, mr.user_id, mr.nama_teknisi, mr.waktu_input, 
                          mr.kondisi_fisik, mr.fungsi_perangkat, mr.catatan,
                          b.nama_barang, b.no_barang, u.nama AS nama_user
                   FROM maintanance_rutin mr
                   LEFT JOIN data_barang_it b ON mr.barang_id = b.id
                   LEFT JOIN users u ON mr.user_id = u.id
                   ORDER BY mr.waktu_input DESC";
$resultMaintenance = $conn->query($sqlMaintenance);
$dataMaintenance = [];
if ($resultMaintenance) {
    while($row = $resultMaintenance->fetch_assoc()){ 
        $dataMaintenance[] = $row; 
    }
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

// Data Pengguna
$sqlUsers = "SELECT id, nik, nama, jabatan, unit_kerja, email, no_hp, status, created_at, last_login 
             FROM users ORDER BY created_at DESC";
$resultUsers = $conn->query($sqlUsers);
$dataUsers = [];
if ($resultUsers) {
    while($row = $resultUsers->fetch_assoc()){ 
        $dataUsers[] = $row; 
    }
}


// Data Arsip Digital
$sqlArsip = "SELECT id, judul, kategori, file_path, file_name_original, petugas_input, tanggal_input 
             FROM arsip_digital ORDER BY tanggal_input DESC";
$resultArsip = $conn->query($sqlArsip);
$dataArsip = [];
if ($resultArsip) {
    while($row = $resultArsip->fetch_assoc()){ 
        $dataArsip[] = $row; 
    }
}

// Data Surat Masuk
$sqlSurat = "SELECT id, no_surat, tgl_surat, tgl_terima, pengirim, asal_surat, perihal, 
                    lampiran, jenis_surat, sifat_surat, perlu_balasan, status_balasan, 
                    disposisi_ke, catatan, file_surat, waktu_input 
             FROM surat_masuk ORDER BY tgl_terima DESC";
$resultSurat = $conn->query($sqlSurat);
$dataSurat = [];
if ($resultSurat) {
    while($row = $resultSurat->fetch_assoc()){ 
        $dataSurat[] = $row; 
    }
}


// Data Agenda Direktur
$sqlAgenda = "SELECT a.*, u.nama AS nama_user 
              FROM agenda_direktur a
              LEFT JOIN users u ON a.user_input = u.id
              ORDER BY a.tanggal DESC, a.jam DESC";
$resultAgenda = $conn->query($sqlAgenda);
$dataAgenda = [];
if ($resultAgenda) {
    while($row = $resultAgenda->fetch_assoc()){ 
        $dataAgenda[] = $row; 
    }
}


// Data Surat Keluar
$sqlSuratKeluar = "SELECT id, no_surat, tgl_surat, tujuan, perihal, 
                          lampiran, jenis_surat, sifat_surat, status_pengiriman, 
                          catatan, file_surat, waktu_input 
                   FROM surat_keluar ORDER BY tgl_surat DESC";
$resultSuratKeluar = $conn->query($sqlSuratKeluar);
$dataSuratKeluar = [];
if ($resultSuratKeluar) {
    while($row = $resultSuratKeluar->fetch_assoc()){ 
        $dataSuratKeluar[] = $row; 
    }
}


// Data Barang IT
$sqlBarangIT = "SELECT b.*, u.nama AS nama_user 
                FROM data_barang_it b
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY b.waktu_input DESC";
$resultBarangIT = $conn->query($sqlBarangIT);
$dataBarangIT = [];
if ($resultBarangIT) {
    while($row = $resultBarangIT->fetch_assoc()){ 
        $dataBarangIT[] = $row; 
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


$sqlLB = "SELECT ck.id, ck.judul, ck.isi, ck.tanggal, u.nama 
          FROM catatan_kerja ck
          JOIN users u ON ck.user_id = u.id
          ORDER BY ck.tanggal DESC";

$resultLB = $conn->query($sqlLB);
$dataLogBook = [];
if ($resultLB) {
    while($row = $resultLB->fetch_assoc()){ $dataLogBook[] = $row; }
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
      <h1>Dashboard Direktur</h1>
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
            <div class="card-header"><h4>Tiket</h4></div>
            <div class="card-body">IT Hardware</div>
          </div>
        </div>
      </div>

      <!-- IT Software -->
      <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1" data-toggle="modal" data-target="#modalTiketSoftware">
          <div class="card-icon bg-primary"><i class="fas fa-laptop-code"></i></div>
          <div class="card-wrap">
            <div class="card-header"><h4>Tiket</h4></div>
            <div class="card-body">IT Software</div>
          </div>
        </div>
      </div>

      <!-- Log Book -->
      <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1" data-toggle="modal" data-target="#modalLogBook">
          <div class="card-icon bg-success"><i class="fas fa-book"></i></div>
          <div class="card-wrap">
            <div class="card-header"><h4>Log Book</h4></div>
            <div class="card-body">Karyawan</div>
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

  <!-- Pengguna -->
  <div class="col-lg-3 col-md-6 col-sm-6 col-12">
    <div class="card card-statistic-1" data-toggle="modal" data-target="#modalUsers">
      <div class="card-icon bg-secondary"><i class="fas fa-user"></i></div>
      <div class="card-wrap">
        <div class="card-header"><h4>Pengguna</h4></div>
        <div class="card-body">Data Users</div>
      </div>
    </div>
  </div>

  <!-- Surat Masuk -->
  <div class="col-lg-3 col-md-6 col-sm-6 col-12">
    <div class="card card-statistic-1" data-toggle="modal" data-target="#modalSuratMasuk">
      <div class="card-icon bg-info"><i class="fas fa-envelope"></i></div>
      <div class="card-wrap">
        <div class="card-header"><h4>Surat Masuk</h4></div>
        <div class="card-body">Arsip Surat</div>
      </div>
    </div>
  </div>

<!-- Surat Keluar -->
<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalSuratKeluar">
    <div class="card-icon bg-danger"><i class="fas fa-paper-plane"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Surat Keluar</h4></div>
      <div class="card-body">Arsip Surat</div>
    </div>
  </div>
</div>

<!-- Baris Ketiga -->

<!-- Agenda Direktur -->
<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalAgenda">
    <div class="card-icon bg-primary"><i class="fas fa-calendar-alt"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Agenda Direktur</h4></div>
      <div class="card-body">Kegiatan</div>
    </div>
  </div>
</div>

<!-- Data Barang IT -->
<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalBarangIT">
    <div class="card-icon bg-warning"><i class="fas fa-hdd"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Data Barang IT</h4></div>
      <div class="card-body">Inventaris</div>
    </div>
  </div>
</div>

<!-- Maintenance Rutin -->
<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalMaintenance">
    <div class="card-icon bg-primary"><i class="fas fa-tools"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Maintenance</h4></div>
      <div class="card-body">Rutin</div>
    </div>
  </div>
</div>

<!-- Arsip Digital -->
<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalArsipDigital">
    <div class="card-icon bg-success"><i class="fas fa-folder-open"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Arsip Digital</h4></div>
      <div class="card-body">Dokumen</div>
    </div>
  </div>
</div>


<!-- Baris Keempat -->
<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalTiketSarpras">
    <div class="card-icon bg-primary"><i class="fas fa-snowflake"></i></div>
    <div class="card-wrap">
      <div class="card-header"><h4>Tiket Sarpras</h4></div>
      <div class="card-body">Monitoring AC</div>
    </div>
  </div>
</div>


<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalSemuaAntrian">
    <div class="card-icon bg-primary">
      <i class="fas fa-hospital-symbol"></i>
    </div>
    <div class="card-wrap">
      <div class="card-header"><h4>Bridging BPJS</h4></div>
      <div class="card-body">% Semua Antrian</div>
    </div>
  </div>
</div>

<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1" data-toggle="modal" data-target="#modalIzinKeluar">
    <div class="card-icon bg-primary">
      <i class="fas fa-door-open"></i>
    </div>
    <div class="card-wrap">
      <div class="card-header"><h4>Izin Keluar</h4></div>
      <div class="card-body">Karyawan</div>
    </div>
  </div>
</div>





  </section>
</div>

<!-- Modal Data Barang IT -->
<div class="modal fade" id="modalBarangIT" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">

      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="fas fa-hdd"></i> Data Barang IT</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>No Barang</th>
              <th>Nama Barang</th>
              <th>Kategori</th>
              <th>Merk</th>
              <th>Spesifikasi</th>
              <th>Lokasi</th>
              <th>Kondisi</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataBarangIT): $no=1; foreach($dataBarangIT as $brg): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($brg['no_barang'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($brg['nama_barang'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($brg['kategori'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($brg['merk'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($brg['spesifikasi'] ?? '-'); ?></td>]
                <td><?= htmlspecialchars($brg['lokasi'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($brg['kondisi'] ?? '-'); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="11" class="text-center text-muted">Belum ada data barang IT</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>

    </div>
  </div>
</div>


<!-- Modal Maintenance Rutin -->
<div class="modal fade" id="modalMaintenance" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      
   <div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fas fa-wrench"></i> Data Maintenance Rutin
    </h5>
    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
</div>


      
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th>No</th>
              <th>No Barang</th>
              <th>Barang</th>
              <th>Teknisi</th>
              <th>Kondisi Fisik</th>
              <th>Fungsi Perangkat</th>
              <th>Catatan</th>
              <th>Waktu Maintanance</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataMaintenance): $no=1; foreach($dataMaintenance as $m): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($m['no_barang'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($m['nama_barang'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($m['nama_teknisi'] ?? '-'); ?></td>
                <td><?= nl2br(htmlspecialchars($m['kondisi_fisik'] ?? '-')); ?></td>
                <td><?= nl2br(htmlspecialchars($m['fungsi_perangkat'] ?? '-')); ?></td>
                <td><?= nl2br(htmlspecialchars($m['catatan'] ?? '-')); ?></td>
                <td><?= date('d-m-Y H:i', strtotime($m['waktu_input'])); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted">Belum ada data maintenance rutin</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
      
    </div>
  </div>
</div>


<!-- Modal Log Book -->
<div class="modal fade" id="modalLogBook" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="fas fa-book"></i> Log Book - Semua Karyawan
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      
      <!-- Body -->
      <div class="modal-body">

        <!-- 🔎 Pencarian Nama Karyawan -->
        <div class="form-group">
          <label><i class="fas fa-search"></i> Cari Nama Karyawan:</label>
          <input type="text" id="cariKaryawan" class="form-control" placeholder="Ketik nama karyawan...">
        </div>

        <div class="table-responsive mt-3">
          <table class="table table-bordered table-hover table-sm mb-0" id="tabelLogBook">
            <thead class="thead-light text-center">
              <tr>
                <th style="width:50px;">No</th>
                <th style="width:150px;">Tanggal</th>
                <th style="width:180px;">Nama Karyawan</th>
                <th style="width:220px;">Judul</th>
                <th>Isi</th>
              </tr>
            </thead>
            <tbody>
              <?php if($dataLogBook): $no=1; foreach($dataLogBook as $lb): ?>
                <tr>
                  <td class="text-center align-middle"><?= $no++; ?></td>
                  <td class="text-center align-middle" style="white-space:nowrap;">
                    <?= date('d-m-Y H:i', strtotime($lb['tanggal'])); ?>
                  </td>
                  <td class="align-middle"><?= htmlspecialchars($lb['nama']); ?></td>
                  <td class="align-middle font-weight-bold"><?= htmlspecialchars($lb['judul']); ?></td>
                  <td style="text-align:justify;"><?= nl2br(htmlspecialchars($lb['isi'])); ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr>
                  <td colspan="5" class="text-center text-muted">Belum ada catatan kerja</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
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

<!-- Modal Semua Antrian -->
<div class="modal fade" id="modalSemuaAntrian" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-chart-line"></i> Data % Semua Antrian</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body table-responsive">
        <table class="table table-bordered table-sm table-striped text-center">
          <thead class="thead-light">
            <tr>
              <th style="width:50px;">No</th>

              <th>Bulan</th>

              <th>Tahun</th>
              <th>Jumlah SEP</th>
              <th>Jumlah Antrian</th>
              <th>Jumlah MJKN</th>
              <th>% All Pemanfaatan</th>
            </tr>
          </thead>
          <tbody>
          <?php
          // Fungsi ubah angka bulan ke nama bulan Indonesia
          function namaBulan($angka) {
              $bulan = [
                  1 => 'Januari',
                  2 => 'Februari',
                  3 => 'Maret',
                  4 => 'April',
                  5 => 'Mei',
                  6 => 'Juni',
                  7 => 'Juli',
                  8 => 'Agustus',
                  9 => 'September',
                  10 => 'Oktober',
                  11 => 'November',
                  12 => 'Desember'
              ];
              return isset($bulan[$angka]) ? $bulan[$angka] : '-';
          }

          // Urut berdasarkan tahun ASC, lalu bulan ASC (Januari dulu)
          $query = mysqli_query($conn, "SELECT * FROM semua_antrian ORDER BY tahun ASC, bulan ASC");

          if (mysqli_num_rows($query) > 0):
              $no = 1;
              while ($row = mysqli_fetch_assoc($query)):
          ?>
            <tr>
              <td><?= $no++; ?></td>

              <td><?= namaBulan((int)$row['bulan']); ?></td>

              <td><?= htmlspecialchars($row['tahun']); ?></td>
              <td><?= number_format($row['jumlah_sep']); ?></td>
              <td><?= number_format($row['jumlah_antri']); ?></td>
              <td><?= number_format($row['jumlah_mjkn']); ?></td>
              <td class="font-weight-bold text-success"><?= number_format($row['persen_all'], 2); ?>%</td>
            </tr>
          <?php
              endwhile;
          else:
          ?>
            <tr><td colspan="7">Belum ada data semua antrian</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>


<!-- Modal Data Izin Keluar Hari Ini -->
<div class="modal fade" id="modalIzinKeluar" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-door-open"></i> Data Izin Keluar Pegawai (Hari Ini)</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body table-responsive">
        <table class="table table-bordered table-sm table-striped text-center">
          <thead class="thead-light">
            <tr>
              <th style="width:50px;">No</th>
              <th>Nama</th>
              <th>Bagian</th>
              <th>Tanggal</th>
              <th>Jam Keluar</th>
              <th>Jam Kembali</th>
              <th>Keperluan</th>
              <th>Status Atasan</th>
              <th>Status SDM</th>
              <th>Lama</th>
            </tr>
          </thead>
          <tbody>
          <?php
          date_default_timezone_set('Asia/Jakarta');
          $hari_ini = date('Y-m-d');

          $query = mysqli_query($conn, "
            SELECT * FROM izin_keluar 
            WHERE tanggal = '$hari_ini' 
            ORDER BY created_at DESC
          ");

          if (mysqli_num_rows($query) > 0):
              $no = 1;
              while ($row = mysqli_fetch_assoc($query)):
                  $lama = "-";
                  $class_lama = "";
                  if (!empty($row['jam_keluar']) && !empty($row['jam_kembali_real'])) {
                      $waktu_keluar  = strtotime($row['tanggal'].' '.$row['jam_keluar']);
                      $waktu_kembali = strtotime($row['jam_kembali_real']);
                      if ($waktu_kembali > $waktu_keluar) {
                          $selisih = $waktu_kembali - $waktu_keluar;
                          $jam = floor($selisih / 3600);
                          $menit = floor(($selisih % 3600) / 60);
                          $lama = sprintf("%02d jam %02d menit", $jam, $menit);
                          if ($selisih > 3600) $class_lama = "text-danger font-weight-bold";
                      }
                  }

                  $badgeAtasan = ($row['status_atasan'] == 'disetujui') ? 'success' :
                                 (($row['status_atasan'] == 'ditolak') ? 'danger' : 'secondary');
                  $badgeSdm    = ($row['status_sdm'] == 'disetujui') ? 'success' :
                                 (($row['status_sdm'] == 'ditolak') ? 'danger' : 'secondary');
          ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama']); ?></td>
              <td><?= htmlspecialchars($row['bagian']); ?></td>
              <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
              <td><?= htmlspecialchars($row['jam_keluar']); ?></td>
              <td><?= $row['jam_kembali_real'] ?: '-'; ?></td>
              <td><?= htmlspecialchars($row['keperluan']); ?></td>
              <td>
                <span class="badge badge-<?= $badgeAtasan; ?>">
                  <?= ucfirst($row['status_atasan']); ?>
                </span><br>
                <small><?= $row['waktu_acc_atasan'] ? date('d-m-Y H:i', strtotime($row['waktu_acc_atasan'])) : '-'; ?></small>
              </td>
              <td>
                <span class="badge badge-<?= $badgeSdm; ?>">
                  <?= ucfirst($row['status_sdm']); ?>
                </span><br>
                <small><?= $row['waktu_acc_sdm'] ? date('d-m-Y H:i', strtotime($row['waktu_acc_sdm'])) : '-'; ?></small>
              </td>
              <td class="<?= $class_lama; ?>"><?= $lama; ?></td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="11">Belum ada data izin keluar untuk hari ini (<?= date('d-m-Y'); ?>).</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>








<!-- Modal Surat Masuk -->
<div class="modal fade" id="modalSuratMasuk" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-envelope"></i> Data Surat Masuk</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>No Surat</th>
              <th>Tgl Surat</th>
              <th>Tgl Terima</th>
              <th>Pengirim</th>
              <th>Asal Surat</th>
              <th>Perihal</th>
              <th>Lampiran</th>
              <th>Jenis</th>
              <th>Sifat</th>
              <th>Perlu Balasan</th>
              <th>Status Balasan</th>
              <th>Disposisi Ke</th>
              <th>Catatan</th>
              <th>File</th>
              <th>Waktu Input</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataSurat): $no=1; foreach($dataSurat as $s): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($s['no_surat']); ?></td>
                <td><?= date('d-m-Y', strtotime($s['tgl_surat'])); ?></td>
                <td><?= date('d-m-Y', strtotime($s['tgl_terima'])); ?></td>
                <td><?= htmlspecialchars($s['pengirim']); ?></td>
                <td><?= htmlspecialchars($s['asal_surat'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($s['perihal'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($s['lampiran'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($s['jenis_surat'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($s['sifat_surat'] ?? '-'); ?></td>
                <td class="text-center">
                  <?= $s['perlu_balasan'] === 'Ya' ? '<span class="badge badge-danger">Ya</span>' : '<span class="badge badge-success">Tidak</span>'; ?>
                </td>
                <td><?= htmlspecialchars($s['status_balasan']); ?></td>
                <td><?= htmlspecialchars($s['disposisi_ke'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($s['catatan'] ?? '-'); ?></td>
                <td>
                  <?php if($s['file_surat']): ?>
                    <a href="uploads/<?= htmlspecialchars($s['file_surat']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?= date('d-m-Y H:i', strtotime($s['waktu_input'])); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="16" class="text-center text-muted">Belum ada surat masuk</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
      
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


<!-- Modal Arsip Digital -->
<div class="modal fade" id="modalArsipDigital" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-folder-open"></i> Arsip Digital</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>Judul</th>
              <th>Kategori</th>
              <th>Petugas Input</th>
              <th>Tanggal Input</th>
              <th>File</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataArsip): $no=1; foreach($dataArsip as $ad): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($ad['judul']); ?></td>
                <td><?= htmlspecialchars($ad['kategori'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($ad['petugas_input'] ?? '-'); ?></td>
                <td><?= date('d-m-Y H:i', strtotime($ad['tanggal_input'])); ?></td>
                <td>
                  <?php if($ad['file_path']): ?>
                    <a href="uploads/<?= htmlspecialchars($ad['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada arsip digital</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
      
    </div>
  </div>
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

<!-- Modal Surat Keluar -->
<div class="modal fade" id="modalSuratKeluar" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Data Surat Keluar</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>No Surat</th>
              <th>Tgl Surat</th>
              <th>Tujuan</th>
              <th>Perihal</th>
              <th>Lampiran</th>
              <th>Jenis</th>
              <th>Sifat</th>
              <th>Status</th>
              <th>Catatan</th>
              <th>File</th>
              <th>Waktu Input</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataSuratKeluar): $no=1; foreach($dataSuratKeluar as $sk): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($sk['no_surat']); ?></td>
                <td><?= date('d-m-Y', strtotime($sk['tgl_surat'])); ?></td>
                <td><?= htmlspecialchars($sk['tujuan']); ?></td>
                <td><?= htmlspecialchars($sk['perihal'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($sk['lampiran'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($sk['jenis_surat'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($sk['sifat_surat'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($sk['status_pengiriman'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($sk['catatan'] ?? '-'); ?></td>
                <td>
                  <?php if($sk['file_surat']): ?>
                    <a href="uploads/<?= htmlspecialchars($sk['file_surat']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?= date('d-m-Y H:i', strtotime($sk['waktu_input'])); ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="12" class="text-center text-muted">Belum ada surat keluar</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
      
    </div>
  </div>
</div>

<!-- Modal Agenda Direktur -->
<div class="modal fade" id="modalAgenda" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Agenda Direktur</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>Judul</th>
              <th>Keterangan</th>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>File Pendukung</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataAgenda): $no=1; foreach($dataAgenda as $ag): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($ag['judul']); ?></td>
                <td><?= nl2br(htmlspecialchars($ag['keterangan'])); ?></td>
                <td><?= date('d-m-Y', strtotime($ag['tanggal'])); ?></td>
                <td><?= $ag['jam'] ? date('H:i', strtotime($ag['jam'])) : '-'; ?></td>
                <td>
                  <?php if($ag['file_pendukung']): ?>
                    <a href="uploads/<?= htmlspecialchars($ag['file_pendukung']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">Belum ada agenda direktur</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
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

<!-- Modal Data Pengguna -->
<div class="modal fade" id="modalUsers" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xxl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="fas fa-user"></i> Data Pengguna</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th style="width:50px;">No</th>
              <th>NIK</th>
              <th>Nama</th>
              <th>Jabatan</th>
              <th>Unit Kerja</th>
              <th>Email</th>
              <th>No. HP</th>
              <th>Status</th>
              <th>Mendaftar</th>
              <th>Login Trakhir</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataUsers): $no=1; foreach($dataUsers as $u): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($u['nik']); ?></td>
                <td><?= htmlspecialchars($u['nama']); ?></td>
                <td><?= htmlspecialchars($u['jabatan'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($u['unit_kerja'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($u['email']); ?></td>
                <td><?= htmlspecialchars($u['no_hp'] ?? '-'); ?></td>
                <td class="text-center">
                  <?php if($u['status'] === 'active'): ?>
                    <span class="badge badge-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge badge-warning">Pending</span>
                  <?php endif; ?>
                </td>
                <td><?= date('d-m-Y H:i', strtotime($u['created_at'])); ?></td>
                <td><?= $u['last_login'] ? date('d-m-Y H:i', strtotime($u['last_login'])) : '-'; ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr>
                <td colspan="10" class="text-center text-muted">Belum ada data pengguna</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
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

  <!-- 🔧 Script pencarian -->
<script>
document.getElementById("cariKaryawan").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#tabelLogBook tbody tr");
  
  rows.forEach(row => {
    let nama = row.cells[2]?.textContent.toLowerCase() || "";
    row.style.display = nama.includes(filter) ? "" : "none";
  });
});
</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
