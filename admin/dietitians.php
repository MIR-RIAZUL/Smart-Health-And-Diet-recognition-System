<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Handle Approve / Reject / Status Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        setFlashMessage('error', 'Security token mismatch. Please try again.');
        header('Location: dietitians.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $dietitianProfileId = (int)($_POST['profile_id'] ?? 0);
    $dietitianUserId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'approve') {
        $pdo->beginTransaction();
        try {
            $stmt1 = $pdo->prepare("UPDATE dietitian_profiles SET approval_status = 'approved', updated_at = NOW() WHERE id = :id");
            $stmt1->execute(['id' => $dietitianProfileId]);

            $stmt2 = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = :uid");
            $stmt2->execute(['uid' => $dietitianUserId]);

            $pdo->commit();
            setFlashMessage('success', 'Dietitian application has been APPROVED. The practitioner now has full portal access.');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage('error', 'Failed to approve dietitian: ' . $e->getMessage());
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'Qualifications do not meet minimum requirements at this time.');
        $pdo->beginTransaction();
        try {
            $stmt1 = $pdo->prepare("UPDATE dietitian_profiles SET approval_status = 'rejected', rejection_reason = :reason, updated_at = NOW() WHERE id = :id");
            $stmt1->execute(['id' => $dietitianProfileId, 'reason' => $reason]);

            $stmt2 = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = :uid");
            $stmt2->execute(['uid' => $dietitianUserId]);

            $pdo->commit();
            setFlashMessage('warning', 'Dietitian application has been REJECTED.');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage('error', 'Failed to reject dietitian: ' . $e->getMessage());
        }
    } elseif ($action === 'toggle_active') {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :uid");
        $stmt->execute(['uid' => $dietitianUserId]);
        $current = $stmt->fetchColumn();
        $new = ($current === 'active') ? 'suspended' : 'active';

        $update = $pdo->prepare("UPDATE users SET status = :status WHERE id = :uid");
        $update->execute(['status' => $new, 'uid' => $dietitianUserId]);
        setFlashMessage('success', "Dietitian account has been {$new}.");
    }

    header('Location: dietitians.php');
    exit;
}

// Filter parameters
$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT dp.*, u.name, u.email, u.phone, u.status as user_status, u.created_at as registered_at,
           COALESCE(da.patient_count, 0) as patient_count
    FROM dietitian_profiles dp
    JOIN users u ON dp.user_id = u.id
    LEFT JOIN (SELECT dietitian_id, COUNT(*) as patient_count FROM dietitian_assignments GROUP BY dietitian_id) da ON dp.user_id = da.dietitian_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR dp.specialization LIKE :search OR dp.qualification LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($statusFilter === 'pending') {
    $query .= " AND dp.approval_status = 'pending'";
} elseif ($statusFilter === 'approved') {
    $query .= " AND dp.approval_status = 'approved'";
} elseif ($statusFilter === 'rejected') {
    $query .= " AND dp.approval_status = 'rejected'";
}

$query .= " ORDER BY FIELD(dp.approval_status, 'pending', 'approved', 'rejected'), dp.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$dietitiansList = $stmt->fetchAll();

// Quick counters
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM dietitian_profiles WHERE approval_status = 'pending'")->fetchColumn();
$approvedCount = (int)$pdo->query("SELECT COUNT(*) FROM dietitian_profiles WHERE approval_status = 'approved'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM dietitian_profiles WHERE approval_status = 'rejected'")->fetchColumn();

