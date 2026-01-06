<?php
// Database configuration and PHP functions
$host = '127.0.0.1';
$dbname = 'velvet_vogue';
$username = 'root'; // Change as per your setup
$password = ''; // Change as per your setup

// Initialize variables
$dashboard_data = [];
$error = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE payment_status = 'paid'");
    $dashboard_data['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

    // Get total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $dashboard_data['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

    // Get total products
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $dashboard_data['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    // Get total customers
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM users WHERE user_type = 'customer'");
    $dashboard_data['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

    // Get pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
    $dashboard_data['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];

    // Get low stock products
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity < 10");
    $dashboard_data['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];

    // Get new inquiries
    $stmt = $pdo->query("SELECT COUNT(*) as new_inquiries FROM inquiries WHERE status = 'new'");
    $dashboard_data['new_inquiries'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_inquiries'];

    // Get recent inquiries
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 8");
    $dashboard_data['recent_inquiries'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top products
    $stmt = $pdo->query("
        SELECT p.name, p.price, p.stock_quantity, c.name as category 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC 
        LIMIT 6
    ");
    $dashboard_data['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = $e->getMessage();
}

// Function to format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Function to format date
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

// Function to get status badge class
function getStatusBadge($status) {
    switch($status) {
        case 'new': return 'status-new';
        case 'read': return 'status-read';
        case 'replied': return 'status-replied';
        case 'pending': return 'status-pending';
        case 'paid': return 'status-paid';
        default: return 'status-default';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velvet Vogue - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #490437ff;
            --primary-dark: #4f043cff;
            --secondary: #ff4081;
            --light: #f5f5f5;
            --dark: #333;
            --gray: #777;
            --light-gray: #e0e0e0;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --info: #2196f3;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: white;
            transition: all 0.3s;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        /* KPI Cards */
        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
            border-left: 4px solid var(--primary);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
        }

        .kpi-card.revenue {
            border-left-color: var(--success);
        }

        .kpi-card.orders {
            border-left-color: var(--info);
        }

        .kpi-card.products {
            border-left-color: var(--primary);
        }

        .kpi-card.customers {
            border-left-color: var(--secondary);
        }

        .kpi-card.pending {
            border-left-color: var(--warning);
        }

        .kpi-card.stock {
            border-left-color: var(--danger);
        }

        .kpi-card.inquiries {
            border-left-color: var(--info);
        }

        .kpi-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .kpi-card-header i {
            font-size: 1.5rem;
        }

        .kpi-card.revenue .kpi-card-header i { color: var(--success); }
        .kpi-card.orders .kpi-card-header i { color: var(--info); }
        .kpi-card.products .kpi-card-header i { color: var(--primary); }
        .kpi-card.customers .kpi-card-header i { color: var(--secondary); }
        .kpi-card.pending .kpi-card-header i { color: var(--warning); }
        .kpi-card.stock .kpi-card-header i { color: var(--danger); }
        .kpi-card.inquiries .kpi-card-header i { color: var(--info); }

        .kpi-card-body h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .kpi-card-body p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .kpi-card-footer {
            display: flex;
            align-items: center;
            margin-top: 10px;
            font-size: 0.8rem;
        }

        .trend-up {
            color: var(--success);
        }

        .trend-down {
            color: var(--danger);
        }

        /* Content Sections */
        .content-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .content-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
        }

        .content-header h3 {
            font-size: 1.2rem;
            color: var(--primary);
        }

        .content-header a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        /* Activity List */
        .activity-list {
            margin-top: 15px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            font-size: 1rem;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-content p {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .status-new { background: var(--info); color: white; }
        .status-read { background: var(--light-gray); color: var(--dark); }
        .status-replied { background: var(--success); color: white; }
        .status-pending { background: var(--warning); color: white; }
        .status-paid { background: var(--success); color: white; }
        .status-default { background: var(--gray); color: white; }

        /* Product List */
        .product-list {
            margin-top: 15px;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-info {
            flex: 1;
        }

        .product-info h4 {
            font-size: 0.9rem;
            margin-bottom: 3px;
        }

        .product-info p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .product-price {
            font-weight: bold;
            color: var(--primary);
        }

        .stock-info {
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 10px;
            background: var(--light);
        }

        .stock-low {
            background: #ffebee;
            color: var(--danger);
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: var(--gray);
        }

        .error {
            background-color: #ffebee;
            color: var(--danger);
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .last-updated {
            text-align: right;
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 10px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-sections {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2, .sidebar-menu span {
                display: none;
            }
            .sidebar-menu a {
                justify-content: center;
                padding: 15px 0;
            }
            .sidebar-menu i {
                margin-right: 0;
            }
            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 576px) {
            .kpi-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Velvet Vogue</h2>
            <p>Admin Dashboard</p>
        </div>
        <!-- Updated on 2026-01-06: Minor UI improvement for admin dashboard -->

        <div class="sidebar-menu">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="./Admin products_Management.php"><i class="fas fa-shopping-bag"></i> <span>Products</span></a></li>
                <li><a href="Admin Customer_Management.php"><i class="fas fa-users"></i> <span>Customers</span></a></li>
                <li><a href="./Admin orders_Management.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
                <li><a href="./Admin.php"><i class="fas fa-comments"></i> <span>Inquiries</span></a></li>
                <li><a href="./Admin Settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h4>Welcome back Akeeth!</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <?php if(!empty($error)): ?>
            <div class="error">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card revenue">
                <div class="kpi-card-header">
                    <h3>Total Revenue</h3>
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="total-revenue"><?php echo formatCurrency($dashboard_data['total_revenue'] ?? 0); ?></h3>
                    <p>All Time Revenue</p>
                </div>
                <div class="kpi-card-footer trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Live from Database</span>
                </div>
            </div>

            <div class="kpi-card orders">
                <div class="kpi-card-header">
                    <h3>Total Orders</h3>
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="total-orders"><?php echo $dashboard_data['total_orders'] ?? 0; ?></h3>
                    <p>Completed Orders</p>
                </div>
                <div class="kpi-card-footer trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Live from Database</span>
                </div>
            </div>

            <div class="kpi-card products">
                <div class="kpi-card-header">
                    <h3>Total Products</h3>
                    <i class="fas fa-box"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="total-products"><?php echo $dashboard_data['total_products'] ?? 0; ?></h3>
                    <p>Available Products</p>
                </div>
                <div class="kpi-card-footer trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Live from Database</span>
                </div>
            </div>

            <div class="kpi-card customers">
                <div class="kpi-card-header">
                    <h3>Total Customers</h3>
                    <i class="fas fa-users"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="total-customers"><?php echo $dashboard_data['total_customers'] ?? 0; ?></h3>
                    <p>Registered Customers</p>
                </div>
                <div class="kpi-card-footer trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Live from Database</span>
                </div>
            </div>

            <div class="kpi-card pending">
                <div class="kpi-card-header">
                    <h3>Pending Orders</h3>
                    <i class="fas fa-clock"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="pending-orders"><?php echo $dashboard_data['pending_orders'] ?? 0; ?></h3>
                    <p>Awaiting Processing</p>
                </div>
                <div class="kpi-card-footer trend-up">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Requires Attention</span>
                </div>
            </div>

            <div class="kpi-card stock">
                <div class="kpi-card-header">
                    <h3>Low Stock Items</h3>
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="low-stock"><?php echo $dashboard_data['low_stock'] ?? 0; ?></h3>
                    <p>Products Need Restocking</p>
                </div>
                <div class="kpi-card-footer trend-down">
                    <i class="fas fa-arrow-down"></i>
                    <span>Needs Attention</span>
                </div>
            </div>

            <div class="kpi-card inquiries">
                <div class="kpi-card-header">
                    <h3>New Inquiries</h3>
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="kpi-card-body">
                    <h3 id="new-inquiries"><?php echo $dashboard_data['new_inquiries'] ?? 0; ?></h3>
                    <p>Unread Customer Messages</p>
                </div>
                <div class="kpi-card-footer trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Requires Response</span>
                </div>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="content-sections">
            <!-- Recent Inquiries -->
            <div class="content-card">
                <div class="content-header">
                    <h3>Recent Inquiries</h3>
                    <a href="#">View All</a>
                </div>
                <div class="activity-list" id="recent-inquiries">
                    <?php if(empty($dashboard_data['recent_inquiries'])): ?>
                        <div class="loading">No recent inquiries</div>
                    <?php else: ?>
                        <?php foreach($dashboard_data['recent_inquiries'] as $inquiry): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="activity-content">
                                    <h4>
                                        <?php echo htmlspecialchars($inquiry['name']); ?>
                                        <span class="status-badge <?php echo getStatusBadge($inquiry['status']); ?>">
                                            <?php echo ucfirst($inquiry['status']); ?>
                                        </span>
                                    </h4>
                                    <p><strong><?php echo htmlspecialchars($inquiry['subject']); ?></strong></p>
                                    <p><?php echo htmlspecialchars($inquiry['message']); ?></p>
                                    <p class="last-updated"><?php echo formatDate($inquiry['created_at']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Products -->
            <div class="content-card">
                <div class="content-header">
                    <h3>Latest Products</h3>
                    <a href="#">View All</a>
                </div>
                <div class="product-list" id="top-products">
                    <?php if(empty($dashboard_data['top_products'])): ?>
                        <div class="loading">No products found</div>
                    <?php else: ?>
                        <?php foreach($dashboard_data['top_products'] as $product): ?>
                            <div class="product-item">
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></p>
                                </div>
                                <div class="product-meta">
                                    <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                                    <div class="stock-info <?php echo ($product['stock_quantity'] < 10) ? 'stock-low' : ''; ?>">
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="last-updated">
            Last updated: <?php echo date('M j, Y g:i A'); ?>
        </div>
    </div>

    <script>
        // Auto-refresh functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Set up auto-refresh every 60 seconds
            setInterval(refreshData, 60000);
        });

        // Function to refresh data (simulated - in real implementation, this would be an AJAX call)
        function refreshData() {
            // Show loading state
            const kpiCards = document.querySelectorAll('.kpi-card-footer span');
            kpiCards.forEach(card => {
                const originalText = card.textContent;
                card.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
                
                // Restore original text after 2 seconds if refresh fails
                setTimeout(() => {
                    card.innerHTML = originalText;
                }, 2000);
            });

            // In a real implementation, this would be an AJAX call
            // For now, we'll just reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }

        // Add hover effects and interactions
        document.querySelectorAll('.kpi-card').forEach(card => {
            card.addEventListener('click', function() {
                const title = this.querySelector('h3').textContent;
                alert(`You clicked on ${title}. This would navigate to detailed view in a full implementation.`);
            });
        });
    </script>
</body>
</html>