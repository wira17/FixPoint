<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// === CEK LOGIN ===
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    echo "<script>alert('Anda belum login.'); window.location.href='login.php';</script>";
    exit;
}

// === CEK AKSES MENU ===
$current_file = basename(__FILE__);
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = ? AND menu.file_menu = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// === SET DEFAULT FILTER (TANGGAL HARI INI) ===
$today = date('Y-m-d');
$tgl_dari   = $_GET['tgl_dari']   ?? '';
$tgl_sampai = $_GET['tgl_sampai'] ?? '';
$search     = $_GET['search']     ?? '';

if (empty($tgl_dari) && empty($tgl_sampai)) {
    $tgl_dari = $tgl_sampai = $today;
}

// === PAGINATION ===
$limit  = 10; 
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// === BANGUN QUERY ===
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($tgl_dari) && !empty($tgl_sampai)) {
    $where .= " AND DATE(tanggal) BETWEEN ? AND ?";
    $params[] = $tgl_dari;
    $params[] = $tgl_sampai;
    $types .= "ss";
} elseif (!empty($tgl_dari)) {
    $where .= " AND DATE(tanggal) >= ?";
    $params[] = $tgl_dari;
    $types .= "s";
} elseif (!empty($tgl_sampai)) {
    $where .= " AND DATE(tanggal) <= ?";
    $params[] = $tgl_sampai;
    $types .= "s";
}

