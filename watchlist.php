<?php
$pageTitle = 'Top 100 Watchlist';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$userId = $auth->getUserId();
$db = Database::getInstance();

$myWatchlist = $db->fetchAll("SELECT * FROM watchlist WHERE user_id=? ORDER BY added_at DESC", [$userId], 'i');
$coins = getTop100Coins();
$watchlistIds = array_column($myWatchlist, 'coin_id');
?>

<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted">Track top 100 cryptocurrencies with live prices and news.</p>
    <button class="btn btn-primary" onclick="openModal('addWatchlistModal')"><i class="fas fa-plus"></i> Add to Watchlist</button>
</div>

<?php if (!empty($myWatchlist)): ?>
<div class="card mb-3">
    <div class="card-header"><h3><i class="fas fa-star text-warning"></i> My Watchlist</h3></div>
    <div class="card-body">
        <div class="table-container"><table>
            <thead><tr><th>Coin</th><th>Symbol</th><th>Added</th><th>News</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($myWatchlist as $w): ?>
            <tr>
                <td><strong><?php echo sanitize($w['coin_name']); ?></strong></td>
                <td><span class="badge badge-primary"><?php echo sanitize($w['coin_symbol']); ?></span></td>
                <td class="text-muted"><?php echo timeAgo($w['added_at']); ?></td>
                <td><button class="btn btn-sm btn-outline" onclick="viewCoinNews('<?php echo sanitize($w['coin_symbol']); ?>','<?php echo sanitize($w['coin_name']); ?>')"><i class="fas fa-newspaper"></i> View News</button></td>
                <td><button class="btn btn-sm btn-danger btn-icon" onclick="confirmDelete('Remove from watchlist?','/trading/api/watchlist.php?action=remove&id=<?php echo $w['id']; ?>')"><i class="fas fa-trash"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-coins text-primary"></i> Top 100 Cryptocurrencies</h3>
        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="coinSearch" class="form-control" placeholder="Search coins..." onkeyup="filterCoins()"></div>
    </div>
    <div class="card-body">
        <div id="coinLoading" class="text-center"><div class="loading-spinner"></div><p class="text-muted mt-1">Loading live prices...</p></div>
        <div class="table-container" id="coinTable" style="display:none;"><table>
            <thead><tr><th>#</th><th>Coin</th><th>Price</th><th>24h</th><th>7d</th><th>Market Cap</th><th>Volume</th><th>News</th><th>Watch</th></tr></thead>
            <tbody id="coinBody"></tbody>
        </table></div>
        <div id="coinFallback" style="display:none;">
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> Could not load live prices. Showing static list.</div>
            <div class="table-container"><table>
                <thead><tr><th>#</th><th>Coin</th><th>Symbol</th><th>News</th><th>Watch</th></tr></thead>
                <tbody>
                <?php foreach ($coins as $i => $c): ?>
                <tr><td><?php echo $i+1; ?></td><td><strong><?php echo $c['name']; ?></strong></td><td><span class="badge badge-primary"><?php echo $c['symbol']; ?></span></td>
                <td><button class="btn btn-sm btn-outline" onclick="viewCoinNews('<?php echo $c['symbol']; ?>','<?php echo $c['name']; ?>')"><i class="fas fa-newspaper"></i></button></td>
                <td><?php if (in_array($c['id'], $watchlistIds)): ?><span class="badge badge-success"><i class="fas fa-check"></i></span>
                <?php else: ?><form method="POST" action="/trading/api/watchlist.php" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="coin_id" value="<?php echo $c['id']; ?>"><input type="hidden" name="coin_name" value="<?php echo $c['name']; ?>"><input type="hidden" name="coin_symbol" value="<?php echo $c['symbol']; ?>"><button type="submit" class="btn btn-sm btn-primary btn-icon"><i class="fas fa-plus"></i></button></form><?php endif; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="coinNewsModal"><div class="modal" style="max-width:700px;"><div class="modal-header"><h3><i class="fas fa-newspaper text-primary"></i> <span id="newsModalTitle">Coin News</span></h3><button class="modal-close" onclick="closeModal('coinNewsModal')">&times;</button></div><div class="modal-body" id="newsModalBody"><div class="loading-spinner"></div></div></div></div>

<div class="modal-overlay" id="addWatchlistModal"><div class="modal"><div class="modal-header"><h3>Add Coin to Watchlist</h3><button class="modal-close" onclick="closeModal('addWatchlistModal')">&times;</button></div>
<form method="POST" action="/trading/api/watchlist.php"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="add">
<div class="modal-body"><div class="form-group"><label>Select Coin</label><select name="coin_id" class="form-control" required id="wlSelect" onchange="updateWlFields()"><option value="">Choose...</option><?php foreach ($coins as $c): ?><option value="<?php echo $c['id']; ?>" data-name="<?php echo $c['name']; ?>" data-symbol="<?php echo $c['symbol']; ?>"><?php echo $c['symbol'].' - '.$c['name']; ?></option><?php endforeach; ?></select></div><input type="hidden" name="coin_name" id="wlName"><input type="hidden" name="coin_symbol" id="wlSymbol"></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addWatchlistModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button></div></form></div></div>

