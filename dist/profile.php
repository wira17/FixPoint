<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// === CEK AKSES USER ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

$success = '';

// === AMBIL DATA USER ===
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$row = mysqli_fetch_assoc($q_user);
$nik        = $row['nik'];
$nama       = $row['nama'];
$jabatan    = $row['jabatan'];
$unit_kerja = $row['unit_kerja'];
$email      = $row['email'];
$no_hp      = $row['no_hp'];
$status     = $row['status'];
$created_at = $row['created_at'];
$atasan_id  = $row['atasan_id'];
$ttd        = $row['ttd'] ?? '';

// === PROSES UPDATE PROFIL ===
if (isset($_POST['update'])) {
  $nama          = mysqli_real_escape_string($conn, $_POST['nama']);
  $email         = mysqli_real_escape_string($conn, $_POST['email']);
  $no_hp         = mysqli_real_escape_string($conn, $_POST['no_hp']);
  $jabatan_id    = $_POST['jabatan'];
  $unit_kerja_id = $_POST['unit_kerja'];
  $atasan_id     = intval($_POST['atasan_id']);
  $password_baru = trim($_POST['password_baru'] ?? '');

  // Ambil nama jabatan
  $jabatan_nama = '';
  if (!empty($jabatan_id)) {
    $res_jabatan = mysqli_query($conn, "SELECT nama_jabatan FROM jabatan WHERE id = '$jabatan_id' LIMIT 1");
    if ($row_jabatan = mysqli_fetch_assoc($res_jabatan)) {
      $jabatan_nama = mysqli_real_escape_string($conn, $row_jabatan['nama_jabatan']);
    }
  }

  // Ambil nama unit kerja
  $unit_nama = '';
  if (!empty($unit_kerja_id)) {
    $res_unit = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja WHERE id = '$unit_kerja_id' LIMIT 1");
    if ($row_unit = mysqli_fetch_assoc($res_unit)) {
      $unit_nama = mysqli_real_escape_string($conn, $row_unit['nama_unit']);
    }
  }

  // === PROSES UPLOAD TTD ===
  if (!empty($_FILES['ttd']['name'])) {
      $targetDir = "ttd/";
      $fileName = basename($_FILES['ttd']['name']);
      $fileTmp = $_FILES['ttd']['tmp_name'];
      $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

      // Nama file baru = nik_user.ext
      $newFileName = $nik . "." . $fileExt;
      $targetFile = $targetDir . $newFileName;

      // Validasi ekstensi file
      $allowedExt = ['jpg', 'jpeg', 'png'];
      if (in_array($fileExt, $allowedExt)) {
          // Hapus file lama jika ada
          $qOld = mysqli_query($conn, "SELECT ttd FROM users WHERE id = $user_id");
          $rOld = mysqli_fetch_assoc($qOld);
          if (!empty($rOld['ttd']) && file_exists($targetDir . $rOld['ttd'])) {
              unlink($targetDir . $rOld['ttd']);
          }

          // Upload file baru
          if (move_uploaded_file($fileTmp, $targetFile)) {
              mysqli_query($conn, "UPDATE users SET ttd = '$newFileName' WHERE id = $user_id");
          }
      }
  }

  // === UPDATE PROFIL UTAMA ===
  $query_update = "UPDATE users SET 
    nama = '$nama',
    email = '$email',
    no_hp = '$no_hp',
    jabatan = '$jabatan_nama',
    unit_kerja = '$unit_nama',
    atasan_id = $atasan_id";

  if (!empty($password_baru)) {
    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
    $query_update .= ", password_hash = '$password_hash'";
  }

  $query_update .= " WHERE id = $user_id";
  $update = mysqli_query($conn, $query_update);

  $success = $update ? "Profil berhasil diperbarui." : "Terjadi kesalahan saat memperbarui data.";
}

// === AMBIL ULANG DATA SETELAH UPDATE ===
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$row = mysqli_fetch_assoc($q_user);
$nik        = $row['nik'];
$nama       = $row['nama'];
$jabatan    = $row['jabatan'];
$unit_kerja = $row['unit_kerja'];
$email      = $row['email'];
$no_hp      = $row['no_hp'];
$status     = $row['status'];
$created_at = $row['created_at'];
$atasan_id  = $row['atasan_id'];
$ttd        = $row['ttd'] ?? '';

// Ambil daftar dropdown
$daftar_jabatan_arr = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_jabatan FROM jabatan"), MYSQLI_ASSOC);
$daftar_unit_arr = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_unit FROM unit_kerja"), MYSQLI_ASSOC);
$daftar_atasan_arr = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama FROM users WHERE id != $user_id"), MYSQLI_ASSOC);

