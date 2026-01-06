<?php
require_once 'config.php';

// Sample product data (replace by db lookup if you want)
$products = [
    1 => ['id'=>1,'name'=>"Men's Formal Suit",'price'=>299.99,'stock'=>25,'sizes'=>['S','M','L','XL'],'colors'=>['Black','Navy Blue'],'image_url'=>'./Velvet_vogue_images/T SHIRT.webp'],
    2 => ['id'=>2,'name'=>"Women's Evening Gown",'price'=>199.99,'stock'=>10,'sizes'=>['XS','S','M','L'],'colors'=>['Red','Black'],'image_url'=>'./Velvet_vogue_images/Gown.jpg'],
    3 => ['id'=>3,'name'=>"Casual T-Shirt",'price'=>24.99,'stock'=>50,'sizes'=>['S','M','L','XL'],'colors'=>['White','Black','Gray'],'image_url'=>'./Velvet_vogue_images/T-shirt.jpg'],
    4 => ['id'=>4,'name'=>"Winter Jacket",'price'=>159.99,'stock'=>15,'sizes'=>['S','M','L','XL'],'colors'=>['Black','Navy Blue'],'image_url'=>'./Velvet_vogue_images/Winter Jacket.jpg'],
    5 => ['id'=>5,'name'=>"Summer Dress",'price'=>149.99,'stock'=>10,'sizes'=>['XS','S','M','L'],'colors'=>['Red','Black'],'image_url'=>'./Velvet_vogue_images/Summer Dress.webp'],
    6 => ['id'=>6,'name'=>"Office Blazer",'price'=>89.99,'stock'=>50,'sizes'=>['S','M','L','XL'],'colors'=>['Black', 'Navy Blue', 'Beige'],'image_url'=>'./Velvet_vogue_images/Office Blazer.avif'],
    7 => ['id'=>7,'name'=>"Leather Handbag",'price'=>79.99,'stock'=>15,'sizes'=>['Standard'],'colors'=>['Brown', 'Black', 'Tan'],'image_url'=>'./Velvet_vogue_images/Leather Handbag.webp'],
    8 => ['id'=>8,'name'=>"Luxury Watch",'price'=>199.99,'stock'=>10,'sizes'=>['One Size'],'colors'=>['Silver', 'Gold', 'Black'],'image_url'=>'./Velvet_vogue_images/Luxury Watch.jpg']
   
];


// get product id
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 8;
$product = $products[$product_id] ?? reset($products);

