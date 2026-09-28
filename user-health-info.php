<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Please log in to manage your health information.');
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') === 'user') {
    header('Location: user/health-info.php');
    exit;
} else {
    redirectByRole($_SESSION['user_role'] ?? 'user');
}
