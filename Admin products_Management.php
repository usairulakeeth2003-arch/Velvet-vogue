<?php
// Include config with require_once to prevent multiple inclusions
require_once 'config.php';

// Ensure $pdo is available
global $pdo;

// Check if $pdo is set, if not create connection
if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
// Added note for future product validation enhancement

// Handle form actions
$action = $_GET['action'] ?? '';
$product_id = $_GET['id'] ?? 0;
$banner_id = $_GET['banner_id'] ?? 0;
$message = '';

// Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Product Form
    if (isset($_POST['product_action'])) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $clothing_type = $_POST['clothing_type'] ?? '';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $stock_quantity = $_POST['stock_quantity'] ?? 0;
        
        // Handle image upload
        $image_url = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image_name = time() . '_' . $_FILES['image']['name'];
            $image_path = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image_url = 'uploads/' . $image_name;
            }
        }
        
        if ($_POST['product_action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, gender, clothing_type, featured, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category_id, $gender, $clothing_type, $featured, $stock_quantity, $image_url]);
            $message = "Product added successfully!";
        } elseif ($_POST['product_action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category_id=?, gender=?, clothing_type=?, featured=?, stock_quantity=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $description, $price, $category_id, $gender, $clothing_type, $featured, $stock_quantity, $image_url, $_POST['product_id']]);
            $message = "Product updated successfully!";
        }
    }
    
    // Handle Banner Form
    if (isset($_POST['banner_action'])) {
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $button_text = $_POST['button_text'] ?? 'Learn More';
        $button_link = $_POST['button_link'] ?? '#';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $display_order = $_POST['display_order'] ?? 0;
        
        // Handle banner image upload
        $banner_image_url = $_POST['existing_banner_image'] ?? '';
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image_name = 'banner_' . time() . '_' . $_FILES['banner_image']['name'];
            $image_path = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $image_path)) {
                $banner_image_url = 'uploads/' . $image_name;
            }
        }
        
        if ($_POST['banner_action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO banners (title, subtitle, image_url, button_text, button_link, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $banner_image_url, $button_text, $button_link, $is_active, $display_order]);
            $message = "Banner added successfully!";
        } elseif ($_POST['banner_action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE banners SET title=?, subtitle=?, image_url=?, button_text=?, button_link=?, is_active=?, display_order=? WHERE id=?");
            $stmt->execute([$title, $subtitle, $banner_image_url, $button_text, $button_link, $is_active, $display_order, $_POST['banner_id']]);
            $message = "Banner updated successfully!";
        }
    }
    
    // Redirect to avoid form resubmission
    if ($message) {
        header('Location: products_management.php?message=' . urlencode($message));
        exit;
    }
}

// Delete Product
if ($action === 'delete' && $product_id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    header('Location: products_management.php?message=Product deleted successfully!');
    exit;
}

// Delete Banner
if ($action === 'delete_banner' && $banner_id) {
    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([$banner_id]);
    header('Location: products_management.php?message=Banner deleted successfully!');
    exit;
}

// Get all products
try {
    $products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $products = [];
    error_log("Products query error: " . $e->getMessage());
}

// Get all banners
try {
    $banners = $pdo->query("SELECT * FROM banners ORDER BY display_order, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $banners = [];
    error_log("Banners query error: " . $e->getMessage());
}

// Get product for editing
$edit_product = null;
if ($action === 'edit' && $product_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $edit_product = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Edit product error: " . $e->getMessage());
    }
}

