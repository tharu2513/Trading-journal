<?php
$pageTitle = 'Meeting Links';
require_once __DIR__ . '/includes/header.php';
$auth->requireLogin();
$userId = $auth->getUserId();
$db = Database::getInstance();
$csrf = $auth->generateCSRF();
$isAdmin = isUserAdmin($userId);

// Attendance එකක් සේව් කරන ලොජික් එක
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_action'])) {
    $meetingId = (int)$_POST['meeting_id'];
    $attendance_status = $_POST['attendance_status']; // present හෝ absent

    // කලින් මාර්ක් කරලා තියෙනවාද බලනවා, නැත්නම් අලුතින් දානවා (INSERT ... ON DUPLICATE KEY UPDATE)
    $db->query("INSERT INTO meeting_attendance (meeting_id, user_id, status) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = ?", [$meetingId, $userId, $attendance_status, $attendance_status]);

    if ($attendance_status === 'absent') {
        setFlash('warning', 'You marked yourself as Absent. Redirected back.');
        header('Location: /trading/dashboard.php'); // Absent නිසා බැක් කරනවා
        exit;
    } else {
        setFlash('success', 'Thank you! You are marked as Present.');
        header('Location: /trading/meetings.php');
        exit;
    }
}

// Admin actions: create/delete meetings (ඔයාගේ පරණ කෝඩ් එක)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && $auth->verifyCSRF($_POST['csrf_token']??'')) {
    $act = $_POST['form_action'] ?? '';
    if ($act === 'create_meeting') {
        $title = sanitize($_POST['title']??'');
        $desc = sanitize($_POST['description']??'');
        $url = sanitize($_POST['meeting_url']??'');
        $mid = sanitize($_POST['meeting_id']??'');
        $mpw = sanitize($_POST['meeting_password']??'');
        $sched_input = $_POST['scheduled_at'] ?? '';
        $sched = !empty($sched_input) ? date('Y-m-d H:i:s', strtotime($sched_input)) : '';
        $dur = max(1,(int)($_POST['duration_minutes']??60));
        if (empty($title)||empty($url)||empty($sched)) { 
            setFlash('error','Title, URL and date are required.'); 
        } else {
            $db->insert("INSERT INTO meetings (title,description,meeting_url,meeting_id,meeting_password,scheduled_at,duration_minutes,status,created_by) VALUES (?,?,?,?,?,?,?,?,?)",
                [$title,$desc,$url,$mid,$mpw,$sched,$dur,'upcoming',$userId]);
            setFlash('success','Meeting created!');
        }
    } elseif ($act === 'update_status') {
        $mid = (int)$_POST['meeting_id_update'];
        $status = $_POST['new_status']??'upcoming';
        $db->update("UPDATE meetings SET status=? WHERE id=?", [$status,$mid]);
        setFlash('success','Meeting status updated!');
    }
    header('Location: /trading/meetings.php'); exit;
}

if (isset($_GET['delete']) && $isAdmin) {
    $db->delete("DELETE FROM meetings WHERE id=?", [(int)$_GET['delete']]);
    setFlash('success','Meeting deleted.'); 
    header('Location: /trading/meetings.php'); exit;
}

// Auto-update live meetings
$db->update("UPDATE meetings SET status='live' WHERE status='upcoming' AND scheduled_at <= NOW() AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > NOW()");

// Auto-update past meetings
$db->update("UPDATE meetings SET status='completed' WHERE DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) <= NOW() AND status IN ('upcoming','live')");

// Upcoming Meetings ටික ගන්නවා

if ($isAdmin) {
    $upcoming = $db->fetchAll("SELECT m.*, u.username as creator FROM meetings m LEFT JOIN users u ON m.created_by=u.id WHERE m.status IN ('upcoming','live','inactive') ORDER BY m.scheduled_at ASC");
} else {
    $upcoming = $db->fetchAll("SELECT m.*, u.username as creator FROM meetings m LEFT JOIN users u ON m.created_by=u.id WHERE m.status IN ('upcoming','live') ORDER BY m.scheduled_at ASC");
}

