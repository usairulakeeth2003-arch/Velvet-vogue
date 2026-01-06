<?php
// admin_dashboard.php
session_start();

// --- ADMIN AUTH CHECK ---
// Ensure admin_login.php sets $_SESSION['admin_logged_in'] = true and $_SESSION['admin_username'].
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// --- Database connection ---
$host = 'localhost';
$dbname = 'product_tracking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch(PDOException $e) {
    // In production, log error instead of echoing.
    die("Connection failed: " . $e->getMessage());
}

// --- Allowed statuses and labels (single source of truth) ---
$status_stages = [
    'processing'      => 'Order Processing',
    'packed'          => 'Packed',
    'shipped'         => 'Shipped',
    'out_for_delivery'=> 'Out for Delivery',
    'delivered'       => 'Delivered'
];

// --- Flash messages helper ---
function set_flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}
function get_flash($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

// --- Handle status update (POST) with validation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if (!$order_id || !$new_status || !array_key_exists($new_status, $status_stages)) {
        set_flash('error', 'Invalid order ID or status.');
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        set_flash('success', 'Order status updated successfully.');
    }

    // Post/Redirect/Get to avoid duplicate submissions
    header('Location: admin_dashboard.php' . (isset($_GET['search']) ? ('?search=' . urlencode($_GET['search'])) : ''));
    exit();
}

// --- Handle search or fetch all orders ---
$search_query = '';
if (isset($_GET['search']) && strlen(trim($_GET['search'])) > 0) {
    $search_query = trim($_GET['search']);
    $search_term = "%$search_query%";
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_code LIKE ? OR customer_name LIKE ? OR product_name LIKE ? ORDER BY order_date DESC");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- compute counts safely ---
$total_orders = count($orders);
$delivered_count = 0;
$processing_count = 0;
$in_transit_count = 0;

foreach ($orders as $order) {
    $s = $order['status'] ?? '';
    if ($s === 'delivered') $delivered_count++;
    if ($s === 'processing') $processing_count++;
    if (in_array($s, ['packed','shipped','out_for_delivery'])) $in_transit_count++;
}

$success_message = get_flash('success');
$error_message = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Dashboard - Product Tracking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* (Same styles as provided earlier; trimmed for brevity) */
    /* You can paste your full CSS here; I kept it concise in this example */
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f7fa; color:#333; }
    .header { background: linear-gradient(135deg,#2c3e50,#4a6491); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; }
    .container { max-width:1200px; margin:20px auto; padding:20px; }
    .success-message{background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:16px;}
    .error-message{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:16px;}
    table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,0.06);}
    th{background:#34495e;color:#fff;padding:12px;text-align:left}
    td{padding:12px;border-bottom:1px solid #eee}
    .update-form{display:flex;gap:8px;align-items:center}
    .status-select{padding:8px;border-radius:6px;border:1px solid #ddd}
    .update-btn{padding:8px 12px;border-radius:6px;background:#2ecc71;color:#fff;border:none;cursor:pointer}
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-boxes"></i> Orders management </h1>
        <div class="admin-info">
            <span style="font-weight:600"><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            <a href="logout.php" style="margin-left:16px;padding:8px 12px;background:#e74c3c;color:#fff;border-radius:6px;text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
            <div><i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a'); ?></div>
        </div>

        <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
            <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);min-width:140px;text-align:center;">
                <div style="font-size:28px;font-weight:700"><?php echo $total_orders; ?></div>
                <div style="font-weight:600;color:#7f8c8d;text-transform:uppercase;font-size:12px">Total Orders</div>
            </div>
            <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);min-width:140px;text-align:center;">
                <div style="font-size:28px;font-weight:700"><?php echo $delivered_count; ?></div>
                <div style="font-weight:600;color:#7f8c8d;text-transform:uppercase;font-size:12px">Delivered</div>
            </div>
            <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);min-width:140px;text-align:center;">
                <div style="font-size:28px;font-weight:700"><?php echo $processing_count; ?></div>
                <div style="font-weight:600;color:#7f8c8d;text-transform:uppercase;font-size:12px">Processing</div>
            </div>
            <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);min-width:140px;text-align:center;">
                <div style="font-size:28px;font-weight:700"><?php echo $in_transit_count; ?></div>
                <div style="font-weight:600;color:#7f8c8d;text-transform:uppercase;font-size:12px">In Transit</div>
            </div>
        </div>

        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 8px 25px rgba(0,0,0,0.06);margin-bottom:20px;">
            <form method="GET" style="display:flex;gap:8px;">
                <input type="text" name="search" placeholder="Search by tracking code, customer or product..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ddd">
                <button type="submit" style="padding:10px 14px;border-radius:6px;background:#3498db;color:#fff;border:none;cursor:pointer;"><i class="fas fa-search"></i> Search</button>
                <?php if ($search_query): ?>
                    <a href="admin_dashboard.php" style="padding:10px 14px;border-radius:6px;background:#95a5a6;color:#fff;text-decoration:none;margin-left:8px;"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div>
            <?php if (count($orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th># Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Tracking Code</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $order_id = (int)$order['order_id'];
                            $status = $order['status'] ?? 'processing';
                            $safe_status_key = htmlspecialchars($status);
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order_id; ?></strong></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3498db,#2c3e50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
                                        <?php echo strtoupper(substr($order['customer_name'],0,1) ?: 'U'); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <div style="color:#7f8c8d;font-size:13px"><?php echo htmlspecialchars($order['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:36px;height:36px;border-radius:8px;background:#e8f4fc;display:flex;align-items:center;justify-content:center;color:#3498db">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div style="font-weight:600"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                </div>
                            </td>
                            <td><code style="background:#f8f9fa;padding:6px;border-radius:6px;border:1px dashed #ddd;display:inline-block"><?php echo htmlspecialchars($order['tracking_code']); ?></code></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($order['order_date'] ?? date('Y-m-d H:i:s')))); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo preg_replace('/[^a-z0-9_]/','', $safe_status_key); ?>" style="display:inline-block;padding:6px 10px;border-radius:16px;border:1px solid #ddd;font-weight:700;text-transform:uppercase;font-size:12px;">
                                    <?php echo htmlspecialchars($status_stages[$status] ?? ucfirst(str_replace('_',' ',$status))); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="update-form" onsubmit="return confirm('Update order status?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <select name="status" class="status-select" required>
                                        <option value="">Select status</option>
                                        <?php foreach ($status_stages as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" <?php echo ($order['status'] == $key) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="update-btn"><i class="fas fa-sync-alt"></i> Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding:40px;text-align:center;color:#7f8c8d;background:#fff;border-radius:8px;box-shadow:0 8px 25px rgba(0,0,0,0.06)">
                    <i class="fas fa-clipboard" style="font-size:48px;margin-bottom:8px"></i>
                    <h3>No Orders Found</h3>
                    <p>No orders match your search criteria.</p>
                    <?php if ($search_query): ?>
                        <a href="admin_dashboard.php" style="display:inline-block;padding:10px 14px;background:#3498db;color:#fff;border-radius:6px;text-decoration:none;margin-top:12px;"><i class="fas fa-list"></i> View All Orders</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:20px;">
            <a href="track_product.php" style="text-decoration:none;color:#3498db"><i class="fas fa-arrow-left"></i> Back to Public Tracking Page</a>
        </div>
    </div>
</body>
</html>
