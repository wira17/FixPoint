<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// === Query semua tabel ===
$semua_antrian = mysqli_query($conn, "SELECT * FROM semua_antrian ORDER BY tahun DESC, bulan DESC");
$poli_antrian = mysqli_query($conn, "SELECT * FROM poli_antrian ORDER BY tahun DESC, bulan DESC");
$satu_sehat = mysqli_query($conn, "SELECT * FROM satu_sehat ORDER BY tahun DESC, bulan DESC");
$maintanance_rutin = mysqli_query($conn, "SELECT * FROM maintanance_rutin ORDER BY waktu_input DESC");
$progres_kerja = mysqli_query($conn, "SELECT * FROM progres_kerja ORDER BY tahun DESC, bulan DESC");
$berita_acara = mysqli_query($conn, "SELECT * FROM berita_acara_hardware ORDER BY tanggal DESC");
$data_erm = mysqli_query($conn, "SELECT * FROM data_erm ORDER BY tahun DESC, bulan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Slide Pelaporan</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .container {
      margin-top: 30px;
    }
    h2 {
      font-weight: 600;
      color: #0d6efd;
      text-align: center;
      margin-bottom: 20px;
    }
    table {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    th {
      background-color: #0d6efd;
      color: white;
      text-align: center;
    }
    td {
      text-align: center;
      vertical-align: middle;
    }
    .carousel {
      position: relative;
    }
    .carousel-control-prev,
    .carousel-control-next {
      width: 5%;
    }
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
      filter: invert(1);
      background-color: rgba(0, 0, 0, 0.3);
      border-radius: 50%;
      padding: 10px;
    }
    .slide-content {
      padding: 20px;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>📊 Slide Pelaporan</h2>

  <div id="reportCarousel" class="carousel slide" data-bs-ride="false">
    <div class="carousel-inner">

      <!-- === Slide 1: Semua Antrian === -->
      <div class="carousel-item active">
        <div class="slide-content">
          <h4 class="text-center mb-3">📋 Data Semua Antrian</h4>
          <table class="table table-bordered table-striped table-hover">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>ID Perusahaan</th>
                <th>Jumlah SEP</th>
                <th>Jumlah Antri</th>
                <th>Jumlah MJKN</th>
                <th>% Semua</th>
                <th>% MJKN</th>
                <th>Petugas Input</th>
                <th>Tanggal Input</th>
                <th>Bulan</th>
                <th>Tahun</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($semua_antrian)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['id_perusahaan']}</td>
                          <td>{$r['jumlah_sep']}</td>
                          <td>{$r['jumlah_antri']}</td>
                          <td>{$r['jumlah_mjkn']}</td>
                          <td>{$r['persen_all']}%</td>
                          <td>{$r['persen_mjkn']}%</td>
                          <td>{$r['petugas_input']}</td>
                          <td>{$r['tanggal_input']}</td>
                          <td>{$r['bulan']}</td>
                          <td>{$r['tahun']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- === Slide 2: Poli Antrian === -->
      <div class="carousel-item">
        <div class="slide-content">
          <h4 class="text-center mb-3">🏥 Data Poli Antrian</h4>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>ID Poli</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Jumlah SEP</th>
                <th>Jumlah Antri</th>
                <th>Jumlah MJKN</th>
                <th>% Semua</th>
                <th>% MJKN</th>
                <th>Petugas Input</th>
                <th>Tanggal Input</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($poli_antrian)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['id_poli']}</td>
                          <td>{$r['bulan']}</td>
                          <td>{$r['tahun']}</td>
                          <td>{$r['jumlah_sep']}</td>
                          <td>{$r['jumlah_antri']}</td>
                          <td>{$r['jumlah_mjkn']}</td>
                          <td>{$r['persen_all']}%</td>
                          <td>{$r['persen_mjkn']}%</td>
                          <td>{$r['petugas_input']}</td>
                          <td>{$r['tanggal_input']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>


 <!-- === Slide 3: Satu Sehat === -->
      <div class="carousel-item">
        <div class="slide-content">
          <h4 class="text-center mb-3">🩺 Data Satu Sehat</h4>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Endpoint</th>
                <th>Jumlah</th>
                <th>Petugas Input</th>
                <th>Tanggal Input</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($satu_sehat)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['bulan']}</td>
                          <td>{$r['tahun']}</td>
                          <td>{$r['endpoint']}</td>
                          <td>{$r['jumlah']}</td>
                          <td>{$r['petugas_input']}</td>
                          <td>{$r['tanggal_input']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- === Slide 4: Maintanance Rutin === -->
      <div class="carousel-item">
        <div class="slide-content">
          <h4 class="text-center mb-3">🧰 Data Maintanance Rutin</h4>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>Barang ID</th>
                <th>User ID</th>
                <th>Nama Teknisi</th>
                <th>Waktu Input</th>
                <th>Kondisi Fisik</th>
                <th>Fungsi Perangkat</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($maintanance_rutin)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['barang_id']}</td>
                          <td>{$r['user_id']}</td>
                          <td>{$r['nama_teknisi']}</td>
                          <td>{$r['waktu_input']}</td>
                          <td>{$r['kondisi_fisik']}</td>
                          <td>{$r['fungsi_perangkat']}</td>
                          <td>{$r['catatan']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- === Slide 5: Progres Kerja === -->
      <div class="carousel-item">
        <div class="slide-content">
          <h4 class="text-center mb-3">📈 Data Progres Kerja</h4>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Progres</th>
                <th>Petugas Input</th>
                <th>Tanggal Input</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($progres_kerja)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['bulan']}</td>
                          <td>{$r['tahun']}</td>
                          <td>{$r['progres']}</td>
                          <td>{$r['petugas_input']}</td>
                          <td>{$r['tanggal_input']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- === Slide 6: Berita Acara Hardware === -->
      <div class="carousel-item">
        <div class="slide-content">
          <h4 class="text-center mb-3">🧾 Data Berita Acara Hardware</h4>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>Nomor Tiket</th>
                <th>Nomor BA</th>
                <th>Tanggal</th>
                <th>Nama Pelapor</th>
                <th>Unit Kerja</th>
                <th>Kategori</th>
                <th>Kendala</th>
                <th>Teknisi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($berita_acara)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['nomor_tiket']}</td>
                          <td>{$r['nomor_ba']}</td>
                          <td>{$r['tanggal']}</td>
                          <td>{$r['nama_pelapor']}</td>
                          <td>{$r['unit_kerja']}</td>
                          <td>{$r['kategori']}</td>
                          <td>{$r['kendala']}</td>
                          <td>{$r['teknisi']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- === Slide 7: Data ERM === -->
      <div class="carousel-item">
        <div class="slide-content">
          <h4 class="text-center mb-3">💻 Data ERM</h4>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>ID</th>
                <th>ID Unit</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Menu ERM</th>
                <th>Petugas Input</th>
                <th>Tanggal Input</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($r = mysqli_fetch_assoc($data_erm)) {
                  echo "<tr>
                          <td>{$no}</td>
                          <td>{$r['id']}</td>
                          <td>{$r['id_unit']}</td>
                          <td>{$r['bulan']}</td>
                          <td>{$r['tahun']}</td>
                          <td>{$r['menu_erm']}</td>
                          <td>{$r['petugas_input']}</td>
                          <td>{$r['tanggal_input']}</td>
                        </tr>";
                  $no++;
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
      <!-- === Tambahkan slide lainnya di bawah seperti yang kamu punya === -->
      <!-- (Satu Sehat, Maintanance Rutin, Progres Kerja, Berita Acara, Data ERM) -->

    </div>

    <!-- Tombol navigasi -->
    <button class="carousel-control-prev" type="button" data-bs-target="#reportCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#reportCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
