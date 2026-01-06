<?php
// Database connection
$host = '127.0.0.1';
$dbname = 'velvet_vogue';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get featured products
    $featured_products = $pdo->query("SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT 4")->fetchAll();
    
    // Get men's products
    $mens_products = $pdo->query("SELECT * FROM products WHERE gender = 'men' ORDER BY created_at DESC LIMIT 4")->fetchAll();
    
    // Get women's products
    $womens_products = $pdo->query("SELECT * FROM products WHERE gender = 'women' ORDER BY created_at DESC LIMIT 4")->fetchAll();
    
    // Get formal wear
    $formal_products = $pdo->query("SELECT * FROM products WHERE clothing_type = 'formal' ORDER BY created_at DESC LIMIT 4")->fetchAll();
    
    // Get casual wear
    $casual_products = $pdo->query("SELECT * FROM products WHERE clothing_type = 'casual' ORDER BY created_at DESC LIMIT 4")->fetchAll();
    
} catch(PDOException $e) {
    $error = "Unable to load products. Please try again later.";
    $featured_products = $mens_products = $womens_products = $formal_products = $casual_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />


        <!-- After the title tag -->
    <meta name="description" content="Velvet Vogue - Premium fashion boutique offering elegant dresses, men's & women's wear, formal and casual clothing collections. Discover luxury fashion with modern trends and timeless sophistication.">
    <meta name="keywords" content="fashion boutique, elegant dresses, men's wear, women's wear, formal wear, casual clothing, luxury fashion, Sri Lanka fashion, Colombo boutique">
    <meta name="author" content="Velvet Vogue">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.velvetvogue.com/">
    <meta property="og:title" content="Velvet Vogue - Premium Fashion Boutique">
    <meta property="og:description" content="Discover our exclusive collection of elegant dresses and premium fashion wear for every occasion.">
    <meta property="og:image" content="https://www.velvetvogue.com/images/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://www.velvetvogue.com/">
    <meta property="twitter:title" content="Velvet Vogue - Premium Fashion Boutique">
    <meta property="twitter:description" content="Discover our exclusive collection of elegant dresses and premium fashion wear for every occasion.">
    <meta property="twitter:image" content="https://www.velvetvogue.com/images/twitter-image.jpg">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://www.velvetvogue.com/">

    <!-- Schema.org structured data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ClothingStore",
      "name": "Velvet Vogue",
      "image": "https://www.velvetvogue.com/images/logo.jpg",
      "description": "Premium fashion boutique offering elegant dresses and luxury clothing collections",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "21 Fashion Street",
        "addressLocality": "Colombo",
        "addressRegion": "Western Province",
        "postalCode": "00100",
        "addressCountry": "LK"
      },
      "telephone": "+94771234567",
      "email": "info@velvetvogue.com",
      "url": "https://www.velvetvogue.com/",
      "priceRange": "$$",
      "openingHours": "Mo-Sa 10:00-20:00",
      "sameAs": [
        "https://www.facebook.com/velvetvogue",
        "https://www.instagram.com/velvetvogue"
      ]
    }
    </script>



    <link rel="icon" type="image/png" href="./Velvet_vogue_images/velvetvogue.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Velvet Vogue - Home</title>
    <style>
        :root {
            --primary: #630678ff;
            --primary-dark: #71086aff;
            --secondary: #ff4081;
            --light: #f5f5f5;
            --dark: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f9f9f9;
        }

        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 10vh;
        }

        .right-container {
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .top-container {
            padding: 20px 50px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            height: 60px;
            width: auto;
        }

        .top-items-container {
            border: 2px solid var(--primary);
            padding: 12px 25px;
            border-radius: 10px;
            background: var(--primary);
            transition: all 0.3s;
        }

        .top-items-container:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .top-items-container a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }

        .cart-container {
            padding: 50px;
            background: white;
            margin: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .carousel-item img {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }

        .carousel-caption {
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 20px;
        }

        .Main-banner-container-border {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            gap: 50px;
            padding: 50px;
            justify-content: space-around;
            align-items: center;
            color: white;
            margin: 20px;
            border-radius: 15px;
        }

        .description h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .description h2 {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .description h3 {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.8;
        }

        .logo4 {
            width: 300px;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .section-title {
            text-align: center;
            margin: 40px 0 30px 0;
            color: var(--primary);
            font-size: 2rem;
            font-weight: bold;
        }

        .third-banner-container {
            padding: 20px 50px;
        }

        .selection-container {
            display: flex;
            flex-direction: row;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            overflow: hidden;
            width: 18rem;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .card-img-top {
            height: 250px;
            object-fit: cover;
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .card-text {
            color: var(--dark);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 50px 0 20px;
            margin-top: 50px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-about h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .footer-about p {
            line-height: 1.6;
            opacity: 0.9;
        }

        .social-medias h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .social-medias a {
            display: block;
            color: white;
            text-decoration: none;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .social-medias a:hover {
            color: var(--secondary);
            transform: translateX(5px);
        }

        .footer-links h3,
        .footer-contact h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-links a:hover {
            color: var(--secondary);
        }

        .footer-contact p {
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icons a {
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .social-icons a:hover {
            color: var(--secondary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .footer-bottom span {
            color: var(--secondary);
            font-weight: bold;
        }

        .empty-section {
            text-align: center;
            padding: 40px;
            color: #666;
            background: white;
            border-radius: 10px;
            margin: 20px 0;
            width: 100%;
        }

        .empty-section i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .admin-link {
            background: var(--secondary);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin-left: 15px;
            transition: all 0.3s;
        }

        .admin-link:hover {
            background: #e91e63;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .top-container {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
            }
            
            .Main-banner-container-border {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
                margin: 10px;
            }
            
            .selection-container {
                gap: 20px;
            }
            
            .card {
                width: 100% !important;
                max-width: 300px;
            }
            
            .logo4 {
                width: 250px;
                margin-top: 20px;
            }
            
            .cart-container {
                padding: 20px;
                margin: 10px;
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

        <!-- Carousel -->
        <div class="cart-container">
          <div id="carouselExampleCaptions" class="carousel slide">
            <div class="carousel-indicators">
              <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
              <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
              <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
              <div class="carousel-item active">
                <img src="Velvet_vogue_images/Banner 4.avif" class="d-block w-100" alt="Fashion Collection">
                <div class="carousel-caption d-none d-md-block">
                  <h5>New Collection 2025</h5>
                  <p>Discover our latest fashion trends and exclusive designs</p>
                </div>
              </div>
              <div class="carousel-item">
                <img src="Velvet_vogue_images/Banner 5.jpg" class="d-block w-100" alt="Seasonal Offers">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Seasonal Offers</h5>
                  <p>Get up to 50% off on selected items</p>
                </div>
              </div>
              <div class="carousel-item">
                <img src="Velvet_vogue_images/Banner 3.jpeg" class="d-block w-100" alt="Premium Quality">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Premium Quality</h5>
                  <p>Experience luxury with our premium fabric collection</p>
                </div>
              </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>

        <!-- Main Banner -->
        <div class="Main-banner-container-border">
          <div class="description">
            <div>
              <h1>Wear clothes that matter...</h1>
              <h2>Velvet Vogue is a stylish boutique that offers a wide range of elegant dresses for every occasion, combining modern fashion trends with timeless sophistication.</h2>
              <h3>A premier dress shop known for its luxurious collection of evening gowns, party wear, and casual dresses. The boutique emphasizes quality fabrics, unique designs, and personalized customer service.</h3>
            </div>
          </div>
          
          <div class="img4">
            <img src="./Velvet_vogue_images/PROFFESSIONALS.jpg" alt="Professional Collection" class="logo4" />
          </div>
        </div>

        <!-- Featured Products -->
        <div class="section-title">Featured Products</div>
        <div class="third-banner-container">
          <div class="selection-container">
            <?php if (!empty($featured_products)): ?>
              <?php foreach ($featured_products as $product): ?>
                <div class="card">
                  <img src="<?php echo $product['image_url'] ?: './Velvet_vogue_images/velvetvogue.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                    <p class="card-text"><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>
                    <a href="./Product Categories.php" class="btn btn-primary">See more...</a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-section">
                <i class="fas fa-box-open"></i>
                <h4>No Featured Products</h4>
                <p>Check back later for featured items</p>
              </div>
            <?php endif; ?>

            
          </div>
        </div>

        <!-- Men's Wear -->
        <div class="section-title">New Arrivals</div>
        <div class="third-banner-container">
          <div class="selection-container">
            <?php if (!empty($mens_products)): ?>
              <?php foreach ($mens_products as $product): ?>
                <div class="card">
                  <img src="<?php echo $product['image_url'] ?: './Velvet_vogue_images/velvetvogue.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                    <p class="card-text"><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>
                    <a href="./Product Categories.php" class="btn btn-primary">See more...</a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-section">
                <i class="fas fa-tshirt"></i>
                <h4>No Men's Products</h4>
                <p>Men's collection coming soon</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Women's Wear -->
        <div class="section-title">Women's Wear</div>
        <div class="third-banner-container">
          <div class="selection-container">
            <?php if (!empty($womens_products)): ?>
              <?php foreach ($womens_products as $product): ?>
                <div class="card">
                  <img src="<?php echo $product['image_url'] ?: './Velvet_vogue_images/velvetvogue.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                    <p class="card-text"><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>
                    <a href="./Product Categories.php" class="btn btn-primary">See more...</a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-section">
                <i class="fas fa-female"></i>
                <h4>No Women's Products</h4>
                <p>Women's collection coming soon</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Formal Wear -->
        <div class="section-title">Formal Wear</div>
        <div class="third-banner-container">
          <div class="selection-container">
            <?php if (!empty($formal_products)): ?>
              <?php foreach ($formal_products as $product): ?>
                <div class="card">
                  <img src="<?php echo $product['image_url'] ?: './Velvet_vogue_images/velvetvogue.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                    <p class="card-text"><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>
                    <a href="./Product Categories.php" class="btn btn-primary">See more...</a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-section">
                <i class="fas fa-user-tie"></i>
                <h4>No Formal Wear</h4>
                <p>Formal collection coming soon</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Casual Wear -->
        <div class="section-title">Seasonal offers</div>
        <div class="third-banner-container">
          <div class="selection-container">
            <?php if (!empty($casual_products)): ?>
              <?php foreach ($casual_products as $product): ?>
                <div class="card">
                  <img src="<?php echo $product['image_url'] ?: './Velvet_vogue_images/Gown.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                    <p class="card-text"><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>
                    <a href="./Product Categories.php" class="btn btn-primary">See more...</a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-section">
                <i class="fas fa-tshirt"></i>
                <h4>No Casual Wear</h4>
                <p>Casual collection coming soon</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
          <div class="footer-container">
            <div class="footer-about">
              <h2>Velvet Vogue</h2>
              <p>Step into elegance and confidence. At Velvet Vogue, we design dresses that make you feel as beautiful as you look.</p>
            </div>

            <div class="social-medias">
              <h2>VISIT OUR PAGES</h2>
              <a href="https://www.facebook.com" target="_blank">Facebook</a>
              <a href="https://www.google.com" target="_blank">Google</a>
              <a href="https://www.whatsapp.com" target="_blank">Whatsapp</a>
            </div>

            <div class="footer-links">
              <h3>Quick Links</h3>
              <ul>
                <li><a href="./Home_page.php">Home</a></li>
                <li><a href="#">Shop</a></li>
                <li><a href="./Product Categories.php">Collections</a></li>
                <li><a href="./About_us.php">About Us</a></li>
                <li><a href="./inquiry.php">Contact</a></li>
              </ul>
            </div>

            <div class="footer-contact">
              <h3>Contact Us</h3>
              <p>Email: info@velvetvogue.com</p>
              <p>Phone: +94 77 123 4567</p>
              <p>Address: 21 Fashion Street, Colombo, Sri Lanka</p>

              <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-google"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
              </div>
            </div>
          </div>

          <div class="footer-bottom">
            <p>© 2025 <span>Velvet Vogue</span>. All Rights Reserved.</p>
          </div>
        </footer>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>