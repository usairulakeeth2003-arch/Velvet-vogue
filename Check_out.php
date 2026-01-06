<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Success</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f4f4f4;
            font-family: "Poppins", sans-serif;
        }

        /* Loading Screen */
        .loading-container {
            text-align: center;
            animation: fadeOut 1s ease forwards;
            animation-delay: 2.5s;
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid #ddd;
            border-top: 8px solid #4CAF50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeOut {
            100% { opacity: 0; visibility: hidden; }
        }

        /* Thank You Message */
        .thankyou-container {
            display: none;
            text-align: center;
            opacity: 0;
            animation: fadeIn 1.2s ease forwards;
            animation-delay: 0.5s;
        }

        @keyframes fadeIn {
            100% { opacity: 1; }
        }

        .tick {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #4CAF50;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px auto;
            animation: pop 0.6s ease forwards;
        }

        @keyframes pop {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        .tick:before {
            content: '✔';
            font-size: 70px;
            color: white;
        }

        h1 {
            font-size: 32px;
            color: #333;
            margin-top: 10px;
        }

        p {
            font-size: 18px;
            color: #555;
        }
    </style>

</head>
<body>

    <!-- Loading Animation -->
    <div class="loading-container" id="loading">
        <div class="spinner"></div>
        <p style="margin-top:15px; font-size:18px; color:#777;">Processing your order...</p>
    </div>

    <!-- Thank You Message -->
    <div class="thankyou-container" id="thankyou">
        <div class="tick"></div>
        <h1>Thank You... Come Again!</h1>
        <p>Your order has been placed successfully.</p>
    </div>

    <script>
        // Show thank you after loading animation
        setTimeout(() => {
            document.getElementById("loading").style.display = "none";
            document.getElementById("thankyou").style.display = "block";
        }, 3000);
    </script>

</body>
</html>