if (!empty($search)) {
    $where .= " AND (nama LIKE ? OR keperluan LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// === HITUNG TOTAL DATA ===
$count_sql = "SELECT COUNT(*) AS total FROM izin_keluar $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// === AMBIL DATA IZIN KELUAR ===
$sql = "SELECT * FROM izin_keluar $where ORDER BY tanggal DESC, created_at DESC LIMIT ?, ?";
$params_page = $params;
$types_page = $types . "ii";
$params_page[] = $offset;
$params_page[] = $limit;

$stmt_data = $conn->prepare($sql);
$stmt_data->bind_param($types_page, ...$params_page);
$stmt_data->execute();
$data_izin = $stmt_data->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Izin Keluar</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .izin-table { font-size: 13px; white-space: nowrap; }
    .izin-table th, .izin-table td { padding: 6px 10px; vertical-align: middle; }
    .pagination { justify-content: center; }
    .text-merah { color: red; font-weight: bold; }
    .card-header h4 { margin-bottom: 0; }
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
              <h4>Data Izin Keluar Pegawai (<?= date('d-m-Y', strtotime($tgl_dari)) ?>)</h4>
            </div>
            <div class="card-body">

              <!-- FILTER FORM -->
              <form method="GET" class="form-inline mb-3">
                <label class="mr-2">Dari:</label>
                <input type="date" name="tgl_dari" value="<?= htmlspecialchars($tgl_dari) ?>" class="form-control mr-2">
                <label class="mr-2">Sampai:</label>
                <input type="date" name="tgl_sampai" value="<?= htmlspecialchars($tgl_sampai) ?>" class="form-control mr-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/keperluan" class="form-control mr-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="data_izin_keluar.php" class="btn btn-secondary ml-2"><i class="fas fa-undo"></i> Reset</a>
                <a href="laporan_izin_keluar.php?tgl_dari=<?= urlencode($tgl_dari) ?>&tgl_sampai=<?= urlencode($tgl_sampai) ?>&search=<?= urlencode($search) ?>" 
                   target="_blank" class="btn btn-success ml-2">
                   <i class="fas fa-file-pdf"></i> Cetak Laporan
                </a>
              </form>

            <!-- TABEL DATA -->
<div class="table-responsive">
  <table class="table table-bordered table-striped izin-table">
    <thead class="thead-dark">
      <tr class="text-center">
        <th>No</th>
        <th>Nama</th>
        <th>Bagian</th>
        <th>Tanggal</th>
        <th>Jam Keluar</th>
        <th>Jam Kembali</th>
        <th>Jam Kembali Real</th>
        <th>Keterangan Kembali</th> <!-- 🔹 Tambahan baru -->
        <th>Keperluan</th>
        <th>ACC Atasan</th>
        <th>ACC SDM</th>
        <th>Lama</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($data_izin && $data_izin->num_rows > 0): ?>
      <?php $no = $offset + 1; while ($izin = $data_izin->fetch_assoc()): ?>
      <?php
        $lama = "-";
        $class_lama = "";

        if (!empty($izin['jam_keluar']) && !empty($izin['jam_kembali_real'])) {
            $waktu_keluar = strtotime($izin['tanggal'] . ' ' . $izin['jam_keluar']);
            $waktu_kembali = preg_match('/\d{4}-\d{2}-\d{2}/', $izin['jam_kembali_real'])
                ? strtotime($izin['jam_kembali_real'])
                : strtotime($izin['tanggal'] . ' ' . $izin['jam_kembali_real']);

            if ($waktu_kembali > $waktu_keluar) {
                $selisih = $waktu_kembali - $waktu_keluar;
                $jam = floor($selisih / 3600);
                $menit = floor(($selisih % 3600) / 60);
                $lama = sprintf("%02d jam %02d menit", $jam, $menit);
                if ($selisih > 3600) $class_lama = "text-merah";
            }
        }
      ?>
      <tr>
        <td class="text-center"><?= $no++ ?></td>
        <td><?= htmlspecialchars($izin['nama']) ?></td>
        <td><?= htmlspecialchars($izin['bagian']) ?></td>
        <td><?= date('d-m-Y', strtotime($izin['tanggal'])) ?></td>
        <td><?= htmlspecialchars($izin['jam_keluar']) ?></td>
        <td><?= $izin['jam_kembali'] ? htmlspecialchars($izin['jam_kembali']) : '-' ?></td>
        <td><?= $izin['jam_kembali_real'] ? htmlspecialchars($izin['jam_kembali_real']) : '-' ?></td>
        <td><?= $izin['keterangan_kembali'] ? htmlspecialchars($izin['keterangan_kembali']) : '-' ?></td> <!-- 🔹 Tambahan baru -->
        <td><?= htmlspecialchars($izin['keperluan']) ?></td>
        <td class="text-center">
          <?php
            $badgeAts = ($izin['status_atasan'] == 'disetujui') ? 'success' :
                        (($izin['status_atasan'] == 'ditolak') ? 'danger' : 'secondary');
            echo "<span class='badge badge-$badgeAts'>".ucfirst($izin['status_atasan'])."</span><br>";
            echo "<small>".($izin['waktu_acc_atasan'] ? date('d-m-Y H:i', strtotime($izin['waktu_acc_atasan'])) : '-')."</small>";
          ?>
        </td>
        <td class="text-center">
          <?php
            $badgeSdm = ($izin['status_sdm'] == 'disetujui') ? 'success' :
                        (($izin['status_sdm'] == 'ditolak') ? 'danger' : 'secondary');
            echo "<span class='badge badge-$badgeSdm'>".ucfirst($izin['status_sdm'])."</span><br>";
            echo "<small>".($izin['waktu_acc_sdm'] ? date('d-m-Y H:i', strtotime($izin['waktu_acc_sdm'])) : '-')."</small>";
          ?>
        </td>
        <td class="text-center <?= $class_lama ?>"><?= $lama ?></td>
        <td class="text-center">
          <a href="cetak_izin_keluar.php?id=<?= $izin['id'] ?>" target="_blank" 
             class="btn btn-sm btn-info" title="Cetak Surat">
            <i class="fas fa-print"></i>
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="13" class="text-center">Tidak ada data izin keluar hari ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

              <!-- PAGINATION -->
              <nav>
                <ul class="pagination">
                  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                      <a class="page-link" href="?page=<?= $i ?>&tgl_dari=<?= urlencode($tgl_dari) ?>&tgl_sampai=<?= urlencode($tgl_sampai) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>

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
