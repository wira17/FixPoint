<?php
// master_indikator_unit.php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$nama_user = $_SESSION['nama_user'] ?? '';
$activeTab = 'data';
$modals = [];

// akses menu
$current_file = basename(__FILE__);
$rAkses = mysqli_query($conn, "SELECT 1 FROM akses_menu 
            JOIN menu ON akses_menu.menu_id = menu.id 
            WHERE akses_menu.user_id = '".intval($user_id)."' 
              AND menu.file_menu = '".mysqli_real_escape_string($conn,$current_file)."'");
if (!$rAkses || mysqli_num_rows($rAkses) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// proses simpan
if (isset($_POST['simpan'])) {
    $id_rs           = intval($_POST['id_rs']);
    $unit_id         = intval($_POST['unit_id']);
    $nama_indikator  = mysqli_real_escape_string($conn, $_POST['nama_indikator']);
    $definisi        = mysqli_real_escape_string($conn, $_POST['definisi']);
    $numerator       = mysqli_real_escape_string($conn, $_POST['numerator']);
    $denominator     = mysqli_real_escape_string($conn, $_POST['denominator']);
    $standar         = mysqli_real_escape_string($conn, $_POST['standar']);
    $sumber_data     = mysqli_real_escape_string($conn, $_POST['sumber_data']);
    $frekuensi       = mysqli_real_escape_string($conn, $_POST['frekuensi']);
    $penanggung_jawab= intval($_POST['penanggung_jawab']); // ambil ID user

    if ($nama_indikator && $standar && $unit_id) {
        $q = "INSERT INTO indikator_unit 
                (id_rs, unit_id, nama_indikator, definisi, numerator, denominator, standar, sumber_data, frekuensi, penanggung_jawab) 
              VALUES 
                (" . ($id_rs ? $id_rs : "NULL") . ", 
                 '$unit_id', 
                 '$nama_indikator', 
                 '$definisi', 
                 '$numerator', 
                 '$denominator', 
                 '$standar', 
                 '$sumber_data', 
                 '$frekuensi', 
                 '$penanggung_jawab')";
        if (mysqli_query($conn, $q)) {
            $_SESSION['flash_message'] = "Data berhasil disimpan.";
            $activeTab = 'data';
        } else {
            $_SESSION['flash_message'] = "Gagal menyimpan data: " . mysqli_error($conn);
            $activeTab = 'input';
        }
    } else {
        $_SESSION['flash_message'] = "Lengkapi semua field!";
        $activeTab = 'input';
    }
}

// proses update
if (isset($_POST['update'])) {
    $id_unit        = intval($_POST['id_unit']);
    $id_rs          = intval($_POST['id_rs']) ?: "NULL";
    $unit_id        = intval($_POST['unit_id']);
    $nama_indikator = mysqli_real_escape_string($conn, $_POST['nama_indikator']);
    $definisi       = mysqli_real_escape_string($conn, $_POST['definisi']);
    $numerator      = mysqli_real_escape_string($conn, $_POST['numerator']);
    $denominator    = mysqli_real_escape_string($conn, $_POST['denominator']);
    $standar        = mysqli_real_escape_string($conn, $_POST['standar']);
    $sumber_data    = mysqli_real_escape_string($conn, $_POST['sumber_data']);
    $frekuensi      = mysqli_real_escape_string($conn, $_POST['frekuensi']);
    $pj_id          = intval($_POST['penanggung_jawab']);

    $q = "UPDATE indikator_unit SET
          id_rs=$id_rs,
          unit_id='$unit_id',
          nama_indikator='$nama_indikator',
          definisi='$definisi',
          numerator='$numerator',
          denominator='$denominator',
          standar='$standar',
          sumber_data='$sumber_data',
          frekuensi='$frekuensi',
          penanggung_jawab='$pj_id'
          WHERE id_unit='$id_unit'";
    if (mysqli_query($conn, $q)) {
        $_SESSION['flash_message'] = "Data berhasil diperbarui.";
    } else {
        $_SESSION['flash_message'] = "Gagal memperbarui data: " . mysqli_error($conn);
    }
    header("Location: master_indikator_unit.php");
    exit;
}

// hapus data
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM indikator_unit WHERE id_unit='$id'");
    $_SESSION['flash_message'] = "Data berhasil dihapus.";
    header("Location: master_indikator_unit.php");
    exit;
}

// ambil indikator RS
$indikatorRS = mysqli_query($conn, "SELECT id_rs, nama_indikator FROM indikator_rs ORDER BY nama_indikator");
// ambil unit kerja
$units = mysqli_query($conn, "SELECT id, nama_unit FROM unit_kerja ORDER BY nama_unit");
// ambil user sebagai penanggung jawab
$users = mysqli_query($conn, "SELECT id, nama FROM users ORDER BY nama");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Master Indikator Unit</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .dokumen-table { font-size: 13px; white-space: nowrap; }
    .dokumen-table th, .dokumen-table td { padding: 6px 10px; vertical-align: middle; }
    .flash-center {
      position: fixed; top: 20%; left: 50%; transform: translate(-50%, -50%);
      z-index: 1050; min-width: 300px; max-width: 90%; text-align: center;
      padding: 15px; border-radius: 8px; font-weight: 500;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
            <?= htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message']); ?>
          </div>
        <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4 class="mb-0">Master Indikator Unit</h4>
            </div>
            <div class="card-body">
              <ul class="nav nav-tabs" id="indikatorTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link <?= ($activeTab=='input')?'active':'' ?>" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Data</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?= ($activeTab=='data')?'active':'' ?>" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Indikator</a>
                </li>
              </ul>

              <div class="tab-content mt-3">
               <!-- FORM INPUT -->
<div class="tab-pane fade <?= ($activeTab=='input')?'show active':'' ?>" id="input" role="tabpanel">
  <form method="POST">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Indikator RS (Opsional)</label>
          <select name="id_rs" class="form-control">
            <option value="">-- Tidak terkait --</option>
            <?php while($rs = mysqli_fetch_assoc($indikatorRS)): ?>
              <option value="<?= $rs['id_rs'] ?>"><?= htmlspecialchars($rs['nama_indikator']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Unit Kerja</label>
          <select name="unit_id" class="form-control" required>
            <option value="">-- Pilih Unit --</option>
            <?php while($u = mysqli_fetch_assoc($units)): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_unit']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Nama Indikator</label>
          <input type="text" name="nama_indikator" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Definisi Operasional</label>
          <textarea name="definisi" class="form-control" required></textarea>
        </div>
        <div class="form-group">
          <label>Numerator</label>
          <textarea name="numerator" class="form-control" required></textarea>
        </div>
        <div class="form-group">
          <label>Denominator</label>
          <textarea name="denominator" class="form-control" required></textarea>
        </div>
        <div class="form-group">
          <label>Standar/Target (%)</label>
          <input type="number" step="0.01" name="standar" class="form-control" required>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Sumber Data</label>
          <input type="text" name="sumber_data" class="form-control">
        </div>
        <div class="form-group">
          <label>Frekuensi Pelaporan</label>
          <select name="frekuensi" class="form-control">
            <option value="">-- Pilih --</option>
            <option value="Harian">Harian</option>
            <option value="Mingguan">Mingguan</option>
            <option value="Bulanan">Bulanan</option>
            <option value="Triwulan">Triwulan</option>
            <option value="Tahunan">Tahunan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Penanggung Jawab</label>
          <select name="penanggung_jawab" class="form-control" required>
            <option value="">-- Pilih Penanggung Jawab --</option>
            <?php while($usr = mysqli_fetch_assoc($users)): ?>
              <option value="<?= $usr['id'] ?>"><?= htmlspecialchars($usr['nama']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
    </div>
    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
  </form>
</div>

<!-- DATA -->
<div class="tab-pane fade <?= ($activeTab=='data')?'show active':'' ?>" id="data" role="tabpanel">
  <div class="table-responsive">
    <table class="table table-bordered table-striped dokumen-table">
      <thead class="thead-light">
        <tr>
          <th>No</th>
          <th>Unit</th>
          <th>Nama Indikator</th>
          <th>Standar</th>
          <th>Frekuensi</th>
          <th>Penanggung Jawab</th>
          <th>Indikator RS</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $qInd = mysqli_query($conn, "SELECT iu.id_unit, iu.nama_indikator, iu.standar, iu.frekuensi, 
                                          u.nama_unit, rs.nama_indikator AS indikator_rs,
                                          usr.nama AS penanggung_jawab, iu.id_rs, iu.unit_id,
                                          iu.definisi, iu.numerator, iu.denominator, iu.sumber_data, iu.penanggung_jawab AS pj_id
                                   FROM indikator_unit iu
                                   LEFT JOIN unit_kerja u ON iu.unit_id=u.id
                                   LEFT JOIN indikator_rs rs ON iu.id_rs=rs.id_rs
                                   LEFT JOIN users usr ON iu.penanggung_jawab=usr.id
                                   ORDER BY iu.id_unit DESC");
      $no=1;
      while($row = mysqli_fetch_assoc($qInd)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['nama_unit']) ?></td>
          <td><?= htmlspecialchars($row['nama_indikator']) ?></td>
          <td><?= htmlspecialchars($row['standar']) ?></td>
          <td><?= htmlspecialchars($row['frekuensi']) ?></td>
          <td><?= htmlspecialchars($row['penanggung_jawab'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['indikator_rs'] ?? '-') ?></td>
          <td>
            <button class="btn btn-sm btn-warning editBtn"
                    data-id="<?= $row['id_unit'] ?>"
                    data-id_rs="<?= $row['id_rs'] ?>"
                    data-unit_id="<?= $row['unit_id'] ?>"
                    data-nama_indikator="<?= htmlspecialchars($row['nama_indikator'], ENT_QUOTES) ?>"
                    data-definisi="<?= htmlspecialchars($row['definisi'], ENT_QUOTES) ?>"
                    data-numerator="<?= htmlspecialchars($row['numerator'], ENT_QUOTES) ?>"
                    data-denominator="<?= htmlspecialchars($row['denominator'], ENT_QUOTES) ?>"
                    data-standar="<?= htmlspecialchars($row['standar'], ENT_QUOTES) ?>"
                    data-sumber_data="<?= htmlspecialchars($row['sumber_data'], ENT_QUOTES) ?>"
                    data-frekuensi="<?= htmlspecialchars($row['frekuensi'], ENT_QUOTES) ?>"
                    data-pj_id="<?= $row['pj_id'] ?>"
                    data-toggle="modal" data-target="#editModal">
              <i class="fas fa-edit"></i> Edit
            </button>
            <a href="?hapus=<?= $row['id_unit'] ?>" onclick="return confirm('Hapus data ini?')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

              </div> <!-- end tab-content -->
            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="POST" id="editForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Indikator Unit</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_unit" id="edit_id_unit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Indikator RS (Opsional)</label>
                <select name="id_rs" id="edit_id_rs" class="form-control">
                  <option value="">-- Tidak terkait --</option>
                  <?php mysqli_data_seek($indikatorRS,0); while($rs = mysqli_fetch_assoc($indikatorRS)): ?>
                    <option value="<?= $rs['id_rs'] ?>"><?= htmlspecialchars($rs['nama_indikator']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Unit Kerja</label>
                <select name="unit_id" id="edit_unit_id" class="form-control" required>
                  <option value="">-- Pilih Unit --</option>
                  <?php mysqli_data_seek($units,0); while($u = mysqli_fetch_assoc($units)): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_unit']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Nama Indikator</label>
                <input type="text" name="nama_indikator" id="edit_nama_indikator" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Definisi Operasional</label>
                <textarea name="definisi" id="edit_definisi" class="form-control" required></textarea>
              </div>
              <div class="form-group">
                <label>Numerator</label>
                <textarea name="numerator" id="edit_numerator" class="form-control" required></textarea>
              </div>
              <div class="form-group">
                <label>Denominator</label>
                <textarea name="denominator" id="edit_denominator" class="form-control" required></textarea>
              </div>
              <div class="form-group">
                <label>Standar/Target (%)</label>
                <input type="number" step="0.01" name="standar" id="edit_standar" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Sumber Data</label>
                <input type="text" name="sumber_data" id="edit_sumber_data" class="form-control">
              </div>
              <div class="form-group">
                <label>Frekuensi Pelaporan</label>
                <select name="frekuensi" id="edit_frekuensi" class="form-control">
                  <option value="">-- Pilih --</option>
                  <option value="Harian">Harian</option>
                  <option value="Mingguan">Mingguan</option>
                  <option value="Bulanan">Bulanan</option>
                  <option value="Triwulan">Triwulan</option>
                  <option value="Tahunan">Tahunan</option>
                </select>
              </div>
              <div class="form-group">
                <label>Penanggung Jawab</label>
                <select name="penanggung_jawab" id="edit_pj_id" class="form-control" required>
                  <option value="">-- Pilih Penanggung Jawab --</option>
                  <?php mysqli_data_seek($users,0); while($usr = mysqli_fetch_assoc($users)): ?>
                    <option value="<?= $usr['id'] ?>"><?= htmlspecialchars($usr['nama']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
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
  $(function(){
    setTimeout(function(){ $("#flashMsg").fadeOut("slow"); }, 2500);

    $('.editBtn').click(function(){
      $('#edit_id_unit').val($(this).data('id'));
      $('#edit_id_rs').val($(this).data('id_rs'));
      $('#edit_unit_id').val($(this).data('unit_id'));
      $('#edit_nama_indikator').val($(this).data('nama_indikator'));
      $('#edit_definisi').val($(this).data('definisi'));
      $('#edit_numerator').val($(this).data('numerator'));
      $('#edit_denominator').val($(this).data('denominator'));
      $('#edit_standar').val($(this).data('standar'));
      $('#edit_sumber_data').val($(this).data('sumber_data'));
      $('#edit_frekuensi').val($(this).data('frekuensi'));
      $('#edit_pj_id').val($(this).data('pj_id'));
    });
  });
</script>

<?php
foreach ($modals as $m) echo $m;
?>

</body>
</html>
