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

// === PROSES SUBMIT AJAX ===
if(isset($_POST['jawaban'])){
    $judul_soal_id = (int)$_GET['id'];
    $benar = 0;
    $total = 0;

    foreach($_POST['jawaban'] as $soal_id => $jawaban){
        $soal_id = (int)$soal_id;
        $jawaban = mysqli_real_escape_string($conn, $jawaban);

        $cek = mysqli_query($conn, "SELECT jawaban_benar FROM soal WHERE id='$soal_id'");
        $data = mysqli_fetch_assoc($cek);
        $jawabanBenar = ($data && $jawaban == $data['jawaban_benar']) ? 'ya' : 'tidak';

        if($jawabanBenar == 'ya') $benar++;
        $total++;

        mysqli_query($conn, "INSERT INTO jawaban_ujian 
            (user_id, judul_soal_id, soal_id, jawaban, benar, tanggal_ujian)
            VALUES ('$user_id', '$judul_soal_id', '$soal_id', '$jawaban', '$jawabanBenar', NOW())");
    }

    $nilai = ($total > 0) ? round(($benar/$total)*100,2) : 0;

    mysqli_query($conn, "INSERT INTO hasil_ujian 
        (user_id, judul_soal_id, jumlah_benar, total_soal, nilai, tanggal_selesai)
        VALUES ('$user_id', '$judul_soal_id', '$benar', '$total', '$nilai', NOW())");

    $id_hasil = mysqli_insert_id($conn);

    header('Content-Type: application/json');
    echo json_encode([
        'success'=>true,
        'benar'=>$benar,
        'total'=>$total,
        'nilai'=>$nilai,
        'judul_soal_id'=>$judul_soal_id,
        'id_hasil'=>$id_hasil
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ujian Tertulis</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<style>
#timer { font-size: 18px; font-weight: bold; color: #dc3545; }
.soal-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 25px; background-color: #fdfdfd; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.soal-teks { font-size: 16px; line-height: 1.6; margin-bottom: 15px; color: #333; white-space: pre-line; }
.pilihan-jawaban label { display: block; background: #fafafa; border: 1px solid #ddd; border-radius: 8px; padding: 10px 15px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s ease; }
.pilihan-jawaban input[type=radio] { margin-right: 8px; }
.pilihan-jawaban label:hover { background-color: #e9f7ef; border-color: #28a745; }
.pilihan-jawaban input[type=radio]:checked + label { background-color: #d4edda; border-color: #28a745; }
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
            <table class="table table-bordered table-hover text-center" id="tabelUjian">
                <thead class="thead-dark">
                    <tr>
                        <th>No</th>
                        <th>Judul Soal</th>
                        <th>Durasi (menit)</th>
                        <th>Tanggal Mulai</th>
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
                    $cekSudah = mysqli_query($conn, "SELECT 1 FROM hasil_ujian WHERE user_id='$user_id' AND judul_soal_id='{$row['id']}' LIMIT 1");
                    $sudahUjian = mysqli_num_rows($cekSudah) > 0;
                ?>
                    <tr id="row-<?= $row['id']; ?>">
                        <td><?= $no++; ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['judul_soal']); ?></td>
                        <td><?= $row['durasi']; ?></td>
                        <td><?= date('d-m-Y', strtotime($tgl_mulai)); ?></td>
                        <td><?= date('d-m-Y', strtotime($tgl_selesai)); ?></td>
                        <td class="status-cell">
                            <?php if ($sudahUjian): 
                                $hasil = mysqli_query($conn, "SELECT id FROM hasil_ujian WHERE user_id='$user_id' AND judul_soal_id='{$row['id']}' LIMIT 1");
                                $hasilData = mysqli_fetch_assoc($hasil);
                                $id_hasil = $hasilData['id'];
                            ?>
                                <span class="badge bg-success text-white"><i class="fas fa-check"></i> Sudah Mengikuti</span>
                                <a href="cetak_hasil.php?id=<?= $id_hasil ?>" class="btn btn-info btn-sm" target="_blank" title="Cetak Hasil"><i class="fas fa-print"></i></a>
                            <?php elseif ($today >= $tgl_mulai && $today <= $tgl_selesai): ?>
                                <a href="ujian_tertulis.php?id=<?= $row['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-play"></i> Mulai Ujian</a>
                            <?php elseif ($today < $tgl_mulai): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Belum dimulai</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times"></i> Selesai</span>
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
} else {
$judul_soal_id = (int)$_GET['id'];
$qJudul = mysqli_query($conn, "SELECT * FROM judul_soal WHERE id='$judul_soal_id'");
if (mysqli_num_rows($qJudul) == 0) {
    echo "<div class='alert alert-danger'>❌ Judul soal tidak ditemukan.</div>";
    exit;
}
$judul = mysqli_fetch_assoc($qJudul);

// CEK SUDAH UJIAN
$cekSudah = mysqli_query($conn, "SELECT 1 FROM hasil_ujian WHERE user_id='$user_id' AND judul_soal_id='$judul_soal_id' LIMIT 1");
if (mysqli_num_rows($cekSudah) > 0) {
    echo "<div class='alert alert-success text-center'><i class='fas fa-check-circle'></i> Anda sudah mengikuti ujian ini.</div>";
    exit;
}

$qSoal = mysqli_query($conn, "SELECT * FROM soal WHERE judul_soal_id='$judul_soal_id' ORDER BY id ASC");
?>
<div class="card shadow">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-question-circle"></i> <?= htmlspecialchars($judul['judul_soal']); ?></h4>
        <div id="timer"><i class="fas fa-hourglass-half"></i> <span id="timeLeft"></span></div>
    </div>

    <div class="card-body">
        <form method="POST" id="ujianForm">
            <?php $no = 1; while ($soal = mysqli_fetch_assoc($qSoal)): ?>
            <div class="soal-card">
                <div class="mb-2 text-muted">Soal <?= $no++; ?></div>
                <div class="soal-teks"><?= nl2br(htmlspecialchars($soal['soal'])); ?></div>
                <div class="pilihan-jawaban">
                    <?php foreach (['a','b','c','d'] as $opt): ?>
                        <input type="radio" name="jawaban[<?= $soal['id']; ?>]" id="<?= $opt.$soal['id']; ?>" value="<?= $opt; ?>" required>
                        <label for="<?= $opt.$soal['id']; ?>"><?= strtoupper($opt) ?>. <?= htmlspecialchars($soal['pilihan_'.$opt]); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endwhile; ?>

            <div class="text-center mt-3">
                <button type="submit" id="btnKirim" class="btn btn-success btn-lg"><i class="fas fa-paper-plane"></i> Kirim Jawaban</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Timer
const durasiTotal = <?= $judul['durasi']; ?> * 60;
const keyEnd = "ujian_end_<?= $judul_soal_id; ?>";
const form = document.getElementById('ujianForm');
const display = document.getElementById('timeLeft');

let endTime = sessionStorage.getItem(keyEnd);
if (!endTime) { 
    endTime = new Date().getTime() + durasiTotal * 1000; 
    sessionStorage.setItem(keyEnd, endTime); 
} else {
    endTime = parseInt(endTime);
}

let timer = setInterval(updateTimer, 1000);

function updateTimer() {
    const now = new Date().getTime();
    const distance = endTime - now;
    const totalSec = Math.floor(distance / 1000);

    if (totalSec <= 0) {
        clearInterval(timer);
        display.textContent = '0:00';
        Swal.fire('⏰ Waktu habis!', 'Jawaban akan dikirim otomatis.', 'warning');
        sessionStorage.removeItem(keyEnd);
        submitJawaban();
        return;
    }

    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;
    display.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
}

updateTimer();

function submitJawaban(){
    let formData = new FormData(form);
    fetch(window.location.href, { method:'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            let msgHtml = `<p>Jumlah Benar: <b>${data.benar}</b></p>
                           <p>Total Soal: <b>${data.total}</b></p>
                           <p>Nilai: <b>${data.nilai}</b></p>`;

            if(data.nilai < 75){
                msgHtml += `<p style="color:red;"><b>⚠ Nilai Anda di bawah 75! Wajib remedial.</b></p>`;
            }

            Swal.fire({
                title:'✅ Jawaban Tersimpan!',
                icon:'success',
                html: msgHtml,
                confirmButtonText:'OK'
            }).then(()=>{
                form.querySelectorAll('input[type=radio]').forEach(i=>i.disabled=true);
                document.getElementById('btnKirim').disabled = true;

                // Update status di tabel daftar ujian
                let row = document.getElementById('row-'+data.judul_soal_id);
                if(row){
                    let statusHtml = `<span class="badge bg-success text-white"><i class="fas fa-check"></i> Sudah Mengikuti</span>
                                      <a href="cetak_hasil.php?id=${data.id_hasil}" class="btn btn-info btn-sm" target="_blank" title="Cetak Hasil"><i class="fas fa-print"></i></a>`;
                    if(data.nilai < 75){
                        statusHtml += `<br><span class="text-danger font-weight-bold">Wajib Remedial</span>`;
                    }
                    row.querySelector('.status-cell').innerHTML = statusHtml;
                }
            });
        } else {
            Swal.fire('Error!', data.message || 'Terjadi kesalahan saat mengirim jawaban.', 'error');
        }
    }).catch(err=>{
        Swal.fire('Error!', 'Terjadi kesalahan saat mengirim jawaban.', 'error');
    });
}


document.getElementById('btnKirim').addEventListener('click', function(e){
    e.preventDefault();
    Swal.fire({
        title:'Apakah Anda yakin?',
        text:'Jawaban Anda akan disimpan dan tidak bisa diubah!',
        icon:'question',
        showCancelButton:true,
        confirmButtonText:'Ya, Kirim!',
        cancelButtonText:'Batal',
        reverseButtons:true,
        focusCancel:true
    }).then(result=>{
        if(result.isConfirmed){
            submitJawaban();
        }
    });
});
</script>

<?php } ?>

</div>
</section>
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
</body>
</html>
