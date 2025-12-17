<?php
require 'config/db.php';
if(($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['delete_id'])){
    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $success = $stmt->execute([$id]);

    echo json_encode([
        "success" => $success
    ]);

    exit;
    
    // This file is now used by AJAX only
}

$input = json_decode(file_get_contents("php://input"), true);
if(isset($input['action']) && $input['action'] === 'edit'){
    $d = $input['data'];

    $stmt = $conn->prepare(
        "UPDATE patients SET code=?, name=?, diagnosis=? WHERE id=?"
    );

    $success = $stmt->execute([
        $d['code'],
        $d['name'],
        $d['diagnosis'],
        $d['id']
    ]);

    echo json_encode(["success" => $success]);
    exit;
}