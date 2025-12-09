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

// Ambil semua user
$users_result = $conn->query("SELECT * FROM users ORDER BY id ASC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Karyawan Lengkap</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table-responsive { margin-top:20px; overflow-x:auto; white-space:nowrap; }
.flash-center { position:fixed; top:20%; left:50%; transform:translate(-50%,-50%); z-index:1050; min-width:300px; max-width:90%; text-align:center; padding:15px; border-radius:8px; font-weight:500; box-shadow:0 5px 15px rgba(0,0,0,0.3);}
.table td, .table th { vertical-align: middle !important; }

/* Modal full width */
.modal-dialog.modal-xl {
    max-width: 95%;
    width: 95%;
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

<?php if(isset($_SESSION['flash_message'])): ?>
<div class="alert alert-info flash-center" id="flashMsg">
<?= htmlspecialchars($_SESSION['flash_message']) ?>
</div>
<?php unset($_SESSION['flash_message']); endif; ?>

<div class="card">
<div class="card-header"><h4>Data Karyawan Lengkap</h4></div>
<div class="card-body table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th>No</th>

<th>Aksi</th>
<th>NIK</th>
<th>Nama</th>
<th>Jabatan</th>
<th>Unit Kerja</th>
<th>Email</th>
<th>No HP</th>
<th>Status</th>
<th>Jenis Kelamin</th>
<th>Tempat Lahir</th>
<th>Tanggal Lahir</th>
<th>Alamat</th>
<th>Kota</th>
<th>No. KTP</th>
<th>Hubungan Keluarga</th>
<th>Riwayat Pekerjaan</th>
<th>Riwayat Pendidikan</th>
<th>Gol Darah</th>
<th>Riwayat Penyakit</th>
<th>Status Vaksinasi</th>
<th>BPJS Kesehatan</th>
<th>BPJS Ketenagakerjaan</th>
<th>Asuransi Tambahan</th>
<th>Dokumen Pendukung</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
foreach($users as $user){
    $userId = $user['id'];

    // Informasi pribadi
    $stmt = $conn->prepare("SELECT * FROM informasi_pribadi WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId); $stmt->execute();
    $info_pribadi = $stmt->get_result()->fetch_assoc() ?? [];

    // Riwayat pekerjaan
    $stmt = $conn->prepare("SELECT * FROM riwayat_pekerjaan WHERE user_id = ? ORDER BY tanggal_mulai DESC");
    $stmt->bind_param("i", $userId); $stmt->execute();
    $pekerjaan_res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pekerjaan_str = implode(" | ", array_map(fn($p)=>($p['nama_perusahaan']??'-')." ({$p['posisi']}, {$p['tanggal_mulai']} s/d {$p['tanggal_selesai']})", $pekerjaan_res));

    // Riwayat pendidikan
    $stmt = $conn->prepare("SELECT * FROM riwayat_pendidikan WHERE user_id = ? ORDER BY tgl_lulus DESC");
    $stmt->bind_param("i", $userId); $stmt->execute();
    $pendidikan_res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pendidikan_str = implode(" | ", array_map(fn($pd)=>($pd['pendidikan_terakhir']??'-')." {$pd['jurusan']} ({$pd['kampus']}, {$pd['tgl_lulus']})", $pendidikan_res));

    // Riwayat kesehatan
    $stmt = $conn->prepare("SELECT * FROM riwayat_kesehatan WHERE user_id = ?");
    $stmt->bind_param("i", $userId); $stmt->execute();
    $kesehatan = $stmt->get_result()->fetch_assoc() ?? [];

    // Dokumen pendukung
    $stmt = $conn->prepare("SELECT * FROM dokumen_pendukung WHERE user_id = ?");
    $stmt->bind_param("i", $userId); $stmt->execute();
    $dokumen = $stmt->get_result()->fetch_assoc() ?? [];
    $dok_fields = ['ktp','ijazah','str','sip','vaksin','pelatihan','surat_kerja','pas_foto'];
    $dokumen_str = implode(" ", array_map(fn($f)=>!empty($dokumen[$f]) ? "<a href='uploads/{$dokumen[$f]}' target='_blank' title='".strtoupper($f)."'><i class='fas fa-file-pdf'></i></a>" : '', $dok_fields));

    // JSON untuk modal
    $full_data = htmlspecialchars(json_encode([
        'user'=>$user,
        'info_pribadi'=>$info_pribadi,
        'pekerjaan'=>$pekerjaan_res,
        'pendidikan'=>$pendidikan_res,
        'kesehatan'=>$kesehatan,
        'dokumen'=>$dokumen
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));

   echo "<tr>
    <td>{$no}</td>
    <td class='text-center'>
    <a href='cetak_karyawan.php?id={$userId}' target='_blank' title='Cetak' class='btn btn-success btn-sm mx-1'>
        <i class='fas fa-print'></i>
    </a>
    <button class='btn btn-info btn-sm mx-1' onclick='lihatData({$full_data})' title='Lihat'>
        <i class='fas fa-eye'></i>
    </button>
</td>
    <td>".htmlspecialchars($user['nik'])."</td>
    <td>".htmlspecialchars($user['nama'])."</td>
    <td>".htmlspecialchars($user['jabatan'])."</td>
    <td>".htmlspecialchars($user['unit_kerja'])."</td>
    <td>".htmlspecialchars($user['email'])."</td>
    <td>".htmlspecialchars($user['no_hp'])."</td>
    <td>".htmlspecialchars($user['status'])."</td>
    <td>".htmlspecialchars($info_pribadi['jenis_kelamin']??'-')."</td>
    <td>".htmlspecialchars($info_pribadi['tempat_lahir']??'-')."</td>
    <td>".(!empty($info_pribadi['tanggal_lahir']) ? date('d-m-Y', strtotime($info_pribadi['tanggal_lahir'])) : '-')."</td>
    <td>".htmlspecialchars($info_pribadi['alamat']??'-')."</td>
    <td>".htmlspecialchars($info_pribadi['kota']??'-')."</td>
    <td>".htmlspecialchars($info_pribadi['no_ktp']??'-')."</td>
    <td>".htmlspecialchars($info_pribadi['hubungan_keluarga']??'-')."</td>
    <td>".htmlspecialchars($pekerjaan_str)."</td>
    <td>".htmlspecialchars($pendidikan_str)."</td>
    <td>".htmlspecialchars($kesehatan['gol_darah']??'-')."</td>
    <td>".htmlspecialchars($kesehatan['riwayat_penyakit']??'-')."</td>
    <td>".htmlspecialchars($kesehatan['status_vaksinasi']??'-')."</td>
    <td>".htmlspecialchars($kesehatan['no_bpjs_kesehatan']??'-')."</td>
    <td>".htmlspecialchars($kesehatan['no_bpjs_kerja']??'-')."</td>
    <td>".htmlspecialchars($kesehatan['asuransi_tambahan']??'-')."</td>
    <td class='text-center'>{$dokumen_str}</td>
  

</tr>";

    $no++;
}
?>
</tbody>
</table>
</div>
</div>

</div>
</section>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalLihat" tabindex="-1" role="dialog" aria-labelledby="modalLihatLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLihatLabel">Detail Karyawan</h5>
       
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-footer">
      
      </div>
    </div>
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

function lihatData(data){
    let html = '<h5>Informasi Pribadi</h5><table class="table table-sm table-bordered">';
    const info = data.info_pribadi || {};
    const user = data.user || {};
    html += `<tr><th>NIK</th><td>${user.nik||'-'}</td><th>Nama</th><td>${user.nama||'-'}</td></tr>`;
    html += `<tr><th>Jenis Kelamin</th><td>${info.jenis_kelamin||'-'}</td><th>Tempat Lahir</th><td>${info.tempat_lahir||'-'}</td></tr>`;
    html += `<tr><th>Tanggal Lahir</th><td>${info.tanggal_lahir||'-'}</td><th>Alamat</th><td>${info.alamat||'-'}</td></tr>`;
    html += `<tr><th>Kota</th><td>${info.kota||'-'}</td><th>No. KTP</th><td>${info.no_ktp||'-'}</td></tr>`;
    html += `<tr><th>Hubungan Keluarga</th><td colspan="3">${info.hubungan_keluarga||'-'}</td></tr>`;
    html += '</table>';

    // Riwayat Pekerjaan
    html += '<h5>Riwayat Pekerjaan</h5><table class="table table-sm table-bordered"><tr><th>No</th><th>Perusahaan</th><th>Posisi</th><th>Periode</th><th>Alasan Keluar</th></tr>';
    data.pekerjaan.forEach((p,i)=>{
        html += `<tr>
            <td>${i+1}</td>
            <td>${p.nama_perusahaan||'-'}</td>
            <td>${p.posisi||'-'}</td>
            <td>${p.tanggal_mulai||'-'} s/d ${p.tanggal_selesai||'-'}</td>
            <td>${p.alasan_keluar||'-'}</td>
        </tr>`;
    });
    html += '</table>';

    // Riwayat Pendidikan
    html += '<h5>Riwayat Pendidikan</h5><table class="table table-sm table-bordered"><tr><th>No</th><th>Pendidikan</th><th>Jurusan</th><th>Kampus</th><th>Tanggal Lulus</th><th>No Ijazah</th></tr>';
    data.pendidikan.forEach((pd,i)=>{
        html += `<tr>
            <td>${i+1}</td>
            <td>${pd.pendidikan_terakhir||'-'}</td>
            <td>${pd.jurusan||'-'}</td>
            <td>${pd.kampus||'-'}</td>
            <td>${pd.tgl_lulus||'-'}</td>
            <td>${pd.no_ijazah||'-'}</td>
        </tr>`;
    });
    html += '</table>';

    // Riwayat Kesehatan
    const k = data.kesehatan || {};
    html += '<h5>Riwayat Kesehatan</h5><table class="table table-sm table-bordered">';
    html += `<tr><th>Gol Darah</th><td>${k.gol_darah||'-'}</td><th>Riwayat Penyakit</th><td>${k.riwayat_penyakit||'-'}</td></tr>`;
    html += `<tr><th>Status Vaksinasi</th><td>${k.status_vaksinasi||'-'}</td><th>BPJS Kesehatan</th><td>${k.no_bpjs_kesehatan||'-'}</td></tr>`;
    html += `<tr><th>BPJS Kerja</th><td>${k.no_bpjs_kerja||'-'}</td><th>Asuransi Tambahan</th><td>${k.asuransi_tambahan||'-'}</td></tr>`;
    html += '</table>';

    // Dokumen Pendukung (hapus ID dan USER_ID)
    html += '<h5>Dokumen Pendukung</h5><ul>';
    const d = data.dokumen || {};
    for(let key in d){
        if(d[key] && key !== 'id' && key !== 'user_id'){  // <- Hapus kolom ID & USER_ID
            html += `<li>${key.toUpperCase()}: <a href="uploads/${d[key]}" target="_blank">Lihat</a></li>`;
        }
    }
    html += '</ul>';

    $('#modalBody').html(html);
    new bootstrap.Modal(document.getElementById('modalLihat')).show();
}
    
</script>
</body>
</html>
