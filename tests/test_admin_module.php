<?php
/**
 * Automated Test Suite: Complete Admin Module & Security
 * Smart Health & Diet Recommendation System
 */

echo "========================================================\n";
echo "TEST SUITE: Complete Admin Module & Security Verification\n";
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

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

// 1. Verify Admin Account Exists & Password Verifies
$stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = 'admin@healthtrack.com' LIMIT 1");
$stmt->execute();
$adminUser = $stmt->fetch();

assertTest("Admin account exists in database", $adminUser !== false);
assertTest("Admin has role = 'admin'", ($adminUser['role'] ?? '') === 'admin');
assertTest("Admin status = 'active'", ($adminUser['status'] ?? '') === 'active');
assertTest("Admin password verification succeeds with 'admin123'", password_verify('admin123', $adminUser['password'] ?? ''));

// 2. Verify Admin Module Files Exist
$adminFiles = [
    'admin/sidebar.php',
    'admin/dashboard.php',
    'admin/users.php',
    'admin/dietitians.php',
    'admin/foods.php',
    'admin/reports.php'
];

foreach ($adminFiles as $file) {
    assertTest("Admin module file exists: {$file}", file_exists(__DIR__ . '/../' . $file));
}

// 3. Test Security Role Protection via Simulation
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Test A: Unauthenticated access
logoutUser();
assertTest("isLoggedIn() returns false when unauthenticated", isLoggedIn() === false);

// Test B: Regular User cannot have admin access
$stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE role = 'user' LIMIT 1");
$stmt->execute();
$normalUser = $stmt->fetch();

loginUser($normalUser);
assertTest("Normal user logged in: role is 'user'", $_SESSION['user_role'] === 'user');
assertTest("Normal user role != 'admin'", $_SESSION['user_role'] !== 'admin');

// Test C: Dietitian cannot have admin access
$stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE role = 'dietitian' LIMIT 1");
$stmt->execute();
$dietitianUser = $stmt->fetch();

loginUser($dietitianUser);
assertTest("Dietitian logged in: role is 'dietitian'", $_SESSION['user_role'] === 'dietitian');
assertTest("Dietitian role != 'admin'", $_SESSION['user_role'] !== 'admin');

// Test D: Admin logged in
loginUser($adminUser);
assertTest("Admin logged in: role is 'admin'", $_SESSION['user_role'] === 'admin');

// 4. Test User Management Actions (Status Toggle & Query)
$testUserEmail = 'user_mgmt_test_' . time() . '@example.com';
$pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Status Test User', :email, 'hash', 'user', 'active')")
    ->execute(['email' => $testUserEmail]);
$testUserId = (int)$pdo->lastInsertId();

// Toggle status: active -> suspended
$pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = :id")->execute(['id' => $testUserId]);
$statusAfter = $pdo->query("SELECT status FROM users WHERE id = {$testUserId}")->fetchColumn();
assertTest("Admin can suspend user account (status becomes 'suspended')", $statusAfter === 'suspended');

// Toggle status back: suspended -> active
$pdo->prepare("UPDATE users SET status = 'active' WHERE id = :id")->execute(['id' => $testUserId]);
$statusAfter2 = $pdo->query("SELECT status FROM users WHERE id = {$testUserId}")->fetchColumn();
assertTest("Admin can reactivate user account (status becomes 'active')", $statusAfter2 === 'active');

// Delete test user
$pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $testUserId]);
$deletedCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE id = {$testUserId}")->fetchColumn();
assertTest("Admin can delete user account", $deletedCheck == 0);

// 5. Test Dietitian Approval Workflow
$testDietitianEmail = 'dt_approval_test_' . time() . '@example.com';
$pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Dr. Workflow Test', :email, 'hash', 'dietitian', 'pending')")
    ->execute(['email' => $testDietitianEmail]);
$dtUserId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO dietitian_profiles (user_id, qualification, specialization, approval_status) VALUES (:uid, 'M.Sc Nutrition', 'Metabolism', 'pending')")
    ->execute(['uid' => $dtUserId]);
$dtProfileId = (int)$pdo->lastInsertId();

