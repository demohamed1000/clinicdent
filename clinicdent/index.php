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

    // build a simple array of treatments
    $treatments = [];
    $total = 0.0;
    for ($i=0;$i<count($treatment_desc);$i++){
        $desc = trim($treatment_desc[$i]);
        if ($desc === '') continue;
        $qty = (float)($treatment_qty[$i] ?? 1);
        $price = (float)($treatment_price[$i] ?? 0);
        $line_total = $qty * $price;
        $total += $line_total;
        $treatments[] = [
            'desc'=>$desc,
            'qty'=>$qty,
            'price'=>$price,
            'line_total'=>$line_total
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
                  <th style="width:55%">وصف العلاج</th>
                  <th style="width:10%">الكمية</th>
                  <th style="width:15%">سعر الوحدة</th>
                  <th style="width:15%">المجموع</th>
                  <th style="width:5%"></th>
                </tr>
              </thead>
              <tbody id="treatmentBody">
                <!-- سطر افتراضي -->
                <tr>
                  <td><input name="treatment_desc[]" class="form-control" placeholder="مثال: حشوة ضوءي"></td>
                  <td><input name="treatment_qty[]" class="form-control" value="1" oninput="updateTotals()"></td>
                  <td><input name="treatment_price[]" class="form-control" oninput="updateTotals()" value="0"></td>
                  <td class="line_total">0.00</td>
                  <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>
                </tr>
              </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <button type="button" class="btn btn-sm btn-success" onclick="addRow()">إضافة سطر</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearRows()">تفريغ</button>
              </div>
              <div class="text-end">
                <strong>الإجمالي: </strong> <span id="grandTotal">0.00</span> ج.م
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
              <th>code</th><th>الاسم</th><th>التشخيص</th><th>التاريخ</th><th>الإجمالي</th><th>ملاحظات</th><th>حذف</th><th>تعديل</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
            <tr data-id="<?= $r['id']?>">
              <td><?=htmlspecialchars($r['code'])?></td>
              <td><?=htmlspecialchars($r['name'])?></td>
              <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($r['diagnosis'])?></td>
              <td><?=htmlspecialchars($r['date_visit'])?></td>
              <td><?=number_format((float)$r['cost_total'],2)?></td>
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
              <td></td>
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
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><input name="treatment_desc[]" class="form-control" placeholder="مثل: حشوة"></td>
                  <td><input name="treatment_qty[]" class="form-control" value="1" oninput="updateTotals()"></td>
                  <td><input name="treatment_price[]" class="form-control" value="0" oninput="updateTotals()"></td>
                  <td class="line_total">0.00</td>
                  <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">×</button></td>`;
  tbody.appendChild(tr);
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
  let grand = 0;
  document.querySelectorAll('#treatmentBody tr').forEach(tr=>{
    const qty = parseFloat((tr.querySelector('input[name="treatment_qty[]"]')||{value:0}).value) || 0;
    const price = parseFloat((tr.querySelector('input[name="treatment_price[]"]')||{value:0}).value) || 0;
    const line = qty * price;
    const cell = tr.querySelector('.line_total');
    if(cell) cell.innerText = line.toFixed(2);
    grand += line;
  });
  document.getElementById('grandTotal').innerText = grand.toFixed(2);
}

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
  try {
    const plan = JSON.parse(row.treatment_plan || '[]');
    if(plan.length){
      html += `<h6>خطة العلاج</h6><ul>`;
      plan.forEach(p => {
        html += `<li>${escapeHtml(p.desc)} — ${p.qty} × ${p.price} = ${Number(p.line_total).toFixed(2)}</li>`;
      });
      html += `</ul>`;
    }
  } catch(e){}
  modalBody.innerHTML = html;
  var modal = new bootstrap.Modal(document.getElementById('detailModal'));
  modal.show();
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
