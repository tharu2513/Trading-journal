<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$auth = new Auth();

if ($auth->isLoggedIn()) { header('Location: /trading/dashboard.php'); exit; }
if ($auth->isAdminLoggedIn()) { header('Location: /trading/admin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRF($csrf)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Try Admin Login first
        $adminResult = $auth->adminLogin($email, $password);
        if ($adminResult['success']) {
            header('Location: /trading/admin/dashboard.php'); exit;
        }
        
        // If not admin, try User Login
        $userResult = $auth->login($email, $password);
        if ($userResult['success']) {
            header('Location: /trading/dashboard.php'); exit;
        }
        
        // Both failed
        $error = 'Invalid credentials. Please check your email and password.';
    }
}
$csrfToken = $auth->generateCSRF();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Crypto Trading Journal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #2563EB;
            --primary-dark: #1E40AF;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        
        /* Animated Background Elements */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.5;
            animation: float 10s infinite ease-in-out alternate;
            z-index: 1;
        }
        .shape-1 { width: 400px; height: 400px; background: #2563EB; top: -100px; left: -100px; }
        .shape-2 { width: 300px; height: 300px; background: #06B6D4; bottom: -50px; right: -50px; animation-delay: -5s; }
        
        @keyframes float {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-30px) scale(1.1); }
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-area .icon-box {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), #06B6D4);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin-bottom: 15px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }
        .logo-area h1 {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .logo-area p {
            color: #94A3B8;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .input-group label {
            display: block;
            color: #CBD5E1;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            margin-left: 2px;
        }
        .input-field {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 16px 14px 45px;
            color: white;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            background: rgba(15, 23, 42, 0.8);
        }
        .input-icon {
            position: absolute;
            bottom: 16px;
            left: 16px;
            color: #64748B;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        .input-field:focus + .input-icon, .input-field:not(:placeholder-shown) + .input-icon {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(37, 99, 235, 0.4);
            background: linear-gradient(135deg, #3B82F6, var(--primary));
        }
        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links {
            margin-top: 25px;
            text-align: center;
            color: #94A3B8;
            font-size: 14px;
        }
        .footer-links a {
            color: var(--primary-lighter);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .footer-links a:hover {
            color: white;
        }
    </style>
</head>
<body>

    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="login-wrapper">
        <div class="glass-card">
            
            <div class="logo-area">
                <div class="icon-box">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="input-field" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" class="input-field" placeholder="Enter your password" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer-links">
                Don't have an account? <a href="/trading/signup.php">Create one</a>
            </div>

        </div>
    </div>

    <!-- Script to adjust icon color dynamically on input -->
    <script>
        document.querySelectorAll('.input-field').forEach(input => {
            // Initial check
            if(input.value.trim() !== '') {
                input.nextElementSibling.style.color = '#2563EB';
            }
            // On type
            input.addEventListener('input', function() {
                if(this.value.trim() !== '') {
                    this.nextElementSibling.style.color = '#2563EB';
                } else {
                    this.nextElementSibling.style.color = '#64748B';
                }
            });
        });
    </script>
</body>
</html>
