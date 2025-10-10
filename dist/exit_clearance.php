<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// ========================
// Cek hak akses halaman
// ========================
$qAkses = "SELECT 1 FROM akses_menu 
           JOIN menu ON akses_menu.menu_id = menu.id 
           WHERE akses_menu.user_id = '$user_id' 
             AND menu.file_menu = '$current_file'";
$rAkses = mysqli_query($conn, $qAkses);
if (mysqli_num_rows($rAkses) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// ========================
// Ambil semua data karyawan (users) untuk pilihan
// ========================
$karyawanList = mysqli_query($conn, "SELECT id, nik, nama, jabatan, unit_kerja FROM users ORDER BY nama ASC");

// simpan array karyawan untuk dropdown penerima
$karyawanData = [];
mysqli_data_seek($karyawanList, 0);
while ($row = mysqli_fetch_assoc($karyawanList)) {
    $karyawanData[] = $row;
}

// ========================
// Proses simpan form
// ========================
if (isset($_POST['simpan'])) {
    $id_karyawan = intval($_POST['id_karyawan']);
    $tgl_resign  = mysqli_real_escape_string($conn, $_POST['tgl_resign']);

    // Ambil detail user dari tabel users
    $qUser  = mysqli_query($conn, "SELECT nik, nama, jabatan, unit_kerja FROM users WHERE id = '$id_karyawan'");
    $uData  = mysqli_fetch_assoc($qUser);

    $nik        = $uData['nik'];
    $nama       = $uData['nama'];
    $jabatan    = $uData['jabatan'];
    $unit_kerja = $uData['unit_kerja'];

    $aset = json_encode($_POST['aset']);
$serah_terima = json_encode([
    'checklist' => $_POST['checklist'],
    'dokumen'   => $_POST['dokumen'],
    'penerima'  => $_POST['penerima'],
    'tgl_serah' => $_POST['tgl_serah'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sql = "INSERT INTO exit_clearance 
            (user_id, nik, nama, jabatan, unit_kerja, tgl_resign, aset, serah_terima, created_at) 
            VALUES 
            ('$id_karyawan','$nik','$nama','$jabatan','$unit_kerja','$tgl_resign','$aset','$serah_terima',NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data exit clearance berhasil disimpan'); window.location.href='exit_clearance.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data: " . mysqli_error($conn) . "');</script>";
    }
}

// ========================
// Ambil data exit clearance tersimpan
// ========================
$dataExit = mysqli_query($conn, "SELECT * FROM exit_clearance ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Exit Clearance</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />

  <style>
    .table thead th { background-color: #000 !important; color: #fff !important; }
    .scroll-x { overflow-x: auto; }
    .wide-table { min-width: 1200px; } /* tabel panjang ke kanan */
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
            <div class="card-header">
              <h4><i class="fas fa-user-check text-primary mr-2"></i> Exit Clearance</h4>
            </div>

            <div class="card-body">
              <!-- Nav Tabs -->
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Exit Clearance</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Tersimpan</a>
                </li>
              </ul>

              <div class="tab-content mt-3" id="myTabContent">
                <!-- Tab Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                  <form method="POST" action="">
                    <h5 class="mb-3 text-primary">1. Identitas Karyawan</h5>
                    <div class="form-group col-md-6">
                      <label>Pilih Karyawan</label>
                      <select name="id_karyawan" id="id_karyawan" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php foreach ($karyawanData as $row) { ?>
                          <option value="<?= $row['id']; ?>"
                            data-nik="<?= $row['nik']; ?>"
                            data-nama="<?= $row['nama']; ?>"
                            data-jabatan="<?= $row['jabatan']; ?>"
                            data-unit="<?= $row['unit_kerja']; ?>">
                            <?= $row['nik']." - ".$row['nama']; ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>

                    <div class="row">
                      <div class="form-group col-md-3">
                        <label>NIK</label>
                        <input type="text" id="nik" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Nama</label>
                        <input type="text" id="nama" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Jabatan</label>
                        <input type="text" id="jabatan" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Departemen</label>
                        <input type="text" id="unit_kerja" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Tanggal Efektif Resign</label>
                        <input type="date" name="tgl_resign" class="form-control" required>
                      </div>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">2. Pengembalian Aset Perusahaan</h5>
                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Jenis Aset</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th>Penerima</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $aset_list = [
                            ["Laptop / PC", "Merk, Serial Number"],
                            ["ID Card / Access Card", "Nomor ID"],
                            ["Seragam / Jaket", "Ukuran, jumlah"],
                            ["HP Kantor / SIM Card", "Provider, nomor"],
                            ["Kendaraan Operasional", "Plat nomor"],
                          ];
                          foreach ($aset_list as $i => $a) {
                            echo "<tr>
                              <td>{$a[0]}</td>
                              <td><input type='text' name='aset[$i][keterangan]' class='form-control' placeholder='{$a[1]}'></td>
                              <td>
                                <select name='aset[$i][status]' class='form-control'>
                                  <option value=''>Pilih</option>
                                  <option value='Sudah'>Sudah</option>
                                  <option value='Belum'>Belum</option>
                                </select>
                              </td>
                              <td>
                                <select name='aset[$i][penerima]' class='form-control'>
                                  <option value=''>-- Pilih Penerima --</option>";
                                  foreach ($karyawanData as $k) {
                                    echo "<option value='{$k['nama']}'>{$k['nama']} - {$k['jabatan']}</option>";
                                  }
                            echo "  </select>
                              </td>
                              <input type='hidden' name='aset[$i][jenis]' value='{$a[0]}'>
                            </tr>";
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>

                    <hr>
                    <h5 class="mb-3 text-primary">3. Penyelesaian Pekerjaan / Serah Terima</h5>
                    <div class="row">
                      <div class="form-group col-md-12">
                        <label>Checklist tugas yang sudah diselesaikan</label>
                        <textarea name="checklist" class="form-control" rows="3"></textarea>
                      </div>
                      <div class="form-group col-md-12">
                        <label>Dokumen atau file yang sudah diserahkan</label>
                        <textarea name="dokumen" class="form-control" rows="3"></textarea>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Nama penerima tugas</label>
                        <select name="penerima" class="form-control" required>
                          <option value="">-- Pilih Penerima --</option>
                          <?php foreach ($karyawanData as $k) { ?>
                            <option value="<?= $k['nama']; ?>"><?= $k['nama']." - ".$k['jabatan']; ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Tanggal serah terima</label>
                        <input type="date" name="tgl_serah" class="form-control">
                      </div>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Exit Clearance</button>
                  </form>
                </div>

                <!-- Tab Data -->
              <!-- Tab Data -->
<div class="tab-pane fade" id="data" role="tabpanel">
  <h5 class="mb-3 text-primary">Data Exit Clearance Tersimpan</h5>
  <div class="scroll-x">
    <table class="table table-bordered wide-table">
      <thead>
        <tr>
          <th>NIK</th>
          <th>Nama</th>
          <th>Jabatan</th>
          <th>Departemen</th>
          <th>Tanggal Resign</th>
          <th>Aset</th>
          <th>Serah Terima</th>
          <th>Dibuat</th>
          <th>Aksi</th> <!-- Kolom baru -->
        </tr>
      </thead>
      <tbody>
    <?php while ($d = mysqli_fetch_assoc($dataExit)) { 
  $aset  = json_decode($d['aset'], true);
  $serah = json_decode($d['serah_terima'], true); // cukup begini saja
?>

  <tr>
    <td><?= $d['nik']; ?></td>
    <td><?= $d['nama']; ?></td>
    <td><?= $d['jabatan']; ?></td>
    <td><?= $d['unit_kerja']; ?></td>
    <td><?= $d['tgl_resign']; ?></td>
    <td>
      <ul>
        <?php if ($aset) { foreach ($aset as $a) { ?>
          <li><?= $a['jenis']; ?> (<?= $a['keterangan']; ?>) - <?= $a['status']; ?>, Penerima: <?= $a['penerima']; ?></li>
        <?php } } ?>
      </ul>
    </td>
    <td>
    <?php if ($serah) { ?>
  <ul style="padding-left:18px; margin:0;">
    <?php if (!empty($serah['checklist'])) { ?>
      <li><strong>Checklist:</strong><br><?= nl2br(htmlspecialchars($serah['checklist'])); ?></li>
    <?php } ?>
    <?php if (!empty($serah['dokumen'])) { ?>
      <li><strong>Dokumen:</strong><br><?= nl2br(htmlspecialchars($serah['dokumen'])); ?></li>
    <?php } ?>
    <?php if (!empty($serah['penerima'])) { ?>
      <li><strong>Penerima:</strong> <?= htmlspecialchars($serah['penerima']); ?></li>
    <?php } ?>
    <?php if (!empty($serah['tgl_serah'])) { ?>
      <li><strong>Tgl Serah:</strong> <?= htmlspecialchars($serah['tgl_serah']); ?></li>
    <?php } ?>
  </ul>
<?php } else { ?>
  <i>Tidak ada data</i>
<?php } ?>

    </td>
    <td><?= $d['created_at']; ?></td>
    <td>
      <a href="cetak_exit_clearance.php?id=<?= $d['id']; ?>" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-print"></i>
      </a>
    </td>
  </tr>
<?php } ?>


      </tbody>
    </table>
  </div>
</div>

              <!-- End Tab Content -->
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="assets/modules/jquery.min.js"></script>
<script>
  // Auto isi field berdasarkan pilihan karyawan
  $('#id_karyawan').on('change', function() {
    var opt = $(this).find(':selected');
    $('#nik').val(opt.data('nik'));
    $('#nama').val(opt.data('nama'));
    $('#jabatan').val(opt.data('jabatan'));
    $('#unit_kerja').val(opt.data('unit'));
  });
</script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