// Handle add to cart (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');

    // server-side validation
    if ($size === '' || $color === '') {
        $_SESSION['error'] = "Please select size and color.";
        header("Location: product_view.php?product_id=" . $product_id);
        exit;
    }

    // prepare cart item data
    $item = [
        'product_id' => $product_id,
        'name' => $products[$product_id]['name'],
        'price' => $products[$product_id]['price'],
        'quantity' => $quantity,
        'size' => $size,
        'color' => $color,
        'image_url' => $products[$product_id]['image_url'],
    ];

    // If DB is available, insert/update cart table, else store in session
    if ($pdo) {
        try {
            // Use session_id to track anonymous carts; you can also use user_id if logged in
            $session_id = session_id();

            // Check for existing row with same product/size/color for this session
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND size = ? AND color = ?");
            $stmt->execute([$session_id, $product_id, $size, $color]);
            $existing = $stmt->fetch();

            if ($existing) {
                $newQty = $existing['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQty, $existing['id']]);
            } else {
                // Use only columns that exist in your table
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, session_id, product_id, quantity, size, color, created_at)
                                       VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $user_id = $_SESSION['user_id'] ?? null;
                $stmt->execute([$user_id, $session_id, $product_id, $quantity, $size, $color]);
            }
        } catch (PDOException $e) {
            // If database insert fails, fall back to session
            error_log("Database cart error: " . $e->getMessage());
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
            $key = $product_id . '|' . $size . '|' . $color;
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$key] = $item;
            }
        }
    } else {
        // session fallback
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
        $key = $product_id . '|' . $size . '|' . $color;
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$key] = $item;
        }
    }

    // success + redirect to cart
    $_SESSION['cart_message'] = "Product added to cart";
    exit;
}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .size-option { cursor:pointer; display:inline-block; padding:6px 12px; border:1px solid #ddd; margin-right:6px; border-radius:4px;}
        .size-option.selected { background:#440044; color:#fff; border-color:#440044;}
        .color-option { width:34px; height:34px; display:inline-block; border-radius:50%; margin-right:8px; border:2px solid #ddd; vertical-align:middle; cursor:pointer; }
        .color-option.selected { outline:3px solid #440044; }
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
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['cart_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['cart_message']); unset($_SESSION['cart_message']); ?></div>
    <?php endif; ?>

    <div class="row bg-white shadow-sm rounded p-3">
        <div class="col-md-6">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" onerror="this.src='./Velvet_vogue_images/PROFFESSIONALS.jpg'" class="img-fluid" alt="">
        </div>
        <div class="col-md-6">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <h4 class="text-success">$<?php echo number_format($product['price'],2); ?></h4>
            <p>Stock: <?php echo (int)$product['stock']; ?></p>

            <form method="POST" id="addToCartForm">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Size <span class="text-danger">*</span></label><br>
                    <?php foreach ($product['sizes'] as $s): ?>
                        <div class="size-option" data-size="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></div>
                    <?php endforeach; ?>
                    <input type="hidden" name="size" id="selectedSize" required>
                    <div class="text-danger small" id="sizeErr" style="display:none;">Please select size</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Color <span class="text-danger">*</span></label><br>
                    <?php foreach ($product['colors'] as $c): 
                        $bg = match($c) {
                            'Black' => '#000',
                            'White' => '#fff',
                            'Red' => '#f00',
                            'Navy Blue' => '#001f3f',
                            'Gray' => '#808080',
                            'Yellow' => '#ff0',
                            default => '#777'
                        };
                    ?>
                        <div class="color-option" data-color="<?php echo htmlspecialchars($c); ?>" title="<?php echo htmlspecialchars($c); ?>" style="background:<?php echo $bg;?>;"></div>
                    <?php endforeach; ?>
                    <input type="hidden" name="color" id="selectedColor" required>
                    <div class="text-danger small" id="colorErr" style="display:none;">Please select color</div>
                </div>

                <div class="mb-3 d-flex align-items-center">
                    <label class="me-3">Quantity</label>
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="changeQty(-1)">-</button>
                    <input type="number" min="1" max="<?php echo $product['stock']; ?>" name="quantity" id="quantity" value="1" style="width:80px;" class="form-control text-center">
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="changeQty(1)">+</button>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-dark">Add to Cart</button>
                </div>
            </form>
            <hr>
            <a href="cart.php">View Cart</a>
        </div>
    </div>
</div>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="./Product Categories.php" class="btn btn-outline-secondary">Continue Shopping</a>
    </div>

<script>
document.querySelectorAll('.size-option').forEach(e=>{
    e.addEventListener('click', () => {
        document.querySelectorAll('.size-option').forEach(x=>x.classList.remove('selected'));
        e.classList.add('selected');
        document.getElementById('selectedSize').value = e.getAttribute('data-size');
        document.getElementById('sizeErr').style.display = 'none';
    });
});
document.querySelectorAll('.color-option').forEach(e=>{
    e.addEventListener('click', () => {
        document.querySelectorAll('.color-option').forEach(x=>x.classList.remove('selected'));
        e.classList.add('selected');
        document.getElementById('selectedColor').value = e.getAttribute('data-color');
        document.getElementById('colorErr').style.display = 'none';
    });
});

function changeQty(delta){
    const q = document.getElementById('quantity');
    let v = parseInt(q.value) + delta;
    if (isNaN(v) || v < 1) v = 1;
    const max = parseInt(q.getAttribute('max')) || 9999;
    if (v > max) v = max;
    q.value = v;
}

document.getElementById('addToCartForm').addEventListener('submit', function(e){
    let ok = true;
    if (!document.getElementById('selectedSize').value) { document.getElementById('sizeErr').style.display='block'; ok=false; }
    if (!document.getElementById('selectedColor').value) { document.getElementById('colorErr').style.display='block'; ok=false; }
    if (!ok) e.preventDefault();
});
</script>
</body>
</html>