// Past Meetings
$past = $db->fetchAll("SELECT m.*, u.username as creator FROM meetings m LEFT JOIN users u ON m.created_by=u.id WHERE m.status IN ('completed','cancelled') ORDER BY m.scheduled_at DESC LIMIT 20");
?>

<?php if ($isAdmin): ?>
<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted">Manage Zoom meeting links for your team.</p>
    <button class="btn btn-primary" onclick="openModal('addMeetingModal')"><i class="fas fa-plus"></i> New Meeting</button>
</div>
<?php else: ?>
<p class="text-muted mb-3">Join team meetings via the links below.</p>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><h3><i class="fas fa-video text-primary"></i> Upcoming & Live Meetings</h3></div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
        <div class="empty-state"><i class="fas fa-video"></i><h3>No upcoming meetings</h3><p>Check back later for scheduled meetings.</p></div>
        <?php else: ?>
        <?php foreach ($upcoming as $m): 
            // මේ යූසර් දැනටමත් මේ මීටින් එකට Attendance දාලද කියලා චෙක් කරනවා
            $userAttendance = $db->fetchOne("SELECT status FROM meeting_attendance WHERE meeting_id = ? AND user_id = ?", [$m['id'], $userId]);
            $hasMarkedPresent = ($userAttendance && $userAttendance['status'] === 'present');
        ?>
        <div class="news-card" style="border-left:4px solid <?php echo $m['status']==='live'?'var(--success)':'var(--primary)'; ?>; padding: 20px; margin-bottom: 15px; background: var(--white); border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div class="news-header d-flex justify-between align-center">
                <span class="news-title fw-bold" style="font-size: 16px; color: var(--gray-800);">
                    <?php if ($m['status']==='live'): ?><span class="badge badge-success" style="animation:pulse 1.5s infinite;"><i class="fas fa-circle"></i> LIVE</span> <?php endif; ?>
                    <?php echo sanitize($m['title']); ?>
                </span>
                <span class="badge badge-<?php echo $m['status']==='live'?'success':'primary'; ?>"><?php echo strtoupper($m['status']); ?></span>
            </div>
            
            <?php if ($m['description']): ?><div class="news-body mt-1" style="color: var(--gray-600); font-size: 14px;"><?php echo sanitize($m['description']); ?></div><?php endif; ?>
            
            <div class="news-meta mt-1 d-flex gap-2" style="font-size: 13px; color: var(--gray-500); flex-wrap: wrap;">
                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y - h:i A', strtotime($m['scheduled_at'])); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo $m['duration_minutes']; ?> min</span>
                <span><i class="fas fa-user"></i> By <?php echo sanitize($m['creator'] ?? 'Admin'); ?></span>
            </div>

            <div class="mt-2" style="background: var(--gray-50); padding: 15px; border-radius: 6px; border: 1px solid var(--gray-100);">
                <?php if (!$hasMarkedPresent): ?>
                    <p class="fw-bold mb-1" style="font-size: 14px; color: var(--gray-700);">Are you attending this meeting? Please mark below:</p>
                    <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="meeting_id" value="<?php echo $m['id']; ?>">
                        <input type="hidden" name="attendance_action" value="1">
                        
                        <button type="submit" name="attendance_status" value="present" class="btn btn-success" style="display:inline-flex; align-items:center; gap:6px; font-size:14px; padding:8px 16px;">
                            ✅ Yes, I'm Present
                        </button>
                        <button type="submit" name="attendance_status" value="absent" class="btn btn-danger" style="display:inline-flex; align-items:center; gap:6px; font-size:14px; padding:8px 16px;">
                            ❌ No, I'm Absent
                        </button>
                    </form>
                <?php else: ?>
                    <div class="d-flex justify-between align-center flex-wrap gap-1">
                        <div style="font-size: 13px; color: var(--success); font-weight: 600;">
                            <i class="fas fa-check-circle"></i> You are marked as Present for this session.
                        </div>
                        <div class="d-flex gap-1 mt-1">
                            <a href="<?php echo sanitize($m['meeting_url']); ?>" target="_blank" class="btn btn-<?php echo $m['status']==='live'?'success':'primary'; ?> btn-sm" <?php echo $m['status']==='live'?'style="animation:pulse 1.5s infinite;"':''; ?>><i class="fas fa-video"></i> Join Meeting</a>
                            
                            <?php if ($isAdmin): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="form_action" value="update_status">
                                <input type="hidden" name="meeting_id_update" value="<?php echo $m['id']; ?>">
                                <?php if ($m['status']==='upcoming'): ?>
                                <button type="submit" name="new_status" value="live" class="btn btn-success btn-sm"><i class="fas fa-play"></i> Set Live</button>
                                <?php else: ?>
                                <button type="submit" name="new_status" value="completed" class="btn btn-secondary btn-sm"><i class="fas fa-check"></i> End</button>
                                <?php endif; ?>
                            </form>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete('Delete this meeting?', '/trading/meetings.php?delete=<?php echo $m['id']; ?>')"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="news-meta mt-1 d-flex gap-2" style="font-size: 12px; color: var(--gray-600);">
                        <?php if ($m['meeting_id']): ?><span><i class="fas fa-hashtag"></i> ID: <strong><?php echo sanitize($m['meeting_id']); ?></strong></span><?php endif; ?>
                        <?php if ($m['meeting_password']): ?><span><i class="fas fa-key"></i> Pass: <strong><?php echo sanitize($m['meeting_password']); ?></strong></span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($past)): ?>
