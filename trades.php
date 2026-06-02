<?php
$pageTitle = 'Trade Journal';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$userId = $auth->getUserId();
$db = Database::getInstance();

$filterStatus = $_GET['status'] ?? 'all';
$filterCoin = $_GET['coin'] ?? '';
$filterExchange = $_GET['exchange'] ?? '';
$filterMarket = $_GET['market'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20; $offset = ($page - 1) * $perPage;

$where = "user_id = ?"; $params = [$userId]; $types = 'i';
if ($filterStatus !== 'all') { $where .= " AND status = ?"; $params[] = $filterStatus; $types .= 's'; }
if ($filterCoin) { $where .= " AND coin_symbol = ?"; $params[] = $filterCoin; $types .= 's'; }
if ($filterExchange) { $where .= " AND exchange_name = ?"; $params[] = $filterExchange; $types .= 's'; }
if ($filterMarket) { $where .= " AND market_type = ?"; $params[] = $filterMarket; $types .= 's'; }

$totalRow = $db->fetchOne("SELECT COUNT(*) as cnt FROM trades WHERE $where", $params, $types);
$total = $totalRow['cnt']; $totalPages = ceil($total / $perPage);
$params2 = $params; $params2[] = $perPage; $params2[] = $offset; $types2 = $types . 'ii';
$trades = $db->fetchAll("SELECT * FROM trades WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?", $params2, $types2);

$userCoins = $db->fetchAll("SELECT DISTINCT coin_symbol FROM trades WHERE user_id=? ORDER BY coin_symbol", [$userId], 'i');
$userExchanges = $db->fetchAll("SELECT DISTINCT exchange_name FROM trades WHERE user_id=? ORDER BY exchange_name", [$userId], 'i');
$coins = getTop100Coins();
$exchanges = getExchanges();
$forexBrokers = getForexBrokers();
$stockBrokers = getStockBrokers();
$forexPairs = getForexPairs();
$stockSymbols = getStockSymbols();
?>

<div class="d-flex justify-between align-center mb-3" style="flex-wrap:wrap;gap:10px;">
    <div class="d-flex gap-1">
        <button class="btn btn-primary" onclick="openModal('addTradeModal')"><i class="fas fa-plus"></i> Crypto Trade</button>
        <button class="btn btn-success" onclick="openModal('addForexModal')"><i class="fas fa-plus"></i> Forex Trade</button>
        <button class="btn btn-outline" onclick="openModal('addStockModal')"><i class="fas fa-plus"></i> Stock Trade</button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3"><div class="card-body">
    <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
        <select name="market" class="form-control" style="width:auto;"><option value="">All Markets</option><option value="crypto" <?php echo $filterMarket==='crypto'?'selected':''; ?>>Crypto</option><option value="forex" <?php echo $filterMarket==='forex'?'selected':''; ?>>Forex</option><option value="stocks" <?php echo $filterMarket==='stocks'?'selected':''; ?>>Stocks</option></select>
        <select name="status" class="form-control" style="width:auto;"><option value="all">All Status</option><option value="open" <?php echo $filterStatus==='open'?'selected':''; ?>>Open</option><option value="closed" <?php echo $filterStatus==='closed'?'selected':''; ?>>Closed</option></select>
        <select name="coin" class="form-control" style="width:auto;"><option value="">All Symbols</option>
            <?php foreach ($userCoins as $c): ?><option value="<?php echo $c['coin_symbol']; ?>" <?php echo $filterCoin===$c['coin_symbol']?'selected':''; ?>><?php echo $c['coin_symbol']; ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="/trading/trades.php" class="btn btn-secondary btn-sm">Reset</a>
    </form>
</div></div>

<!-- Trades Table -->
<div class="card"><div class="card-body">
    <?php if (empty($trades)): ?>
    <div class="empty-state"><i class="fas fa-exchange-alt"></i><h3>No trades found</h3><p>Add your first trade to start tracking.</p></div>
    <?php else: ?>
    <div class="table-container"><table>
        <thead><tr><th>Date</th><th>Mkt</th><th>Symbol</th><th>Exchange</th><th>Type</th><th>Entry</th><th>Exit</th><th>SL</th><th>Size</th><th>Lev</th><th>Pips</th><th>PnL</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($trades as $t): $mt = $t['market_type'] ?? 'crypto'; ?>
        <tr>
            <td><?php echo date('M d', strtotime($t['trade_date'])); ?></td>
            <td><span class="badge badge-<?php echo $mt==='crypto'?'primary':($mt==='forex'?'success':'info'); ?>" style="font-size:0.65rem;"><?php echo strtoupper($mt); ?></span></td>
            <td><strong><?php echo sanitize($t['coin_symbol']); ?></strong></td>
            <td style="font-size:0.8rem;"><?php echo sanitize($t['exchange_name']); ?></td>
            <td><span class="badge badge-<?php echo $t['trade_type']==='long'?'success':'danger'; ?>"><?php echo strtoupper($t['trade_type']); ?></span></td>
            <td><?php echo formatPrice($t['entry_price'], $mt); ?></td>
            <td><?php echo $t['exit_price'] ? formatPrice($t['exit_price'], $mt) : '-'; ?></td>
            <td><?php echo $t['stop_loss'] ? formatPrice($t['stop_loss'], $mt) : '-'; ?></td>
            <td><?php echo $mt==='forex' && $t['lot_size'] ? $t['lot_size'].' lot' : formatMoney($t['position_size']); ?></td>
            <td><?php echo $t['leverage']; ?>x</td>
            <td class="fw-bold <?php echo ($t['pip_gain']??0)>=0?'text-success':'text-danger'; ?>"><?php echo $t['pip_gain']!==null ? $t['pip_gain'].' pips' : '-'; ?></td>
            <td class="fw-bold <?php echo ($t['pnl']??0)>=0?'text-success':'text-danger'; ?>"><?php echo $t['pnl']!==null?formatMoney($t['pnl']):'-'; ?></td>
            <td><span class="badge badge-<?php echo $t['status']==='open'?'warning':($t['status']==='closed'?'success':'neutral'); ?>"><?php echo ucfirst($t['status']); ?></span></td>
            <td>
                <button class="btn btn-sm btn-secondary btn-icon" onclick="editTrade(<?php echo $t['id']; ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                <?php if ($t['status']==='open'): ?>
                <button class="btn btn-sm btn-success btn-icon" onclick="openCloseModal(<?php echo $t['id']; ?>,'<?php echo sanitize($t['coin_symbol']); ?>','<?php echo $mt; ?>')" title="Close"><i class="fas fa-check"></i></button>
                <?php endif; ?>
                <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDelete('Delete?','/trading/api/trades.php?action=delete&id=<?php echo $t['id']; ?>')" title="Delete"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php if ($totalPages > 1): ?><div class="pagination mt-2">
        <?php for ($i=1;$i<=$totalPages;$i++): ?><a href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?>&coin=<?php echo $filterCoin; ?>&market=<?php echo $filterMarket; ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?>
    </div><?php endif; ?>
    <?php endif; ?>
</div></div>

<!-- Add Crypto Trade Modal -->
<div class="modal-overlay" id="addTradeModal"><div class="modal" style="max-width:750px;"><div class="modal-header"><h3><i class="fas fa-bitcoin text-primary"></i> Add Crypto Trade</h3><button class="modal-close" onclick="closeModal('addTradeModal')">&times;</button></div>
<form method="POST" action="/trading/api/trades.php"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="market_type" value="crypto">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label>Coin</label><select name="coin" class="form-control" required><option value="">Select...</option><?php foreach ($coins as $c): ?><option value="<?php echo $c['id'].'|'.$c['name'].'|'.$c['symbol']; ?>"><?php echo $c['symbol'].' - '.$c['name']; ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Exchange</label><select name="exchange_name" class="form-control" required><?php foreach ($exchanges as $ex): ?><option><?php echo $ex; ?></option><?php endforeach; ?></select></div></div>
    <div class="form-row"><div class="form-group"><label>Type</label><select name="trade_type" class="form-control"><option value="long">Long</option><option value="short">Short</option></select></div>
    <div class="form-group"><label>Date</label><input type="datetime-local" name="trade_date" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div></div>
    <div class="form-row"><div class="form-group"><label>Entry Price</label><input type="number" name="entry_price" class="form-control" step="0.00000001" required></div>
    <div class="form-group"><label>Stop Loss</label><input type="number" name="stop_loss" class="form-control" step="0.00000001"></div>
    <div class="form-group"><label>Take Profit</label><input type="number" name="take_profit" class="form-control" step="0.00000001"></div></div>
    <div class="form-row"><div class="form-group"><label>Position Size ($)</label><input type="number" name="position_size" class="form-control" step="0.01" required></div>
    <div class="form-group"><label>Leverage</label><input type="number" name="leverage" class="form-control" min="1" value="1"></div>
    <div class="form-group"><label>Fees ($)</label><input type="number" name="fees" class="form-control" step="0.01" value="0"></div></div>
    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addTradeModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Trade</button></div>
</form></div></div>

<!-- Add Forex Trade Modal -->
<div class="modal-overlay" id="addForexModal"><div class="modal" style="max-width:750px;"><div class="modal-header"><h3><i class="fas fa-dollar-sign text-success"></i> Add Forex Trade</h3><button class="modal-close" onclick="closeModal('addForexModal')">&times;</button></div>
<form method="POST" action="/trading/api/trades.php"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="market_type" value="forex">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label>Currency Pair</label><select name="coin" class="form-control" required><option value="">Select pair...</option>
        <?php foreach ($forexPairs as $fp): ?><option value="forex|<?php echo $fp['pair']; ?>|<?php echo $fp['pair']; ?>" data-pip="<?php echo $fp['pip_decimal']; ?>"><?php echo $fp['pair'].' ('.$fp['category'].')'; ?></option><?php endforeach; ?>
    </select></div>
    <div class="form-group"><label>Broker</label><select name="exchange_name" class="form-control" required><?php foreach ($forexBrokers as $b): ?><option><?php echo $b; ?></option><?php endforeach; ?></select></div></div>
    <div class="form-row"><div class="form-group"><label>Type</label><select name="trade_type" class="form-control"><option value="long">Buy (Long)</option><option value="short">Sell (Short)</option></select></div>
    <div class="form-group"><label>Date</label><input type="datetime-local" name="trade_date" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div></div>
    <div class="form-row"><div class="form-group"><label>Entry Price</label><input type="number" name="entry_price" class="form-control" step="0.00001" required></div>
    <div class="form-group"><label>Stop Loss</label><input type="number" name="stop_loss" class="form-control" step="0.00001"></div>
    <div class="form-group"><label>Take Profit</label><input type="number" name="take_profit" class="form-control" step="0.00001"></div></div>
    <div class="form-row"><div class="form-group"><label>Lot Size</label><input type="number" name="lot_size" class="form-control" step="0.01" value="0.01" min="0.01"></div>
    <div class="form-group"><label>Position Size ($)</label><input type="number" name="position_size" class="form-control" step="0.01" value="0"></div>
    <div class="form-group"><label>Leverage</label><input type="number" name="leverage" class="form-control" min="1" value="100"></div></div>
    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addForexModal')">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Add Forex Trade</button></div>
</form></div></div>

<!-- Add Stock Trade Modal -->
<div class="modal-overlay" id="addStockModal"><div class="modal" style="max-width:750px;"><div class="modal-header"><h3><i class="fas fa-chart-line text-info"></i> Add Stock Trade</h3><button class="modal-close" onclick="closeModal('addStockModal')">&times;</button></div>
<form method="POST" action="/trading/api/trades.php"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="market_type" value="stocks">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label>Stock Symbol</label><select name="coin" class="form-control" required><option value="">Select...</option>
        <?php foreach ($stockSymbols as $ss): ?><option value="stock|<?php echo $ss['name']; ?>|<?php echo $ss['symbol']; ?>"><?php echo $ss['symbol'].' - '.$ss['name'].' ('.$ss['exchange'].')'; ?></option><?php endforeach; ?>
    </select></div>
    <div class="form-group"><label>Broker</label><select name="exchange_name" class="form-control" required><?php foreach ($stockBrokers as $b): ?><option><?php echo $b; ?></option><?php endforeach; ?></select></div></div>
    <div class="form-row"><div class="form-group"><label>Type</label><select name="trade_type" class="form-control"><option value="long">Buy (Long)</option><option value="short">Sell (Short)</option></select></div>
    <div class="form-group"><label>Date</label><input type="datetime-local" name="trade_date" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div></div>
    <div class="form-row"><div class="form-group"><label>Entry Price ($)</label><input type="number" name="entry_price" class="form-control" step="0.01" required></div>
    <div class="form-group"><label>Stop Loss ($)</label><input type="number" name="stop_loss" class="form-control" step="0.01"></div>
    <div class="form-group"><label>Take Profit ($)</label><input type="number" name="take_profit" class="form-control" step="0.01"></div></div>
    <div class="form-row"><div class="form-group"><label>Shares (Qty)</label><input type="number" name="position_size" class="form-control" step="1" min="1" required></div>
    <div class="form-group"><label>Fees ($)</label><input type="number" name="fees" class="form-control" step="0.01" value="0"></div></div>
    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addStockModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Stock Trade</button></div>
</form></div></div>

<!-- Close Trade Modal -->
<div class="modal-overlay" id="closeTradeModal"><div class="modal"><div class="modal-header"><h3><i class="fas fa-check-circle text-success"></i> Close Trade - <span id="closeCoinName"></span></h3><button class="modal-close" onclick="closeModal('closeTradeModal')">&times;</button></div>
<form method="POST" action="/trading/api/trades.php"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="close"><input type="hidden" name="trade_id" id="closeTradeId"><input type="hidden" name="close_market_type" id="closeMarketType">
<div class="modal-body">
    <div class="form-group"><label>Exit Price</label><input type="number" name="exit_price" class="form-control" step="0.00000001" required></div>
    <div class="form-group"><label>Fees ($)</label><input type="number" name="fees" class="form-control" step="0.01" value="0"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('closeTradeModal')">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Close Trade</button></div>
</form></div></div>

<!-- Edit Trade Modal -->
<div class="modal-overlay" id="editTradeModal"><div class="modal" style="max-width:750px;"><div class="modal-header"><h3><i class="fas fa-edit text-primary"></i> Edit Trade</h3><button class="modal-close" onclick="closeModal('editTradeModal')">&times;</button></div>
<form method="POST" action="/trading/api/trades.php"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="trade_id" id="editTradeId">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label>Entry Price</label><input type="number" name="entry_price" id="editEntry" class="form-control" step="0.00000001"></div>
    <div class="form-group"><label>Exit Price</label><input type="number" name="exit_price" id="editExit" class="form-control" step="0.00000001"></div></div>
    <div class="form-row"><div class="form-group"><label>Stop Loss</label><input type="number" name="stop_loss" id="editSL" class="form-control" step="0.00000001"></div>
    <div class="form-group"><label>Take Profit</label><input type="number" name="take_profit" id="editTP" class="form-control" step="0.00000001"></div></div>
    <div class="form-row"><div class="form-group"><label>Position Size</label><input type="number" name="position_size" id="editSize" class="form-control" step="0.01"></div>
    <div class="form-group"><label>Leverage</label><input type="number" name="leverage" id="editLev" class="form-control" min="1"></div></div>
    <div class="form-group"><label>Notes</label><textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editTradeModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button></div>
</form></div></div>

<script>
function openCloseModal(id,coin,market){document.getElementById('closeTradeId').value=id;document.getElementById('closeCoinName').textContent=coin;document.getElementById('closeMarketType').value=market||'crypto';openModal('closeTradeModal');}
function editTrade(id){fetch('/trading/api/trades.php?action=get&id='+id).then(r=>r.json()).then(t=>{document.getElementById('editTradeId').value=t.id;document.getElementById('editEntry').value=t.entry_price;document.getElementById('editExit').value=t.exit_price||'';document.getElementById('editSL').value=t.stop_loss||'';document.getElementById('editTP').value=t.take_profit||'';document.getElementById('editSize').value=t.position_size;document.getElementById('editLev').value=t.leverage;document.getElementById('editNotes').value=t.notes||'';openModal('editTradeModal');});}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
