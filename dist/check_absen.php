<?php
include 'koneksi.php';
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'User belum login']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['descriptor'])){
    echo json_encode(['success'=>false,'message'=>'Data descriptor tidak ada']); exit;
}

$descInput = $input['descriptor'];

// Ambil semua descriptor dari users
$sql = "SELECT id, wajah FROM users WHERE wajah IS NOT NULL";
$res = mysqli_query($conn,$sql);
$matched = false;

function cosineSimilarity($a,$b){
    $dot=0;$na=0;$nb=0;
    for($i=0;$i<count($a);$i++){
        $dot += $a[$i]*$b[$i];
        $na += $a[$i]*$a[$i];
        $nb += $b[$i]*$b[$i];
    }
    return $dot/(sqrt($na)*sqrt($nb));
}

while($row = mysqli_fetch_assoc($res)){
    $descDb = json_decode($row['wajah'],true);
    if(!$descDb) continue;
    $sim = cosineSimilarity($descInput,$descDb);
    if($sim>0.6){ // threshold
        $matched = true;
        $user_id = $row['id'];
        $status = 'Masuk';
        $waktu = date('Y-m-d H:i:s');
        mysqli_query($conn,"INSERT INTO absensi (user_id,waktu,status) VALUES ('$user_id','$waktu','$status')");
        break;
    }
}

if($matched){
    echo json_encode(['success'=>true,'matched'=>true,'message'=>'Absensi berhasil']);
}else{
    echo json_encode(['success'=>true,'matched'=>false,'message'=>'Wajah tidak cocok']);
}
