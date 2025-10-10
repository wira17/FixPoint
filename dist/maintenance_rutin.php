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

// Proses Simpan
if (isset($_POST['simpan'])) {
  $user_id = $_SESSION['user_id'];
  $barang_id = $_POST['barang_id'];
  $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
  $kondisi_fisik = isset($_POST['kondisi_fisik']) ? implode(", ", $_POST['kondisi_fisik']) : '';
  $fungsi_perangkat = isset($_POST['fungsi_perangkat']) ? implode(", ", $_POST['fungsi_perangkat']) : '';

   // Ambil nama teknisi dari user
  $get_user = mysqli_query($conn, "SELECT nama FROM users WHERE id = '$user_id' LIMIT 1");
  $nama_teknisi = ($get_user && mysqli_num_rows($get_user) > 0) ? mysqli_fetch_assoc($get_user)['nama'] : 'Tidak Diketahui';

  $query = "INSERT INTO maintanance_rutin 
            (user_id, nama_teknisi, barang_id, kondisi_fisik, fungsi_perangkat, catatan, waktu_input)
            VALUES 
            ('$user_id', '$nama_teknisi', '$barang_id', '$kondisi_fisik', '$fungsi_perangkat', '$catatan', NOW())";


  if (mysqli_query($conn, $query)) {
    $_SESSION['flash_message'] = "✅ Data maintanance berhasil disimpan.";
    echo "<script>location.href='maintenance_rutin.php';</script>";
    exit;
  } else {
    $error_message = mysqli_error($conn);
    $_SESSION['flash_message'] = "❌ Gagal menyimpan data: $error_message";
  }
}

$data_barang = mysqli_query($conn, "SELECT * FROM data_barang_it ORDER BY nama_barang ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>f.i.x.p.o.i.n.t</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <style>
    #notif-toast {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 9999;
      display: none;
      min-width: 300px;
    }
    .table-nowrap td, .table-nowrap th {
      white-space: nowrap;
    }

    .table-responsive {
  overflow-x: auto;
}


