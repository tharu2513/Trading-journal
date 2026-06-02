<?php
$pageTitle = 'Analytics';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$userId = $auth->getUserId();
$stats = getTradeStats($userId);
$winRate = getWinRate($userId);
?>

<div class="card mb-3"><div class="card-body"><div class="d-flex justify-between align-center" style="flex-wrap:wrap;gap:10px;">
    <h3 class="text-primary"><i class="fas fa-chart-bar"></i> Advanced Analytics</h3>
    <div class="chart-controls">
        <button class="chart-filter-btn active" id="fMonthly" onclick="loadAll('monthly')">Monthly</button>
        <button class="chart-filter-btn" id="fWeekly" onclick="loadAll('weekly')">Weekly</button>
        <button class="chart-filter-btn" id="fDaily" onclick="loadAll('daily')">Daily</button>
        <button class="chart-filter-btn" id="fYearly" onclick="loadAll('yearly')">Yearly</button>
    </div>
</div></div></div>

<div class="grid-2 mb-3">
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-bar text-primary"></i> PnL Bar Chart</h3></div><div class="card-body"><canvas id="pnlBar" height="300"></canvas></div></div>
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-line text-primary"></i> Equity Curve</h3></div><div class="card-body"><canvas id="equity" height="300"></canvas></div></div>
</div>

<div class="grid-3 mb-3">
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-pie text-primary"></i> Win/Loss Ratio</h3></div>
        <div class="card-body text-center"><canvas id="wlPie" height="250"></canvas>
        <div class="mt-2"><span class="badge badge-success">Wins: <?php echo $winRate['wins']; ?></span> <span class="badge badge-danger">Losses: <?php echo $winRate['losses']; ?></span> <span class="badge badge-neutral">BE: <?php echo $winRate['breakeven']; ?></span></div></div></div>
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-pie text-primary"></i> Coin Distribution</h3></div><div class="card-body"><canvas id="coinPie" height="250"></canvas></div></div>
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-pie text-primary"></i> Exchange Distribution</h3></div><div class="card-body"><canvas id="exPie" height="250"></canvas></div></div>
</div>

<div class="grid-2 mb-3">
    <div class="card"><div class="card-header"><h3><i class="fas fa-layer-group text-primary"></i> Win/Loss by Coin</h3></div><div class="card-body"><canvas id="wlCoin" height="300"></canvas></div></div>
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-line text-primary"></i> Net PnL Trend</h3></div><div class="card-body"><canvas id="netLine" height="300"></canvas></div></div>
</div>

<div class="card"><div class="card-header"><h3><i class="fas fa-table text-primary"></i> Monthly Summary</h3></div>
<div class="card-body"><div id="mSummary"><div class="text-center"><div class="loading-spinner"></div></div></div></div></div>

<script>
const BC=['#1E40AF','#2563EB','#3B82F6','#60A5FA','#93C5FD','#BFDBFE','#1D4ED8','#1E3A8A','#3730A3','#4F46E5'];
let C={};
function dc(n){if(C[n])C[n].destroy();}

function loadAll(p){
    document.querySelectorAll('.chart-filter-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('f'+p.charAt(0).toUpperCase()+p.slice(1)).classList.add('active');
    loadPnL(p);loadEquity();loadWL();loadCoinP();loadExP();loadWLC();loadNet(p);loadMS();
}

function loadPnL(p){
    fetch('/trading/api/analytics.php?action=pnl_chart&period='+p+'&limit=30').then(r=>r.json()).then(d=>{
        dc('pnl');C.pnl=new Chart(document.getElementById('pnlBar'),{
            type:'bar',data:{labels:d.map(x=>x.label),datasets:[{label:'Gains',data:d.map(x=>parseFloat(x.gains)),backgroundColor:'#10B981',borderRadius:6},{label:'Losses',data:d.map(x=>-parseFloat(x.losses)),backgroundColor:'#EF4444',borderRadius:6}]},
            options:{responsive:true,plugins:{legend:{position:'top'}},scales:{y:{ticks:{callback:v=>'$'+v}},x:{grid:{display:false}}}}
        });
    });
}

function loadEquity(){
    fetch('/trading/api/analytics.php?action=equity_curve').then(r=>r.json()).then(d=>{
        dc('eq');C.eq=new Chart(document.getElementById('equity'),{
            type:'line',data:{labels:d.map(x=>x.date),datasets:[{label:'Equity',data:d.map(x=>x.equity),borderColor:'#2563EB',backgroundColor:'rgba(37,99,235,0.1)',fill:true,tension:0.3,pointRadius:2}]},
            options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>'$'+v}}}}
        });
    });
}

