<?php
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlashMessage('success', 'You have been successfully logged out.');

$redirect = !empty($_GET['redirect']) && in_array($_GET['redirect'], ['register.php', 'login.php']) ? $_GET['redirect'] : 'login.php';
header("Location: $redirect");
exit;
