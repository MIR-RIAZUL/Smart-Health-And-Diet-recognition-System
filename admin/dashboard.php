<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Summary metrics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalDietitians = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'dietitian'")->fetchColumn();
$pendingDietitians = (int)$pdo->query("SELECT COUNT(*) FROM dietitian_profiles WHERE approval_status = 'pending'")->fetchColumn();
$totalFoods = (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$totalMealPlans = (int)$pdo->query("SELECT COUNT(*) FROM meal_plans")->fetchColumn();
$totalAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent Activity Data
$recentUsers = $pdo->query("
    SELECT u.id, u.name, u.email, u.status, u.created_at, h.health_goal, h.bmi 
    FROM users u 
    LEFT JOIN health_profiles h ON u.id = h.user_id 
    WHERE u.role = 'user' 
    ORDER BY u.created_at DESC 
    LIMIT 4
")->fetchAll();

$recentDietitians = $pdo->query("
    SELECT u.id, u.name, u.email, dp.specialization, dp.qualification, dp.approval_status, dp.created_at 
    FROM dietitian_profiles dp 
    JOIN users u ON dp.user_id = u.id 
    ORDER BY dp.created_at DESC 
    LIMIT 4
")->fetchAll();

$recentPlans = $pdo->query("
    SELECT mp.id, mp.title, mp.goal, mp.status, mp.created_at, 
           u_patient.name as patient_name, u_diet.name as dietitian_name
    FROM meal_plans mp 
    JOIN users u_patient ON mp.user_id = u_patient.id 
    JOIN users u_diet ON mp.dietitian_id = u_diet.id 
    ORDER BY mp.created_at DESC 
    LIMIT 4
")->fetchAll();

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Admin Dashboard</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-layout">
        
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content" style="overflow-y: auto;">
            <header class="top-header" style="justify-content: space-between;">
                <div>
                    <h1 class="header-title" style="font-size: 22px;">Administrator Dashboard</h1>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">System oversight, access controls, and dietetics administration</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="status-badge status-active">System Online</span>
                </div>
            </header>

            <div class="content-wrapper" style="max-width: 1300px; padding: 30px 40px;">
                <?php renderFlashMessage(); ?>

                <!-- Top Metric KPI Grid (6 Summary Cards) -->
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 30px;">
                    
                    <a href="users.php" style="text-decoration: none;">
                        <div class="summary-card" style="padding: 20px 16px; border: 1px solid transparent; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div class="summary-value text-green" style="font-size: 26px;"><?= $totalUsers ?></div>
                            <div class="summary-label" style="font-size: 13px; margin-top: 4px;">Total Users</div>
                        </div>
                    </a>

                    <a href="dietitians.php" style="text-decoration: none;">
                        <div class="summary-card" style="padding: 20px 16px; border: 1px solid transparent; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div class="summary-value text-blue" style="font-size: 26px;"><?= $totalDietitians ?></div>
                            <div class="summary-label" style="font-size: 13px; margin-top: 4px;">Total Dietitians</div>
                        </div>
                    </a>

                    <a href="dietitians.php?status=pending" style="text-decoration: none;">
                        <div class="summary-card" style="padding: 20px 16px; border: 1px solid <?= ($pendingDietitians > 0) ? '#f39c12' : 'transparent' ?>; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div class="summary-value" style="font-size: 26px; color: #f39c12;"><?= $pendingDietitians ?></div>
                            <div class="summary-label" style="font-size: 13px; margin-top: 4px;">Pending Approvals</div>
                        </div>
                    </a>

                    <a href="foods.php" style="text-decoration: none;">
                        <div class="summary-card" style="padding: 20px 16px; border: 1px solid transparent; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div class="summary-value text-orange" style="font-size: 26px;"><?= $totalFoods ?></div>
                            <div class="summary-label" style="font-size: 13px; margin-top: 4px;">Food Database</div>
                        </div>
                    </a>

                    <a href="reports.php" style="text-decoration: none;">
                        <div class="summary-card" style="padding: 20px 16px; border: 1px solid transparent; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div class="summary-value" style="font-size: 26px; color: #9b59b6;"><?= $totalMealPlans ?></div>
                            <div class="summary-label" style="font-size: 13px; margin-top: 4px;">Meal Plans</div>
                        </div>
                    </a>

                    <div class="summary-card" style="padding: 20px 16px;">
                        <div class="summary-value" style="font-size: 26px; color: var(--text-light);"><?= $totalAccounts ?></div>
                        <div class="summary-label" style="font-size: 13px; margin-top: 4px;">Total Accounts</div>
                    </div>

                </div>

                <!-- Admin Action Hub Grid (Matching Existing Design) -->
                <div class="admin-dashboard-grid" style="max-width: 100%; margin-bottom: 35px; gap: 20px;">
                    <a href="users.php" class="admin-hub-card card-manage" style="padding: 35px 20px; border-radius: 20px;">
                        <div class="icon-wrapper icon-green" style="width: 60px; height: 60px; margin-bottom: 14px;">
                            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <h3 style="font-size: 18px;">Manage Users</h3>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Search, activate, suspend, or delete</p>
                    </a>

                    <a href="dietitians.php" class="admin-hub-card card-approve" style="padding: 35px 20px; border-radius: 20px;">
                        <div class="icon-wrapper icon-blue" style="width: 60px; height: 60px; margin-bottom: 14px;">
                            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
                        </div>
                        <h3 style="font-size: 18px;">Approve Dietitians</h3>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Review credentials & certify applicants</p>
                    </a>

                    <a href="foods.php" class="admin-hub-card card-food" style="padding: 35px 20px; border-radius: 20px;">
                        <div class="icon-wrapper icon-orange" style="width: 60px; height: 60px; margin-bottom: 14px;">
                            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.5"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                        </div>
                        <h3 style="font-size: 18px;">Food Database</h3>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Nutrition facts, macros, & calories CRUD</p>
                    </a>

                    <a href="reports.php" class="admin-hub-card card-reports" style="padding: 35px 20px; border-radius: 20px;">
                        <div class="icon-wrapper icon-purple" style="width: 60px; height: 60px; margin-bottom: 14px;">
                            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="8" y1="18" x2="8" y2="15"></line><line x1="16" y1="18" x2="16" y2="15"></line></svg>
                        </div>
                        <h3 style="font-size: 18px;">System Reports</h3>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Audit trails, activity charts & metrics</p>
                    </a>
                </div>

                <!-- Recent Activity Split Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    
                    <!-- Left: Recent User Registrations -->
                    <div class="panel-card" style="border-radius: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="panel-title" style="margin-bottom: 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#2ecc71" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                                Recent User Registrations
                            </h3>
                            <a href="users.php" style="color: var(--primary-orange); font-size: 12px; font-weight: 600; text-decoration: none;">View All &rarr;</a>
                        </div>

                        <?php if (empty($recentUsers)): ?>
                            <p style="color: var(--text-muted); font-size: 13px;">No users registered yet.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($recentUsers as $ru): ?>
                                    <div style="background-color: var(--bg-dark); border-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="avatar" style="width: 34px; height: 34px; font-size: 12px;">
                                                <?= strtoupper(substr($ru['name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div style="font-size: 14px; font-weight: 600; color: var(--text-light);"><?= e($ru['name']) ?></div>
                                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($ru['email']) ?></div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="status-badge status-<?= ($ru['status'] === 'active') ? 'active' : 'suspended' ?>" style="font-size: 11px; padding: 4px 8px;">
                                                <?= ucfirst(e($ru['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right: Pending Dietitian Applications -->
                    <div class="panel-card" style="border-radius: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="panel-title" style="margin-bottom: 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#f39c12" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                Dietitian Applications
                            </h3>
                            <a href="dietitians.php" style="color: var(--primary-orange); font-size: 12px; font-weight: 600; text-decoration: none;">Review Queue &rarr;</a>
                        </div>

                        <?php if (empty($recentDietitians)): ?>
                            <p style="color: var(--text-muted); font-size: 13px;">No dietitian applications yet.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($recentDietitians as $rd): ?>
                                    <div style="background-color: var(--bg-dark); border-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="avatar avatar-blue" style="width: 34px; height: 34px; font-size: 12px;">
                                                <?= strtoupper(substr($rd['name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div style="font-size: 14px; font-weight: 600; color: var(--text-light);"><?= e($rd['name']) ?></div>
                                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($rd['specialization'] ?? 'Dietetics') ?></div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <?php if ($rd['approval_status'] === 'approved'): ?>
                                                <span class="status-badge status-active" style="font-size: 11px; padding: 4px 8px;">Approved</span>
                                            <?php elseif ($rd['approval_status'] === 'rejected'): ?>
                                                <span class="status-badge status-suspended" style="font-size: 11px; padding: 4px 8px;">Rejected</span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background-color: rgba(243, 156, 18, 0.15); color: #f39c12; font-size: 11px; padding: 4px 8px;">Pending</span>
                                            <?php endif; ?>
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
</body>
</html>
