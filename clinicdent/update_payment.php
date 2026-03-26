<?php
require 'config/db.php';

$data = json_decode(file_get_contents("php://input"),true);

$id = (int)$data['id'];
$amount = (float)$data['amount'];

// GET FROM PATIENTS
$stmt = $conn->prepare('SELECT treatment_plan FROM patients WHERE id = ?');
$stmt->execute([$id]);
// $patientPlan = $stmt->fetchColumn();
$patientPlan = json_decode($stmt->fetchColumn(),true) ?? [];

// GET FROM PAYMENTS
$pay = $conn->prepare('SELECT id, patient_id, amount FROM payments WHERE id = ?');
$pay->execute([$id]);
$paymentPlan = $pay->fetchAll(PDO::FETCH_ASSOC);


$remainingAmount = $amount;
// Apply payment to negative remaining lines
foreach($patientPlan as &$row){
    $lineRemaining = (float)($row['line_remaining'] ?? 0);
    if($remainingAmount <= 0){
        break;
        }
    if($lineRemaining <= 0){
        continue;
    }

    $payNow = min($lineRemaining, $remainingAmount);
    $row['paid_money'] += $payNow;
    $row['line_remaining'] -= $payNow;

    $remainingAmount -= $payNow;
            
}
unset($row);
if($remainingAmount > 0 && !empty($patientPlan)){
    $lastIndex = count($patientPlan) - 1;
    // $row['paid_money'] += $remainingAmount;
    // $row['line_remaining'] -=$remainingAmount;
    $patientPlan[$lastIndex]['paid_money'] += $remainingAmount;
    $patientPlan[$lastIndex]['line_remaining'] -= $remainingAmount;
}



// Recalculate remaining
$total_paid = 0;
$remaining_money = 0;

foreach($patientPlan as $row){
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
$stmt->execute([json_encode($patientPlan, JSON_UNESCAPED_UNICODE),
$total_paid,
$remaining_to_clinic,
$remaining_to_patient,
$id]);

$stmt = $conn->prepare("INSERT INTO payments (patient_id, amount, type) VALUES (?,?,?)");
$stmt->execute([$id, $amount, 'in']);

$payment_id = $conn->lastInsertId();

echo json_encode([
    'success' => true,
    'amount' => $amount,
    'created_at' => date('Y-m-d H:i:s'),
    'payment_id' => $payment_id,
    'paid' => round($total_paid, 2),
    'remaining' => round($remaining_money, 2)
]);
exit;

