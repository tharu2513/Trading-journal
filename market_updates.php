<?php
$pageTitle = 'Market Updates';
require_once __DIR__ . '/includes/header.php'; // header handles auth, CSRF, and user data
$db = Database::getInstance();

// Build filter query
$where = [];
$params = [];
if (!empty($_GET['market_type'])) {
    $where[] = 'market_type = ?';
    $params[] = $_GET['market_type'];
}
if (!empty($_GET['strategy'])) {
    $where[] = '(strategy = ? OR strategy_custom = ?)';
    $params[] = $_GET['strategy'];
    $params[] = $_GET['strategy'];
}
if (!empty($_GET['direction'])) {
    $where[] = 'direction = ?';
    $params[] = $_GET['direction'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'shared_at >= ?';
    $params[] = date('Y-m-d 00:00:00', strtotime($_GET['date_from']));
}
if (!empty($_GET['date_to'])) {
    $where[] = 'shared_at <= ?';
    $params[] = date('Y-m-d 23:59:59', strtotime($_GET['date_to']));
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$charts = $db->fetchAll("SELECT cs.*, u.username FROM chart_shares cs LEFT JOIN users u ON cs.user_id = u.id $whereClause ORDER BY cs.shared_at DESC", $params);
?>

<div class="d-flex justify-between align-center mb-4">
    <h2 class="mb-0">Market Updates & Shared Charts</h2>
    <a href="/trading/chartshare.php" class="btn btn-primary"><i class="fas fa-plus"></i> Share a Chart</a>
</div>

<form method="GET" class="mb-4 card p-3" style="background: var(--gray-50); border: 1px solid var(--gray-200);">
    <div class="form-row" style="display:flex; gap:1rem; flex-wrap:wrap;">
        <div class="form-group" style="flex:1; min-width:120px;">
            <label>Market</label>
            <select name="market_type" class="form-control">
                <option value="">All</option>
                <option value="crypto" <?= ($_GET['market_type']??'')==='crypto'?'selected':''; ?>>Crypto</option>
                <option value="forex" <?= ($_GET['market_type']??'')==='forex'?'selected':''; ?>>Forex</option>
                <option value="stocks" <?= ($_GET['market_type']??'')==='stocks'?'selected':''; ?>>Stocks</option>
            </select>
        </div>
        <div class="form-group" style="flex:1; min-width:120px;">
            <label>Strategy</label>
            <input type="text" name="strategy" class="form-control" value="<?= htmlspecialchars($_GET['strategy']??''); ?>" placeholder="e.g., Elliott Wave">
        </div>
        <div class="form-group" style="flex:1; min-width:120px;">
            <label>Direction</label>
            <select name="direction" class="form-control">
                <option value="">All</option>
                <option value="bullish" <?= ($_GET['direction']??'')==='bullish'?'selected':''; ?>>Bullish</option>
                <option value="bearish" <?= ($_GET['direction']??'')==='bearish'?'selected':''; ?>>Bearish</option>
                <option value="neutral" <?= ($_GET['direction']??'')==='neutral'?'selected':''; ?>>Neutral</option>
            </select>
        </div>
        <div class="form-group" style="flex:1; min-width:120px;">
            <label>From Date</label>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($_GET['date_from']??''); ?>">
        </div>
        <div class="form-group" style="flex:1; min-width:120px;">
            <label>To Date</label>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($_GET['date_to']??''); ?>">
        </div>
        <div class="form-group" style="flex:1; min-width:120px; align-self:flex-end;">
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-filter"></i> Filter</button>
        </div>
    </div>
</form>

<?php if (empty($charts)): ?>
    <div class="card p-5 text-center">
        <div class="empty-state">
            <i class="fas fa-chart-area" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
            <h3>No charts found.</h3>
            <p class="text-muted">Adjust your filters or be the first to <a href="/trading/chartshare.php">share a chart</a>.</p>
        </div>
    </div>
<?php else: ?>
    <div class="grid" style="display:grid; gap:1.5rem; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));">
        <?php foreach ($charts as $c): ?>
            <div class="card" style="background: var(--white); border:1px solid var(--gray-200); border-radius: var(--radius-md); padding:1.5rem; box-shadow: var(--shadow-sm); display: flex; flex-direction: column;">
                <div class="d-flex justify-between align-center mb-2">
                    <h4 style="margin-bottom:0; color: var(--gray-800); font-weight: 600; font-size: 1.1rem;">
                        <?= htmlspecialchars($c['title']); ?>
                    </h4>
                    <span class="badge badge-<?= $c['direction'] === 'bullish' ? 'success' : ($c['direction'] === 'bearish' ? 'danger' : 'secondary') ?>" style="font-size:0.75rem;">
                        <?= ucfirst($c['direction']); ?>
                    </span>
                </div>
                
                <div class="mb-3 d-flex gap-2 flex-wrap" style="font-size: 0.8rem;">
                    <span class="badge" style="background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200);">
                        <i class="fas fa-chart-line"></i> <?= htmlspecialchars($c['symbol']); ?>
                    </span>
                    <span class="badge" style="background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200);">
                        <i class="fas fa-layer-group"></i> <?= ucfirst(htmlspecialchars($c['market_type'])); ?>
                    </span>
                    <span class="badge" style="background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200);">
                        <i class="fas fa-chess-knight"></i> <?= htmlspecialchars($c['strategy'] === 'other' ? $c['strategy_custom'] : str_replace('_', ' ', $c['strategy'])); ?>
                    </span>
                    <?php if (!empty($c['timeframe'])): ?>
                    <span class="badge" style="background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200);">
                        <i class="fas fa-clock"></i> <?= htmlspecialchars($c['timeframe']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <p style="font-size:0.9rem; color: var(--gray-600); margin-bottom:1rem; flex-grow: 1;">
                    <?= nl2br(htmlspecialchars($c['description'])); ?>
                </p>
                
                <?php if (!empty($c['image_path'])): ?>
                    <div style="margin-bottom: 1rem; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--gray-200);">
                        <a href="<?= htmlspecialchars($c['image_path']); ?>" target="_blank">
                            <img src="<?= htmlspecialchars($c['image_path']); ?>" alt="Chart" style="width:100%; height: auto; display: block; object-fit: cover;">
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="mt-auto pt-3" style="border-top: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center;">
                    <div class="user-info d-flex align-center gap-2">
                        <div class="user-avatar-small" style="width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <small style="color: var(--gray-700); font-weight: 500;"><?= htmlspecialchars($c['username']); ?></small>
                    </div>
                    <small style="color: var(--gray-500);"><i class="far fa-clock"></i> <?= timeAgo($c['shared_at']); ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
