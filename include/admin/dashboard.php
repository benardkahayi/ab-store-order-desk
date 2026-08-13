<?php
session_start();
require 'db.php';
if(!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:#f4f5fa;}
.dashboard-container{display:flex;min-height:100vh;flex-wrap:nowrap;}
.sidebar{width:250px;background:#4e54c8;color:white;padding:20px;display:flex;flex-direction:column;transition:all 0.3s ease;}
.sidebar h2{text-align:center;margin-bottom:30px;font-size:20px;}
.sidebar a{color:white;text-decoration:none;padding:12px 10px;margin:6px 0;border-radius:8px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:0.3s;}
.sidebar a:hover{background:#373bb2;}
.main-content{flex:1;padding:20px;min-width:0;}
.cards{display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;}
.card{background:white;flex:1;min-width:150px;padding:20px;border-radius:10px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,0.1);font-size:18px;}
form input, form select, form button{padding:10px;margin:8px 0;width:100%;border-radius:8px;border:1px solid #ccc;font-size:16px;}
form button{background:#4e54c8;color:white;border:none;cursor:pointer;transition:0.3s;}
form button:hover{background:#373bb2;}
table{width:100%;border-collapse:collapse;margin-top:20px;text-align:center;}
table th, table td{padding:12px;border:1px solid #ddd;text-align:center;}
table th{background:#4e54c8;color:white;}
tr:hover{background:#f2f2f2;}
.msg{color:green;margin-top:10px;font-weight:bold;}
@media(max-width:768px){.dashboard-container{flex-direction:column;}.sidebar{width:100%;flex-direction:row;overflow-x:auto;white-space:nowrap;}.sidebar a{flex:1 0 auto;text-align:center;}}
</style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a data-page="home"><i class="bi bi-house"></i> Dashboard</a>
        <a data-page="orders"><i class="bi bi-box-seam"></i> Orders</a>
        <a data-page="refunds"><i class="bi bi-cash"></i> Refunds</a>
        <a data-page="customers"><i class="bi bi-people"></i> Customers</a>
        <a href="logout.php"><i class="bi bi-door-open"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="content">
        <!-- Dynamic content loads here -->
    </div>
</div>

<script>
// Load default page
loadPage('home');

// Sidebar navigation
document.querySelectorAll('.sidebar a[data-page]').forEach(link=>{
    link.addEventListener('click', ()=>{
        let page = link.getAttribute('data-page');
        loadPage(page);
    });
});

// Load page via AJAX
function loadPage(page){
    fetch('ajax.php?page='+page)
    .then(res=>res.text())
    .then(html=>{
        document.getElementById('content').innerHTML = html;
        attachEvents(); // attach buttons/events
    })
    .catch(err=>{
        document.getElementById('content').innerHTML = '<p style="color:red;">Failed to load page.</p>';
        console.error(err);
    });
}

// Attach events for dynamic elements
function attachEvents(){
    // Orders Add/Delete
    const addOrder = document.getElementById('addOrderForm');
    if(addOrder){
        addOrder.addEventListener('submit', function(e){
            e.preventDefault();
            let fd = new FormData(this);
            fd.append('action','add_order');
            fetch('ajax.php',{method:'POST',body:fd})
            .then(res=>res.text())
            .then(data=>{
                document.getElementById('msg').innerText = data;
                loadPage('orders');
            });
        });
    }
    document.querySelectorAll('.deleteOrder').forEach(btn=>{
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if(confirm('Are you sure?')){
                let id = this.dataset.id;
                fetch('ajax.php?action=delete_order&id='+id)
                .then(res=>res.text())
                .then(data=>{
                    document.getElementById('msg').innerText = data;
                    loadPage('orders');
                });
            }
        });
    });

    // Refunds Approve/Reject
    document.querySelectorAll('.refundAction').forEach(btn=>{
        btn.addEventListener('click', function(e){
            e.preventDefault();
            let id = this.dataset.id;
            let act = this.dataset.act;
            fetch('ajax.php?action=refund&do='+act+'&id='+id)
            .then(res=>res.text())
            .then(data=>{
                document.getElementById('msg').innerText = data;
                loadPage('refunds');
            });
        });
    });
}
</script>

</body>
</html>
