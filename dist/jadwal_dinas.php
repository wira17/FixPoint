<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id   = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];
$current_file = basename(__FILE__);

$bulanIndo = [
    1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 5 => "Mei", 6 => "Juni",
    7 => "Juli", 8 => "Agustus", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"
];

// 🔹 Cek akses
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// 🔹 Ambil unit kerja user login
$unitQuery = mysqli_query($conn, "SELECT unit_kerja FROM users WHERE id='$user_id'");
$unitRow   = mysqli_fetch_assoc($unitQuery);
$unitLogin = $unitRow['unit_kerja'] ?? '';

// 🔹 Ambil daftar karyawan
$keyword = $_GET['keyword'] ?? '';
$userQuery = "SELECT id, nama, unit_kerja FROM users WHERE unit_kerja = '".mysqli_real_escape_string($conn, $unitLogin)."'";
if ($keyword != '') {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $userQuery .= " AND nama LIKE '%$kw%'";
}
$userResult = mysqli_query($conn, $userQuery);

// 🔹 Ambil daftar jam kerja
$jamQuery = "SELECT * FROM jam_kerja ORDER BY jam_mulai";
$jamResult = mysqli_query($conn, $jamQuery);
$jamList = [];     // label lengkap (untuk modal)
$jamNamaOnly = []; // nama singkat (untuk total shift)
if ($jamResult) {
    while ($j = mysqli_fetch_assoc($jamResult)) {
        $jamList[$j['id']] = $j['nama_jam'] . " ({$j['jam_mulai']}-{$j['jam_selesai']})";
        $jamNamaOnly[$j['id']] = $j['nama_jam'];
    }
}

