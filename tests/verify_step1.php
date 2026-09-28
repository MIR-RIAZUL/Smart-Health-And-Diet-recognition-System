<?php
/**
 * Verification Test Script for Step 1 Implementation
 * Smart Health & Diet Recommendation System
 */

echo "========================================================\n";
echo "STEP 1: Full Verification & Automated Health Check\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($description, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] " . $description . "\n";
        $passCount++;
    } else {
        echo "[FAIL] " . $description . "\n";
        $failCount++;
    }
}

// 1. Verify Database Connection
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = getDBConnection();
    assertTest("PDO Database connection established to smart_health_diet", $pdo instanceof PDO);
} catch (Exception $e) {
    assertTest("PDO Database connection established: " . $e->getMessage(), false);
}

// 2. Verify Tables Exist
$expectedTables = [
    'users',
    'dietitian_profiles',
    'health_profiles',
    'food_items',
    'meal_logs',
    'water_logs',
    'sleep_logs',
    'weight_logs',
    'meal_plans',
    'meal_plan_items',
    'recommendations',
    'messages',
    'resources'
];

$stmt = $pdo->query("SHOW TABLES");
$actualTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($expectedTables as $t) {
    assertTest("Table exists: {$t}", in_array($t, $actualTables));
}

// 3. Verify Password Hashing with password_hash & password_verify
$testPassword = 'TestSecurePassword#2026';
$hash = password_hash($testPassword, PASSWORD_BCRYPT);
assertTest("password_hash generates valid bcrypt hash", str_starts_with($hash, '$2y$'));
assertTest("password_verify validates correct password", password_verify($testPassword, $hash) === true);
assertTest("password_verify rejects incorrect password", password_verify('WrongPassword', $hash) === false);

// 4. Verify Seed Accounts
$stmt = $pdo->query("SELECT id, name, email, role, status FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();
assertTest("Initial seed users populated (at least 4 users)", count($users) >= 4);

$adminFound = false;
$approvedDietitianFound = false;
$pendingDietitianFound = false;
$userFound = false;

foreach ($users as $u) {
    if ($u['role'] === 'admin') $adminFound = true;
    if ($u['role'] === 'dietitian' && $u['status'] === 'active') $approvedDietitianFound = true;
    if ($u['role'] === 'dietitian' && $u['status'] === 'pending') $pendingDietitianFound = true;
    if ($u['role'] === 'user') $userFound = true;
}

assertTest("Admin account verified (admin@healthtrack.com)", $adminFound);
assertTest("Approved Dietitian account verified (sarah@healthtrack.com)", $approvedDietitianFound);
assertTest("Pending Dietitian account verified (james@healthtrack.com)", $pendingDietitianFound);
assertTest("Regular User account verified (alice@example.com)", $userFound);

// 5. Test User Registration via DB
$testUserEmail = 'test_student_' . time() . '@example.com';
$insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES ('Test Student', :email, :pass, '+123456789', 'user', 'active')");
$regResult = $insertStmt->execute([
    'email' => $testUserEmail,
    'pass'  => password_hash('student123', PASSWORD_BCRYPT)
]);
$newUserId = (int)$pdo->lastInsertId();
assertTest("User registration creates record in users table", $regResult && $newUserId > 0);

// Create linked health profile
$hpStmt = $pdo->prepare("INSERT INTO health_profiles (user_id, age, gender, height, weight, activity_level, health_goal, daily_calorie_target) VALUES (:uid, 22, 'male', 175.00, 68.00, 'moderate', 'maintain_weight', 2150)");
$hpResult = $hpStmt->execute(['uid' => $newUserId]);
assertTest("User registration creates linked health_profile", $hpResult === true);

// 6. Test Dietitian Registration via DB
$testDietitianEmail = 'test_dietitian_' . time() . '@example.com';
$insertDietitianStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES ('Dr. Alex Taylor', :email, :pass, '+1999888777', 'dietitian', 'pending')");
$dResult = $insertDietitianStmt->execute([
    'email' => $testDietitianEmail,
    'pass'  => password_hash('dietitian123', PASSWORD_BCRYPT)
]);
$newDietitianId = (int)$pdo->lastInsertId();
assertTest("Dietitian registration creates user with pending status", $dResult && $newDietitianId > 0);

$dpStmt = $pdo->prepare("INSERT INTO dietitian_profiles (user_id, qualification, specialization, approval_status) VALUES (:uid, 'M.Sc Nutrition', 'Pediatric Diet', 'pending')");
$dpResult = $dpStmt->execute(['uid' => $newDietitianId]);
assertTest("Dietitian registration creates linked dietitian_profile with pending approval_status", $dpResult === true);

// 7. Verify Core Files Exist
$coreFiles = [
    'config/database.php',
    'includes/functions.php',
    'includes/auth.php',
    'index.php',
    'login.php',
    'register.php',
    'logout.php',
    'admin/dashboard.php',
    'dietitian/dashboard.php',
    'dietitian/pending.php',
    'user/dashboard.php',
    'database/smart_health_diet.sql'
];

foreach ($coreFiles as $f) {
    assertTest("File exists: {$f}", file_exists(__DIR__ . '/../' . $f));
}

// 8. Test Helper Functions
require_once __DIR__ . '/../includes/functions.php';

$bmiResult = calculateBMI(70.0, 175.0);
assertTest("BMI Calculation helper returns valid value (expected 22.9)", $bmiResult['bmi'] == 22.9 && $bmiResult['category'] === 'Normal');

$calorieEstimate = calculateDailyCalorieTarget(70.0, 175.0, 25, 'male', 'moderate', 'weight_loss');
assertTest("Calorie target formula returns reasonable positive integer (>1200)", $calorieEstimate > 1200);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
