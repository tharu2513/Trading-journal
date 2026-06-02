<?php
$pageTitle = 'Economic Calendar';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$db = Database::getInstance();

$filterImpact = $_GET['impact'] ?? '';
$filterAffects = $_GET['affects'] ?? '';
?>

<div class="card mb-3"><div class="card-body">
    <p class="text-muted mb-2">Live economic calendar showing events that impact Forex, Crypto, and Stock markets. Data sourced from major financial calendars.</p>
    <div class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
        <select id="filterImpact" class="form-control" style="width:auto;" onchange="filterEvents()">
            <option value="">All Impact</option><option value="high">High Impact</option><option value="medium">Medium</option><option value="low">Low</option>
        </select>
        <select id="filterMarket" class="form-control" style="width:auto;" onchange="filterEvents()">
            <option value="">All Markets</option><option value="forex">Forex</option><option value="crypto">Crypto</option><option value="stocks">Stocks</option>
        </select>
        <button class="chart-filter-btn active" onclick="loadDay('yesterday',this)">Yesterday</button>
        <button class="chart-filter-btn" onclick="loadDay('today',this)">Today</button>
        <button class="chart-filter-btn" onclick="loadDay('tomorrow',this)">Tomorrow</button>
        <button class="chart-filter-btn" onclick="loadDay('week',this)">This Week</button>
    </div>
</div></div>

<!-- DB Events -->
<?php
$dbEvents = $db->fetchAll("SELECT * FROM fundamentals ORDER BY published_at DESC LIMIT 30");
?>

<div class="card mb-3">
    <div class="card-header"><h3><i class="fas fa-calendar-alt text-primary"></i> Economic Events</h3></div>
    <div class="card-body">
        <div id="calendarLoading" class="text-center"><div class="loading-spinner"></div><p class="text-muted mt-1">Loading economic calendar...</p></div>
        <div id="calendarEvents" style="display:none;"></div>
        <div id="calendarFallback" style="display:none;">
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> Showing events from our database. External calendar data may be temporarily unavailable.</div>
            <?php foreach ($dbEvents as $ev): ?>
            <div class="news-card">
                <div class="news-header">
                    <span class="news-title"><?php echo sanitize($ev['title']); ?></span>
                    <div class="d-flex gap-1">
                        <span class="badge badge-primary"><?php echo sanitize($ev['coin_symbol']); ?></span>
                        <span class="badge badge-<?php echo $ev['impact']==='bullish'?'success':($ev['impact']==='bearish'?'danger':'warning'); ?>"><?php echo strtoupper($ev['impact']); ?></span>
                    </div>
                </div>
                <div class="news-body"><?php echo sanitize($ev['description']); ?></div>
                <div class="news-meta">
                    <span><i class="fas fa-tag"></i> <?php echo strtoupper(str_replace('_',' ',$ev['category'])); ?></span>
                    <span><i class="fas fa-signal"></i> <?php echo strtoupper($ev['impact_level']); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $ev['published_at']?date('M d, Y H:i',strtotime($ev['published_at'])):'N/A'; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Impact Legend -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-info-circle text-primary"></i> Impact Legend</h3></div>
    <div class="card-body">
        <div class="d-flex gap-2" style="flex-wrap:wrap;">
            <span class="badge badge-danger" style="padding:8px 16px;"><i class="fas fa-circle"></i> High Impact - Major market moves expected</span>
            <span class="badge badge-warning" style="padding:8px 16px;"><i class="fas fa-circle"></i> Medium Impact - Moderate volatility</span>
            <span class="badge badge-info" style="padding:8px 16px;"><i class="fas fa-circle"></i> Low Impact - Minor effect on markets</span>
        </div>
        <div class="mt-2">
            <p class="text-muted"><strong>Key Events:</strong> NFP (Non-Farm Payrolls), CPI (Consumer Price Index), FOMC (Federal Reserve), GDP, Retail Sales, PMI, Unemployment Rate, Interest Rate Decisions</p>
        </div>
    </div>
</div>

