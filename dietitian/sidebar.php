<?php
// Shared Dietitian Sidebar Navigation
$currentPage = $currentPage ?? 'dashboard';
?>
<aside class="sidebar dietitian-sidebar">
    <div class="sidebar-header">
        <div class="logo-container small-logo">
            <div class="filled-heart-icon" style="background-color: #3498db; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="white" width="16" height="16">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            </div>
            <div class="sidebar-title-group">
                <span class="logo-text-small">Health Track</span>
                <span class="sidebar-subtext" style="color: #3498db; font-size: 11px; font-weight: 600;">Dietitian Portal</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav dietitian-nav">
        <a href="dashboard.php" class="nav-link dietitian-nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        <a href="patients.php" class="nav-link dietitian-nav-link <?= ($currentPage === 'patients') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Assigned Patients
        </a>
        <a href="guidance.php" class="nav-link dietitian-nav-link <?= ($currentPage === 'guidance') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Patient Guidance
        </a>
        <a href="../dietitian-meal-plans.html" class="nav-link dietitian-nav-link <?= ($currentPage === 'meal-plans') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Meal Plans
        </a>
        <a href="../dietitian-guides.html" class="nav-link dietitian-nav-link <?= ($currentPage === 'guides') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Nutritional Guides
        </a>
    </nav>

    <div class="sidebar-footer border-top-footer">
        <div class="user-profile-compact">
            <div class="avatar avatar-blue">
                <?= strtoupper(substr($currentUser['name'], 0, 2)) ?>
            </div>
            <div class="profile-text">
                <span class="profile-name"><?= e($currentUser['name']) ?></span>
                <span class="profile-role"><?= e($profile['specialization'] ?? 'Registered Dietitian') ?></span>
            </div>
        </div>
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