// 🔹 Simpan jadwal per bulan
if (isset($_POST['simpan'])) {
    $user_id_post = (int)$_POST['user_id'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    $hari_kerja = $_POST['jam'] ?? [];

    foreach ($hari_kerja as $tanggal => $jam_id) {
        if ($jam_id != '') {
            $tgl = sprintf("%04d-%02d-%02d", $tahun, $bulan, $tanggal);
            $jam_id_esc = (int)$jam_id;

            $stmt = $conn->prepare("INSERT INTO jadwal_dinas 
                (user_id, tanggal, bulan, tahun, jam_kerja_id, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    jam_kerja_id=VALUES(jam_kerja_id), 
                    created_by=VALUES(created_by), 
                    created_at=NOW()");
            $stmt->bind_param("isiiis", $user_id_post, $tgl, $bulan, $tahun, $jam_id_esc, $nama_user);
            $stmt->execute();
            $stmt->close();
        }
    }

    $_SESSION['flash_message'] = "Jadwal berhasil disimpan.";
    header("Location: jadwal_dinas.php?bulan=$bulan&tahun=$tahun");
    exit;
}

// 🔹 Tentukan tanggal dalam bulan terpilih
$selected_bulan = $_GET['bulan'] ?? date('n');
$selected_tahun = $_GET['tahun'] ?? date('Y');
$daysInMonth = date('t', strtotime("$selected_tahun-$selected_bulan-01"));

// 🔹 Ambil data jadwal tersimpan
$savedQuery = "SELECT jd.*, u.nama AS nama_karyawan 
               FROM jadwal_dinas jd
               JOIN users u ON jd.user_id=u.id
               WHERE jd.bulan='$selected_bulan' AND jd.tahun='$selected_tahun'
               ORDER BY u.nama, jd.tanggal";
$savedResult = mysqli_query($conn, $savedQuery);

// 🔹 Susun data jadwal tersimpan
$savedData = [];
if ($savedResult) {
    while($row = mysqli_fetch_assoc($savedResult)){
        $tgl = (int)date('j', strtotime($row['tanggal']));
        $jamNama = $jamNamaOnly[$row['jam_kerja_id']] ?? '-';
        $savedData[$row['nama_karyawan']][$tgl] = $jamNama;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Jadwal Dinas Bulanan</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
.table-scroll { overflow-x: auto; }
.table-jadwal th, .table-jadwal td,
.saved-table th, .saved-table td { text-align: center; min-width: 50px; }
#total-shift { font-size: 14px; margin-bottom: 10px; }
#total-shift span { display: inline-block; margin-right: 15px; cursor: default; }

/* Jam icon as button */
.jam-icon { 
    background: transparent; 
    border: none; 
    padding: 0; 
    cursor: pointer; 
    color: #007bff; 
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.jam-icon i { font-size: 14px; }

/* modal list pointer */
.modal-jam .list-group-item { cursor: pointer; }

/* Saved table first column */
.saved-table th:first-child, .saved-table td:first-child {
    min-width: 50px; 
    text-align: center; 
    white-space: nowrap;
}

/* Kolom karyawan */
.saved-table th:nth-child(2), .saved-table td:nth-child(2) {
    min-width: 180px; 
    text-align: left; 
    white-space: nowrap;
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
<div class="card-header d-flex justify-content-between align-items-center">
<h4>Input Jadwal Dinas Bulanan</h4>
</div>

<div class="card-body">
<?php if(isset($_SESSION['flash_message'])): ?>
<div id="notif-toast" class="alert alert-info text-center"><?= $_SESSION['flash_message']; ?></div>
<?php unset($_SESSION['flash_message']); endif; ?>

<!-- 🔹 Total shift -->
<div id="total-shift"></div>

<form method="POST">
<div class="form-row mb-3">
    <div class="col-md-4">
        <select name="user_id" class="form-control" required>
            <option value="">Pilih Karyawan</option>
            <?php mysqli_data_seek($userResult, 0);
            while($u = mysqli_fetch_assoc($userResult)): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> - <?= htmlspecialchars($u['unit_kerja']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="bulan" class="form-control" required>
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m==$selected_bulan?'selected':'' ?>>
                    <?= $bulanIndo[$m] ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="tahun" class="form-control" required>
            <?php for($y=date('Y')-5;$y<=date('Y')+5;$y++): ?>
                <option value="<?= $y ?>" <?= $y==$selected_tahun?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan Jadwal</button>
    </div>
</div>

<?php if(!empty($jamList)): ?>
<div class="table-scroll">
<table class="table table-bordered table-sm table-jadwal">
<thead class="thead-dark">
<tr>
    <?php for($d=1;$d<=$daysInMonth;$d++): ?>
        <th><?= $d ?></th>
    <?php endfor; ?>
</tr>
</thead>
<tbody>
<tr>
    <?php for($d=1;$d<=$daysInMonth;$d++): ?>
    <td class="text-center">
        <!-- gunakan button agar mudah manipulasi html tanpa nested <i> -->
        <button type="button" class="jam-icon" data-tanggal="<?= $d ?>" aria-label="Pilih jam tanggal <?= $d ?>">
            <i class="fas fa-question-circle"></i>
        </button>
        <input type="hidden" name="jam[<?= $d ?>]" id="jam-<?= $d ?>" value="">
    </td>
    <?php endfor; ?>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
</form>

<hr>
<form method="get" class="form-inline mb-3">
    <label class="mr-2">Bulan</label>
    <select name="bulan" class="form-control mr-3">
        <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $m==$selected_bulan?'selected':'' ?>>
                <?= $bulanIndo[$m] ?>
            </option>
        <?php endfor; ?>
    </select>
    <label class="mr-2">Tahun</label>
    <select name="tahun" class="form-control mr-3">
        <?php for($y=date('Y')-5;$y<=date('Y')+5;$y++): ?>
            <option value="<?= $y ?>" <?= $y==$selected_tahun?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Tampilkan</button>
    <a href="cetak_jadwal_dinas.php?bulan=<?= $selected_bulan ?>&tahun=<?= $selected_tahun ?>" target="_blank" class="btn btn-info">
      <i class="fas fa-print"></i> Cetak
    </a>
</form>

<div class="saved-table table-scroll">
<h5>Data Jadwal Tersimpan - <?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?></h5>
<table class="table table-bordered table-sm">
<thead class="thead-light">
<tr>
    <th>No.</th>
    <th>Karyawan</th>
    <?php for($d=1;$d<=$daysInMonth;$d++): ?>
        <th><?= $d ?></th>
    <?php endfor; ?>
</tr>
</thead>
<tbody>
<?php $no = 1; ?>
<?php foreach($savedData as $karyawan => $tglData): ?>
<tr>
    <td><?= $no++; ?></td>
    <td><?= htmlspecialchars($karyawan) ?></td>
    <?php for($d=1;$d<=$daysInMonth;$d++): ?>
        <td><?= htmlspecialchars($tglData[$d] ?? '-') ?></td>
    <?php endfor; ?>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</div>
</div>

</section>
</div>
</div>
</div>

<!-- Modal Jam -->
<div class="modal fade" id="modalJam" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-jam">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Jam Kerja</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <ul class="list-group">
        <?php foreach($jamList as $id => $jam): ?>
          <li class="list-group-item" data-id="<?= $id ?>"><?= htmlspecialchars($jam) ?></li>
        <?php endforeach; ?>
        </ul>
      </div>
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

<script>
(function($){
  var $modal = $('#modalJam');
  var currentTanggal = 0;
  var modalBusy = false;
  var jamKerjaNama = <?php echo json_encode($jamNamaOnly); ?> || {};

  $(document).ready(function(){
    // pindahkan modal ke body supaya tidak terpengaruh wrapper Stisla
    if ($modal.length && !$modal.parent().is('body')) {
      $modal.appendTo('body');
    }

    // cleanup leftover backdrops on load (safety)
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');

    // handler open modal via tombol .jam-icon
    $(document).on('click', '.jam-icon', function(e){
      e.preventDefault();
      e.stopPropagation();
      if (modalBusy) return; // mencegah race
      currentTanggal = $(this).data('tanggal') || 0;

      // pastikan tidak ada backdrop tertinggal
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open');
      // reset modal internal state
      $modal.removeClass('show').attr('aria-hidden', 'false').css('display','block');
      // show modal
      try {
        $modal.modal('show');
      } catch(err) {
        // fallback manual
        $('.modal-backdrop').remove();
        $('body').addClass('modal-open');
        $modal.addClass('show').css('display','block');
      }
    });

    // ketika modal mulai show/hide set flag
    $modal.on('show.bs.modal', function(){ modalBusy = true; });
    $modal.on('shown.bs.modal', function(){
      modalBusy = false;
      // fokus ke list pertama supaya keyboard accessible
      $(this).find('.list-group-item').first().focus();
    });

    $modal.on('hide.bs.modal', function(){ modalBusy = true; });
    $modal.on('hidden.bs.modal', function(){
      modalBusy = false;
      // bersihkan backdrop & kelas body agar tidak stuck
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open');
      // reset styles kelas agar siap dipakai lagi
      $modal.removeAttr('style').removeClass('show').attr('aria-hidden','true');
      $modal.find('.modal-dialog').removeAttr('style');
    });

    // pilih jam kerja
    $(document).on('click', '.modal-jam .list-group-item', function(e){
      e.preventDefault();
      e.stopPropagation();
      if (modalBusy) return;
      var jamId = $(this).data('id');
      var jamText = $(this).text();

      // isi input hidden tanggal yg sesuai
      if (currentTanggal) {
        $('#jam-' + currentTanggal).val(jamId);
        // ubah tampilan tombol menjadi icon + teks (safely)
        var $btn = $('.jam-icon[data-tanggal="' + currentTanggal + '"]');
        if ($btn.length) {
          $btn.html('<i class="fas fa-clock text-success"></i> ' + jamText);
        }
      }

      // beri sedikit delay agar UI update, lalu hide modal
      setTimeout(function(){
        try { $modal.modal('hide'); }
        catch(err){
          // fallback: manual hide
          $modal.removeClass('show').hide();
          $('.modal-backdrop').remove();
          $('body').removeClass('modal-open');
        }
      }, 80);

      // update counter shift
      updateShiftCount();
    });

    // Hitung total shift
    function updateShiftCount(){
      var shiftCount = {};
      $.each(jamKerjaNama, function(id, nama){
        shiftCount[id] = 0;
      });

      $('input[name^="jam"]').each(function(){
        var val = $(this).val();
        if (val && shiftCount[val] !== undefined){
          shiftCount[val]++;
        }
      });

      var html = '';
      $.each(jamKerjaNama, function(id, nama){
        html += '<span style="margin-right:12px;">' + nama + ': ' + shiftCount[id] + '</span>';
      });
      $('#total-shift').html(html);
    }

    // initial update
    updateShiftCount();

    // show toast if any
    var toast = $('#notif-toast');
    if (toast.length) toast.fadeIn(300).delay(2000).fadeOut(500);
  });
})(jQuery);
</script>

</body>
</html>
 