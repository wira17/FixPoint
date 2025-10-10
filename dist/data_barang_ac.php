<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// 🔒 Cek hak akses
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// 🔢 Fungsi kode otomatis
function generate_kode_ac($conn) {
  $q = mysqli_query($conn, "SELECT MAX(CAST(RIGHT(kode_ac,4) AS UNSIGNED)) AS max_no FROM data_barang_ac");
  $r = mysqli_fetch_assoc($q);
  $next = ($r && $r['max_no'] !== null) ? ((int)$r['max_no'] + 1) : 1;
  return 'RSPH-AC-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// Handler AJAX untuk kode otomatis
if (isset($_GET['action']) && $_GET['action'] === 'get_kode') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['kode' => generate_kode_ac($conn)]);
  exit;
}

// Generate awal
$kode_otomatis = generate_kode_ac($conn);

// ✅ Simpan Data Baru
if (isset($_POST['simpan'])) {
  $kode_ac   = generate_kode_ac($conn);
  $lokasi     = mysqli_real_escape_string($conn, $_POST['lokasi']);
  $merk       = mysqli_real_escape_string($conn, $_POST['merk']);
  $tipe       = mysqli_real_escape_string($conn, $_POST['tipe']);
  $kapasitas  = mysqli_real_escape_string($conn, $_POST['kapasitas']);
  $no_seri    = mysqli_real_escape_string($conn, $_POST['no_seri']);
  $tahun_beli = mysqli_real_escape_string($conn, $_POST['tahun_beli']);
  $kondisi    = mysqli_real_escape_string($conn, $_POST['kondisi']);
  $status     = mysqli_real_escape_string($conn, $_POST['status']);
  $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
  $tgl_input  = date('Y-m-d H:i:s');

  $query = "INSERT INTO data_barang_ac (
    user_id, kode_ac, lokasi, merk, tipe, kapasitas, no_seri, tahun_beli, kondisi, status, keterangan, waktu_input
  ) VALUES (
    '$user_id', '$kode_ac', '$lokasi', '$merk', '$tipe', '$kapasitas', '$no_seri', '$tahun_beli', '$kondisi', '$status', '$keterangan', '$tgl_input'
  )";

  if (mysqli_query($conn, $query)) {
    $_SESSION['flash_message'] = "✅ Data AC berhasil disimpan dengan kode <b>$kode_ac</b>.";
    echo "<script>location.href='data_barang_ac.php';</script>";
    exit;
  } else {
    $_SESSION['flash_message'] = "❌ Gagal menyimpan data: " . mysqli_error($conn);
  }
}

