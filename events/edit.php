<?php
/**
 * Edit Event (Admin only)
 */
$pageTitle = 'Edit Event';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/audit.php';
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
    $end_date      = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $event_time    = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
    $academic_year = sanitize($_POST['academic_year'] ?? '');
    $semester      = sanitize($_POST['semester'] ?? 'First Semester');
    $department    = sanitize($_POST['department'] ?? 'All');
    $category      = $_POST['category'] ?? 'other';
    $reminder_time = (int) ($_POST['reminder_time'] ?? 1);
    $reminder_unit = $_POST['reminder_unit'] ?? 'days';
    
    // Determine status from which button was clicked
    $status = isset($_POST['save_draft']) ? 'draft' : 'published';
    
    $old = array_merge($event, compact('title', 'description', 'event_date', 'end_date', 'event_time', 'academic_year', 'semester', 'department', 'category', 'reminder_time', 'reminder_unit', 'status'));
    
    if (empty($title))      $errors[] = 'Event title is required.';
    if (empty($event_date)) $errors[] = 'Event date is required.';
    if ($reminder_time < 0) $errors[] = 'Reminder time cannot be negative.';
    if (!in_array($reminder_unit, ['minutes', 'hours', 'days'])) $errors[] = 'Invalid reminder unit.';
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE events SET title=?, description=?, event_date=?, end_date=?, event_time=?, academic_year=?, semester=?, department=?, category=?, reminder_time=?, reminder_unit=?, status=?
            WHERE id=?
        ");
        $stmt->execute([
            $title, $description, $event_date, $end_date,
            $event_time, $academic_year, $semester, $department, $category,
            $reminder_time, $reminder_unit, $status, $eventId
        ]);
        
        // Audit log
        logAuditAction($_SESSION['user_id'], 'updated', 'event', $eventId, "Updated event: $title (Status: $status)");
        
        setFlash('success', 'Event updated successfully!');
        redirect('events/index.php');
    }
}
?>

<div class="page-content">
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
                                <label for="academic_year" class="form-label fw-semibold">Academic Year</label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                       value="<?= sanitize($old['academic_year']) ?>" placeholder="e.g. 2025/2026">
                            </div>
                            <div class="col-md-6">
                                <label for="semester" class="form-label fw-semibold">Semester</label>
                                <select class="form-select" id="semester" name="semester">
                                    <option <?= $old['semester'] === 'First Semester' ? 'selected' : '' ?>>First Semester</option>
                                    <option <?= $old['semester'] === 'Second Semester' ? 'selected' : '' ?>>Second Semester</option>
                                    <option <?= $old['semester'] === 'Vacation' ? 'selected' : '' ?>>Vacation</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="event_date" class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" 
                                       value="<?= $old['event_date'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label fw-semibold">End Date <small class="text-muted">(Optional)</small></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?= $old['end_date'] ?>">
                            </div>
                            <div class="col-md-4">
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
                                <label class="form-label fw-semibold">Remind Before</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="reminder_time" 
                                           value="<?= sanitize($old['reminder_time'] ?? 1) ?>" min="0">
                                    <select class="form-select" name="reminder_unit" style="max-width: 120px;">
                                        <option value="minutes" <?= ($old['reminder_unit'] ?? 'days') === 'minutes' ? 'selected' : '' ?>>Minutes</option>
                                        <option value="hours" <?= ($old['reminder_unit'] ?? 'days') === 'hours' ? 'selected' : '' ?>>Hours</option>
                                        <option value="days" <?= ($old['reminder_unit'] ?? 'days') === 'days' ? 'selected' : '' ?>>Days</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex gap-3">
                            <button type="submit" name="publish" value="1" class="btn btn-primary-solid px-4">
                                <i class="bi bi-send me-1"></i> <?= ($old['status'] ?? 'published') === 'draft' ? 'Publish Event' : 'Update & Keep Published' ?>
                            </button>
                            <button type="submit" name="save_draft" value="1" class="btn btn-outline-secondary">
                                <i class="bi bi-file-earmark-text me-1"></i> Save as Draft
                            </button>
                            <a href="<?= BASE_URL ?>events/index.php" class="btn btn-link text-muted ms-auto">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
