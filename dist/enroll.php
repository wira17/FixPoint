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

$user_id = $_SESSION['user_id'];
$descriptor = mysqli_real_escape_string($conn,json_encode($input['descriptor']));

$sql = "UPDATE users SET wajah='$descriptor' WHERE id='$user_id'";
if(mysqli_query($conn,$sql)){
    echo json_encode(['success'=>true,'message'=>'Enrol wajah berhasil']);
}else{
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan enroll wajah']);
}
