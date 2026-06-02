<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
$auth = new Auth();
$auth->logout();
header('Location: /trading/login.php');
exit;
