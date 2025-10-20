<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// Cek akses user
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Ambil nama user
$userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM users WHERE id = '$user_id'"));
$user_nama = $userData['nama'] ?? 'unknown';
$notif = '';

// Ambil daftar faskes dari tabel perusahaan
$perusahaan = mysqli_query($conn, "SELECT * FROM perusahaan ORDER BY nama_perusahaan ASC");

// Daftar bulan dan tahun
$bulan_list = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];
$tahun_list = range(2020, 2030);

// Proses simpan data Mobile JKN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id_perusahaan  = (int) $_POST['id_perusahaan'];
    $bulan          = (int) $_POST['bulan'];
    $tahun          = (int) $_POST['tahun'];
    $jumlah_sep     = (int) $_POST['jumlah_sep'];
    $jumlah_mjkn    = (int) $_POST['jumlah_mjkn'];

    if ($jumlah_sep <= 0) {
        $notif = "Jumlah SEP harus lebih dari 0!";
    } else {
        $persen_mjkn = round(($jumlah_mjkn / $jumlah_sep) * 100, 2);

        $insert = mysqli_query($conn, "INSERT INTO semua_antrian 
            (id_perusahaan, bulan, tahun, jumlah_sep, jumlah_mjkn, persen_mjkn, petugas_input, tanggal_input) 
            VALUES ($id_perusahaan, $bulan, $tahun, $jumlah_sep, $jumlah_mjkn, $persen_mjkn, '$user_nama', NOW())");

        if ($insert) {
            $_SESSION['flash_message'] = "Data Mobile JKN berhasil disimpan.";
            header("Location: mjkn_antrian.php");
            exit;
        } else {
            $notif = "Gagal menyimpan data.";
        }
    }
}

