<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "<p class='text-danger'>ID tidak valid.</p>";
  exit;
}

$sql = "
  SELECT p.id,
         u.nik,
         u.nama AS nama_karyawan,
         u.email,
         u.no_hp,
         u.jabatan AS nama_jabatan,       -- ambil langsung dari users
         u.unit_kerja AS nama_unit,       -- ambil langsung dari users
         mc.nama_cuti,
         p.lama_hari AS lama_pengajuan,
         p.alasan,
         p.status,
         d.nama AS nama_delegasi,
         a.nama AS nama_atasan,           -- join ke users atasan
         GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti,
         jc.lama_hari AS jatah_hari,
         jc.sisa_hari,
         jc.tahun
  FROM pengajuan_cuti p
  JOIN users u ON p.karyawan_id = u.id
  LEFT JOIN users d ON p.delegasi_id = d.id
  LEFT JOIN users a ON u.atasan_id = a.id
  JOIN master_cuti mc ON p.cuti_id = mc.id
  LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
  LEFT JOIN jatah_cuti jc 
         ON jc.karyawan_id = p.karyawan_id 
        AND jc.cuti_id = p.cuti_id 
        AND jc.tahun = YEAR(p.created_at)
  WHERE p.id = '$id'
  GROUP BY p.id
  LIMIT 1
";
$res = mysqli_query($conn, $sql) or die("Error: " . mysqli_error($conn));
$row = mysqli_fetch_assoc($res);

if (!$row) {
  echo "<p class='text-danger'>Data tidak ditemukan.</p>";
  exit;
}

// Siapkan array tanggal cuti
$tanggal_cuti_raw = $row['tanggal_cuti'] ?? '';
$tanggal_list = [];
if ($tanggal_cuti_raw !== '') {
  $tanggal_list = array_map('trim', explode(',', $tanggal_cuti_raw));
}
?>
<div class="container-fluid">
  <h6 class="mb-3"><i class="fas fa-user"></i> Informasi Karyawan</h6>
  <table class="table table-sm table-bordered">
    <tr><th style="width:200px">NIK</th><td><?= htmlspecialchars($row['nik']) ?></td></tr>
    <tr><th>Nama</th><td><?= htmlspecialchars($row['nama_karyawan']) ?></td></tr>
    <tr><th>Jabatan</th><td><?= htmlspecialchars($row['nama_jabatan'] ?? '-') ?></td></tr>
    <tr><th>Unit Kerja</th><td><?= htmlspecialchars($row['nama_unit'] ?? '-') ?></td></tr>
    <tr><th>Email</th><td><?= htmlspecialchars($row['email']) ?></td></tr>
    <tr><th>No. HP</th><td><?= htmlspecialchars($row['no_hp'] ?? '-') ?></td></tr>
    <tr><th>Atasan</th><td><?= htmlspecialchars($row['nama_atasan'] ?? '-') ?></td></tr>
  </table>

  <h6 class="mt-4 mb-3"><i class="fas fa-calendar-alt"></i> Informasi Pengajuan Cuti</h6>
  <table class="table table-sm table-bordered">
    <tr><th style="width:200px">Jenis Cuti</th><td><?= htmlspecialchars($row['nama_cuti']) ?></td></tr>
    <tr><th>Tanggal Cuti</th>
      <td>
        <?php if (count($tanggal_list) > 0): ?>
          <ul class="mb-0 pl-3">
            <?php foreach ($tanggal_list as $t): ?>
              <li><?= htmlspecialchars($t) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <span>-</span>
        <?php endif; ?>
      </td>
    </tr>
    <tr><th>Lama Hari (Pengajuan)</th><td><?= htmlspecialchars($row['lama_pengajuan']) ?> hari</td></tr>
    <tr><th>Alasan</th><td><?= nl2br(htmlspecialchars($row['alasan'])) ?></td></tr>
    <tr><th>Delegasi</th><td><?= htmlspecialchars($row['nama_delegasi'] ?? '-') ?></td></tr>
    <tr><th>Status</th><td><?= htmlspecialchars($row['status']) ?></td></tr>
  </table>

  <h6 class="mt-4 mb-3"><i class="fas fa-clipboard-list"></i> Informasi Jatah Cuti</h6>
  <table class="table table-sm table-bordered">
    <tr><th style="width:200px">Jatah Hari</th><td><?= ($row['jatah_hari'] !== null ? htmlspecialchars($row['jatah_hari']) . ' hari' : '-') ?></td></tr>
    <tr><th>Sisa Hari</th><td><?= ($row['sisa_hari'] !== null ? htmlspecialchars($row['sisa_hari']) . ' hari' : '-') ?></td></tr>
    <tr><th>Tahun Jatah</th><td><?= ($row['tahun'] !== null ? htmlspecialchars($row['tahun']) : '-') ?></td></tr>
  </table>
</div>
