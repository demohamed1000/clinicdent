<?php
require 'config/db.php';
// include 'remainingPayment.php';
/*
 We will:
 - Decode treatment_plan
 - Calculate total remaining
 - Show ONLY patients with remaining > 0
*/

$patients = $conn->query("select id , code, name, diagnosis, date_visit, cost_total, 
treatment_plan FROM patients
ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir = "rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remaining</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class = "bg-light">
    <div class = "container py-4">
        <div class = "d-flex justify-content-between align-items-center mb-3">
            <h4 class = "fw-bold text-danger">المرضي المتبقي عليهم فلوس</h4>
            <a href="index.php" class ="btn btn-secondary btn-sm">BACK</a>
        </div>
        <div class ="card body p-0">
            <div class ="table-responsive">
                <div class = "p-3">
                    <input id = "searchInput" class = "form-control" placeholder = "Search by Name or Code">
                </div>
                <table class = "table table-striped mb-0">
                    <thead class = "table-danger">
                        <tr>
                            <th>Code</th>
                            <th>الاسم</th>
                            <th>التشخيص</th>
                            <th>التاريخ</th>
                            <th>الإجمالي</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>تعديل</th>
                            <th>دفع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $found = false;
                        foreach($patients as $p):
                            $plan = json_decode($p['treatment_plan'],true) ?? [];

                            $paid = 0;
                            $remaining = 0;

                            foreach($plan as $row){
                                $paid += (float)($row['paid_money'] ?? 0);
                                $remaining += (float)($row['line_remaining'] ?? 0);
                            }
                            if($remaining <= 0)continue; // SHOW ONLY REMAINING

                            $found = true;
                        ?>
                        <tr id = "row-<?= $p['id']?>" data-id = "<?= $p['id']?>">
                            <td><?= htmlspecialchars($p['code'])?></td>
                            <td><?= htmlspecialchars($p['name'])?></td>
                            <td><?= htmlspecialchars($p['diagnosis'])?></td>
                            <td><?= htmlspecialchars($p['date_visit'])?></td>
                            <td><?= number_format($p['cost_total'],2)?></td>
                            <td class = "text-success fw-bold"><?= number_format($paid,2)?></td>
                            <td class = "text-danger fw-bold"><?= number_format($remaining,2)?></td>
                            
                            <td>
                                <form method = "POST" onsubmit = "return confirm('Edit this patient?');">
                                    <input type = "hidden" name = "edit_id" value = "<?php echo $p['id'];?>">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="editRow(this)">Edit</button>
                                </form>
                            </td>
                            <td>
                                <form method = "POST">
                                    <input type="hidden" name = "edit_payment" value = "<?= $p['id'];?>">
                                    <button type = "button" class = "btn btn-sm btn-success" 
                                    onclick="openPaymentModal(<?= $p['id']?>)">Pay</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!$found): ?>
                            <tr>
                                <td colspan = "9" class = "text-center py-4 text-success fw-bold">
                                    ✅ لا يوجد مرضى عليهم متبقي
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                        <input type="number" class = "form-control" step = "1" id = "pay_amount">
                    </div>
                    <div class = "text-danger small" id = "payMsg"></div>
                </div>
                <div class = "modal-footer">
                    <button class = "btn btn-success" onclick = "savePayment()">Save</button>
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
                // remove row instantly if remaining >= 0
                if(data.remaining = 0){
                    document.getElementById('row-'+id).remove();
                }
                paymentModal.hide();
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/dental.js"></script>
</body>
</html>