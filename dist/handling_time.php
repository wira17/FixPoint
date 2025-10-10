<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Filter data
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

if ($dari_tanggal) $dari_tanggal = date('Y-m-d', strtotime($dari_tanggal));
if ($sampai_tanggal) $sampai_tanggal = date('Y-m-d', strtotime($sampai_tanggal));
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>f.i.x.p.o.i.n.t</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <style>
    .table-responsive-custom { width: 100%; overflow-x: auto; }
    .table-responsive-custom table { min-width: 1500px; white-space: nowrap; }
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
              <h4><i class="fas fa-clock"></i> Data Handling Time Tiket</h4>
            </div>
            <div class="card-body">

              <!-- Tabs -->
              <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                  <a class="nav-link active" href="handling_time.php">IT Hardware</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="handling_time_software.php">IT Software</a>
                </li>
              </ul>

              <!-- Filter -->
              <form method="GET" class="form-inline mb-3">
                <div class="form-group mr-2">
                  <label class="mr-2">Dari</label>
                  <input type="date" name="dari_tanggal" class="form-control" value="<?php echo $dari_tanggal; ?>">
                </div>
                <div class="form-group mr-2">
                  <label class="mr-2">Sampai</label>
                  <input type="date" name="sampai_tanggal" class="form-control" value="<?php echo $sampai_tanggal; ?>">
                </div>
                <div class="form-group mr-2">
                  <input type="text" name="keyword" class="form-control" placeholder="Cari NIK / Nama / No Tiket"
                         value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2">Filter</button>
                <a href="handling_time.php" class="btn btn-secondary btn-sm mr-2">Reset</a>
                <a href="handling_time_hardware_pdf.php?dari_tanggal=<?php echo $dari_tanggal; ?>&sampai_tanggal=<?php echo $sampai_tanggal; ?>&keyword=<?php echo urlencode($keyword); ?>"
                   target="_blank" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> Cetak PDF</a>
              </form>

              <!-- Tabel -->
              <div class="table-responsive-custom">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>Nomor Tiket</th>
                      <th>NIK</th>
                      <th>Nama</th>
                      <th>Jabatan</th>
                      <th>Unit Kerja</th>
                      <th>Kategori</th>
                      <th>Kendala</th>
                      <th>Status</th>
                      <th>Teknisi</th>
                      <th>Tgl Input</th>
                      <th>Diproses</th>
                      <th>Selesai</th>
                      <th>Validasi</th>
                      <th>Waktu Validasi</th>
                      <th>Respon Time</th>
                      <th>Selesai Time</th>
                      <th>Validasi Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no = 1;
                    $query = "SELECT * FROM tiket_it_hardware WHERE 1=1";

                    if (!empty($keyword)) {
                      $kw = mysqli_real_escape_string($conn, $keyword);
                      $query .= " AND (nik LIKE '%$kw%' OR nama LIKE '%$kw%' OR nomor_tiket LIKE '%$kw%')";
                    }
                    if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
                      $query .= " AND DATE(tanggal_input) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
                    }

                    $query .= " ORDER BY tanggal_input DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                      while ($row = mysqli_fetch_assoc($result)) {
                        $kendala = htmlspecialchars($row['kendala']);
                        echo "<tr>";
                        echo "<td class='text-center'>{$no}</td>";
                        echo "<td>{$row['nomor_tiket']}</td>";
                        echo "<td>{$row['nik']}</td>";
                        echo "<td>{$row['nama']}</td>";
                        echo "<td>{$row['jabatan']}</td>";
                        echo "<td>{$row['unit_kerja']}</td>";
                        echo "<td>{$row['kategori']}</td>";

                        // Tombol Lihat
                        echo "<td class='text-center'>
                                <button type='button' class='btn btn-info btn-sm btn-lihat' data-kendala='{$kendala}' title='Lihat Kendala'>
                                  <i class='fas fa-eye'></i>
                                </button>
                              </td>";

                        $status = $row['status'];
                        $badgeClass = match (strtolower($status)) {
                          'menunggu' => 'warning',
                          'diproses' => 'info',
                          'selesai' => 'success',
                          'tidak bisa diperbaiki' => 'danger',
                          default => 'secondary'
                        };
                        echo "<td class='text-center'><span class='badge badge-{$badgeClass}'>{$status}</span></td>";

                        echo "<td>{$row['teknisi_nama']}</td>";
                        echo "<td>" . formatTanggal($row['tanggal_input']) . "</td>";
                        echo "<td>" . formatTanggal($row['waktu_diproses']) . "</td>";
                        echo "<td>" . formatTanggal($row['waktu_selesai']) . "</td>";
                        echo "<td>{$row['status_validasi']}</td>";
                        echo "<td>" . formatTanggal($row['waktu_validasi']) . "</td>";
                        echo "<td>" . hitungDurasi($row['tanggal_input'], $row['waktu_diproses']) . "</td>";
                        echo "<td>" . hitungDurasi($row['tanggal_input'], $row['waktu_selesai']) . "</td>";
                        echo "<td>" . hitungDurasi($row['tanggal_input'], $row['waktu_validasi']) . "</td>";
                        echo "</tr>";
                        $no++;
                      }
                    } else {
                      echo "<tr><td colspan='18' class='text-center'>Tidak ada data ditemukan.</td></tr>";
                    }

                    function formatTanggal($tanggal) {
                      return $tanggal ? date('d-m-Y H:i', strtotime($tanggal)) : '-';
                    }

                    function hitungDurasi($mulai, $selesai) {
                      if (!$mulai || !$selesai) return '-';
                      $start = new DateTime($mulai);
                      $end = new DateTime($selesai);
                      $interval = $start->diff($end);
                      $jam = $interval->h + ($interval->days * 24);
                      $menit = $interval->i;
                      return "{$jam}j {$menit}m";
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

<!-- Modal Kendala -->
<div class="modal fade" id="modalKendala" tabindex="-1" role="dialog" aria-labelledby="modalKendalaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-eye"></i> Detail Kendala</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="isiKendala" class="mb-0"></p>
      </div>
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
$(document).ready(function() {
  // Klik tombol lihat
  $(document).on('click', '.btn-lihat', function() {
    let kendala = $(this).data('kendala');
    $('#isiKendala').text(kendala || 'Tidak ada keterangan.');
    $('#modalKendala').modal('show');
  });

  // Pastikan modal bisa dibuka berulang kali
  $('#modalKendala').on('hidden.bs.modal', function () {
    $('body').removeClass('modal-open');
    $('.modal-backdrop').remove();
  });
});
</script>

</body>
</html>
