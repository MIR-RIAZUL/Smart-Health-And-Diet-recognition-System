<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

$pdo = getDBConnection();

// Fetch assigned dietitian details
$dtStmt = $pdo->prepare("
    SELECT u.name as dietitian_name, u.email as dietitian_email, u.phone as dietitian_phone,
           dp.specialization, dp.qualification, dp.experience, dp.certification,
           da.assigned_at, da.notes as admin_assignment_notes
    FROM dietitian_assignments da
    JOIN users u ON da.dietitian_id = u.id
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE da.user_id = :uid
    LIMIT 1
");
$dtStmt->execute(['uid' => $userId]);
$assignedDietitian = $dtStmt->fetch();

// Fetch all recommendations received from dietitians
$recStmt = $pdo->prepare("
    SELECT r.*, u.name as dietitian_name, dp.specialization as dietitian_specialization
    FROM recommendations r
    JOIN users u ON r.dietitian_id = u.id
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE r.user_id = :uid
    ORDER BY r.created_at DESC
");
$recStmt->execute(['uid' => $userId]);
$recommendations = $recStmt->fetchAll();

// Fetch user profile for quick reference
$profStmt = $pdo->prepare("SELECT * FROM health_profiles WHERE user_id = :uid LIMIT 1");
$profStmt->execute(['uid' => $userId]);
$profile = $profStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - My Dietitian Guidance</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container small-logo">
                    <div class="filled-heart-icon">
                        <svg viewBox="0 0 24 24" fill="white" width="16" height="16">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <span class="logo-text-small">Health Track</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Overview
                </a>
                <a href="health-info.php" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    Health Information
                </a>
                <a href="guidance.php" class="nav-link active">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Dietitian Guidance
                </a>
                <a href="../log-meals.html" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    Log Meals
                </a>
                <a href="../calorie-reports.html" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>
                    Calorie Reports
                </a>
                <a href="#" class="nav-link">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    My Diet Plans
                </a>
            </nav>

            <div class="sidebar-footer">
                <div style="font-size: 13px; color: var(--text-light); font-weight: 600; margin-bottom: 2px;"><?= e($currentUser['name']) ?></div>
                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 14px;"><?= e($currentUser['email']) ?></div>
                <a href="../logout.php" class="nav-link sign-out">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content" style="overflow-y: auto;">
            <header class="top-header">
                <div>
                    <h1 class="header-title">Personalized Dietary Guidance</h1>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Custom nutrition strategies and clinical dietary advice from your assigned dietitian</p>
                </div>
            </header>

            <div class="content-wrapper" style="max-width: 1000px;">
                <?php renderFlashMessage(); ?>

                <!-- Assigned Dietitian Banner Card -->
                <?php if ($assignedDietitian): ?>
                    <div class="panel-card" style="padding: 24px; margin-bottom: 28px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.08) 0%, rgba(26, 29, 36, 0.95) 100%); border: 1px solid rgba(52, 152, 219, 0.3);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                            <div style="display: flex; gap: 16px; align-items: center;">
                                <div class="avatar avatar-blue" style="width: 52px; height: 52px; font-size: 18px; border-radius: 50%;">
                                    <?= strtoupper(substr($assignedDietitian['dietitian_name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <h3 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 0;">
                                            Dr. <?= e($assignedDietitian['dietitian_name']) ?>
                                        </h3>
                                        <span class="status-badge status-active" style="font-size: 11px;">Assigned Specialist</span>
                                    </div>
                                    <p style="font-size: 13px; color: #3498db; margin: 4px 0 0 0; font-weight: 500;">
                                        <?= e($assignedDietitian['specialization'] ?: 'Clinical Nutrition Specialist') ?> • <?= e($assignedDietitian['qualification'] ?: 'Registered Dietitian') ?>
                                    </p>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                                        Contact: <span style="color: var(--text-light);"><?= e($assignedDietitian['dietitian_email']) ?></span>
                                        <?php if (!empty($assignedDietitian['dietitian_phone'])): ?>
                                            • <span><?= e($assignedDietitian['dietitian_phone']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Assigned Since</span>
                                <div style="font-size: 13px; color: var(--text-light); font-weight: 600;">
                                    <?= date('M d, Y', strtotime($assignedDietitian['assigned_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($assignedDietitian['admin_assignment_notes'])): ?>
                            <div style="margin-top: 16px; padding: 10px 14px; background: rgba(243, 156, 18, 0.1); border-left: 3px solid #f39c12; border-radius: 6px; font-size: 12px; color: #f39c12;">
                                <strong>Clinical Note:</strong> <?= e($assignedDietitian['admin_assignment_notes']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="panel-card" style="padding: 24px; margin-bottom: 28px; background: rgba(242, 80, 12, 0.06); border: 1px solid rgba(242, 80, 12, 0.3);">
                        <div style="display: flex; gap: 14px; align-items: center;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(242, 80, 12, 0.15); display: flex; align-items: center; justify-content: center; color: #f2500c; flex-shrink: 0;">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            </div>
                            <div>
                                <h4 style="font-size: 16px; font-weight: 600; color: #ffffff; margin: 0 0 4px 0;">Dietitian Assignment in Progress</h4>
                                <p style="font-size: 13px; color: #b0b4bd; margin: 0;">
                                    Your profile is active. Our clinical administrator will assign a certified nutritionist tailored to your health goal (<strong><?= e(ucwords(str_replace('_', ' ', $profile['health_goal'] ?? 'General fitness'))) ?></strong>). Once assigned, your dietary guidelines will be delivered here.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section: Nutritional Recommendations History -->
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 17px; font-weight: 600; color: #ffffff; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#2ecc71" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Clinical Recommendations & Diet Advice (<?= count($recommendations) ?>)
                    </h2>

                    <?php if (empty($recommendations)): ?>
                        <div class="panel-card" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#606470" stroke-width="1.5" style="margin-bottom: 12px;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            <p style="font-size: 15px; font-weight: 600; color: var(--text-light); margin-bottom: 4px;">No Guidance Issued Yet</p>
                            <p style="font-size: 13px; max-width: 440px; margin: 0 auto;">
                                When your dietitian analyzes your weekly meal logs and biometrics, they will send customized nutritional advice and dietary instructions here.
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($recommendations as $rec): ?>
                                <div class="panel-card" style="padding: 24px; border-left: 4px solid #f2500c; position: relative;">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                                        <h3 style="font-size: 16px; font-weight: 700; color: #f2500c; margin: 0;">
                                            <?= e($rec['title']) ?>
                                        </h3>
                                        <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
                                            <?= date('M d, Y • h:i A', strtotime($rec['created_at'])) ?>
                                        </span>
                                    </div>

                                    <div style="font-size: 14px; color: #e0e4ec; line-height: 1.6; margin-bottom: 16px; white-space: pre-wrap; background: rgba(0,0,0,0.2); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                                        <?= e($rec['recommendation']) ?>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 12px;">
                                        <div>
                                            Guidance by: <strong style="color: #3498db;">Dr. <?= e($rec['dietitian_name']) ?></strong> (<?= e($rec['dietitian_specialization'] ?: 'Nutritionist') ?>)
                                        </div>
                                        <span style="color: #2ecc71; font-weight: 500;">Active Clinical Guidance</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
