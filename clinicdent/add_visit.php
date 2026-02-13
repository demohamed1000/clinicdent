<?php
require "config/db.php";

$patient_id = (int)($_POST['patient_id'] ?? 0);
$visit_date = ($_POST['visit_date'] ?? '');
$notes = ucfirst($_POST['notes'] ?? '');

if($patient_id && $visit_date){
    $stmt = $conn->prepare("INSERT INTO patient_visits (patient_id , visit_date, notes, status)
    VALUES (?, ?, ?, 'scheduled') ");
    $stmt->execute([$patient_id, $visit_date, $notes]);
}
header("Location: patient.php?id= ".$patient_id);
exit;
