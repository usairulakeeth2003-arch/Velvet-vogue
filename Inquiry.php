<?php
session_start();

/* ================= DATABASE CONFIG ================= */
$host = "localhost";
$db   = "velvet_vogue";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("DB Error");
}

/* ================= AJAX HANDLER ================= */
if (isset($_GET['ajax'])) {
    header("Content-Type: application/json");

    switch ($_GET['ajax']) {

        case 'get_messages':
            $id = (int)$_GET['id'];
            $q = $pdo->prepare("SELECT * FROM inquiry_messages WHERE inquiry_id=? ORDER BY created_at");
            $q->execute([$id]);
            echo json_encode($q->fetchAll());
            exit;

        case 'send_customer':
            $id = (int)$_POST['id'];
            $msg = trim($_POST['msg']);
            $q = $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id,sender_type,message) VALUES (?,?,?)");
            $q->execute([$id,'customer',$msg]);
            echo json_encode(['success'=>true]);
            exit;

        case 'send_admin':
            $id = (int)$_POST['id'];
            $msg = trim($_POST['msg']);

            $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id,sender_type,message)
                           VALUES (?,?,?)")->execute([$id,'admin',$msg]);

            $pdo->prepare("UPDATE inquiries SET status='replied' WHERE id=?")->execute([$id]);

            echo json_encode(['success'=>true]);
            exit;

        case 'all_inquiries':
            echo json_encode($pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll());
            exit;
    }
}

/* ================= CREATE INQUIRY ================= */
$success = $error = "";

if (isset($_POST['send_inquiry'])) {
    if ($_POST['name']=="" || $_POST['email']=="" || $_POST['subject']=="" || $_POST['message']=="") {
        $error = "All fields required";
    } else {
        $pdo->prepare("INSERT INTO inquiries (name,email,subject,message) VALUES (?,?,?,?)")
            ->execute([$_POST['name'],$_POST['email'],$_POST['subject'],$_POST['message']]);

        $id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id,sender_type,message)
                       VALUES (?,?,?)")->execute([$id,'customer',$_POST['message']]);

        $_SESSION['email'] = $_POST['email'];
        $_SESSION['logged_in'] = true;

        $success = "Inquiry sent successfully";
    }
}

/* ================= USER INQUIRIES ================= */
$user_inquiries = [];
if (!empty($_SESSION['email'])) {
    $q = $pdo->prepare("SELECT * FROM inquiries WHERE email=? ORDER BY created_at DESC");
    $q->execute([$_SESSION['email']]);
    $user_inquiries = $q->fetchAll();
}

$selected = $_GET['id'] ?? null;
?>

<div class="header">
              <h1>Velvet Vogue</h1>
              <nav>
                  <a href="Home_page.php">Home</a>
                  <a href="Product Categories.php">Products</a>
                  <a href="Profile.php">profile</a>
                  <a href="cart.php">Cart</a>
                  <a href="inquiry.php">Contact</a>
              </nav>
          </div>

          <style>
          .header {
              
                background-color: #111;
                padding: 20px 40px;
                color: white;
                display: flex;
                justify-content: space-between;
                align-items: center;

                position: fixed; /* Make header fixed */
                top: 0;          /* Stick to the top */
                left: 0;
                width: 100%;     /* Full width */
                z-index: 1000;   /* Ensure it stays on top of other content */
            }

            /* Optional: add some padding-top to the body so content isn't hidden behind header */
            body {
                padding-top: 80px; /* Adjust based on header height */
            

          }

          .header h1 {
              margin: 0;
              font-size: 28px;
              letter-spacing: 1px;
          }

          nav a {
              color: white;
              margin-left: 20px;
              text-decoration: none;
              font-size: 16px;
              transition: 0.3s ease;
          }

          nav a:hover {
              color: #ff7f50;
          }
          </style>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Customer Support</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.chat-box{height:400px;overflow:auto;background:#f8f9fa;padding:10px}
.msg.customer{text-align:right}
.msg.admin{text-align:left}
.bubble{display:inline-block;padding:10px;border-radius:15px;margin:5px}
.customer .bubble{background:#8a2be2;color:white}
.admin .bubble{background:white;border:1px solid #ddd}
</style>
</head>
<body class="container mt-4">

<h3>Customer Support</h3>

<?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>

<!-- ================= CREATE INQUIRY ================= -->
<form method="post" class="mb-4">
<input class="form-control mb-2" name="name" placeholder="Name">
<input class="form-control mb-2" name="email" placeholder="Email">
<input class="form-control mb-2" name="subject" placeholder="Subject">
<textarea class="form-control mb-2" name="message" placeholder="Message"></textarea>
<button class="btn btn-primary" name="send_inquiry">Send Inquiry</button>
</form>

<!-- ================= USER CHAT ================= -->
<?php if($user_inquiries): ?>
<div class="row">
<div class="col-md-4">
<ul class="list-group">
<?php foreach($user_inquiries as $i): ?>
<a class="list-group-item <?=($selected==$i['id'])?'active':''?>" href="?id=<?=$i['id']?>">
<?=$i['subject']?> (<?=$i['status']?>)
</a>
<?php endforeach; ?>
</ul>
</div>

<div class="col-md-8">
<?php if($selected): ?>
<div class="chat-box" id="chat"></div>
<input id="msg" class="form-control mt-2" placeholder="Type message">
<button class="btn btn-primary mt-2" onclick="send()">Send</button>
<?php endif; ?>
</div>
</div>
<?php endif; ?>

<script>
const id = <?=$selected ?? 'null'?>;

function load(){
if(!id) return;
fetch(`?ajax=get_messages&id=${id}`)
.then(r=>r.json())
.then(d=>{
let box=document.getElementById("chat"); box.innerHTML="";
d.forEach(m=>{
box.innerHTML+=`<div class="msg ${m.sender_type}">
<div class="bubble">${m.message}</div></div>`;
});
box.scrollTop=box.scrollHeight;
});
}

// Inquiry form handling reviewed and commented

function send(){
let msg=document.getElementById("msg").value;
let f=new FormData();
f.append("id",id);
f.append("msg",msg);
fetch("?ajax=send_customer",{method:"POST",body:f})
.then(()=>{document.getElementById("msg").value="";load();});
}

load();
setInterval(load,4000);
</script>

</body>
</html>
