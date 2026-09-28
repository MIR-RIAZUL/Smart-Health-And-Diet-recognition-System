<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('user');
$currentUser = getCurrentUser();
$pdo = getDBConnection();
$userId = (int)$currentUser['id'];

$error = '';
$success = '';

// Fetch existing profile if available
$stmt = $pdo->prepare("SELECT * FROM health_profiles WHERE user_id = :uid LIMIT 1");
$stmt->execute(['uid' => $userId]);
$profile = $stmt->fetch();

// Default values
$age = (int)($profile['age'] ?? 25);
$weight = (float)($profile['weight'] ?? 70.0);
$height = (float)($profile['height'] ?? 170.0);
$goalWeight = (float)($profile['weight'] ?? 65.0);
$gender = $profile['gender'] ?? 'female';
$healthGoal = $profile['health_goal'] ?? 'maintain_weight';
$activityLevel = $profile['activity_level'] ?? 'moderate';
$dietaryPreference = $profile['dietary_preference'] ?? 'anything';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = (int)($_POST['age'] ?? $age);
    $weight = (float)($_POST['current_weight'] ?? $weight);
    $height = (float)($_POST['height'] ?? $height);
    $goalWeight = (float)($_POST['goal_weight'] ?? $weight);
    $gender = in_array($_POST['gender'] ?? '', ['male', 'female', 'other']) ? $_POST['gender'] : 'female';
    $healthGoal = trim($_POST['health_goal'] ?? 'maintain_weight');
    $activityLevel = trim($_POST['activity_level'] ?? 'moderate');
    $dietaryPreference = trim($_POST['diet'] ?? 'anything');

    if ($age <= 0 || $weight <= 0 || $height <= 0) {
        $error = 'Please enter valid numbers for age, current weight, and height.';
    } else {
        // Calculate BMI & Calorie Target
        $bmiData = calculateBMI($weight, $height);
        $bmi = $bmiData['bmi'];
        $calorieTarget = calculateDailyCalorieTarget($weight, $height, $age, $gender, $activityLevel, $healthGoal);

        // Update health_profiles table
        $stmt = $pdo->prepare("
            INSERT INTO health_profiles (user_id, age, gender, height, weight, activity_level, health_goal, dietary_preference, daily_calorie_target, bmi, updated_at)
            VALUES (:uid, :age, :gender, :height, :weight, :activity, :goal, :diet, :cal, :bmi, NOW())
            ON DUPLICATE KEY UPDATE 
                age = VALUES(age), 
                gender = VALUES(gender), 
                height = VALUES(height), 
                weight = VALUES(weight), 
                activity_level = VALUES(activity_level), 
                health_goal = VALUES(health_goal), 
                dietary_preference = VALUES(dietary_preference), 
                daily_calorie_target = VALUES(daily_calorie_target), 
                bmi = VALUES(bmi), 
                updated_at = NOW()
        ");
        $stmt->execute([
            'uid'      => $userId,
            'age'      => $age,
            'gender'   => $gender,
            'height'   => $height,
            'weight'   => $weight,
            'activity' => $activityLevel,
            'goal'     => $healthGoal,
            'diet'     => $dietaryPreference,
            'cal'      => $calorieTarget,
            'bmi'      => $bmi
        ]);

        // Record weight in weight_logs
        $weightLogStmt = $pdo->prepare("
            INSERT INTO weight_logs (user_id, weight, log_date)
            VALUES (:uid, :weight, CURDATE())
            ON DUPLICATE KEY UPDATE weight = VALUES(weight)
        ");
        $weightLogStmt->execute(['uid' => $userId, 'weight' => $weight]);

        setFlashMessage('success', "Health profile updated successfully! Your estimated BMI is {$bmi} ({$bmiData['category']}) with a daily target of {$calorieTarget} kcal.");
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Setup Health Information</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container small-logo">
                    <div class="filled-heart-icon">
                        <svg viewBox="0 0 24 24" fill="white" width="16" height="16">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <span class="logo-text-small">Health Track</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link back-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Dashboard
                </a>
                
                <a href="health-info.php" class="nav-link active">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    Health Information
                </a>
                <a href="../log-meals.html" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    Log Meals
                </a>
                <a href="../calorie-reports.html" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>
                    Calorie Reports
                </a>
                <a href="#" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    My Diet Plans
                </a>
            </nav>

            <div class="sidebar-footer">
                <div style="font-size: 13px; color: var(--text-light); font-weight: 600; margin-bottom: 2px;"><?= e($currentUser['name']) ?></div>
                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 14px;"><?= e($currentUser['email']) ?></div>
                <a href="../logout.php" class="nav-link sign-out">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-header">
                <h1 class="header-title">Setup Health Profile</h1>
            </header>

            <div class="content-wrapper">
                
                <?php renderFlashMessage(); ?>

                <?php if (!empty($error)): ?>
                    <div style="background-color: rgba(231, 76, 60, 0.15); border: 1px solid #e74c3c; color: #e74c3c; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500;">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <div style="background-color: rgba(52, 152, 219, 0.08); border-left: 4px solid #3498db; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: #b0b4bd; line-height: 1.5;">
                    <strong style="color: #3498db;">Personalized Biometrics:</strong> Please input your basic physical metrics to calculate your Body Mass Index (BMI) and daily caloric targets. This information will help your dietitian construct custom nutritional guidance.
                </div>

                <form class="health-info-form" action="health-info.php" method="POST">
                    
                    <!-- Basic Information Card -->
                    <div class="form-card">
                        <h2 class="card-title">Basic Biometrics</h2>
                        
                        <div class="input-grid">
                            <div class="input-group">
                                <label>AGE (YEARS) *</label>
                                <input type="number" name="age" value="<?= e($age) ?>" min="10" max="120" required>
                            </div>
                            <div class="input-group">
                                <label>CURRENT WEIGHT (KG) *</label>
                                <input type="number" name="current_weight" value="<?= e($weight) ?>" step="0.1" min="20" max="300" required>
                            </div>
                            <div class="input-group">
                                <label>HEIGHT (CM) *</label>
                                <input type="number" name="height" value="<?= e($height) ?>" min="50" max="250" required>
                            </div>
                            <div class="input-group">
                                <label>TARGET GOAL WEIGHT (KG)</label>
                                <input type="number" name="goal_weight" value="<?= e($goalWeight) ?>" step="0.1" min="20" max="300">
                            </div>
                        </div>

                        <div class="pill-group">
                            <label class="pill-group-label">GENDER *</label>
                            <div class="pill-options">
                                <label class="pill-radio"><input type="radio" name="gender" value="male" <?= ($gender === 'male') ? 'checked' : '' ?>><span class="pill-btn">Male</span></label>
                                <label class="pill-radio"><input type="radio" name="gender" value="female" <?= ($gender === 'female') ? 'checked' : '' ?>><span class="pill-btn">Female</span></label>
                                <label class="pill-radio"><input type="radio" name="gender" value="other" <?= ($gender === 'other') ? 'checked' : '' ?>><span class="pill-btn">Other</span></label>
                            </div>
                        </div>
                    </div>

                    <!-- Goals & Activity Card -->
                    <div class="form-card">
                        <h2 class="card-title">Health Goals & Lifestyle</h2>
                        
                        <div class="pill-group">
                            <label class="pill-group-label">PRIMARY HEALTH GOAL *</label>
                            <div class="pill-options">
                                <label class="pill-radio"><input type="radio" name="health_goal" value="weight_loss" <?= ($healthGoal === 'weight_loss') ? 'checked' : '' ?>><span class="pill-btn">Weight Loss</span></label>
                                <label class="pill-radio"><input type="radio" name="health_goal" value="weight_gain" <?= ($healthGoal === 'weight_gain' || $healthGoal === 'muscle_gain') ? 'checked' : '' ?>><span class="pill-btn">Muscle / Weight Gain</span></label>
                                <label class="pill-radio"><input type="radio" name="health_goal" value="maintain_weight" <?= ($healthGoal === 'maintain_weight' || $healthGoal === 'maintenance') ? 'checked' : '' ?>><span class="pill-btn">Maintain Weight</span></label>
                                <label class="pill-radio"><input type="radio" name="health_goal" value="general_fitness" <?= ($healthGoal === 'general_fitness' || $healthGoal === 'general_health') ? 'checked' : '' ?>><span class="pill-btn">General Fitness</span></label>
                            </div>
                        </div>

                        <div class="pill-group">
                            <label class="pill-group-label">PHYSICAL ACTIVITY LEVEL *</label>
                            <div class="pill-options">
                                <label class="pill-radio"><input type="radio" name="activity_level" value="sedentary" <?= ($activityLevel === 'sedentary') ? 'checked' : '' ?>><span class="pill-btn">Sedentary (Desk Job)</span></label>
                                <label class="pill-radio"><input type="radio" name="activity_level" value="light" <?= ($activityLevel === 'light') ? 'checked' : '' ?>><span class="pill-btn">Lightly Active</span></label>
                                <label class="pill-radio"><input type="radio" name="activity_level" value="moderate" <?= ($activityLevel === 'moderate' || $activityLevel === 'active') ? 'checked' : '' ?>><span class="pill-btn">Moderately Active</span></label>
                                <label class="pill-radio"><input type="radio" name="activity_level" value="very_active" <?= ($activityLevel === 'very_active') ? 'checked' : '' ?>><span class="pill-btn">Very Active</span></label>
                                <label class="pill-radio"><input type="radio" name="activity_level" value="extra_active" <?= ($activityLevel === 'extra_active') ? 'checked' : '' ?>><span class="pill-btn">Extra Active</span></label>
                            </div>
                        </div>

                        <div class="pill-group">
                            <label class="pill-group-label">DIETARY PREFERENCE</label>
                            <div class="pill-options">
                                <label class="pill-radio"><input type="radio" name="diet" value="anything" <?= ($dietaryPreference === 'anything') ? 'checked' : '' ?>><span class="pill-btn">No Restrictions</span></label>
                                <label class="pill-radio"><input type="radio" name="diet" value="vegetarian" <?= ($dietaryPreference === 'vegetarian') ? 'checked' : '' ?>><span class="pill-btn">Vegetarian</span></label>
                                <label class="pill-radio"><input type="radio" name="diet" value="vegan" <?= ($dietaryPreference === 'vegan') ? 'checked' : '' ?>><span class="pill-btn">Vegan</span></label>
                                <label class="pill-radio"><input type="radio" name="diet" value="keto" <?= ($dietaryPreference === 'keto') ? 'checked' : '' ?>><span class="pill-btn">Keto</span></label>
                                <label class="pill-radio"><input type="radio" name="diet" value="halal" <?= ($dietaryPreference === 'halal') ? 'checked' : '' ?>><span class="pill-btn">Halal</span></label>
                                <label class="pill-radio"><input type="radio" name="diet" value="paleo" <?= ($dietaryPreference === 'paleo') ? 'checked' : '' ?>><span class="pill-btn">Paleo</span></label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="primary-btn submit-info-btn">Save Health Information & View Dashboard</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
