<?php
include 'koneksi.php';
require('assets/vendor/fpdf/fpdf.php');

if (!isset($_GET['id'])) {
  die('ID tidak ditemukan.');
}

$id = intval($_GET['id']);
$query = mysqli_query($conn, "
  SELECT 
    mr.*, 
    b.no_barang,
    b.nama_barang,
    b.kategori,
    b.merk,
    b.lokasi
  FROM maintanance_rutin mr
  JOIN data_barang_it b ON mr.barang_id = b.id
  WHERE mr.id = $id
  LIMIT 1
");

if (!$query || mysqli_num_rows($query) == 0) {
  die('Data tidak ditemukan.');
}

$data = mysqli_fetch_assoc($query);

// Hitung tanggal maintenance berikutnya (3 bulan ke depan)
$tanggal_berikutnya = date('d/m/Y', strtotime($data['waktu_input'] . ' +3 months'));

$pdf = new FPDF('L', 'mm', array(100, 70)); // Ukuran kartu kecil
$pdf->AddPage();
$pdf->SetMargins(8, 8, 8);

// Kotak border merah
$pdf->SetDrawColor(220, 20, 60);
$pdf->SetLineWidth(1);
$pdf->Rect(5, 5, 90, 60);

// Judul
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Kartu Maintenance', 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(35, 6, 'No Barang', 0, 0);
$pdf->Cell(0, 6, ': ' . $data['no_barang'], 0, 1);

$pdf->Cell(35, 6, 'Nama Barang', 0, 0);
$pdf->Cell(0, 6, ': ' . $data['nama_barang'], 0, 1);

$pdf->Cell(35, 6, 'Kategori', 0, 0);
$pdf->Cell(0, 6, ': ' . $data['kategori'], 0, 1);

$pdf->Cell(35, 6, 'Merk', 0, 0);
$pdf->Cell(0, 6, ': ' . $data['merk'], 0, 1);

$pdf->Cell(35, 6, 'Lokasi', 0, 0);
$pdf->Cell(0, 6, ': ' . $data['lokasi'], 0, 1);

$pdf->Cell(35, 6, 'Teknisi', 0, 0);
$pdf->Cell(0, 6, ': ' . $data['nama_teknisi'], 0, 1);

$pdf->Cell(35, 6, 'Tanggal Maintenance', 0, 0);
$pdf->Cell(0, 6, ': ' . date('d/m/Y', strtotime($data['waktu_input'])), 0, 1);

// Kotak merah dengan tulisan maintenance berikutnya
$pdf->Ln(5);
$pdf->SetFillColor(255, 200, 200);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Maintenance Berikutnya: ' . $tanggal_berikutnya, 1, 1, 'C', true);

$pdf->Output('I', 'Kartu_Maintenance.pdf');
?>
