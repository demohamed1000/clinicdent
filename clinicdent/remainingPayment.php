<?php
// THIS PAGE IS NOT YET USED AND I LEAVE IT FOR JUST LEARNING
require 'config/db.php';

$data = json_decode(file_get_contents("php://input"),true);

$id = (int)$data['id'];
$amount = (float)$data['amount'];

$stmt->$conn("SELECT treatment_plan FROM patients WHERE id = ?");
$stmt->execute([$id]);

$plan = json_decode($stmt->fetchColumn(),true) ?? [];

$remaining = 0;
foreach($plan as &$row){
    if(($row['line_remaining'] ?? 0) > 0){
        $row['paid_money'] += $remaining;
        $row['line_remaining'] += $remaining;
        break;
    }
}
foreach($plan as $row){
    $remaining += (float)($row['line_remaining'] ?? 0);
}

$stmt->$conn("UPDATE patients SET treatment_plan = ? WHERE id = ?");
$stmt->execute([json_encode($plan), $id]);

echo json_encode([
    'success' => true,
    'remaining' => round($remaining, 2)
]
);