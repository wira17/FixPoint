<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];

// Ambil data user
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$row = mysqli_fetch_assoc($query);

$data_user = [
    'nik' => $row['nik'],
    'nama' => $row['nama'],
    'email' => $row['email'],
    'no_hp' => $row['no_hp'],
    'jabatan' => $row['jabatan'],
    'unit_kerja' => $row['unit_kerja'],
    'atasan_id' => $row['atasan_id'],
    'status' => $row['status'],
    'status_karyawan_id' => $row['status_karyawan_id'],
    'created_at' => $row['created_at']
];

// Ambil daftar jabatan
$daftar_jabatan_arr = [];
$res = mysqli_query($conn, "SELECT id, nama_jabatan FROM jabatan");
while($r = mysqli_fetch_assoc($res)) $daftar_jabatan_arr[] = $r;

// Ambil daftar unit kerja
$daftar_unit_arr = [];
$res = mysqli_query($conn, "SELECT id, nama_unit FROM unit_kerja");
while($r = mysqli_fetch_assoc($res)) $daftar_unit_arr[] = $r;

// Ambil daftar atasan
$daftar_atasan_arr = [];
$res = mysqli_query($conn, "SELECT id, nama FROM users WHERE id != $user_id");
while($r = mysqli_fetch_assoc($res)) $daftar_atasan_arr[] = $r;

// Ambil daftar status karyawan
$daftar_status_arr = [];
$res = mysqli_query($conn, "SELECT id, nama_status FROM status_karyawan ORDER BY nama_status ASC");
while($r = mysqli_fetch_assoc($res)) $daftar_status_arr[] = $r;

// Nama atasan
$nama_atasan = '-';
if (!empty($data_user['atasan_id'])) {
    $q_atasan = mysqli_query($conn, "SELECT nama FROM users WHERE id = '{$data_user['atasan_id']}' LIMIT 1");
    $r_atasan = mysqli_fetch_assoc($q_atasan);
    $nama_atasan = $r_atasan['nama'] ?? '-';
}

// Nama status karyawan
$status_karyawan = '-';
foreach($daftar_status_arr as $s){
    if($s['id'] == $data_user['status_karyawan_id']){
        $status_karyawan = $s['nama_status'];
        break;
    }
}

// Proses update
$notif = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $jabatan_id = $_POST['jabatan'];
    $unit_kerja_id = $_POST['unit_kerja'];
    $atasan_id = intval($_POST['atasan_id']);
    $status_karyawan_id = intval($_POST['status_karyawan_id']);
    $password_baru = trim($_POST['password_baru'] ?? '');

    // Nama jabatan & unit
    $jabatan_nama = '';
    if (!empty($jabatan_id)) {
        $res_jabatan = mysqli_query($conn, "SELECT nama_jabatan FROM jabatan WHERE id = '$jabatan_id' LIMIT 1");
        $jabatan_nama = mysqli_fetch_assoc($res_jabatan)['nama_jabatan'] ?? '';
    }

    $unit_nama = '';
    if (!empty($unit_kerja_id)) {
        $res_unit = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja WHERE id = '$unit_kerja_id' LIMIT 1");
        $unit_nama = mysqli_fetch_assoc($res_unit)['nama_unit'] ?? '';
    }

    $query_update = "UPDATE users SET 
        nama='$nama', email='$email', no_hp='$no_hp', 
        jabatan='$jabatan_nama', unit_kerja='$unit_nama', atasan_id=$atasan_id, status_karyawan_id=$status_karyawan_id";
    if (!empty($password_baru)) {
        $query_update .= ", password_hash='".password_hash($password_baru, PASSWORD_DEFAULT)."'";
    }
    $query_update .= " WHERE id=$user_id";
    $update = mysqli_query($conn, $query_update);

    $notif = $update ? "Data akun berhasil diperbarui" : "Terjadi kesalahan: ".mysqli_error($conn);
    if (!empty($password_baru) && $update) $notif = "Profil dan password berhasil diperbarui.";

    // Refresh data
    $data_user['nama'] = $nama;
    $data_user['email'] = $email;
    $data_user['no_hp'] = $no_hp;
    $data_user['jabatan'] = $jabatan_nama;
    $data_user['unit_kerja'] = $unit_nama;
    $data_user['atasan_id'] = $atasan_id;
    $data_user['status_karyawan_id'] = $status_karyawan_id;
    $nama_atasan = $nama_atasan;
    $status_karyawan = '';
    foreach($daftar_status_arr as $s){
        if($s['id'] == $status_karyawan_id){
            $status_karyawan = $s['nama_status'];
            break;
        }
    }
}