<script>
const wlIds = <?php echo json_encode($watchlistIds); ?>;
function updateWlFields(){const s=document.getElementById('wlSelect'),o=s.options[s.selectedIndex];document.getElementById('wlName').value=o.dataset.name||'';document.getElementById('wlSymbol').value=o.dataset.symbol||'';}

fetch('https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=100&page=1&sparkline=false&price_change_percentage=7d')
.then(r=>r.json()).then(data=>{
    document.getElementById('coinLoading').style.display='none';
    document.getElementById('coinTable').style.display='block';
    const tb=document.getElementById('coinBody');
    data.forEach((c,i)=>{
        const ch24=c.price_change_percentage_24h||0, ch7=c.price_change_percentage_7d_in_currency||0, inWl=wlIds.includes(c.id);
        tb.innerHTML+=`<tr class="crd" data-name="${c.name.toLowerCase()}" data-sym="${c.symbol}">
            <td>${i+1}</td><td><div class="coin-row"><img src="${c.image}" class="coin-icon"><div class="coin-info"><span class="coin-name">${c.name}</span><span class="coin-symbol">${c.symbol.toUpperCase()}</span></div></div></td>
            <td class="fw-bold">$${parseFloat(c.current_price).toLocaleString('en-US',{maximumFractionDigits:c.current_price<1?6:2})}</td>
            <td class="fw-bold ${ch24>=0?'text-success':'text-danger'}">${ch24>=0?'+':''}${ch24.toFixed(2)}%</td>
            <td class="fw-bold ${ch7>=0?'text-success':'text-danger'}">${ch7>=0?'+':''}${ch7.toFixed(2)}%</td>
            <td>$${(c.market_cap/1e9).toFixed(2)}B</td><td>$${(c.total_volume/1e6).toFixed(0)}M</td>
            <td><button class="btn btn-sm btn-outline" onclick="viewCoinNews('${c.symbol.toUpperCase()}','${c.name}')"><i class="fas fa-newspaper"></i></button></td>
            <td>${inWl?'<span class="badge badge-success"><i class="fas fa-check"></i></span>':`<form method="POST" action="/trading/api/watchlist.php" style="display:inline"><input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRF(); ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="coin_id" value="${c.id}"><input type="hidden" name="coin_name" value="${c.name}"><input type="hidden" name="coin_symbol" value="${c.symbol.toUpperCase()}"><button type="submit" class="btn btn-sm btn-primary btn-icon"><i class="fas fa-plus"></i></button></form>`}</td></tr>`;
    });
}).catch(()=>{document.getElementById('coinLoading').style.display='none';document.getElementById('coinFallback').style.display='block';});

function filterCoins(){const q=document.getElementById('coinSearch').value.toLowerCase();document.querySelectorAll('.crd').forEach(r=>{r.style.display=(r.dataset.name.includes(q)||r.dataset.sym.includes(q))?'':'none';});}

function viewCoinNews(sym,name){
    document.getElementById('newsModalTitle').textContent=name+' ('+sym+') News';
    document.getElementById('newsModalBody').innerHTML='<div class="text-center"><div class="loading-spinner"></div></div>';
    openModal('coinNewsModal');
    fetch('/trading/api/news.php?action=by_coin&symbol='+sym).then(r=>r.json()).then(news=>{
        if(!news.length){document.getElementById('newsModalBody').innerHTML='<div class="empty-state"><i class="fas fa-newspaper"></i><h3>No news yet</h3><p>No fundamental news for '+sym+'.</p></div>';return;}
        let h='';news.forEach(n=>{h+=`<div class="news-card"><div class="news-header"><span class="news-title">${n.title}</span><span class="badge badge-${n.impact==='bullish'?'success':(n.impact==='bearish'?'danger':'warning')}">${n.impact.toUpperCase()}</span></div><div class="news-body">${n.description||''}</div><div class="news-meta"><span><i class="fas fa-tag"></i> ${(n.category||'other').replace('_',' ').toUpperCase()}</span><span><i class="fas fa-signal"></i> ${(n.impact_level||'medium').toUpperCase()}</span><span><i class="fas fa-clock"></i> ${n.published_at?new Date(n.published_at).toLocaleDateString():'Recent'}</span></div></div>`;});
        document.getElementById('newsModalBody').innerHTML=h;
    });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
