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
if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// === Ambil daftar komponen penilaian ===
$komponen = [];
$qKomponen = mysqli_query($conn, "SELECT id, nama_set FROM master_komponen_praktek ORDER BY id ASC");
if(!$qKomponen) die("Query komponen gagal: " . mysqli_error($conn));
while($k = mysqli_fetch_assoc($qKomponen)){
    $komponen[$k['id']] = $k['nama_set'];
}

// === SIMPAN NILAI PRAKTEK ===
if (isset($_POST['simpan'])) {
    $perawat_id = (int)$_POST['perawat_id'];
    $kategori_id = (int)$_POST['kategori'];

    $qKat = mysqli_query($conn, "SELECT nama_jenis FROM jenis_kredensial WHERE id='$kategori_id'");
    if(!$qKat) die("Query kategori gagal: " . mysqli_error($conn));
    $katRow = mysqli_fetch_assoc($qKat);
    $kategori = $katRow['nama_jenis'];

    $total = 0;
    $nilai_komponen = [];

    foreach($komponen as $id_kom => $nama_kom){
        $nilai = isset($_POST['kom_'.$id_kom]) ? (float)$_POST['kom_'.$id_kom] : 0;
        $nilai_komponen[$id_kom] = $nilai;
        $total += $nilai;
    }

    $jumlah_komponen = count($komponen);
    $nilai_akhir = $jumlah_komponen > 0 ? $total / $jumlah_komponen : 0;
    $nilai_akhir = ($nilai_akhir == floor($nilai_akhir)) ? floor($nilai_akhir) : round($nilai_akhir,2);
    $status = ($nilai_akhir >= 75) ? 'Lulus' : 'Remedial';

    // Insert ke nilai_praktek
    $insert = mysqli_query($conn, "INSERT INTO nilai_praktek 
        (perawat_id, kategori, nilai_akhir, status, tanggal_input)
        VALUES ('$perawat_id','$kategori','$nilai_akhir','$status', NOW())");

    if($insert){
        $id_nilai = mysqli_insert_id($conn);
        // Insert detail per komponen
        foreach($nilai_komponen as $id_kom => $nilai){
            mysqli_query($conn, "INSERT INTO nilai_praktek_detail (nilai_id, komponen_id, nilai) 
                VALUES ('$id_nilai', '$id_kom', '$nilai')");
        }

        $_SESSION['flash_message'] = "✅ Nilai praktek berhasil disimpan.";
        header("Location: praktek.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>❌ Gagal menyimpan nilai praktek: ".mysqli_error($conn)."</div>";
    }
}

// === PENCARIAN & FILTER ===
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$filter_kategori = isset($_GET['filter_kategori']) ? mysqli_real_escape_string($conn, $_GET['filter_kategori']) : '';
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$filter_tanggal = isset($_GET['filter_tanggal']) ? mysqli_real_escape_string($conn, $_GET['filter_tanggal']) : '';

$where_clauses = [];
if(!empty($keyword)) $where_clauses[] = "u.nama LIKE '%$keyword%'";
if(!empty($filter_kategori)) $where_clauses[] = "n.kategori='$filter_kategori'";
if(!empty($filter_status)) $where_clauses[] = "n.status='$filter_status'";
if(!empty($filter_tanggal)) $where_clauses[] = "DATE(n.tanggal_input)='$filter_tanggal'";
$where = count($where_clauses) ? 'WHERE '.implode(' AND ', $where_clauses) : '';

// Query data praktek
$query = "SELECT n.id, n.perawat_id, n.kategori, n.nilai_akhir, n.status, n.tanggal_input, 
                 u.nama AS perawat_nama
          FROM nilai_praktek n
          LEFT JOIN users u ON n.perawat_id = u.id
          $where
          ORDER BY n.tanggal_input DESC";
$result = mysqli_query($conn, $query);
if(!$result) die("Query data praktek gagal: ".mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Input Nilai Praktek Perawat</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/components.css">
<style>
#notif-toast {
  position: fixed;
  top: 10%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 9999;
  display: none;
  min-width: 320px;
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
              <h4><i class="fas fa-user-nurse"></i> Nilai Praktek Perawat
                <a href="#" data-toggle="modal" data-target="#modalPanduan" class="text-danger ml-2"><i class="fas fa-question-circle"></i></a>
              </h4>
              <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah"><i class="fas fa-plus-circle"></i> Input Nilai</button>
            </div>
            <div class="card-body">
              <?php
              if(isset($_SESSION['flash_message'])){
                  echo "<div id='notif-toast' class='alert alert-info text-center'>{$_SESSION['flash_message']}</div>";
                  unset($_SESSION['flash_message']);
              }
              ?>

              <form method="GET" class="form-inline mb-3">
                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" class="form-control mr-2 mb-2" placeholder="🔍 Cari nama perawat...">

                <select name="filter_kategori" class="form-control mr-2 mb-2">
                  <option value="">-- Semua Kategori --</option>
                  <?php
                  $qKat = mysqli_query($conn, "SELECT nama_jenis FROM jenis_kredensial ORDER BY nama_jenis ASC");
                  while($r = mysqli_fetch_assoc($qKat)){
                      $sel = ($filter_kategori==$r['nama_jenis'])?'selected':'';
                      echo "<option value='".htmlspecialchars($r['nama_jenis'])."' $sel>".htmlspecialchars($r['nama_jenis'])."</option>";
                  }
                  ?>
                </select>

                <select name="filter_status" class="form-control mr-2 mb-2">
                  <option value="">-- Semua Status --</option>
                  <option value="Lulus" <?= ($filter_status=='Lulus')?'selected':'' ?>>Lulus</option>
                  <option value="Remedial" <?= ($filter_status=='Remedial')?'selected':'' ?>>Remedial</option>
                </select>

                <input type="date" name="filter_tanggal" value="<?= $filter_tanggal ?>" class="form-control mr-2 mb-2">
                <button type="submit" class="btn btn-primary btn-sm mb-2"><i class="fas fa-filter"></i> Filter</button>
                <a href="praktek.php" class="btn btn-secondary btn-sm mb-2 ml-2"><i class="fas fa-sync-alt"></i> Reset</a>
              </form>

              <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="thead-dark text-center">
                    <tr>
                      <th>No</th>
                      <th>Nama Perawat</th>
                      <th>Kategori</th>
                      <th>Nilai Akhir</th>
                      <th>Status</th>
                      <th>Tanggal Ujian</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no=1;
                    if(mysqli_num_rows($result)>0){
                        while($row=mysqli_fetch_assoc($result)){
                            $nilai_display = ($row['nilai_akhir']==floor($row['nilai_akhir']))?floor($row['nilai_akhir']):$row['nilai_akhir'];
                            echo "<tr>
                                <td class='text-center'>{$no}</td>
                                <td>".htmlspecialchars($row['perawat_nama'])."</td>
                                <td class='text-center'>".htmlspecialchars($row['kategori'])."</td>
                                <td class='text-center'>{$nilai_display}</td>
                                <td class='text-center'>{$row['status']}</td>
                                <td class='text-center'>".date('d-m-Y H:i', strtotime($row['tanggal_input']))."</td>
                                <td class='text-center'>
                                  <a href='cetak_lembar_praktek.php?id={$row['id']}' target='_blank' class='btn btn-primary btn-sm'><i class='fas fa-print'></i> Print</a>
                                </td>
                            </tr>";
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Tidak ada data.</td></tr>";
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

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Komponen Penilaian</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Perawat</label>
              <select name="perawat_id" class="form-control" required>
                <option value="">-- Pilih Perawat --</option>
                <?php
                $qPerawat=mysqli_query($conn,"SELECT id,nama,jabatan FROM users WHERE status='active' ORDER BY nama ASC");
                while($p=mysqli_fetch_assoc($qPerawat)){
                    echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['nama'].' / '.$p['jabatan']).'</option>';
                }
                ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Kategori</label>
              <select name="kategori" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <?php
                $qKat=mysqli_query($conn,"SELECT id,nama_jenis FROM jenis_kredensial ORDER BY nama_jenis ASC");
                while($k=mysqli_fetch_assoc($qKat)){
                    echo '<option value="'.$k['id'].'">'.htmlspecialchars($k['nama_jenis']).'</option>';
                }
                ?>
              </select>
            </div>
          </div>

          <hr>
          <h6><b>Isi Nilai Per Komponen</b></h6>
          <div class="form-row">
            <?php foreach($komponen as $id=>$nama){ ?>
              <div class="form-group col-md-4">
                <label><?= htmlspecialchars($nama) ?></label>
                <input type="number" step="0.1" min="0" max="100" name="kom_<?= $id ?>" class="form-control" required>
              </div>
            <?php } ?>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan Nilai</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times-circle"></i> Tutup</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL PANDUAN -->
<div class="modal fade" id="modalPanduan" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-question-circle"></i> Panduan Input Nilai Praktek</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <ol>
          <li>Pilih perawat.</li>
          <li>Pilih kategori.</li>
          <li>Isi nilai setiap komponen sesuai instruksi.</li>
          <li>Nilai akhir dihitung otomatis dan status Lulus/Remedial ditentukan berdasarkan >= 75.</li>
          <li>Data berhasil disimpan akan muncul notifikasi dan tabel otomatis terupdate.</li>
        </ol>
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
<script>
$(document).ready(function(){
    var toast = $('#notif-toast');
    if(toast.length) toast.fadeIn(300).delay(2500).fadeOut(500);
});
</script>
</body>
</html>
