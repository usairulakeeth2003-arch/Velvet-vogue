<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'velvet_vogue');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Create connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn->connect_error) {
            // Prepare statement
            $stmt = $conn->prepare("INSERT INTO subscriptions (email) VALUES (?)");
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $subscriptionMessage = '<div style="color: #4CAF50; margin-top: 10px; font-weight: bold;">Subscription added successfully!</div>';
            } else {
                if ($conn->errno == 1062) {
                    $subscriptionMessage = '<div style="color: #f44336; margin-top: 10px; font-weight: bold;">This email is already subscribed.</div>';
                } else {
                    $subscriptionMessage = '<div style="color: #f44336; margin-top: 10px; font-weight: bold;">An error occurred. Please try again.</div>';
                }
            }
            
            $stmt->close();
            $conn->close();
        } else {
            $subscriptionMessage = '<div style="color: #f44336; margin-top: 10px; font-weight: bold;">Database connection failed.</div>';
        }
    } else {
        $subscriptionMessage = '<div style="color: #f44336; margin-top: 10px; font-weight: bold;">Invalid email address.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Velvet Vogue</title>

    <style>
        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background-color: #f5f5f5;
        }

        .header {
            background-color: #111;
            padding: 20px 40px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .about-section {
            padding: 80px 10%;
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .about-image img {
            width: 450px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .about-text h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #333;
        }

        .about-text p {
            line-height: 1.8;
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
        }

        .mission-box {
            margin-top: 50px;
            padding: 40px;
            background-color: white;
            border-left: 5px solid #ff7f50;
            border-radius: 5px;
        }

        .mission-box h3 {
            margin: 0 0 10px 0;
            font-size: 26px;
            color: #222;
        }

        .mission-box p {
            font-size: 17px;
            color: #444;
        }

        footer {
            background-color: #111;
            color: white;
            padding: 40px 10%;
            text-align: center;
            margin-top: 60px;
        }

        /* Subscription Box Styling */
        .subscribe-box {
            margin-bottom: 20px;
        }

        .subscribe-box input[type="email"] {
            padding: 10px 15px;
            width: 250px;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
        }

        .subscribe-box button {
            padding: 10px 20px;
            background-color: #ff7f50;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        .subscribe-box button:hover {
            background-color: #ff5620;
        }

        .success-message {
            color: #4CAF50;
            margin-top: 10px;
            font-weight: bold;
        }

        .error-message {
            color: #f44336;
            margin-top: 10px;
            font-weight: bold;
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

    <!-- About Section -->
    <section class="about-section">
        <div class="about-image">
            <img src="./Velvet_vogue_images/fashion-clothing-shop-retail-formal-fashion-clothing-shop-retail-formal-101679178.webp" alt="Velvet Vogue Store">
        </div>

        <div class="about-text">
            <h2>About Velvet Vogue</h2>
            <p>
                Velvet Vogue was founded by <strong>John Finlo</strong>, an ambitious entrepreneur
                with a passion for fashion, creativity, and self-expression.
                Our store provides a carefully curated collection of trendy casualwear and elegant
                formal wear designed for young adults who want to showcase their unique style.
            </p>

            <p>
                From stylish outfits to essential accessories, Velvet Vogue is committed
                to offering a seamless and enjoyable shopping experience. Our goal is to make fashion
                accessible, exciting, and personal for every customer.
            </p>

            <div class="mission-box">
                <h3>Our Mission</h3>
                <p>
                    To empower individuals to express their identity through modern and stylish clothing.
                    We focus on quality, comfort, and design—ensuring every piece helps you feel confident
                    and fashionable.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <!-- Subscription Box -->
        <div class="subscribe-box">
            <?php
            // Display subscription message if exists
            if (isset($subscriptionMessage)) {
                echo $subscriptionMessage;
            }
            ?>
            
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Enter your email..." required>
                <button type="submit">Subscribe</button>
            </form>
        </div>

        <p>&copy; 2024 Velvet Vogue. All rights reserved.</p>
    </footer>

</body>
</html>