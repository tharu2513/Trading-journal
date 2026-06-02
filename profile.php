<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$userId = $auth->getUserId();
$db = Database::getInstance();

$settings = $db->fetchOne("SELECT * FROM user_settings WHERE user_id=?", [$userId], 'i');
if (!$settings) {
    $db->insert("INSERT INTO user_settings (user_id) VALUES (?)", [$userId], 'i');
    $settings = $db->fetchOne("SELECT * FROM user_settings WHERE user_id=?", [$userId], 'i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auth->verifyCSRF($_POST['csrf_token'] ?? '')) {
    $act = $_POST['form_action'] ?? '';
    if ($act === 'update_profile') {
        $un = trim($_POST['username'] ?? '');
        if (strlen($un) < 3) { setFlash('error', 'Username min 3 chars.'); }
        else {
            $ex = $db->fetchOne("SELECT id FROM users WHERE username=? AND id!=?", [$un, $userId], 'si');
            if ($ex) { setFlash('error', 'Username already taken.'); }
            else {
                $db->update("UPDATE users SET username=? WHERE id=?", [$un, $userId], 'si');
                $_SESSION['username'] = $un;
                setFlash('success', 'Profile updated!');
            }
        }
    } elseif ($act === 'change_password') {
        $email = trim($_POST['verify_email'] ?? '');
        $cur = $_POST['current_password'] ?? '';
        $np = $_POST['new_password'] ?? '';
        $cp = $_POST['confirm_password'] ?? '';
        $u = $db->fetchOne("SELECT email, password FROM users WHERE id=?", [$userId], 'i');
        if ($email !== $u['email']) { setFlash('error', 'Email verification failed. Enter your registered email.'); }
        elseif (!password_verify($cur, $u['password'])) { setFlash('error', 'Current password is incorrect.'); }
        elseif (strlen($np) < 6) { setFlash('error', 'New password min 6 chars.'); }
        elseif ($np !== $cp) { setFlash('error', 'Passwords do not match.'); }
        else {
            $db->update("UPDATE users SET password=? WHERE id=?", [password_hash($np, PASSWORD_BCRYPT), $userId], 'si');
            setFlash('success', 'Password changed successfully!');
        }
    } elseif ($act === 'update_settings') {
        $de = sanitize($_POST['default_exchange'] ?? 'Binance');
        $dl = max(1, (int)($_POST['default_leverage'] ?? 1));
        $rp = max(0, min(100, (float)($_POST['risk_per_trade'] ?? 2)));
        $db->update("UPDATE user_settings SET default_exchange=?, default_leverage=?, risk_per_trade=? WHERE user_id=?", [$de, $dl, $rp, $userId], 'sidi');
        setFlash('success', 'Settings saved!');
    }
    header('Location: /trading/profile.php'); exit;
}

$currentUser = $auth->getCurrentUser();
$stats = getTradeStats($userId);
$winRate = getWinRate($userId);
$csrf = $auth->generateCSRF();
// Mask email for display
$emailParts = explode('@', $currentUser['email']);
$maskedEmail = substr($emailParts[0], 0, 2) . str_repeat('*', max(3, strlen($emailParts[0])-2)) . '@' . $emailParts[1];
?>

<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user text-primary"></i> Profile Information</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="form_action" value="update_profile">
                <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" value="<?php echo sanitize($currentUser['username']); ?>" required minlength="3"></div>
                <div class="form-group"><label>Email</label>
                    <div class="form-control" style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed;"><i class="fas fa-lock" style="margin-right:8px;"></i><?php echo $maskedEmail; ?></div>
                    <small class="text-muted">Email is hidden for security. Use it to verify password changes.</small>
                </div>
                <div class="form-group"><label>Role</label>
                    <div class="form-control" style="background:var(--gray-100);cursor:not-allowed;"><span class="badge badge-<?php echo ($currentUser['role']??'member')==='super_admin'?'danger':(($currentUser['role']??'member')==='admin'?'warning':'primary'); ?>"><?php echo ucfirst(str_replace('_',' ',$currentUser['role']??'member')); ?></span></div>
                </div>
                <div class="form-group"><label>Member Since</label><input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($currentUser['created_at'])); ?>" disabled></div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-lock text-primary"></i> Change Password</h3></div>
        <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:20px;"><i class="fas fa-info-circle"></i> To change your password, verify your identity with your email and current password.</div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="form_action" value="change_password">
                <div class="form-group"><label><i class="fas fa-envelope"></i> Verify Your Email</label><input type="email" name="verify_email" class="form-control" required placeholder="Enter your registered email"></div>
                <div class="form-group"><label><i class="fas fa-lock"></i> Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="form-group"><label><i class="fas fa-key"></i> New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
                <div class="form-group"><label><i class="fas fa-key"></i> Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
            </form>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-cog text-primary"></i> Trading Settings</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="form_action" value="update_settings">
                <div class="form-group"><label>Default Exchange</label>
                    <select name="default_exchange" class="form-control">
                        <?php foreach (getExchanges() as $ex): ?><option value="<?php echo $ex; ?>" <?php echo ($settings['default_exchange']??'')===$ex?'selected':''; ?>><?php echo $ex; ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Default Leverage</label><input type="number" name="default_leverage" class="form-control" min="1" max="200" value="<?php echo $settings['default_leverage']??1; ?>"></div>
                <div class="form-group"><label>Risk Per Trade (%)</label><input type="number" name="risk_per_trade" class="form-control" step="0.1" min="0" max="100" value="<?php echo $settings['risk_per_trade']??2; ?>"></div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-bar text-primary"></i> Your Stats</h3></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="text-center" style="padding:16px;background:var(--primary-bg);border-radius:var(--radius-sm);">
                    <div style="font-size:2rem;font-weight:700;color:var(--primary);"><?php echo $stats['total_trades']; ?></div><div class="text-muted">Total Trades</div>
                </div>
                <div class="text-center" style="padding:16px;background:var(--success-bg);border-radius:var(--radius-sm);">
                    <div style="font-size:2rem;font-weight:700;color:var(--success);"><?php echo $winRate['win_rate']; ?>%</div><div class="text-muted">Win Rate</div>
                </div>
                <div class="text-center" style="padding:16px;background:<?php echo $stats['total_pnl']>=0?'var(--success-bg)':'var(--danger-bg)'; ?>;border-radius:var(--radius-sm);">
                    <div style="font-size:2rem;font-weight:700;color:<?php echo $stats['total_pnl']>=0?'var(--success)':'var(--danger)'; ?>;"><?php echo formatMoney($stats['total_pnl']); ?></div><div class="text-muted">Total PnL</div>
                </div>
                <div class="text-center" style="padding:16px;background:var(--primary-bg);border-radius:var(--radius-sm);">
                    <div style="font-size:2rem;font-weight:700;color:var(--primary);"><?php echo formatMoney($currentUser['balance']); ?></div><div class="text-muted">Balance</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