.text-success {
  color: green !important;
}
.text-warning {
  color: orange !important;
}
.text-danger {
  color: red !important;
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

          <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="notif-toast" class="alert alert-info text-center">
              <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
           <div class="card-header">
  <h4>
    Form Maintenance Rutin 
    <i class="fas fa-question-circle text-info ml-2" style="cursor: pointer;" data-toggle="modal" data-target="#infoModal"></i>
  </h4>
</div>

       <div class="card-body">
  <ul class="nav nav-tabs" id="tabMenu" role="tablist">
  <li class="nav-item">
    <a class="nav-link" id="form-tab" data-toggle="tab" href="#form" role="tab">Form Maintenance</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Maintenance</a>
  </li>
</ul>

  <div class="tab-content pt-3">
  <div class="tab-pane fade" id="form" role="tabpanel">
      <form method="POST">
        <div class="form-group">
          <label for="barang_id">Pilih Barang</label>
          <select name="barang_id" class="form-control" required>
            <option value="">-- Pilih Barang --</option>
            <?php mysqli_data_seek($data_barang, 0); ?>
            <?php while($row = mysqli_fetch_assoc($data_barang)): ?>
              <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_barang']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Kondisi Fisik</label>
          <div class="form-row">
            <?php
            $fisik = ['Bodi Utuh', 'Layar Jernih', 'Kabel Normal', 'port tidak rusak', 'label aset jelas', 'tidak ada komponen longgar'];
            foreach ($fisik as $f) {
              echo "<div class='form-check col-md-4'>
                      <input class='form-check-input' type='checkbox' name='kondisi_fisik[]' value='$f'>
                      <label class='form-check-label'>$f</label>
                    </div>";
            }
            ?>
          </div>
        </div>

        <div class="form-group">
          <label>Fungsi Perangkat</label>
          <div class="form-row">
            <?php
            $fungsi = ['Booting normal', 'koneksi stabil', 'Resoulisi oke', 'USB & Periperal terdeteksi', 'Performa responsif', 'Update OS dan Antivirus tersedia'];
            foreach ($fungsi as $f) {
              echo "<div class='form-check col-md-4'>
                      <input class='form-check-input' type='checkbox' name='fungsi_perangkat[]' value='$f'>
                      <label class='form-check-label'>$f</label>
                    </div>";
            }
            ?>
          </div>
        </div>

        <div class="form-group">
          <label for="catatan">Catatan Teknisi</label>
          <textarea name="catatan" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </form>
    </div>

<!-- DATA TAB -->
  <div class="tab-pane fade" id="data" role="tabpanel">

  <form method="GET" class="form-inline mb-3" onsubmit="return goToDataTab(this);">

  <div class="form-group mr-2">
    <label for="dari" class="mr-2">Dari</label>
    <input type="date" id="dari" name="dari" class="form-control" value="<?= $_GET['dari'] ?? '' ?>" required>
  </div>
  <div class="form-group mr-2">
    <label for="sampai" class="mr-2">Sampai</label>
    <input type="date" id="sampai" name="sampai" class="form-control" value="<?= $_GET['sampai'] ?? '' ?>" required>
  </div>

  <button type="submit" class="btn btn-primary btn-sm mr-2">
    <i class="fas fa-filter"></i> Filter
  </button>

  <?php if (!empty($_GET['dari']) && !empty($_GET['sampai'])): ?>
    <a href="rekap_maintenance_rutin.php?dari=<?= urlencode($_GET['dari']) ?>&sampai=<?= urlencode($_GET['sampai']) ?>" 
       target="_blank" class="btn btn-success btn-sm">
      <i class="fas fa-print"></i> Cetak
    </a>
  <?php endif; ?>

</form>


 <div class="table-responsive table-sm">
  <table class="table table-bordered table-hover table-sm text-nowrap" style="font-size: 13px;">
    <thead class="thead-light">
      <tr>
        <th style="width: 30px;">No</th>
        <th style="width: 50px; text-align:center;">Kartu</th>
        <th>Nama Barang</th>
        <th>Lokasi</th>
        <th>Kondisi Fisik</th>
        <th>Fungsi Perangkat</th>
        <th>Catatan</th>
        <th>Teknisi</th>
        <th>Waktu</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $where = "";
      if (isset($_GET['dari'], $_GET['sampai']) && $_GET['dari'] && $_GET['sampai']) {
        $dari = mysqli_real_escape_string($conn, $_GET['dari']);
        $sampai = mysqli_real_escape_string($conn, $_GET['sampai']);
        $where = "WHERE DATE(mr.waktu_input) BETWEEN '$dari' AND '$sampai'";
      }

      $limit = 6;
      $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
      if ($page < 1) $page = 1;
      $offset = ($page - 1) * $limit;

      $total_query = mysqli_query($conn, "SELECT COUNT(*) as total 
                                          FROM maintanance_rutin mr 
                                          JOIN data_barang_it db ON mr.barang_id = db.id 
                                          $where");
      $total_data = mysqli_fetch_assoc($total_query)['total'];
      $total_pages = ceil($total_data / $limit);

      $query = mysqli_query($conn, "SELECT mr.*, db.nama_barang, db.lokasi 
                                    FROM maintanance_rutin mr 
                                    JOIN data_barang_it db ON mr.barang_id = db.id 
                                    $where
                                    ORDER BY mr.waktu_input DESC
                                    LIMIT $limit OFFSET $offset");

      while ($row = mysqli_fetch_assoc($query)):
        $waktu_input = strtotime($row['waktu_input']);
        $now = time();
        $selisih_bulan = floor(($now - $waktu_input) / (30 * 24 * 60 * 60));

        if ($selisih_bulan < 1) {
          $status_text = 'Aman';
          $status_color = 'text-success font-weight-bold';
        } elseif ($selisih_bulan < 2) {
          $status_text = 'Persiapkan Maintenance';
          $status_color = 'text-warning font-weight-bold';
        } else {
          $status_text = 'Wajib Maintenance';
          $status_color = 'text-danger font-weight-bold';
        }
      ?>
        <tr>
          <td><?= $no++ ?></td>

          <!-- Kolom Kartu -->
          <td class="text-center">
            <a href="cetak_kartu_maintenance_it.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-light btn-sm" title="Cetak Kartu">
              <i class="fas fa-id-card text-primary"></i>
            </a>
          </td>

          <td><?= htmlspecialchars($row['nama_barang']) ?></td>
          <td><?= htmlspecialchars($row['lokasi']) ?></td>
          <td><?= htmlspecialchars($row['kondisi_fisik']) ?></td>
          <td><?= htmlspecialchars($row['fungsi_perangkat']) ?></td>
          <td><?= htmlspecialchars($row['catatan']) ?></td>
          <td><?= htmlspecialchars($row['nama_teknisi']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($row['waktu_input'])) ?></td>
          <td class="<?= $status_color ?>"><?= $status_text ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <?php if ($total_pages > 1): ?>
  <nav>
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?dari=<?= $_GET['dari'] ?? '' ?>&sampai=<?= $_GET['sampai'] ?? '' ?>&page=<?= $page-1 ?>#data">Prev</a>
        </li>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
          <a class="page-link" href="?dari=<?= $_GET['dari'] ?? '' ?>&sampai=<?= $_GET['sampai'] ?? '' ?>&page=<?= $i ?>#data"><?= $i ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <li class="page-item">
          <a class="page-link" href="?dari=<?= $_GET['dari'] ?? '' ?>&sampai=<?= $_GET['sampai'] ?? '' ?>&page=<?= $page+1 ?>#data">Next</a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>

</div>



        </div>
      </section>
    </div>
  </div>
</div>

<!-- Modal Penjelasan Warna -->
<div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="infoModalLabel"><i class="fas fa-info-circle"></i> Penjelasan Warna Status</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="font-size: 14px;">
        <p><span class="text-success font-weight-bold">Hijau (Aman)</span> — Maintenance terakhir kurang dari 1 bulan yang lalu.</p>
        <p><span class="text-warning font-weight-bold">Oranye (Persiapkan Maintenance)</span> — Maintenance terakhir antara 1 hingga 2 bulan yang lalu.</p>
        <p><span class="text-danger font-weight-bold">Merah (Wajib Maintenance)</span> — Maintenance terakhir lebih dari 2 bulan yang lalu.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
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
  $(document).ready(function () {
    // Aktifkan toast
    var toast = $('#notif-toast');
    if (toast.length) {
      toast.fadeIn(300).delay(2000).fadeOut(500);
    }

    // Pulihkan tab aktif dari localStorage
    var activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
      $('#tabMenu a[href="' + activeTab + '"]').tab('show');
    }

    // Simpan tab yang diklik
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      localStorage.setItem('activeTab', $(e.target).attr('href'));
    });
  });
</script>
<script>
  // Simpan tab ke localStorage saat diklik
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    localStorage.setItem('activeTab', $(e.target).attr('href'));
  });

  // Aktifkan tab dari localStorage atau #hash URL
  $(document).ready(function () {
    var hash = window.location.hash;
    var activeTab = hash || localStorage.getItem('activeTab');
    if (activeTab) {
      $('#tabMenu a[href="' + activeTab + '"]').tab('show');
    }

    var toast = $('#notif-toast');
    if (toast.length) {
      toast.fadeIn(300).delay(2000).fadeOut(500);
    }
  });

  // Tambahkan #data ke URL saat klik tombol Filter
  function goToDataTab(form) {
    const url = new URL(window.location.href.split('#')[0]); // hilangkan #hash
    const formData = new FormData(form);

    for (let [key, value] of formData.entries()) {
      url.searchParams.set(key, value);
    }

    // Tambahkan anchor tab
    window.location.href = url.toString() + '#data';
    return false; // cegah form submit default
  }
</script>


</body>
</html>
