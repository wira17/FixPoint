<?php
// input_harian.php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id   = $_SESSION['user_id'] ?? 0;

// ambil nama user login dari tabel users
$nama_user = '';
if ($user_id > 0) {
    $qUser = mysqli_query($conn, "SELECT nama FROM users WHERE id = '".intval($user_id)."' LIMIT 1");
    if ($qUser && $rowU = mysqli_fetch_assoc($qUser)) {
        $nama_user = $rowU['nama'];
    }
}

$activeTab = $_GET['tab'] ?? 'data';
$filterJenis = $_GET['filter_jenis'] ?? ''; // filter jenis indikator

// akses menu
$current_file = basename(__FILE__);
$rAkses = mysqli_query($conn, "SELECT 1 
            FROM akses_menu 
            JOIN menu ON akses_menu.menu_id = menu.id 
            WHERE akses_menu.user_id = '".intval($user_id)."' 
              AND menu.file_menu = '".mysqli_real_escape_string($conn,$current_file)."'");
if (!$rAkses || mysqli_num_rows($rAkses) == 0) {
    echo "<script>alert('Tidak ada akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// proses simpan
if (isset($_POST['simpan'])) {
    $jenis      = mysqli_real_escape_string($conn, $_POST['jenis_indikator']);
    $id_indikator = intval($_POST['id_indikator']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $numerator  = intval($_POST['numerator']);
    $denominator= intval($_POST['denominator']);
    $ket        = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $petugas    = mysqli_real_escape_string($conn, $nama_user);

    if ($jenis && $id_indikator && $tanggal && $denominator > 0) {
        $q = "INSERT INTO indikator_harian 
              (jenis_indikator, id_indikator, tanggal, numerator, denominator, keterangan, petugas) 
              VALUES 
              ('$jenis','$id_indikator','$tanggal','$numerator','$denominator','$ket','$petugas')";

        if (mysqli_query($conn, $q)) {
            $_SESSION['flash_message'] = "Data berhasil disimpan.";
            header("Location: input_harian.php");
            exit;
        } else {
            $_SESSION['flash_message'] = "Gagal: " . mysqli_error($conn);
            header("Location: input_harian.php?tab=input");
            exit;
        }
    } else {
        $_SESSION['flash_message'] = "Lengkapi semua field!";
        header("Location: input_harian.php?tab=input");
        exit;
    }
}

// hapus data
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM indikator_harian WHERE id_harian='$id'");
    $_SESSION['flash_message'] = "Data berhasil dihapus.";
    header("Location: input_harian.php");
    exit;
}

// ambil daftar indikator
$nasional = mysqli_query($conn, "SELECT id_nasional AS id, nama_indikator, standar, 'nasional' AS jenis FROM indikator_nasional");
$rs       = mysqli_query($conn, "SELECT id_rs AS id, nama_indikator, standar, 'rs' AS jenis FROM indikator_rs");
$unit     = mysqli_query($conn, "SELECT iu.id_unit AS id, iu.nama_indikator, iu.standar, u.nama_unit, 'unit' AS jenis
                                 FROM indikator_unit iu 
                                 LEFT JOIN unit_kerja u ON iu.unit_id=u.id 
                                 ORDER BY u.nama_unit, iu.nama_indikator");

$indikators = [];
while($row = mysqli_fetch_assoc($nasional)) $indikators['nasional'][] = $row;
while($row = mysqli_fetch_assoc($rs))       $indikators['rs'][] = $row;
while($row = mysqli_fetch_assoc($unit))     $indikators['unit'][] = $row;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Input Harian Indikator</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .dokumen-table { font-size: 13px; white-space: nowrap; }
    .dokumen-table th, .dokumen-table td { padding: 6px 10px; }
    .info-box { background:#f8f9fa; border:1px solid #ddd; padding:10px; margin-top:10px; border-radius:6px; }
    .flash-message { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; background: rgba(40, 167, 69, 0.95); color: #fff; padding: 20px 30px; border-radius: 10px; font-size: 16px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.3); animation: fadeIn 0.5s; }
    .flash-content i { margin-right: 8px; font-size: 20px; }
    @keyframes fadeIn { from { opacity: 0; transform: translate(-50%, -60%); } to { opacity: 1; transform: translate(-50%, -50%); } }
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

        <?php if(isset($_SESSION['flash_message'])): ?>
          <div id="flashMessage" class="flash-message">
            <div class="flash-content">
              <i class="fas fa-check-circle"></i> <?= $_SESSION['flash_message']; ?>
            </div>
          </div>
          <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <div class="card">
          <div class="card-header"><h4>Input Harian Indikator</h4></div>
          <div class="card-body">
            <ul class="nav nav-tabs">
              <li class="nav-item"><a class="nav-link <?= ($activeTab=='input')?'active':'' ?>" data-toggle="tab" href="#input">Input Data</a></li>
              <li class="nav-item"><a class="nav-link <?= ($activeTab=='data')?'active':'' ?>" data-toggle="tab" href="#data">Data Harian</a></li>
            </ul>

            <div class="tab-content mt-3">
              <!-- FORM INPUT -->
              <div class="tab-pane fade <?= ($activeTab=='input')?'show active':'' ?>" id="input">
                <form method="POST">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Jenis Indikator</label>
                        <select name="jenis_indikator" id="jenis_indikator" class="form-control" required>
                          <option value="">-- Pilih Jenis --</option>
                          <option value="nasional">Nasional</option>
                          <option value="rs">RS</option>
                          <option value="unit">Unit</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label><i class="fas fa-list"></i> Indikator</label>
                        <select name="id_indikator" id="id_indikator" class="form-control select2" required>
                          <option value="">-- Pilih Indikator --</option>
                        </select>
                      </div>
                      <div id="indikatorInfo" class="info-box" style="display:none;"></div>
                      <div class="form-group mt-3">
                        <label><i class="fas fa-calendar-alt"></i> Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><i class="fas fa-sort-numeric-up"></i> Numerator</label>
                        <input type="number" name="numerator" class="form-control" required>
                      </div>
                      <div class="form-group">
                        <label><i class="fas fa-divide"></i> Denominator</label>
                        <input type="number" name="denominator" class="form-control" required>
                      </div>
                      <div class="form-group">
                        <label><i class="fas fa-comment-dots"></i> Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                      </div>
                      <div class="form-group text-right mt-4">
                        <button type="submit" name="simpan" class="btn btn-primary">
                          <i class="fas fa-save"></i> Simpan
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>

              <!-- DATA -->
              <div class="tab-pane fade <?= ($activeTab=='data')?'show active':'' ?>" id="data">
                <div class="mb-3">
                  <form method="GET" class="form-inline">
                    <input type="hidden" name="tab" value="data">
                    <label class="mr-2">Filter Jenis:</label>
                    <select name="filter_jenis" class="form-control mr-2" onchange="this.form.submit()">
                      <option value="">-- Semua --</option>
                      <option value="nasional" <?= ($filterJenis=='nasional')?'selected':'' ?>>Nasional</option>
                      <option value="rs" <?= ($filterJenis=='rs')?'selected':'' ?>>RS</option>
                      <option value="unit" <?= ($filterJenis=='unit')?'selected':'' ?>>Unit</option>
                    </select>
                  </form>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped dokumen-table">
                    <thead class="thead-light">
                      <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Indikator</th>
                        <th>Numerator</th>
                        <th>Denominator</th>
                        <th>Persentase</th>
                        <th>Keterangan</th>
                        <th>Petugas</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php
                    $qStr = "SELECT h.*, 
                              CASE h.jenis_indikator 
                                WHEN 'nasional' THEN (SELECT nama_indikator FROM indikator_nasional WHERE id_nasional=h.id_indikator) 
                                WHEN 'rs' THEN (SELECT nama_indikator FROM indikator_rs WHERE id_rs=h.id_indikator) 
                                WHEN 'unit' THEN (SELECT nama_indikator FROM indikator_unit WHERE id_unit=h.id_indikator) 
                              END AS nama_indikator
                              FROM indikator_harian h";

                    if($filterJenis) $qStr .= " WHERE h.jenis_indikator='".mysqli_real_escape_string($conn,$filterJenis)."'";

                    $qStr .= " ORDER BY h.tanggal DESC, h.id_harian DESC";

                    $q = mysqli_query($conn, $qStr);
                    $no=1;
                    while($row = mysqli_fetch_assoc($q)): 
                      $persen = ($row['denominator'] > 0) ? ($row['numerator']/$row['denominator']*100) : 0;
                    ?>
                      <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= strtoupper($row['jenis_indikator']) ?></td>
                        <td><?= htmlspecialchars($row['nama_indikator']) ?></td>
                        <td><?= $row['numerator'] ?></td>
                        <td><?= $row['denominator'] ?></td>
                        <td><?= number_format($persen,2) ?>%</td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><?= htmlspecialchars($row['petugas']) ?></td>
                      </tr>
                    <?php endwhile; ?>
                    </tbody>
                  </table>
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

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function(){
  setTimeout(function(){ $("#flashMessage").fadeOut("slow"); }, 3000);
  $('.select2').select2({placeholder: "-- Pilih Indikator --", allowClear: true, width: '100%'});
  var indikatorData = <?= json_encode($indikators) ?>;

  $("#jenis_indikator").change(function(){
    var jenis = $(this).val();
    var $idInd = $("#id_indikator");
    $idInd.empty().append('<option value=""></option>');
    if(indikatorData[jenis]){
      indikatorData[jenis].forEach(function(opt){
        var text = (opt.nama_unit? opt.nama_unit+' - ':'')+opt.nama_indikator;
        $idInd.append('<option data-standar="'+opt.standar+'" value="'+opt.id+'">'+text+'</option>');
      });
    }
    $idInd.val(null).trigger('change');
    $("#indikatorInfo").hide();
  });

  $("#id_indikator").change(function(){
    var standar = $(this).find(':selected').data('standar') || '';
    var nama = $(this).find(':selected').text();
    if(nama){ $("#indikatorInfo").html("<b>Indikator:</b> "+nama+"<br><b>Standar:</b> "+standar).show(); }
    else { $("#indikatorInfo").hide(); }
  });
});
</script>
</body>
</html>
