<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Check if already logged in
$alreadyLoggedIn = isLoggedIn();
$loggedInUser = $alreadyLoggedIn ? getCurrentUser() : null;

$error = '';
$fullname = '';
$email = '';
$phone = '';
$role = 'user';
$qualification = '';
$specialization = '';
$gender = 'other';
$age = 25;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['user', 'dietitian']) ? $_POST['role'] : 'user';
    $qualification = trim($_POST['qualification'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    // Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = getDBConnection();

        // Check if email is already taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            // Hash password securely with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $status = ($role === 'dietitian') ? 'pending' : 'active';

            $pdo->beginTransaction();
            try {
                $insertUser = $pdo->prepare("
                    INSERT INTO users (name, email, password, phone, role, status) 
                    VALUES (:name, :email, :password, :phone, :role, :status)
                ");
                $insertUser->execute([
                    'name'     => $fullname,
                    'email'    => $email,
                    'password' => $hashedPassword,
                    'phone'    => $phone,
                    'role'     => $role,
                    'status'   => $status
                ]);
                $newUserId = (int)$pdo->lastInsertId();

                if ($role === 'dietitian') {
                    // Create dietitian profile with pending status
                    $insertDietitian = $pdo->prepare("
                        INSERT INTO dietitian_profiles (user_id, qualification, specialization, approval_status)
                        VALUES (:uid, :qual, :spec, 'pending')
                    ");
                    $insertDietitian->execute([
                        'uid'  => $newUserId,
                        'qual' => $qualification ?: 'Registered Nutritionist / Dietitian',
                        'spec' => $specialization ?: 'General Nutrition'
                    ]);
                } else {
                    // Create initial health profile for user
                    $insertHealth = $pdo->prepare("
                        INSERT INTO health_profiles (user_id, age, gender, height, weight, activity_level, health_goal, daily_calorie_target)
                        VALUES (:uid, 25, 'male', 170.00, 70.00, 'sedentary', 'maintain_weight', 2000)
                    ");
                    $insertHealth->execute(['uid' => $newUserId]);
                }

                $pdo->commit();

                // If a user was previously logged in, reset the session for the new account
                if ($alreadyLoggedIn) {
                    logoutUser();
                }

                // Log in newly created user
                $newUserRecord = [
                    'id'    => $newUserId,
                    'name'  => $fullname,
                    'email' => $email,
                    'role'  => $role
                ];
                loginUser($newUserRecord);

                if ($role === 'dietitian') {
                    setFlashMessage('info', 'Your dietitian registration was received and is pending admin approval.');
                    header("Location: dietitian/pending.php");
                } else {
                    setFlashMessage('success', 'Account created successfully! Welcome to Health Track. Please enter your health information.');
                    header("Location: user/health-info.php");
                }
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to create account. Please try again: ' . $e->getMessage();
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
    <meta name="description" content="Create an account on Health Track - Smart Health & AI Diet Recognition System. Personalized nutrition, macro logs, and certified dietitians.">
    <title>Health Track - Create Account</title>
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

        <!-- Right Registration Panel -->
        <div class="right-panel">
            <div class="login-container">
                <a href="login.php" class="back-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Login
                </a>

                <div class="logo-container small-logo">
                    <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        <path d="M12 8v6M9 11h6" stroke-linecap="round"/>
                    </svg>
                    <span class="logo-text-small" style="font-size: 22px; font-weight: 700; color: white;">Health Track</span>
                </div>

                <h1 class="page-title">Create account</h1>
                <p class="form-subtitle">Join thousands on their health journey</p>

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

                <?php if ($alreadyLoggedIn && $loggedInUser): ?>
                    <div style="background: rgba(242, 80, 12, 0.1); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(242, 80, 12, 0.35); border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; font-size: 13px; color: #f0f2f5;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #f2500c; box-shadow: 0 0 8px #f2500c;"></span>
                            <span style="color: #ff6b35; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Active Session</span>
                        </div>
                        <p style="margin-top: 6px; color: #d0d4dc;">
                            <span style="color: #f2500c; font-weight: 600;">Note:</span> You are currently signed in as <strong><?= e($loggedInUser['name']) ?></strong> (<?= e(ucfirst($loggedInUser['role'])) ?>).
                        </p>
                        <div style="margin-top: 10px; display: flex; gap: 14px; align-items: center; font-size: 13px;">
                            <a href="<?= ($loggedInUser['role'] === 'admin') ? 'admin/dashboard.php' : (($loggedInUser['role'] === 'dietitian') ? 'dietitian/dashboard.php' : 'user/dashboard.php') ?>" style="color: #ff6b35; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Go to Dashboard &rarr;
                            </a>
                            <span style="color: #606470;">•</span>
                            <a href="logout.php?redirect=register.php" style="color: #b0b4bd; text-decoration: none; hover:underline;">Sign Out</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="register.php">
                    <div class="input-row">
                        <div class="input-group">
                            <label for="fullname">Full Name *</label>
                            <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>" placeholder="e.g. John Doe" required autocomplete="name">
                        </div>
                        <div class="input-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" placeholder="e.g. +1 234 567 8900" autocomplete="tel">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="email">Email address *</label>
                        <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="name@example.com" required autocomplete="email">
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
                        </div>
                        <div class="input-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="role-group">
                        <span class="role-label">I am registering as:</span>
                        <div class="role-options">
                            <label class="role-option">
                                <input type="radio" name="role" value="user" <?= ($role === 'user') ? 'checked' : '' ?> onchange="toggleDietitianFields()">
                                <span class="role-btn">User / Patient</span>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="dietitian" <?= ($role === 'dietitian') ? 'checked' : '' ?> onchange="toggleDietitianFields()">
                                <span class="role-btn">Dietitian / Nutritionist</span>
                            </label>
                        </div>
                    </div>

                    <!-- Additional Dietitian specific fields (shown only if Dietitian role is selected) -->
                    <div id="dietitianExtraFields" style="display: <?= ($role === 'dietitian') ? 'block' : 'none' ?>; margin-bottom: 20px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 18px 16px;">
                        <div class="input-row">
                            <div class="input-group">
                                <label for="qualification">Qualifications / Degree</label>
                                <input type="text" id="qualification" name="qualification" value="<?= e($qualification) ?>" placeholder="e.g. M.Sc. Clinical Nutrition, RD">
                            </div>
                            <div class="input-group">
                                <label for="specialization">Primary Specialization</label>
                                <input type="text" id="specialization" name="specialization" value="<?= e($specialization) ?>" placeholder="e.g. Sports Nutrition, Diabetes">
                            </div>
                        </div>
                        <p style="font-size: 12px; color: #8c909a; margin-top: 8px;">
                            Note: Dietitian accounts are subject to administrative review before being enabled.
                        </p>
                    </div>
                    
                    <button type="submit" class="primary-btn">Create Account</button>
                </form>

                <p class="auth-footer">Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        function toggleDietitianFields() {
            const role = document.querySelector('input[name="role"]:checked').value;
            const extraFields = document.getElementById('dietitianExtraFields');
            if (role === 'dietitian') {
                extraFields.style.display = 'block';
            } else {
                extraFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>
