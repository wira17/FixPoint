<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Cek login
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    echo "<script>alert('Anda belum login.'); window.location.href='login.php';</script>";
    exit;
}

$current_file = basename(__FILE__);

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Proses simpan nomor surat jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomor_surat'], $_POST['unit_id'])) {
    $nomor_surat = $conn->real_escape_string($_POST['nomor_surat']);
    $unit_id = intval($_POST['unit_id']);

    $insert = $conn->prepare("INSERT INTO no_surat (nomor_surat, unit_id) VALUES (?, ?)");
    $insert->bind_param("si", $nomor_surat, $unit_id);
    if ($insert->execute()) {
        $_SESSION['flash_message'] = "Nomor surat berhasil disimpan.";
        header("Location: master_no_surat.php");
        exit;
    } else {
        $_SESSION['flash_message'] = "Terjadi kesalahan: " . $conn->error;
        header("Location: master_no_surat.php");
        exit;
    }
}

// Ambil unit kerja untuk dropdown
$unit_result = $conn->query("SELECT * FROM unit_kerja ORDER BY nama_unit ASC");
$units = $unit_result->fetch_all(MYSQLI_ASSOC);

// Ambil nomor surat yang sudah ada
$nosurat_result = $conn->query("
    SELECT n.*, u.nama_unit 
    FROM no_surat n 
    LEFT JOIN unit_kerja u ON n.unit_id = u.id 
    ORDER BY n.id ASC
");
$nosurat_list = $nosurat_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Master Nomor Surat</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table-responsive { margin-top:20px; overflow-x:auto; white-space:nowrap; }
.flash-center { position:fixed; top:20%; left:50%; transform:translate(-50%,-50%); z-index:1050; min-width:300px; max-width:90%; text-align:center; padding:15px; border-radius:8px; font-weight:500; box-shadow:0 5px 15px rgba(0,0,0,0.3);}
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
<div class="alert alert-info flash-center" id="flashMsg">
<?= htmlspecialchars($_SESSION['flash_message']) ?>
</div>
<?php unset($_SESSION['flash_message']); endif; ?>

<div class="card">
<div class="card-header">
<h4>Master Nomor Surat</h4>
</div>
<div class="card-body">

<!-- Form Input -->
<form method="POST" class="mb-4">
    <div class="row">
        <div class="col-md-4">
            <label>Nomor Surat</label>
            <input type="text" name="nomor_surat" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label>Unit Kerja</label>
            <select name="unit_id" class="form-control" required>
                <option value="">-- Pilih Unit --</option>
                <?php foreach($units as $unit): ?>
                    <option value="<?= $unit['id'] ?>"><?= htmlspecialchars($unit['nama_unit']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </div>
</form>

<!-- Tabel Nomor Surat -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th>No</th>
<th>Nomor Surat</th>
<th>Unit Kerja</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
foreach($nosurat_list as $ns){
    echo "<tr>
        <td>{$no}</td>
        <td>".htmlspecialchars($ns['nomor_surat'])."</td>
        <td>".htmlspecialchars($ns['nama_unit'] ?? '-')."</td>
        <td class='text-center'>
            <a href='edit_no_surat.php?id={$ns['id']}' class='btn btn-sm btn-warning mx-1' title='Edit'><i class='fas fa-edit'></i></a>
            <a href='hapus_no_surat.php?id={$ns['id']}' onclick=\"return confirm('Yakin ingin hapus nomor surat ini?')\" class='btn btn-sm btn-danger mx-1' title='Hapus'><i class='fas fa-trash'></i></a>
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
</section>
</div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script>
$(document).ready(function(){
    setTimeout(()=>$("#flashMsg").fadeOut("slow"),3000);
});
</script>
</body>
</html>
