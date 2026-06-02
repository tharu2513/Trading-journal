<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$userId = $auth->getUserId();

$stats = getTradeStats($userId);
$winRate = getWinRate($userId);
$todayPnL = getTotalPnL($userId, 'today');
$weekPnL = getTotalPnL($userId, 'week');
$monthPnL = getTotalPnL($userId, 'month');
$totalPnL = getTotalPnL($userId, 'all');
$pnlChart = getPnLChartData($userId, 'monthly', 12);
$coinDist = getCoinDistribution($userId);
$recentTrades = Database::getInstance()->fetchAll(
    "SELECT * FROM trades WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$userId], 'i'
);
$balHistory = getBalanceHistory($userId, 10);
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <h4>Account Balance</h4>
            <div class="stat-value"><?php echo formatMoney($currentUser['balance']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?php echo $totalPnL >= 0 ? 'green' : 'red'; ?>">
            <i class="fas fa-<?php echo $totalPnL >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
        </div>
        <div class="stat-info">
            <h4>Total PnL</h4>
            <div class="stat-value"><?php echo formatMoney($totalPnL); ?></div>
            <div class="stat-change <?php echo $todayPnL >= 0 ? 'positive' : 'negative'; ?>">Today: <?php echo formatMoney($todayPnL); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-bullseye"></i></div>
        <div class="stat-info">
            <h4>Win Rate</h4>
            <div class="stat-value"><?php echo $winRate['win_rate']; ?>%</div>
            <div class="stat-change positive"><?php echo $winRate['wins']; ?>W / <?php echo $winRate['losses']; ?>L</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-exchange-alt"></i></div>
        <div class="stat-info">
            <h4>Total Trades</h4>
            <div class="stat-value"><?php echo $stats['total_trades']; ?></div>
            <div class="stat-change"><?php echo $stats['open_trades']; ?> open</div>
        </div>
    </div>
</div>

<!-- PnL Summary Row -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="stat-card">
        <div class="stat-info">
            <h4>Weekly PnL</h4>
            <div class="stat-value <?php echo $weekPnL >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo formatMoney($weekPnL); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4>Monthly PnL</h4>
            <div class="stat-value <?php echo $monthPnL >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo formatMoney($monthPnL); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4>Best Trade</h4>
            <div class="stat-value text-success"><?php echo formatMoney($stats['best_trade']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4>Worst Trade</h4>
            <div class="stat-value text-danger"><?php echo formatMoney($stats['worst_trade']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4>Avg Win</h4>
            <div class="stat-value text-success"><?php echo formatMoney($stats['avg_win']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4>Avg Loss</h4>
            <div class="stat-value text-danger"><?php echo formatMoney($stats['avg_loss']); ?></div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2 mb-3">
    <!-- PnL Bar Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar text-primary"></i> PnL Overview</h3>
            <div class="chart-controls">
                <button class="chart-filter-btn active" onclick="updatePnLChart('monthly')">Monthly</button>
                <button class="chart-filter-btn" onclick="updatePnLChart('weekly')">Weekly</button>
                <button class="chart-filter-btn" onclick="updatePnLChart('daily')">Daily</button>
            </div>
        </div>
        <div class="card-body"><canvas id="pnlBarChart" height="280"></canvas></div>
    </div>

    <!-- Win Rate & Coin Pie -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie text-primary"></i> Win Rate & Distribution</h3>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="text-center">
                    <div class="win-rate-circle" style="background:conic-gradient(var(--success) <?php echo $winRate['win_rate']*3.6; ?>deg, var(--danger) <?php echo $winRate['win_rate']*3.6; ?>deg);">
                        <div class="win-rate-inner"><?php echo $winRate['win_rate']; ?>%</div>
                    </div>
                    <p class="text-muted mt-1">Win Rate</p>
                    <p><span class="text-success fw-bold"><?php echo $winRate['wins']; ?> Wins</span> / <span class="text-danger fw-bold"><?php echo $winRate['losses']; ?> Losses</span></p>
                </div>
                <div><canvas id="coinPieChart" height="200"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Balance Management -->
<div class="card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-wallet text-primary"></i> Manage Balance</h3>
        <button class="btn btn-primary btn-sm" onclick="openModal('balanceModal')"><i class="fas fa-plus"></i> Update Balance</button>
    </div>
    <div class="card-body">
        <?php if (empty($balHistory)): ?>
        <div class="empty-state"><i class="fas fa-piggy-bank"></i><h3>No balance history</h3><p>Add your account balance to start tracking.</p></div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($balHistory as $bh): ?>
                <tr>
                    <td><?php echo date('M d, Y H:i', strtotime($bh['created_at'])); ?></td>
                    <td><span class="badge badge-<?php echo $bh['type']==='deposit'?'success':($bh['type']==='withdrawal'?'danger':'info'); ?>"><?php echo ucfirst($bh['type']); ?></span></td>
                    <td class="<?php echo $bh['amount']>=0?'text-success':'text-danger'; ?> fw-bold"><?php echo formatMoney($bh['amount']); ?></td>
                    <td class="fw-bold"><?php echo formatMoney($bh['new_balance']); ?></td>
                    <td><?php echo sanitize($bh['description'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Trades -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history text-primary"></i> Recent Trades</h3>
        <a href="/trading/trades.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recentTrades)): ?>
        <div class="empty-state"><i class="fas fa-exchange-alt"></i><h3>No trades yet</h3><p>Start adding your trades to track performance.</p><a href="/trading/trades.php" class="btn btn-primary">Add First Trade</a></div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead><tr><th>Coin</th><th>Exchange</th><th>Type</th><th>Entry</th><th>Exit</th><th>SL</th><th>Size</th><th>PnL</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recentTrades as $t): ?>
                <tr>
                    <td><strong><?php echo sanitize($t['coin_symbol']); ?></strong><br><small class="text-muted"><?php echo sanitize($t['coin_name']); ?></small></td>
                    <td><?php echo sanitize($t['exchange_name']); ?></td>
                    <td><span class="badge badge-<?php echo $t['trade_type']==='long'?'success':'danger'; ?>"><?php echo strtoupper($t['trade_type']); ?></span></td>
                    <td><?php echo formatCryptoPrice($t['entry_price']); ?></td>
                    <td><?php echo $t['exit_price'] ? formatCryptoPrice($t['exit_price']) : '-'; ?></td>
                    <td><?php echo $t['stop_loss'] ? formatCryptoPrice($t['stop_loss']) : '-'; ?></td>
                    <td><?php echo formatMoney($t['position_size']); ?></td>
                    <td class="fw-bold <?php echo ($t['pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo $t['pnl'] !== null ? formatMoney($t['pnl']) : '-'; ?></td>
                    <td><span class="badge badge-<?php echo $t['status']==='open'?'warning':($t['status']==='closed'?'success':'neutral'); ?>"><?php echo ucfirst($t['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Balance Modal -->
<div class="modal-overlay" id="balanceModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Update Account Balance</h3>
            <button class="modal-close" onclick="closeModal('balanceModal')">&times;</button>
        </div>
        <form method="POST" action="/trading/api/balance.php">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>Transaction Type</label>
                    <select name="type" class="form-control" required>
                        <option value="deposit">Deposit</option>
                        <option value="withdrawal">Withdrawal</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($)</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="Enter amount" required>
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g., Initial deposit">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('balanceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// PnL Bar Chart
const pnlData = <?php echo json_encode(array_reverse($pnlChart)); ?>;
const pnlCtx = document.getElementById('pnlBarChart').getContext('2d');
let pnlBarChart = new Chart(pnlCtx, {
    type: 'bar',
    data: {
        labels: pnlData.map(d => d.label),
        datasets: [
            { label: 'Gains', data: pnlData.map(d => parseFloat(d.gains)), backgroundColor: '#10B981', borderRadius: 6 },
            { label: 'Losses', data: pnlData.map(d => -parseFloat(d.losses)), backgroundColor: '#EF4444', borderRadius: 6 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '$' + v } },
            x: { grid: { display: false } }
        }
    }
});

function updatePnLChart(period) {
    document.querySelectorAll('.chart-filter-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    fetch('/trading/api/analytics.php?action=pnl_chart&period=' + period)
        .then(r => r.json()).then(data => {
            pnlBarChart.data.labels = data.map(d => d.label);
            pnlBarChart.data.datasets[0].data = data.map(d => parseFloat(d.gains));
            pnlBarChart.data.datasets[1].data = data.map(d => -parseFloat(d.losses));
            pnlBarChart.update();
        });
}

// Coin Pie Chart
const coinData = <?php echo json_encode($coinDist); ?>;
if (coinData.length > 0) {
    new Chart(document.getElementById('coinPieChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: coinData.map(d => d.coin_symbol),
            datasets: [{
                data: coinData.map(d => d.trade_count),
                backgroundColor: ['#2563EB','#3B82F6','#60A5FA','#93C5FD','#BFDBFE','#1E40AF','#1D4ED8','#2563EB','#3B82F6','#60A5FA']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
