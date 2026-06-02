<?php
$pageTitle = 'US Economy Fundamentals';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$db = Database::getInstance();

$filterCat = $_GET['category'] ?? '';
$filterCoin = $_GET['coin'] ?? '';
$where = '1=1'; $params = []; $types = '';
if ($filterCat) { $where .= " AND category=?"; $params[] = $filterCat; $types .= 's'; }
if ($filterCoin) { $where .= " AND coin_symbol=?"; $params[] = strtoupper($filterCoin); $types .= 's'; }
$news = $db->fetchAll("SELECT * FROM fundamentals WHERE $where ORDER BY published_at DESC LIMIT 50", $params, $types);

$categories = ['' => 'All Categories','cpi' => 'CPI','interest_rate' => 'Interest Rate (Fed)','employment' => 'Employment / NFP','gdp' => 'GDP Data','dxy' => 'DXY (Dollar Index)','other' => 'Other'];
$coins = getTop100Coins();
?>

<div class="card mb-3"><div class="card-body">
    <p class="text-muted mb-2">Track US economy fundamentals impacting Bitcoin and crypto markets.</p>
    <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
        <select name="category" class="form-control" style="width:auto;">
            <?php foreach ($categories as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo $filterCat===$k?'selected':''; ?>><?php echo $v; ?></option><?php endforeach; ?>
        </select>
        <select name="coin" class="form-control" style="width:auto;"><option value="">All Coins</option>
            <?php foreach ($coins as $c): ?><option value="<?php echo $c['symbol']; ?>" <?php echo $filterCoin===$c['symbol']?'selected':''; ?>><?php echo $c['symbol'].' - '.$c['name']; ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="/trading/fundamentals.php" class="btn btn-secondary btn-sm">Reset</a>
    </form>
</div></div>

<div class="stats-grid mb-3">
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-arrow-up"></i></div><div class="stat-info"><h4>Bullish</h4><div class="stat-value text-success"><?php echo count(array_filter($news, fn($n)=>$n['impact']==='bullish')); ?></div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fas fa-arrow-down"></i></div><div class="stat-info"><h4>Bearish</h4><div class="stat-value text-danger"><?php echo count(array_filter($news, fn($n)=>$n['impact']==='bearish')); ?></div></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-minus"></i></div><div class="stat-info"><h4>Neutral</h4><div class="stat-value text-warning"><?php echo count(array_filter($news, fn($n)=>$n['impact']==='neutral')); ?></div></div></div>
</div>

<div class="card mb-3"><div class="card-header"><h3><i class="fas fa-search text-primary"></i> View Impact News by Coin</h3></div>
<div class="card-body">
    <div class="d-flex gap-1" style="flex-wrap:wrap;">
        <?php foreach (['BTC'=>'Bitcoin','ETH'=>'Ethereum','SOL'=>'Solana','XRP'=>'XRP','ADA'=>'Cardano','DOGE'=>'Dogecoin','AVAX'=>'Avalanche','DOT'=>'Polkadot','LINK'=>'Chainlink','SUI'=>'Sui'] as $sym=>$nm): ?>
        <button class="chart-filter-btn <?php echo $sym==='BTC'?'active':''; ?>" onclick="loadCoinNews('<?php echo $sym; ?>','<?php echo $nm; ?>',this)"><?php echo $sym; ?></button>
        <?php endforeach; ?>
    </div>
    <div id="coinNewsResult" class="mt-2"></div>
</div></div>

<div class="card"><div class="card-header"><h3><i class="fas fa-newspaper text-primary"></i> All Fundamental Events</h3></div>
<div class="card-body">
    <?php if (empty($news)): ?><div class="empty-state"><i class="fas fa-newspaper"></i><h3>No events found</h3></div>
    <?php else: foreach ($news as $n): ?>
    <div class="news-card">
        <div class="news-header"><span class="news-title"><?php echo sanitize($n['title']); ?></span>
            <div class="d-flex gap-1"><span class="badge badge-primary"><?php echo sanitize($n['coin_symbol']); ?></span>
            <span class="badge badge-<?php echo $n['impact']==='bullish'?'success':($n['impact']==='bearish'?'danger':'warning'); ?>">
                <i class="fas fa-<?php echo $n['impact']==='bullish'?'arrow-up':($n['impact']==='bearish'?'arrow-down':'minus'); ?>"></i> <?php echo strtoupper($n['impact']); ?>
            </span></div>
        </div>
        <div class="news-body"><?php echo sanitize($n['description']); ?></div>
        <div class="news-meta">
            <span><i class="fas fa-tag"></i> <?php echo strtoupper(str_replace('_',' ',$n['category'])); ?></span>
            <span><i class="fas fa-signal"></i> <?php echo strtoupper($n['impact_level']); ?></span>
            <span><i class="fas fa-clock"></i> <?php echo $n['published_at']?date('M d, Y',strtotime($n['published_at'])):'Recent'; ?></span>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div></div>

<script>
function loadCoinNews(sym,name,btn){
    document.querySelectorAll('.chart-filter-btn').forEach(b=>b.classList.remove('active'));
    if(btn)btn.classList.add('active');
    const c=document.getElementById('coinNewsResult');
    c.innerHTML='<div class="text-center"><div class="loading-spinner"></div></div>';
    fetch('/trading/api/news.php?action=by_coin&symbol='+sym).then(r=>r.json()).then(news=>{
        if(!news.length){c.innerHTML='<div class="alert alert-info">No news for '+sym+'.</div>';return;}
        let h='<h4 class="mb-2">'+name+' ('+sym+') Impact News</h4>';
        news.forEach(n=>{h+=`<div class="news-card"><div class="news-header"><span class="news-title">${n.title}</span><span class="badge badge-${n.impact==='bullish'?'success':(n.impact==='bearish'?'danger':'warning')}">${n.impact.toUpperCase()}</span></div><div class="news-body">${n.description||''}</div><div class="news-meta"><span><i class="fas fa-tag"></i> ${(n.category||'other').replace('_',' ').toUpperCase()}</span><span><i class="fas fa-signal"></i> ${(n.impact_level||'medium').toUpperCase()}</span></div></div>`;});
        c.innerHTML=h;
    });
}
loadCoinNews('BTC','Bitcoin',null);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
