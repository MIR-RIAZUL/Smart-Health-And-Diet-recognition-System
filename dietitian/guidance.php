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

// Handle Actions (Create / Delete Recommendation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        setFlashMessage('error', 'Security token mismatch. Please try again.');
        header('Location: guidance.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_guidance') {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $recommendation = trim($_POST['recommendation'] ?? '');

        // Verify patient is assigned to this dietitian
        $check = $pdo->prepare("SELECT u.name FROM dietitian_assignments da JOIN users u ON da.user_id = u.id WHERE da.dietitian_id = :did AND da.user_id = :uid LIMIT 1");
        $check->execute(['did' => $dietitianId, 'uid' => $patientId]);
        $patientName = $check->fetchColumn();

        if (!$patientName) {
            setFlashMessage('error', 'You can only provide guidance to patients assigned to your care.');
        } elseif (empty($title) || empty($recommendation)) {
            setFlashMessage('error', 'Please provide both a guidance title and detailed dietary recommendations.');
        } else {
            $insert = $pdo->prepare("
                INSERT INTO recommendations (dietitian_id, user_id, title, recommendation, created_at)
                VALUES (:did, :uid, :title, :rec, NOW())
            ");
            $insert->execute([
                'did'   => $dietitianId,
                'uid'   => $patientId,
                'title' => $title,
                'rec'   => $recommendation
            ]);
            setFlashMessage('success', "Nutritional guidance successfully issued to {$patientName}!");
        }
    } elseif ($action === 'delete_guidance') {
        $recId = (int)($_POST['rec_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM recommendations WHERE id = :id AND dietitian_id = :did");
        $del->execute(['id' => $recId, 'did' => $dietitianId]);
        setFlashMessage('success', "Recommendation removed successfully.");
    }

    header('Location: guidance.php');
    exit;
}

// Fetch assigned patients for the dropdown
$assignedPatientsStmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, h.health_goal, h.daily_calorie_target
    FROM dietitian_assignments da
    JOIN users u ON da.user_id = u.id
    LEFT JOIN health_profiles h ON u.id = h.user_id
    WHERE da.dietitian_id = :did
    ORDER BY u.name ASC
");
$assignedPatientsStmt->execute(['did' => $dietitianId]);
$assignedPatients = $assignedPatientsStmt->fetchAll();

// Fetch all recommendations previously given by this dietitian
$filterPatient = (int)($_GET['patient_id'] ?? 0);
$query = "
    SELECT r.*, u.name as patient_name, u.email as patient_email, h.health_goal
    FROM recommendations r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN health_profiles h ON u.id = h.user_id
    WHERE r.dietitian_id = :did
";
$params = ['did' => $dietitianId];

if ($filterPatient > 0) {
    $query .= " AND r.user_id = :pid";
    $params['pid'] = $filterPatient;
}

$query .= " ORDER BY r.created_at DESC";
$recStmt = $pdo->prepare($query);
$recStmt->execute($params);
$guidanceList = $recStmt->fetchAll();

$currentPage = 'guidance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Patient Guidance & Recommendations</title>
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
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <div>
                            <h2 class="section-title">Patient Dietary Guidance</h2>
                            <p class="section-subtitle">Prescribe personalized nutritional strategies and recommendations</p>
                        </div>
                    </div>

                    <button type="button" class="primary-btn" onclick="openNewGuidanceModal()" style="margin-bottom: 0; width: auto; background: #3498db; display: flex; align-items: center; gap: 8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Write New Guidance
                    </button>
                </div>

                <!-- Two-Column Layout: Filter by Patient & History -->
                <div style="margin-bottom: 24px;">
                    <form method="GET" action="guidance.php" style="display: flex; gap: 12px; align-items: center; background: #1a1d24; padding: 14px 20px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <span style="font-size: 13px; color: var(--text-muted); font-weight: 500;">Filter by Patient:</span>
                        <select name="patient_id" onchange="this.form.submit()" style="padding: 8px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 13px;">
                            <option value="0">-- All Assigned Patients --</option>
                            <?php foreach ($assignedPatients as $ap): ?>
                                <option value="<?= (int)$ap['id'] ?>" <?= ($filterPatient === (int)$ap['id']) ? 'selected' : '' ?>>
                                    <?= e($ap['name']) ?> (<?= e(ucwords(str_replace('_', ' ', $ap['health_goal'] ?? 'General'))) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($filterPatient > 0): ?>
                            <a href="guidance.php" style="font-size: 12px; color: #f2500c; text-decoration: underline;">Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Guidance Entries Table -->
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>PATIENT</th>
                                <th>GUIDANCE FOCUS</th>
                                <th>RECOMMENDATION SUMMARY</th>
                                <th class="text-right">DATE ISSUED</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($guidanceList)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 48px; color: var(--text-muted);">
                                        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#606470" stroke-width="1.5" style="margin-bottom: 12px;"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4M12 16h.01"></path></svg>
                                        <p style="font-size: 15px; font-weight: 600; color: var(--text-light); margin-bottom: 4px;">No Guidance Notes Found</p>
                                        <p style="font-size: 13px;">Click "Write New Guidance" to provide clinical nutrition advice to any assigned user.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($guidanceList as $g): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="avatar avatar-blue" style="font-size: 13px;">
                                                    <?= strtoupper(substr($g['patient_name'], 0, 2)) ?>
                                                </div>
                                                <div class="user-info">
                                                    <span class="user-name"><?= e($g['patient_name']) ?></span>
                                                    <span class="user-email"><?= e($g['patient_email']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: #f2500c; font-size: 13px; font-weight: 600;">
                                                <?= e($g['title']) ?>
                                            </strong>
                                        </td>
                                        <td style="max-width: 420px; font-size: 13px; color: #b0b4bd;">
                                            <div style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                                                <?= e($g['recommendation']) ?>
                                            </div>
                                        </td>
                                        <td class="text-right text-muted" style="font-size: 13px; white-space: nowrap;">
                                            <?= date('M d, Y • h:i A', strtotime($g['created_at'])) ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="action-buttons" style="display: inline-flex; gap: 8px;">
                                                <!-- View Full Guidance Modal -->
                                                <button type="button" class="action-btn view-btn" title="View Full Guidance"
                                                        onclick="viewGuidance(<?= htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                </button>

                                                <!-- Delete Guidance -->
                                                <form method="POST" action="guidance.php" style="display: inline;" 
                                                      onsubmit="return confirm('Remove this recommendation entry?');">
                                                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="delete_guidance">
                                                    <input type="hidden" name="rec_id" value="<?= $g['id'] ?>">
                                                    <button type="submit" class="action-btn reject-btn" title="Delete" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </button>
                                                </form>
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

    <!-- Create Guidance Modal -->
    <div id="newGuidanceModal" class="details-modal">
        <div class="modal-box" style="max-width: 580px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2>Compose Patient Guidance</h2>
                    <p>Deliver actionable nutrition advice to your assigned user</p>
                </div>
                <button type="button" class="close-btn" onclick="closeNewGuidanceModal()">&times;</button>
            </div>

            <form method="POST" action="guidance.php">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="create_guidance">

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Select Patient *</label>
                    <select name="patient_id" required style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px;">
                        <option value="">-- Choose Assigned Patient --</option>
                        <?php foreach ($assignedPatients as $ap): ?>
                            <option value="<?= (int)$ap['id'] ?>" <?= ($filterPatient === (int)$ap['id']) ? 'selected' : '' ?>>
                                <?= e($ap['name']) ?> (<?= e(ucwords(str_replace('_', ' ', $ap['health_goal'] ?? 'General'))) ?>, <?= (int)($ap['daily_calorie_target'] ?? 2000) ?> kcal)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Guidance Title / Focus Area *</label>
                    <input type="text" name="title" required placeholder="e.g. Protein Distribution & Pre-Workout Carbohydrate Window" 
                           style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px;">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Prescribed Recommendations & Advice *</label>
                    <textarea name="recommendation" rows="6" required placeholder="Detail specific dietary guidelines: target protein per meal, hydration, micronutrients to emphasize, recommended snacks, and foods to minimize..." 
                              style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="filter-btn" onclick="closeNewGuidanceModal()" style="border: 1px solid var(--border-color);">Cancel</button>
                    <button type="submit" class="primary-btn" style="margin-bottom: 0; width: auto; padding: 10px 24px; background: #3498db;">Send Guidance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Guidance Details Modal -->
    <div id="viewGuidanceModal" class="details-modal">
        <div class="modal-box" style="max-width: 560px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="viewGuidanceTitle">Nutritional Guidance</h2>
                    <p id="viewGuidanceMeta">Patient & Date</p>
                </div>
                <button type="button" class="close-btn" onclick="closeViewGuidanceModal()">&times;</button>
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 24px;">
                <h4 style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin: 0 0 8px 0;">Prescription / Dietary Instructions</h4>
                <div id="viewGuidanceContent" style="font-size: 14px; color: #f0f2f5; line-height: 1.6; white-space: pre-wrap;"></div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="primary-btn" onclick="closeViewGuidanceModal()" style="margin-bottom: 0;">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openNewGuidanceModal() {
            document.getElementById('newGuidanceModal').classList.add('active');
        }

        function closeNewGuidanceModal() {
            document.getElementById('newGuidanceModal').classList.remove('active');
        }

        function viewGuidance(g) {
            document.getElementById('viewGuidanceTitle').innerText = g.title;
            document.getElementById('viewGuidanceMeta').innerText = 'Prescribed to ' + g.patient_name + ' • ' + g.created_at;
            document.getElementById('viewGuidanceContent').innerText = g.recommendation;
            document.getElementById('viewGuidanceModal').classList.add('active');
        }

        function closeViewGuidanceModal() {
            document.getElementById('viewGuidanceModal').classList.remove('active');
        }
    </script>
</body>
</html>
