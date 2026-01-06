<?php
session_start();
require_once 'config.php';

// ==========================
// Handle Cart Actions
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_quantity') {
        updateCartQuantity($pdo, $_POST['cart_id'], intval($_POST['quantity']));
    } elseif ($action === 'remove_item') {
        removeCartItem($pdo, $_POST['cart_id']);
    } elseif ($action === 'clear_cart') {
        clearCart($pdo);
    }
}

// Cart calculation logic reviewed for accuracy


// ==========================
// Functions
// ==========================

function getCartItems($pdo) {
    if ($pdo) {
        $session_id = session_id();
        $user_id = $_SESSION['user_id'] ?? null;

        try {
            // If you have a products table, join it to get updated price & image
            if ($user_id) {
                $stmt = $pdo->prepare("
                    SELECT c.id, c.product_id, p.name, p.price, c.quantity, c.size, c.color, p.image_url
                    FROM cart c
                    JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ? OR c.session_id = ?
                ");
                $stmt->execute([$user_id, $session_id]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT c.id, c.product_id, p.name, p.price, c.quantity, c.size, c.color, p.image_url
                    FROM cart c
                    JOIN products p ON c.product_id = p.id
                    WHERE c.session_id = ?
                ");
                $stmt->execute([$session_id]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database cart read error: " . $e->getMessage());
        }
    }

    // Session fallback
    $items = [];
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $row) {
            $row['id'] = $key;
            $items[] = $row;
        }
    }
    return $items;
}

function getCartCount($pdo) {
    if ($pdo) {
        $session_id = session_id();
        $user_id = $_SESSION['user_id'] ?? null;

        try {
            if ($user_id) {
                $stmt = $pdo->prepare("SELECT SUM(quantity) AS cnt FROM cart WHERE user_id = ? OR session_id = ?");
                $stmt->execute([$user_id, $session_id]);
            } else {
                $stmt = $pdo->prepare("SELECT SUM(quantity) AS cnt FROM cart WHERE session_id = ?");
                $stmt->execute([$session_id]);
            }
            $r = $stmt->fetch();
            return (int)($r['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log("Database cart count error: " . $e->getMessage());
        }
    }

    $count = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $it) $count += $it['quantity'];
    }
    return $count;
}

function updateCartQuantity($pdo, $cart_id, $quantity) {
    if ($quantity < 1) $quantity = 1;

    if ($pdo) {
        try {
            $session_id = session_id();
            $user_id = $_SESSION['user_id'] ?? null;

            if ($user_id) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND (user_id = ? OR session_id = ?)");
                $stmt->execute([$quantity, $cart_id, $user_id, $session_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ?");
                $stmt->execute([$quantity, $cart_id, $session_id]);
            }
        } catch (PDOException $e) {
            error_log("Cart update error: " . $e->getMessage());
        }
    } else {
        if (isset($_SESSION['cart'][$cart_id])) {
            $_SESSION['cart'][$cart_id]['quantity'] = $quantity;
        }
    }

    // Prevent cache issues
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Location: cart.php");
    exit;
}

function removeCartItem($pdo, $cart_id) {
    if ($pdo) {
        $session_id = session_id();
        $user_id = $_SESSION['user_id'] ?? null;

        try {
            if ($user_id) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND (user_id = ? OR session_id = ?)");
                $stmt->execute([$cart_id, $user_id, $session_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?");
                $stmt->execute([$cart_id, $session_id]);
            }
        } catch (PDOException $e) {
            error_log("Cart remove error: " . $e->getMessage());
        }
    } else {
        unset($_SESSION['cart'][$cart_id]);
    }

    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Location: cart.php");
    exit;
}

function clearCart($pdo) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    if ($pdo) {
        try {
            if ($user_id) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? OR session_id = ?");
                $stmt->execute([$user_id, $session_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
                $stmt->execute([$session_id]);
            }
        } catch (PDOException $e) {
            error_log("Clear cart error: " . $e->getMessage());
        }
    } else {
        $_SESSION['cart'] = [];
    }

    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Location: cart.php");
    exit;
}

// ==========================
// Fetch Cart & Calculate Totals
// ==========================
$cart_items = getCartItems($pdo);
$cart_count = getCartCount($pdo);
$_SESSION['cart_count'] = $cart_count;

$subtotal = 0;
foreach ($cart_items as $it) {
    $subtotal += $it['price'] * $it['quantity'];
}
$shipping = ($cart_count > 0) ? 10.00 : 0.00;
$tax_rate = 0.08;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $shipping + $tax;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Cart - Velvet Vogue</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.cart-item-image{width:100px;height:110px;object-fit:cover;border-radius:6px;}
.quantity-input{width:70px;}
.remove-btn{background:none;border:none;color:#c00;cursor:pointer;}
</style>
</head>
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
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Shopping Cart</h2>
        <a href="./Product Categories.php" class="btn btn-outline-secondary">Continue Shopping</a>
    </div>

    <?php if ($cart_count === 0): ?>
        <div class="card p-5 text-center">
            <div style="font-size:64px">🛒</div>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Add items to your cart from the product pages.</p>
            <a href="Product_categories.php" class="btn btn-dark">Shop Now</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($cart_items as $item): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="cart-item-image" onerror="this.src='./Velvet_vogue_images/PROFFESSIONALS.jpg'">
                                </div>
                                <div class="col">
                                    <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <div class="text-muted">$<?php echo number_format($item['price'],2); ?> · Size: <?php echo htmlspecialchars($item['size']); ?> · Color: <?php echo htmlspecialchars($item['color']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                        <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" class="form-control quantity-input">
                                        <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                    </form>
                                </div>
                                <div class="col-auto text-end">
                                    <strong>$<?php echo number_format($item['price'] * $item['quantity'],2); ?></strong>
                                    <form method="POST" style="margin-top:8px;">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                        <button class="remove-btn" type="submit" onclick="return confirm('Remove item?')">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <form method="POST" onsubmit="return confirm('Clear entire cart?');">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="btn btn-outline-danger">Clear Cart</button>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card p-3">
                    <h5>Order Summary</h5>
                    <div class="d-flex justify-content-between mt-3"><div>Subtotal (<?php echo $cart_count; ?> items)</div><div>$<?php echo number_format($subtotal,2); ?></div></div>
                    <div class="d-flex justify-content-between"><div>Shipping</div><div>$<?php echo number_format($shipping,2); ?></div></div>
                    <div class="d-flex justify-content-between"><div>Tax</div><div>$<?php echo number_format($tax,2); ?></div></div>
                    <hr>
                    <div class="d-flex justify-content-between"><strong>Total</strong><strong>$<?php echo number_format($total,2); ?></strong></div>
                    
                    
                    <form action="Payment.php" method="POST">
                        <input type="hidden" name="total" value="<?php echo $total; ?>">
                        <button type="submit" class="btn btn-success w-100 mt-3">Payment</button>
                    </form>

                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php

require_once 'config.php';


$product_id = $_GET['product_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $review = trim($_POST['review']);

    $stmt = $pdo->prepare(
        "INSERT INTO reviews (user_id, product_id, rating, review_text)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$_SESSION['user_id'], $product_id, $rating, $review]);

    header("Location: Product_details.php?id=" . $product_id);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Add Your Review</h3>

    <form method="POST">
        <div class="mb-3">
            <label>Rating</label>
            <select name="rating" class="form-select" required>
                <option value="">Select</option>
                <option value="5">★★★★★</option>
                <option value="4">★★★★</option>
                <option value="3">★★★</option>
                <option value="2">★★</option>
                <option value="1">★</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Review</label>
            <textarea name="review" class="form-control" rows="4" required></textarea>
        </div>

        <button type="submit" class="btn btn-dark">Submit Review</button>
    </form>
</div>
</body>
</html>

