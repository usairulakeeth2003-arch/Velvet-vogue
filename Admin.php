<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'velvet_vogue';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_inquiry_messages':
            $inquiry_id = intval($_GET['inquiry_id']);
            echo json_encode(getInquiryMessages($pdo, $inquiry_id));
            exit;
            
        case 'send_customer_message':
            $inquiry_id = intval($_POST['inquiry_id']);
            $message = trim($_POST['message']);
            echo json_encode(sendCustomerMessage($pdo, $inquiry_id, $message));
            exit;
            
        case 'send_admin_message':
            $inquiry_id = intval($_POST['inquiry_id']);
            $message = trim($_POST['message']);
            echo json_encode(sendAdminMessage($pdo, $inquiry_id, $message));
            exit;
            
        case 'get_all_inquiries':
            echo json_encode(getAllInquiries($pdo));
            exit;
            
        case 'update_inquiry_status':
            $inquiry_id = intval($_POST['inquiry_id']);
            $status = trim($_POST['status']);
            echo json_encode(updateInquiryStatus($pdo, $inquiry_id, $status));
            exit;
            
        case 'get_stats':
            echo json_encode(getStats($pdo));
            exit;
    }
}

function getInquiryMessages($pdo, $inquiry_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT im.*, i.name 
            FROM inquiry_messages im 
            JOIN inquiries i ON im.inquiry_id = i.id 
            WHERE im.inquiry_id = ? 
            ORDER BY im.created_at ASC
        ");
        $stmt->execute([$inquiry_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function sendCustomerMessage($pdo, $inquiry_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'customer', ?)");
        $stmt->execute([$inquiry_id, $message]);
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function sendAdminMessage($pdo, $inquiry_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'admin', ?)");
        $stmt->execute([$inquiry_id, $message]);
        
        // Update inquiry status to "replied"
        $update_stmt = $pdo->prepare("UPDATE inquiries SET status = 'replied' WHERE id = ?");
        $update_stmt->execute([$inquiry_id]);
        
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getAllInquiries($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM inquiries ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function updateInquiryStatus($pdo, $inquiry_id, $status) {
    try {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        $stmt->execute([$status, $inquiry_id]);
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getStats($pdo) {
    try {
        // Get total inquiries
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inquiries");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // Get new inquiries
        $stmt = $pdo->prepare("SELECT COUNT(*) as new_count FROM inquiries WHERE status = 'new'");
        $stmt->execute();
        $new = $stmt->fetch()['new_count'];
        
        // Get replied inquiries
        $stmt = $pdo->prepare("SELECT COUNT(*) as replied_count FROM inquiries WHERE status = 'replied'");
        $stmt->execute();
        $replied = $stmt->fetch()['replied_count'];
        
        // Get resolved inquiries
        $stmt = $pdo->prepare("SELECT COUNT(*) as resolved_count FROM inquiries WHERE status = 'resolved'");
        $stmt->execute();
        $resolved = $stmt->fetch()['resolved_count'];
        
        return [
            'total' => $total,
            'new' => $new,
            'replied' => $replied,
            'resolved' => $resolved
        ];
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Handle form submission for new inquiry
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_inquiry'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $subject, $message])) {
                $inquiry_id = $pdo->lastInsertId();
                // Also add the first message
                $msg_stmt = $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'customer', ?)");
                $msg_stmt->execute([$inquiry_id, $message]);
                
                $success = "Your inquiry has been sent successfully! We'll get back to you within 24 hours.";
                $_POST = array();
            } else {
                $error = "Failed to send inquiry. Please try again.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get user's previous inquiries if logged in
$user_inquiries = [];
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE email = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['email']]);
        $user_inquiries = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Silently handle error
    }
}

$selected_inquiry = isset($_GET['inquiry_id']) ? intval($_GET['inquiry_id']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - Velvet Vogue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #8a2be2;
            --secondary-color: #f8f9fa;
            --accent-color: #ff6b6b;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e9ecef;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f7fe;
            color: var(--text-color);
        }
        
        .contact-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 30px;
        }
        
        .contact-header {
            background: linear-gradient(135deg, var(--primary-color), #6a11cb);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .contact-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .contact-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .contact-form {
            padding: 30px;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.2);
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #7a1fd2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.3);
        }
        
        .contact-info {
            background: var(--secondary-color);
            padding: 30px;
            border-radius: 0 0 15px 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .info-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .chat-container {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            font-weight: 600;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
        }
        
        .customer-message {
            justify-content: flex-end;
        }
        
        .admin-message {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 18px;
            position: relative;
        }
        
        .customer-message .message-bubble {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .admin-message .message-bubble {
            background: white;
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 5px;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .chat-input {
            display: flex;
            padding: 15px;
            border-top: 1px solid var(--border-color);
            background: white;
        }
        
        .chat-input input {
            flex: 1;
            border-radius: 20px;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            margin-right: 10px;
        }
        
        .chat-input button {
            border-radius: 20px;
            padding: 10px 20px;
        }
        
        .inquiry-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid var(--border-color);
            padding: 15px;
            transition: all 0.3s;
        }
        
        .list-group-item:hover {
            background: #f8f9fa;
        }
        
        .list-group-item.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 5px 8px;
        }
        
        .admin-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 30px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #343a40, #495057);
            color: white;
            padding: 20px;
        }
        
        .admin-sidebar {
            background: #f8f9fa;
            padding: 20px;
            height: 100%;
        }
        
        .admin-content {
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .admin-btn {
            display: block;
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 10px;
            text-align: left;
            border: none;
            background: white;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .admin-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .admin-btn i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .inquiry-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .inquiry-table th, .inquiry-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }
        
        .inquiry-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .inquiry-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-select {
            border: none;
            background: transparent;
            font-size: 0.8rem;
            padding: 5px;
            border-radius: 5px;
        }
        
        .status-new {
            background: #ffeaa7;
            color: #e17055;
        }
        
        .status-replied {
            background: #a29bfe;
            color: white;
        }
        
        .status-resolved {
            background: #55efc4;
            color: #00b894;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .contact-container, .admin-panel {
                margin-top: 15px;
            }
            
            .contact-form, .contact-info {
                padding: 20px;
            }
            
            .message-bubble {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    
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

</div> 
        <!-- Previous Inquiries with Chat -->
        <?php if (!empty($user_inquiries)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="mb-4">Your Previous Inquiries</h4>
                
                <div class="row">
                    <div class="col-lg-4">
                        <div class="inquiry-list">
                            <div class="list-group">
                                <?php foreach ($user_inquiries as $inquiry): ?>
                                <a href="?inquiry_id=<?php echo $inquiry['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $selected_inquiry == $inquiry['id'] ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($inquiry['subject']); ?></h6>
                                        <small>
                                            <span class="badge bg-<?php echo $inquiry['status'] == 'new' ? 'danger' : ($inquiry['status'] == 'replied' ? 'success' : 'warning'); ?>">
                                                <?php echo ucfirst($inquiry['status']); ?>
                                            </span>
                                        </small>
                                    </div>
                                    <small><?php echo date('M j, Y g:i A', strtotime($inquiry['created_at'])); ?></small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <?php if ($selected_inquiry): ?>
                        <div class="chat-container">
                            <div class="chat-header">
                                Conversation about: <?php 
                                    $inquiry_subject = '';
                                    foreach ($user_inquiries as $inquiry) {
                                        if ($inquiry['id'] == $selected_inquiry) {
                                            $inquiry_subject = $inquiry['subject'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($inquiry_subject);
                                ?>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <!-- Messages will be loaded via AJAX -->
                            </div>
                            <div class="chat-input">
                                <input type="text" id="messageInput" placeholder="Type your message...">
                                <button class="btn btn-primary" id="sendMessageBtn">Send</button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Select an inquiry to view the conversation</h5>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Admin Panel -->
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
        <div class="admin-panel mt-5">
            <div class="admin-header">
                <h3><i class="fas fa-cog me-2"></i>Admin Panel</h3>
                <p class="mb-0">Manage customer inquiries and support</p>
            </div>
            
            <div class="row">
                <div class="col-lg-3">
                    <div class="admin-sidebar">
                        <button class="admin-btn" id="dashboardBtn">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </button>
                        <button class="admin-btn" id="inquiriesBtn">
                            <i class="fas fa-inbox"></i> All Inquiries
                        </button>
                        <button class="admin-btn" id="newInquiriesBtn">
                            <i class="fas fa-exclamation-circle"></i> New Inquiries
                        </button>
                        <button class="admin-btn" id="repliedInquiriesBtn">
                            <i class="fas fa-reply"></i> Replied Inquiries
                        </button>
                        <button class="admin-btn" id="resolvedInquiriesBtn">
                            <i class="fas fa-check-circle"></i> Resolved Inquiries
                        </button>
                    </div>
                </div>
                
                <div class="col-lg-9">
                    <div class="admin-content">
                        <!-- Dashboard Section -->
                        <div id="dashboardSection">
                            <h4 class="mb-4">Support Dashboard</h4>
                            
                            <div class="row" id="statsContainer">
                                <!-- Stats will be loaded via AJAX -->
                            </div>
                            
                            <div class="mt-4">
                                <h5>Recent Inquiries</h5>
                                <div id="recentInquiries">
                                    <!-- Recent inquiries will be loaded via AJAX -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- All Inquiries Section -->
                        <div id="inquiriesSection" style="display: none;">
                            <h4 class="mb-4">All Customer Inquiries</h4>
                            <div id="allInquiriesTable">
                                <!-- Inquiries table will be loaded via AJAX -->
                            </div>
                        </div>
                        
                        <!-- Chat Section for Admin -->
                        <div id="adminChatSection" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 id="adminChatTitle">Conversation</h4>
                                <button class="btn btn-sm btn-outline-secondary" id="backToInquiries">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Inquiries
                                </button>
                            </div>
                            
                            <div class="chat-container">
                                <div class="chat-messages" id="adminChatMessages">
                                    <!-- Messages will be loaded via AJAX -->
                                </div>
                                <div class="chat-input">
                                    <input type="text" id="adminMessageInput" placeholder="Type your reply...">
                                    <button class="btn btn-primary" id="sendAdminMessageBtn">Send</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Customer Chat Functionality
        <?php if ($selected_inquiry): ?>
        let currentInquiryId = <?php echo $selected_inquiry; ?>;
        
        function loadMessages() {
            fetch(`?ajax=get_inquiry_messages&inquiry_id=${currentInquiryId}`)
                .then(response => response.json())
                .then(data => {
                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.innerHTML = '';
                    
                    if (data.error) {
                        chatMessages.innerHTML = `<div class="text-center text-muted py-3">Error loading messages</div>`;
                        return;
                    }
                    
                    if (data.length === 0) {
                        chatMessages.innerHTML = `<div class="text-center text-muted py-3">No messages yet</div>`;
                        return;
                    }
                    
                    data.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${message.sender_type === 'customer' ? 'customer-message' : 'admin-message'}`;
                        
                        const bubbleDiv = document.createElement('div');
                        bubbleDiv.className = 'message-bubble';
                        bubbleDiv.textContent = message.message;
                        
                        const timeDiv = document.createElement('div');
                        timeDiv.className = 'message-time';
                        timeDiv.textContent = new Date(message.created_at).toLocaleString();
                        
                        bubbleDiv.appendChild(timeDiv);
                        messageDiv.appendChild(bubbleDiv);
                        chatMessages.appendChild(messageDiv);
                    });
                    
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        document.getElementById('sendMessageBtn').addEventListener('click', function() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (message === '') return;
            
            const formData = new FormData();
            formData.append('inquiry_id', currentInquiryId);
            formData.append('message', message);
            
            fetch('?ajax=send_customer_message', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadMessages();
                } else {
                    alert('Error sending message: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
        
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('sendMessageBtn').click();
            }
        });
        
        // Load messages initially and set interval for updates
        loadMessages();
        setInterval(loadMessages, 5000);
        <?php endif; ?>
        
        // Admin Panel Functionality
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
        let adminCurrentInquiryId = null;
        
        // Load dashboard stats
        function loadDashboardStats() {
            fetch('?ajax=get_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('statsContainer').innerHTML = `<div class="col-12"><div class="alert alert-danger">Error loading stats</div></div>`;
                        return;
                    }
                    
                    const statsContainer = document.getElementById('statsContainer');
                    statsContainer.innerHTML = `
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number">${data.total}</div>
                                <div class="stat-label">Total Inquiries</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number">${data.new}</div>
                                <div class="stat-label">New Inquiries</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number">${data.replied}</div>
                                <div class="stat-label">Replied</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number">${data.resolved}</div>
                                <div class="stat-label">Resolved</div>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
                
            // Load recent inquiries
            fetch('?ajax=get_all_inquiries')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('recentInquiries').innerHTML = `<div class="alert alert-danger">Error loading recent inquiries</div>`;
                        return;
                    }
                    
                    const recentInquiries = document.getElementById('recentInquiries');
                    if (data.length === 0) {
                        recentInquiries.innerHTML = `<div class="text-center py-3 text-muted">No inquiries yet</div>`;
                        return;
                    }
                    
                    let html = `
                        <div class="table-responsive">
                            <table class="inquiry-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    // Show only the first 5 inquiries
                    data.slice(0, 5).forEach(inquiry => {
                        html += `
                            <tr>
                                <td>${escapeHtml(inquiry.name)}</td>
                                <td>${escapeHtml(inquiry.subject)}</td>
                                <td>${new Date(inquiry.created_at).toLocaleDateString()}</td>
                                <td>
                                    <select class="status-select status-${inquiry.status}" onchange="updateInquiryStatus(${inquiry.id}, this.value)">
                                        <option value="new" ${inquiry.status === 'new' ? 'selected' : ''}>New</option>
                                        <option value="replied" ${inquiry.status === 'replied' ? 'selected' : ''}>Replied</option>
                                        <option value="resolved" ${inquiry.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="openAdminChat(${inquiry.id}, '${escapeHtml(inquiry.subject)}')">
                                        <i class="fas fa-comment"></i> Chat
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    recentInquiries.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Load all inquiries for the inquiries table
        function loadAllInquiries(filter = 'all') {
            fetch('?ajax=get_all_inquiries')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('allInquiriesTable').innerHTML = `<div class="alert alert-danger">Error loading inquiries</div>`;
                        return;
                    }
                    
                    const allInquiriesTable = document.getElementById('allInquiriesTable');
                    if (data.length === 0) {
                        allInquiriesTable.innerHTML = `<div class="text-center py-3 text-muted">No inquiries yet</div>`;
                        return;
                    }
                    
                    let html = `
                        <div class="table-responsive">
                            <table class="inquiry-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.forEach(inquiry => {
                        // Apply filter
                        if (filter !== 'all' && inquiry.status !== filter) {
                            return;
                        }
                        
                        html += `
                            <tr>
                                <td>${escapeHtml(inquiry.name)}</td>
                                <td>${escapeHtml(inquiry.email)}</td>
                                <td>${escapeHtml(inquiry.subject)}</td>
                                <td>${new Date(inquiry.created_at).toLocaleDateString()}</td>
                                <td>
                                    <select class="status-select status-${inquiry.status}" onchange="updateInquiryStatus(${inquiry.id}, this.value)">
                                        <option value="new" ${inquiry.status === 'new' ? 'selected' : ''}>New</option>
                                        <option value="replied" ${inquiry.status === 'replied' ? 'selected' : ''}>Replied</option>
                                        <option value="resolved" ${inquiry.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="openAdminChat(${inquiry.id}, '${escapeHtml(inquiry.subject)}')">
                                        <i class="fas fa-comment"></i> Chat
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    allInquiriesTable.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Update inquiry status
        function updateInquiryStatus(inquiryId, status) {
            const formData = new FormData();
            formData.append('inquiry_id', inquiryId);
            formData.append('status', status);
            
            fetch('?ajax=update_inquiry_status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error updating status: ' + data.error);
                }
                // Reload the current view
                if (document.getElementById('dashboardSection').style.display !== 'none') {
                    loadDashboardStats();
                } else {
                    loadAllInquiries();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Open admin chat
        function openAdminChat(inquiryId, subject) {
            adminCurrentInquiryId = inquiryId;
            
            // Hide other sections and show chat
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('inquiriesSection').style.display = 'none';
            document.getElementById('adminChatSection').style.display = 'block';
            
            // Set chat title
            document.getElementById('adminChatTitle').textContent = `Conversation: ${subject}`;
            
            // Load messages
            loadAdminMessages();
        }
        
        // Load admin messages
        function loadAdminMessages() {
            if (!adminCurrentInquiryId) return;
            
            fetch(`?ajax=get_inquiry_messages&inquiry_id=${adminCurrentInquiryId}`)
                .then(response => response.json())
                .then(data => {
                    const adminChatMessages = document.getElementById('adminChatMessages');
                    adminChatMessages.innerHTML = '';
                    
                    if (data.error) {
                        adminChatMessages.innerHTML = `<div class="text-center text-muted py-3">Error loading messages</div>`;
                        return;
                    }
                    
                    if (data.length === 0) {
                        adminChatMessages.innerHTML = `<div class="text-center text-muted py-3">No messages yet</div>`;
                        return;
                    }
                    
                    data.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${message.sender_type === 'customer' ? 'customer-message' : 'admin-message'}`;
                        
                        const bubbleDiv = document.createElement('div');
                        bubbleDiv.className = 'message-bubble';
                        bubbleDiv.textContent = message.message;
                        
                        const timeDiv = document.createElement('div');
                        timeDiv.className = 'message-time';
                        timeDiv.textContent = new Date(message.created_at).toLocaleString();
                        
                        bubbleDiv.appendChild(timeDiv);
                        messageDiv.appendChild(bubbleDiv);
                        adminChatMessages.appendChild(messageDiv);
                    });
                    
                    adminChatMessages.scrollTop = adminChatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Send admin message
        document.getElementById('sendAdminMessageBtn').addEventListener('click', function() {
            if (!adminCurrentInquiryId) return;
            
            const messageInput = document.getElementById('adminMessageInput');
            const message = messageInput.value.trim();
            
            if (message === '') return;
            
            const formData = new FormData();
            formData.append('inquiry_id', adminCurrentInquiryId);
            formData.append('message', message);
            
            fetch('?ajax=send_admin_message', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadAdminMessages();
                    
                    // Update the status in the background if needed
                    if (document.getElementById('dashboardSection').style.display !== 'none') {
                        loadDashboardStats();
                    } else {
                        loadAllInquiries();
                    }
                } else {
                    alert('Error sending message: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
        
        document.getElementById('adminMessageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('sendAdminMessageBtn').click();
            }
        });
        
        // Admin navigation
        document.getElementById('dashboardBtn').addEventListener('click', function() {
            document.getElementById('dashboardSection').style.display = 'block';
            document.getElementById('inquiriesSection').style.display = 'none';
            document.getElementById('adminChatSection').style.display = 'none';
            loadDashboardStats();
        });
        
        document.getElementById('inquiriesBtn').addEventListener('click', function() {
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('inquiriesSection').style.display = 'block';
            document.getElementById('adminChatSection').style.display = 'none';
            loadAllInquiries('all');
        });
        
        document.getElementById('newInquiriesBtn').addEventListener('click', function() {
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('inquiriesSection').style.display = 'block';
            document.getElementById('adminChatSection').style.display = 'none';
            loadAllInquiries('new');
        });
        
        document.getElementById('repliedInquiriesBtn').addEventListener('click', function() {
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('inquiriesSection').style.display = 'block';
            document.getElementById('adminChatSection').style.display = 'none';
            loadAllInquiries('replied');
        });
        
        document.getElementById('resolvedInquiriesBtn').addEventListener('click', function() {
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('inquiriesSection').style.display = 'block';
            document.getElementById('adminChatSection').style.display = 'none';
            loadAllInquiries('resolved');
        });
        
        document.getElementById('backToInquiries').addEventListener('click', function() {
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('inquiriesSection').style.display = 'block';
            document.getElementById('adminChatSection').style.display = 'none';
            loadAllInquiries();
        });
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initialize admin panel
        loadDashboardStats();
        <?php endif; ?>
    </script>
</body>
</html>