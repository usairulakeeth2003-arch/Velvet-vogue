<?php
require_once 'config.php';

// Ensure $pdo is available
global $pdo;

// Handle actions
$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? 0;
$message = '';

// Update user status
if ($action === 'update_status' && $user_id) {
    $new_status = $_GET['status'] ?? '';
    
    if (in_array($new_status, ['active', 'suspended', 'deactivated'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            if ($new_status === 'active') {
                $message = "User activated successfully!";
            } elseif ($new_status === 'suspended') {
                $message = "User suspended successfully!";
            } else {
                $message = "User deactivated successfully!";
            }
        } catch (PDOException $e) {
            $message = "Error updating user status: " . $e->getMessage();
        }
    }
}

// Delete user (with confirmation)
if ($action === 'delete' && $user_id) {
    // Check if user has orders before deletion
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $order_count = $stmt->fetch()['order_count'];
        
        if ($order_count > 0) {
            $message = "Cannot delete user with existing orders. Please deactivate instead.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'customer'");
            $stmt->execute([$user_id]);
            $message = "User deleted successfully!";
        }
    } catch (PDOException $e) {
        $message = "Error deleting user: " . $e->getMessage();
    }
}

// Get all customers with their order counts and recent activity
try {
    $customers = $pdo->query("
        SELECT 
            u.*,
            COUNT(o.id) as order_count,
            MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.user_type = 'customer'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $customers = [];
    error_log("Customers query error: " . $e->getMessage());
}

// Get statistics
try {
    $total_customers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'")->fetch()['count'];
    $active_customers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND account_status = 'active'")->fetch()['count'];
    $suspended_customers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND account_status = 'suspended'")->fetch()['count'];
    $deactivated_customers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND account_status = 'deactivated'")->fetch()['count'];
} catch (PDOException $e) {
    $total_customers = $active_customers = $suspended_customers = $deactivated_customers = 0;
    error_log("Statistics query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Velvet Vogue</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0a71a9ff;
            --primary-dark: #5e35b1;
            --secondary: #ff4081;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #a6170dff;
            --info: #0a71a9ff;
            --light: #f5f5f5;
            --dark: #333;
            --gray: #777;
            --light-gray: #e0e0e0;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f9f9f9;
            min-height: 100vh;
        }
        
        .admin-header {
            background: white;
            box-shadow: var(--card-shadow);
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .admin-header .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            color: var(--primary);
            margin: 0;
            font-size: 1.5rem;
        }
        
        .admin-header nav a {
            margin-left: 20px;
            text-decoration: none;
            color: var(--dark);
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .admin-header nav a:hover {
            background: var(--light);
            color: var(--primary);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #c3e6cb;
            font-weight: 500;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #f5c6cb;
            font-weight: 500;
        }
        
        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card.total { border-left-color: var(--primary); }
        .stat-card.active { border-left-color: var(--success); }
        .stat-card.suspended { border-left-color: var(--warning); }
        .stat-card.deactivated { border-left-color: var(--danger); }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.total i { color: var(--primary); }
        .stat-card.active i { color: var(--success); }
        .stat-card.suspended i { color: var(--warning); }
        .stat-card.deactivated i { color: var(--danger); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Customer Table */
        .customers-section {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 40px;
        }
        
        .section-header {
            padding: 20px 25px;
            background: var(--primary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 14px;
        }
        
        .customers-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .customers-table th {
            background: var(--light);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 1px solid var(--light-gray);
        }
        
        .customers-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }
        
        .customers-table tr:hover {
            background: #f8f9fa;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .customer-info h4 {
            margin: 0 0 5px 0;
            color: var(--dark);
        }
        
        .customer-info p {
            margin: 0;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-active {
            background: #e8f5e8;
            color: var(--success);
        }
        
        .status-suspended {
            background: #fff3e0;
            color: var(--warning);
        }
        
        .status-deactivated {
            background: #ffebee;
            color: var(--danger);
        }
        
        .order-count {
            background: var(--light);
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .last-activity {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 0.75rem;
        }
        
        .btn-preview {
            background: var(--info);
            color: white;
        }
        
        .btn-preview:hover {
            background: #1976d2;
            transform: translateY(-1px);
        }
        
        .btn-activate {
            background: var(--success);
            color: white;
        }
        
        .btn-activate:hover {
            background: #45a049;
            transform: translateY(-1px);
        }
        
        .btn-suspend {
            background: var(--warning);
            color: white;
        }
        
        .btn-suspend:hover {
            background: #f57c00;
            transform: translateY(-1px);
        }
        
        .btn-deactivate {
            background: var(--danger);
            color: white;
        }
        
        .btn-deactivate:hover {
            background: #d32f2f;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: #6c757d;
            color: white;
        }
        
        .btn-delete:hover {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .admin-header .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .admin-header nav {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .admin-header nav a {
                margin-left: 0;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .search-box {
                width: 100%;
            }
            
            .customers-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
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

    <div class="container">
        <?php if (isset($message) && $message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card total">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            
            <div class="stat-card active">
                <i class="fas fa-user-check"></i>
                <div class="stat-number"><?php echo $active_customers; ?></div>
                <div class="stat-label">Active Customers</div>
            </div>
            
            <div class="stat-card suspended">
                <i class="fas fa-user-clock"></i>
                <div class="stat-number"><?php echo $suspended_customers; ?></div>
                <div class="stat-label">Suspended</div>
            </div>
            
            <div class="stat-card deactivated">
                <i class="fas fa-user-slash"></i>
                <div class="stat-number"><?php echo $deactivated_customers; ?></div>
                <div class="stat-label">Deactivated</div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="customers-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Customer List</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search customers...">
                    <button class="btn btn-preview" onclick="searchCustomers()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <?php if (empty($customers)): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Customers Found</h3>
                    <p>Customer accounts will appear here once they register.</p>
                </div>
            <?php else: ?>
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Last Activity</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div class="customer-avatar">
                                            <?php echo strtoupper(substr($customer['first_name'] ?? 'U', 0, 1) . substr($customer['last_name'] ?? 'S', 0, 1)); ?>
                                        </div>
                                        <div class="customer-info">
                                            <h4><?php echo htmlspecialchars($customer['first_name'] ?? 'Unknown') . ' ' . htmlspecialchars($customer['last_name'] ?? 'User'); ?></h4>
                                            <p><?php echo htmlspecialchars($customer['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = 'status-active';
                                    $status_text = 'Active';
                                    $status_icon = 'fa-check-circle';
                                    
                                    if ($customer['account_status'] === 'suspended') {
                                        $status_class = 'status-suspended';
                                        $status_text = 'Suspended';
                                        $status_icon = 'fa-clock';
                                    } elseif ($customer['account_status'] === 'deactivated') {
                                        $status_class = 'status-deactivated';
                                        $status_text = 'Deactivated';
                                        $status_icon = 'fa-ban';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="order-count">
                                        <i class="fas fa-shopping-bag"></i>
                                        <?php echo $customer['order_count'] ?? 0; ?> orders
                                    </span>
                                </td>
                                <td>
                                    <span class="last-activity">
                                        <?php 
                                        if ($customer['last_order_date']) {
                                            echo date('M j, Y', strtotime($customer['last_order_date']));
                                        } else {
                                            echo 'No orders yet';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="last-activity">
                                        <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-preview btn-sm" onclick="previewCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                        
                                        <?php if ($customer['account_status'] !== 'active'): ?>
                                            <a href="?action=update_status&user_id=<?php echo $customer['id']; ?>&status=active" 
                                               class="btn btn-activate btn-sm"
                                               onclick="return confirm('Activate this customer? They will be able to make purchases again.')">
                                                <i class="fas fa-play"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($customer['account_status'] !== 'suspended'): ?>
                                            <a href="?action=update_status&user_id=<?php echo $customer['id']; ?>&status=suspended" 
                                               class="btn btn-suspend btn-sm"
                                               onclick="return confirm('Suspend this customer? They will not be able to make purchases until activated.')">
                                                <i class="fas fa-pause"></i> Suspend
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($customer['account_status'] !== 'deactivated'): ?>
                                            <a href="?action=update_status&user_id=<?php echo $customer['id']; ?>&status=deactivated" 
                                               class="btn btn-deactivate btn-sm"
                                               onclick="return confirm('Deactivate this customer? They will not be able to login or make purchases.')">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (($customer['order_count'] ?? 0) === 0): ?>
                                            <a href="?action=delete&user_id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-delete btn-sm"
                                               onclick="return confirm('Permanently delete this customer? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Customer Preview Modal -->
        <div id="customerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: var(--primary);"><i class="fas fa-user"></i> Customer Details</h3>
                    <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--gray);">&times;</button>
                </div>
                <div id="customerDetails">
                    <!-- Customer details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        function searchCustomers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.customers-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Preview customer details
        function previewCustomer(userId) {
            // In a real implementation, this would fetch customer details via AJAX
            // For now, we'll show a mockup
            const customerDetails = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 15px;">
                        <?php echo strtoupper(substr($customer['first_name'] ?? 'U', 0, 1) . substr($customer['last_name'] ?? 'S', 0, 1)); ?>
                    </div>
                    <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($customer['first_name'] ?? 'Unknown') . ' ' . htmlspecialchars($customer['last_name'] ?? 'User'); ?></h4>
                    <p style="color: var(--gray); margin: 0;"><?php echo htmlspecialchars($customer['email']); ?></p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div style="background: var(--light); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary);"><?php echo $customer['order_count'] ?? 0; ?></div>
                        <div style="font-size: 0.9rem; color: var(--gray);">Total Orders</div>
                    </div>
                    <div style="background: var(--light); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--success);">$0.00</div>
                        <div style="font-size: 0.9rem; color: var(--gray);">Total Spent</div>
                    </div>
                </div>
                
                <div style="background: var(--light); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h5 style="margin: 0 0 10px 0; color: var(--dark);"><i class="fas fa-info-circle"></i> Account Information</h5>
                    <div style="display: grid; gap: 8px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--gray);">Status:</span>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--gray);">Registered:</span>
                            <span><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--gray);">Last Order:</span>
                            <span><?php echo $customer['last_order_date'] ? date('M j, Y', strtotime($customer['last_order_date'])) : 'No orders'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button class="btn btn-activate" style="margin-right: 10px;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="btn btn-preview">
                        <i class="fas fa-shopping-cart"></i> View Orders
                    </button>
                </div>
            `;
            
            document.getElementById('customerDetails').innerHTML = customerDetails;
            document.getElementById('customerModal').style.display = 'flex';
        }

        // Close modal
        function closeModal() {
            document.getElementById('customerModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('customerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Auto-hide success message after 5 seconds
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s';
                setTimeout(() => successMessage.remove(), 500);
            }, 5000);
        }

        // Enable search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
    </script>
</body>
</html>