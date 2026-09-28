<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('dietitian');
$currentUser = getCurrentUser();
$dietitianId = (int)$currentUser['id'];

$pdo = getDBConnection();

// Fetch dietitian profile details
$stmt = $pdo->prepare("SELECT * FROM dietitian_profiles WHERE user_id = :uid LIMIT 1");
$stmt->execute(['uid' => $dietitianId]);
$profile = $stmt->fetch();

// Handle Post: Send Guidance to Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_guidance') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        setFlashMessage('error', 'Security token mismatch. Please try again.');
        header('Location: dashboard.php');
        exit;
    }

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $guidanceText = trim($_POST['recommendation'] ?? '');

    // Verify patient is assigned to this dietitian
    $check = $pdo->prepare("SELECT u.name FROM dietitian_assignments da JOIN users u ON da.user_id = u.id WHERE da.dietitian_id = :did AND da.user_id = :uid LIMIT 1");
    $check->execute(['did' => $dietitianId, 'uid' => $patientId]);
    $patientName = $check->fetchColumn();

    if (!$patientName) {
        setFlashMessage('error', 'You can only provide guidance to patients assigned to your care.');
    } elseif (empty($title) || empty($guidanceText)) {
        setFlashMessage('error', 'Please provide both a guidance title and detailed dietary recommendations.');
    } else {
        $insertRec = $pdo->prepare("
            INSERT INTO recommendations (dietitian_id, user_id, title, recommendation, created_at)
            VALUES (:did, :uid, :title, :rec, NOW())
        ");
        $insertRec->execute([
            'did'   => $dietitianId,
            'uid'   => $patientId,
            'title' => $title,
            'rec'   => $guidanceText
        ]);
        setFlashMessage('success', "Nutritional guidance successfully sent to {$patientName}!");
    }
    header('Location: dashboard.php');
    exit;
}

