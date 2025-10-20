<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// === Hitung bulan & tahun sebelumnya ===
$bulan_sekarang = date('n');
$tahun_sekarang = date('Y');
if ($bulan_sekarang == 1) {
  $bulan_lalu = 12;
  $tahun_lalu = $tahun_sekarang - 1;
} else {
  $bulan_lalu = $bulan_sekarang - 1;
  $tahun_lalu = $tahun_sekarang;
}

// === Query Data ===
$data_satu = mysqli_query($conn, "
  SELECT endpoint, SUM(jumlah) AS total 
  FROM satu_sehat 
  WHERE bulan='$bulan_lalu' AND tahun='$tahun_lalu'
  GROUP BY endpoint ORDER BY endpoint ASC
");

$data_antrian = mysqli_query($conn, "
  SELECT tahun, bulan, jumlah_sep, jumlah_antri, jumlah_mjkn, persen_all, persen_mjkn 
  FROM semua_antrian 
  WHERE tahun='$tahun_sekarang'
  ORDER BY tahun ASC, bulan ASC
");

$data_progres = mysqli_query($conn, "
  SELECT bulan, tahun, progres 
  FROM progres_kerja
  WHERE bulan='$bulan_lalu' AND tahun='$tahun_lalu'
  ORDER BY tahun DESC, bulan DESC
");

$data_maint = mysqli_query($conn, "
  SELECT id, nama_teknisi, waktu_input, kondisi_fisik, fungsi_perangkat, catatan 
  FROM maintanance_rutin 
  ORDER BY waktu_input DESC
");

$data_hw = mysqli_query($conn, "
  SELECT nomor_tiket, nama, unit_kerja, kategori, tanggal_input, status, teknisi_nama 
  FROM tiket_it_hardware 
  ORDER BY tanggal_input DESC
");

$data_sw = mysqli_query($conn, "
  SELECT nomor_tiket, nama, unit_kerja, kategori, tanggal_input, status, teknisi_nama 
  FROM tiket_it_software 
  ORDER BY tanggal_input DESC
");

$bulan_list = [
  1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Slide Pelaporan</title>
<link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
<style>
body {
  background: linear-gradient(135deg, #0a2342, #0d335d);
  color: #fff;
  font-family: 'Segoe UI', sans-serif;
  overflow: hidden;
}
.slide {
  display: none;
  height: 100vh;
  padding: 40px;
  text-align: center;
}
.active-slide { display: block; animation: fadeIn 0.8s; }
@keyframes fadeIn { from {opacity:0;} to {opacity:1;} }

h2 {
  color: #00d1ff;
  font-weight: 700;
  margin-bottom: 25px;
  text-shadow: 0 0 8px rgba(0,0,0,0.4);
}

.card-table {
  background: rgba(255,255,255,0.08);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 0 15px rgba(0,0,0,0.3);
  max-width: 1200px;
  margin: 0 auto;
}

.table {
  table-layout: fixed;
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.table th, .table td {
  color: #fff;
  vertical-align: middle;
  word-wrap: break-word;
  padding: 8px;
}
.table thead {
  background: rgba(255,255,255,0.15);
  font-weight: bold;
}
.table tbody tr:hover {
  background-color: rgba(255,255,255,0.08);
}
.table-responsive {
  max-height: 65vh;
  overflow-y: auto;
  border-radius: 10px;
}

.nav-btn {
  position: fixed;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.1);
  border: none;
  color: #fff;
  font-size: 28px;
  padding: 18px 22px;
  cursor: pointer;
  border-radius: 50%;
  transition: 0.3s;
  z-index: 99;
}
.nav-btn:hover { background: rgba(255,255,255,0.25); }
#prevBtn { left: 25px; }
#nextBtn { right: 25px; }

footer {
  position: absolute;
  bottom: 15px;
  width: 100%;
  text-align: center;
  font-size: 13px;
  color: #bbb;
}

.action-btn {
  position: fixed;
  top: 20px;
  z-index: 100;
  background: rgba(255,255,255,0.1);
  color: #fff;
  border: none;
  font-size: 20px;
  padding: 10px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.3s;
}
.action-btn:hover { background: rgba(255,255,255,0.25); transform: scale(1.05); }
.back-btn { left: 25px; }
</style>
</head>
<body>

<!-- Tombol kembali -->
<button class="action-btn back-btn" onclick="window.location.href='dashboard.php'">
  <i class="fas fa-home"></i>
</button>

<!-- === SLIDE 2: % ALL === -->
<div class="slide active-slide" id="slide2">
  <h2><i class="fas fa-chart-line"></i> Persentase Semua Antrian (% ALL) - Tahun <?= $tahun_sekarang ?></h2>
  <div class="card-table table-responsive">
    <table class="table table-dark table-bordered table-striped text-center">
      <thead>
        <tr>
          <th>No</th>
          <th>Bulan</th>
          <th>Tahun</th>
          <th>Jumlah SEP</th>
          <th>Jumlah Antrian</th>
          <th>% ALL</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1; 
        mysqli_data_seek($data_antrian, 0); 
        while ($r = mysqli_fetch_assoc($data_antrian)) : 
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $bulan_list[$r['bulan']] ?></td>
          <td><?= $r['tahun'] ?></td>
          <td><?= number_format($r['jumlah_sep']) ?></td>
          <td><?= number_format($r['jumlah_antri']) ?></td>
          <td class="text-success font-weight-bold"><?= number_format($r['persen_all'], 2) ?>%</td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>


<!-- === SLIDE 3: % JKN === -->
<div class="slide" id="slide3">
  <h2><i class="fas fa-id-card"></i> Persentase JKN - Tahun <?= $tahun_sekarang ?></h2>
  <div class="card-table table-responsive">
    <table class="table table-dark table-bordered table-striped text-center">
      <thead>
        <tr>
          <th>No</th>
          <th>Bulan</th>
          <th>Tahun</th>
          <th>Jumlah SEP</th>
          <th>Jumlah Mobile JKN</th>
          <th>% Mobile JKN</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        // Pastikan pointer query dikembalikan ke awal
        mysqli_data_seek($data_antrian, 0);
        while ($r = mysqli_fetch_assoc($data_antrian)) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= $bulan_list[$r['bulan']] ?></td>
            <td><?= $r['tahun'] ?></td>
            <td><?= number_format($r['jumlah_sep']) ?></td>
            <td><?= number_format($r['jumlah_mjkn']) ?></td>
            <td class="text-info font-weight-bold"><?= number_format($r['persen_mjkn'], 2) ?>%</td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<!-- === SLIDE 1: SATUSEHAT === -->
<div class="slide" id="slide1">
  <h2><i class="fas fa-laptop-medical"></i> Laporan SATUSEHAT - <?= $bulan_list[$bulan_lalu]." ".$tahun_lalu ?></h2>
  <div class="card-table">
    <canvas id="chartSatuSehat" height="130"></canvas>
  </div>
</div>

<!-- === SLIDE 7: Tiket IT Software === -->
<div class="slide" id="slide7">
  <h2><i class="fas fa-code"></i> Tiket IT Software</h2>
  <div class="card-table table-responsive">
    <table class="table table-dark table-bordered table-striped text-center">
      <thead>
        <tr><th>No</th><th>No Tiket</th><th>Nama</th><th>Unit</th><th>Kategori</th><th>Status</th><th>Teknisi</th><th>Tanggal</th></tr>
      </thead>
      <tbody>
        <?php $no=1; while($s=mysqli_fetch_assoc($data_sw)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($s['nomor_tiket']) ?></td>
          <td><?= htmlspecialchars($s['nama']) ?></td>
          <td><?= htmlspecialchars($s['unit_kerja']) ?></td>
          <td><?= htmlspecialchars($s['kategori']) ?></td>
          <td><?= htmlspecialchars($s['status']) ?></td>
          <td><?= htmlspecialchars($s['teknisi_nama']) ?></td>
          <td><?= $s['tanggal_input'] ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- === SLIDE 6: Tiket IT Hardware === -->
<div class="slide" id="slide6">
  <h2><i class="fas fa-desktop"></i> Tiket IT Hardware</h2>
  <div class="card-table table-responsive">
    <table class="table table-dark table-bordered table-striped text-center">
      <thead>
        <tr><th>No</th><th>No Tiket</th><th>Nama</th><th>Unit</th><th>Kategori</th><th>Status</th><th>Teknisi</th><th>Tanggal</th></tr>
      </thead>
      <tbody>
        <?php $no=1; while($h=mysqli_fetch_assoc($data_hw)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($h['nomor_tiket']) ?></td>
          <td><?= htmlspecialchars($h['nama']) ?></td>
          <td><?= htmlspecialchars($h['unit_kerja']) ?></td>
          <td><?= htmlspecialchars($h['kategori']) ?></td>
          <td><?= htmlspecialchars($h['status']) ?></td>
          <td><?= htmlspecialchars($h['teknisi_nama']) ?></td>
          <td><?= $h['tanggal_input'] ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- === SLIDE 5: Maintenance === -->
<div class="slide" id="slide5">
  <h2><i class="fas fa-tools"></i> Data Maintenance Rutin</h2>
  <div class="card-table table-responsive">
    <table class="table table-dark table-bordered table-striped text-center">
      <thead><tr><th>No</th><th>Teknisi</th><th>Waktu</th><th>Kondisi</th><th>Fungsi</th><th>Catatan</th></tr></thead>
      <tbody>
        <?php $no=1; while($m=mysqli_fetch_assoc($data_maint)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($m['nama_teknisi']) ?></td>
          <td><?= $m['waktu_input'] ?></td>
          <td><?= htmlspecialchars($m['kondisi_fisik']) ?></td>
          <td><?= htmlspecialchars($m['fungsi_perangkat']) ?></td>
          <td><?= nl2br(htmlspecialchars($m['catatan'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- === SLIDE 4: Progres Kerja === -->
<div class="slide" id="slide4">
  <h2><i class="fas fa-tasks"></i> Progres Kerja Bulan <?= $bulan_list[$bulan_lalu]." ".$tahun_lalu ?></h2>
  <div class="card-table table-responsive">
    <table class="table table-bordered table-striped table-dark text-center">
      <thead><tr><th>No</th><th>Bulan</th><th>Tahun</th><th>Progres</th></tr></thead>
      <tbody>
        <?php $no=1; while($p=mysqli_fetch_assoc($data_progres)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $bulan_list[$p['bulan']] ?></td>
          <td><?= $p['tahun'] ?></td>
          <td><?= nl2br(htmlspecialchars($p['progres'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>


<!-- === SLIDE 8: Handling Time IT Hardware === -->
<div class="slide" id="slide8">
  <h2><i class="fas fa-clock"></i> Handling Time Tiket IT Hardware - <?= $bulan_list[$bulan_lalu] . " " . $tahun_lalu ?></h2>
  <div class="card-table">
    <div class="table-responsive-custom">
      <table class="table table-bordered table-sm table-hover text-center">
        <thead class="thead-dark">
          <tr>
            <th>No</th>
            <th>Nomor Tiket</th>
            <th>Kategori</th>
            <th>Kendala / Teknisi</th>
            <th>Waktu Order</th>
            <th>Waktu Selesai</th>
            <th>Lama Pengerjaan</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;

        // Hitung bulan & tahun sebelumnya
        $bulan_sekarang = date('n');
        $tahun_sekarang = date('Y');
        if ($bulan_sekarang == 1) {
            $bulan_lalu = 12;
            $tahun_lalu = $tahun_sekarang - 1;
        } else {
            $bulan_lalu = $bulan_sekarang - 1;
            $tahun_lalu = $tahun_sekarang;
        }

        $query = "SELECT * FROM tiket_it_hardware 
                  WHERE MONTH(tanggal_input) = '$bulan_lalu' 
                    AND YEAR(tanggal_input) = '$tahun_lalu'
                  ORDER BY tanggal_input DESC";
        $result = mysqli_query($conn, $query);

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

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $kendala = htmlspecialchars($row['kendala']);
                $teknisi = htmlspecialchars($row['teknisi_nama']);
                $tgl_input = formatTanggal($row['tanggal_input']);
                $selesai_time = formatTanggal($row['waktu_selesai']);
                $lama = hitungDurasi($row['tanggal_input'], $row['waktu_selesai']);

                echo "<tr>";
                echo "<td>{$no}</td>";
                echo "<td>{$row['nomor_tiket']}</td>";
                echo "<td>{$row['kategori']}</td>";
                echo "<td>{$kendala} / {$teknisi}</td>";
                echo "<td>{$tgl_input}</td>";
                echo "<td>{$selesai_time}</td>";
                echo "<td>{$lama}</td>";
                echo "</tr>";
                $no++;
            }
        } else {
            echo "<tr><td colspan='7'>Tidak ada data ditemukan.</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- === SLIDE 9: Handling Time IT Software === -->
<div class="slide" id="slide9">
  <h2><i class="fas fa-clock"></i> Handling Time Tiket IT Software - <?= $bulan_list[$bulan_lalu] . " " . $tahun_lalu ?></h2>
  <div class="card-table">
    <div class="table-responsive-custom">
      <table class="table table-bordered table-sm table-hover text-center">
        <thead class="thead-dark">
          <tr>
            <th>No</th>
            <th>Nomor Tiket</th>
            <th>Kategori</th>
            <th>Kendala / Teknisi</th>
            <th>Waktu Order</th>
            <th>Waktu Selesai</th>
            <th>Lama Pengerjaan</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;

        // Ambil data tiket IT Software bulan & tahun sebelumnya
        $query_sw = "SELECT * FROM tiket_it_software 
                     WHERE MONTH(tanggal_input) = '$bulan_lalu' 
                       AND YEAR(tanggal_input) = '$tahun_lalu'
                     ORDER BY tanggal_input DESC";
        $result_sw = mysqli_query($conn, $query_sw);

        if (mysqli_num_rows($result_sw) > 0) {
            while ($row = mysqli_fetch_assoc($result_sw)) {
                $kendala = htmlspecialchars($row['kendala']);
                $teknisi = htmlspecialchars($row['teknisi_nama']);
                $tgl_input = formatTanggal($row['tanggal_input']);
                $selesai_time = formatTanggal($row['waktu_selesai']);
                $lama = hitungDurasi($row['tanggal_input'], $row['waktu_selesai']);

                echo "<tr>";
                echo "<td>{$no}</td>";
                echo "<td>{$row['nomor_tiket']}</td>";
                echo "<td>{$row['kategori']}</td>";
                echo "<td>{$kendala} / {$teknisi}</td>";
                echo "<td>{$tgl_input}</td>";
                echo "<td>{$selesai_time}</td>";
                echo "<td>{$lama}</td>";
                echo "</tr>";
                $no++;
            }
        } else {
            echo "<tr><td colspan='7'>Tidak ada data ditemukan.</td></tr>";
        }
        ?>
        </tbody>
      </table>
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



<!-- Tombol Navigasi -->
<button id="prevBtn" class="nav-btn"><i class="fas fa-chevron-left"></i></button>
<button id="nextBtn" class="nav-btn"><i class="fas fa-chevron-right"></i></button>

<footer>
  <i class="far fa-clock"></i> <?= date('d F Y H:i') ?> | <b>Dashboard Pelaporan SIMRS</b>
</footer>

<script src="assets/modules/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
  $(document).on('click', '.btn-lihat', function() {
    let kendala = $(this).data('kendala');
    $('#isiKendala').text(kendala || 'Tidak ada keterangan.');
    $('#modalKendala').modal('show');
  });
  $('#modalKendala').on('hidden.bs.modal', function () {
    $('body').removeClass('modal-open');
    $('.modal-backdrop').remove();
  });
});
</script>


<script>
const slides = document.querySelectorAll('.slide');
let currentSlide = 0;
function showSlide(n){
  slides[currentSlide].classList.remove('active-slide');
  currentSlide = (n + slides.length) % slides.length;
  slides[currentSlide].classList.add('active-slide');
}
document.getElementById('nextBtn').onclick = () => showSlide(currentSlide + 1);
document.getElementById('prevBtn').onclick = () => showSlide(currentSlide - 1);

// === Chart SATUSEHAT ===
const ctx = document.getElementById('chartSatuSehat').getContext('2d');
const labels = [<?php mysqli_data_seek($data_satu, 0);
while($row=mysqli_fetch_assoc($data_satu)){ echo "'".$row['endpoint']."',"; } ?>];
<?php mysqli_data_seek($data_satu, 0); ?>
const values = [<?php while($row=mysqli_fetch_assoc($data_satu)){ echo $row['total'].","; } ?>];
new Chart(ctx,{
  type:'line',
  data:{
    labels:labels,
    datasets:[{
      label:'Jumlah Terkirim',
      data:values,
      borderColor:'#00c8ff',
      borderWidth:3,
      fill:true,
      backgroundColor:'rgba(0,200,255,0.1)',
      pointBackgroundColor:'#00c8ff'
    }]
  },
  options:{
    plugins:{legend:{labels:{color:'white'}}},
    scales:{
      x:{ticks:{color:'white'},grid:{color:'rgba(255,255,255,0.1)'}},
      y:{ticks:{color:'white'},grid:{color:'rgba(255,255,255,0.1)'}}
    }
  }
});
</script>
</body>
</html>
