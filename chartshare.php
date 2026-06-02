<?php
$pageTitle = 'Chart Share';
require_once __DIR__ . '/includes/header.php'; // header handles auth, CSRF, and user data
$db = Database::getInstance();

$csrf = $auth->generateCSRF();
// At this point, $auth, $currentUser, $csrf, $db are available from header

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'share_chart') {
    if (!$auth->verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid CSRF token.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $market_type = $_POST['market_type'] ?? 'crypto';
        $symbol = trim($_POST['symbol'] ?? '');
        $strategy = $_POST['strategy'] ?? 'other';
        $custom_strategy = trim($_POST['custom_strategy'] ?? '');
        $timeframe = trim($_POST['timeframe'] ?? '');
        $direction = $_POST['direction'] ?? 'neutral';
        $expires_at = $_POST['expires_at'] ?? null;
        $imagePath = null;
        $imageData = null;

        // Simple validation
        if (empty($title) || empty($symbol)) {
            setFlash('error', 'Title and Symbol are required.');
        } else {
            // Handle optional image upload
            if (!empty($_FILES['chart_image']['tmp_name'])) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($_FILES['chart_image']['type'], $allowed)) {
                    $uploadDir = __DIR__ . '/uploads/charts/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = pathinfo($_FILES['chart_image']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('chart_') . '.' . $ext;
                    $destPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['chart_image']['tmp_name'], $destPath)) {
                        $imagePath = '/trading/uploads/charts/' . $fileName;
                        $imageData = file_get_contents($destPath);
                    } else {
                        setFlash('warning', 'Image upload failed, proceeding without image.');
                    }
                } else {
                    setFlash('warning', 'Invalid image type, allowed: JPEG, PNG, WEBP.');
                }
            }
            // Insert into DB
            $db->insert("INSERT INTO chart_shares (user_id, title, description, market_type, symbol, strategy, strategy_custom, timeframe, direction, image_path, image_data, expires_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)", [
                $currentUser['id'], $title, $description, $market_type, $symbol, $strategy, $custom_strategy, $timeframe, $direction, $imagePath, $imageData,
                $expires_at ? date('Y-m-d H:i:s', strtotime($expires_at)) : null
            ]);
            setFlash('success', 'Chart shared successfully!');
        }
    }
    header('Location: /trading/chartshare.php');
    exit;
}

// Form processing completes here.
?>

<div class="d-flex justify-between align-center mb-4">
    <h2 class="mb-0">Share a Chart</h2>
    <a href="/trading/market_updates.php" class="btn btn-secondary"><i class="fas fa-eye"></i> View Market Updates</a>
</div>

<div class="card" style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <div style="background: var(--gray-50); padding: 1.5rem 2rem; border-bottom: 1px solid var(--gray-200);">
        <h3 style="margin: 0; color: var(--gray-800); font-weight: 600;"><i class="fas fa-chart-area text-primary"></i> Chart Details</h3>
        <p style="margin: 0.5rem 0 0 0; color: var(--gray-500); font-size: 0.9rem;">Provide details about your technical analysis to share with the community.</p>
    </div>
    
    <form method="POST" enctype="multipart/form-data" style="padding: 2rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
        <input type="hidden" name="action" value="share_chart">
        
        <div class="form-group mb-4">
            <label style="font-weight: 500; color: var(--gray-700);">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., BTC/USD Bull Flag Breakout Setup">
        </div>
        
        <div class="form-group mb-4">
            <label style="font-weight: 500; color: var(--gray-700);">Analysis Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Explain your bias, entry triggers, and key levels..."></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label style="font-weight: 500; color: var(--gray-700);">Market Type</label>
                <select name="market_type" class="form-control">
                    <option value="crypto" selected>Crypto</option>
                    <option value="forex">Forex</option>
                    <option value="stocks">Stocks</option>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight: 500; color: var(--gray-700);">Symbol <span class="text-danger">*</span></label>
                <input type="text" name="symbol" class="form-control" required placeholder="e.g., BTC/USD">
            </div>
            <div class="form-group">
                <label style="font-weight: 500; color: var(--gray-700);">Timeframe</label>
                <select name="timeframe" class="form-control">
                    <option value="1M">1 Minute</option>
                    <option value="5M">5 Minutes</option>
                    <option value="15M">15 Minutes</option>
                    <option value="1H">1 Hour</option>
                    <option value="4H" selected>4 Hours</option>
                    <option value="1D">Daily</option>
                    <option value="1W">Weekly</option>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight: 500; color: var(--gray-700);">Bias / Direction</label>
                <select name="direction" class="form-control">
                    <option value="neutral" selected>Neutral</option>
                    <option value="bullish">Bullish (Long)</option>
                    <option value="bearish">Bearish (Short)</option>
                </select>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius-sm); border: 1px dashed var(--gray-300);">
            <div class="form-group mb-0">
                <label style="font-weight: 500; color: var(--gray-700);">Trading Strategy</label>
                <select name="strategy" id="strategySelect" class="form-control" onchange="toggleCustomStrategy()">
                    <option value="other" selected>Other / General</option>
                    <option value="elliott_wave">Elliott Wave</option>
                    <option value="smc">Smart Money Concepts (SMC)</option>
                    <option value="order_flow">Order Flow</option>
                    <option value="neo_wave">Neo Wave</option>
                    <option value="mastering_elliott">Mastering Elliott</option>
                    <option value="harmonic">Harmonic Patterns</option>
                    <option value="retail_concepts">Retail Concepts</option>
                    <option value="support_resistance">Support & Resistance</option>
                    <option value="chart_patterns">Classic Chart Patterns</option>
                </select>
            </div>
            <div class="form-group mb-0" id="customStrategyGroup">
                <label style="font-weight: 500; color: var(--gray-700);">Specify Strategy</label>
                <input type="text" name="custom_strategy" class="form-control" placeholder="Type your strategy here...">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="form-group mb-0">
                <label style="font-weight: 500; color: var(--gray-700);">Upload Chart Image (Optional)</label>
                <div style="border: 1px solid var(--gray-300); padding: 0.5rem; border-radius: var(--radius-sm); background: #fff;">
                    <input type="file" name="chart_image" accept="image/*" class="form-control-file" style="border: none; padding: 0; width: 100%;">
                </div>
                <small class="text-muted mt-1 d-block">Supported formats: JPG, PNG, WEBP</small>
            </div>
            <div class="form-group mb-0">
                <label style="font-weight: 500; color: var(--gray-700);">Analysis Expires At (Optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control">
                <small class="text-muted mt-1 d-block">When does this setup become invalid?</small>
            </div>
        </div>

        <div style="border-top: 1px solid var(--gray-200); padding-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
            <button type="reset" class="btn btn-secondary">Clear Form</button>
            <button type="submit" class="btn btn-primary" style="padding-left: 2rem; padding-right: 2rem;"><i class="fas fa-paper-plane"></i> Publish Chart</button>
        </div>
    </form>
</div>

<script>
function toggleCustomStrategy() {
    const select = document.getElementById('strategySelect');
    const customGroup = document.getElementById('customStrategyGroup');
    if (select.value === 'other') {
        customGroup.style.display = 'block';
    } else {
        customGroup.style.display = 'none';
        customGroup.querySelector('input').value = '';
    }
}
// Run on load
document.addEventListener('DOMContentLoaded', toggleCustomStrategy);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