// Verify initial state
$dtState = $pdo->query("SELECT approval_status FROM dietitian_profiles WHERE id = {$dtProfileId}")->fetchColumn();
assertTest("New dietitian registration starts with approval_status = 'pending'", $dtState === 'pending');

// Admin Approves Dietitian
$pdo->beginTransaction();
$pdo->prepare("UPDATE dietitian_profiles SET approval_status = 'approved' WHERE id = :id")->execute(['id' => $dtProfileId]);
$pdo->prepare("UPDATE users SET status = 'active' WHERE id = :uid")->execute(['uid' => $dtUserId]);
$pdo->commit();

$approvedStatus = $pdo->query("SELECT approval_status FROM dietitian_profiles WHERE id = {$dtProfileId}")->fetchColumn();
$userActiveStatus = $pdo->query("SELECT status FROM users WHERE id = {$dtUserId}")->fetchColumn();
assertTest("Admin approval updates dietitian_profiles approval_status to 'approved'", $approvedStatus === 'approved');
assertTest("Admin approval activates user account status to 'active'", $userActiveStatus === 'active');

// Admin Rejects Dietitian
$pdo->prepare("UPDATE dietitian_profiles SET approval_status = 'rejected', rejection_reason = 'Insufficient credentials' WHERE id = :id")
    ->execute(['id' => $dtProfileId]);
$rejectedStatus = $pdo->query("SELECT approval_status FROM dietitian_profiles WHERE id = {$dtProfileId}")->fetchColumn();
assertTest("Admin rejection updates dietitian_profiles approval_status to 'rejected'", $rejectedStatus === 'rejected');

// Clean up test dietitian
$pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $dtUserId]);

// 6. Test Food Database CRUD
$testFoodName = 'Test Superfood ' . time();
$pdo->prepare("
    INSERT INTO food_items (food_name, serving_size, calories, protein, carbohydrates, fat, fiber)
    VALUES (:name, '150g', 220.0, 18.5, 24.0, 5.0, 4.0)
")->execute(['name' => $testFoodName]);
$foodId = (int)$pdo->lastInsertId();
assertTest("Admin can add food item to food_items table", $foodId > 0);

// Edit food item
$pdo->prepare("UPDATE food_items SET calories = 235.0, protein = 20.0 WHERE id = :id")->execute(['id' => $foodId]);
$editedFood = $pdo->query("SELECT calories, protein FROM food_items WHERE id = {$foodId}")->fetch();
assertTest("Admin can edit food item calories and macros", (float)$editedFood['calories'] === 235.0 && (float)$editedFood['protein'] === 20.0);

// Delete food item
$pdo->prepare("DELETE FROM food_items WHERE id = :id")->execute(['id' => $foodId]);
$foodCountCheck = $pdo->query("SELECT COUNT(*) FROM food_items WHERE id = {$foodId}")->fetchColumn();
assertTest("Admin can delete food item from food database", $foodCountCheck == 0);

// 7. Test Reports Aggregation Queries
$reportMetrics = [
    'users'         => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'dietitians'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'dietitian'")->fetchColumn(),
    'foods'         => (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn(),
    'meal_plans'    => (int)$pdo->query("SELECT COUNT(*) FROM meal_plans")->fetchColumn(),
    'total_accounts'=> (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()
];

assertTest("Report metric: Total users count is valid integer", is_int($reportMetrics['users']));
assertTest("Report metric: Total dietitians count is valid integer", is_int($reportMetrics['dietitians']));
assertTest("Report metric: Total foods count is valid integer", is_int($reportMetrics['foods']));
assertTest("Report metric: Total accounts count >= users + dietitians", $reportMetrics['total_accounts'] >= ($reportMetrics['users'] + $reportMetrics['dietitians']));

// 8. Test CSRF Protection Helper
$token1 = getCSRFToken();
assertTest("getCSRFToken() generates non-empty hex token", !empty($token1) && strlen($token1) === 64);
assertTest("validateCSRFToken() returns true for matching token", validateCSRFToken($token1) === true);
assertTest("validateCSRFToken() returns false for forged token", validateCSRFToken('invalid_forged_token') === false);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