<div class="card">
    <div class="card-header"><h3><i class="fas fa-history text-primary"></i> Past Meetings</h3></div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead><tr><th>Title</th><th>Date</th><th>Duration</th><th>Host</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($past as $m): ?>
                <tr>
                    <td class="fw-bold"><?php echo sanitize($m['title']); ?></td>
                    <td><?php echo date('M d, Y h:i A', strtotime($m['scheduled_at'])); ?></td>
                    <td><?php echo $m['duration_minutes']; ?> min</td>
                    <td><?php echo sanitize($m['creator'] ?? 'Admin'); ?></td>
                    <td><span class="badge badge-<?php echo $m['status']==='completed'?'success':'neutral'; ?>"><?php echo ucfirst($m['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="modal-overlay" id="addMeetingModal"><div class="modal" style="max-width:650px;"><div class="modal-header"><h3><i class="fas fa-video text-primary"></i> Create Meeting</h3><button class="modal-close" onclick="closeModal('addMeetingModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>"><input type="hidden" name="form_action" value="create_meeting">
<div class="modal-body">
    <div class="form-group"><label>Meeting Title *</label><input type="text" name="title" class="form-control" required placeholder="e.g., Weekly Market Analysis"></div>
    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3" placeholder="Meeting agenda..."></textarea></div>
    <div class="form-group"><label>Zoom Meeting URL *</label><input type="url" name="meeting_url" class="form-control" required placeholder="https://zoom.us/j/..."></div>
    <div class="form-row">
        <div class="form-group"><label>Meeting ID</label><input type="text" name="meeting_id" class="form-control" placeholder="123 456 7890"></div>
        <div class="form-group"><label>Password</label><input type="text" name="meeting_password" class="form-control" placeholder="Optional"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Scheduled Date & Time *</label><input type="datetime-local" name="scheduled_at" class="form-control" required></div>
        <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" class="form-control" value="60" min="1"></div>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addMeetingModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create</button></div>
</form></div></div>
<?php endif; ?>

<style>@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.5;}}</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>