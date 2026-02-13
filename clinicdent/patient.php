<?php

require 'config/db.php';

$id = (int)($_GET['id'] ?? 0);

// PATIENT INFO
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$patient){
    die("Patient not found");
}

// PAYMENT HISTORY
$stmt = $conn->prepare(
    "SELECT amount, created_at
    FROM payments
    WHERE patient_id = ?
    ORDER BY created_at DESC"
);
$stmt->execute([$id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// UPCOMING VISITS
$stmt = $conn->prepare(
    "SELECT id, visit_date, created_at, notes FROM patient_visits
    WHERE patient_id = ? AND status = 'scheduled' 
    ORDER BY visit_date ASC"
);
$stmt->execute([$id]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);


// TREATMENT PLAN 
$plan = json_decode($patient['treatment_plan'], true) ?? [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class = "bg-light">
    <div class = "container py-4">
        <a href="index.php" class = "btn btn-secondary btn-sm mb-3">← Back</a>
        <!-- THIS IS PATIENT INFO -->
        <div class = "card mb-3">
            <div class = "card-header fw-bold d-flex justify-content-between align-items-center">
                <td>
                    <span>Patient Information</span> 
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="editCard(this)">Edit</button>
                </td>
            </div>
            <div class = "card-body" data-id = <?= $patient['id'] ?>>
                <p><strong>Code: </strong><b class = "text-primary" data-field = "code"><?=$patient['code'] ?></b></p>
                <p><strong>Name: </strong><b class = "text-success" data-field = "name"><?= htmlspecialchars(ucfirst($patient['name'])) ?></b></p>
                <p><strong>Diagnosis: </strong><b class = "text-danger" data-field = "diagnosis"><?= htmlspecialchars(ucfirst($patient['diagnosis'])) ?></b></p>
                <p><strong>Visit Date: </strong><?= $patient['date_visit'] ?></p>
            </div>
        </div>
        <!-- TREATMENT PLAN -->
        <div class = "card mb-3">
            <div class = "card-header fw-bold">Treatment Plan</div>
            <div class = "card-body p-0">
                <table class = "table table-bordered mb-0">
                    <thead class = "table-light">
                        <tr>
                            <th>Treatment</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($plan as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars(ucfirst($row['desc'])) ?></td>
                                <td><?= $row['qty'] ?></td>
                                <td><?= number_format($row['price'],2) ?></td>
                                <td><?= number_format($row['line_total'],2) ?></td>
                                <td class = "text-success"><?= number_format($row['paid_money'],2) ?></td>
                                <td class = "text-danger"><?= number_format($row['line_remaining'],2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Payment History -->
        <div class = "card">
            <div class = "card-header fw-bold d-flex justify-content-between align-items-center">Payment History
                <td>
                    <form method = "POST">
                        <input type="hidden" name = "edit_payment" type = "number" oninput = "validateCode(this)"value = "<?= $patient['id'];?>">
                        <button type = "button" class = "btn btn-sm btn-success" 
                        onclick="openPaymentModal(<?= $patient['id']?>)">Pay</button>
                    </form>
                </td>
            </div>
            <div class = "card-body p-0">
                <table class = "table table-stripped mb-0">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id = "paymentTableBody">
                        <?php if(!$payments): ?>
                            <tr class = "empty_row"><td colspan = "2" class = "text-center">No Payments Yet</td></tr>
                        <?php endif; ?>
                        <?php foreach($payments as $pay): ?>
                            <tr>
                                <td class = "text-success fw-bold"><?= number_format($pay['amount'],2) ?></td>
                                <td><?= $pay['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div> 
        <div class = "card mt-3">
            <div class = "card-header fw-bold d-flex justify-content-between align-items-center">
                <span class = "text-primary fw-bold">UPCOMING VISITS</span>
                <button class = "btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addVisitModal">
                    + ADD VISIT
                </button>
            </div>
            <div class = "card-body" p-0>
                <table class = "table table-stripped mb-0">
                    <thead>
                        <tr>
                            <th class = "text-primary">Date</th>
                            <th class = "text-primary">Created at</th>
                            <th class = "text-info">Notes</th>
                            <th class = "text-warning">Edit</th>
                            <th class = "text-danger">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$visits): ?>
                            <tr><td colspan = "4" class = "text-center">No Visits Yet</td></tr>
                        <?php endif; ?>
                        <?php foreach($visits as $visit): ?>
                            <tr colspan = "4">
                                <td class = "text-success fw-bold"><?= ($visit['visit_date']) ?></td>
                                <td><?= $visit['created_at'] ?></td>
                                <td><?= $visit['notes'] ?></td>
                                <td><?= $visit['created_at'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick = "DeleteInPatientPage(<?= $visit['id'] ?>, this)">
                                        ×
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class = "modal fade" id = "deleteModal" tabindex="-1">
                <div class = "modal-dialog modal-dialog-centered">
                    <div class = "modal-content">
                        <div class = "modal-header bg-danger text-white">
                            <h5 class = "modal-title">Confirm Delete</h5>
                            <button type = "button" class = "btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class = "modal-body">
                            Are you sure you want to delete this patient?
                        </div>
                        <div class = "modal-footer">
                            <button class = "btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class = "btn btn-danger" id = "confirmDeleteBtn" data-bs-dismiss="modal">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class = "modal fade" id = "addVisitModal" tabindex = "-1">
                <div class = "modal-dialog">
                    <div class = "modal-content">
                        <div class= "modal-header">
                            <h4 class = "modal-title">Add New Visit</h4>
                            <button type = "button" class="btn-close" data-bs-dismiss= "modal"></button>
                        </div>
                        
                        <form method = "POST" action="add_visit.php">
                            <div class = "modal-body">
                                <input type="hidden" name = "patient_id" value = "<?= $patient['id'] ?>">
                                <div class = "row mb-3">
                                    <div class = "col-md-12 mb-2">
                                        <input type="date" name = "visit_date" class = "form-control">
                                    </div>
                                    <div class = " textarea-container mb-3">
                                        <textarea type="text" name = "notes" rows = "2" maxlength = "120" class = "form-control" placeholder = "Add Notes"></textarea>
                                        <div class = "counter" dir = "rtl"></div>
                                    </div>
                                </div>
                            </div>
                            <div class = "modal-footer">
                                <button type = "button" class = "btn btn-danger" data-bs-dismiss = "modal">Cancel</button>
                                <button class = "btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- PAYMENT MODAL -->
    <div class = "modal fade" id ="paymentModal" tabindex = "-1">
        <div class = "modal-dialog">
            <div class = "modal-content">
                <div class = "modal-header">
                    <h5 class ="modal-title">Pay/Refund</h5>
                    <button type = "button" class = "btn-close" data-bs-dismiss = "modal"></button>
                </div>
                <div class = "modal-body">
                    <input type="hidden" id = "patient_id">
                    <div class ="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class = "form-control" step = "1" oninput = "validateCode(this)" id = "pay_amount">
                    </div>
                    <div class = "text-danger small" id = "payMsg"></div>
                </div>
                <div class = "modal-footer">
                    <button class = "btn btn-success" onclick = "savePayment()">Pay</button>
                    <button class = "btn btn-secondary" data-bs-dismiss = "modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <script>

        // PAYMENT MODAL TO CLIENT
        let paymentModal;
        
        function openPaymentModal(id){
            document.getElementById('patient_id').value = id;
            document.getElementById('pay_amount').value = '';
            document.getElementById('payMsg').innerText = '';
        
            paymentModal = new bootstrap.Modal(
                document.getElementById('paymentModal')
            );
            paymentModal.show();
        
        }
        
        function savePayment(){
            const id = document.getElementById('patient_id').value;
            const amount = parseFloat(document.getElementById('pay_amount').value);
        
            if(!amount || amount<=0){
                document.getElementById('payMsg').innerText = 'Enter a valid amount';
                return;
            }
            fetch('update_payment.php',{
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, amount})
            })
            .then(res=>res.json())
            .then(data=>{
                if(!data.success){
                    document.getElementById('payMsg').innerText = data.message;
                    return;
                }
                paymentModal.hide();

                const tableBody = document.getElementById("paymentTableBody");

                // REMOVE "No Paymens Yet" after the first payment
                const emptyRow = tableBody.querySelector(".empty_row");
                if(emptyRow){
                    emptyRow.remove();
                }

                // CREATE A NEW ROW
                const newRow = document.createElement("tr");
                const amountCell = document.createElement("td");
                amountCell.className = "text-success fw-bold";
                amountCell.innerText = parseFloat(data.amount).toFixed(2);

                const dataCell = document.createElement("td");
                dataCell.innerText = data.created_at;

                newRow.appendChild(amountCell);
                newRow.appendChild(dataCell);

                // ADD ROW AT THE TOP
                tableBody.prepend(newRow);
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src = "assets/dental.js"></script>
</body>
</html>
