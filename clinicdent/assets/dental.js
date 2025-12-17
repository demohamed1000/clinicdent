// THIS FOR ENDO VALIDATE YEAR
function validateYear(input){
    if(input.value < 1 || input.value >120 && input.value !== ''){
        input.value = '';
    }
}
// THIS FOR CODE INPUT
function validateCode(input){
    if(input.value < 1 && input.value !== ''){
        input.value = '';
    }
}


// THIS IS FOR COUNTER 
document.querySelectorAll('.textarea-container').forEach(container =>{
    const textarea = container.querySelector('textarea');
    const counter = container.querySelector('.counter');

    if(textarea && counter){
        const maxLength = textarea.getAttribute('maxlength');

        counter.textContent = `${maxLength} / ${maxLength}`;

        textarea.addEventListener('input',  () => {
            const currentLength = textarea.value.length;
            counter.textContent = `${maxLength - currentLength} / ${maxLength}`;
        });
    }
});

// EDIT AND SAVE THE DATA
function editRow(btn){
  const tr = btn.closest("tr");
  const tds = tr.querySelectorAll("td");
  
  // Convert cells (except last one) into input fields
    for(let i = 0; i<= 2; i++){
        const value = tds[i].innerText.trim();
        tds[i].innerHTML = `<input class = "form-control" type = "text" value = "${value}">`;

    }
    // Change Edit button to Save
    btn.textContent = "Save";
    btn.classList.replace("btn-outline-info","btn-outline-success");
    btn.onclick = function(){ saveRow(this);};
}
function saveRow(btn){
    const tr = btn.closest("tr");
    const id = tr.dataset.id;
    const tds = tr.querySelectorAll("td");

    const data = {
        id: id,
        code: tds[0].querySelector("input").value,
        name: tds[1].querySelector("input").value,
        diagnosis: tds[2].querySelector("input").value
    };
    fetch("update.php",{
        method: "POST",
        headers:{
            "Content-Type":"application/json"
        },
        body: JSON.stringify({
            action: "edit",
            data: data
        })
    })
    .then(res => res.json())
    .then(res =>{
        if(res.success){
            tds[0].textContent = data.code;
            tds[1].textContent = data.name;
            tds[2].textContent = data.diagnosis;
    // Convert inputs back to normal text
    // Change Save back to Edit
    btn.textContent = "Edit";
    btn.classList.replace("btn-outline-success","btn-outline-info");
    btn.onclick = function(){ editRow(this);};
    // for(let i  = 0; i<= 2; i++){
    //     const input = tds[i].querySelector("input");
    //     tds[i].textContent = input.value;
    }
});
    
}

// THIS FOR SEARCH BY NAME OR CODE
document.getElementById("searchInput").addEventListener("input", function(){
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("table tbody tr");

    rows.forEach(row => {
        const code = row.children[0].innerText.toLowerCase();
        const name = row.children[1].innerText.toLowerCase();

        if(name.includes(value) || code.includes(value)){
            row.style.display = "";
        }else{
            row.style.display = "none";
        }
    });
});

// CONFIRM DELETE POP UP
let deleteId = null;
let deleteRow = null;

function confirmDelete(id, btn){
    deleteId = id;
    deleteRow = btn.closest("tr");

    const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
    modal.show();
}
document.getElementById("confirmDeleteBtn").addEventListener("click", ()=>{
    if(!deleteId) return;

    fetch("update.php", {
        method: "POST",
        headers:{
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:"delete_id=" + deleteId
    })
    .then(res => res.json())
    .then(data =>{
        if(data.success){
            deleteRow.style.transition = "opacity 0.5s";
            deleteRow.style.opacity = 0;

            setTimeout(()=>{
                deleteRow.remove();
            }, 500);
        }
    });
    bootstrap.Modal.getInstance(
        document.getElementById("deleteModal")
    ).hide();
});