// Filter periode
$filter_bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : '';
$where = [];
if ($filter_bulan) $where[] = "sa.bulan = $filter_bulan";
if ($filter_tahun) $where[] = "sa.tahun = $filter_tahun";
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Ambil data untuk tabel & chart Mobile JKN
$data_query = mysqli_query($conn, "SELECT 
                                    sa.id_perusahaan,
                                    p.nama_perusahaan,
                                    sa.bulan,
                                    sa.tahun,
                                    SUM(sa.jumlah_sep) as jumlah_sep,
                                    SUM(sa.jumlah_mjkn) as jumlah_mjkn,
                                    ROUND(SUM(sa.jumlah_mjkn)/SUM(sa.jumlah_sep)*100,2) as persen_mjkn
                                  FROM semua_antrian sa
                                  JOIN perusahaan p ON sa.id_perusahaan = p.id
                                  $where_sql
                                  GROUP BY sa.id_perusahaan, sa.bulan, sa.tahun
                                  ORDER BY sa.tahun DESC, sa.bulan DESC");

$chart_labels = [];
$chart_mjkn = [];
$chart_rows = [];
while($row = mysqli_fetch_assoc($data_query)){
    $nama_bulan = $bulan_list[$row['bulan']] ?? '-';
    $chart_labels[] = $row['nama_perusahaan'] . " " . $nama_bulan . " " . $row['tahun'];
    $chart_mjkn[] = $row['persen_mjkn'];
    $chart_rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Mobile JKN</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table thead th { background-color: #000 !important; color: #fff !important; }
.card-body .form-row .form-group { margin-bottom: 1rem; }
.table-responsive { overflow-x:auto; }
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
<div class="card-header"><h4>Manajemen Mobile JKN</h4></div>
<div class="card-body">

<ul class="nav nav-tabs" id="mjknTab" role="tablist">
<li class="nav-item"><a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab"><i class="fas fa-edit"></i> Input Data</a></li>
<li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab"><i class="fas fa-table"></i> Data Tersimpan</a></li>
</ul>

<div class="tab-content mt-4">
<!-- Tab Input Data -->
<div class="tab-pane fade show active" id="input" role="tabpanel">
<?php if ($notif): ?><div class="alert alert-danger"><?= $notif ?></div><?php endif; ?>
<?php if (isset($_SESSION['flash_message'])): ?>
<div id="notif-toast" class="alert alert-success text-center">
<?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
</div>
<?php endif; ?>

<form method="POST">
<div class="form-row">
<div class="form-group col-md-4">
<label><i class="fas fa-hospital"></i> Faskes</label>
<select name="id_perusahaan" class="form-control" required>
<option value="">-- Pilih Faskes --</option>
<?php while($row = mysqli_fetch_assoc($perusahaan)): ?>
<option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_perusahaan']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group col-md-4">
<label><i class="fas fa-calendar-alt"></i> Bulan</label>
<select name="bulan" class="form-control" required>
<option value="">-- Pilih Bulan --</option>
<?php foreach($bulan_list as $num => $nama): ?>
<option value="<?= $num ?>"><?= $nama ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group col-md-4">
<label><i class="fas fa-calendar"></i> Tahun</label>
<select name="tahun" class="form-control" required>
<option value="">-- Pilih Tahun --</option>
<?php foreach($tahun_list as $th): ?>
<option value="<?= $th ?>"><?= $th ?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="form-row">
<div class="form-group col-md-6">
<label><i class="fas fa-file-medical"></i> Jumlah SEP Rjtl</label>
<input type="number" name="jumlah_sep" class="form-control" required>
</div>
<div class="form-group col-md-6">
<label><i class="fas fa-users"></i> Jumlah Mobile JKN</label>
<input type="number" name="jumlah_mjkn" class="form-control" required>
</div>
</div>

<div class="form-group">
<label><i class="fas fa-user"></i> Petugas Input</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($user_nama) ?>" readonly>
</div>

<button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
</form>
</div>

<!-- Tab Data Tersimpan -->
<div class="tab-pane fade" id="data" role="tabpanel">
<form class="form-inline mb-3" method="GET">
<label class="mr-2"><i class="fas fa-calendar-alt"></i> Filter Bulan:</label>
<select name="bulan" class="form-control mr-2">
<option value="">-- Semua Bulan --</option>
<?php foreach($bulan_list as $num => $nama): ?>
<option value="<?= $num ?>" <?= $num==$filter_bulan?'selected':'' ?>><?= $nama ?></option>
<?php endforeach; ?>
</select>
<label class="mr-2">Tahun:</label>
<select name="tahun" class="form-control mr-2">
<option value="">-- Semua Tahun --</option>
<?php foreach($tahun_list as $th): ?>
<option value="<?= $th ?>" <?= $th==$filter_tahun?'selected':'' ?>><?= $th ?></option>
<?php endforeach; ?>
</select>
<button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>

<!-- Tombol buka modal -->
<button type="button" class="btn btn-info ml-2" data-toggle="modal" data-target="#grafikModal">
<i class="fas fa-chart-bar"></i> Lihat Grafik</button>
</form>

<div class="table-responsive">
<table class="table table-striped table-bordered nowrap">
<thead>
<tr>
<th>No</th>
<th>Faskes</th>
<th>Bulan</th>
<th>Tahun</th>
<th>Jumlah SEP</th>
<th>Jumlah Mobile JKN</th>
<th>% MJKN</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
foreach($chart_rows as $row){
    $nama_bulan = $bulan_list[$row['bulan']] ?? '-';
    echo "<tr>
            <td>{$no}</td>
            <td>".htmlspecialchars($row['nama_perusahaan'])."</td>
            <td>{$nama_bulan}</td>
            <td>{$row['tahun']}</td>
            <td>{$row['jumlah_sep']}</td>
            <td>{$row['jumlah_mjkn']}</td>
            <td class='".($row['persen_mjkn'] < 95 ? 'text-danger font-weight-bold' : '')."'>
                {$row['persen_mjkn']}%
            </td>
          </tr>";
    $no++;
}
?>
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

<!-- Modal Grafik -->
<div class="modal fade" id="grafikModal" tabindex="-1" role="dialog" aria-labelledby="grafikModalLabel" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width:95%;">
<div class="modal-content" style="height:90vh;">
<div class="modal-header">
<h5 class="modal-title" id="grafikModalLabel">Grafik Persentase Mobile JKN</h5>
<button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
<span aria-hidden="true">&times;</span>
</button>
</div>
<div class="modal-body" style="height:calc(100% - 70px);">
<canvas id="grafikModalChart" style="width:100%; height:100%;"></canvas>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>
</div>
</div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function () {
    // Notifikasi hilang otomatis
    $('#notif-toast').fadeIn(300).delay(2000).fadeOut(500);

    let modalChart;

    // Saat modal grafik dibuka
    $('#grafikModal').on('shown.bs.modal', function () {
        const ctx = document.getElementById('grafikModalChart').getContext('2d');
        const labels = <?= json_encode($chart_labels) ?>;
        const dataMJKN = <?= json_encode($chart_mjkn) ?>;

        // Jika tidak ada data
        if (labels.length === 0) {
            ctx.font = "16px Arial";
            ctx.fillText("Tidak ada data untuk ditampilkan.", 20, 50);
            return;
        }

        // Hapus chart lama jika ada
        if (modalChart) {
            modalChart.destroy();
        }

        // Buat chart baru
        modalChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '% MJKN',
                    data: dataMJKN,
                    borderColor: 'rgba(255, 99, 132, 1)', // warna garis merah lembut
                    backgroundColor: 'transparent',        // tanpa arsiran
                    tension: 0,                             // garis lurus tajam
                    fill: false,                            // tanpa area warna
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 800
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '%';
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Grafik Persentase Mobile JKN per Faskes'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0,
                            font: { size: 11 }
                        },
                        title: {
                            display: true,
                            text: 'Faskes & Periode'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Persentase (%)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                }
            }
        });
    });

    // Hapus chart jika modal ditutup agar tidak dobel render
    $('#grafikModal').on('hidden.bs.modal', function () {
        if (modalChart) {
            modalChart.destroy();
            modalChart = null;
        }
    });
});
</script>
</body>
</html>
