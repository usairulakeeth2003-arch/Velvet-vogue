<?php
session_start();
require_once 'config.php';

// Initialize payment variables
$total_amount = $_POST['total'] ?? 0;
$payment_method = $_POST['payment_method'] ?? 'card';
$payment_status = 'pending';

// Handle PayPal IPN (Instant Payment Notification) simulation
if (isset($_POST['paypal_payment']) && $_POST['paypal_payment'] == 'completed') {
    $payment_status = 'completed';
    $payment_method = 'paypal';
    $transaction_id = 'PAYPAL-' . uniqid();
}

// Handle card payment simulation
if (isset($_POST['card_payment']) && $_POST['card_payment'] == 'completed') {
    $payment_status = 'completed';
    $payment_method = 'card';
    $transaction_id = 'CARD-' . uniqid();
}

// Handle PDF download request
if (isset($_GET['download_pdf']) && isset($_GET['shipping_id'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="VelvetVogue_Receipt_' . $_GET['shipping_id'] . '.pdf"');
    echo generatePDFReceipt($_GET['shipping_id']);
    exit;
}

// When user submits shipping form with payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shipping'])) {

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $postal = $_POST['postal'];
    $account = $_POST['account'] ?? '';
    $total = $_POST['total'];
    $payment_method = $_POST['payment_method'] ?? 'card';
    $card_number = isset($_POST['card_number']) ? substr($_POST['card_number'], -4) : '';
    $card_expiry = $_POST['card_expiry'] ?? '';
    $card_cvv = $_POST['card_cvv'] ?? '';
    
    // Generate receipt number
    $receipt_number = 'VV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Generate transaction ID based on payment method
    $transaction_id = $payment_method == 'paypal' ? 'PAYPAL-' . uniqid() : 'CARD-' . uniqid();

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert payment details first
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (user_id, transaction_id, payment_method, amount, status, card_last4, card_expiry)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $transaction_id,
            $payment_method,
            $total,
            'completed',
            $card_number,
            $card_expiry
        ]);
        
        $payment_id = $pdo->lastInsertId();

        // Insert shipping details with payment reference
        $stmt = $pdo->prepare("
            INSERT INTO shipping_details 
            (user_id, payment_id, fullname, email, phone, address, city, postal_code, 
             account_number, total_amount, receipt_number, receipt_generated, payment_method)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $payment_id,
            $fullname, $email, $phone,
            $address, $city, $postal,
            $account, $total, $receipt_number, 1, $payment_method
        ]);

        $shipping_id = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Store in session
        $_SESSION['last_shipping_id'] = $shipping_id;
        $_SESSION['receipt_number'] = $receipt_number;
        $_SESSION['transaction_id'] = $transaction_id;
        
        // Send email receipt if requested
        if (isset($_POST['email_receipt']) && $_POST['email_receipt'] == 'on') {
            sendEmailReceipt($email, $fullname, $receipt_number, $total, $transaction_id, $payment_method);
        }
        
        // Redirect to receipt section
        header("Location: payment.php?show_receipt=true&shipping_id=" . $shipping_id);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error processing payment: " . $e->getMessage();
    }
}
// Payment handling comments added for clarity

// Fetch shipping details for receipt display
$shipping_details = null;
if (isset($_GET['show_receipt']) && isset($_GET['shipping_id'])) {
    $shipping_id = $_GET['shipping_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT sd.*, p.transaction_id, p.payment_method as pmethod 
            FROM shipping_details sd
            LEFT JOIN payments p ON sd.payment_id = p.id
            WHERE sd.id = ? 
            AND (sd.user_id = ? OR ? IS NULL)
        ");
        
        $stmt->execute([
            $shipping_id,
            $_SESSION['user_id'] ?? null,
            $_SESSION['user_id'] ?? null
        ]);
        
        $shipping_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error fetching receipt: " . $e->getMessage();
    }
}

