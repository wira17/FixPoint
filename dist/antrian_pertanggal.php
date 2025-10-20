<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];

// Fungsi format tanggal Indonesia
function formatTanggalIndo($tanggal) {
    $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1] - 1] . ' ' . $pecah[0];
}

$notif = '';
$filter_tanggal_awal = isset($_POST['filter_awal']) ? $_POST['filter_awal'] : '';
$filter_tanggal_akhir = isset($_POST['filter_akhir']) ? $_POST['filter_akhir'] : '';

// Simpan data
if (isset($_POST['simpan'])) {
    $tanggal            = $_POST['tanggal'];
    $sep_terbit         = (int)$_POST['sep_terbit'];
    $total_terkirim     = (int)$_POST['total_terkirim'];
    $total_gagal        = (int)$_POST['total_gagal'];
    $mjkn_selesai       = (int)$_POST['mjkn_selesai'];
    $mjkn_belum         = (int)$_POST['mjkn_belum'];
    $jkn_selesai        = (int)$_POST['jkn_selesai'];
    $jkn_belum          = (int)$_POST['jkn_belum'];
    $nonjkn_selesai     = (int)$_POST['nonjkn_selesai'];
    $nonjkn_belum       = (int)$_POST['nonjkn_belum'];

    // Hitung total berhasil dan persentase (meskipun total_gagal > total_terkirim)
    $total_berhasil = $total_terkirim - $total_gagal;
    $persentase = ($sep_terbit > 0) ? round(($total_berhasil / $sep_terbit) * 100, 2) : 0;

    $query = "INSERT INTO antrian_pertanggal (
        tanggal, sep_terbit, total_terkirim, total_gagal, total_berhasil,
        jkn_selesai, mjkn_selesai, jkn_belum, mjkn_belum,
        nonjkn_selesai, nonjkn_belum, persentase, tgl_input, user_input
    ) VALUES (
        '$tanggal', '$sep_terbit', '$total_terkirim', '$total_gagal', '$total_berhasil',
        '$jkn_selesai', '$mjkn_selesai', '$jkn_belum', '$mjkn_belum',
        '$nonjkn_selesai', '$nonjkn_belum', '$persentase', NOW(), '$user_id'
    )";

    if (mysqli_query($conn, $query)) {
        $notif = "Data berhasil disimpan!";
    } else {
        $notif = "Gagal menyimpan data! Error: " . mysqli_error($conn);
    }
}

// Ambil data sesuai filter
$where = '';
if ($filter_tanggal_awal && $filter_tanggal_akhir) {
    $where = "WHERE a.tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
}

