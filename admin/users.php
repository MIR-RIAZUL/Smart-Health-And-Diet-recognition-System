<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$currentUser = getCurrentUser();
$pdo = getDBConnection();

$actionError = '';
$actionSuccess = '';

// Handle Actions: Status Toggle (Activate/Suspend) & Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        setFlashMessage('error', 'Security token mismatch. Please try again.');
        header('Location: users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($targetUserId === (int)$currentUser['id']) {
        setFlashMessage('error', 'You cannot modify or delete your own active administrator account.');
        header('Location: users.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute(['id' => $targetUserId]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus !== false) {
            $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';
            $update = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
            $update->execute(['status' => $newStatus, 'id' => $targetUserId]);
            setFlashMessage('success', "User account status updated to {$newStatus}.");
        }
    } elseif ($action === 'delete_user') {
        $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $del->execute(['id' => $targetUserId]);
        setFlashMessage('success', "User account has been permanently removed.");
    } elseif ($action === 'assign_dietitian') {
        $dietitianId = (int)($_POST['dietitian_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($dietitianId <= 0) {
            // Unassign
            $unassign = $pdo->prepare("DELETE FROM dietitian_assignments WHERE user_id = :uid");
            $unassign->execute(['uid' => $targetUserId]);
            setFlashMessage('success', "Dietitian assignment removed for this user.");
        } else {
            // Verify dietitian exists and is active
            $check = $pdo->prepare("SELECT name FROM users WHERE id = :did AND role = 'dietitian' AND status = 'active' LIMIT 1");
            $check->execute(['did' => $dietitianId]);
            $dietitianName = $check->fetchColumn();

            if ($dietitianName) {
                $assign = $pdo->prepare("
                    INSERT INTO dietitian_assignments (user_id, dietitian_id, assigned_by, notes, assigned_at)
                    VALUES (:uid, :did, :aid, :notes, NOW())
                    ON DUPLICATE KEY UPDATE 
                        dietitian_id = VALUES(dietitian_id),
                        assigned_by = VALUES(assigned_by),
                        notes = VALUES(notes),
                        assigned_at = NOW()
                ");
                $assign->execute([
                    'uid'   => $targetUserId,
                    'did'   => $dietitianId,
                    'aid'   => (int)$currentUser['id'],
                    'notes' => $notes
                ]);
                setFlashMessage('success', "User successfully assigned to Dietitian {$dietitianName}.");
            } else {
                setFlashMessage('error', "Invalid or inactive dietitian selected.");
            }
        }
    }

    header('Location: users.php');
    exit;
}

// Fetch approved active dietitians for assignment dropdown
$dietitiansList = $pdo->query("
    SELECT u.id, u.name, u.email, dp.specialization, dp.qualification 
    FROM users u 
    JOIN dietitian_profiles dp ON u.id = dp.user_id 
    WHERE u.role = 'dietitian' AND u.status = 'active' AND dp.approval_status = 'approved'
    ORDER BY u.name ASC
")->fetchAll();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$query = "
    SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at,
           h.age, h.gender, h.height, h.weight, h.bmi, h.health_goal, h.daily_calorie_target,
           da.dietitian_id as assigned_dietitian_id, da.notes as assignment_notes, da.assigned_at,
           d.name as dietitian_name, dp.specialization as dietitian_specialization
    FROM users u 
    LEFT JOIN health_profiles h ON u.id = h.user_id 
    LEFT JOIN dietitian_assignments da ON u.id = da.user_id
    LEFT JOIN users d ON da.dietitian_id = d.id
    LEFT JOIN dietitian_profiles dp ON d.id = dp.user_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search OR d.name LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($statusFilter === 'active') {
    $query .= " AND u.status = 'active'";
} elseif ($statusFilter === 'suspended') {
    $query .= " AND u.status = 'suspended'";
} elseif ($statusFilter === 'pending') {
    $query .= " AND u.status = 'pending'";
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

// Total count
$totalCount = count($usersList);
$currentPage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Manage Users</title>
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

                <!-- Controls Bar (Title, Search, Filters) -->
                <div class="admin-controls-bar">
                    <div class="controls-left">
                        <div class="title-icon icon-green-outline">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="section-title">Manage Users</h2>
                            <p class="section-subtitle"><?= $totalCount ?> user accounts found</p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="users.php" class="search-box">
                        <?php if ($statusFilter !== 'all'): ?>
                            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                        <?php endif; ?>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, phone...">
                    </form>

                    <!-- Filter Pills -->
                    <div class="filter-pills">
                        <a href="users.php?<?= !empty($search) ? 'search='.urlencode($search).'&' : '' ?>status=all" 
                           class="filter-pill <?= ($statusFilter === 'all') ? 'active' : '' ?>">All</a>
                        <a href="users.php?<?= !empty($search) ? 'search='.urlencode($search).'&' : '' ?>status=active" 
                           class="filter-pill <?= ($statusFilter === 'active') ? 'active' : '' ?>">Active</a>
                        <a href="users.php?<?= !empty($search) ? 'search='.urlencode($search).'&' : '' ?>status=suspended" 
                           class="filter-pill <?= ($statusFilter === 'suspended') ? 'active' : '' ?>">Suspended</a>
                    </div>
                </div>

                <!-- Data Table Card -->
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>USER</th>
                                <th>ROLE</th>
                                <th>GOAL / STATS</th>
                                <th>ASSIGNED DIETITIAN</th>
                                <th class="text-right">BMI</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-right">REGISTERED</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usersList)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No users matched your search criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usersList as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="avatar <?= ($u['role'] === 'dietitian') ? 'avatar-blue' : '' ?>" style="font-size: 13px;">
                                                    <?= strtoupper(substr($u['name'], 0, 2)) ?>
                                                </div>
                                                <div class="user-info">
                                                    <span class="user-name"><?= e($u['name']) ?></span>
                                                    <span class="user-email"><?= e($u['email']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: <?= ($u['role'] === 'admin') ? '#2ecc71' : (($u['role'] === 'dietitian') ? '#3498db' : '#f2500c') ?>;">
                                                <?= e($u['role']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted" style="font-size: 13px;">
                                            <?= e(ucwords(str_replace('_', ' ', $u['health_goal'] ?? 'General health'))) ?>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'user'): ?>
                                                <?php if (!empty($u['assigned_dietitian_id'])): ?>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background: rgba(52, 152, 219, 0.15); border: 1px solid rgba(52, 152, 219, 0.3); border-radius: 20px; font-size: 12px; color: #3498db; font-weight: 500;">
                                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                                            <?= e($u['dietitian_name']) ?>
                                                        </span>
                                                        <button type="button" class="action-btn" title="Reassign Dietitian" style="background: none; border: none; padding: 2px; color: #8c909a; cursor: pointer;"
                                                                onclick="openAssignModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name']), ENT_QUOTES) ?>', <?= (int)$u['assigned_dietitian_id'] ?>, '<?= htmlspecialchars(addslashes($u['assignment_notes'] ?? ''), ENT_QUOTES) ?>')">
                                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button" class="action-pill pill-orange" style="font-size: 11px; padding: 4px 10px; border: none; cursor: pointer;"
                                                            onclick="openAssignModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name']), ENT_QUOTES) ?>', 0, '')">
                                                        + Assign Dietitian
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #606470; font-size: 12px;">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right font-bold" style="color: var(--text-light);">
                                            <?= $u['bmi'] ? number_format($u['bmi'], 1) : '--' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-badge status-<?= ($u['status'] === 'active') ? 'active' : 'suspended' ?>">
                                                <?= ucfirst(e($u['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-right text-muted" style="font-size: 13px;">
                                            <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="action-buttons" style="display: inline-flex; gap: 8px;">
                                                
                                                <?php if ($u['role'] === 'user'): ?>
                                                    <!-- Assign Dietitian Button -->
                                                    <button type="button" class="action-btn" title="Assign / Reassign Dietitian" style="color: #3498db; background: rgba(52, 152, 219, 0.1);"
                                                            onclick="openAssignModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name']), ENT_QUOTES) ?>', <?= (int)($u['assigned_dietitian_id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($u['assignment_notes'] ?? ''), ENT_QUOTES) ?>')">
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- View User Details Modal Trigger -->
                                                <button type="button" class="action-btn view-btn" title="View Details" 
                                                        onclick="openUserModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                </button>

                                                <?php if ($u['id'] !== $currentUser['id']): ?>
                                                    <!-- Toggle Status (Activate / Suspend) -->
                                                    <form method="POST" action="users.php" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to <?= ($u['status'] === 'active') ? 'suspend' : 'activate' ?> this account?');">
                                                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <button type="submit" class="action-btn ban-btn" title="<?= ($u['status'] === 'active') ? 'Suspend Account' : 'Activate Account' ?>">
                                                            <?php if ($u['status'] === 'active'): ?>
                                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                                            <?php else: ?>
                                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#2ecc71" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                            <?php endif; ?>
                                                        </button>
                                                    </form>

                                                    <!-- Delete User -->
                                                    <form method="POST" action="users.php" style="display: inline;" 
                                                          onsubmit="return confirm('WARNING: Permanently delete <?= e($u['name']) ?>? This action cannot be undone.');">
                                                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <button type="submit" class="action-btn reject-btn" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;" title="Delete User">
                                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

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

    <!-- User Profile Details Modal -->
    <div id="userModal" class="details-modal">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="modalUserName">User Profile</h2>
                    <p id="modalUserEmail">Details & Biometric Overview</p>
                </div>
                <button type="button" class="close-btn" onclick="closeUserModal()">&times;</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <div class="detail-box">
                    <span class="detail-label">Age</span>
                    <span class="detail-val" id="modalAge">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Gender</span>
                    <span class="detail-val" id="modalGender">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Height</span>
                    <span class="detail-val" id="modalHeight">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Weight</span>
                    <span class="detail-val" id="modalWeight">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Calculated BMI</span>
                    <span class="detail-val" id="modalBMI">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Calorie Target</span>
                    <span class="detail-val" id="modalCalories">--</span>
                </div>
            </div>

            <div class="detail-box" style="margin-bottom: 24px;">
                <span class="detail-label">Primary Health Goal</span>
                <span class="detail-val" id="modalGoal">--</span>
            </div>

            <button type="button" class="primary-btn" onclick="closeUserModal()" style="margin-bottom: 0;">Close</button>
        </div>
    </div>

    <!-- Assign Dietitian Modal -->
    <div id="assignModal" class="details-modal">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="assignModalTitle">Assign Dietitian</h2>
                    <p id="assignModalSubtitle">Designate a clinical nutritionist to guide this user</p>
                </div>
                <button type="button" class="close-btn" onclick="closeAssignModal()">&times;</button>
            </div>

            <form method="POST" action="users.php">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="assign_dietitian">
                <input type="hidden" name="user_id" id="assignUserId" value="0">

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Select Dietitian *</label>
                    <select id="dietitianSelect" name="dietitian_id" style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px;" required>
                        <option value="0">-- Unassigned (Remove Assignment) --</option>
                        <?php foreach ($dietitiansList as $dt): ?>
                            <option value="<?= (int)$dt['id'] ?>">
                                <?= e($dt['name']) ?> (<?= e($dt['specialization'] ?: 'General Nutrition') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Clinical Focus / Assignment Notes (Optional)</label>
                    <textarea id="assignmentNotes" name="notes" rows="3" placeholder="e.g. Focus on high-protein meal planning, weight loss target of 70kg, monitor daily hydration..." style="width: 100%; padding: 12px 14px; background: #111419; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-light); font-size: 14px; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="filter-btn" onclick="closeAssignModal()" style="border: 1px solid var(--border-color);">Cancel</button>
                    <button type="submit" class="primary-btn" style="margin-bottom: 0; width: auto; padding: 10px 24px;">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUserModal(user) {
            document.getElementById('modalUserName').innerText = user.name;
            document.getElementById('modalUserEmail').innerText = user.email + (user.phone ? ' • ' + user.phone : '');
            document.getElementById('modalAge').innerText = user.age ? user.age + ' yrs' : 'Not set';
            document.getElementById('modalGender').innerText = user.gender ? user.gender.toUpperCase() : 'Not set';
            document.getElementById('modalHeight').innerText = user.height ? user.height + ' cm' : 'Not set';
            document.getElementById('modalWeight').innerText = user.weight ? user.weight + ' kg' : 'Not set';
            document.getElementById('modalBMI').innerText = user.bmi ? user.bmi : 'Not calculated';
            document.getElementById('modalCalories').innerText = user.daily_calorie_target ? user.daily_calorie_target + ' kcal' : '2000 kcal';
            document.getElementById('modalGoal').innerText = user.health_goal ? user.health_goal.replace('_', ' ').toUpperCase() : 'GENERAL HEALTH';

            document.getElementById('userModal').classList.add('active');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function openAssignModal(userId, userName, currentDietitianId, notes) {
            document.getElementById('assignUserId').value = userId;
            document.getElementById('assignModalTitle').innerText = 'Assign Dietitian: ' + userName;
            document.getElementById('assignModalSubtitle').innerText = 'Select a qualified practitioner to guide ' + userName;
            document.getElementById('dietitianSelect').value = currentDietitianId || 0;
            document.getElementById('assignmentNotes').value = notes || '';
            document.getElementById('assignModal').classList.add('active');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
        }
    </script>
</body>
</html>