// Functions
function generatePDFReceipt($shipping_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT sd.*, p.transaction_id, p.payment_method as pmethod 
        FROM shipping_details sd
        LEFT JOIN payments p ON sd.payment_id = p.id
        WHERE sd.id = ?
    ");
    $stmt->execute([$shipping_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        return "%PDF-1.4\n1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n2 0 obj\n<</Type/Pages/Kids[3 0 R]/Count 1>>\nendobj\n3 0 obj\n<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R>>\nendobj\n4 0 obj\n<</Length 44>>\nstream\nBT\n/F1 12 Tf\n56 720 Td\n(Receipt not found) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n0000000102 00000 n\n0000000169 00000 n\ntrailer\n<</Size 5/Root 1 0 R>>\nstartxref\n245\n%%EOF\n";
    }
    
    $pdf_content = "%PDF-1.4\n";
    $pdf_content .= "1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n";
    $pdf_content .= "2 0 obj\n<</Type/Pages/Kids[3 0 R]/Count 1>>\nendobj\n";
    $pdf_content .= "3 0 obj\n<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R>>\nendobj\n";
    
    $receipt_text = "Velvet Vogue - Official Receipt\n\n";
    $receipt_text .= "Receipt Number: " . ($data['receipt_number'] ?? 'VV-' . $shipping_id) . "\n";
    $receipt_text .= "Transaction ID: " . ($data['transaction_id'] ?? 'N/A') . "\n";
    $receipt_text .= "Payment Method: " . ($data['pmethod'] ?? $data['payment_method'] ?? 'Card') . "\n";
    $receipt_text .= "Date: " . date('F d, Y H:i:s') . "\n\n";
    $receipt_text .= "Customer Information:\n";
    $receipt_text .= "Name: " . htmlspecialchars($data['fullname']) . "\n";
    $receipt_text .= "Email: " . htmlspecialchars($data['email']) . "\n";
    $receipt_text .= "Phone: " . htmlspecialchars($data['phone']) . "\n\n";
    $receipt_text .= "Shipping Address:\n";
    $receipt_text .= htmlspecialchars($data['address']) . "\n";
    $receipt_text .= htmlspecialchars($data['city']) . " - " . htmlspecialchars($data['postal_code']) . "\n\n";
    $receipt_text .= "Payment Details:\n";
    if ($data['account_number']) {
        $receipt_text .= "Account: ****" . substr($data['account_number'], -4) . "\n";
    }
    $receipt_text .= "Total Amount: $" . number_format($data['total_amount'], 2) . "\n\n";
    $receipt_text .= "Status: PAID\n";
    $receipt_text .= "Thank you for your purchase!\n";
    $receipt_text .= "Velvet Vogue Customer Support: support@velvetvogue.com\n";
    
    $text_length = strlen($receipt_text);
    $pdf_content .= "4 0 obj\n<</Length " . $text_length . ">>\nstream\n" . $receipt_text . "\nendstream\nendobj\n";
    $pdf_content .= "xref\n0 5\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n0000000102 00000 n\n0000000169 00000 n\ntrailer\n<</Size 5/Root 1 0 R>>\nstartxref\n" . (strlen($pdf_content) - 100) . "\n%%EOF\n";
    
    return $pdf_content;
}

function sendEmailReceipt($to_email, $customer_name, $receipt_number, $amount, $transaction_id, $payment_method) {
    $subject = "Your Velvet Vogue Receipt - " . $receipt_number;
    $message = "
    <html>
    <head>
        <title>Payment Receipt</title>
    </head>
    <body>
        <h2>Velvet Vogue - Payment Receipt</h2>
        <p>Dear " . htmlspecialchars($customer_name) . ",</p>
        <p>Thank you for your purchase! Your payment has been successfully processed.</p>
        <p><strong>Receipt Number:</strong> " . $receipt_number . "</p>
        <p><strong>Transaction ID:</strong> " . $transaction_id . "</p>
        <p><strong>Payment Method:</strong> " . ucfirst($payment_method) . "</p>
        <p><strong>Amount Paid:</strong> $" . number_format($amount, 2) . "</p>
        <p><strong>Date:</strong> " . date('F d, Y H:i:s') . "</p>
        <br>
        <p>You can view and download your receipt from your account at any time.</p>
        <p>If you have any questions, please contact our support team.</p>
        <br>
        <p>Best regards,<br>Velvet Vogue Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Velvet Vogue <noreply@velvetvogue.com>' . "\r\n";
    
    error_log("Email receipt sent to: $to_email - Transaction: $transaction_id");
    // mail($to_email, $subject, $message, $headers);
}

// Check if we should show receipt or payment form
$show_receipt = isset($_GET['show_receipt']) && $shipping_details;
?>

<!DOCTYPE html>
<html>
<head>
<title><?php echo $show_receipt ? 'Payment Receipt' : 'Payment'; ?> - Velvet Vogue</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<!-- PayPal SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=test&currency=USD"></script>

<style>
body { 
    background: #f5f5f5; 
    padding-top: 80px;
}
.payment-container, .receipt-container {
    max-width: 900px;
    margin: 30px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
.header {
    background-color: #111;
    padding: 20px 40px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}
.payment-methods {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.payment-method {
    cursor: pointer;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.3s;
}
.payment-method:hover {
    border-color: #007bff;
    background: #f0f8ff;
}
.payment-method.active {
    border-color: #007bff;
    background: #e7f1ff;
}
.payment-method i {
    font-size: 24px;
    margin-right: 10px;
}
.card-details {
    display: none;
    animation: fadeIn 0.5s;
}
.paypal-container {
    display: none;
    text-align: center;
    padding: 20px;
}
#paypal-button-container {
    max-width: 300px;
    margin: 20px auto;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.card-icons {
    font-size: 24px;
    margin-left: 10px;
}
.receipt-header {
    text-align: center;
    border-bottom: 2px solid #333;
    padding-bottom: 20px;
    margin-bottom: 30px;
}
.btn-action {
    margin: 5px;
    min-width: 180px;
}
.payment-badge {
    font-size: 0.8em;
    padding: 3px 8px;
    border-radius: 12px;
}
.security-badge {
    background: #28a745;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9em;
}
.payment-processor-logos {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 20px 0;
}
.payment-processor-logos i {
    font-size: 32px;
    opacity: 0.7;
}
@media print {
    .no-print { display: none !important; }
    .receipt-container { box-shadow: none; margin: 0; }
}
</style>
</head>

<body>
<div class="header no-print">
    <h1>Velvet Vogue</h1>
    <nav>
        <a href="Home_page.php">Home</a>
        <a href="Product Categories.php">Products</a>
        <a href="Profile.php">Profile</a>
        <a href="cart.php">Cart</a>
        <a href="inquiry.php">Contact</a>
    </nav>
</div>

<?php if ($show_receipt && $shipping_details): ?>
<!-- RECEIPT SECTION -->
<div class="receipt-container">
    <div class="receipt-header">
        <h2><i class="bi bi-check-circle text-success"></i> Payment Successful</h2>
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="alert alert-success">
                    <i class="bi bi-<?php echo $shipping_details['pmethod'] == 'paypal' ? 'paypal' : 'credit-card'; ?>"></i>
                    <?php echo strtoupper($shipping_details['pmethod'] ?? 'Card'); ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-info">
                    <i class="bi bi-receipt"></i>
                    <?php echo $shipping_details['receipt_number']; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning">
                    <i class="bi bi-currency-dollar"></i>
                    $<?php echo number_format($shipping_details['total_amount'], 2); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <h5><i class="bi bi-person-badge"></i> Order Details</h5>
            <table class="table">
                <tr><th>Customer Name:</th><td><?php echo htmlspecialchars($shipping_details['fullname']); ?></td></tr>
                <tr><th>Email:</th><td><?php echo htmlspecialchars($shipping_details['email']); ?></td></tr>
                <tr><th>Shipping Address:</th><td><?php echo htmlspecialchars($shipping_details['address'] . ', ' . $shipping_details['city'] . ' - ' . $shipping_details['postal_code']); ?></td></tr>
                <tr><th>Transaction ID:</th><td><code><?php echo $shipping_details['transaction_id']; ?></code></td></tr>
                <tr><th>Payment Method:</th><td><?php echo strtoupper($shipping_details['pmethod'] ?? 'Card'); ?></td></tr>
            </table>
        </div>
        <div class="col-md-4">
            <h5><i class="bi bi-qr-code"></i> Quick Actions</h5>
            <div class="d-grid gap-2">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <a href="payment.php?download_pdf=true&shipping_id=<?php echo $shipping_details['id']; ?>" 
                   class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
                <a href="track_product.php" class="btn btn-info">
                    <i class="bi bi-truck"></i> Track Order
                </a>
                <a href="Home_page.php" class="btn btn-dark">
                    <i class="bi bi-house"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- PAYMENT FORM SECTION -->
<div class="payment-container">
    <h2><i class="bi bi-credit-card"></i> Complete Your Purchase</h2>
    <p class="text-muted">Choose your preferred payment method</p>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1"><i class="bi bi-cart-check"></i> Order Total</h5>
                <span class="fs-4">$<?php echo number_format($total_amount, 2); ?></span>
            </div>
            <span class="security-badge">
                <i class="bi bi-shield-check"></i> Secure Payment
            </span>
        </div>
    </div>

    <form method="POST" id="paymentForm">
        <input type="hidden" name="total" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="payment_method" id="payment_method" value="card">

        <!-- Personal Information -->
        <h5 class="mb-3"><i class="bi bi-person"></i> Personal Information</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="text" name="fullname" class="form-control" required 
                       placeholder="Full Name *" value="<?php echo $_POST['fullname'] ?? ''; ?>">
            </div>
            <div class="col-md-6 mb-3">
                <input type="email" name="email" class="form-control" required 
                       placeholder="Email Address *" value="<?php echo $_POST['email'] ?? ''; ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="text" name="phone" class="form-control" required 
                       placeholder="Phone Number *" value="<?php echo $_POST['phone'] ?? ''; ?>">
            </div>
            <div class="col-md-6 mb-3">
                <input type="text" name="account" class="form-control" 
                       placeholder="Account Number (Optional)">
            </div>
        </div>

        <!-- Shipping Address -->
        <h5 class="mb-3 mt-4"><i class="bi bi-truck"></i> Shipping Address</h5>
        <div class="mb-3">
            <textarea name="address" class="form-control" required rows="2" 
                      placeholder="Full Address *"><?php echo $_POST['address'] ?? ''; ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="text" name="city" class="form-control" required 
                       placeholder="City *" value="<?php echo $_POST['city'] ?? ''; ?>">
            </div>
            <div class="col-md-6 mb-3">
                <input type="text" name="postal" class="form-control" required 
                       placeholder="Postal Code *" value="<?php echo $_POST['postal'] ?? ''; ?>">
            </div>
        </div>

        <!-- Payment Method Selection -->
        <h5 class="mb-3 mt-4"><i class="bi bi-credit-card-2-front"></i> Payment Method</h5>
        <div class="payment-methods">
            <!-- Credit/Debit Card -->
            <div class="payment-method active" id="cardMethod" onclick="selectPaymentMethod('card')">
                <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card text-primary"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Credit/Debit Card</h6>
                        <p class="mb-0 text-muted small">Pay with Visa, Mastercard, American Express</p>
                    </div>
                    <div class="card-icons">
                        <i class="bi bi-credit-card-2-front text-primary"></i>
                        <i class="bi bi-shield-check text-success"></i>
                    </div>
                </div>
            </div>

            <!-- PayPal -->
            <div class="payment-method" id="paypalMethod" onclick="selectPaymentMethod('paypal')">
                <div class="d-flex align-items-center">
                    <i class="bi bi-paypal text-primary"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">PayPal</h6>
                        <p class="mb-0 text-muted small">Pay securely with your PayPal account</p>
                    </div>
                    <div>
                        <i class="bi bi-paypal" style="color: #003087; font-size: 32px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Details Section -->
        <div class="card-details" id="cardDetails">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Card Number *</label>
                    <div class="input-group">
                        <input type="text" name="card_number" id="card_number" 
                               class="form-control" placeholder="1234 5678 9012 3456"
                               maxlength="19" oninput="formatCardNumber(this)">
                        <span class="input-group-text">
                            <i class="bi bi-credit-card"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-lock"></i> Your card details are encrypted and secure
                        </small>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Card Type</label>
                    <div id="cardType" class="form-control" style="background: #f8f9fa;">
                        <i class="bi bi-credit-card"></i> Enter card number
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Expiry Date *</label>
                    <input type="text" name="card_expiry" id="card_expiry" 
                           class="form-control" placeholder="MM/YY"
                           maxlength="5" oninput="formatExpiry(this)">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">CVV *</label>
                    <div class="input-group">
                        <input type="password" name="card_cvv" id="card_cvv" 
                               class="form-control" placeholder="123" maxlength="4">
                        <span class="input-group-text" onclick="toggleCVV()" style="cursor: pointer;">
                            <i class="bi bi-eye" id="cvvEye"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-control" style="background: #f8f9fa; height: 42px;">
                        <i class="bi bi-shield-check text-success"></i> 3D Secure
                    </div>
                </div>
            </div>
            
            <div class="payment-processor-logos">
                <i class="bi bi-credit-card" style="color: #1a1f71;" title="Visa"></i>
                <i class="bi bi-credit-card" style="color: #ff5f00;" title="Mastercard"></i>
                <i class="bi bi-credit-card" style="color: #016fd0;" title="American Express"></i>
                <i class="bi bi-shield-check" style="color: #28a745;" title="Secure"></i>
            </div>
        </div>

        <!-- PayPal Section -->
        <div class="paypal-container" id="paypalContainer">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                You will be redirected to PayPal to complete your payment securely.
            </div>
            <div id="paypal-button-container"></div>
            <div class="mt-3">
                <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-medium.png" 
                     alt="PayPal" style="height: 40px;">
            </div>
        </div>

        <!-- Receipt Options -->
        <div class="receipt-options mt-4">
            <h6><i class="bi bi-receipt"></i> Receipt Preferences</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="email_receipt" id="email_receipt" checked>
                <label class="form-check-label" for="email_receipt">
                    Email me a digital receipt
                </label>
            </div>
        </div>

        <!-- Terms and Submit -->
        <div class="alert alert-light border mt-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="#" class="text-decoration-none">Terms & Conditions</a> and 
                    authorize this payment of <strong>$<?php echo number_format($total_amount, 2); ?></strong>
                </label>
            </div>
        </div>

        <div class="d-grid gap-2 mt-4">
    <button type="submit" 
            name="save_shipping" 
            class="btn btn-dark btn-lg" 
            id="submitBtn"
            onclick="return goCheckout();">
        <i class="bi bi-lock-fill"></i> Pay $<?php echo number_format($total_amount, 2); ?>
    </button>
</div>

<script>
function goCheckout() {
    // Optional confirmation message
    if (confirm("Payment successful! Continue to checkout?")) {
        window.location.href = "./Check_out.php"; 
        return false; // prevent form from submitting
    }
    return false;
}
</script>

            <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                <i class="bi bi-arrow-left"></i> Back to Cart
            </button>


            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='user_orders.php'">
    <i class="bi bi-arrow-right"></i> Track order
</button>

        </div>
        
        
        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="bi bi-shield-check"></i> 256-bit SSL Encryption • PCI DSS Compliant
            </small>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
// Payment method selection
let selectedMethod = 'card';

function selectPaymentMethod(method) {
    selectedMethod = method;
    document.getElementById('payment_method').value = method;
    
    // Update UI
    document.querySelectorAll('.payment-method').forEach(el => {
        el.classList.remove('active');
    });
    
    if (method === 'card') {
        document.getElementById('cardMethod').classList.add('active');
        document.getElementById('cardDetails').style.display = 'block';
        document.getElementById('paypalContainer').style.display = 'none';
        document.getElementById('submitBtn').innerHTML = `<i class="bi bi-lock-fill"></i> Pay $<?php echo number_format($total_amount, 2); ?>`;
    } else {
        document.getElementById('paypalMethod').classList.add('active');
        document.getElementById('cardDetails').style.display = 'none';
        document.getElementById('paypalContainer').style.display = 'block';
        document.getElementById('submitBtn').style.display = 'none';
        initializePayPal();
    }
}

// Format card number with spaces
function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    let formatted = '';
    
    for (let i = 0; i < value.length && i < 16; i++) {
        if (i > 0 && i % 4 === 0) {
            formatted += ' ';
        }
        formatted += value[i];
    }
    
    input.value = formatted;
    detectCardType(value);
}

// Format expiry date
function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        input.value = value.substring(0, 2) + '/' + value.substring(2, 4);
    } else {
        input.value = value;
    }
}

