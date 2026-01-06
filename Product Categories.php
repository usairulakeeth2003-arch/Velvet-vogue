<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories - Velvet Vogue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 80px;
        }

        .category-header {
            background: linear-gradient(135deg, #440044, #663366);
            color: white;
            padding: 50px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .category-filter {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .product-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: 0.3s ease;
            margin-bottom: 30px;
            height: 500px;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            height: 250px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .product-price {
            color: #28a745;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .btn-details {
            background: #440044;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: 0.3s ease;
        }
        .btn-details:hover {
            background: #330033;
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
            width: 100%;
            z-index: 1000;
        }

        .header a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .header a:hover {
            color: #ff7f50;
        }

        .category-badge {
            background: #440044;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

<!-- Header -->
<div class="header">
    <h1>Velvet Vogue</h1>
    <nav>
        <a href="Home_page.php">Home</a>
        <a href="Product Categories.php">Products</a>
        <a href="Profile.php">Profile</a>
        <a href="cart.php">Cart</a>
        <a href="inquiry.php">Contact</a>
    </nav>
</div>

<!-- Category Header -->
<div class="category-header">
    <div class="container">
        <h1 class="display-4 fw-bold">Our Collections</h1>
        <p class="lead">Discover the perfect style for every occasion</p>
    </div>
</div>

<div class="container">

    <!-- FILTERS -->
    <div class="category-filter">
        <div class="row g-3">
            
            <!-- Gender Filter -->
            <div class="col-md-2">
                <select id="genderFilter" class="form-select">
                    <option value="all">All Genders</option>
                    <option value="men">Men</option>
                    <option value="women">Women</option>
                    <option value="accessories">Accessories</option>
                </select>
            </div>

            <!-- Clothing Type -->
            <div class="col-md-2">
                <select id="clothingType" class="form-select">
                    <option value="all">All Types</option>
                    <option value="formal">Formal</option>
                    <option value="casual">Casual</option>
                </select>
            </div>

            <!-- Size -->
            <div class="col-md-2">
                <select id="sizeFilter" class="form-select">
                    <option value="all">All Sizes</option>
                    <option value="S">Small (S)</option>
                    <option value="M">Medium (M)</option>
                    <option value="L">Large (L)</option>
                </select>
            </div>

            <!-- Price Range -->
            <div class="col-md-2">
                <select id="priceRange" class="form-select">
                    <option value="all">All Prices</option>
                    <option value="0-50">$0 - $50</option>
                    <option value="51-150">$51 - $150</option>
                    <option value="151-300">$151 - $300</option>
                </select>
            </div>

            <!-- Sort -->
            <div class="col-md-2">
                <select id="sortSelect" class="form-select">
                    <option value="newest">Newest First</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-md-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
            </div>

        </div>
    </div>

    <!-- PRODUCTS GRID -->
    <div class="row" id="productsGrid">

        <!-- ALL 8 PRODUCTS WITH FILTER ATTRIBUTES -->

        <!-- 1 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="men"
             data-type="formal"
             data-size="L"
             data-price="299.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/T SHIRT.webp" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Men's Formal</span>
                    <h5 class="card-title mt-2">Men's Formal Suit</h5>
                    <p class="card-text">Elegant black formal suit for professional occasions.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$299.99</span>
                        <a href="Product view.php?product_id=1" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="women"
             data-type="formal"
             data-size="M"
             data-price="199.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/Gown.jpg" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Women's Formal</span>
                    <h5 class="card-title mt-2">Women's Evening Gown</h5>
                    <p class="card-text">Beautiful red evening gown for special events.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$199.99</span>
                        <a href="Product view.php?product_id=2" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="men"
             data-type="casual"
             data-size="M"
             data-price="24.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/T-shirt.jpg" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Men's Casual</span>
                    <h5 class="card-title mt-2">Casual T-Shirt</h5>
                    <p class="card-text">Comfortable cotton t-shirt for everyday wear.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$24.99</span>
                        <a href="Product view.php?product_id=3" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="men"
             data-type="casual"
             data-size="L"
             data-price="159.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/Winter Jacket.jpg" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Men's Casual</span>
                    <h5 class="card-title mt-2">Winter Jacket</h5>
                    <p class="card-text">Warm winter jacket for cold weather protection.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$159.99</span>
                        <a href="Product view.php?product_id=4" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="women"
             data-type="casual"
             data-size="S"
             data-price="149.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/Summer Dress.webp" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Women's Casual</span>
                    <h5 class="card-title mt-2">Summer Dress</h5>
                    <p class="card-text">Light and comfortable summer dress.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$149.99</span>
                        <a href="Product view.php?product_id=5" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="women"
             data-type="formal"
             data-size="M"
             data-price="89.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/Office Blazer.avif" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Women's Formal</span>
                    <h5 class="card-title mt-2">Office Blazer</h5>
                    <p class="card-text">Professional blazer for workplace elegance.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$89.99</span>
                        <a href="Product view.php?product_id=6" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="accessories"
             data-type="casual"
             data-size="all"
             data-price="79.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/Leather Handbag.webp" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Accessories</span>
                    <h5 class="card-title mt-2">Leather Handbag</h5>
                    <p class="card-text">Elegant leather handbag for daily use.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$79.99</span>
                        <a href="Product view.php?product_id=7" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8 -->
        <div class="col-lg-3 col-md-4 col-sm-6"
             data-gender="accessories"
             data-type="formal"
             data-size="all"
             data-price="199.99">
            <div class="card product-card">
                <img src="./Velvet_vogue_images/Luxury Watch.jpg" class="product-image">
                <div class="card-body">
                    <span class="category-badge">Accessories</span>
                    <h5 class="card-title mt-2">Luxury Watch</h5>
                    <p class="card-text">Premium watch for style and functionality.</p>
                    <div class="d-flex justify-content-between">
                        <span class="product-price">$199.99</span>
                        <a href="Product view.php?product_id=8" class="btn btn-details">Details</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", () => {
    const filters = {
        gender: document.getElementById("genderFilter"),
        type: document.getElementById("clothingType"),
        size: document.getElementById("sizeFilter"),
        price: document.getElementById("priceRange"),
        sort: document.getElementById("sortSelect"),
        search: document.getElementById("searchInput"),
    };

    const productsGrid = document.getElementById("productsGrid");
    const products = Array.from(productsGrid.children);

    function filterProducts() {
        let filtered = products.filter(product => {
            const gender = product.dataset.gender;
            const type = product.dataset.type;
            const size = product.dataset.size;
            const price = parseFloat(product.dataset.price);
            const searchTitle = product.querySelector(".card-title").textContent.toLowerCase();

            // Gender
            if (filters.gender.value !== "all" && gender !== filters.gender.value) return false;

            // Clothing type
            if (filters.type.value !== "all" && type !== filters.type.value) return false;

            // Size
            if (filters.size.value !== "all" && size !== filters.size.value) return false;

            // Price range
            if (filters.price.value !== "all") {
                let [min, max] = filters.price.value.split("-").map(Number);
                if (!(price >= min && price <= max)) return false;
            }

            // Search
            if (filters.search.value.trim() !== "" &&
                !searchTitle.includes(filters.search.value.toLowerCase())) return false;

            return true;
        });

        // Sorting
        filtered.sort((a, b) => {
            let priceA = parseFloat(a.dataset.price);
            let priceB = parseFloat(b.dataset.price);

            if (filters.sort.value === "price-low") return priceA - priceB;
            if (filters.sort.value === "price-high") return priceB - priceA;

            return 0;
        });

        productsGrid.innerHTML = "";
        filtered.forEach(p => productsGrid.appendChild(p));
    }

    Object.values(filters).forEach(input =>
        input.addEventListener("input", filterProducts)
    );
});
</script>

</body>
</html>
