<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// === CEK AKSES MENU ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">
  <title>Ujian Tertulis</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    #timer {
      font-size: 20px;
      font-weight: bold;
      color: #dc3545;
    }
    .soal-card {
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 25px;
      background-color: #fdfdfd;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .soal-teks {
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 15px;
      color: #333;
      white-space: pre-line;
    }
    .pilihan-jawaban label {
      display: block;
      background: #fafafa;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px 15px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .pilihan-jawaban input[type=radio] {
      margin-right: 8px;
    }
    .pilihan-jawaban label:hover {
      background-color: #e9f7ef;
      border-color: #28a745;
    }
    .pilihan-jawaban input[type=radio]:checked + label {
      background-color: #d4edda;
      border-color: #28a745;
    }
    .card-header h4 {
      margin-bottom: 0;
    }
    .pagination-nav {
      display: flex;
      justify-content: space-between;
      margin-top: 25px;
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
        <div class="section-header">
          <h1><i class="fas fa-pen-nib"></i> Ujian Tertulis</h1>
        </div>

        <div class="section-body">
<?php
// === MODE 1: DAFTAR UJIAN ===
if (!isset($_GET['id'])) {
    $qJudul = mysqli_query($conn, "SELECT * FROM judul_soal ORDER BY tanggal_buat DESC");
    $today = date('Y-m-d');
?>
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h4><i class="fas fa-list"></i> Daftar Ujian</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead class="thead-dark">
              <tr>
                <th>No</th>
                <th>Judul Soal</th>
                <th>Durasi (menit)</th>
                <th>Tanggal Ujian</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($row = mysqli_fetch_assoc($qJudul)):
                $tgl_mulai = date('Y-m-d', strtotime($row['tanggal_mulai']));
                $tgl_selesai = date('Y-m-d', strtotime($row['tanggal_selesai']));

                // cek apakah user sudah pernah mengikuti ujian ini
                $cekSudah = mysqli_query($conn, "SELECT 1 FROM jawaban_ujian WHERE user_id='$user_id' AND judul_soal_id='{$row['id']}' LIMIT 1");
                $sudahUjian = mysqli_num_rows($cekSudah) > 0;
              ?>
              <tr>
                <td><?= $no++; ?></td>
                <td class="text-left"><?= htmlspecialchars($row['judul_soal']); ?></td>
                <td><?= $row['durasi']; ?></td>
                <td><?= date('d-m-Y', strtotime($tgl_mulai)); ?></td>
                <td><?= date('d-m-Y', strtotime($tgl_selesai)); ?></td>
                <td>
                  <?php if ($sudahUjian): ?>
                    <span class="badge bg-success text-white">
                      <i class="fas fa-check"></i> Sudah Mengikuti
                    </span>
                  <?php elseif ($today == $tgl_mulai): ?>
                    <a href="ujian_tertulis.php?id=<?= $row['id']; ?>" class="btn btn-success btn-sm">
                      <i class="fas fa-play"></i> Mulai Ujian
                    </a>
                  <?php elseif ($today < $tgl_mulai): ?>
                    <span class="badge bg-warning text-dark">
                      <i class="fas fa-clock"></i> Belum dimulai
                    </span>
                  <?php else: ?>
                    <span class="badge bg-danger">
                      <i class="fas fa-times"></i> Selesai
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
<?php
}
// === MODE 2: HALAMAN UJIAN ===
else {
    $judul_soal_id = (int)$_GET['id'];
    $qJudul = mysqli_query($conn, "SELECT * FROM judul_soal WHERE id='$judul_soal_id'");
    if (mysqli_num_rows($qJudul) == 0) {
        echo "<div class='alert alert-danger'>❌ Judul soal tidak ditemukan.</div>";
        exit;
    }
    $judul = mysqli_fetch_assoc($qJudul);

    // === CEK APAKAH USER SUDAH MENGIKUTI UJIAN ===
    $cekSudah = mysqli_query($conn, "SELECT 1 FROM jawaban_ujian WHERE user_id='$user_id' AND judul_soal_id='$judul_soal_id' LIMIT 1");
    if (mysqli_num_rows($cekSudah) > 0) {
        echo "<div class='alert alert-success text-center'><i class='fas fa-check-circle'></i> Anda sudah mengikuti ujian ini. Anda tidak dapat mengikuti kembali.</div>";
        exit;
    }

    $today = date('Y-m-d');
    $tgl_mulai = date('Y-m-d', strtotime($judul['tanggal_mulai']));
    $tgl_selesai = date('Y-m-d', strtotime($judul['tanggal_selesai']));

    if ($today < $tgl_mulai) {
        echo "<div class='alert alert-warning text-center'><i class='fas fa-clock'></i> Ujian ini belum dimulai.</div>";
        exit;
    }

    if ($today > $tgl_selesai) {
        echo "<div class='alert alert-danger text-center'><i class='fas fa-times-circle'></i> Waktu ujian ini sudah berakhir.</div>";
        exit;
    }

    // === Simpan jawaban ===
    if (isset($_POST['kirim'])) {
        foreach ($_POST['jawaban'] as $soal_id => $jawaban) {
            $soal_id = (int)$soal_id;
            $jawaban = mysqli_real_escape_string($conn, $jawaban);
            $cek = mysqli_query($conn, "SELECT 1 FROM jawaban_ujian WHERE user_id='$user_id' AND soal_id='$soal_id'");
            if (mysqli_num_rows($cek) == 0) {
                mysqli_query($conn, "INSERT INTO jawaban_ujian (user_id, judul_soal_id, soal_id, jawaban, tanggal_ujian)
                                    VALUES ('$user_id', '$judul_soal_id', '$soal_id', '$jawaban', NOW())");
            }
        }
        echo "<script>alert('✅ Jawaban berhasil dikirim.'); window.location.href='ujian_tertulis.php';</script>";
        exit;
    }

    // === Pagination Soal ===
    $perPage = 2;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $perPage;
    $totalSoal = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM soal WHERE judul_soal_id='$judul_soal_id'"));
    $totalPages = ceil($totalSoal / $perPage);
    $qSoal = mysqli_query($conn, "SELECT * FROM soal WHERE judul_soal_id='$judul_soal_id' ORDER BY id ASC LIMIT $offset, $perPage");
?>
    <div class="card shadow">
      <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-question-circle"></i> <?= htmlspecialchars($judul['judul_soal']); ?></h4>
        <div id="timer"><i class="fas fa-hourglass-half"></i> Waktu: <span id="timeLeft"></span></div>
      </div>

      <div class="card-body">
        <form method="POST" id="ujianForm">
          <?php
          $no = $offset + 1;
          while ($soal = mysqli_fetch_assoc($qSoal)):
          ?>
            <div class="soal-card">
              <div class="mb-2 text-muted">Soal <?= $no++; ?></div>
              <div class="soal-teks"><?= nl2br(htmlspecialchars($soal['soal'])); ?></div>

              <div class="pilihan-jawaban">
                <input type="radio" name="jawaban[<?= $soal['id']; ?>]" id="a<?= $soal['id']; ?>" value="a" required>
                <label for="a<?= $soal['id']; ?>">A. <?= htmlspecialchars($soal['pilihan_a']); ?></label>

                <input type="radio" name="jawaban[<?= $soal['id']; ?>]" id="b<?= $soal['id']; ?>" value="b">
                <label for="b<?= $soal['id']; ?>">B. <?= htmlspecialchars($soal['pilihan_b']); ?></label>

                <input type="radio" name="jawaban[<?= $soal['id']; ?>]" id="c<?= $soal['id']; ?>" value="c">
                <label for="c<?= $soal['id']; ?>">C. <?= htmlspecialchars($soal['pilihan_c']); ?></label>

                <input type="radio" name="jawaban[<?= $soal['id']; ?>]" id="d<?= $soal['id']; ?>" value="d">
                <label for="d<?= $soal['id']; ?>">D. <?= htmlspecialchars($soal['pilihan_d']); ?></label>
              </div>
            </div>
          <?php endwhile; ?>

          <div class="pagination-nav">
            <div>
              <?php if ($page > 1): ?>
                <a href="ujian_tertulis.php?id=<?= $judul_soal_id; ?>&page=<?= $page - 1; ?>" class="btn btn-secondary">
                  <i class="fas fa-arrow-left"></i> Sebelumnya
                </a>
              <?php endif; ?>
            </div>
            <div>
              <?php if ($page < $totalPages): ?>
                <a href="ujian_tertulis.php?id=<?= $judul_soal_id; ?>&page=<?= $page + 1; ?>" class="btn btn-primary">
                  Selanjutnya <i class="fas fa-arrow-right"></i>
                </a>
              <?php else: ?>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#confirmModal">
                  <i class="fas fa-paper-plane"></i> Kirim Jawaban
                </button>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>
    </div>

    <script>
      const ujianId = <?= $judul_soal_id; ?>;
      const durasiTotal = <?= $judul['durasi']; ?> * 60;
      const keyEnd = "ujian_end_" + ujianId;
      const form = document.getElementById('ujianForm');
      const display = document.getElementById('timeLeft');

      let endTime = sessionStorage.getItem(keyEnd);
      if (!endTime) {
        const now = new Date().getTime();
        endTime = now + durasiTotal * 1000;
        sessionStorage.setItem(keyEnd, endTime);
      }

      function updateTimer() {
        const now = new Date().getTime();
        const distance = endTime - now;
        const totalSec = Math.floor(distance / 1000);

        if (totalSec <= 0) {
          clearInterval(timer);
          alert("⏰ Waktu ujian habis! Jawaban dikirim otomatis.");
          sessionStorage.removeItem(keyEnd);
          form.submit();
          return;
        }

        const minutes = Math.floor(totalSec / 60);
        const seconds = totalSec % 60;
        display.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
      }

      const timer = setInterval(updateTimer, 1000);
      updateTimer();
    </script>
<?php } ?>
        </div>
      </section>
    </div>
  </div>
</div>


 <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header bg-warning text-white">
            <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Pengiriman</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body text-center">
            <p>Apakah Anda yakin ingin mengirim jawaban?</p>
            <p class="text-danger"><strong>Jawaban yang sudah dikirim tidak dapat diubah kembali.</strong></p>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <i class="fas fa-times"></i> Tidak
            </button>
            <button type="submit" name="kirim" class="btn btn-success" form="ujianForm">
              <i class="fas fa-check"></i> Ya, Kirim Sekarang
            </button>
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
</body>
</html>
