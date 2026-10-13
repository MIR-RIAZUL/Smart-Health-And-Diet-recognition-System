<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole($_SESSION['user_role'] ?? 'user');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Please contact the administrator.';
            } else {
                loginUser($user);
                setFlashMessage('success', "Welcome back, " . $user['name'] . "!");
                redirectByRole($user['role']);
            }
        } else {
            $error = 'Invalid email address or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to Health Track - Your Smart Health & AI Diet Recognition System. Monitor nutrition, track daily calories, and consult dietitians.">
    <title>Health Track - Sign In</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="split-layout">
        <!-- Left Branding Panel -->
        <div class="left-panel">
            <div class="brand-content">
                <div class="logo-container">
                    <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        <path d="M12 8v6M9 11h6" stroke-linecap="round"/>
                    </svg>
                    <h1 class="logo-text">Health Track</h1>
                </div>
                <p class="subtitle">Smart Health & Diet Recommendation System</p>
                <div class="stats-container">
                    <div class="stat-item">
                        <span class="stat-number">10K+</span>
                        <span class="stat-label">Users</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Satisfaction</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Dietitians</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Login Panel -->
        <div class="right-panel">
            <div class="login-container">
                <div class="logo-container small-logo">
                    <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        <path d="M12 8v6M9 11h6" stroke-linecap="round"/>
                    </svg>
                    <h2 class="logo-text">Health Track</h2>
                </div>

                <h1 class="welcome-text">Welcome!</h1>

                <?php renderFlashMessage(); ?>

                <?php if (!empty($error)): ?>
                    <div style="background: rgba(231, 76, 60, 0.15); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(231, 76, 60, 0.4); color: #ff6b6b; padding: 14px 18px; border-radius: 14px; margin-bottom: 24px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="login.php" id="loginForm">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@example.com" required autocomplete="email">
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                    
                    <div class="forgot-password">
                        <a href="reset-password.html">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="login-btn">Log in</button>
                </form>

                <div class="create-account" style="margin-top: 20px;">
                    <span style="color: #8c8c8c; font-size: 15px;">Don't have an account? </span>
                    <a href="register.php">Create Account</a>
                </div>

                <!-- Glassmorphic 1-Click Demo Credentials Box -->
                <div style="margin-top: 30px; padding: 18px 20px; background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-weight: 700; color: #ffffff; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #2ecc71; box-shadow: 0 0 8px #2ecc71;"></span>
                            Instant Demo Credentials
                        </span>
                        <span style="font-size: 11px; color: #8c909a;">Click to Auto-fill</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                        <button type="button" onclick="fillDemo('admin@healthtrack.com', 'admin123')" style="background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.25); color: #2ecc71; padding: 8px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; cursor: pointer; text-align: left; transition: all 0.2s ease;">
                            🛡️ Admin
                        </button>
                        <button type="button" onclick="fillDemo('sarah@healthtrack.com', 'dietitian123')" style="background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.25); color: #3498db; padding: 8px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; cursor: pointer; text-align: left; transition: all 0.2s ease;">
                            🩺 Dietitian
                        </button>
                        <button type="button" onclick="fillDemo('alice@example.com', 'user123')" style="background: rgba(242, 80, 12, 0.1); border: 1px solid rgba(242, 80, 12, 0.25); color: #ff6b35; padding: 8px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; cursor: pointer; text-align: left; transition: all 0.2s ease;">
                            👤 Patient (Alice)
                        </button>
                        <button type="button" onclick="fillDemo('james@healthtrack.com', 'dietitian123')" style="background: rgba(243, 156, 18, 0.1); border: 1px solid rgba(243, 156, 18, 0.25); color: #f39c12; padding: 8px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; cursor: pointer; text-align: left; transition: all 0.2s ease;">
                            ⏳ Pending Dietitian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(email, password) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            emailInput.value = email;
            passInput.value = password;
            
            // Visual pulse feedback on input fields
            [emailInput, passInput].forEach(el => {
                el.style.borderColor = '#f2500c';
                el.style.boxShadow = '0 0 15px rgba(242, 80, 12, 0.3)';
                setTimeout(() => {
                    el.style.borderColor = '';
                    el.style.boxShadow = '';
                }, 400);
            });
        }
    </script>
</body>
</html>