// Detect card type
function detectCardType(number) {
    const cardType = document.getElementById('cardType');
    const firstDigit = number.charAt(0);
    
    if (/^4/.test(number)) {
        cardType.innerHTML = '<i class="bi bi-credit-card" style="color: #1a1f71;"></i> Visa';
        cardType.style.color = '#1a1f71';
    } else if (/^5[1-5]/.test(number)) {
        cardType.innerHTML = '<i class="bi bi-credit-card" style="color: #ff5f00;"></i> Mastercard';
        cardType.style.color = '#ff5f00';
    } else if (/^3[47]/.test(number)) {
        cardType.innerHTML = '<i class="bi bi-credit-card" style="color: #016fd0;"></i> American Express';
        cardType.style.color = '#016fd0';
    } else if (/^6/.test(number)) {
        cardType.innerHTML = '<i class="bi bi-credit-card" style="color: #006fcf;"></i> Discover';
        cardType.style.color = '#006fcf';
    } else {
        cardType.innerHTML = '<i class="bi bi-credit-card"></i> Enter card number';
        cardType.style.color = '#6c757d';
    }
}

// Toggle CVV visibility
function toggleCVV() {
    const cvvInput = document.getElementById('card_cvv');
    const eyeIcon = document.getElementById('cvvEye');
    
    if (cvvInput.type === 'password') {
        cvvInput.type = 'text';
        eyeIcon.className = 'bi bi-eye-slash';
    } else {
        cvvInput.type = 'password';
        eyeIcon.className = 'bi bi-eye';
    }
}

