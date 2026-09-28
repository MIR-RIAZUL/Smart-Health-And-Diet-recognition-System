<?php
/**
 * Test Create Account Flow
 */

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function httpReq($url, $method = 'GET', $data = [], $cookieJar = null, $followRedirect = false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    $location = '';
    if (preg_match('/Location:\s*([^\r\n]+)/i', $headers, $matches)) {
        $location = trim($matches[1]);
    }
    
    curl_close($ch);
    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body, 'location' => $location];
}

$passed = 0;
$total = 0;

function assertTest($name, $condition, $info = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "[PASS] $name\n";
    } else {
        echo "[FAIL] $name - $info\n";
    }
}

echo "=== STARTING CREATE ACCOUNT FLOW TESTS ===\n\n";

// Test 1: Fresh visitor accessing register.php (clicking Create Account from login/guest)
$res = httpReq("$baseUrl/register.php", 'GET', [], $cookieFile);
assertTest(
    "Fresh visitor visits register.php (no direct redirect to dashboard)",
    $res['code'] === 200 && strpos($res['body'], 'Create account') !== false,
    "Status: {$res['code']}, Location: {$res['location']}"
);

// Test 2: Log in as existing user, then click "Create Account" (/register.php)
$loginRes = httpReq("$baseUrl/login.php", 'POST', [
    'email' => 'alice@example.com',
    'password' => 'user123'
], $cookieFile);
assertTest("Login as alice succeeds", $loginRes['code'] === 302, "Status: {$loginRes['code']}, Body: " . substr($loginRes['body'], 0, 200));

// Now visit register.php while already logged in
$loggedRegRes = httpReq("$baseUrl/register.php", 'GET', [], $cookieFile);
assertTest(
    "Visiting register.php while logged in does NOT bounce straight to dashboard",
    $loggedRegRes['code'] === 200,
    "Status: {$loggedRegRes['code']}, Location: {$loggedRegRes['location']}"
);
assertTest(
    "Visiting register.php while logged in shows friendly session banner",
    strpos($loggedRegRes['body'], 'You are currently signed in as') !== false
);

// Test 3: Submitting registration for new user
$testEmail = 'newtestuser_' . time() . '@example.com';
$regPostRes = httpReq("$baseUrl/register.php", 'POST', [
    'fullname' => 'John Doe',
    'email' => $testEmail,
    'phone' => '+1 555-0199',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'user'
], $cookieFile);

assertTest(
    "Submitting Create Account returns 302 redirect",
    $regPostRes['code'] === 302,
    "Status: {$regPostRes['code']}"
);
assertTest(
    "Submitting Create Account redirects to user/health-info.php (NOT dashboard)",
    $regPostRes['location'] === 'user/health-info.php',
    "Location: {$regPostRes['location']}"
);

// Test 4: Visit user/health-info.php with new user session
$healthInfoGet = httpReq("$baseUrl/user/health-info.php", 'GET', [], $cookieFile);
assertTest(
    "New user lands on Health Information page (HTTP 200)",
    $healthInfoGet['code'] === 200 && strpos($healthInfoGet['body'], 'Setup Health Profile') !== false,
    "Status: {$healthInfoGet['code']}"
);

// Test 5: Submit health profile metrics
$healthInfoPost = httpReq("$baseUrl/user/health-info.php", 'POST', [
    'age' => 28,
    'current_weight' => 78.5,
    'height' => 175.0,
    'goal_weight' => 72.0,
    'gender' => 'male',
    'health_goal' => 'weight_loss',
    'activity_level' => 'moderate',
    'diet' => 'keto'
], $cookieFile);

assertTest(
    "Submitting Health Information redirects to user dashboard (dashboard.php)",
    $healthInfoPost['code'] === 302 && $healthInfoPost['location'] === 'dashboard.php',
    "Status: {$healthInfoPost['code']}, Location: {$healthInfoPost['location']}"
);

// Test 6: Verify in Database
require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
$uStmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$uStmt->execute(['email' => $testEmail]);
$userRow = $uStmt->fetch();
assertTest("New user exists in users table", !empty($userRow));

$hpStmt = $pdo->prepare("SELECT * FROM health_profiles WHERE user_id = :uid");
$hpStmt->execute(['uid' => $userRow['id']]);
$hpRow = $hpStmt->fetch();
assertTest(
    "Health profile was saved with calculated BMI and calorie target",
    !empty($hpRow) && (float)$hpRow['bmi'] > 0 && (int)$hpRow['daily_calorie_target'] > 0,
    "BMI: " . ($hpRow['bmi'] ?? 'null') . ", Calories: " . ($hpRow['daily_calorie_target'] ?? 'null')
);

// Test 7: Dietitian registration flow
$dietitianEmail = 'dietitian_test_' . time() . '@example.com';
$dietitianCookie = __DIR__ . '/test_dietitian_cookie.txt';
if (file_exists($dietitianCookie)) {
    unlink($dietitianCookie);
}
$dietitianRegRes = httpReq("$baseUrl/register.php", 'POST', [
    'fullname' => 'Dr. Karen White',
    'email' => $dietitianEmail,
    'phone' => '+1 555-8888',
    'password' => 'dietitian123',
    'confirm_password' => 'dietitian123',
    'role' => 'dietitian',
    'qualification' => 'Ph.D. Sports Nutrition',
    'specialization' => 'Athletic Performance'
], $dietitianCookie);

assertTest(
    "Dietitian registration redirects to dietitian/pending.php",
    $dietitianRegRes['code'] === 302 && $dietitianRegRes['location'] === 'dietitian/pending.php',
    "Status: {$dietitianRegRes['code']}, Location: {$dietitianRegRes['location']}"
);

// Clean up test cookie files
if (file_exists($cookieFile)) unlink($cookieFile);
if (file_exists($dietitianCookie)) unlink($dietitianCookie);

echo "\n=== SUMMARY: $passed / $total TESTS PASSED ===\n";
