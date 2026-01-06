<?php
// track_product.php (public tracking page)
session_start();

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
    die("Connection failed: " . $e->getMessage());
}

// Allowed statuses and descriptions
$status_stages = [
    'processing'       => ['label' => 'Order Processing', 'desc' => 'Your order is being verified and prepared.'],
    'packed'           => ['label' => 'Packed', 'desc' => 'Your item has been packed and is ready for shipment.'],
    'shipped'          => ['label' => 'Shipped', 'desc' => 'Your item has been shipped and is on its way.'],
    'out_for_delivery' => ['label' => 'Out for Delivery', 'desc' => 'Your item is out for delivery today.'],
    'delivered'        => ['label' => 'Delivered', 'desc' => 'Your item has been delivered.']
];

$tracking_code = '';
$order = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_code'])) {
    $tracking_code = trim($_POST['tracking_code']);
    if ($tracking_code === '') {
        $error = 'Please enter your tracking code.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_code = ?");
        $stmt->execute([$tracking_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $error = 'No order found with that tracking code.';
        }
    }
} elseif (isset($_GET['tracking_code'])) {
    // Support direct links: track_product.php?tracking_code=TRK...
    $tracking_code = trim($_GET['tracking_code']);
    if ($tracking_code !== '') {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_code = ?");
        $stmt->execute([$tracking_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $error = 'No order found with that tracking code.';
        }
    }
}

// helper to compute progress percentage
function compute_progress($status_key, $status_stages) {
    $keys = array_keys($status_stages);
    $index = array_search($status_key, $keys);
    if ($index === false) return 0;
    return intval((($index + 1) / count($keys)) * 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Track Your Product</title>
    <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg,#f5f7fa,#c3cfe2); min-height:100vh; padding:20px }
    .container { max-width:1000px;margin:0 auto }
    header{text-align:center;margin-bottom:24px}
    .tracking-form{background:#fff;padding:20px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.08)}
    input[type="text"]{width:500px;padding:12px;border-radius:8px;border:1px solid #e0e0e0;margin-bottom:8px}
    .btn{display:inline-block;padding:10px 16px;border-radius:8px;background:linear-gradient(to right,#4CAF50,#45a049);color:#fff;border:none;cursor:pointer}
    .tracking-result{background:#fff;padding:20px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.08);margin-top:16px}
    .timeline-line{height:6px;background:#e0e0e0;border-radius:6px;position:relative}
    .timeline-progress{height:6px;background:#4CAF50;border-radius:6px;transition:width:0.5s}
    .stage{width:20%;text-align:center;padding-top:12px;display:inline-block;vertical-align:top}
    .stage-icon{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:3px solid #e0e0e0;background:#fff;margin-bottom:8px}
    .stage.active .stage-icon, .stage.completed .stage-icon{background:#4CAF50;color:#fff;border-color:#4CAF50}
    .no-order{padding:20px;text-align:center;color:#e74c3c}
    .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:12px}
    .info-item{background:#f8f9fa;padding:12px;border-radius:8px}
    @media (max-width:768px){ .stage{display:block;width:100%;text-align:left} .timeline-line{height:4px} .timeline-progress{height:4px} }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Track Your Product</h1>
        <p class="subtitle" style="color:#7f8c8d">Enter your tracking code to check delivery status</p>
    </header>

    <div class="tracking-form">
        <?php if (!empty($error)): ?>
            <div style="background:#fdecea;color:#611a15;padding:10px;border-radius:8px;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="tracking_code" style="font-weight:600;margin-bottom:6px;display:block">Tracking Code</label>
            <input type="text" id="tracking_code" name="tracking_code" placeholder="Enter tracking code (e.g. TRKABC123...)" value="<?php echo htmlspecialchars($tracking_code); ?>" required>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                <button type="submit" class="btn">Track Order</button>
            </div>
        </form>
    </div>

    <?php if ($tracking_code): ?>
        <div class="tracking-result">
            <?php if ($order): 
                $status_key = $order['status'] ?? 'processing';
                $progress = compute_progress($status_key, $status_stages);
            ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div style="font-size:12px;color:#7f8c8d">Order ID</div>
                        <div style="font-weight:700">#<?php echo (int)$order['order_id']; ?></div>
                    </div>
                    <div class="info-item">
                        <div style="font-size:12px;color:#7f8c8d">Customer</div>
                        <div style="font-weight:700"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                        <div style="font-size:13px;color:#7f8c8d"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div style="font-size:12px;color:#7f8c8d">Product</div>
                        <div style="font-weight:700"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div style="font-size:12px;color:#7f8c8d">Tracking Code</div>
                        <div style="font-weight:700;font-family:monospace"><?php echo htmlspecialchars($order['tracking_code']); ?></div>
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <div class="timeline-line" aria-hidden="true">
                        <div class="timeline-progress" style="width:<?php echo $progress; ?>%"></div>
                    </div>

                    <div style="display:flex;justify-content:space-between;margin-top:10px;flex-wrap:wrap">
                        <?php
                        $i = 1;
                        foreach ($status_stages as $key => $stage):
                            $keys = array_keys($status_stages);
                            $is_completed = array_search($key, $keys) <= array_search($status_key, $keys);
                            $is_active = ($key === $status_key);
                        ?>
                            <div class="stage <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>">
                                <div class="stage-icon"><?php echo $i; ?></div>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($stage['label']); ?></div>
                                <div style="color:#7f8c8d;font-size:13px"><?php echo htmlspecialchars($stage['desc']); ?></div>
                            </div>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>

                <div>
                    <h3>Current Status: <span style="color:#4CAF50"><?php echo htmlspecialchars($status_stages[$status_key]['label'] ?? ucfirst(str_replace('_',' ',$status_key))); ?></span></h3>
                    <p style="color:#7f8c8d"><?php echo htmlspecialchars($status_stages[$status_key]['desc'] ?? 'Status information not available.'); ?></p>
                </div>
            <?php else: ?>
                <div class="no-order">
                    <h3>No order found with tracking code: <?php echo htmlspecialchars($tracking_code); ?></h3>
                    <p>Please verify the code or contact support.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
