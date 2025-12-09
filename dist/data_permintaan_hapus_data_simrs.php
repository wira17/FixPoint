<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    echo "<script>alert('Anda belum login');window.location='login.php';</script>";
    exit;
}

$current_file = basename(__FILE__);

$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt   = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini');window.location='dashboard.php';</script>";
    exit;
}


// === UPDATE STATUS ===
if (isset($_POST['update_status'])) {
    $id     = $_POST['id'];
    $status = $_POST['status'];

    $waktu_update = date('Y-m-d H:i:s');
    $admin_nama   = $_SESSION['nama'] ?? 'Administrator';

    $conn->query("
        UPDATE permintaan_hapus_data 
        SET status='$status', updated_status_at='$waktu_update', updated_by='$admin_nama'
        WHERE id='$id'
    ");

    $_SESSION['flash_message'] = "✔ Status berhasil diperbarui menjadi <b>$status</b>.";
    echo "<script>location.href='data_permintaan_hapus_data_simrs.php';</script>";
    exit;
}


// === PAGINATION SETTING ===
$limit = 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM permintaan_hapus_data");
$totalData  = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);


// === LOAD DATA WITH PAGINATION ===
$data = $conn->query("SELECT * FROM permintaan_hapus_data ORDER BY id DESC LIMIT $start,$limit");

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Permintaan Hapus Data</title>

<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">

<style>
.flash-center{
    position:fixed; top:15%; left:50%; transform:translate(-50%, -50%);
    padding:10px 20px; background:#28a745; color:white; border-radius:6px;
    font-weight:bold; z-index:999999;
}

/* ---- scroll kanan jika data panjang ---- */
.table-scroll {
    overflow-x: auto;
    white-space: nowrap;
}

.table td {
    white-space: nowrap;
}

/* Modal fix */
.modal-backdrop { z-index:1040!important; }
.modal { z-index:1050!important; }

.small-text { font-size:10px; color:#666; }
</style>
</head>
<body>

<div id="app">
<div class="main-wrapper main-wrapper-1">

<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
<section class="section"><div class="section-body">

<!-- Flash -->
<?php if(isset($_SESSION['flash_message'])){ ?>
<div class="flash-center" id="flashMsg"><?= $_SESSION['flash_message']; ?></div>
<script>setTimeout(()=>{document.getElementById('flashMsg').style.display='none';},3000);</script>
<?php unset($_SESSION['flash_message']); } ?>


<div class="card">
<div class="card-header"><h4>📁 Data Permintaan Hapus Data SIMRS</h4></div>

<div class="card-body table-scroll">

<table class="table table-bordered table-hover table-sm">
<thead class="text-center bg-dark text-white">
<tr>
<th>No</th>
<th>Nama</th>
<th>Jabatan</th>
<th>Unit</th>
<th>Kronologi</th>
<th>Tanggal</th>
<th>Status</th>
<th>Update Terakhir</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>
<?php 
$no=$start+1;
while($row = $data->fetch_assoc()):
?>

<tr>
<td><?= $no++; ?></td>
<td><?= $row['nama']; ?></td>
<td><?= $row['jabatan']; ?></td>
<td><?= $row['unit_kerja']; ?></td>
<td><?= str_replace(["\n","\r"]," ", $row['kronologi']); ?></td>
<td><?= $row['tanggal']; ?></td>

<td class="text-center">
<?php
$color = [
"Menunggu"=>"warning",
"Diproses"=>"primary",
"Ditolak"=>"danger",
"Selesai"=>"success"
][$row['status']];
?>
<span class="badge badge-<?= $color ?>"><?= $row['status']; ?></span>
</td>

<td class="small-text">
<?= $row['updated_status_at'] ? 
"<b>".date('d-m-Y H:i',strtotime($row['updated_status_at']))."</b><br><i>oleh ".$row['updated_by']."</i>" 
: "<i>- belum pernah diubah -</i>"; ?>
</td>

<td class="text-center">
    <button class="btn btn-sm btn-info"
        onclick="openModal(
            '<?= $row['id']; ?>',
            '<?= $row['status']; ?>',
            `<?= htmlspecialchars($row['kronologi'], ENT_QUOTES); ?>`,
            '<?= $row['nama']; ?>'
        )">
        <i class="fas fa-edit"></i>
    </button>
</td>

</tr>
<?php endwhile; ?>
</tbody>
</table>

<br>

<!-- PAGINATION -->
<nav>
<ul class="pagination justify-content-center">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page ? 'active':'' ?>">
<a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>

</div></div>

</div></section>
</div>
</div>
</div>


<!-- MODAL GLOBAL -->
<div class="modal fade" id="modalStatusGlobal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<div class="modal-header bg-primary text-white">
<h5 class="modal-title"><i class="fas fa-edit"></i> Update Status</h5>
<button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
</div>

<form method="POST">
<div class="modal-body">
<p><strong id="modalNama"></strong></p>
<p id="modalKronologi" style="white-space:pre-line;"></p>

<input type="hidden" name="id" id="modalId">

<div class="form-group">
<label>Status</label>
<select name="status" id="modalStatus" class="form-control">
<option>Menunggu</option>
<option>Diproses</option>
<option>Ditolak</option>
<option>Selesai</option>
</select>
</div>
</div>

<div class="modal-footer">
<button type="submit" name="update_status" class="btn btn-success">Simpan</button>
<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>
</form>

</div></div></div>


<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>

<script>
function openModal(id,status,kronologi,nama){
    document.getElementById("modalId").value = id;
    document.getElementById("modalStatus").value = status;
    document.getElementById("modalNama").innerHTML = nama;
    document.getElementById("modalKronologi").innerHTML = kronologi;
    $('#modalStatusGlobal').modal('show');
}
</script>

</body>
</html>