// PayPal Integration
function initializePayPal() {
    // For demo purposes - in production, use real PayPal Client ID
    paypal.Buttons({
        style: {
            layout: 'vertical',
            color:  'gold',
            shape:  'rect',
            label:  'paypal'
        },
        
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?php echo $total_amount; ?>'
                    }
                }]
            });
        },
        
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Create a hidden form to submit payment data
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                // Add form data
                const paymentId = document.createElement('input');
                paymentId.type = 'hidden';
                paymentId.name = 'paypal_payment';
                paymentId.value = 'completed';
                form.appendChild(paymentId);
                
                // Add all form data from main form
                const mainForm = document.getElementById('paymentForm');
                const inputs = mainForm.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    if (input.name && input.type !== 'submit') {
                        const clone = input.cloneNode(true);
                        form.appendChild(clone);
                    }
                });
                
                // Submit the form
                document.body.appendChild(form);
                form.submit();
            });
        },
        
        onError: function(err) {
            console.error('PayPal Error:', err);
            alert('There was an error processing your PayPal payment. Please try again.');
        }
    }).render('#paypal-button-container');
}

// Form validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    if (selectedMethod === 'card') {
        // Validate card details
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const cardExpiry = document.getElementById('card_expiry').value;
        const cardCVV = document.getElementById('card_cvv').value;
        
        if (!/^\d{16}$/.test(cardNumber)) {
            e.preventDefault();
            alert('Please enter a valid 16-digit card number.');
            document.getElementById('card_number').focus();
            return false;
        }
        
        if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
            e.preventDefault();
            alert('Please enter a valid expiry date (MM/YY).');
            document.getElementById('card_expiry').focus();
            return false;
        }
        
        if (!/^\d{3,4}$/.test(cardCVV)) {
            e.preventDefault();
            alert('Please enter a valid CVV (3 or 4 digits).');
            document.getElementById('card_cvv').focus();
            return false;
        }
        
        // Show processing animation
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
        submitBtn.disabled = true;
    }
    
    return true;
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set default payment method
    selectPaymentMethod('card');
    
    // Add input masks
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 12);
        });
    }
});

// For demo purposes - simulate different card types
document.getElementById('card_number').addEventListener('input', function() {
    const demoCards = {
        '4242424242424242': 'Visa',
        '5555555555554444': 'Mastercard',
        '378282246310005': 'American Express',
        '6011111111111117': 'Discover'
    };
    
    const currentValue = this.value.replace(/\s/g, '');
    if (demoCards[currentValue]) {
        alert(`Demo ${demoCards[currentValue]} card detected. In production, this would be a real transaction.`);
    }
});
</script>

<!-- Database Tables SQL (Add these to your database) -->
<!-- 
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    transaction_id VARCHAR(100) UNIQUE,
    payment_method ENUM('card', 'paypal', 'bank') DEFAULT 'card',
    amount DECIMAL(10,2),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    card_last4 VARCHAR(4),
    card_expiry VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

ALTER TABLE shipping_details 
ADD COLUMN payment_id INT,
ADD COLUMN payment_method VARCHAR(20),
ADD FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL;
-->

</body>
</html>