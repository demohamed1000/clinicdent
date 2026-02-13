<?php
require 'config/db.php';

$data = json_decode(file_get_contents("php://input"),true);

$id = (int)$data['id'];
$amount = (float)$data['amount'];

$stmt = $conn->prepare('SELECT treatment_plan FROM patients WHERE id = ?');
$stmt->execute([$id]);

$plan = json_decode($stmt->fetchColumn(),true) ?? [];

$remaining = 0;

// Apply payment to negative remaining lines
foreach($plan as &$row){
    if(($row['line_remaining'] ?? 0) < 0){
        $row['line_remaining'] += $amount;
        break;
    }elseif(($row['line_remaining'] ?? 0) > 0){
        $row['paid_money'] += $amount;
        $row['line_remaining'] -= $amount;
        break;
    }
}
// Recalculate remaining
$total_paid = 0;
$remaining_money = 0;

foreach($plan as $row){
    $total_paid += (float)($row['paid_money'] ?? 0);
    $remaining_money += (float)($row['line_remaining'] ?? 0);
}

$remaining_to_clinic = 0;
$remaining_to_patient = 0;
if($remaining_money > 0){
    $remaining_to_clinic = $remaining_money;
}elseif($remaining_money < 0){
    $remaining_to_patient = $remaining_money;
}
$stmt = $conn->prepare('UPDATE patients SET treatment_plan = ?,
total_paid = ?,
remaining_to_clinic = ?,
remaining_to_patient = ?
WHERE id = ?');
$stmt->execute([json_encode($plan, JSON_UNESCAPED_UNICODE),
$total_paid,
$remaining_to_clinic,
$remaining_to_patient,
$id]);

$stmt = $conn->prepare("INSERT INTO payments (patient_id, amount) VALUES (?,?)");
$stmt->execute([$id, $amount]);

echo json_encode([
    'success' => true,
    'amount' => $amount,
    'created_at' => date('Y-m-d H:i:s'),
    'paid' => round($total_paid, 2),
    'remaining' => round($remaining_money, 2)
]);
exit;

