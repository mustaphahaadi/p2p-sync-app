<?php
/**
 * Interactive Calendar View
 * Smart Reminder System
 */
$pageTitle = 'Academic Calendar';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pdo = getDBConnection();

// Fetch events visible to the user
$where = [];
$params = [];
$userDept = $_SESSION['user_dept'] ?? 'All';

if (!isAdmin()) {
    $where[] = "(department = ? OR department = 'All')";
    $params[] = $userDept;
}

$sql = "SELECT id, title, description, event_date, end_date, category, department FROM events";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Format for FullCalendar
$fcEvents = [];
foreach ($events as $e) {
    // Determine colors based on category
    $color = '#4f46e5'; // default primary
    switch ($e['category']) {
        case 'lecture': $color = '#3b82f6'; break;
        case 'exam': $color = '#ef4444'; break;
        case 'registration': $color = '#10b981'; break;
        case 'deadline': $color = '#f59e0b'; break;
        case 'seminar': $color = '#8b5cf6'; break;
        case 'workshop': $color = '#06b6d4'; break;
    }

    // FullCalendar end date is exclusive. So if an event is multi-day inclusive, we must add 1 day to end_date.
    $end = null;
    if (!empty($e['end_date']) && $e['end_date'] !== $e['event_date']) {
        $end = date('Y-m-d', strtotime($e['end_date'] . ' +1 day'));
    }

    $fcEvents[] = [
        'id' => $e['id'],
        'title' => $e['title'],
        'start' => $e['event_date'],
        'end' => $end,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'description' => mb_strimwidth($e['description'] ?? '', 0, 100, '...'),
            'category' => ucfirst($e['category']),
            'department' => $e['department']
        ]
    ];
}
?>

<!-- Load FullCalendar -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<div class="page-content">
    <div class="page-header fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= BASE_URL ?>events/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h1><i class="bi bi-calendar-range me-2" style="color:var(--primary)"></i> Academic Calendar</h1>
                    <p>Timeline of campus events and activities</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>events/index.php" class="btn btn-outline-primary-custom">
                    <i class="bi bi-list-ul me-1"></i> List View
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>events/create.php" class="btn btn-primary-solid">
                    <i class="bi bi-plus-lg me-1"></i> Add Event
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="content-card fade-in">
        <div class="content-card-body">
            <div id="calendar" style="min-height: 600px;"></div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Event Title</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2"><i class="bi bi-calendar me-2 text-muted"></i> <span id="modalDate" class="fw-semibold"></span></p>
        <p class="mb-2"><i class="bi bi-tag me-2 text-muted"></i> <span id="modalCategory" class="badge bg-secondary"></span></p>
        <p class="mb-3"><i class="bi bi-building me-2 text-muted"></i> <span id="modalDept"></span></p>
        
        <hr>
        <p id="modalDesc" class="text-muted" style="font-size:0.9rem; white-space:pre-wrap;"></p>
      </div>
      <div class="modal-footer" id="modalFooter">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var eventsData = <?= json_encode($fcEvents) ?>;
        var isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
        var baseUrl = '<?= BASE_URL ?>';
        
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'title',
                center: '',
                right: 'prev,next today dayGridMonth,timeGridWeek'
            },
            themeSystem: 'bootstrap5',
            events: eventsData,
            height: 'auto',
            eventClick: function(info) {
                var props = info.event.extendedProps;
                
                document.getElementById('modalTitle').textContent = info.event.title;
                
                var dateStr = info.event.start.toLocaleDateString();
                if (info.event.end && info.event.end > info.event.start) {
                    var realEnd = new Date(info.event.end);
                    // Subtract 1 day because FullCalendar makes end exclusive
                    realEnd.setDate(realEnd.getDate() - 1);
                    if (realEnd.toLocaleDateString() !== dateStr) {
                         dateStr += ' - ' + realEnd.toLocaleDateString();
                    }
                }
                
                document.getElementById('modalDate').textContent = dateStr;
                document.getElementById('modalCategory').textContent = props.category;
                document.getElementById('modalDept').textContent = props.department;
                document.getElementById('modalDesc').textContent = props.description || 'No description provided.';
                
                var footer = document.getElementById('modalFooter');
                if (isAdmin) {
                    footer.innerHTML = `
                        <a href="${baseUrl}events/edit.php?id=${info.event.id}" class="btn btn-primary-solid"><i class="bi bi-pencil me-1"></i> Edit Event</a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    `;
                }
                
                var modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            }
        });
        
        calendar.render();
    });
</script>

<style>
/* FullCalendar customization */
.fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
}
.fc-button-primary {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
}
.fc-button-primary:hover {
    background-color: var(--primary-hover) !important;
    border-color: var(--primary-hover) !important;
}
.fc-event {
    cursor: pointer;
    border-radius: 4px;
    padding: 2px 4px;
    font-size: 0.8rem;
    font-weight: 500;
}
.fc-day-today {
    background-color: var(--primary-light) !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