// Ambil data
$lokasi_query = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
$data_ac = mysqli_query($conn, "SELECT * FROM data_barang_ac ORDER BY waktu_input DESC");
$rekap_kondisi = mysqli_query($conn, "SELECT kondisi, COUNT(*) AS jumlah FROM data_barang_ac GROUP BY kondisi");
$rekap_status  = mysqli_query($conn, "SELECT status, COUNT(*) AS jumlah FROM data_barang_ac GROUP BY status");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Barang AC Ruangan</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <style>
    #notif-toast { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; display: none; min-width: 300px; }
    .table thead th { background-color: #000; color: #fff; }
    .table-nowrap td, .table-nowrap th { white-space: nowrap; }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; include 'sidebar.php'; ?>
    <div class="main-content">
      <section class="section">
        <div class="section-body">

          <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="notif-toast" class="alert alert-info text-center"><?= $_SESSION['flash_message']; ?></div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header"><h4>Manajemen Data Barang AC Ruangan</h4></div>
            <div class="card-body">

              <!-- Tabs -->
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#input">Input Data</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#data">Data AC</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#laporan">Laporan</a></li>
              </ul>

              <div class="tab-content mt-3">
                <!-- 🔹 Tab Input -->
                <div class="tab-pane fade show active" id="input">
                  <form method="POST">
                    <div class="form-row">
                      <div class="form-group col-md-4">
                        <label>Kode AC (Otomatis)</label>
                        <input type="text" name="kode_ac" id="kode_ac_input" class="form-control" 
                          value="<?= htmlspecialchars($kode_otomatis) ?>" readonly>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Merk</label>
                        <input type="text" name="merk" class="form-control" placeholder="Contoh : Daikin, Sharp, LG" required>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Tipe</label>
                        <input type="text" name="tipe" class="form-control" placeholder="Contoh : Split, Wall, Cassette">
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-4">
                        <label>Kapasitas (PK)</label>
                        <input type="text" name="kapasitas" class="form-control" placeholder="Contoh : 1, 1.5, 2">
                      </div>
                      <div class="form-group col-md-4">
                        <label>No. Seri</label>
                        <input type="text" name="no_seri" class="form-control" placeholder="Contoh : SN123456789">
                      </div>
                      <div class="form-group col-md-4">
                        <label>Tahun Pembelian</label>
                        <input type="number" name="tahun_beli" class="form-control" min="2000" max="<?= date('Y'); ?>" placeholder="Contoh : 2019">
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label>Lokasi</label>
                        <select name="lokasi" class="form-control" required>
                          <option value="">-- Pilih Lokasi --</option>
                          <?php while ($row = mysqli_fetch_assoc($lokasi_query)) : ?>
                            <option value="<?= htmlspecialchars($row['nama_unit']) ?>"><?= htmlspecialchars($row['nama_unit']) ?></option>
                          <?php endwhile; ?>
                        </select>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Kondisi</label>
                        <select name="kondisi" class="form-control" required>
                          <option value="Baik">Baik</option>
                          <option value="Perlu Servis">Perlu Servis</option>
                          <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                          <option value="Aktif">Aktif</option>
                          <option value="Nonaktif">Nonaktif</option>
                        </select>
                      </div>
                    </div>

                    <div class="form-group">
                      <label>Keterangan</label>
                      <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Keterangan Detail dari barang"></textarea>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                  </form>
                </div>

                <!-- 🔹 Tab Data -->
                <div class="tab-pane fade" id="data">
                  <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped table-nowrap">
                      <thead>
                        <tr>
                          <th>No</th><th>Kode</th><th>Merk</th><th>Tipe</th><th>PK</th><th>No Seri</th>
                          <th>Tahun</th><th>Lokasi</th><th>Kondisi</th><th>Status</th><th>Keterangan</th><th>Tgl Input</th><th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; while ($ac=mysqli_fetch_assoc($data_ac)): ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= $ac['kode_ac'] ?></td>
                          <td><?= $ac['merk'] ?></td>
                          <td><?= $ac['tipe'] ?></td>
                          <td><?= $ac['kapasitas'] ?></td>
                          <td><?= $ac['no_seri'] ?></td>
                          <td><?= $ac['tahun_beli'] ?></td>
                          <td><?= $ac['lokasi'] ?></td>
                          <td><?= $ac['kondisi'] ?></td>
                          <td><?= $ac['status'] ?></td>
                          <td><?= $ac['keterangan'] ?></td>
                          <td><?= date('d-m-Y H:i', strtotime($ac['waktu_input'])) ?></td>
                          <td>
                            <button class="btn btn-warning btn-sm editBtn"
                              data-id="<?= $ac['id'] ?>"
                              data-kode="<?= $ac['kode_ac'] ?>"
                              data-merk="<?= $ac['merk'] ?>"
                              data-tipe="<?= $ac['tipe'] ?>"
                              data-kapasitas="<?= $ac['kapasitas'] ?>"
                              data-noseri="<?= $ac['no_seri'] ?>"
                              data-tahun="<?= $ac['tahun_beli'] ?>"
                              data-lokasi="<?= $ac['lokasi'] ?>"
                              data-kondisi="<?= $ac['kondisi'] ?>"
                              data-status="<?= $ac['status'] ?>"
                              data-keterangan="<?= htmlspecialchars($ac['keterangan']) ?>">
                              <i class="fas fa-edit"></i>
                            </button>
                          </td>
                        </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- 🔹 Tab Laporan (rekap kondisi/status tetap) -->
                <div class="tab-pane fade" id="laporan">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header bg-primary text-white"><h5>Rekap Kondisi</h5></div>
                        <div class="card-body">
                          <ul class="list-group">
                            <?php while ($k=mysqli_fetch_assoc($rekap_kondisi)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <?= $k['kondisi'] ?><span class="badge badge-primary badge-pill"><?= $k['jumlah'] ?></span>
                            </li>
                            <?php endwhile; ?>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header bg-success text-white"><h5>Rekap Status</h5></div>
                        <div class="card-body">
                          <ul class="list-group">
                            <?php while ($s=mysqli_fetch_assoc($rekap_status)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <?= $s['status'] ?><span class="badge badge-success badge-pill"><?= $s['jumlah'] ?></span>
                            </li>
                            <?php endwhile; ?>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div><!-- end tab-content -->
            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</div>

<!-- 🔸 Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="editForm">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white"><h5><i class="fas fa-edit"></i> Edit Data AC</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="form-row">
            <div class="form-group col-md-4"><label>Kode AC</label><input type="text" name="kode_ac" id="edit_kode" class="form-control" readonly></div>
            <div class="form-group col-md-4"><label>Merk</label><input type="text" name="merk" id="edit_merk" class="form-control" required></div>
            <div class="form-group col-md-4"><label>Tipe</label><input type="text" name="tipe" id="edit_tipe" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4"><label>Kapasitas (PK)</label><input type="text" name="kapasitas" id="edit_kapasitas" class="form-control"></div>
            <div class="form-group col-md-4"><label>No. Seri</label><input type="text" name="no_seri" id="edit_noseri" class="form-control"></div>
            <div class="form-group col-md-4"><label>Tahun Pembelian</label><input type="number" name="tahun_beli" id="edit_tahun" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6"><label>Lokasi</label>
              <select name="lokasi" id="edit_lokasi" class="form-control">
                <?php $lokasi_query2=mysqli_query($conn,"SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
                while($row=mysqli_fetch_assoc($lokasi_query2)): ?>
                <option value="<?= $row['nama_unit']?>"><?= $row['nama_unit']?></option>
                <?php endwhile;?>
              </select>
            </div>
            <div class="form-group col-md-3"><label>Kondisi</label>
              <select name="kondisi" id="edit_kondisi" class="form-control">
                <option value="Baik">Baik</option><option value="Perlu Servis">Perlu Servis</option><option value="Rusak Berat">Rusak Berat</option>
              </select>
            </div>
            <div class="form-group col-md-3"><label>Status</label>
              <select name="status" id="edit_status" class="form-control">
                <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script>
$(function(){
  if($('#notif-toast').length) $('#notif-toast').fadeIn(300).delay(2000).fadeOut(500);

  function refreshKode(){ $.getJSON('data_barang_ac.php?action=get_kode', res=>$('#kode_ac_input').val(res.kode)); }
  refreshKode();
  $('a[data-toggle="tab"]').on('shown.bs.tab', e=>{ if($(e.target).attr("href")==="#input") refreshKode(); });

  $(document).on('click','.editBtn',function(){
    $('#edit_id').val($(this).data('id'));
    $('#edit_kode').val($(this).data('kode'));
    $('#edit_merk').val($(this).data('merk'));
    $('#edit_tipe').val($(this).data('tipe'));
    $('#edit_kapasitas').val($(this).data('kapasitas'));
    $('#edit_noseri').val($(this).data('noseri'));
    $('#edit_tahun').val($(this).data('tahun'));
    $('#edit_lokasi').val($(this).data('lokasi'));
    $('#edit_kondisi').val($(this).data('kondisi'));
    $('#edit_status').val($(this).data('status'));
    $('#edit_keterangan').val($(this).data('keterangan'));
    $('#editModal').modal('show');
  });

  $('#editForm').on('submit',function(e){
    e.preventDefault();
    $.post('update_barang_ac.php', $(this).serialize(), function(res){
      alert(res); location.reload();
    }).fail(()=>alert('Gagal terhubung ke server.'));
  });
});
</script>
</body>
</html>