function loadWL(){
    dc('wl');C.wl=new Chart(document.getElementById('wlPie'),{
        type:'doughnut',data:{labels:['Wins','Losses','Breakeven'],datasets:[{data:[<?php echo $winRate['wins'].','.$winRate['losses'].','.$winRate['breakeven']; ?>],backgroundColor:['#10B981','#EF4444','#94A3B8']}]},
        options:{responsive:true,plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}}
    });
}

function loadCoinP(){
    fetch('/trading/api/analytics.php?action=coin_distribution').then(r=>r.json()).then(d=>{
        dc('cp');C.cp=new Chart(document.getElementById('coinPie'),{
            type:'doughnut',data:{labels:d.map(x=>x.coin_symbol),datasets:[{data:d.map(x=>x.trade_count),backgroundColor:BC}]},
            options:{responsive:true,plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}}
        });
    });
}

function loadExP(){
    fetch('/trading/api/analytics.php?action=exchange_distribution').then(r=>r.json()).then(d=>{
        dc('ep');C.ep=new Chart(document.getElementById('exPie'),{
            type:'pie',data:{labels:d.map(x=>x.exchange_name),datasets:[{data:d.map(x=>x.trade_count),backgroundColor:BC}]},
            options:{responsive:true,plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}}
        });
    });
}

function loadWLC(){
    fetch('/trading/api/analytics.php?action=win_loss_by_coin').then(r=>r.json()).then(d=>{
        dc('wlc');C.wlc=new Chart(document.getElementById('wlCoin'),{
            type:'bar',data:{labels:d.map(x=>x.coin_symbol),datasets:[{label:'Wins',data:d.map(x=>parseInt(x.wins)),backgroundColor:'#10B981',borderRadius:4},{label:'Losses',data:d.map(x=>parseInt(x.losses)),backgroundColor:'#EF4444',borderRadius:4}]},
            options:{responsive:true,scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,beginAtZero:true}},plugins:{legend:{position:'top'}}}
        });
    });
}

function loadNet(p){
    fetch('/trading/api/analytics.php?action=pnl_chart&period='+p+'&limit=30').then(r=>r.json()).then(d=>{
        dc('nl');C.nl=new Chart(document.getElementById('netLine'),{
            type:'line',data:{labels:d.map(x=>x.label),datasets:[{label:'Net PnL',data:d.map(x=>parseFloat(x.net_pnl)),borderColor:'#2563EB',backgroundColor:'rgba(37,99,235,0.1)',fill:true,tension:0.4,pointBackgroundColor:d.map(x=>parseFloat(x.net_pnl)>=0?'#10B981':'#EF4444'),pointRadius:4}]},
            options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>'$'+v}}}}
        });
    });
}

function loadMS(){
    fetch('/trading/api/analytics.php?action=monthly_summary').then(r=>r.json()).then(d=>{
        if(!d.length){document.getElementById('mSummary').innerHTML='<div class="empty-state"><p>No data yet.</p></div>';return;}
        let h='<table><thead><tr><th>Month</th><th>Trades</th><th>Wins</th><th>Win Rate</th><th>Total PnL</th><th>Avg PnL</th></tr></thead><tbody>';
        d.forEach(x=>{const pnl=parseFloat(x.total_pnl),wr=x.trades>0?((x.wins/x.trades)*100).toFixed(1):0;
            h+=`<tr><td class="fw-bold">${x.month}</td><td>${x.trades}</td><td>${x.wins}</td><td>${wr}%</td><td class="fw-bold ${pnl>=0?'text-success':'text-danger'}">$${pnl.toFixed(2)}</td><td>$${parseFloat(x.avg_pnl).toFixed(2)}</td></tr>`;});
        h+='</tbody></table>';document.getElementById('mSummary').innerHTML=h;
    });
}

loadAll('monthly');
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