// Dynamic metrics
$mealPlansCount = (int)$pdo->query("SELECT COUNT(*) FROM meal_plans WHERE dietitian_id = {$dietitianId}")->fetchColumn();
$assignedUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM dietitian_assignments WHERE dietitian_id = {$dietitianId}")->fetchColumn();
$recommendationsCount = (int)$pdo->query("SELECT COUNT(*) FROM recommendations WHERE dietitian_id = {$dietitianId}")->fetchColumn();
$totalGuidesCount = (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE dietitian_id = {$dietitianId}")->fetchColumn();

// Fetch assigned patients with their biometrics & activity
$patientsStmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.phone, 
           h.age, h.gender, h.height, h.weight, h.bmi, h.health_goal, h.daily_calorie_target, h.dietary_preference,
           da.assigned_at, da.notes as admin_notes,
           (SELECT COALESCE(SUM(calories), 0) FROM meal_logs ml WHERE ml.user_id = u.id AND ml.meal_date = CURDATE()) as today_calories,
           (SELECT COUNT(*) FROM recommendations r WHERE r.user_id = u.id AND r.dietitian_id = :did) as guidance_count,
           (SELECT created_at FROM recommendations r WHERE r.user_id = u.id AND r.dietitian_id = :did ORDER BY created_at DESC LIMIT 1) as last_guidance_date
    FROM dietitian_assignments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN health_profiles h ON u.id = h.user_id
    WHERE da.dietitian_id = :did
    ORDER BY da.assigned_at DESC
");
$patientsStmt->execute(['did' => $dietitianId]);
$assignedPatients = $patientsStmt->fetchAll();

// Fetch recent guidance sent by this dietitian
$guidanceStmt = $pdo->prepare("
    SELECT r.*, u.name as patient_name, u.email as patient_email 
    FROM recommendations r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.dietitian_id = :did 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$guidanceStmt->execute(['did' => $dietitianId]);
$recentGuidance = $guidanceStmt->fetchAll();

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Dietitian Dashboard</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .details-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .details-modal.active { display: flex; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content dietitian-main" style="overflow-y: auto;">
            <header class="page-header-clean">
                <h1 class="page-main-title">Dietitian Clinical Dashboard</h1>
                <p class="page-sub-title">Welcome back, Dr. <?= e($currentUser['name']) ?> • Specialization: <?= e($profile['specialization'] ?? 'Clinical Nutrition') ?></p>
            </header>

            <div class="content-wrapper full-width-wrap">
                <?php renderFlashMessage(); ?>
                
                <!-- Top KPI Grid -->
                <div class="dietitian-kpi-grid">
                    <div class="dt-kpi-card">
                        <div class="kpi-icon-box bg-teal-light text-teal">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div class="dt-kpi-number"><?= $assignedUsersCount ?></div>
                        <div class="dt-kpi-label">Assigned Patients</div>
                    </div>

                    <div class="dt-kpi-card">
                        <div class="kpi-icon-box bg-blue-light text-blue">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                        <div class="dt-kpi-number"><?= $recommendationsCount ?></div>
                        <div class="dt-kpi-label">Dietary Guidance Sent</div>
                    </div>

                    <div class="dt-kpi-card">
                        <div class="kpi-icon-box bg-orange-light text-orange">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <div class="dt-kpi-number"><?= $mealPlansCount ?></div>
                        <div class="dt-kpi-label">Active Meal Plans</div>
                    </div>

                    <div class="dt-kpi-card">
                        <div class="kpi-icon-box bg-green-light text-green">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </div>
                        <div class="dt-kpi-number"><?= $totalGuidesCount ?></div>
                        <div class="dt-kpi-label">Nutritional Guides</div>
                    </div>
                </div>

                <!-- Split Grid: Assigned Patients & Guidance History -->
                <div class="dashboard-split-grid" style="margin-top: 24px;">
                    
                    <!-- Left: Assigned Patients Priority List -->
                    <div class="panel-card" style="padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="panel-title flex-title" style="margin-bottom: 0;">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#3498db" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                My Assigned Patients (<?= count($assignedPatients) ?>)
                            </h3>
                            <a href="patients.php" style="font-size: 13px; color: #3498db; text-decoration: underline; font-weight: 500;">View All Details &rarr;</a>
                        </div>

                        <?php if (empty($assignedPatients)): ?>
                            <div style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
                                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#606470" stroke-width="1.5" style="margin-bottom: 12px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
                                <p style="font-size: 15px; font-weight: 600; color: var(--text-light); margin-bottom: 6px;">No Patients Assigned Yet</p>
                                <p style="font-size: 13px; max-width: 380px; margin: 0 auto;">
                                    The system administrator assigns patients to your care. Once assigned, you can analyze their biometrics, track calorie logs, and provide customized nutritional guidance.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="attention-list">
                                <?php foreach ($assignedPatients as $p): ?>
                                    <div class="attention-item" style="padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                                        <div class="att-text">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                                <h4 style="font-size: 15px; font-weight: 600; color: var(--text-light); margin: 0;"><?= e($p['name']) ?></h4>
                                                <?php if (!empty($p['bmi'])): ?>
                                                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 10px; background: rgba(242, 80, 12, 0.15); color: #f2500c; font-weight: 600;">
                                                        BMI <?= number_format($p['bmi'], 1) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 6px 0;">
                                                Goal: <strong style="color: var(--text-light);"><?= e(ucwords(str_replace('_', ' ', $p['health_goal'] ?? 'General health'))) ?></strong> • 
                                                Target: <strong style="color: var(--text-light);"><?= (int)($p['daily_calorie_target'] ?? 2000) ?> kcal</strong> • 
                                                Diet: <?= e(ucfirst($p['dietary_preference'] ?? 'General')) ?>
                                            </p>
                                            <div style="font-size: 11px; color: #8c909a;">
                                                Today's Intake: <span style="color: #2ecc71; font-weight: 600;"><?= (int)$p['today_calories'] ?> kcal</span> • 
                                                Guidance: <?= (int)$p['guidance_count'] ?> notes given
                                                <?php if (!empty($p['admin_notes'])): ?>
                                                    • <em style="color: #f39c12;">Admin Note: <?= e($p['admin_notes']) ?></em>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 8px; flex-shrink: 0;">
                                            <button type="button" class="action-pill pill-blue" style="border: none; cursor: pointer; padding: 7px 14px; font-size: 12px;"
                                                    onclick="openGuidanceModal(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name']), ENT_QUOTES) ?>')">
                                                + Guide Patient
                                            </button>
                                            <button type="button" class="action-pill pill-orange" style="border: none; cursor: pointer; padding: 7px 12px; font-size: 12px;"
                                                    onclick="openPatientVitals(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                                Review Vitals
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right: Recent Nutritional Guidance Sent -->
                    <div class="panel-card" style="padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="panel-title flex-title" style="margin-bottom: 0;">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#2ecc71" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                Recent Guidance Sent
                            </h3>
                            <a href="guidance.php" style="font-size: 13px; color: #2ecc71; text-decoration: underline; font-weight: 500;">All Guidance &rarr;</a>
                        </div>

                        <?php if (empty($recentGuidance)): ?>
                            <div style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
                                <p style="font-size: 14px; color: var(--text-light); font-weight: 600; margin-bottom: 6px;">No Guidance Issued Yet</p>
                                <p style="font-size: 13px;">Click "Guide Patient" on any assigned user to provide personalized nutritional advice and diet direction.</p>
                            </div>
                        <?php else: ?>
                            <div class="schedule-timeline">
                                <?php foreach ($recentGuidance as $g): ?>
                                    <div class="timeline-item" style="padding-bottom: 16px;">
                                        <div class="tl-dot bg-dot-green"></div>
                                        <div class="tl-content">
                                            <span class="tl-time"><?= date('M d, Y • h:i A', strtotime($g['created_at'])) ?></span>
                                            <span class="tl-name">Patient: <?= e($g['patient_name']) ?></span>
                                            <strong style="color: #f2500c; font-size: 13px; display: block; margin: 2px 0;"><?= e($g['title']) ?></strong>
                                            <span class="tl-desc" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?= e($g['recommendation']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- Quick Send Guidance Modal -->
    <div id="guidanceModal" class="details-modal">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="guidanceModalTitle">Provide Patient Guidance</h2>
                    <p id="guidanceModalSubtitle">Send personalized dietary instructions</p>
                </div>
                <button type="button" class="close-btn" onclick="closeGuidanceModal()">&times;</button>
            </div>

            <form method="POST" action="dashboard.php">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="send_guidance">
                <input type="hidden" name="patient_id" id="guidancePatientId" value="0">

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Guidance Focus / Title *</label>
                    <input type="text" name="title" id="guidanceTitle" required placeholder="e.g. Protein Pacing Strategy & Evening Carb Reduction" 
                           style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px;">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Detailed Dietary Advice & Recommendations *</label>
                    <textarea name="recommendation" id="guidanceText" rows="5" required placeholder="Provide clear instructions on target meal distribution, foods to prioritize, hydration benchmarks, and foods to avoid..." 
                              style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="filter-btn" onclick="closeGuidanceModal()" style="border: 1px solid var(--border-color);">Cancel</button>
                    <button type="submit" class="primary-btn" style="margin-bottom: 0; width: auto; padding: 10px 24px; background: #3498db;">Send Guidance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Patient Vitals Review Modal -->
    <div id="vitalsModal" class="details-modal">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="vitalsPatientName">Patient Biometrics</h2>
                    <p id="vitalsPatientEmail">Health Profile & Clinical Metrics</p>
                </div>
                <button type="button" class="close-btn" onclick="closeVitalsModal()">&times;</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <div class="detail-box">
                    <span class="detail-label">Age</span>
                    <span class="detail-val" id="vitalsAge">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Gender</span>
                    <span class="detail-val" id="vitalsGender">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Height</span>
                    <span class="detail-val" id="vitalsHeight">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Current Weight</span>
                    <span class="detail-val" id="vitalsWeight">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Calculated BMI</span>
                    <span class="detail-val" id="vitalsBMI">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Daily Calorie Target</span>
                    <span class="detail-val" id="vitalsCalories">--</span>
                </div>
            </div>

            <div class="detail-box" style="margin-bottom: 16px;">
                <span class="detail-label">Primary Health Goal</span>
                <span class="detail-val" id="vitalsGoal">--</span>
            </div>

            <div class="detail-box" style="margin-bottom: 24px;">
                <span class="detail-label">Dietary Preference</span>
                <span class="detail-val" id="vitalsDiet">--</span>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="primary-btn" onclick="closeVitalsModal()" style="margin-bottom: 0;">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openGuidanceModal(patientId, patientName) {
            document.getElementById('guidancePatientId').value = patientId;
            document.getElementById('guidanceModalTitle').innerText = 'Guide Patient: ' + patientName;
            document.getElementById('guidanceModalSubtitle').innerText = 'Write personalized clinical dietary advice for ' + patientName;
            document.getElementById('guidanceTitle').value = '';
            document.getElementById('guidanceText').value = '';
            document.getElementById('guidanceModal').classList.add('active');
        }

        function closeGuidanceModal() {
            document.getElementById('guidanceModal').classList.remove('active');
        }

        function openPatientVitals(patient) {
            document.getElementById('vitalsPatientName').innerText = patient.name;
            document.getElementById('vitalsPatientEmail').innerText = patient.email + (patient.phone ? ' • ' + patient.phone : '');
            document.getElementById('vitalsAge').innerText = patient.age ? patient.age + ' yrs' : 'Not recorded';
            document.getElementById('vitalsGender').innerText = patient.gender ? patient.gender.toUpperCase() : 'Not recorded';
            document.getElementById('vitalsHeight').innerText = patient.height ? patient.height + ' cm' : 'Not recorded';
            document.getElementById('vitalsWeight').innerText = patient.weight ? patient.weight + ' kg' : 'Not recorded';
            document.getElementById('vitalsBMI').innerText = patient.bmi ? patient.bmi : 'Not calculated';
            document.getElementById('vitalsCalories').innerText = patient.daily_calorie_target ? patient.daily_calorie_target + ' kcal' : '2000 kcal';
            document.getElementById('vitalsGoal').innerText = patient.health_goal ? patient.health_goal.replace('_', ' ').toUpperCase() : 'GENERAL HEALTH';
            document.getElementById('vitalsDiet').innerText = patient.dietary_preference ? patient.dietary_preference.toUpperCase() : 'ANYTHING';

            document.getElementById('vitalsModal').classList.add('active');
        }

        function closeVitalsModal() {
            document.getElementById('vitalsModal').classList.remove('active');
        }
    </script>
</body>
</html>
