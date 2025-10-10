<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

// Ambil input JSON
$data = json_decode(file_get_contents("php://input"), true);

if(!isset($data['user_id'], $data['type'], $data['image'], $data['latitude'], $data['longitude'])){
    echo json_encode(['success'=>false,'message'=>'Data tidak lengkap. Lokasi wajib dikirim.']);
    exit;
}

$user_id   = intval($data['user_id']);
$type      = trim($data['type']);
$image     = $data['image'];
$latitude  = floatval($data['latitude']);
$longitude = floatval($data['longitude']);

// ========================
// Lokasi Kantor (Tentukan di sini)
// ========================
$office_lat = -1.5218765296805112;   // latitude kantor
$office_lng = 102.12508644110466;    // longitude kantor
$radius     = 200; // meter, boleh diubah sesuai kebutuhan


// Fungsi hitung jarak Haversine
function distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// Validasi lokasi
$jarak = distance($latitude, $longitude, $office_lat, $office_lng);
if($jarak > $radius){
    echo json_encode([
        'success'=>false,
        'message'=>"Anda di luar area absensi (Jarak: ".round($jarak)." m, Maksimal: {$radius} m)"
    ]);
    exit;
}

// ========================
// Validasi tipe absen
// ========================
$valid_types = ['masuk','keluar','istirahat_masuk','istirahat_keluar'];
if(!in_array($type, $valid_types)){
    echo json_encode(['success'=>false,'message'=>'Tipe absensi tidak valid.']);
    exit;
}

// Folder penyimpanan foto
$upload_dir = "absen_foto/";
if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Simpan foto
$foto_name = $user_id . '_' . $type . '_' . date('Ymd_His') . '.jpg';
$foto_path = $upload_dir . $foto_name;
$image_base64 = preg_replace('#^data:image/\w+;base64,#i', '', $image);
$image_base64 = str_replace(' ', '+', $image_base64);
if(!file_put_contents($foto_path, base64_decode($image_base64))){
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan foto.']);
    exit;
}

// Tentukan kolom jam sesuai tipe
$jam_field_map = [
    'masuk'           => 'jam_masuk',
    'keluar'          => 'jam_keluar',
    'istirahat_masuk' => 'istirahat_masuk',
    'istirahat_keluar'=> 'istirahat_keluar'
];
$jam_field = $jam_field_map[$type];
$current_time = date('H:i:s');
$tanggal = date('Y-m-d');

// Cek apakah sudah absen tipe ini hari ini
$query_check = "SELECT id FROM absensi WHERE user_id=? AND tanggal=? AND status=?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param('iss', $user_id, $tanggal, $type);
$stmt_check->execute();
$stmt_check->store_result();
if($stmt_check->num_rows > 0){
    echo json_encode(['success'=>false,'message'=>'Anda sudah melakukan absensi '.$type.' hari ini.']);
    exit;
}

// Insert data absensi
$insert_sql = "INSERT INTO absensi (user_id, tanggal, $jam_field, status, foto, latitude, longitude) 
               VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param('issssdd', $user_id, $tanggal, $current_time, $type, $foto_name, $latitude, $longitude);

if($stmt->execute()){
    echo json_encode(['success'=>true,'message'=>'Absensi '.$type.' berhasil dicatat.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan absensi.']);
}
