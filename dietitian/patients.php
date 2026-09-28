<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('dietitian');
$currentUser = getCurrentUser();
$dietitianId = (int)$currentUser['id'];

$pdo = getDBConnection();

// Fetch dietitian profile
$stmt = $pdo->prepare("SELECT * FROM dietitian_profiles WHERE user_id = :uid LIMIT 1");
$stmt->execute(['uid' => $dietitianId]);
$profile = $stmt->fetch();

// Search filter
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT u.id, u.name, u.email, u.phone, u.status,
           h.age, h.gender, h.height, h.weight, h.bmi, h.health_goal, h.daily_calorie_target, h.dietary_preference,
           da.assigned_at, da.notes as admin_notes,
           (SELECT COUNT(*) FROM meal_logs ml WHERE ml.user_id = u.id) as total_meals_logged,
           (SELECT COUNT(*) FROM recommendations r WHERE r.user_id = u.id AND r.dietitian_id = :did) as guidance_count,
           (SELECT created_at FROM recommendations r WHERE r.user_id = u.id AND r.dietitian_id = :did ORDER BY created_at DESC LIMIT 1) as last_guidance_date
    FROM dietitian_assignments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN health_profiles h ON u.id = h.user_id
    WHERE da.dietitian_id = :did
";
$params = ['did' => $dietitianId];

if (!empty($search)) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params['search'] = "%{$search}%";
}

