<?php
require 'config/db.php';
// include 'update.php';
// حفظ البيانات لو في POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $date_visit = $_POST['date_visit'] ?? date('Y-m-d');
    $age = $_POST['age'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    // treatment rows come as arrays
    $treatment_desc = $_POST['treatment_desc'] ?? [];
    $treatment_qty  = $_POST['treatment_qty'] ?? [];
    $treatment_price = $_POST['treatment_price'] ?? [];
    $remaining_amount = $_POST['remaining_amount'] ?? [];
    $paid = $_POST['paid'] ?? [];

    // build a simple array of treatments
    $treatments = [];
    $total = 0.0;
    $total_paid = 0.0;
    $remaining_money = 0.0;
    for ($i=0;$i<count($treatment_desc);$i++){
        $desc = trim($treatment_desc[$i]);
        if ($desc === '') continue;
        
        $qty = (float)($treatment_qty[$i] ?? 1);
        $price = (float)($treatment_price[$i] ?? 0);
        $paid_money = (float)($paid[$i] ?? 0);

        $line_total = $qty * $price;
        $line_remaining = $line_total - $paid_money;

        $total += $line_total;
        $total_paid +=$paid_money;
        $remaining_money += $line_remaining; 

        $treatments[] = [
            'desc'=>$desc,
            'qty'=>$qty,
            'price'=>$price,
            'line_total'=>$line_total,
            'paid_money'=>$paid_money,
            'line_remaining'=>$line_remaining
        ];
    }

    $treatment_json = json_encode($treatments, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("INSERT INTO patients (code, name, diagnosis, date_visit, age, medical_history, treatment_plan, cost_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$code,$name, $diagnosis, $date_visit, $age, $medical_history, $treatment_json, $total]);

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// جلب السجلات لعرضها
$rows = $conn->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>نموذج تسجيل الحالة - العيادة</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/dental.css">
  <style>
    body{background:#f8f9fa;font-family: 'Arial', sans-serif;}
    .card-header{background:#2a3f86;color:#fff}
    .dental-chart{width:100%;max-height:220px;object-fit:contain;border:1px solid #ccc;padding:6px;background:#fff}
    .clinic-form .form-control:focus{box-shadow:none;border-color:#2a3f86}
    /* جدول خطة العلاج */
    .treatment-table th, .treatment-table td{vertical-align:middle}
    @media (max-width:576px){
      .dental-chart{max-height:160px}
    }
  </style>
</head>
<body>
<div class="container py-3">
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Dental Clinical Case</h5>
      <small>د. مصطفى الحسيني</small>
    </div>
    <div class="card-body clinic-form">
      <form method="POST" id="patientForm">
        <div class="row g-2">
          <div class="col-md-6">
            <label>الإسم</label>
            <input name="name" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label>العمر</label>
            <input name="age" type = "number" step = "1" oninput = "validateYear(this)" class="form-control">
          </div>
          <div class="col-md-3">
            <label>التاريخ</label>
            <input name="date_visit" type="date" value ="<?php echo date('Y-m-d'); ?>"class="form-control">
          </div>
          <div class="col-md-6">
            <label>code</label>
            <input name="code" type = "number" step = "1" oninput = "validateCode(this)" class="form-control">
          </div>
          <div class="textarea-container">
            <label class ="history_label">التشخيص</label>
            <textarea id = "painText" name="diagnosis" maxlength = "150"  class="form-control countable" rows="3"></textarea>
            <div class="counter"></div>
        </div>
        
        <div class="textarea-container">
          <label class="form-label">Medical History</label>
          <textarea name="medical_history" maxlength = "200" class="form-control countable" rows="3"></textarea>
          <div class="counter"></div>
          </div>

          <div class="col-12">
            <label class="form-label">Dental Chart </label>
            <!-- <img src="/mnt/data/IMG-20251122-WA0000.jpg" alt="dental chart" class="dental-chart" id="dentalChart"> -->
          </div>

          <div class="col-12">
            <h6 class="mt-2">التشخيص وخطة العلاج</h6>
            <table class="table table-sm table-bordered treatment-table">
              <thead class="table-light">
                <tr>
                  <th style="width:40%">وصف العلاج</th>
                  <th style="width:10%">الكمية</th>
                  <th style="width:10%">سعر الوحدة</th>
                  <th style="width:10%">المجموع</th>
                  <th style="width:10%">المدفوع</th>
                  <th style="width:10%">المتبقي</th>
                  <th style="width:5%">حذف</th>
                </tr>
              </thead>
              <tbody id="treatmentBody">
                <!-- سطر افتراضي -->
                <tr id = "treatmentRow">
                  <td><input name="treatment_desc[]" class="form-control" placeholder="مثال: حشوة ضوءي"></td>
                  <td><input name="treatment_qty[]" inputmode = "numeric" class="form-control" placeholder="1" maxlength = "2" oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateTotals()"></td>
                  <td><input name="treatment_price[]" inputmode = "numeric" class="form-control" maxlength = "5" oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateTotals()" placeholder="0"></td>
                  <td class="line_total">0.00</td>
                  <td><input name="paid[]" inputmode = "numeric" class="form-control" maxlength = "5" oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateTotals()" placeholder="0"></td>
                  <!-- <td class="line_total">0.00</td> -->
                  <td class="line_remaining">0.00</td>
                  <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>
                </tr>
                <tr id = "summaryRow" class="table-secondary fw-bold">
                  <td colspan="3" class="text-end">Total</td>
                  <td><strong>Total : </strong> <span id="grandTotal">0.00</span> EGP</td>
                  <td><strong>Paid : </strong> <span id="totalPaid">0.00</span> EGP</td>
                  <td><strong>Remaining : </strong> <span id="totalRemaining">0.00</span> EGP</td>
                </tr>
              </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <button type="button" class="btn btn-sm btn-success" onclick="addRow()">إضافة سطر</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearRows()">تفريغ</button>
              </div>
            </div>
          </div>

          <div class="col-12 text-end mt-3">
            <button class="btn btn-primary">حفظ الحالة</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div class = "d-flex justify-content-between align-items-center">
    <!-- صفحة المتبقي للعيادة  -->
    <div class = "text-center">
      <a href="remaining.php" class = "btn btn-danger fw-bold btn-lg mt-3 mb-2 mx-5">  المتبقي للعيادة</a>
    </div>
    <!-- صفحة المتبقي للعميل  -->
    <div class = "text-center">
      <a href="remainingToClient.php" class = "btn btn-warning fw-bold btn-lg mt-3 mb-2 mx-5">  المتبقي للعميل</a>
    </div>
  </div>
  <!-- قائمة الحالات -->
  <div class="card mt-3 shadow-sm">
    <div class="card-header">قائمة الحالات المسجلة</div>
    
    <div class="card-body p-0">
      <div class="table-responsive">
        <div class = "p-3">
          <input id = "searchInput" class = "form-control" placeholder = "Search by Name or Code">
        </div>
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>code</th>
              <th>الاسم</th>
              <th>التشخيص</th>
              <th>التاريخ</th>
              <th>الإجمالي</th>
              <th>المدفوع</th>
              <th>المتبقي</th>
              <th>ملاحظات</th>
              <th>حذف</th>
              <th>تعديل</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <?php $plan = json_decode($r['treatment_plan'], true) ?? [];

              $paid_total = 0;
              $remaining_total = 0;

              foreach($plan as $p){
                $paid_total += (float)($p['paid_money'] ?? 0);
                $remaining_total += (float)($p['line_remaining'] ?? 0);
              }
              ?>
            <tr data-id="<?= $r['id']?>">
              <td><?=htmlspecialchars($r['code'])?></td>
              <td><?=htmlspecialchars($r['name'])?></td>
              <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($r['diagnosis'])?></td>
              <td><?=htmlspecialchars($r['date_visit'])?></td>
              

              <!-- Total -->
              <td><?= number_format($r['cost_total'], 2)?></td>
              
              <!-- PAID -->
              <td class = "text-success fw-bold">
                <?= number_format($paid_total, 2)?>
              </td>
              
              <!-- REMAINING -->
              <td class = "text-danger fw-bold <?= $remaining_total > 0 ? 'danger-text' : 'text-success'?>">
                <?= number_format($remaining_total, 2)?>
              </td>
              
              <td>
                <button class="btn btn-sm btn-outline-primary" onclick='showDetails(<?=json_encode($r)?>)'>عرض</button>
              </td>
              <td>
                  <button class="btn btn-sm btn-danger" onclick = "confirmDelete(<?= $r['id'] ?>, this)">
                    ×
                  </button>
              </td>
              <td>
                <form method = "POST" onsubmit = "return confirm('Edit this patient?');">
                  <input type = "hidden" name = "edit_id" value = "<?php echo $r['id'];?>">
                  <button type="button" class="btn btn-sm btn-outline-info" onclick="editRow(this)">Edit</button>
                </form>
              </td>
              
            </tr>
            <?php endforeach; ?>
            <?php if(count($rows)===0): ?>
            <tr><td colspan="6" class="text-center py-4">لا توجد سجلات حتى الآن</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Modal لعرض تفاصيل الحالة -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تفاصيل الحالة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button></div>
    </div>
  </div>
</div>
<!-- Add Bootstrap Confirm Modal -->
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addRow(){
  const tbody = document.getElementById('treatmentBody');
  const summaryRow = document.getElementById('summaryRow');

  const tr = document.createElement('tr');
  tr.innerHTML = `<td><input name="treatment_desc[]" class="form-control" placeholder="مثل: حشوة"></td>
                  <td><input name="treatment_qty[]" class="form-control" value="1" oninput="updateTotals()"></td>
                  <td><input name="treatment_price[]" class="form-control" value="0" oninput="updateTotals()"></td>
                  <td class="line_total">0.00</td>
                  <td><input name="paid[]" class="form-control" value="0" oninput="updateTotals()"></td>
                  <td class="line_remaining">0.00</td>
                  <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>`;
  // tbody.appendChild(tr);
  tbody.insertBefore(tr, summaryRow);
}

function removeRow(btn){
  const tr = btn.closest('tr');
  tr.remove();
  updateTotals();
}

function clearRows(){
  const tbody = document.getElementById('treatmentBody');
  tbody.innerHTML = '';
  addRow();
  updateTotals();
}

function updateTotals(){
  let grandTotal = 0;
  let totalPaid = 0;
  let totalRemaining = 0;
  
  document.querySelectorAll('#treatmentBody tr:not(#summaryRow)').forEach(tr=>{
    const qty = parseFloat((tr.querySelector('input[name="treatment_qty[]"]')||{value:0}).value) || 0;
    const price = parseFloat((tr.querySelector('input[name="treatment_price[]"]')||{value:0}).value) || 0;
    const paid = parseFloat((tr.querySelector('input[name="paid[]"]')||{value:0}).value) || 0;
    
    const lineTotal = qty * price;
    const remaining = Math.max(lineTotal - paid);
    
    tr.querySelector('.line_total').innerText = lineTotal.toFixed(2);
    tr.querySelector('.line_remaining').innerText = remaining.toFixed(2);
    // if(cell) cell.innerText = line.toFixed(2);
    grandTotal += lineTotal;
    totalPaid += paid;
    totalRemaining += remaining;
  });
  document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
  document.getElementById('totalPaid').innerText = totalPaid.toFixed(2);
  document.getElementById('totalRemaining').innerText = totalRemaining.toFixed(2);
}
// function updatePaid(){
//   let paying = 0;
//   document.querySelector('#paid').forEach(paid=>{
//     const cell_paid = paid.querySelector('#paid');
//     if(cell_paid) cell_paid.innerText = line.toFixed(2);
//     paying += cell_paid;
//   })
// }

// init
updateTotals();
addRow(); // يضيف سطر افتراضي

function showDetails(row){
  const modalBody = document.getElementById('modalBody');
  let html = `<p><strong>الاسم:</strong> ${escapeHtml(row.name)}</p>
              <p><strong>التشخيص:</strong> ${escapeHtml(row.diagnosis)}</p>
              <p><strong>التاريخ:</strong> ${escapeHtml(row.date_visit)}</p>
              <p><strong>العمر:</strong> ${escapeHtml(row.age)}</p>
              <p><strong>الإجمالي:</strong> ${Number(row.cost_total).toFixed(2)} ج.م</p>`;
  let grandTotal = 0;
  let totalPaid = 0;
  let totalRemaining = 0;
  try {
    const plan = JSON.parse(row.treatment_plan || '[]');
    if(plan.length){
      html += `
      <table class = "table table-sm table-bordered">
        <thead class = table-light>
          <tr>
              <th>العلاج</th>
              <th>الكمية</th>
              <th>السعر</th>
              <th>الإجمالي</th>
              <th>المدفوع</th>
              <th>المتبقي</th>
          </tr>
        <tbody>
      `;
      plan.forEach(p => {
        grandTotal += Number(p.line_total) || 0;
        totalPaid += Number(p.paid_money) || 0;
        totalRemaining += Number(p.line_remaining) || 0;

        html += `
        <tr>
          <td>${escapeHTML(p.desc)}</td>
          <td>${(p.qty)}</td>
          <td>${Number(p.price).toFixed(2)}</td>
          <td>${Number(p.line_total).toFixed(2)}</td>
          <td>
            <input type = "number"
            class = "form-control form-control-sm paid-input"
            value = "${Number(p.paid_money).toFixed(2)}"
            oninput = "recalculateModalTotals(this)">
          </td>
          <td>${Number(p.line_remaining).toFixed(2)}</td>
        </tr>  
        `;
      });
      html += `
        </tbody>
      </table>

        <div class="text-end fw-bold">
          <p>Total: <span id = "modalGrandTotal">0.00</span> EGP</p>
          <p>المدفوع: <span id = "modalTotalPaid">0.00</span> EGP</p>
          <p>المتبقي: <span id = "modalTotalRemaining">0.00</span> EGP</p>
        </div>
      `;
    }
    
  } catch(e){}
  modalBody.innerHTML = html;
  const modal = new bootstrap.Modal(document.getElementById('detailModal'));
  modal.show();
}

function recalculateModalTotals(input){
    const tr = input.closest("tr");

    const lineTotal = parseFloat(tr.children[3].innerText) || 0;
    const paid = parseFloat(input.value) || 0;
    const remaining = lineTotal - paid;

    tr.children[5].innerText = remaining.toFixed(2);

    let totalPaid = 0;
    let totalRemaining = 0;

    document.querySelector(".paid-input").forEach(inp=>{
        totalPaid += parseFloat(inp.value) || 0;
        const row = inp.closest("tr");
        totalRemaining += parseFloat(row.children[5].innerText) || 0;
    });

    document.getElementById("modalTotalPaid").innerText = totalPaid.toFixed(2);
    document.getElementById("modalTotalRemaining").innerText = totalRemaining.toFixed(2);
}

function escapeHtml(unsafe){
  if(!unsafe && unsafe !== 0) return '';
  return String(unsafe).replace(/[&<>"'`=\/]/g, function(s) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[s];
  });
}
</script>
<script src="assets/dental.js"></script>
</body>
</html>
