<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
$auth = new Auth();
if ($auth->isLoggedIn()) {
    header('Location: /trading/dashboard.php');
} else {
    header('Location: /trading/login.php');
}
exit;