$query .= " ORDER BY da.assigned_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$currentPage = 'patients';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - My Assigned Patients</title>
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
<body class="admin-full-page">
    <div class="dashboard-layout">
        
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="main-content" style="overflow-y: auto;">
            <div class="admin-page-container" style="max-width: 1300px; padding: 30px 40px;">
                
                <?php renderFlashMessage(); ?>

                <!-- Page Header Controls -->
                <div class="admin-controls-bar">
                    <div class="controls-left">
                        <div class="title-icon" style="background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3);">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="section-title">My Assigned Patients</h2>
                            <p class="section-subtitle"><?= count($patients) ?> users currently under your clinical dietary guidance</p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="patients.php" class="search-box">
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search assigned patients...">
                    </form>
                </div>

                <!-- Patients Table -->
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>PATIENT</th>
                                <th>PRIMARY GOAL</th>
                                <th>TARGET CALORIES</th>
                                <th class="text-right">WEIGHT / BMI</th>
                                <th class="text-center">GUIDANCE</th>
                                <th class="text-right">ASSIGNED DATE</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patients)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted);">
                                        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#606470" stroke-width="1.5" style="margin-bottom: 12px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                        <p style="font-size: 15px; font-weight: 600; color: var(--text-light); margin-bottom: 4px;">No Assigned Patients</p>
                                        <p style="font-size: 13px;">Patients assigned to you by the system administrator will appear here for monitoring and guidance.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($patients as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="avatar avatar-blue" style="font-size: 13px;">
                                                    <?= strtoupper(substr($p['name'], 0, 2)) ?>
                                                </div>
                                                <div class="user-info">
                                                    <span class="user-name"><?= e($p['name']) ?></span>
                                                    <span class="user-email"><?= e($p['email']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted" style="font-size: 13px;">
                                            <span style="color: #f2500c; font-weight: 600;">
                                                <?= e(ucwords(str_replace('_', ' ', $p['health_goal'] ?? 'General health'))) ?>
                                            </span>
                                            <div style="font-size: 11px; color: var(--text-muted);">
                                                Diet: <?= e(ucfirst($p['dietary_preference'] ?? 'General')) ?>
                                            </div>
                                        </td>
                                        <td style="font-size: 13px; color: var(--text-light); font-weight: 600;">
                                            <?= (int)($p['daily_calorie_target'] ?? 2000) ?> kcal
                                        </td>
                                        <td class="text-right" style="font-size: 13px;">
                                            <span style="color: var(--text-light); font-weight: 600;"><?= $p['weight'] ? $p['weight'] . ' kg' : '--' ?></span>
                                            <div style="font-size: 11px; color: var(--text-muted);">
                                                BMI: <strong style="color: #3498db;"><?= $p['bmi'] ? number_format($p['bmi'], 1) : '--' ?></strong>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-badge" style="background: rgba(52, 152, 219, 0.15); color: #3498db;">
                                                <?= (int)$p['guidance_count'] ?> Notes
                                            </span>
                                        </td>
                                        <td class="text-right text-muted" style="font-size: 13px;">
                                            <?= date('M d, Y', strtotime($p['assigned_at'])) ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="action-buttons" style="display: inline-flex; gap: 8px;">
                                                <!-- Guide Patient -->
                                                <a href="guidance.php?patient_id=<?= (int)$p['id'] ?>" class="action-btn" title="View / Give Guidance" style="color: #3498db; background: rgba(52, 152, 219, 0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                                </a>

                                                <!-- Review Vitals Modal Trigger -->
                                                <button type="button" class="action-btn view-btn" title="Review Full Vitals" 
                                                        onclick="openPatientModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <!-- Patient Vitals Modal -->
    <div id="patientModal" class="details-modal">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="modalPName">Patient Overview</h2>
                    <p id="modalPEmail">Vitals & Clinical Biometrics</p>
                </div>
                <button type="button" class="close-btn" onclick="closePatientModal()">&times;</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <div class="detail-box">
                    <span class="detail-label">Age</span>
                    <span class="detail-val" id="modalPAge">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Gender</span>
                    <span class="detail-val" id="modalPGender">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Height</span>
                    <span class="detail-val" id="modalPHeight">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Current Weight</span>
                    <span class="detail-val" id="modalPWeight">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Calculated BMI</span>
                    <span class="detail-val" id="modalPBMI">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Caloric Target</span>
                    <span class="detail-val" id="modalPCalories">--</span>
                </div>
            </div>

            <div class="detail-box" style="margin-bottom: 14px;">
                <span class="detail-label">Primary Health Goal</span>
                <span class="detail-val" id="modalPGoal">--</span>
            </div>

            <div class="detail-box" style="margin-bottom: 14px;">
                <span class="detail-label">Dietary Preference</span>
                <span class="detail-val" id="modalPDiet">--</span>
            </div>

            <div class="detail-box" style="margin-bottom: 24px;">
                <span class="detail-label">Administrator Assignment Notes</span>
                <span class="detail-val" id="modalPNotes" style="font-size: 13px; color: #f39c12;">None</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a id="modalGuideBtn" href="guidance.php" class="primary-btn" style="margin-bottom: 0; background: #3498db; text-decoration: none; padding: 10px 20px;">
                    Provide Guidance &rarr;
                </a>
                <button type="button" class="filter-btn" onclick="closePatientModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openPatientModal(p) {
            document.getElementById('modalPName').innerText = p.name;
            document.getElementById('modalPEmail').innerText = p.email + (p.phone ? ' • ' + p.phone : '');
            document.getElementById('modalPAge').innerText = p.age ? p.age + ' yrs' : 'Not recorded';
            document.getElementById('modalPGender').innerText = p.gender ? p.gender.toUpperCase() : 'Not recorded';
            document.getElementById('modalPHeight').innerText = p.height ? p.height + ' cm' : 'Not recorded';
            document.getElementById('modalPWeight').innerText = p.weight ? p.weight + ' kg' : 'Not recorded';
            document.getElementById('modalPBMI').innerText = p.bmi ? p.bmi : 'Not calculated';
            document.getElementById('modalPCalories').innerText = p.daily_calorie_target ? p.daily_calorie_target + ' kcal' : '2000 kcal';
            document.getElementById('modalPGoal').innerText = p.health_goal ? p.health_goal.replace('_', ' ').toUpperCase() : 'GENERAL HEALTH';
            document.getElementById('modalPDiet').innerText = p.dietary_preference ? p.dietary_preference.toUpperCase() : 'ANYTHING';
            document.getElementById('modalPNotes').innerText = p.admin_notes ? p.admin_notes : 'None provided';
            document.getElementById('modalGuideBtn').href = 'guidance.php?patient_id=' + p.id;

            document.getElementById('patientModal').classList.add('active');
        }

        function closePatientModal() {
            document.getElementById('patientModal').classList.remove('active');
        }
    </script>
</body>
</html>
