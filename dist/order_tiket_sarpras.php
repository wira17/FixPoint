<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); 

// === Cek hak akses menu ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// === Ambil data user login ===
$queryUser = mysqli_query($conn, "SELECT nik, nama, jabatan, unit_kerja FROM users WHERE id = '$user_id'");
$userData = mysqli_fetch_assoc($queryUser);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" />
  <title>Order Tiket Sarpras</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <style>
    .table thead th {
      background-color: #000;
      color: #fff;
      white-space: nowrap;
    }
    .table td {
      vertical-align: middle;
      white-space: nowrap;
    }
    .table-responsive-custom {
      width: 100%;
      overflow-x: auto;
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
          <div class="card">
            <div class="card-header">
              <h4>Order Tiket Sarpras</h4>
            </div>
            <div class="card-body">
              
              <!-- Tabs -->
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="order-tab" data-toggle="tab" href="#order" role="tab">Order Tiket</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tiket-saya-tab" data-toggle="tab" href="#tiket-saya" role="tab">Tiket Saya</a>
                </li>
              </ul>

              <div class="tab-content mt-4" id="myTabContent">

                <!-- =================== FORM ORDER =================== -->
                <div class="tab-pane fade show active" id="order" role="tabpanel">
                  <form method="POST" action="simpan_tiket_sarpras.php">
                    <div class="row">
                      <div class="form-group col-md-3">
                        <label>NIK</label>
                        <input type="text" name="nik" value="<?= htmlspecialchars($userData['nik']); ?>" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Nama</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($userData['nama']); ?>" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" value="<?= htmlspecialchars($userData['jabatan']); ?>" class="form-control" readonly>
                      </div>
                      <div class="form-group col-md-3">
                        <label>Unit Kerja</label>
                        <input type="text" name="unit_kerja" value="<?= htmlspecialchars($userData['unit_kerja']); ?>" class="form-control" readonly>
                      </div>

                      <div class="form-group col-md-6">
                        <label>Kategori Permintaan</label>
                        <select name="kategori" class="form-control" required>
                          <option value="">-- Pilih Kategori --</option>
                          <option value="Perbaikan AC">Perbaikan AC</option>
                          <option value="Pengecekan AC">Pengecekan AC</option>
                          <option value="Perbaikan Listrik">Perbaikan Listrik</option>
                          <option value="Perbaikan Furniture">Perbaikan Furniture</option>
                          <option value="Lainnya">Lainnya</option>
                        </select>
                      </div>

                    <div class="form-group col-md-6">
  <label>Lokasi / Ruangan</label>
  <select name="lokasi" class="form-control" required>
    <option value="">-- Pilih Lokasi / Unit --</option>
    <?php
    // Ambil data unit_kerja dari database
    $queryUnit = mysqli_query($conn, "SELECT id, nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
    while ($unit = mysqli_fetch_assoc($queryUnit)) {
        echo "<option value=\"" . htmlspecialchars($unit['nama_unit']) . "\">" . htmlspecialchars($unit['nama_unit']) . "</option>";
    }
    ?>
  </select>
</div>


                      <div class="form-group col-md-12">
                        <label>Kendala / Laporan</label>
                        <textarea name="kendala" class="form-control" rows="3" placeholder="Tuliskan kendala atau permintaan..." required></textarea>
                      </div>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Tiket</button>
                  </form>
                </div>

                <!-- =================== DAFTAR TIKET =================== -->
                <div class="tab-pane fade" id="tiket-saya" role="tabpanel">
                  <div class="table-responsive-custom">
                    <table class="table table-bordered table-striped">
                     <thead>
  <tr>
    <th>No</th>
    <th>Nomor Tiket</th>
    <th>Tanggal Input</th>
    <th>Kategori</th>
    <th>Kendala / Laporan</th>
    <th>Status</th>
    <th>Status Validasi</th>
    <th>Waktu Validasi</th>
    <th>Teknisi</th>
    <th>Catatan IT</th>
    <th>Aksi</th>
    <th>Cetak Tiket</th> <!-- Kolom baru -->
  </tr>
</thead>
<tbody>
<?php
$no = 1;
$queryTiket = mysqli_query($conn, "SELECT * FROM tiket_sarpras WHERE user_id = '$user_id' ORDER BY tanggal_input DESC");
if (mysqli_num_rows($queryTiket) > 0) {
  while ($row = mysqli_fetch_assoc($queryTiket)) {
    echo "<tr>
      <td>{$no}</td>
      <td>{$row['nomor_tiket']}</td>
      <td>" . ($row['tanggal_input'] ? date('d-m-Y H:i', strtotime($row['tanggal_input'])) : '-') . "</td>
      <td>{$row['kategori']}</td>
      <td>{$row['kendala']}</td>
      <td><span class='badge badge-" . statusColor($row['status']) . "'>{$row['status']}</span></td>
      <td><span class='badge badge-" . validasiColor($row['status_validasi']) . "'>{$row['status_validasi']}</span></td>
      <td>" . ($row['waktu_validasi'] ? date('d-m-Y H:i', strtotime($row['waktu_validasi'])) : '-') . "</td>
      <td>" . (!empty($row['teknisi_nama']) ? $row['teknisi_nama'] : '-') . "</td>
      <td>" . (!empty($row['catatan_it']) ? $row['catatan_it'] : '-') . "</td>
      <td class='text-center'>";
      
    // Tombol validasi/tolak tetap ada
    $statusLower = strtolower($row['status']);
    $validasiLower = strtolower($row['status_validasi']);
    if (($statusLower == 'selesai' || $statusLower == 'tidak bisa diperbaiki') && $validasiLower == 'belum validasi') {
      echo "
      <form method='POST' action='validasi_tiket_sarpras.php' style='display:inline-block;'>
        <input type='hidden' name='tiket_id' value='{$row['id']}'>
        <button type='submit' name='validasi' class='btn btn-success btn-sm' title='Validasi'><i class='fas fa-check'></i></button>
      </form>
      <form method='POST' action='validasi_tiket_sarpras.php' style='display:inline-block;'>
        <input type='hidden' name='tiket_id' value='{$row['id']}'>
        <button type='submit' name='tolak' class='btn btn-danger btn-sm' title='Tolak'><i class='fas fa-times'></i></button>
      </form>";
    } else {
      echo "-";
    }

    echo "</td>";

    // Tambah kolom Cetak Tiket
    echo "<td class='text-center'>
      <a href='cetak_tiket_sarpras.php?id={$row['id']}' target='_blank' class='btn btn-primary btn-sm' title='Cetak Tiket'>
        <i class='fas fa-ticket-alt'></i>
      </a>
    </td>";

    echo "</tr>";
    $no++;
  }
} else {
  echo "<tr><td colspan='12' class='text-center'>Belum ada tiket yang dibuat.</td></tr>";
}

// Fungsi warna status
function statusColor($status) {
  switch (strtolower($status)) {
    case 'menunggu': return 'warning';
    case 'diproses': return 'info';
    case 'selesai': return 'success';
    case 'tidak bisa diperbaiki': return 'danger';
    default: return 'secondary';
  }
}

function validasiColor($status_validasi) {
  switch (strtolower($status_validasi)) {
    case 'belum validasi': return 'secondary';
    case 'diterima': return 'success';
    case 'ditolak': return 'danger';
    default: return 'light';
  }
}
?>
</tbody>

                    </table>
                  </div>
                </div>
              </div><!-- end tab -->
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- JS -->
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
