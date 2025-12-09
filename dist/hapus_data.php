<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    echo "<script>alert('Anda belum login.'); window.location.href='login.php';</script>";
    exit;
}

$current_file = basename(__FILE__);

// ====== CEK AKSES MENU ======
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Anda tidak memiliki akses.');window.location.href='dashboard.php';</script>";
    exit;
}

// ====== GET USER DATA ======
$qUser = $conn->query("SELECT nama, jabatan, unit_kerja FROM users WHERE id='$user_id'");
$userData = $qUser->fetch_assoc();

$nama       = $userData['nama'] ?? "-";
$jabatan    = $userData['jabatan'] ?? "-";
$unit       = $userData['unit_kerja'] ?? "-";

// ====== SIMPAN DATA ======
if (isset($_POST['simpan'])) {
    $kronologi = trim($_POST['kronologi']);

    if ($kronologi == "") {
        $_SESSION['flash_message'] = "⚠ Kronologi wajib diisi!";
    } else {

        // === Generate Nomor Surat ===
        $romawi = [
            1 => "I", 2 => "II", 3 => "III", 4 => "IV", 
            5 => "V", 6 => "VI", 7 => "VII", 8 => "VIII", 
            9 => "IX", 10 => "X", 11 => "XI", 12 => "XII"
        ];

        $bulan = date('n');
        $tahun = date('Y');

        // Ambil nomor terakhir
        $qLast = $conn->query("SELECT nomor_surat FROM permintaan_hapus_data ORDER BY id DESC LIMIT 1");
        $lastNum = 1;

        if ($qLast && $qLast->num_rows > 0) {
            $lastData = $qLast->fetch_assoc();
            preg_match('/(\d+)\//', $lastData['nomor_surat'], $match);
            if (isset($match[1])) {
                $lastNum = intval($match[1]) + 1;
            }
        }

        // Format nomor surat
        $nomor_surat = str_pad($lastNum, 4, '0', STR_PAD_LEFT) . "/PHDS/RSPH/" . $romawi[$bulan] . "/$tahun";

        // Simpan data
        $stmt = $conn->prepare("
            INSERT INTO permintaan_hapus_data (user_id, nama, jabatan, unit_kerja, nomor_surat, kronologi) 
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->bind_param("isssss", $user_id, $nama, $jabatan, $unit, $nomor_surat, $kronologi);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "✔️ Permintaan berhasil terkirim!";
        } else {
            $_SESSION['flash_message'] = "❌ Terjadi kesalahan, coba ulangi.";
        }
    }

    echo "<script>location.href='hapus_data.php';</script>";
    exit;
}

// ====== LOAD DATA TABLE ======
$data_query = $conn->query("SELECT * FROM permintaan_hapus_data WHERE user_id='$user_id' ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Permintaan Hapus Data SIMRS</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
<link rel="stylesheet" href="assets/css/style.css" />
<link rel="stylesheet" href="assets/css/components.css" />

<style>
.flash-center {
    position:fixed;
    top:15%;
    left:50%;
    transform:translate(-50%, -50%);
    z-index:1050;
    padding:15px;
    border-radius:8px;
    font-weight:bold;
    background:#ffc107;
}
.table-status span { padding:6px 12px; border-radius:6px; font-size:12px; }
.form-group label { font-size: 14px; font-weight: 600; }
.form-control { font-size: 14px; }
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

<!-- FLASH MESSAGE -->
<?php if (isset($_SESSION['flash_message'])): ?>
<div class="flash-center" id="flashMsg">
<?= $_SESSION['flash_message']; ?>
</div>
<script>
setTimeout(() => { document.getElementById("flashMsg").style.display="none"; }, 3000);
</script>
<?php unset($_SESSION['flash_message']); endif; ?>

<div class="card">
<div class="card-header d-flex align-items-center">
<h4 class="mb-0">🗑 Form Pengajuan Hapus Data SIMRS</h4>

<button type="button" class="btn btn-link text-danger ml-2 p-0" 
        data-toggle="modal" data-target="#prosedurModal">
<i class="fas fa-question-circle fa-lg"></i>
</button>
</div>

<div class="card-body">

<ul class="nav nav-tabs" id="izinTab">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#input">Input Data</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#data">Data Tersimpan</a>
    </li>
</ul>

<div class="tab-content mt-3">

<!-- TAB INPUT -->
<div class="tab-pane fade show active" id="input">
<form method="POST">

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label><b>Nama</b></label>
            <input type="text" class="form-control" value="<?= $nama ?>" readonly>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label><b>Jabatan</b></label>
            <input type="text" class="form-control" value="<?= $jabatan ?>" readonly>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label><b>Unit Kerja</b></label>
            <input type="text" class="form-control" value="<?= $unit ?>" readonly>
        </div>
    </div>
</div>

<div class="form-group mt-2">
    <label><b>Kronologi (Wajib)</b></label>
    <textarea name="kronologi" rows="5" class="form-control" 
    placeholder="Tuliskan alasan lengkap kenapa data harus dihapus..." required></textarea>
</div>

<div class="text-right">
    <button class="btn btn-primary" name="simpan">
        <i class="fas fa-save"></i> Simpan
    </button>
</div>

</form>
</div>

<!-- TAB DATA -->
<div class="tab-pane fade" id="data">
<div class="table-responsive">
<table class="table table-bordered table-sm">
<thead class="text-center bg-dark text-white">
<tr>
<th>No</th>
<th>No Surat</th>
<th>Tanggal</th>
<th>Kronologi</th>
<th>Status</th>
<th>Cetak</th>
</tr>
</thead>
<tbody>

<?php $no=1; while($row = $data_query->fetch_assoc()): ?>
<tr>
<td><?= $no++; ?></td>
<td><?= $row['nomor_surat']; ?></td>
<td><?= $row['tanggal']; ?></td>
<td style="white-space:pre-line;"><?= $row['kronologi']; ?></td>
<td class="text-center table-status">
<?php 
$status = [
"Menunggu" => "badge badge-warning",
"Diproses" => "badge badge-primary",
"Ditolak"  => "badge badge-danger",
"Selesai"  => "badge badge-success"
];
?>
<span class="<?= $status[$row['status']] ?>"><?= $row['status']; ?></span>
</td>
<td class="text-center">
<a href="print_hapus_data.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-sm btn-secondary">
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

<!-- MODAL -->
<div class="modal fade" id="prosedurModal">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header bg-danger text-white">
<h5 class="modal-title">📌 Prosedur Hapus Data</h5>
<button class="close text-white" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
<ol>
<li>Isi dengan data yang valid dan jelas.</li>
<li>Permintaan akan diperiksa admin SIMRS.</li>
<li>Status akan berubah sesuai proses pemeriksaan.</li>
</ol>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
</body>
</html>
