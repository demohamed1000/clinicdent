<?php

require 'config/db.php';
$data = json_decode(file_get_contents("php://input"),true);

$id = (int)$data['id'];
$amount = (float)$data['amount'];

// GET FROM PATIENTS
$stmt = $conn->prepare('SELECT treatment_plan FROM patients WHERE id = ?');
$stmt->execute([$id]);

// GET FROM PAYMENTBACK
// $payback = $conn->prepare('SELECT id, refund_id, amount FROM payBack WHERE id = ?');
// $payback->execute([$id]);
// $paymentBack = $payback->fetchAll(PDO::FETCH_ASSOC);

$clinicPlan = json_decode($stmt->fetchColumn(), true) ?? [];

$remaining_back = $amount;
$lastIndex = count($clinicPlan) - 1;

if($lastIndex >= 0){
    $clinicPlan[$lastIndex]['paid_money'] -= $remaining_back;
    $clinicPlan[$lastIndex]['line_remaining'] += $remaining_back;
}

$remaining_money =0;
$total_paid = 0;
foreach($clinicPlan as $row){
    $total_paid += (float)($row['paid_money'] ?? 0);
    $remaining_money += (float)($row['line_remaining'] ?? 0);
}

$remaining_to_clinic = 0;
$remaining_to_patient = 0;
if($remaining_money > 0){
    $remaining_to_patient = $remaining_money;
}elseif($remaining_money < 0){
    $remaining_to_clinic = $remaining_money;
}
$stmt = $conn->prepare('UPDATE patients SET treatment_plan = ?,
total_paid = ?,
remaining_to_clinic = ?,
remaining_to_patient = ?
WHERE id = ?');
$stmt->execute([json_encode($clinicPlan, JSON_UNESCAPED_UNICODE),
$total_paid,
$remaining_to_clinic,
$remaining_to_patient,
$id]);

$stmt = $conn->prepare("INSERT INTO payments (patient_id, amount, type) VALUES (?,?,?)");
$stmt->execute([$id, $amount,'out']);

// $payment_id = $conn->lastInsertId();

echo json_encode([
    'success' => true,
    'amount' => $amount,
    'created_at' => date('Y-m-d H:i:s'),
    'remaining' => round($remaining_money,2)
]);
exit;