// Get banner for editing
$edit_banner = null;
if ($action === 'edit_banner' && $banner_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$banner_id]);
        $edit_banner = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Edit banner error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product & Banner Management - Velvet Vogue</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #085c8cff;
            --primary-dark: #5e35b1;
            --secondary: #110106ff;
            --light: #f5f5f5;
            --dark: #333;
            --gray: #777;
            --light-gray: #e0e0e0;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #151414ff;
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
            max-width: 1200px;
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
            max-width: 1200px;
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
        
        .management-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .tab-button {
            padding: 15px 30px;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-button:hover {
            color: var(--primary-dark);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .product-form-section, .banner-form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 40px;
            border-left: 4px solid var(--primary);
        }
        
        .banner-form-section {
            border-left-color: var(--secondary);
        }
        
        .product-form-section h2, .banner-form-section h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .banner-form-section h2 {
            color: var(--secondary);
        }
        
        .product-form-section h2 i, .banner-form-section h2 i {
            color: var(--secondary);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(126, 87, 194, 0.3);
        }
        
        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-secondary:hover {
            background: #d5d5d5;
            transform: translateY(-2px);
        }
        
        .btn-pink {
            background: var(--secondary);
            color: white;
        }
        
        .btn-pink:hover {
            background: #e9801eff;
        }
        
        .products-grid-section, .banners-grid-section {
            margin-bottom: 50px;
        }
        
        .products-grid-section h2, .banners-grid-section h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .banners-grid-section h2 {
            color: var(--secondary);
        }
        
        .products-count, .banners-count {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .banners-count {
            background: var(--secondary);
        }
        
        .products-grid, .banners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .banners-grid {
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        }
        
        .product-card, .banner-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--light-gray);
        }
        
        .banner-card {
            border-left: 4px solid var(--secondary);
        }
        
        .product-card:hover, .banner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image, .banner-image {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: var(--light);
        }
        
        .banner-image {
            height: 180px;
        }
        
        .product-image img, .banner-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-card:hover .product-image img,
        .banner-card:hover .banner-image img {
            transform: scale(1.05);
        }
        
        .featured-badge, .active-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .featured-badge {
            background: var(--secondary);
        }
        
        .active-badge {
            background: var(--success);
        }
        
        .inactive-badge {
            background: var(--gray);
        }
        
        .product-info, .banner-info {
            padding: 20px;
        }
        
        .product-info h3, .banner-info h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: var(--dark);
            line-height: 1.4;
        }
        
        .banner-info h4 {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .product-price {
            color: var(--primary);
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .product-stock, .banner-order {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .product-stock.low {
            color: var(--danger);
            font-weight: 600;
        }
        
        .product-actions, .banner-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-edit, .btn-preview, .btn-delete, .btn-edit-banner, .btn-preview-banner, .btn-delete-banner {
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            flex: 1;
            justify-content: center;
        }
        
        .btn-edit, .btn-edit-banner {
            background: var(--primary);
            color: white;
        }
        
        .btn-edit:hover, .btn-edit-banner:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-preview, .btn-preview-banner {
            background: var(--success);
            color: white;
        }
        
        .btn-preview:hover, .btn-preview-banner:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .btn-delete, .btn-delete-banner {
            background: var(--danger);
            color: white;
        }
        
        .btn-delete:hover, .btn-delete-banner:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        
        .current-image {
            margin-top: 15px;
        }
        
        .current-image p {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .current-image img {
            border-radius: 8px;
            border: 2px solid var(--light-gray);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 200px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
            background: white;
            border-radius: 12px;
            border: 2px dashed var(--light-gray);
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
            
            .management-tabs {
                flex-direction: column;
            }
            
            .tab-button {
                text-align: center;
                border-bottom: 1px solid var(--light-gray);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .products-grid, .banners-grid {
                grid-template-columns: 1fr;
            }
            
            .product-actions, .banner-actions {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
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
        <?php if (isset($_GET['message'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Management Tabs -->
        <div class="management-tabs">
            <button class="tab-button active" onclick="switchTab('products')">
                <i class="fas fa-boxes"></i> Product Management
            </button>
            <button class="tab-button" onclick="switchTab('banners')">
                <i class="fas fa-images"></i> Banner Management
            </button>
        </div>

        <!-- Products Tab -->
        <div id="products-tab" class="tab-content active">
            <!-- Product Form -->
            <div class="product-form-section">
                <h2>
                    <i class="fas fa-<?php echo $edit_product ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
                </h2>
                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <input type="hidden" name="product_action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $edit_product['image_url'] ?? ''; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Product Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required 
                                   placeholder="Enter product name">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> Price ($)</label>
                            <input type="number" name="price" step="0.01" min="0" value="<?php echo $edit_product['price'] ?? ''; ?>" required 
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" rows="4" required 
                                  placeholder="Enter product description"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> Category</label>
                            <select name="category_id" required>
                                <option value="1" <?php echo ($edit_product['category_id'] ?? '') == 1 ? 'selected' : ''; ?>>Formal Wear</option>
                                <option value="2" <?php echo ($edit_product['category_id'] ?? '') == 2 ? 'selected' : ''; ?>>Casual Wear</option>
                                <option value="3" <?php echo ($edit_product['category_id'] ?? '') == 3 ? 'selected' : ''; ?>>Accessories</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Gender</label>
                            <select name="gender" required>
                                <option value="men" <?php echo ($edit_product['gender'] ?? '') == 'men' ? 'selected' : ''; ?>>Men</option>
                                <option value="women" <?php echo ($edit_product['gender'] ?? '') == 'women' ? 'selected' : ''; ?>>Women</option>
                                <option value="unisex" <?php echo ($edit_product['gender'] ?? '') == 'unisex' ? 'selected' : ''; ?>>Unisex</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tshirt"></i> Clothing Type</label>
                            <select name="clothing_type" required>
                                <option value="casual" <?php echo ($edit_product['clothing_type'] ?? '') == 'casual' ? 'selected' : ''; ?>>Casual</option>
                                <option value="formal" <?php echo ($edit_product['clothing_type'] ?? '') == 'formal' ? 'selected' : ''; ?>>Formal</option>
                                <option value="accessories" <?php echo ($edit_product['clothing_type'] ?? '') == 'accessories' ? 'selected' : ''; ?>>Accessories</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-boxes"></i> Stock Quantity</label>
                            <input type="number" name="stock_quantity" min="0" value="<?php echo $edit_product['stock_quantity'] ?? 0; ?>" required 
                                   placeholder="0">
                        </div>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="featured" value="1" <?php echo ($edit_product['featured'] ?? 0) ? 'checked' : ''; ?>>
                                <i class="fas fa-star"></i> Featured Product
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Product Image</label>
                        <input type="file" name="image" accept="image/*" <?php echo !$edit_product ? 'required' : ''; ?> 
                               onchange="previewImage(this, 'product')">
                        <?php if ($edit_product && !empty($edit_product['image_url'])): ?>
                            <div class="current-image">
                                <p><i class="fas fa-image"></i> Current Image:</p>
                                <img src="../<?php echo htmlspecialchars($edit_product['image_url']); ?>" alt="Current Image">
                            </div>
                        <?php endif; ?>
                        <div id="productImagePreview" class="current-image" style="display: none;">
                            <p><i class="fas fa-eye"></i> New Image Preview:</p>
                            <img id="productPreviewImg" src="" alt="Preview">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-<?php echo $edit_product ? 'save' : 'plus'; ?>"></i>
                            <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="./Home_page.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Products Grid -->
            <div class="products-grid-section">
                <h2>
                    <i class="fas fa-list"></i> Manage Products
                    <span class="products-count"><?php echo count($products); ?></span>
                </h2>
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No Products Found</h3>
                        <p>Add your first product using the form above to get started!</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="../<?php echo htmlspecialchars($product['image_url'] ?: 'assets/images/placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjIyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                                    <?php if ($product['featured']): ?>
                                        <span class="featured-badge"><i class="fas fa-star"></i> Featured</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                    <p class="product-stock <?php echo $product['stock_quantity'] < 10 ? 'low' : ''; ?>">
                                        <i class="fas fa-<?php echo $product['stock_quantity'] < 10 ? 'exclamation-triangle' : 'boxes'; ?>"></i>
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                        <?php if ($product['stock_quantity'] < 10): ?>
                                            <span style="color: var(--danger); font-weight: bold;">(Low Stock!)</span>
                                        <?php endif; ?>
                                    </p>
                                    <div class="product-actions">
                                        <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="../index.php" class="btn-preview" target="_blank">
                                            <i class="fas fa-eye"></i> Preview
                                        </a>
                                        <a href="?action=delete&id=<?php echo $product['id']; ?>" class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete \'<?php echo addslashes($product['name']); ?>\'?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banners Tab -->
        <div id="banners-tab" class="tab-content">
            <!-- Banner Form -->
            <div class="banner-form-section">
                <h2>
                    <i class="fas fa-<?php echo $edit_banner ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $edit_banner ? 'Edit Banner' : 'Add New Banner'; ?>
                </h2>
                <form method="POST" enctype="multipart/form-data" class="banner-form">
                    <input type="hidden" name="banner_action" value="<?php echo $edit_banner ? 'edit' : 'add'; ?>">
                    <?php if ($edit_banner): ?>
                        <input type="hidden" name="banner_id" value="<?php echo $edit_banner['id']; ?>">
                        <input type="hidden" name="existing_banner_image" value="<?php echo $edit_banner['image_url'] ?? ''; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Banner Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_banner['title'] ?? ''); ?>" required 
                                   placeholder="Enter banner title">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-sort-numeric-down"></i> Display Order</label>
                            <input type="number" name="display_order" min="0" value="<?php echo $edit_banner['display_order'] ?? 0; ?>" required 
                                   placeholder="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Subtitle/Description</label>
                        <textarea name="subtitle" rows="3" required 
                                  placeholder="Enter banner subtitle or description"><?php echo htmlspecialchars($edit_banner['subtitle'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-mouse-pointer"></i> Button Text</label>
                            <input type="text" name="button_text" value="<?php echo htmlspecialchars($edit_banner['button_text'] ?? 'Learn More'); ?>" 
                                   placeholder="Button text">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-link"></i> Button Link</label>
                            <input type="text" name="button_link" value="<?php echo htmlspecialchars($edit_banner['button_link'] ?? '#'); ?>" 
                                   placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="is_active" value="1" <?php echo ($edit_banner['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <i class="fas fa-eye"></i> Active Banner
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Banner Image</label>
                        <input type="file" name="banner_image" accept="image/*" <?php echo !$edit_banner ? 'required' : ''; ?> 
                               onchange="previewImage(this, 'banner')">
                        <?php if ($edit_banner && !empty($edit_banner['image_url'])): ?>
                            <div class="current-image">
                                <p><i class="fas fa-image"></i> Current Image:</p>
                                <img src="../<?php echo htmlspecialchars($edit_banner['image_url']); ?>" alt="Current Banner Image">
                            </div>
                        <?php endif; ?>
                        <div id="bannerImagePreview" class="current-image" style="display: none;">
                            <p><i class="fas fa-eye"></i> New Image Preview:</p>
                            <img id="bannerPreviewImg" src="" alt="Preview">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-pink">
                            <i class="fas fa-<?php echo $edit_banner ? 'save' : 'plus'; ?>"></i>
                            <?php echo $edit_banner ? 'Update Banner' : 'Add Banner'; ?>
                        </button>
                        <?php if ($edit_banner): ?>
                            <a href="products_management.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Banners Grid -->
            <div class="banners-grid-section">
                <h2>
                    <i class="fas fa-images"></i> Manage Banners
                    <span class="banners-count"><?php echo count($banners); ?></span>
                </h2>
                <?php if (empty($banners)): ?>
                    <div class="empty-state">
                        <i class="fas fa-image"></i>
                        <h3>No Banners Found</h3>
                        <p>Add your first banner using the form above to get started!</p>
                    </div>
                <?php else: ?>
                    <div class="banners-grid">
                        <?php foreach ($banners as $banner): ?>
                            <div class="banner-card">
                                <div class="banner-image">
                                    <img src="../<?php echo htmlspecialchars($banner['image_url'] ?: 'assets/images/placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($banner['title']); ?>"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkJhbm5lciBJbWFnZTwvdGV4dD48L3N2Zz4='">
                                    <span class="<?php echo $banner['is_active'] ? 'active-badge' : 'inactive-badge'; ?>">
                                        <i class="fas fa-<?php echo $banner['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                                        <?php echo $banner['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="banner-info">
                                    <h3><?php echo htmlspecialchars($banner['title']); ?></h3>
                                    <h4><?php echo htmlspecialchars($banner['subtitle']); ?></h4>
                                    <p class="banner-order">
                                        <i class="fas fa-sort-numeric-down"></i>
                                        Display Order: <?php echo $banner['display_order']; ?>
                                    </p>
                                    <div class="banner-actions">
                                        <a href="?action=edit_banner&banner_id=<?php echo $banner['id']; ?>" class="btn-edit-banner">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="../index.php" class="btn-preview-banner" target="_blank">
                                            <i class="fas fa-eye"></i> Preview
                                        </a>
                                        <a href="?action=delete_banner&banner_id=<?php echo $banner['id']; ?>" class="btn-delete-banner" 
                                           onclick="return confirm('Are you sure you want to delete the banner \'<?php echo addslashes($banner['title']); ?>\'?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab button
            event.currentTarget.classList.add('active');
        }

        // Image preview functionality
        function previewImage(input, type) {
            const preview = document.getElementById(type + 'ImagePreview');
            const img = document.getElementById(type + 'PreviewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            });
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
    </script>
</body>
</html>