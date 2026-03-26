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
    "SELECT id, amount, created_at, type
    FROM payments
    WHERE patient_id = ?
    ORDER BY created_at DESC"
);
$stmt->execute([$id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PAYMENT BACK HISTORY
// $stmt = $conn->prepare(
//     "SELECT id, amount, created_at
//     FROM payBack
//     WHERE refund_id = ?
//     ORDER BY created_at DESC"
// );
// $stmt->execute([$id]);
// $paymentsBack = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/bbbootstrap/libraries@main/choices.min.css">
    <script src="https://cdn.jsdelivr.net/gh/bbbootstrap/libraries@main/choices.min.js"></script>
</head>
<body class = "bg-light">
    <div class = "container py-4">
        <a href="index.php" class = "btn btn-secondary btn-sm mb-3">← Back</a>
        <!-- THIS IS PATIENT INFO -->
        <div class = "card mb-3">
            <div class = "card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Patient Information</span> 
                <div class = "button-group">
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="editCard(this)">Edit</button>
                </div>
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
            <div class = "card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Treatment Plan</span>
                <button class = "btn btn-success" data-bs-toggle = "modal" data-bs-target = "#addTreatment">+ Add Treatment</button>
            </div>
            <div class = "modal fade" id = "addTreatment" tabindex = "-1">
                <div class = "modal-dialog">
                    <div class = "modal-content">
                        <div class= "modal-header">
                            <h4 class = "modal-title">Add New Treatment</h4>
                            <button type = "button" class="btn-close" data-bs-dismiss= "modal"></button>
                        </div>
                        
                        <form method = "POST" action="addTreatment.php">
                            <div class = "modal-body" id = "treatmentModal">
                                <input type="hidden" name = "patient_id" value = "<?= $patient['id'] ?>">
                                <div class = "col-mb-3">
                                    <div class = "row mb-2">
                                        <div class = "d-flex gap-2 align-items-center mb-2">
                                            <select name = "treatment_desc" id = "treatment_desc" class = "form-control" onchange = "changeTooth()">
                                                <option selected disabled>Choose Treatment</option>
                                                <option>Clinical Examination</option>
                                                <option>Endo</option>
                                                <option>Composite</option>  
                                                <option>Amalgam</option>
                                                <option>Pulpotomy</option>
                                                <option>Pulpectomy</option>
                                                <option>Extraction</option>
                                                <option>Pedo Extraction</option>
                                                <option>Crown & Bridge</option>
                                            </select>
                                            
                                            
                                            <select name="quadrant" id="quadrant_selected" class = "form-control">
                                                <option selected disabled>Choose Quadrant</option>
                                                <option>Upper</option>
                                                <option>Lower</option>
                                                
                                            </select>
                                            
                                        </div>
                                        <div class = "row d-flex justify-content-center mb-2">
                                            <div>
                                                <select name="tooth[]" id="choices-multiple-remove-button" multiple class = "form-control">
                                                
                                                    <!-- <option selected disabled>Choose Tooth</option> -->
                                                    <!-- <option>Right 8</option>
                                                    <option>Right 7</option>
                                                    <option>Right 6</option>
                                                    <option>Right 5</option>
                                                    <option>Right 4</option>
                                                    <option>Right 3</option>
                                                    <option>Right 2</option>
                                                    <option>Right 1</option>
                                                    <option>Left 1</option>
                                                    <option>Left 2</option>
                                                    <option>Left 3</option>
                                                    <option>Left 4</option>
                                                    <option>Left 5</option>
                                                    <option>Left 6</option>
                                                    <option>Left 7</option>
                                                    <option>Left 8</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class = "d-flex gap-2 align-items-center mb-2">
                                            <label>Quantity</label>
                                            <input type="number"  id= "quantity" name = "quantity" step = "1" maxlength = "2" min= "1" max = "32" oninput = "calculateTotals()" class = "form-control">
                                            
                                            <label>Price</label>
                                            <input type="number" id= "price" name = "price" step = "1" oninput = "calculateTotals()" class = "form-control">
                                            <label>Total</label>
                                            <input type="number"  id= "total" name = "total" class = "new_total form-control" readonly>
                                        
                                        </div>
                                        <div class = "d-flex gap-2 align-items-center">
                                            
                                            <label>Paid</label>
                                            <input type="number" id= "paid" name = "paid" step = "1" oninput = "calculateTotals()" class = "form-control">
                                            <label>Remaining</label>
                                            <input type="number"  id= "remaining" name = "remaining" class = "new_remaining form-control" readonly>
                                        
                                        </div>
                                    </div>
                                    
                                    <div class = " textarea-container mb-3">
                                        <textarea type="text" name = "notes" rows = "2" maxlength = "120" class = "form-control" placeholder = "Add Notes"></textarea>
                                        <div class = "counter" dir = "rtl"></div>
                                    </div>
                                </div>
                            </div>
                            <div class = "modal-footer">
                                <button type = "button" class = "btn btn-danger" data-bs-dismiss = "modal">Cancel</button>
                                <button type = "submit" class = "btn btn-primary" onclick = "addTreatment(event)">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class = "card-body p-0">
                <table class = "table table-bordered mb-0">
                    <thead class = "table-light">
                        <tr>
                            <th>Treatment</th>
                            <th>Date</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody id= "treatmentTableBody">
                        
                        <?php foreach($plan as $i => $row): ?> 
                        
                            <tr>
                                <td><?= htmlspecialchars(ucfirst($row['desc'])) ?></td>
                                <td><?= $row['created_at'] ?? ''?></td>
                                <td><?= $row['qty'] ?></td>
                                <td class = "text-success fw-bold"><?= number_format($row['price'],2) ?></td>
                                <td class = "modalGrandTotal"><?= number_format($row['line_total'],2) ?></td>
                                <td class = "paid-amount text-success fw-bold"><?= number_format($row['paid_money'],2) ?></td>
                                <td class = "remaining-amount text-danger fw-bold"><?= number_format($row['line_remaining'],2) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick = "DeleteTreatment(<?= $i ?>,this)">
                                        ×
                                    </button>
                                </td>
                            </tr>
                            
                        <?php endforeach; ?>
                        <tr id= "summaryRow" style = "border-top: 2px solid #000">
                            <td class = "text-info fw-bold">Treatment Total</td>
                            <?php $total = 0;
                            $totalQty = 0;
                            $total_sum = 0;
                            $total_paid = 0;
                            $total_remaining = 0;
                            foreach($plan as $row): 
                                $totalQty += $row['qty'];
                                $total += $row['price'];
                                $total_sum += $row['line_total'];
                                $total_paid += $row['paid_money'];
                                $total_remaining += $row['line_remaining'];
                            endforeach; ?>
                            <td><?= $row['created_at'] ?? ''?></td>
                            <td  id = "qty" class = "fw-bold"><?= number_format($totalQty)?></td>
                            <td id = "price" class = "fw-bold"><?= number_format($total,2)?></td>
                            <td id = "total" class = "modalGrandTotal"><?= number_format($total_sum,2) ?></td>
                            <td id = "paid" class = "paid-amount fw-bold"><?= number_format($total_paid,2) ?></td>
                            <td id = "remaining" class = "remaining-amount fw-bold"><?= number_format($total_remaining,2) ?></td>
                        </tr>
                            
                    </tbody>
                </table>
            </div>
        </div>
        <div class = "modal fade" id = "deleteTreatmentInPatient" tabindex="-1">
            <div class = "modal-dialog modal-dialog-centered">
                <div class = "modal-content">
                    <div class = "modal-header bg-danger text-white">
                        <h5 class = "modal-title">Confirm Delete</h5>
                        <button type = "button" class = "btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class = "modal-body">
                        Are you sure you want to delete this treatment?
                    </div>
                    <div class = "modal-footer">
                        <button class = "btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class = "btn btn-danger" id = "confirmDeleteTreatment" data-bs-dismiss="modal">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment History -->
        <?php 
        $remaining = 0; 
        foreach($plan as $row){
            $remaining += (float)($row['line_remaining'] ?? 0);
        }
        
        ?> 
        <div class = "card">
            <div class = "card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Payment History</span>
                <div class = "button-group d-flex gap-5px">
                    <td>
                        <form method = "POST">
                            <input type="hidden" name = "edit_payment" type = "number" oninput = "validateCode(this)"value = "<?= $patient['id'];?>">
                            <?php  
                            if($remaining > 0): ?>
                                
                                <button type = "button" class = "btn btn-sm btn-success me-3" 
                                onclick="openPaymentModal(<?= $patient['id']?>)">Pay</button>
                            <?php else: ?>
                                <button type = "button" class = "btn btn-sm btn-success me-3" disabled>Paid</button>
                            <?php endif; ?>
                            
                        </form>
                    </td>
                    <td>
                        <form method = "POST">
                            <input type="hidden" name = "edit_payment" type = "number" oninput = "validateCode(this)"value = "<?= $patient['id'];?>">
                            <?php
                            if($remaining < 0): ?>
                            <button type = "button" class = "btn btn-sm btn-secondary" 
                            onclick="openPayBackModal(<?= $patient['id']?>)">Pay Back</button>
                            <?php else: ?>
                                <button type = "button" class = "btn btn-sm btn-success me-3" disabled>No Pay Back</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </div>
            </div>
            <div class = "card-body p-0">
                <table class = "table table-stripped mb-0">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Date & Time</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id = "paymentTableBody">
                        <?php if(!$payments): ?>
                            <tr class = "empty_row"><td colspan = "3" class = "text-center fw-bold">No Payments Added</td></tr>
                        <?php endif; ?>
                        <?php foreach($payments as $pay): ?>
                            <tr>
                                <td class = "text-success fw-bold"><?= number_format($pay['amount'],2) ?></td>
                                <td><?= $pay['created_at'] ?></td>
                                <td>
                                    <?php if($pay['type'] === 'in'): ?>
                                        Patient pay to the clinic
                                        <?php else: ?>
                                            Clinic refunded to the patient
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($pay['type'] === 'in'): ?>
                                        <button class="btn btn-sm btn-danger" onclick = "DeletePay(<?= $pay['id'] ?>, this)">
                                            ×
                                        </button>
                                    <?php else: ?>
                                        <button class = "btn btn-sm btn-danger" disabled>x</button>
                                    <?php endif; ?>
                                </td>
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
                            <tr><td colspan = "5" class = "text-center fw-bold">No Visits Added</td></tr>
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
            <!-- DELETE THE VISIT ADDED -->
            <div class = "modal fade" id = "deleteModalInPatient" tabindex="-1">
                <div class = "modal-dialog modal-dialog-centered">
                    <div class = "modal-content">
                        <div class = "modal-header bg-danger text-white">
                            <h5 class = "modal-title">Confirm Delete</h5>
                            <button type = "button" class = "btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class = "modal-body">
                            Are you sure you want to delete this visit?
                        </div>
                        <div class = "modal-footer">
                            <button class = "btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class = "btn btn-danger" id = "confirmDeleteInPatient" data-bs-dismiss="modal">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- DELETE THE AMOUNT OF MONEY PAID -->
            <div class = "modal fade" id = "deleteMoneyInPatient" tabindex="-1">
                <div class = "modal-dialog modal-dialog-centered">
                    <div class = "modal-content">
                        <div class = "modal-header bg-danger text-white">
                            <h5 class = "modal-title">Confirm Delete</h5>
                            <button type = "button" class = "btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class = "modal-body">
                            Are you sure you want to delete this amount of money paid?
                        </div>
                        <div class = "modal-footer">
                            <button class = "btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class = "btn btn-danger" id = "DeleteInPatient" data-bs-dismiss="modal">Delete</button>
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
                    <h5 class ="modal-title">Pay</h5>
                    <button type = "button" class = "btn-close" data-bs-dismiss = "modal"></button>
                </div>
                <div class = "modal-body">
                    <input type="hidden" id = "patient_id">
                    <div class ="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class = "form-control" autofocus placeholder = "Enter amount" step = "1" oninput = "validateCode(this)" id = "pay_amount">
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
    <!-- PAYMENT BACK MODAL -->
    <div class = "modal fade" id ="paymentBackModal" tabindex = "-1">
        <div class = "modal-dialog">
            <div class = "modal-content">
                <div class = "modal-header">
                    <h5 class ="modal-title">Pay Back</h5>
                    <button type = "button" class = "btn-close" data-bs-dismiss = "modal"></button>
                </div>
                <div class = "modal-body">
                    <input type="hidden" id = "refund_id">
                    <div class ="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class = "form-control" step = "1" oninput = "validateCode(this)" id = "refund_amount">
                    </div>
                    <div class = "text-danger small" id = "refundMsg"></div>
                </div>
                <div class = "modal-footer">
                    <button class = "btn btn-success" onclick = "savePayBack()">Pay Back</button>
                    <button class = "btn btn-secondary" data-bs-dismiss = "modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function addTreatment(event){
            const desc = document.querySelector('select[name = "treatment_desc"]').value;
            const qty = parseFloat(document.querySelector('input[name = "quantity"]').value) || 0;
            const price = parseFloat(document.querySelector('input[name = "price"]').value) || 0;
            const total = parseFloat(document.querySelector('.new_total').value) || 0;
            const paid = parseFloat(document.querySelector('input[name = "paid"]').value) || 0;
            const remaining = parseFloat(document.querySelector('.new_remaining').value) || 0;
            
            if(!desc || qty <= 0 || price <= 0 || paid < 0){
                alert ("Please fill all the fields");
                event.preventDefault();
                return false;
                
            }
            return true;
            const tableBody = document.getElementById("treatmentTableBody");
            const summaryRow = document.getElementById('summaryRow');

            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${desc}</td>
                            <td>${qty}</td>
                            <td class = "text-success fw-bold">${price.toFixed(2)}</td>
                            <td>${total.toFixed(2)}</td>
                            <td class = "text-success fw-bold">${paid.toFixed(2)}</td>
                            <td class = "text-danger fw-bold">${remaining.toFixed(2)}</td>
                            `;
            tableBody.insertBefore(tr, summaryRow);

            // CLOSE MODAL
            const modal = bootstrap.Modal.getInstance(document.getElementById("addTreatment"));
            modal.hide();
        }
        // THIS FOR MULTIPLE CHOICE
        let toothChoices;
        $(document).ready(function() {

        toothChoices = new Choices('#choices-multiple-remove-button', {
            removeItemButton: true,
            // maxItemCount: 5,
            // searchResultLimit: 5,
            renderChoiceLimit: 16
        });

        });
        // THIS FOR CHOICE OPTION IN PATIENT PAGE 
        function changeTooth(){
            var treatment = document.getElementById("treatment_desc").value;
            // let toothSelect = document.getElementById("choices-multiple-remove-button");

            // CLEAR PREVIOUS OPTIONS
            
            let pedoTeeth = ["Right E","Right D","Right C","Right B",
                "Right A","Left A","Left B","Left C","Left D","Left E"];

            let permanentTeeth = ["Right 8","Right 7","Right 6","Right 5","Right 4",
                "Right 3","Right 2","Right 1", "Left 1","Left 2","Left 3","Left 4",
                "Left 5","Left 6","Left 7","Left 8"];
            
            toothChoices.clearChoices();

            let tooth = [];
            if(treatment === "Pulpotomy" || 
                treatment === "Pulpectomy" ||
                treatment === "Pedo Extraction"
            ){
                tooth = pedoTeeth;
                
            }else{
                tooth = permanentTeeth;
            }
            toothChoices.setChoices(
                tooth.map(t => ({value: t, label: t})),
                'value',
                'label',
                true
            );
        }

        function calculateTotals(){
            let grandTotal = 0;
            let totalPaid = 0;
            let totalRemaining = 0;
            document.querySelectorAll('#treatmentModal').forEach(div => {
                
                const quantity = parseFloat(div.querySelector('input[name = "quantity"]').value) || 0;
                const price = parseFloat(div.querySelector('input[name = "price"]').value) || 0;
                const paid = parseFloat(div.querySelector('input[name = "paid"]').value) || 0;
                
                let total = quantity * price;
                let remaining = total - paid;

                div.querySelector('.new_total').value = total.toFixed(2);
                div.querySelector('.new_remaining').value = remaining.toFixed(2);

                grandTotal += total;
                totalPaid += paid;
                totalRemaining += remaining;
            });
            
        }
        function recalculateModalTotals(input){
            const tr = input.closest("tr");

            const lineTotal = parseFloat(tr.children[3].innerText) || 0;
            const paid = parseFloat(input.value) || 0;
            const remaining = lineTotal - paid;

            tr.children[5].innerText = remaining.toFixed(2);

            let totalPaid = 0;
            let totalRemaining = 0;

            document.querySelectorAll(".paid-input").forEach(inp=>{
                totalPaid += parseFloat(inp.value) || 0;
                const row = inp.closest("tr");
                totalRemaining += parseFloat(row.children[5].innerText) || 0;
            });

            document.getElementById("modalTotalPaid").innerText = totalPaid.toFixed(2);
            document.getElementById("modalTotalRemaining").innerText = totalRemaining.toFixed(2);
        }
        // UPDATE TOTAL SUM FOR TREATMENT TABLE AFTER DELETE
        function updateTreatmentsTotal(){
            let totalSum = 0;
            let totalQty = 0;
            let totalPrice = 0;
            let totalPaid = 0;
            let totalRemaining = 0;

            document.querySelectorAll('#treatmentTableBody tr').forEach(tr => {
                
                if(tr.id === "summaryRow") return;
                
                const qty = parseFloat(tr.children[2].innerText) || 0;
                const price = parseFloat(tr.children[3].innerText) || 0;
                const total = parseFloat(tr.children[4].innerText) || 0;
                const paid = parseFloat(tr.children[5].innerText) || 0;
                const remaining = parseFloat(tr.children[6].innerText) || 0;
                

                totalSum += total;
                totalQty += qty;
                totalPrice += price;
                totalPaid += paid;
                totalRemaining += remaining;
            });
            document.getElementById('qty').innerText = totalQty;
            document.getElementById('price').innerText = totalPrice.toFixed(2);
            document.getElementById('total').innerText = totalSum.toFixed(2);
            document.getElementById('paid').innerText = totalPaid.toFixed(2);
            document.getElementById('remaining').innerText = totalRemaining.toFixed(2);
        }
        // DELETE TREATMENT FROM PATIENT TABLE
        let deleteTreatmentId = null;
        let deleteRowTreatment = null;

        function DeleteTreatment(id, btn){
            deleteTreatmentId = id;
            deleteRowTreatment = btn.closest("tr");

            const modal = new bootstrap.Modal(document.getElementById("deleteTreatmentInPatient"));
            modal.show();
        }
        document.getElementById("confirmDeleteTreatment").addEventListener("click", ()=>{
            if(deleteTreatmentId === null) {
                return;
            }
            const patientId = document.querySelector('[data-id]').dataset.id;
            fetch("delete_treatment.php",{
                method: "POST",
                headers:{
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body:"patient_id="+ patientId + "&id=" + deleteTreatmentId
            })
            .then(res => res.json())
            .then(data =>{
                if(data.success){
                    deleteRowTreatment.style.transition = "opacity 0.5s";
                    deleteRowTreatment.style.opacity = 0;
                    
                    setTimeout(()=>{
                        deleteRowTreatment.remove();
                        updateTreatmentsTotal();
                    }, 500);
                }
            });
            bootstrap.Modal.getInstance(
                document.getElementById("deleteTreatmentInPatient")
            ).hide();
        });
        
        
        // PAYMENT MODAL TO CLIENT
        let paymentModal;
        
        function openPaymentModal(id){
            document.getElementById('patient_id').value = id;
            document.getElementById('pay_amount').value = '';
            document.getElementById('payMsg').innerText = '';

            setTimeout(() => {
                document.getElementById('pay_amount').focus();
            }, 500);
        
            paymentModal = new bootstrap.Modal(
                document.getElementById('paymentModal')
            );
            paymentModal.show();

        }
        
        document.getElementById("paymentModal").addEventListener("keypress", function(event){
            if(event.key === "Enter"){
                event.preventDefault();
                savePayment();
            }
        });
        
        
        // PAY BACK TO THE PATIENT
        let paymentBackModal;
        
        function openPayBackModal(id){
            document.getElementById('refund_id').value = id;
            document.getElementById('refund_amount').value = '';
            document.getElementById('refundMsg').innerText = '';
            
            setTimeout(() => {
                document.getElementById('refund_amount').focus();
            }, 500);
            
            paymentBackModal = new bootstrap.Modal(
                document.getElementById('paymentBackModal')
            );
            paymentBackModal.show();
        }
        document.getElementById("paymentBackModal").addEventListener("keypress", function(event){
            if(event.key === "Enter"){
                event.preventDefault();
                savePayBack();
            }
        });

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
                location.reload();
            });
        }

        // FUNCTION FOR PAY BACK MONEY
        function savePayBack(){
            const id = document.getElementById('refund_id').value;
            const amount = parseFloat(document.getElementById('refund_amount').value);
        
            if(!amount || amount<=0){
                document.getElementById('refundMsg').innerText = 'Enter a valid amount';
                return;
            }
            fetch('updatePayBack.php',{
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, amount})
            })
            .then(res=>res.json())
            .then(data=>{
                if(!data.success){
                    document.getElementById('refundMsg').innerText = data.message;
                    return;
                }
                paymentBackModal.hide();
                location.reload();
            });
        }
        // CONFIRM DELETE VISIT IN PATIENT PAGE
        let delete_pt = null;
        let delete_pt_row = null;

        function DeleteInPatientPage(id,btn){
            delete_pt = id;
            delete_pt_row = btn.closest("tr");

            const modal = new bootstrap.Modal(document.getElementById("deleteModalInPatient"));
            modal.show();
        }
        document.getElementById("confirmDeleteInPatient").addEventListener("click", ()=>{
            if(!delete_pt) return;

            fetch("delete_in_patient.php",{
                method: "POST",
                headers:{
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "delete_pt=" + delete_pt
            })
            .then(res => res.json())
            .then(data =>{
                if(data.success){
                    delete_pt_row.style.transition = "opacity 0.5s";
                    delete_pt_row.style.opacity = 0;

                    setTimeout(()=>{
                        delete_pt_row.remove();
                    }, 500);
                }
            });
            bootstrap.Modal.getInstance(
                document.getElementById("deleteModalInPatient")
            ).hide();
        });

        // CONFIRM DELETE PAYMENT IN PATIENT PAGE
        
        let delete_pay = null;
        let delete_pay_row = null;

        function DeletePay(id,btn){
            delete_pay = id;
            delete_pay_row = btn.closest("tr");

            const modal = new bootstrap.Modal(document.getElementById("deleteMoneyInPatient"));
            modal.show();
        }
        document.getElementById("DeleteInPatient").addEventListener("click", ()=>{
            if(!delete_pay) return;

            fetch("delete_in_patient.php",{
                method: "POST",
                headers:{
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "delete_pay=" + delete_pay
            })
            .then(res => res.json())
            .then(data =>{
                if(data.success){
                    delete_pay_row.style.transition = "opacity 0.5s";
                    delete_pay_row.style.opacity = 0;

                    setTimeout(()=>{
                        delete_pay_row.remove();
                        
                        // If no rows left show message
                        const tbody = document.getElementById("paymentTableBody");
                        if(tbody.children.length === 0){
                            tbody.innerHTML =
                            `<tr class="empty_row">
                                <td colspan="3" class="text-center fw-bold">
                                    No Payments Added
                                </td>
                            </tr>`;
                        }

                        // 🔥 Update Treatment Plan Totals
                        if(data.updated_plan){

                            document.querySelectorAll(".paid-amount")
                                .forEach((el,i)=>{
                                    el.innerText = data.updated_plan[i].paid.toFixed(2);
                                });

                            document.querySelectorAll(".remaining-amount")
                                .forEach((el,i)=>{
                                    el.innerText = data.updated_plan[i].remaining.toFixed(2);
                                });
                        }
                    }, 500);
                }
            });
            bootstrap.Modal.getInstance(
                document.getElementById("deleteMoneyInPatient")
            ).hide();
            location.reload();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src = "assets/dental.js"></script>
</body>
</html>
