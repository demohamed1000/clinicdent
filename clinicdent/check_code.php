<?php

require "config/db.php";

$code = $_GET['code'] ?? '';

if($code === ''){
    echo json_encode(['status' => 'empty']);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM patients WHERE code = ? ");
$stmt->execute([$code]);
$count = $stmt->fetchColumn();

if($count > 0){
    echo json_encode(['status' => 'exists']);
}else{
    echo json_encode(['status' => 'available']);
}
