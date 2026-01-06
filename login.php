<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'velvet_vogue';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
$error = '';
$success = '';
$active_tab = 'login'; // Default active tab

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Login form submitted
        $active_tab = 'login';
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } else {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch();
                    
                    if (password_verify($password, $user['password'])) {
                        // Check if this is admin user with "Admin" in password
                        if ($user['username'] === 'Admin' && stripos($password, 'Admin12345') !== false) {
                            // Redirect to Admin dashboard.php for admin user
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['first_name'] = $user['first_name'];
                            $_SESSION['last_name'] = $user['last_name'];
                            $_SESSION['user_type'] = 'Admin';
                            $_SESSION['logged_in'] = true;
                            
                            header("Location: Admin dashboard.php");
                            exit();
                        } else {
                            // Regular customer login
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['first_name'] = $user['first_name'];
                            $_SESSION['last_name'] = $user['last_name'];
                            $_SESSION['user_type'] = $user['user_type'];
                            $_SESSION['logged_in'] = true;
                            
                            // Update last login
                            $updateStmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                            $updateStmt->execute([$user['id']]);
                            
                            header("Location: ./Home_page.php");
                            exit();
                        }
                    } else {
                        $error = "Invalid username or password.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } catch(PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['register'])) {
        // Register form submitted
        $active_tab = 'register';
        $username = trim($_POST['reg_username']);
        $email = trim($_POST['reg_email']);
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['reg_confirm_password'];
        $first_name = trim($_POST['reg_first_name']);
        $last_name = trim($_POST['reg_last_name']);
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name)) {
            $error = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Username or email already exists.";
                } else {
                    // Hash password and insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, user_type) VALUES (?, ?, ?, ?, ?, 'customer')");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name])) {
                        $success = "Registration successful! You can now login.";
                        $active_tab = 'login';
                        // Clear registration form
                        $_POST['reg_username'] = $_POST['reg_email'] = $_POST['reg_first_name'] = $_POST['reg_last_name'] = '';
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            } catch(PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - Velvet Vogue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #FFFF 0%, #FFFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .auth-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background: #270044ff;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .auth-tabs {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .auth-tabs .nav-link {
            color: #333;
            padding: 15px 30px;
            border: none;
            border-radius: 0;
            font-weight: 500;
        }
        
        .auth-tabs .nav-link.active {
            background: #270044ff;
            color: white;
        }
        
        .auth-tabs .nav-link:not(.active):hover {
            background: #e9ecef;
        }
        
        .auth-form {
            padding: 30px;
        }
        
        .btn-auth {
            background: #270044ff;
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        
        .btn-auth:hover {
            background: #330033;
            color: white;
        }
        
        .form-control:focus {
            border-color: #440044;
            box-shadow: 0 0 0 0.2rem rgba(68, 0, 68, 0.25);
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-left: 4px solid #440044;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        
        .Admin-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-container">
                    <div class="auth-header">
                        <i class="fas fa-gem fa-2x mb-3"></i>
                        <h3>Velvet Vogue</h3>
                        <p class="mb-0">Welcome to our fashion community</p>
                    </div>
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav auth-tabs" id="authTabs">
                        <li class="nav-item w-50">
                            <a class="nav-link <?php echo $active_tab == 'login' ? 'active' : ''; ?>" 
                               data-tab="login">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                        </li>
                        <li class="nav-item w-50">
                            <a class="nav-link <?php echo $active_tab == 'register' ? 'active' : ''; ?>" 
                               data-tab="register">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Messages -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger m-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success m-3 mb-0">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <div class="tab-pane <?php echo $active_tab == 'login' ? 'active' : ''; ?>" id="loginTab">
                        <div class="auth-form">
                            <form method="POST" action="">
                                <input type="hidden" name="login" value="1">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username or Email</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="loginPasswordToggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                                </div>
                                
                                <button type="submit" class="btn btn-auth w-100 mb-3">Sign In</button>
                            </form>
                            
                            <div class="demo-credentials">
                                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Demo Accounts:</h6>
                                <div class="small">
                                    <div><strong>Customer:</strong> Use any registered account</div>
                                    <div><strong>Admin:</strong> username: <strong>Admin</strong> | password: <strong>must contain "Admin"</strong></div>
                                    <div class="mt-1"><em>Example: Admin123, myAdmin, passwordAdmin</em></div>
                                </div>
                            </div>
                            
                            <div class="Admin-note">
                                <h6 class="mb-2"><i class="fas fa-shield-alt me-2"></i>Admin Access:</h6>
                                <div class="small">
                                    <p class="mb-1">To access Admin panel:</p>
                                    <ul class="mb-0">
                                        <li>Username must be <strong>Admin</strong></li>
                                        <li>Password must contain the word <strong>"Admin"</strong> (case insensitive)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Register Form -->
                    <div class="tab-pane <?php echo $active_tab == 'register' ? 'active' : ''; ?>" id="registerTab">
                        <div class="auth-form">
                            <form method="POST" action="" id="registerForm">
                                <input type="hidden" name="register" value="1">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" class="form-control" name="reg_first_name" 
                                                   value="<?php echo isset($_POST['reg_first_name']) ? htmlspecialchars($_POST['reg_first_name']) : ''; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="reg_last_name" 
                                                   value="<?php echo isset($_POST['reg_last_name']) ? htmlspecialchars($_POST['reg_last_name']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="reg_username" 
                                           value="<?php echo isset($_POST['reg_username']) ? htmlspecialchars($_POST['reg_username']) : ''; ?>" 
                                           required>
                                    <div class="form-text">Must be unique. Note: "Admin" username is reserved.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="reg_email" 
                                           value="<?php echo isset($_POST['reg_email']) ? htmlspecialchars($_POST['reg_email']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="reg_password" id="reg_password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="regPasswordToggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2">
                                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                    </div>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="reg_confirm_password" id="reg_confirm_password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="regConfirmPasswordToggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text" id="passwordMatchText"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-auth w-100">Create Account</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p class="mb-0">Already have an account? 
                                    <a href="#" class="text-decoration-none switch-tab" data-tab="login">Sign in here</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Back to Home -->
                    <div class="text-center p-3 border-top">
                        <a href="Home_page.php" class="text-muted">
                            <i class="fas fa-arrow-left me-1"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching functionality
        document.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Update active tab
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                this.classList.add('active');
                
                // Show target tab content
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });
                document.getElementById(targetTab + 'Tab').classList.add('active');
                
                // Clear messages when switching tabs
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.display = 'none';
                });
            });
        });

        // Password toggle functionality
        function setupPasswordToggle(buttonId, inputId) {
            document.getElementById(buttonId).addEventListener('click', function() {
                const passwordInput = document.getElementById(inputId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        // Setup password toggles
        setupPasswordToggle('loginPasswordToggle', 'password');
        setupPasswordToggle('regPasswordToggle', 'reg_password');
        setupPasswordToggle('regConfirmPasswordToggle', 'reg_confirm_password');

        // Password strength indicator
        document.getElementById('reg_password').addEventListener('input', function() {
            updatePasswordStrength(this.value);
            checkPasswordMatch();
        });

        document.getElementById('reg_confirm_password').addEventListener('input', checkPasswordMatch);

        function updatePasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.background = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.background = '#ffc107';
            } else {
                strengthBar.style.background = '#28a745';
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('reg_password').value;
            const confirmPassword = document.getElementById('reg_confirm_password').value;
            const matchText = document.getElementById('passwordMatchText');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
                matchText.className = 'form-text';
            } else if (password === confirmPassword) {
                matchText.textContent = 'Passwords match';
                matchText.className = 'form-text text-success';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'form-text text-danger';
            }
        }

        // Auto-fill demo admin credentials
        document.getElementById('username').addEventListener('focus', function() {
            if (this.value === '') {
                this.value = 'Admin';
            }
        });

        // Show admin password hint
        document.getElementById('password').addEventListener('focus', function() {
            if (document.getElementById('username').value === 'Admin' && this.value === '') {
                this.placeholder = 'Enter password containing "Admin"';
            }
        });
    </script>
</body>
</html>