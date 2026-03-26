<?php
require 'config/db.php';
if(($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['delete_pt'])){
    $id = intval($_POST['delete_pt']);

    $stmt = $conn->prepare("DELETE FROM patient_visits WHERE id = ?");
    $success = $stmt->execute([$id]);

    echo json_encode([
        "success" => $success
    ]);

    exit;
    
    // This file is now used by AJAX only
}
if(($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['delete_pay'])){
    $id = intval($_POST['delete_pay']);

    // GET PAYMENT INFO
    $stmt = $conn->prepare("SELECT patient_id, amount FROM payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$payment){
        echo json_encode(["success" => false]);
        exit;
        }
    $patient_id = $payment['patient_id'];
    $amount = (float)$payment['amount'];
    
    // GET TREATMENT PLAN
    $stmt = $conn->prepare("SELECT treatment_plan FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $plan = json_decode($stmt->fetchColumn(),true) ?? [];

    // REVERSE PAYMENT
    foreach($plan as &$row){
        if(($row['paid_money'] ?? 0) >= $amount){
            $row['paid_money'] -= $amount;
            $row['line_remaining'] += $amount;
            break;
        }
    }
    unset($row);

    // RECALCULATE TOTALS
    $total_paid = 0;
    $remaining_money = 0;
    
    foreach($plan as $row){
        $total_paid += (float)($row['paid_money'] ?? 0);
        $remaining_money += (float)($row['line_remaining'] ?? 0);
        
    }

    // UPDATE PATIENT RECORD
    $stmt = $conn->prepare("UPDATE patients SET
    treatment_plan = ?,
    total_paid = ?,
    remaining_to_clinic = ?
    WHERE id = ?");

    $stmt->execute([
        json_encode($plan, JSON_UNESCAPED_UNICODE),
        $total_paid,
        $remaining_money,
        $patient_id
    ]);

    // DELETE PAYMENT RECORD
    $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
    $success = $stmt->execute([$id]);

    echo json_encode([
        "success" => $success,
        "updated_plan" => $updatedPlan
    ]);

    exit;
    
    // This file is now used by AJAX only
}
