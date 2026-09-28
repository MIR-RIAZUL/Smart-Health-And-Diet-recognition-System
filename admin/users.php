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
    }

    header('Location: users.php');
    exit;
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$query = "
    SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at,
           h.age, h.gender, h.height, h.weight, h.bmi, h.health_goal, h.daily_calorie_target
    FROM users u 
    LEFT JOIN health_profiles h ON u.id = h.user_id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
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
                                <th class="text-right">BMI</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-right">REGISTERED</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usersList)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
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
    </script>
</body>
</html>
