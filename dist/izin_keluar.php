<?php
session_start();
include 'koneksi.php';
include 'send_wa.php';
date_default_timezone_set('Asia/Jakarta');

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
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("is", $user_id, $current_file);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Ambil data user + nama atasan
$qUser = $conn->prepare("SELECT u.nik, u.nama, u.jabatan, u.unit_kerja, a.nama AS nama_atasan 
                        FROM users u
                        LEFT JOIN users a ON u.atasan_id = a.id
                        WHERE u.id = ?");
$qUser->bind_param("i", $user_id);
$qUser->execute();
$resUser = $qUser->get_result();
$user = $resUser->fetch_assoc();

// Fungsi ambil nomor WA atasan
function getNomorAtasan($conn, $nama_atasan){
    if(empty($nama_atasan)) return '';
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT no_hp FROM users WHERE nama='".mysqli_real_escape_string($conn, $nama_atasan)."' LIMIT 1"));
    return $row['no_hp'] ?? '';
}

// --- Proses simpan data izin keluar ---
if (isset($_POST['simpan'])) {
    $tanggal = date('Y-m-d');
    $jam_keluar = $_POST['jam_keluar'] ?? '';
    $jam_kembali = $_POST['jam_kembali'] ?? null;
    $keperluan = trim($_POST['keperluan'] ?? '');
    $atasan_langsung = $user['nama_atasan'] ?? '';

    if (empty($jam_keluar) || empty($keperluan)) {
        $_SESSION['flash_message'] = "Jam keluar dan keperluan harus diisi!";
        header("Location: izin_keluar.php");
        exit;
    }

    // Insert data izin
    $insert = $conn->prepare("INSERT INTO izin_keluar 
        (user_id, nik, nama, jabatan, bagian, atasan_langsung, tanggal, jam_keluar, jam_kembali, keperluan, status_atasan, status_sdm, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())");
    $insert->bind_param(
        "isssssssss",
        $user_id,
        $user['nik'],
        $user['nama'],
        $user['jabatan'],
        $user['unit_kerja'], 
        $atasan_langsung,
        $tanggal,
        $jam_keluar,
        $jam_kembali,
        $keperluan
    );

    if ($insert->execute()) {
        // Ambil nomor WA atasan langsung
        $nomor_atasan = getNomorAtasan($conn, $user['nama_atasan']);

        // Pesan WA ke atasan
        $pesanWA = "📝 *IZIN KELUAR PERUSAHAAN*\n";
        $pesanWA .= "Nama: " . $user['nama'] . "\n";
        $pesanWA .= "Jabatan: " . $user['jabatan'] . "\n";
        $pesanWA .= "Unit Kerja: " . $user['unit_kerja'] . "\n";
        $pesanWA .= "Jam Keluar: " . $jam_keluar . " WIB\n";
        $pesanWA .= "Jam Kembali (Estimasi): " . ($jam_kembali ? $jam_kembali . " WIB" : '-') . "\n";
        $pesanWA .= "Keperluan: $keperluan\n";
        $pesanWA .= "Pengajuan oleh: " . $user['nama'];

        $wa_sent = false;
        if (!empty($nomor_atasan)) {
            $wa_sent = sendWA($nomor_atasan, $pesanWA);
        }

        $_SESSION['flash_message'] = $wa_sent 
            ? "✅ Data izin keluar berhasil disimpan dan WA terkirim ke atasan." 
            : "✅ Data izin keluar berhasil disimpan, tapi WA gagal dikirim.";
    } else {
        $_SESSION['flash_message'] = "❌ Gagal menyimpan data izin keluar: " . $insert->error;
    }

    header("Location: izin_keluar.php");
    exit;
}

// --- Proses update jam kembali real + WA ---
if (isset($_GET['kembali'])) {
    $id_kembali = intval($_GET['kembali']);

    // Ambil data izin
    $rowIzin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM izin_keluar WHERE id=$id_kembali AND user_id=$user_id LIMIT 1"));
    if($rowIzin){
        $jam_sekarang = date('H:i:s');
        $update = $conn->prepare("UPDATE izin_keluar 
                                   SET jam_kembali_real = NOW() 
                                   WHERE id = ? AND user_id = ? AND (jam_kembali_real IS NULL OR jam_kembali_real = '')");
        $update->bind_param("ii", $id_kembali, $user_id);
        if ($update->execute()) {
            // Kirim WA ke atasan
           $nomor_atasan = getNomorAtasan($conn, $user['nama_atasan']);
            $pesanWA = "🕒 *UPDATE JAM KEMBALI*\n";
            $pesanWA .= "Nama: " . $user['nama'] . "\n";
            $pesanWA .= "Jabatan: " . $user['jabatan'] . "\n";
            $pesanWA .= "Unit Kerja: " . $user['unit_kerja'] . "\n";
            $pesanWA .= "Jam Kembali Real: " . $jam_sekarang . " WIB\n";
            $pesanWA .= "Keperluan: " . $rowIzin['keperluan'];


            $wa_sent = false;
            if (!empty($nomor_atasan)) {
                $wa_sent = sendWA($nomor_atasan, $pesanWA);
            }

            $_SESSION['flash_message'] = $wa_sent
                ? "✅ Jam kembali berhasil diperbarui dan WA terkirim ke atasan." 
                : "✅ Jam kembali berhasil diperbarui, tapi WA gagal dikirim.";
        } else {
            $_SESSION['flash_message'] = "❌ Gagal memperbarui jam kembali.";
        }
    }

    header("Location: izin_keluar.php");
    exit;
}

// --- Filter & Pagination Data Tersimpan ---
// Ambil filter tanggal
$tanggal_filter = $_GET['tanggal'] ?? date('Y-m-d');

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$start = ($page - 1) * $limit;

// Hitung total data
$stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM izin_keluar WHERE user_id=? AND tanggal=?");
$stmtCount->bind_param("is", $user_id, $tanggal_filter);
$stmtCount->execute();
$resCount = $stmtCount->get_result()->fetch_assoc();
$total_data = $resCount['total'];
$total_page = ceil($total_data / $limit);

// Ambil data izin keluar user berdasarkan filter & pagination
$stmtIzin = $conn->prepare("SELECT * FROM izin_keluar WHERE user_id=? AND tanggal=? ORDER BY created_at DESC LIMIT ?,?");
$stmtIzin->bind_param("isii", $user_id, $tanggal_filter, $start, $limit);
$stmtIzin->execute();
$data_izin = $stmtIzin->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Form Izin Keluar</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
<link rel="stylesheet" href="assets/css/style.css" />
<link rel="stylesheet" href="assets/css/components.css" />
<style>
.flash-center {position:fixed;top:20%;left:50%;transform:translate(-50%,-50%);z-index:1050;min-width:300px;max-width:90%;text-align:center;padding:15px;border-radius:8px;font-weight:500;box-shadow:0 5px 15px rgba(0,0,0,0.3);}
.izin-table{font-size:13px;white-space:nowrap;}
.izin-table th,.izin-table td{padding:6px 10px;vertical-align:middle;}
.izin-table thead th{color:#fff!important;background-color:#000!important;}
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

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-info flash-center" id="flashMsg">
<?= htmlspecialchars($_SESSION['flash_message']) ?>
</div>
<?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex align-items-center">
    <h4 class="mb-0">Form Izin Keluar</h4>
    <!-- Ikon tanda tanya merah -->
    <button type="button" class="btn btn-link text-danger ml-2 p-0" data-toggle="modal" data-target="#prosedurModal" title="Lihat Prosedur">
      <i class="fas fa-question-circle fa-lg"></i>
    </button>
  </div>
  <div class="card-body">
    <ul class="nav nav-tabs" id="izinTab" role="tablist">
      <li class="nav-item"><a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Data</a></li>
      <li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Tersimpan</a></li>
    </ul>


    <div class="tab-content mt-3">
<div class="tab-pane fade show active" id="input" role="tabpanel">
<form method="POST" novalidate>
<div class="form-group"><label>Jam Keluar</label><input type="time" name="jam_keluar" class="form-control" required /></div>
<div class="form-group"><label>Jam Kembali</label><input type="time" name="jam_kembali" class="form-control" /></div>
<div class="form-group"><label>Keperluan / Alasan</label><textarea name="keperluan" class="form-control" rows="3" required></textarea></div>
<button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
</form>
</div>

<div class="tab-pane fade" id="data" role="tabpanel">
  <form method="GET" class="form-inline mb-2">
    <input type="date" name="tanggal" class="form-control mr-2" value="<?= htmlspecialchars($tanggal_filter) ?>">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
  </form>

  <div class="table-responsive">
  <table class="table table-bordered izin-table">
  <thead>
  <tr class="text-center">
    <th>No</th>
    <th>Tanggal</th>
    <th>Jam Keluar</th>
    <th>Jam Kembali (Estimasi)</th>
    <th>Jam Kembali (Real)</th>
    <th>Keperluan</th>
    <th>Waktu Input</th>
    <th>ACC Atasan</th>
    <th>ACC SDM</th>
    <th>Aksi</th>
  </tr>
  </thead>
  <tbody>
  <?php if ($data_izin && $data_izin->num_rows > 0): ?>
  <?php $no = $start + 1; while ($izin = $data_izin->fetch_assoc()) : ?>
  <tr>
    <td class="text-center"><?= $no++ ?></td>
    <td><?= htmlspecialchars(date('d-m-Y', strtotime($izin['tanggal']))) ?></td>
    <td><?= htmlspecialchars($izin['jam_keluar']) ?></td>
    <td class="text-center"><?= htmlspecialchars($izin['jam_kembali']) ?></td>
    <td class="text-center">
      <?php
      if (empty($izin['jam_kembali_real'])) {
        echo '<a href="izin_keluar.php?kembali=' . $izin['id'] . '" class="btn btn-sm btn-warning" onclick="return confirm(\'Yakin ingin update jam kembali sekarang?\')"><i class="fas fa-undo"></i> Kembali / Update</a>';
      } else {
        echo htmlspecialchars($izin['jam_kembali_real']);
      }
      ?>
    </td>
    <td><?= htmlspecialchars($izin['keperluan']) ?></td>
    <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime($izin['created_at']))) ?></td>
    <td class="text-center">
      <?php
      $badgeAts = ($izin['status_atasan']=='disetujui')?'success':(($izin['status_atasan']=='ditolak')?'danger':'secondary');
      echo "<span class='badge badge-{$badgeAts}'>".ucfirst($izin['status_atasan'])."</span><br>";
      echo "<small>".($izin['waktu_acc_atasan']?date('d-m-Y H:i',strtotime($izin['waktu_acc_atasan'])):'-')."</small>";
      ?>
    </td>
    <td class="text-center">
      <?php
      $badgeSdm = ($izin['status_sdm']=='disetujui')?'success':(($izin['status_sdm']=='ditolak')?'danger':'secondary');
      echo "<span class='badge badge-{$badgeSdm}'>".ucfirst($izin['status_sdm'])."</span><br>";
      echo "<small>".($izin['waktu_acc_sdm']?date('d-m-Y H:i',strtotime($izin['waktu_acc_sdm'])):'-')."</small>";
      ?>
    </td>
    <td class="text-center">
      <a href="cetak_izin_keluar.php?id=<?= $izin['id'] ?>" target="_blank" class="btn btn-sm btn-info" title="Cetak Surat"><i class="fas fa-print"></i></a>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php else: ?>
  <tr><td colspan="10" class="text-center">Belum ada data izin keluar.</td></tr>
  <?php endif; ?>
  </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($total_page > 1): ?>
  <nav>
    <ul class="pagination justify-content-center">
      <?php for($i=1;$i<=$total_page;$i++): ?>
        <li class="page-item <?= $i==$page?'active':'' ?>">
          <a class="page-link" href="?tanggal=<?= $tanggal_filter ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

  </div>
</div>


</div>
</div>
</div>
</div>
</section>
</div>
</div>
</div>



<!-- Modal Prosedur -->
<div class="modal fade" id="prosedurModal" tabindex="-1" role="dialog" aria-labelledby="prosedurModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="prosedurModalLabel"><i class="fas fa-info-circle"></i> Prosedur Pengisian Izin Keluar</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h6 class="mb-2">📌 Cara Mengisi Form</h6>
        <ol>
          <li>Isi <b>Jam Keluar</b></li>
          <li>Isi <b>Jam Kembali</b> (estimasi)</li>
          <li>Tulis <b>Alasan / Keperluan</b></li>
        </ol>
        <hr>
        <h6 class="mb-2">📌 Tab Menu Data Tersimpan</h6>
        <ul>
          <li>Pastikan <b>ACC Atasan</b> = Disetujui ✅</li>
          <li>Pastikan <b>ACC SDM</b> = Disetujui ✅</li>
          <li>Jika disetujui, klik tombol <b><i class="fas fa-print"></i> Print</b> untuk mencetak surat izin keluar.</li>
          <li>Tunjukkan surat izin kepada <b>Security</b> sebagai bukti pengesahan.</li>
        </ul>
        <hr>
        <h6 class="mb-2">📌 Saat Kembali ke RS</h6>
        <ul>
          <li>Buka tab <b>Data Tersimpan</b></li>
          <li>Klik tombol <b>Kembali / Update</b> untuk menyimpan <i>Jam Kembali Real</i></li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
$(document).ready(function(){
    setTimeout(function(){$("#flashMsg").fadeOut("slow");},3000);
});
</script>
</body>
</html>