$data = mysqli_query($conn, "
    SELECT a.*, u.nama AS user_nama 
    FROM antrian_pertanggal a 
    LEFT JOIN users u ON a.user_input = u.id 
    $where
    ORDER BY a.tanggal ASC
");

// Data chart
$chart_labels = [];
$chart_data = [];
while($row_chart = mysqli_fetch_assoc($data)) {
    $chart_labels[] = formatTanggalIndo($row_chart['tanggal']);
    $chart_data[] = $row_chart['persentase'];
}
mysqli_data_seek($data, 0); // reset pointer
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Antrian Pertanggal</title>

<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.table-responsive-custom { width: 100%; overflow-x: auto; }
.table-data { white-space: nowrap; min-width: 1200px; }
.icon-help { color: red; margin-left: 10px; cursor: pointer; }
.modal-body code {
  background-color: #f1f1f1;
  padding: 2px 6px;
  border-radius: 4px;
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
            <div class="card-header d-flex align-items-center">
              <h4 class="mb-0">Antrian Pertanggal</h4>
              <i class="fas fa-question-circle icon-help" data-toggle="modal" data-target="#infoRumusModal" title="Lihat penjelasan rumus"></i>
            </div>
            <div class="card-body">

              <!-- FILTER TANGGAL -->
              <form method="POST" class="form-inline mb-3">
                <div class="form-group mr-2">
                  <label>Awal: </label>
                  <input type="date" name="filter_awal" class="form-control ml-1" value="<?= $filter_tanggal_awal ?>">
                </div>
                <div class="form-group mr-2">
                  <label>Akhir: </label>
                  <input type="date" name="filter_akhir" class="form-control ml-1" value="<?= $filter_tanggal_akhir ?>">
                </div>
                <button type="submit" class="btn btn-info"><i class="fas fa-filter"></i> Filter</button>
              </form>

              <!-- TAB -->
              <ul class="nav nav-tabs" id="dataTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="form-tab" data-toggle="tab" href="#form" role="tab">Input Data</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data & Grafik</a>
                </li>
              </ul>

              <div class="tab-content mt-3" id="dataTabContent">
                <!-- FORM -->
                <div class="tab-pane fade show active" id="form" role="tabpanel">
                  <form method="POST">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Tanggal</label>
                          <input type="date" name="tanggal" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>SEP Terbit</label>
                          <input type="number" name="sep_terbit" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>Total Terkirim</label>
                          <input type="number" name="total_terkirim" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>Total Gagal</label>
                          <input type="number" name="total_gagal" class="form-control" required>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group">
                          <label>MJKN Selesai</label>
                          <input type="number" name="mjkn_selesai" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>MJKN Belum</label>
                          <input type="number" name="mjkn_belum" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>JKN Selesai</label>
                          <input type="number" name="jkn_selesai" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>JKN Belum</label>
                          <input type="number" name="jkn_belum" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>Non JKN Selesai</label>
                          <input type="number" name="nonjkn_selesai" class="form-control" required>
                        </div>
                        <div class="form-group">
                          <label>Non JKN Belum</label>
                          <input type="number" name="nonjkn_belum" class="form-control" required>
                        </div>
                      </div>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                  </form>
                </div>

                <!-- DATA & GRAFIK -->
                <div class="tab-pane fade" id="data" role="tabpanel">
                  <canvas id="persentaseChart" height="100"></canvas>
                  <div class="table-responsive-custom mt-3">
                    <table class="table table-bordered table-striped table-data">
                      <thead class="thead-dark">
                        <tr>
                          <th>No</th>
                          <th>Tanggal</th>
                          <th>SEP Terbit</th>
                          <th>Total Terkirim</th>
                          <th>Total Gagal</th>
                          <th>Total Berhasil</th>
                          <th>Persentase (%)</th>
                          <th>MJKN Selesai</th>
                          <th>MJKN Belum</th>
                          <th>JKN Selesai</th>
                          <th>JKN Belum</th>
                          <th>Non JKN Selesai</th>
                          <th>Non JKN Belum</th>
                          <th>User Input</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; while($row=mysqli_fetch_assoc($data)): ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= formatTanggalIndo($row['tanggal']) ?></td>
                          <td><?= $row['sep_terbit'] ?></td>
                          <td><?= $row['total_terkirim'] ?></td>
                          <td><?= $row['total_gagal'] ?></td>
                          <td><?= $row['total_berhasil'] ?></td>
                          <td><b><?= $row['persentase'] ?>%</b></td>
                          <td><?= $row['mjkn_selesai'] ?></td>
                          <td><?= $row['mjkn_belum'] ?></td>
                          <td><?= $row['jkn_selesai'] ?></td>
                          <td><?= $row['jkn_belum'] ?></td>
                          <td><?= $row['nonjkn_selesai'] ?></td>
                          <td><?= $row['nonjkn_belum'] ?></td>
                          <td><?= htmlspecialchars($row['user_nama']) ?></td>
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

<!-- MODAL PENJELASAN RUMUS -->
<div class="modal fade" id="infoRumusModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-calculator"></i> Penjelasan Rumus Persentase</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Rumus menghitung <b>Persentase (%)</b>:</p>
        <code>Total Berhasil = Total Terkirim - Total Gagal</code><br>
        <code>Persentase = (Total Berhasil ÷ SEP Terbit) × 100</code>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
          <i class="fas fa-times"></i> Tutup
        </button>
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
$(document).ready(function(){
  <?php if($notif): ?>
    Swal.fire({
      icon: '<?= (strpos($notif, "berhasil") !== false) ? "success" : "error" ?>',
      title: 'Informasi',
      text: '<?= $notif ?>',
      timer: 4000,
      showConfirmButton: false
    });
  <?php endif; ?>

  // Chart.js
  const ctx = document.getElementById('persentaseChart').getContext('2d');
  const persentaseChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($chart_labels) ?>,
      datasets: [{
        label: 'Persentase (%)',
        data: <?= json_encode($chart_data) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: true, position: 'top' },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100
        }
      }
    }
  });
});
</script>
</body>
</html>
