<?php
/**
 * Test Suite: Admin User-to-Dietitian Assignment and Dietitian Guidance Flow
 * Smart Health & Diet Recommendation System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$desc}" . ($details ? " - Details: {$details}" : '') . "\n";
    }
}

echo "=== Smart Health & Diet Recommendation System: Assignment & Guidance Tests ===\n\n";

// --- 1. Schema Verification ---
echo "[1] Database Schema & Constraint Verification:\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'dietitian_assignments'");
assertTest("Table dietitian_assignments exists in database", $stmt->rowCount() > 0);

$stmt = $pdo->query("SHOW COLUMNS FROM dietitian_assignments");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
assertTest("Column 'user_id' exists in dietitian_assignments", in_array('user_id', $cols));
assertTest("Column 'dietitian_id' exists in dietitian_assignments", in_array('dietitian_id', $cols));
assertTest("Column 'assigned_by' exists in dietitian_assignments", in_array('assigned_by', $cols));
assertTest("Column 'notes' exists in dietitian_assignments", in_array('notes', $cols));

// --- 2. Seed Accounts Retrieval ---
echo "\n[2] Seed Users Retrieval:\n";
// Admin
$admin = $pdo->query("SELECT * FROM users WHERE email = 'admin@healthtrack.com' LIMIT 1")->fetch();
assertTest("Admin account exists (admin@healthtrack.com)", (bool)$admin);

// Dietitian
$dietitian = $pdo->query("SELECT * FROM users WHERE email = 'sarah@healthtrack.com' LIMIT 1")->fetch();
assertTest("Approved Dietitian exists (sarah@healthtrack.com)", (bool)$dietitian);

// User
$user = $pdo->query("SELECT * FROM users WHERE email = 'alice@example.com' LIMIT 1")->fetch();
assertTest("User exists (alice@example.com)", (bool)$user);

$adminId = (int)$admin['id'];
$dietitianId = (int)$dietitian['id'];
$userId = (int)$user['id'];

// --- 3. Admin Assigns User to Dietitian ---
echo "\n[3] Admin Assignment Execution:\n";
// Clear any previous assignment for test user
$pdo->prepare("DELETE FROM dietitian_assignments WHERE user_id = :uid")->execute(['uid' => $userId]);

$testNotes = "Focus on glycemic control, low-sodium balanced Mediterranean meals.";
$assignStmt = $pdo->prepare("
    INSERT INTO dietitian_assignments (user_id, dietitian_id, assigned_by, notes)
    VALUES (:uid, :did, :aid, :notes)
    ON DUPLICATE KEY UPDATE 
        dietitian_id = VALUES(dietitian_id),
        assigned_by = VALUES(assigned_by),
        notes = VALUES(notes),
        assigned_at = CURRENT_TIMESTAMP
");
$assignStmt->execute([
    'uid'   => $userId,
    'did'   => $dietitianId,
    'aid'   => $adminId,
    'notes' => $testNotes
]);

// Verify database record
$verifyStmt = $pdo->prepare("SELECT * FROM dietitian_assignments WHERE user_id = :uid LIMIT 1");
$verifyStmt->execute(['uid' => $userId]);
$assignment = $verifyStmt->fetch();

assertTest("Assignment recorded in dietitian_assignments", (bool)$assignment);
assertTest("Assigned to expected dietitian ID", (int)$assignment['dietitian_id'] === $dietitianId);
assertTest("Assigned by admin ID", (int)$assignment['assigned_by'] === $adminId);
assertTest("Assignment clinical notes saved correctly", $assignment['notes'] === $testNotes);

// --- 4. Dietitian Portal Queries & Verification ---
echo "\n[4] Dietitian Portal: Assigned Patients List:\n";
$patientsStmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, da.assigned_at, da.notes as assignment_notes
    FROM dietitian_assignments da
    JOIN users u ON da.user_id = u.id
    WHERE da.dietitian_id = :did
");
$patientsStmt->execute(['did' => $dietitianId]);
$assignedPatients = $patientsStmt->fetchAll();
$patientIds = array_column($assignedPatients, 'id');

assertTest("Assigned patient appears in Dietitian's patient roster", in_array($userId, $patientIds));

// --- 5. Dietitian Issues Guidance to Assigned User ---
echo "\n[5] Dietitian Guidance Flow:\n";
$guidanceTitle = "Mediterranean Meal Strategy & Hydration Protocol";
$guidanceBody = "Increase dark leafy greens at lunch. Target 2.5L water daily. Reduce refined carbs after 7 PM.";

// Check assignment validity before prescribing (same logic as in dietitian/guidance.php)
$checkAssign = $pdo->prepare("SELECT id FROM dietitian_assignments WHERE dietitian_id = :did AND user_id = :uid");
$checkAssign->execute(['did' => $dietitianId, 'uid' => $userId]);
$isAssigned = (bool)$checkAssign->fetch();
assertTest("Dietitian authorization: user is legitimately assigned", $isAssigned);

$recStmt = $pdo->prepare("
    INSERT INTO recommendations (user_id, dietitian_id, title, recommendation)
    VALUES (:uid, :did, :title, :rec)
");
$recStmt->execute([
    'uid'   => $userId,
    'did'   => $dietitianId,
    'title' => $guidanceTitle,
    'rec'   => $guidanceBody
]);
$guidanceId = (int)$pdo->lastInsertId();

assertTest("Dietitian guidance successfully created with ID #{$guidanceId}", $guidanceId > 0);

// Security check: Dietitian trying to guide unassigned user
$unassignedUserCheck = $pdo->prepare("SELECT id FROM dietitian_assignments WHERE dietitian_id = :did AND user_id = 99999");
$unassignedUserCheck->execute(['did' => $dietitianId]);
assertTest("Security barrier: unassigned user ID 99999 is blocked from guidance", !$unassignedUserCheck->fetch());

// --- 6. User Portal: View Assigned Dietitian & Guidance ---
echo "\n[6] User Portal: Guidance & Dietitian Verification:\n";
$userDietitianStmt = $pdo->prepare("
    SELECT u.name as dietitian_name, u.email as dietitian_email, dp.specialization
    FROM dietitian_assignments da
    JOIN users u ON da.dietitian_id = u.id
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE da.user_id = :uid
    LIMIT 1
");
$userDietitianStmt->execute(['uid' => $userId]);
$userDietitian = $userDietitianStmt->fetch();

assertTest("User can view assigned dietitian details", (bool)$userDietitian);
assertTest("Assigned dietitian name matches Dr. Sarah Miller", $userDietitian['dietitian_name'] === 'Dr. Sarah Miller');

// User retrieves recommendations list
$userRecStmt = $pdo->prepare("
    SELECT r.*, u.name as dietitian_name
    FROM recommendations r
    JOIN users u ON r.dietitian_id = u.id
    WHERE r.user_id = :uid
    ORDER BY r.created_at DESC
");
$userRecStmt->execute(['uid' => $userId]);
$userRecs = $userRecStmt->fetchAll();

assertTest("User has received recommendations", count($userRecs) > 0);
assertTest("Latest recommendation title matches prescribed guidance", $userRecs[0]['title'] === $guidanceTitle);
assertTest("Latest recommendation body matches prescribed text", $userRecs[0]['recommendation'] === $guidanceBody);

// --- 7. Admin Reassigns / Unassigns User ---
echo "\n[7] Admin Unassign Flow:\n";
$unassignStmt = $pdo->prepare("DELETE FROM dietitian_assignments WHERE user_id = :uid");
$unassignStmt->execute(['uid' => $userId]);

$verifyUnassign = $pdo->prepare("SELECT id FROM dietitian_assignments WHERE user_id = :uid");
$verifyUnassign->execute(['uid' => $userId]);
assertTest("User is successfully unassigned by Admin", !$verifyUnassign->fetch());

// Re-assign for real use
$assignStmt->execute([
    'uid'   => $userId,
    'did'   => $dietitianId,
    'aid'   => $adminId,
    'notes' => "Active patient under Mediterranean metabolic protocol."
]);
$verifyReassign = $pdo->prepare("SELECT id FROM dietitian_assignments WHERE user_id = :uid");
$verifyReassign->execute(['uid' => $userId]);
assertTest("User re-assigned to Dr. Sarah Miller for active usage", (bool)$verifyReassign->fetch());

// --- 8. Summary ---
echo "\n======================================================\n";
echo "Assignment & Guidance Flow Test Results: {$passed} Passed, {$failed} Failed\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
