<?php
require 'config/db.php';

$patient_id = (int)($_POST['patient_id'] ?? 0);
$desc = $_POST['treatment_desc'] ?? '';
$qty = (float)($_POST['quantity'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$paid = (float)($_POST['paid'] ?? 0);
$notes = ucfirst($_POST['notes'] ?? '');

if(!$patient_id || !$desc){
    header("Location: patient.php?id=".$patient_id);
    exit;
}

$total = $qty * $price;
$remaining = $total - $paid;

$stmt = $conn->prepare("SELECT treatment_plan FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);


$plan = json_decode($stmt->fetchColumn(), true) ?? [];
$plan[]= [
    'desc' => $desc,
    'created_at' => date("Y-m-d H:i:s"),
    'qty'=> $qty,
    'price'=> $price,
    'line_total'=> $total,
    'paid_money'=> $paid,
    'line_remaining'=> $remaining
];

$cost_total = 0;
$total_paid = 0;
$remaining_money = 0;
foreach($plan as $row){
    $cost_total += $row['line_total'];
    $total_paid += $row['paid_money'];
    $remaining_money += $row['line_remaining'];
}

$remaining_to_clinic = 0;
$remaining_to_patient = 0;
if($remaining_money > 0){
    $remaining_to_clinic = $remaining_money;
}elseif($remaining_money < 0){
    $remaining_to_patient = $remaining_money;
}

$stmt = $conn->prepare('UPDATE patients SET treatment_plan = ?,
cost_total = ?,
total_paid = ?,
remaining_to_clinic = ?,
remaining_to_patient = ?
WHERE id = ?');
$stmt->execute([json_encode($plan, JSON_UNESCAPED_UNICODE),
$cost_total,
$total_paid,
$remaining_to_clinic,
$remaining_to_patient,
$patient_id]);

header("Location:patient.php?id=".$patient_id);
exit;