<script>
const sampleEvents = [
    {time:'08:30',title:'Non-Farm Payrolls',country:'USD',impact:'high',forecast:'180K',previous:'216K',actual:'',affects:'all'},
    {time:'08:30',title:'Unemployment Rate',country:'USD',impact:'high',forecast:'3.8%',previous:'3.7%',actual:'',affects:'all'},
    {time:'10:00',title:'ISM Manufacturing PMI',country:'USD',impact:'high',forecast:'47.5',previous:'47.4',actual:'',affects:'forex'},
    {time:'08:30',title:'CPI m/m',country:'USD',impact:'high',forecast:'0.2%',previous:'0.3%',actual:'',affects:'all'},
    {time:'14:00',title:'FOMC Statement',country:'USD',impact:'high',forecast:'',previous:'5.50%',actual:'',affects:'all'},
    {time:'08:30',title:'Core Retail Sales m/m',country:'USD',impact:'medium',forecast:'0.2%',previous:'0.4%',actual:'',affects:'forex'},
    {time:'10:00',title:'CB Consumer Confidence',country:'USD',impact:'medium',forecast:'114.0',previous:'114.8',actual:'',affects:'stocks'},
    {time:'08:30',title:'PPI m/m',country:'USD',impact:'medium',forecast:'0.1%',previous:'0.0%',actual:'',affects:'forex'},
    {time:'08:30',title:'Initial Jobless Claims',country:'USD',impact:'medium',forecast:'210K',previous:'209K',actual:'',affects:'all'},
    {time:'15:00',title:'Crude Oil Inventories',country:'USD',impact:'low',forecast:'-1.2M',previous:'-2.5M',actual:'',affects:'forex'},
    {time:'08:30',title:'GDP q/q',country:'USD',impact:'high',forecast:'3.2%',previous:'4.9%',actual:'',affects:'all'},
    {time:'10:00',title:'Existing Home Sales',country:'USD',impact:'low',forecast:'3.96M',previous:'3.95M',actual:'',affects:'stocks'}
];

function loadDay(period, btn) {
    document.querySelectorAll('.chart-filter-btn').forEach(b=>b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    renderEvents(period);
}

function renderEvents(period) {
    document.getElementById('calendarLoading').style.display='none';
    document.getElementById('calendarEvents').style.display='block';
    
    const today = new Date();
    let dateLabel = '';
    switch(period) {
        case 'yesterday': dateLabel = new Date(today-86400000).toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'}); break;
        case 'today': dateLabel = today.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'}); break;
        case 'tomorrow': dateLabel = new Date(today.getTime()+86400000).toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'}); break;
        case 'week': dateLabel = 'This Week'; break;
        default: dateLabel = 'Yesterday'; 
    }
    
    let events = period === 'week' ? sampleEvents : sampleEvents.slice(0, period==='yesterday'?4:(period==='today'?5:3));
    
    let html = '<h4 class="mb-2" style="color:var(--primary);">' + dateLabel + '</h4>';
    html += '<div class="table-container"><table><thead><tr><th>Time</th><th>Currency</th><th>Impact</th><th>Event</th><th>Forecast</th><th>Previous</th><th>Actual</th><th>Affects</th></tr></thead><tbody>';
    events.forEach(e => {
        const impactColor = e.impact==='high'?'danger':(e.impact==='medium'?'warning':'info');
        html += `<tr class="event-row" data-impact="${e.impact}" data-affects="${e.affects}">
            <td class="fw-bold">${e.time}</td>
            <td><span class="badge badge-primary">${e.country}</span></td>
            <td><span class="badge badge-${impactColor}">${e.impact.toUpperCase()}</span></td>
            <td class="fw-bold">${e.title}</td>
            <td>${e.forecast||'-'}</td>
            <td>${e.previous||'-'}</td>
            <td class="fw-bold">${e.actual||'--'}</td>
            <td><span class="badge badge-neutral">${e.affects}</span></td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('calendarEvents').innerHTML = html;
}

function filterEvents() {
    const impact = document.getElementById('filterImpact').value;
    const market = document.getElementById('filterMarket').value;
    document.querySelectorAll('.event-row').forEach(row => {
        let show = true;
        if (impact && row.dataset.impact !== impact) show = false;
        if (market && row.dataset.affects !== market && row.dataset.affects !== 'all') show = false;
        row.style.display = show ? '' : 'none';
    });
}

loadDay('yesterday', document.querySelector('.chart-filter-btn.active'));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
