<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = (int)$_GET['user'];
$judul_id = (int)$_GET['judul'];

$qPeserta = mysqli_query($conn, "SELECT nama, email FROM users WHERE id='$user_id'");
$peserta = mysqli_fetch_assoc($qPeserta);

$qJawaban = mysqli_query($conn, "
  SELECT s.soal, s.pilihan_a, s.pilihan_b, s.pilihan_c, s.pilihan_d, 
         s.jawaban_benar, j.jawaban
  FROM jawaban_ujian j
  JOIN soal s ON j.soal_id = s.id
  WHERE j.user_id='$user_id' AND j.judul_soal_id='$judul_id'
");

echo "<h5><strong>Nama:</strong> " . htmlspecialchars($peserta['nama']) . "<br>
<strong>Email:</strong> " . htmlspecialchars($peserta['email']) . "</h5>
<hr>";

echo '<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="thead-dark text-center">
    <tr>
      <th>No</th>
      <th>Pertanyaan</th>
      <th>Pilihan A</th>
      <th>Pilihan B</th>
      <th>Pilihan C</th>
      <th>Pilihan D</th>
      <th>Jawaban Peserta</th>
      <th>Jawaban Benar</th>
    </tr>
  </thead>
  <tbody>';

$no = 1;
while ($r = mysqli_fetch_assoc($qJawaban)) {
  $isCorrect = ($r['jawaban'] == $r['jawaban_benar']);
  echo '<tr>
    <td>'.$no++.'</td>
    <td>'.nl2br(htmlspecialchars($r['soal'])).'</td>
    <td>'.htmlspecialchars($r['pilihan_a']).'</td>
    <td>'.htmlspecialchars($r['pilihan_b']).'</td>
    <td>'.htmlspecialchars($r['pilihan_c']).'</td>
    <td>'.htmlspecialchars($r['pilihan_d']).'</td>
    <td class="text-center '.($isCorrect ? 'bg-success text-white' : 'bg-danger text-white').'">'
      .strtoupper($r['jawaban']).'</td>
    <td class="text-center bg-light">'.strtoupper($r['jawaban_benar']).'</td>
  </tr>';
}
echo '</tbody></table></div>';
?>