// Nama atasan
$nama_atasan = '-';
if (!empty($atasan_id)) {
  $q_atasan = mysqli_query($conn, "SELECT nama FROM users WHERE id = '$atasan_id' LIMIT 1");
  $r_atasan = mysqli_fetch_assoc($q_atasan);
  $nama_atasan = $r_atasan['nama'] ?? '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Pengguna</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
  .list-group-item strong { min-width: 150px; display: inline-block; }
  .form-section { border-right: 1px solid #eee; padding-right: 20px; }
  @media(max-width:768px){ .form-section{border-right:none; padding-right:0;} }
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
<div class="card-header"><h4>Informasi Akun</h4></div>
<div class="card-body">

<!-- FORM EDIT PROFIL (2 KOLOM) -->
<form method="POST" id="formEdit" enctype="multipart/form-data" style="display:none;">
  <div class="row">
    <!-- Kolom Kiri -->
    <div class="col-md-6 form-section">
      <div class="form-group">
        <label>NIK</label>
        <input type="text" value="<?= htmlspecialchars($nik); ?>" class="form-control" readonly>
      </div>
      <div class="form-group">
        <label>Nama</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($nama); ?>" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email); ?>" class="form-control" required>
      </div>
      <div class="form-group">
        <label>No. HP</label>
        <input type="text" name="no_hp" value="<?= htmlspecialchars($no_hp); ?>" class="form-control" required>
      </div>
    </div>

    <!-- Kolom Kanan -->
    <div class="col-md-6">
      <div class="form-group">
        <label>Jabatan</label>
        <select name="jabatan" class="form-control select2">
          <option value="">- Pilih Jabatan -</option>
          <?php foreach($daftar_jabatan_arr as $j): ?>
            <option value="<?= $j['id']; ?>" <?= ($jabatan == $j['nama_jabatan']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($j['nama_jabatan']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Unit Kerja</label>
        <select name="unit_kerja" class="form-control select2">
          <option value="">- Pilih Unit Kerja -</option>
          <?php foreach($daftar_unit_arr as $u): ?>
            <option value="<?= $u['id']; ?>" <?= ($unit_kerja == $u['nama_unit']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['nama_unit']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Atasan</label>
        <select name="atasan_id" class="form-control select2">
          <option value="">- Tidak Ada -</option>
          <?php foreach($daftar_atasan_arr as $a): ?>
            <option value="<?= $a['id']; ?>" <?= ($atasan_id == $a['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['nama']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Password Baru</label>
        <input type="password" name="password_baru" class="form-control" placeholder="Kosongkan jika tidak diganti">
      </div>
      <div class="form-group">
        <label>Tanda Tangan (jpg/png)</label>
        <input type="file" name="ttd" class="form-control" accept=".jpg,.jpeg,.png">
        <?php if (!empty($ttd)): ?>
          <div class="mt-2">
            <img src="ttd/<?= htmlspecialchars($ttd); ?>" alt="TTD" style="height:80px;border:1px solid #ccc;">
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="text-right mt-3">
    <button type="submit" name="update" class="btn btn-success">Simpan Perubahan</button>
    <button type="button" class="btn btn-secondary" onclick="toggleForm()">Batal</button>
  </div>
</form>

<!-- VIEW DATA -->
<div id="dataView">
  <ul class="list-group list-group-flush">
    <li class="list-group-item"><strong>NIK</strong> : <?= htmlspecialchars($nik); ?></li>
    <li class="list-group-item"><strong>Nama</strong> : <?= htmlspecialchars($nama); ?></li>
    <li class="list-group-item"><strong>Email</strong> : <?= htmlspecialchars($email); ?></li>
    <li class="list-group-item"><strong>No. HP</strong> : <?= htmlspecialchars($no_hp); ?></li>
    <li class="list-group-item"><strong>Jabatan</strong> : <?= htmlspecialchars($jabatan); ?></li>
    <li class="list-group-item"><strong>Unit Kerja</strong> : <?= htmlspecialchars($unit_kerja); ?></li>
    <li class="list-group-item"><strong>Atasan</strong> : <?= htmlspecialchars($nama_atasan); ?></li>
    <li class="list-group-item"><strong>Status Akun</strong> : <?= htmlspecialchars($status); ?></li>
    <li class="list-group-item"><strong>Tanggal Daftar</strong> : <?= date('d-m-Y H:i', strtotime($created_at)); ?></li>
    <?php if (!empty($ttd)): ?>
      <li class="list-group-item"><strong>Tanda Tangan</strong>:<br>
        <img src="ttd/<?= htmlspecialchars($ttd); ?>" alt="TTD" style="height:80px;border:1px solid #ccc;margin-top:5px;">
      </li>
    <?php endif; ?>
  </ul>
</div>

<div class="card-footer text-right" id="editButton">
  <button class="btn btn-primary" onclick="toggleForm()">Edit Akun</button>
</div>

</div></div>
</section></div></div></div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>

<script>
function toggleForm() {
  const form = document.getElementById('formEdit');
  const view = document.getElementById('dataView');
  const editBtn = document.getElementById('editButton');
  const isEditing = form.style.display === 'block';
  form.style.display = isEditing ? 'none' : 'block';
  view.style.display = isEditing ? 'block' : 'none';
  editBtn.style.display = isEditing ? 'block' : 'none';
}

$(function(){
  $('select[name="jabatan"], select[name="unit_kerja"], select[name="atasan_id"]').select2({ width:'100%' });
  <?php if (!empty($success)): ?>
  Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '<?= $success ?>',
    timer: 2000,
    position: 'center',
    showConfirmButton: false
  });
  <?php endif; ?>
});
</script>
</body>
</html>
