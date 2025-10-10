<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Cek akses menu
$current_file = basename(__FILE__);
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title>Absensi Wajah</title>

<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
<link rel="stylesheet" href="assets/css/style.css" />
<link rel="stylesheet" href="assets/css/components.css" />

<style>
#video {
    border: 2px solid #007bff;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
    height: auto;
}
#canvas {
    display: none;
}
.absen-buttons button {
    margin: 5px 0;
}
#notif-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 250px;
    z-index: 9999;
    display: none;
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
        <div class="section-body">
          <div class="card">
            <div class="card-body text-center">
              <p>Pastikan wajah Anda terlihat jelas di kamera.</p>
              <video id="video" autoplay muted></video>
              <canvas id="canvas"></canvas>

              <div class="absen-buttons mt-3">
                <button class="btn btn-success" onclick="submitAbsen('masuk')">Masuk</button>
                <button class="btn btn-danger" onclick="submitAbsen('keluar')">Keluar</button>
                <button class="btn btn-warning text-white" onclick="submitAbsen('istirahat_masuk')">Istirahat Masuk</button>
                <button class="btn btn-info text-white" onclick="submitAbsen('istirahat_keluar')">Istirahat Keluar</button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<div id="notif-toast" class="alert alert-success"></div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>

<script>
// Akses kamera
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');

navigator.mediaDevices.getUserMedia({ video: true })
.then(stream => {
    video.srcObject = stream;
})
.catch(err => {
    alert("Tidak bisa mengakses kamera: " + err);
});

// Fungsi capture gambar
function captureImage() {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg');
}

// Fungsi ambil lokasi
function getLocation(callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            callback(pos.coords.latitude, pos.coords.longitude);
        }, err => {
            callback(null, null);
        });
    } else {
        callback(null, null);
    }
}

// Fungsi kirim absen
function submitAbsen(type) {
    const image = captureImage();
    getLocation((lat, lng) => {
        $.ajax({
            url: 'absen_proses.php',
            method: 'POST',
            data: JSON.stringify({ user_id: <?= $user_id ?>, type, image, latitude: lat, longitude: lng }),
            contentType: 'application/json',
            success: function(res){
                if(res.success){
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            },
            error: function(){
                showToast('Terjadi kesalahan server.', 'danger');
            }
        });
    });
}

// Toast notifikasi
function showToast(message, type='success'){
    const toast = $('#notif-toast');
    toast.removeClass('alert-success alert-danger alert-warning alert-info').addClass('alert-'+type).text(message).fadeIn();
    setTimeout(()=>toast.fadeOut(),3000);
}
</script>
</body>
</html>
