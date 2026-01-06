<?php
// track_product.php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'product_tracking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get tracking code from URL or form submission
$tracking_code = '';
$order = null;
$status_stages = [
    'processing' => ['label' => 'Order Processing', 'desc' => 'Your order is being verified and prepared'],
    'packed' => ['label' => 'Packed', 'desc' => 'Your item has been packed and is ready for shipment'],
    'shipped' => ['label' => 'Shipped', 'desc' => 'Your item has been shipped and is on its way'],
    'out_for_delivery' => ['label' => 'Out for Delivery', 'desc' => 'Your item is out for delivery today'],
    'delivered' => ['label' => 'Delivered', 'desc' => 'Your item has been delivered']
];

if (isset($_GET['tracking_code']) || isset($_POST['tracking_code'])) {
    $tracking_code = isset($_GET['tracking_code']) ? $_GET['tracking_code'] : $_POST['tracking_code'];
    
    // Fetch order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_code = ?");
    $stmt->execute([$tracking_code]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Product</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 18px;
        }
        
        .tracking-form {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #4CAF50, #45a049);
            color: white;
            padding: 14px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
        }
        
        .admin-login-btn {
            background: linear-gradient(to right, #3498db, #2980b9);
            margin-left: 10px;
        }
        
        .admin-login-btn:hover {
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .tracking-result {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            display: <?php echo $order ? 'block' : 'none'; ?>;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .info-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .tracking-timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-line {
            position: absolute;
            top: 50px;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .timeline-progress {
            position: absolute;
            top: 50px;
            left: 0;
            height: 4px;
            background-color: #4CAF50;
            z-index: 2;
            transition: width 0.5s ease;
        }
        
        .timeline-stages {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }
        
        .stage {
            text-align: center;
            width: 20%;
        }
        
        .stage-icon {
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 4px solid #e0e0e0;
            font-weight: bold;
            color: #7f8c8d;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .stage.active .stage-icon {
            border-color: #4CAF50;
            background-color: #4CAF50;
            color: white;
        }
        
        .stage.completed .stage-icon {
            border-color: #4CAF50;
            background-color: #4CAF50;
            color: white;
        }
        
        .stage-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stage-desc {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .no-order {
            text-align: center;
            padding: 40px;
            color: #e74c3c;
            font-size: 18px;
        }
        
        .admin-link {
            text-align: center;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .timeline-stages {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stage {
                width: 100%;
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                text-align: left;
            }
            
            .stage-icon {
                margin: 0 15px 0 0;
                flex-shrink: 0;
            }
            
            .timeline-line, .timeline-progress {
                top: 0;
                left: 25px;
                width: 4px;
                height: 100%;
            }
        }
    </style>
</head>
<body>

<div class="page-container">
      <div class="right-container">
        <!-- Top Navigation -->
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
        </div>

           
          </div>
        </div>
    <div class="container">
        <header>
            <h1>Track Your Product</h1>
            <p class="subtitle">Enter your tracking code to check the delivery status</p>
        </header>
        
        <div class="tracking-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="tracking_code">Tracking Code</label>
                    <input type="text" id="tracking_code" name="tracking_code" 
                           placeholder="Enter your tracking code (e.g. TRK123456)" 
                           value="<?php echo htmlspecialchars($tracking_code); ?>" required>
                </div>
                <button type="submit" class="btn">Track Order</button>
                
            </form>
        </div>
        
        <?php if ($tracking_code): ?>
        <div class="tracking-result">
            <?php if ($order): ?>
                <div class="order-info">
                    <div class="info-item">
                        <div class="info-label">Order ID</div>
                        <div class="info-value">#<?php echo $order['order_id']; ?></div>
                    </div>
                   
                    <div class="info-item">
                        <div class="info-label">Tracking Code</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['tracking_code']); ?></div>
                    </div>
                </div>
                
                <div class="tracking-timeline">
                    <div class="timeline-line"></div>
                    <?php
                    // Calculate progress width based on status
                    $status_order = array_keys($status_stages);
                    $current_status_index = array_search($order['status'], $status_order);
                    $progress_width = ($current_status_index + 1) * 20;
                    ?>
                    <div class="timeline-progress" style="width: <?php echo $progress_width; ?>%"></div>
                    
                    <div class="timeline-stages">
                        <?php 
                        $stage_index = 0;
                        foreach ($status_stages as $key => $stage): 
                            $is_completed = array_search($key, $status_order) <= $current_status_index;
                            $is_active = $key === $order['status'];
                        ?>
                        <div class="stage <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>">
                            <div class="stage-icon"><?php echo $stage_index + 1; ?></div>
                            <div class="stage-content">
                                <div class="stage-label"><?php echo $stage['label']; ?></div>
                                <div class="stage-desc"><?php echo $stage['desc']; ?></div>
                            </div>
                        </div>
                        <?php 
                        $stage_index++;
                        endforeach; 
                        ?>
                    </div>
                </div>
                
                <div class="current-status">
                    <h2>Current Status: <span style="color: #4CAF50;"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></h2>
                    <p><?php echo $status_stages[$order['status']]['desc']; ?></p>
                </div>
                
            <?php else: ?>
                <div class="no-order">
                    <h2>No order found with tracking code: <?php echo htmlspecialchars($tracking_code); ?></h2>
                    <p>Please check your tracking code and try again.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-link">
            <p>Are you an admin? <a href="admin_login.php">Click here to login</a></p>
        </div>
    </div>
</body>
</html>