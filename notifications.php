<?php
/**
 * Notifications Page
 */
$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Mark as read
if (isset($_GET['mark_read'])) {
    $notifId = (int) $_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $userId]);
    redirect('notifications.php');
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    setFlash('success', 'All notifications marked as read.');
    redirect('notifications.php');
}

// Fetch notifications with event details
$stmt = $pdo->prepare("
    SELECT n.*, e.title as event_title, e.event_date, e.event_time, e.category, e.department 
    FROM notifications n 
    JOIN events e ON n.event_id = e.id 
    WHERE n.user_id = ? 
    ORDER BY n.sent_at DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadNotifs = array_filter($notifications, fn($n) => !$n['is_read']);
?>

<div class="container py-4">
    <div class="page-header fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="bi bi-bell me-2" style="color:var(--primary)"></i> Notifications</h1>
                <p>
                    <?= count($unreadNotifs) ?> unread &middot; <?= count($notifications) ?> total
                </p>
            </div>
            <?php if (!empty($unreadNotifs)): ?>
            <a href="?mark_all_read=1" class="btn btn-outline-primary-custom">
                <i class="bi bi-check-all me-1"></i> Mark All as Read
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="content-card fade-in">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-bell-slash"></i></div>
                <h5>No Notifications Yet</h5>
                <p>You'll receive notifications when event reminders are triggered.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $i => $notif): ?>
                    <div class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?> fade-in" 
                         style="animation-delay:<?= $i * 0.05 ?>s">
                        <div class="notification-icon <?= getCategoryBadge($notif['category']) ?>">
                            <i class="bi <?= getCategoryIcon($notif['category']) ?> text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold" style="font-size:.95rem"><?= sanitize($notif['event_title']) ?></h6>
                                    <p class="mb-1 text-muted" style="font-size:.85rem"><?= sanitize($notif['message']) ?></p>
                                    <div class="d-flex flex-wrap gap-2" style="font-size:.78rem">
                                        <span class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            Event: <?= date('M d, Y', strtotime($notif['event_date'])) ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-building me-1"></i>
                                            <?= sanitize($notif['department']) ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            Sent: <?= timeAgo($notif['sent_at']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ms-3 text-nowrap">
                                    <?php if (!$notif['is_read']): ?>
                                        <a href="?mark_read=<?= $notif['id'] ?>" class="btn btn-sm btn-outline-primary-custom"
                                           title="Mark as read">
                                            <i class="bi bi-check"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted">Read</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