$fields = [
    'nik'=>['label'=>'NIK','icon'=>'bi-credit-card'],
    'nama'=>['label'=>'Nama','icon'=>'bi-person'],
    'email'=>['label'=>'Email','icon'=>'bi-envelope'],
    'no_hp'=>['label'=>'No. HP','icon'=>'bi-phone'],
    'jabatan'=>['label'=>'Jabatan','icon'=>'bi-briefcase'],
    'unit_kerja'=>['label'=>'Unit Kerja','icon'=>'bi-building'],
    'atasan'=>$nama_atasan ? ['label'=>'Atasan','icon'=>'bi-person-check'] : ['label'=>'Atasan','icon'=>'bi-person-check'],
    'status_karyawan'=>['label'=>'Status Karyawan','icon'=>'bi-toggle-on'],
    'created_at'=>['label'=>'Daftar Akun','icon'=>'bi-calendar-check']
];
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<div class="card">
  <div class="card-header"><h4>Informasi Akun</h4></div>
  <div class="card-body">

    <!-- Form Edit -->
    <form method="POST" id="formEditAkun" style="display:none;">
      <div class="row">
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-person"></i> Nama</label>
          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data_user['nama']); ?>" required>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-envelope"></i> Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data_user['email']); ?>" required>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-phone"></i> No. HP</label>
          <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($data_user['no_hp']); ?>" required>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-briefcase"></i> Jabatan</label>
          <select name="jabatan" class="form-control">
            <option value="">- Pilih Jabatan -</option>
            <?php foreach($daftar_jabatan_arr as $j): ?>
            <option value="<?= $j['id'] ?>" <?= ($data_user['jabatan']==$j['nama_jabatan'])?'selected':'' ?>><?= htmlspecialchars($j['nama_jabatan']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-building"></i> Unit Kerja</label>
          <select name="unit_kerja" class="form-control">
            <option value="">- Pilih Unit Kerja -</option>
            <?php foreach($daftar_unit_arr as $u): ?>
            <option value="<?= $u['id'] ?>" <?= ($data_user['unit_kerja']==$u['nama_unit'])?'selected':'' ?>><?= htmlspecialchars($u['nama_unit']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-person-check"></i> Atasan</label>
          <select name="atasan_id" class="form-control">
            <option value="">- Tidak Ada -</option>
            <?php foreach($daftar_atasan_arr as $a): ?>
            <option value="<?= $a['id'] ?>" <?= ($data_user['atasan_id']==$a['id'])?'selected':'' ?>><?= htmlspecialchars($a['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-toggle-on"></i> Status Karyawan</label>
          <select name="status_karyawan_id" class="form-control" required>
            <option value="">- Pilih Status -</option>
            <?php foreach($daftar_status_arr as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($data_user['status_karyawan_id']==$s['id'])?'selected':'' ?>><?= htmlspecialchars($s['nama_status']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-2">
          <label><i class="bi bi-key"></i> Password Baru</label>
          <input type="password" name="password_baru" class="form-control" placeholder="Kosongkan jika tidak ingin ganti">
        </div>
      </div>
      <div class="text-right mt-2">
        <button type="submit" name="update" class="btn btn-success">Simpan</button>
        <button type="button" class="btn btn-secondary" onclick="toggleFormAkun()">Batal</button>
      </div>
    </form>

    <!-- Tampilan Data -->
    <div id="dataViewAkun" class="row">
      <?php foreach($fields as $key=>$f): ?>
      <div class="col-md-6">
        <ul class="list-group list-group-flush mb-2">
          <li class="list-group-item">
            <i class="bi <?= $f['icon'] ?>"></i> <strong><?= $f['label'] ?></strong> : 
            <?php 
              if($key=='atasan'){
                  echo $nama_atasan;
              } elseif($key=='status_karyawan'){
                  echo $status_karyawan;
              } elseif($key=='created_at'){
                  echo date('d-m-Y H:i',strtotime($data_user[$key]));
              } else {
                  echo htmlspecialchars($data_user[$key]);
              }
            ?>
          </li>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
  <div class="card-footer text-right" id="editButtonAkun">
    <button class="btn btn-primary" onclick="toggleFormAkun()">Edit Akun</button>
  </div>
</div>

<?php if (!empty($notif)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '<?= $notif ?>',
    showConfirmButton: false,
    timer: 2000,
    position: 'center'
});
</script>
<?php endif; ?>

<script>
function toggleFormAkun() {
  const form = document.getElementById('formEditAkun');
  const view = document.getElementById('dataViewAkun');
  const editBtn = document.getElementById('editButtonAkun');
  const isEditing = form.style.display === 'block';
  form.style.display = isEditing ? 'none' : 'block';
  view.style.display = isEditing ? 'block' : 'none';
  editBtn.style.display = isEditing ? 'block' : 'none';
}
</script>
