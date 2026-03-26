<?php
require 'config/db.php';

$patient_id = intval($_POST['patient_id'] ?? 0);
$id = intval($_POST['id'] ?? -1);

$stmt = $conn->prepare("SELECT treatment_plan FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$patient){

    echo json_encode([
        "success" => false
        ]);
        exit;
}
$plan = json_decode($patient['treatment_plan'], true) ?? [];

if(isset($plan[$id])){
    array_splice($plan, $id, 1);
}

$cost_total = 0;
$total_qty = 0;
$total_price = 0;
$total_paid = 0;
$remaining_to_clinic = 0;
$remaining_to_patient = 0;

foreach($plan as $row){

    $cost_total += $row['line_total'];
    $total_qty += $row['qty'];
    $total_price += $row['price'];
    $total_paid += $row['paid_money'];

    $remaining = $row['line_remaining'];

    if($remaining > 0){
        $remaining_to_clinic += $remaining;
    }else{
        $remaining_to_patient += abs($remaining);
    }
}
$stmt = $conn->prepare("UPDATE patients SET treatment_plan=?,
cost_total=?,
total_paid=?,
remaining_to_clinic=?,
remaining_to_patient=?  
WHERE id = ?");
$stmt->execute([json_encode($plan, JSON_UNESCAPED_UNICODE),
$cost_total,
$total_paid,
$remaining_to_clinic,
$remaining_to_patient,
$patient_id]);


echo json_encode(["success"=>true]);

    