$currentPage = 'dietitians';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Manage & Approve Dietitians</title>
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

                <!-- Controls Bar -->
                <div class="admin-controls-bar">
                    <div class="controls-left">
                        <div class="title-icon icon-blue-outline">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                                <polyline points="16 11 18 13 22 9"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h2 class="section-title">Dietitian Management & Approvals</h2>
                            <p class="section-subtitle"><?= $pendingCount ?> pending credential reviews</p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="dietitians.php" class="search-box">
                        <?php if ($statusFilter !== 'all'): ?>
                            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                        <?php endif; ?>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, specialization, qualification...">
                    </form>

                    <!-- Filter Pills -->
                    <div class="filter-pills">
                        <a href="dietitians.php?status=all" class="filter-pill <?= ($statusFilter === 'all') ? 'active' : '' ?>">
                            All (<?= $pendingCount + $approvedCount + $rejectedCount ?>)
                        </a>
                        <a href="dietitians.php?status=pending" class="filter-pill <?= ($statusFilter === 'pending') ? 'active' : '' ?>">
                            Pending (<?= $pendingCount ?>)
                        </a>
                        <a href="dietitians.php?status=approved" class="filter-pill <?= ($statusFilter === 'approved') ? 'active' : '' ?>">
                            Approved (<?= $approvedCount ?>)
                        </a>
                        <a href="dietitians.php?status=rejected" class="filter-pill <?= ($statusFilter === 'rejected') ? 'active' : '' ?>">
                            Rejected (<?= $rejectedCount ?>)
                        </a>
                    </div>
                </div>

                <!-- Dietitians Card Grid (Matching Existing Design) -->
                <?php if (empty($dietitiansList)): ?>
                    <div class="panel-card" style="text-align: center; padding: 60px; color: var(--text-muted);">
                        <h3>No dietitians found for the selected criteria.</h3>
                    </div>
                <?php else: ?>
                    <div class="dietitian-grid">
                        <?php foreach ($dietitiansList as $d): ?>
                            <div class="dietitian-card">
                                <div class="card-header-flex">
                                    <div class="user-cell">
                                        <div class="avatar avatar-blue">
                                            <?= strtoupper(substr($d['name'], 0, 2)) ?>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name" style="font-size: 16px;"><?= e($d['name']) ?></span>
                                            <span class="user-email"><?= e($d['specialization'] ?? 'Dietetics') ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($d['approval_status'] === 'approved'): ?>
                                            <span class="status-badge status-active">Approved</span>
                                        <?php elseif ($d['approval_status'] === 'rejected'): ?>
                                            <span class="status-badge status-suspended">Rejected</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background-color: rgba(243, 156, 18, 0.15); color: #f39c12;">Pending Review</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="details-grid">
                                    <div class="detail-box">
                                        <span class="detail-label">Qualifications</span>
                                        <span class="detail-val"><?= e($d['qualification'] ?? 'M.Sc Nutrition') ?></span>
                                    </div>
                                    <div class="detail-box">
                                        <span class="detail-label">Experience</span>
                                        <span class="detail-val"><?= (int)$d['experience'] ?> years</span>
                                    </div>
                                    <div class="detail-box">
                                        <span class="detail-label">Certifications</span>
                                        <span class="detail-val"><?= e($d['certification'] ?? 'Licensed Dietitian') ?></span>
                                    </div>
                                    <div class="detail-box">
                                        <span class="detail-label">Assigned Patients</span>
                                        <span class="detail-val" style="color: #3498db; font-weight: 600;">
                                            <?= (int)$d['patient_count'] ?> active
                                        </span>
                                    </div>
                                </div>

                                <div class="card-actions">
                                    <!-- View Profile Modal -->
                                    <button type="button" class="card-action-btn view-action" 
                                            onclick="openDietitianModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        View
                                    </button>

                                    <?php if ($d['approval_status'] === 'pending' || $d['approval_status'] === 'rejected'): ?>
                                        <!-- Approve Form -->
                                        <form method="POST" action="dietitians.php" style="flex: 1;" onsubmit="return confirm('Approve Dr. <?= e($d['name']) ?> to practice and create patient diet plans?');">
                                            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="profile_id" value="<?= $d['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $d['user_id'] ?>">
                                            <button type="submit" class="card-action-btn approve-action" style="width: 100%;">
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($d['approval_status'] === 'pending' || $d['approval_status'] === 'approved'): ?>
                                        <!-- Reject Form -->
                                        <form method="POST" action="dietitians.php" style="flex: 1;" onsubmit="return confirm('Reject application for Dr. <?= e($d['name']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="profile_id" value="<?= $d['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $d['user_id'] ?>">
                                            <button type="submit" class="card-action-btn reject-action" style="width: 100%;">
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($d['approval_status'] === 'approved'): ?>
                                        <!-- Active / Suspend toggle -->
                                        <form method="POST" action="dietitians.php" style="flex: 1;" onsubmit="return confirm('Change active status for Dr. <?= e($d['name']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="profile_id" value="<?= $d['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $d['user_id'] ?>">
                                            <button type="submit" class="card-action-btn" style="width: 100%; background: #2a2f3a; color: var(--text-light);">
                                                <?= ($d['user_status'] === 'active') ? 'Suspend' : 'Activate' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Dietitian Detailed Profile Modal -->
    <div id="dietitianModal" class="details-modal">
        <div class="modal-box" style="max-width: 550px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="modalDietitianName">Dr. Sarah Miller</h2>
                    <p id="modalDietitianEmail">Credentials & Accreditation</p>
                </div>
                <button type="button" class="close-btn" onclick="closeDietitianModal()">&times;</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div class="detail-box">
                    <span class="detail-label">Phone</span>
                    <span class="detail-val" id="modalPhone">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Experience</span>
                    <span class="detail-val" id="modalExp">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Approval Status</span>
                    <span class="detail-val" id="modalApproval">--</span>
                </div>
                <div class="detail-box">
                    <span class="detail-label">Account Status</span>
                    <span class="detail-val" id="modalStatus">--</span>
                </div>
            </div>

            <div class="detail-box" style="margin-bottom: 14px;">
                <span class="detail-label">Academic Degrees / Qualifications</span>
                <span class="detail-val" id="modalQual">--</span>
            </div>

            <div class="detail-box" style="margin-bottom: 14px;">
                <span class="detail-label">Certifications / Clinical Licensing</span>
                <span class="detail-val" id="modalCert">--</span>
            </div>

            <div class="detail-box" style="margin-bottom: 24px;">
                <span class="detail-label">Specialization</span>
                <span class="detail-val" id="modalSpec">--</span>
            </div>

            <button type="button" class="primary-btn" onclick="closeDietitianModal()" style="margin-bottom: 0;">Close</button>
        </div>
    </div>

    <script>
        function openDietitianModal(data) {
            document.getElementById('modalDietitianName').innerText = data.name;
            document.getElementById('modalDietitianEmail').innerText = data.email;
            document.getElementById('modalPhone').innerText = data.phone ? data.phone : 'Not provided';
            document.getElementById('modalExp').innerText = data.experience + ' years';
            document.getElementById('modalApproval').innerText = data.approval_status.toUpperCase();
            document.getElementById('modalStatus').innerText = data.user_status.toUpperCase();
            document.getElementById('modalQual').innerText = data.qualification || 'M.Sc Nutrition';
            document.getElementById('modalCert').innerText = data.certification || 'Certified Clinical Nutritionist';
            document.getElementById('modalSpec').innerText = data.specialization || 'General Nutrition';

            document.getElementById('dietitianModal').classList.add('active');
        }

        function closeDietitianModal() {
            document.getElementById('dietitianModal').classList.remove('active');
        }
    </script>
</body>
</html>
