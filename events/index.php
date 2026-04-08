<?php
/**
 * Events Listing
 */
$pageTitle = 'Events';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pdo = getDBConnection();
$userDept = $_SESSION['user_dept'] ?? 'All';

// Filters
$filterCategory   = $_GET['category'] ?? '';
$filterDepartment = $_GET['department'] ?? '';
$filterSearch     = sanitize($_GET['search'] ?? '');
$filterPeriod     = $_GET['period'] ?? 'upcoming';

// Build query
$where = [];
$params = [];

if ($filterPeriod === 'upcoming') {
    $where[] = "event_date >= CURDATE()";
} elseif ($filterPeriod === 'past') {
    $where[] = "event_date < CURDATE()";
}

if ($filterCategory) {
    $where[] = "category = ?";
    $params[] = $filterCategory;
}
if ($filterDepartment) {
    $where[] = "department = ?";
    $params[] = $filterDepartment;
}
if ($filterSearch) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
}

// Non-admin users also see 'All' department events
if (!isAdmin() && empty($filterDepartment)) {
    $where[] = "(department = ? OR department = 'All')";
    $params[] = $userDept;
}

$sql = "SELECT * FROM events";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY event_date " . ($filterPeriod === 'past' ? 'DESC' : 'ASC');

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Departments for filter
$deptStmt = $pdo->query("SELECT DISTINCT department FROM events ORDER BY department");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-content">
    <!-- Page Header -->
    <div class="page-header fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="bi bi-calendar-event me-2" style="color:var(--primary)"></i> Academic Events</h1>
                <p>Browse and manage campus academic calendar events</p>
            </div>
            <?php if (isAdmin()): ?>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>events/calendar.php" class="btn btn-outline-primary-custom">
                    <i class="bi bi-calendar3 me-1"></i> Calendar View
                </a>
                <a href="<?= BASE_URL ?>events/import.php" class="btn btn-outline-secondary">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Import
                </a>
                <a href="<?= BASE_URL ?>events/create.php" class="btn btn-primary-solid">
                    <i class="bi bi-plus-lg me-1"></i> Add Event
                </a>
            </div>
            <?php else: ?>
            <a href="<?= BASE_URL ?>events/calendar.php" class="btn btn-outline-primary-custom">
                <i class="bi bi-calendar3 me-1"></i> Calendar View
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="content-card mb-4 fade-in">
        <div class="content-card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control search-input" name="search" 
                               placeholder="Search events..." value="<?= $filterSearch ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="period">
                        <option value="upcoming" <?= $filterPeriod === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="past" <?= $filterPeriod === 'past' ? 'selected' : '' ?>>Past</option>
                        <option value="all" <?= $filterPeriod === 'all' ? 'selected' : '' ?>>All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach (['lecture','exam','registration','seminar','workshop','deadline','other'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= sanitize($dept) ?>" <?= $filterDepartment === $dept ? 'selected' : '' ?>><?= sanitize($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-solid flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= BASE_URL ?>events/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results count -->
    <p class="text-muted mb-3 fade-in" style="font-size:.85rem">
        <i class="bi bi-list-ul me-1"></i> Showing <strong><?= count($events) ?></strong> event<?= count($events) !== 1 ? 's' : '' ?>
    </p>
    
    <!-- Events Grid -->
    <?php if (empty($events)): ?>
        <div class="content-card fade-in">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                <h5>No Events Found</h5>
                <p>Try adjusting your filters or check back later.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($events as $i => $event): ?>
                <div class="col-md-6 col-lg-4 fade-in" style="animation-delay:<?= ($i % 6) * 0.08 ?>s">
                    <div class="content-card h-100">
                        <div class="content-card-body d-flex flex-column">
                            <!-- Category & Date -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge <?= getCategoryBadge($event['category']) ?>">
                                    <i class="bi <?= getCategoryIcon($event['category']) ?> me-1"></i>
                                    <?= ucfirst($event['category']) ?>
                                </span>
                                <?php
                                $daysLeft = (int) ((strtotime($event['event_date']) - strtotime('today')) / 86400);
                                if ($daysLeft < 0) {
                                    echo '<span class="badge bg-secondary">Passed</span>';
                                } elseif ($daysLeft === 0) {
                                    echo '<span class="badge bg-danger">Today</span>';
                                } elseif ($daysLeft <= 3) {
                                    echo '<span class="badge bg-warning text-dark">' . $daysLeft . 'd left</span>';
                                }
                                ?>
                            </div>
                            
                            <!-- Title & Description -->
                            <h6 class="fw-bold mb-2"><?= sanitize($event['title']) ?></h6>
                            <p class="text-muted mb-3" style="font-size:.85rem;flex-grow:1">
                                <?= sanitize(shortenText($event['description'] ?? '', 120)) ?>
                            </p>
                            
                            <!-- Meta -->
                            <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:.8rem">
                                <span class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php 
                                        if (!empty($event['end_date']) && $event['end_date'] !== $event['event_date']) {
                                            $sFmt = date('Y') == date('Y', strtotime($event['event_date'])) ? 'M d' : 'M d, Y';
                                            echo date($sFmt, strtotime($event['event_date'])) . ' - ' . date('M d, Y', strtotime($event['end_date']));
                                        } else {
                                            echo date('M d, Y', strtotime($event['event_date']));
                                        }
                                    ?>
                                </span>
                                <?php if (!empty($event['semester'])): ?>
                                <span class="text-muted">
                                    <i class="bi bi-bookmark-fill me-1" style="color: var(--teal)"></i>
                                    <?= sanitize($event['semester']) ?> 
                                    <?= !empty($event['academic_year']) ? " (" . sanitize($event['academic_year']) . ")" : "" ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($event['event_time']): ?>
                                <span class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('g:i A', strtotime($event['event_time'])) ?>
                                </span>
                                <?php endif; ?>
                                <span class="text-muted">
                                    <i class="bi bi-building me-1"></i>
                                    <?= sanitize($event['department']) ?>
                                </span>
                            </div>
                            
                            <div class="d-flex gap-2 align-items-center" style="font-size:.78rem">
                                <span class="text-muted">
                                    <i class="bi bi-alarm me-1"></i> Reminder: <?= $event['reminder_days'] ?> day<?= $event['reminder_days'] > 1 ? 's' : '' ?> before
                                </span>
                            </div>
                            
                            <?php if (isAdmin()): ?>
                            <hr class="my-2">
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>events/edit.php?id=<?= $event['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary-custom flex-grow-1">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                <a href="<?= BASE_URL ?>events/delete.php?id=<?= $event['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger flex-grow-1"
                                   onclick="return confirm('Delete this event?')">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
