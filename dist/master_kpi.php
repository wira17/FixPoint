<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// === CEK AKSES MENU ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' 
          AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// === AMBIL UNIT KERJA USER LOGIN ===
$q_user = mysqli_query($conn, "SELECT unit_kerja FROM users WHERE id='$user_id'");
$data_user = mysqli_fetch_assoc($q_user);
$unit_kerja_user = $data_user['unit_kerja'] ?? 'Tidak Diketahui';

// === PROSES TAMBAH / EDIT / HAPUS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    $kode = mysqli_real_escape_string($conn, $_POST['kode_indikator']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_indikator']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    $rumus = mysqli_real_escape_string($conn, $_POST['rumus']);
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $target = mysqli_real_escape_string($conn, $_POST['target']);
    $unit_kerja = mysqli_real_escape_string($conn, $unit_kerja_user);

    if ($aksi === 'tambah') {
        $sql = "INSERT INTO master_indikator_kpi (kode_indikator, nama_indikator, jenis, rumus, satuan, target, unit_kerja)
                VALUES ('$kode', '$nama', '$jenis', '$rumus', '$satuan', '$target', '$unit_kerja')";
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Data KPI berhasil ditambahkan'); window.location.href='master_kpi.php';</script>";
        } else {
            echo "<script>alert('Gagal menambah data: " . mysqli_error($conn) . "');</script>";
        }
    }

    if ($aksi === 'update') {
        $id = $_POST['id'];
        $sql = "UPDATE master_indikator_kpi 
                SET kode_indikator='$kode', nama_indikator='$nama', jenis='$jenis', rumus='$rumus', satuan='$satuan', target='$target'
                WHERE id='$id'";
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Data KPI berhasil diperbarui'); window.location.href='master_kpi.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui data: " . mysqli_error($conn) . "');</script>";
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $sql = "DELETE FROM master_indikator_kpi WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data KPI berhasil dihapus'); window.location.href='kpi.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($conn) . "');</script>";
    }
}

// === TAMPILKAN DATA KPI ===
$query_kpi = "SELECT * FROM master_indikator_kpi WHERE unit_kerja='$unit_kerja_user' ORDER BY id DESC";
$result_kpi = mysqli_query($conn, $query_kpi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Master Indikator KPI</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
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
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4>Data Indikator KPI - <?= htmlspecialchars($unit_kerja_user); ?></h4>
              <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus-circle"></i> Tambah Indikator
              </button>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>Kode</th>
                      <th>Nama Indikator</th>
                      <th>Jenis</th>
                      <th>Rumus</th>
                      <th>Satuan</th>
                      <th>Target</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (mysqli_num_rows($result_kpi) > 0) {
                      $no = 1;
                      while ($row = mysqli_fetch_assoc($result_kpi)) {
                        echo "
                        <tr>
                          <td class='text-center'>$no</td>
                          <td>{$row['kode_indikator']}</td>
                          <td>{$row['nama_indikator']}</td>
                          <td>{$row['jenis']}</td>
                          <td>{$row['rumus']}</td>
                          <td>{$row['satuan']}</td>
                          <td>{$row['target']}</td>
                          <td class='text-center'>
                            <button class='btn btn-warning btn-sm editBtn' 
                              data-id='{$row['id']}' 
                              data-kode='{$row['kode_indikator']}'
                              data-nama='{$row['nama_indikator']}'
                              data-jenis='{$row['jenis']}'
                              data-rumus='{$row['rumus']}'
                              data-satuan='{$row['satuan']}'
                              data-target='{$row['target']}'>
                              <i class='fas fa-edit'></i>
                            </button>
                            <a href='kpi.php?hapus={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus data ini?\")'>
                              <i class='fas fa-trash'></i>
                            </a>
                          </td>
                        </tr>";
                        $no++;
                      }
                    } else {
                      echo "<tr><td colspan='8' class='text-center text-muted'>Belum ada data indikator KPI.</td></tr>";
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
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalTambahLabel"><i class="fas fa-plus-circle"></i> Tambah Indikator KPI</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Kode Indikator</label>
            <input type="text" name="kode_indikator" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Nama Indikator</label>
            <input type="text" name="nama_indikator" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Jenis</label>
            <select name="jenis" class="form-control" required>
              <option value="Proses">Proses</option>
              <option value="Output">Output</option>
              <option value="Outcome">Outcome</option>
            </select>
          </div>
          <div class="form-group">
            <label>Rumus</label>
            <textarea name="rumus" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Satuan</label>
            <input type="text" name="satuan" class="form-control">
          </div>
          <div class="form-group">
            <label>Target</label>
            <input type="text" name="target" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST">
      <input type="hidden" name="aksi" value="update">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title" id="modalEditLabel"><i class="fas fa-edit"></i> Edit Indikator KPI</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Kode Indikator</label>
            <input type="text" name="kode_indikator" id="edit_kode" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Nama Indikator</label>
            <input type="text" name="nama_indikator" id="edit_nama" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Jenis</label>
            <select name="jenis" id="edit_jenis" class="form-control" required>
              <option value="Proses">Proses</option>
              <option value="Output">Output</option>
              <option value="Outcome">Outcome</option>
            </select>
          </div>
          <div class="form-group">
            <label>Rumus</label>
            <textarea name="rumus" id="edit_rumus" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Satuan</label>
            <input type="text" name="satuan" id="edit_satuan" class="form-control">
          </div>
          <div class="form-group">
            <label>Target</label>
            <input type="text" name="target" id="edit_target" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Update</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
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
$(document).on("click", ".editBtn", function () {
  $("#edit_id").val($(this).data('id'));
  $("#edit_kode").val($(this).data('kode'));
  $("#edit_nama").val($(this).data('nama'));
  $("#edit_jenis").val($(this).data('jenis'));
  $("#edit_rumus").val($(this).data('rumus'));
  $("#edit_satuan").val($(this).data('satuan'));
  $("#edit_target").val($(this).data('target'));
  $("#modalEdit").modal('show');
});
</script>
</body>
</html>
