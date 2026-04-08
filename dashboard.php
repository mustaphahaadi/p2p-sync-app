<?php
/**
 * Dashboard
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$userDept = $_SESSION['user_dept'] ?? 'All';

// ---- Stats ----
$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$totalEvents = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM events 
    WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND (department = ? OR department = 'All')
");
$stmt->execute([$userDept]);
$upcomingCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$userId]);
$totalNotifications = $stmt->fetchColumn();

$totalUsers = 0;
if (isAdmin()) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
}

// ---- Upcoming Events (next 14 days) ----
$stmt = $pdo->prepare("
    SELECT * FROM events 
    WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    AND (department = ? OR department = 'All')
    ORDER BY event_date ASC 
    LIMIT 6
");
$stmt->execute([$userDept]);
$upcomingEvents = $stmt->fetchAll();

// ---- Recent Notifications ----
$stmt = $pdo->prepare("
    SELECT n.*, e.title as event_title, e.category 
    FROM notifications n 
    JOIN events e ON n.event_id = e.id 
    WHERE n.user_id = ? 
    ORDER BY n.sent_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recentNotifications = $stmt->fetchAll();

// ---- Today's Events ----
$stmt = $pdo->prepare("
    SELECT * FROM events 
    WHERE event_date = CURDATE() AND (department = ? OR department = 'All')
    ORDER BY event_time ASC
");
$stmt->execute([$userDept]);
$todayEvents = $stmt->fetchAll();
?>

<div class="page-content">
    <!-- Page Header -->
    <div class="page-header fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="bi bi-grid-1x2 me-2" style="color:var(--primary)"></i> Dashboard</h1>
                <p>Welcome back, <strong><?= sanitize($currentUser['name']) ?></strong> — here's what's happening on campus</p>
            </div>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>events/create.php" class="btn btn-primary-solid">
                <i class="bi bi-plus-lg me-1"></i> Add Event
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6 fade-in stagger-1">
            <div class="stat-card stat-events">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-events"><i class="bi bi-calendar-event"></i></div>
                    <div>
                        <div class="stat-value"><?= $totalEvents ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 fade-in stagger-2">
            <div class="stat-card stat-upcoming">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-upcoming"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-value"><?= $upcomingCount ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 fade-in stagger-3">
            <div class="stat-card stat-notifications">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-notifications"><i class="bi bi-bell"></i></div>
                    <div>
                        <div class="stat-value"><?= $totalNotifications ?></div>
                        <div class="stat-label">Notifications</div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (isAdmin()): ?>
        <div class="col-md-3 col-6 fade-in stagger-4">
            <div class="stat-card stat-users">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-users"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-value"><?= $totalUsers ?></div>
                        <div class="stat-label">Users</div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-3 col-6 fade-in stagger-4">
            <div class="stat-card stat-users">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-users"><i class="bi bi-mortarboard"></i></div>
                    <div>
                        <div class="stat-value"><?= sanitize(ucfirst($currentUser['role'])) ?></div>
                        <div class="stat-label"><?= sanitize($currentUser['department']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Today's Events Banner -->
    <?php if (!empty($todayEvents)): ?>
    <div class="alert alert-info d-flex align-items-center mb-4 fade-in">
        <i class="bi bi-star-fill me-3 fs-5"></i>
        <div>
            <strong><?= count($todayEvents) ?> event<?= count($todayEvents) > 1 ? 's' : '' ?> today!</strong>
            <?php foreach ($todayEvents as $te): ?>
                <span class="badge bg-primary ms-2"><?= sanitize($te['title']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Content Row -->
    <div class="row g-4">
        <!-- Upcoming Events -->
        <div class="col-lg-7 fade-in">
            <div class="content-card">
                <div class="content-card-header">
                    <h5><i class="bi bi-calendar2-week me-2" style="color:var(--primary)"></i> Upcoming Events</h5>
                    <a href="<?= BASE_URL ?>events/index.php" class="btn btn-sm btn-outline-primary-custom">View All</a>
                </div>
                <div class="content-card-body p-0">
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                            <h5>No Upcoming Events</h5>
                            <p>There are no events scheduled in the next 14 days.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline-container p-3">
                            <?php foreach ($upcomingEvents as $event): ?>
                                <div class="timeline-item d-flex pb-3 position-relative">
                                    <div class="timeline-badge me-3 d-flex flex-column align-items-center">
                                        <div class="timeline-dot rounded-circle bg-primary" style="width: 12px; height: 12px; margin-top: 5px;"></div>
                                        <div class="timeline-line bg-secondary flex-grow-1 mt-1 opacity-25" style="width: 2px;"></div>
                                    </div>
                                    <div class="timeline-content flex-grow-1 pb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-1 fw-bold"><?= sanitize($event['title']) ?></h6>
                                            <?php
                                            $daysLeft = (int) ((strtotime($event['event_date']) - strtotime('today')) / 86400);
                                            if ($daysLeft === 0) {
                                                echo '<span class="badge bg-danger shadow-sm">Today</span>';
                                            } elseif ($daysLeft === 1) {
                                                echo '<span class="badge bg-warning text-dark shadow-sm">Tomorrow</span>';
                                            } else {
                                                echo '<span class="badge bg-light text-dark border shadow-sm">' . $daysLeft . ' days</span>';
                                            }
                                            ?>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?php 
                                                if (!empty($event['end_date']) && $event['end_date'] !== $event['event_date']) {
                                                    $sFmt = date('Y') == date('Y', strtotime($event['event_date'])) ? 'M d' : 'M d, Y';
                                                    echo date($sFmt, strtotime($event['event_date'])) . ' - ' . date('M d, Y', strtotime($event['end_date']));
                                                } else {
                                                    echo date('M d, Y', strtotime($event['event_date']));
                                                }
                                            ?>
                                            <?php if (!empty($event['semester'])): ?>
                                                &bull; <?= sanitize($event['semester']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="badge <?= getCategoryBadge($event['category']) ?> me-1">
                                                <i class="bi <?= getCategoryIcon($event['category']) ?> me-1"></i>
                                                <?= ucfirst($event['category']) ?>
                                            </span>
                                            <span class="text-muted small">
                                                <i class="bi bi-building me-1"></i><?= sanitize($event['department']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Notifications -->
        <div class="col-lg-5 fade-in">
            <div class="content-card">
                <div class="content-card-header">
                    <h5><i class="bi bi-bell me-2" style="color:var(--amber)"></i> Recent Notifications</h5>
                    <a href="<?= BASE_URL ?>notifications.php" class="btn btn-sm btn-outline-primary-custom">View All</a>
                </div>
                <div class="content-card-body p-0">
                    <?php if (empty($recentNotifications)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-bell-slash"></i></div>
                            <h5>No Notifications</h5>
                            <p>You'll be notified about upcoming events.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentNotifications as $notif): ?>
                                <div class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
                                    <div class="notification-icon <?= getCategoryBadge($notif['category']) ?>">
                                        <i class="bi <?= getCategoryIcon($notif['category']) ?> text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold" style="font-size:.88rem"><?= sanitize($notif['event_title']) ?></div>
                                        <p class="mb-0 text-muted" style="font-size:.78rem">
                                            <?= sanitize(shortenText($notif['message'], 80)) ?>
                                        </p>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= timeAgo($notif['sent_at']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
