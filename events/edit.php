<?php
/**
 * Edit Event (Admin only)
 */
$pageTitle = 'Edit Event';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$pdo = getDBConnection();
$eventId = (int) ($_GET['id'] ?? 0);

if (!$eventId) {
    setFlash('danger', 'Event not found.');
    redirect('events/index.php');
}

// Fetch event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found.');
    redirect('events/index.php');
}

$errors = [];
$old = $event; // pre-fill with existing data

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }
    
    $title         = sanitize($_POST['title'] ?? '');
    $description   = sanitize($_POST['description'] ?? '');
    $event_date    = $_POST['event_date'] ?? '';
    $event_time    = $_POST['event_time'] ?? null;
    $department    = sanitize($_POST['department'] ?? 'All');
    $category      = $_POST['category'] ?? 'other';
    $reminder_days = (int) ($_POST['reminder_days'] ?? 3);
    
    $old = array_merge($event, compact('title', 'description', 'event_date', 'event_time', 'department', 'category', 'reminder_days'));
    
    if (empty($title))      $errors[] = 'Event title is required.';
    if (empty($event_date)) $errors[] = 'Event date is required.';
    if ($reminder_days < 0 || $reminder_days > 30) $errors[] = 'Reminder days must be between 0 and 30.';
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE events SET title=?, description=?, event_date=?, event_time=?, department=?, category=?, reminder_days=?
            WHERE id=?
        ");
        $stmt->execute([
            $title, $description, $event_date,
            $event_time ?: null, $department, $category,
            $reminder_days, $eventId
        ]);
        
        setFlash('success', 'Event updated successfully!');
        redirect('events/index.php');
    }
}
?>

<div class="container py-4">
    <div class="page-header fade-in">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>events/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1><i class="bi bi-pencil-square me-2" style="color:var(--primary)"></i> Edit Event</h1>
                <p>Update event: <strong><?= sanitize($event['title']) ?></strong></p>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="content-card fade-in">
                <div class="content-card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= $e ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= sanitize($old['title']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= sanitize($old['description']) ?></textarea>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="event_date" class="form-label fw-semibold">Event Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" 
                                       value="<?= $old['event_date'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="event_time" class="form-label fw-semibold">Event Time</label>
                                <input type="time" class="form-control" id="event_time" name="event_time" 
                                       value="<?= $old['event_time'] ?>">
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="category" class="form-label fw-semibold">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <?php foreach (['lecture','exam','registration','seminar','workshop','deadline','other'] as $cat): ?>
                                        <option value="<?= $cat ?>" <?= $old['category'] === $cat ? 'selected' : '' ?>>
                                            <?= ucfirst($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="department" class="form-label fw-semibold">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="All" <?= $old['department'] === 'All' ? 'selected' : '' ?>>All Departments</option>
                                    <option <?= $old['department'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                    <option <?= $old['department'] === 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                    <option <?= $old['department'] === 'Business' ? 'selected' : '' ?>>Business</option>
                                    <option <?= $old['department'] === 'Arts & Humanities' ? 'selected' : '' ?>>Arts &amp; Humanities</option>
                                    <option <?= $old['department'] === 'Sciences' ? 'selected' : '' ?>>Sciences</option>
                                    <option <?= $old['department'] === 'Education' ? 'selected' : '' ?>>Education</option>
                                    <option <?= $old['department'] === 'Health Sciences' ? 'selected' : '' ?>>Health Sciences</option>
                                    <option <?= $old['department'] === 'Law' ? 'selected' : '' ?>>Law</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="reminder_days" class="form-label fw-semibold">Remind Before (days)</label>
                                <input type="number" class="form-control" id="reminder_days" name="reminder_days" 
                                       value="<?= $old['reminder_days'] ?>" min="0" max="30">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary-gradient px-4">
                                <i class="bi bi-check-lg me-1"></i> Update Event
                            </button>
                            <a href="<?= BASE_URL ?>events/index.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
