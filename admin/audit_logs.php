<?php
/**
 * System Audit Logs
 */
$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getDBConnection();

// Fetch logs with user data
$query = "
    SELECT a.*, u.name as user_name 
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
";
$stmt = $pdo->query($query);
$logs = $stmt->fetchAll();
?>

<div class="page-content fade-in">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-shield-lock me-2" style="color:var(--primary)"></i> System Audit Logs</h1>
            <p>Recent administrative actions</p>
        </div>
    </div>
    
    <div class="content-card">
        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0">Timestamp</th>
                            <th class="border-0">Admin User</th>
                            <th class="border-0">Action</th>
                            <th class="border-0">Entity</th>
                            <th class="pe-3 border-0">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                                    No audit logs found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap text-muted" style="font-size:.85rem">
                                        <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="fw-medium">
                                        <?= sanitize($log['user_name'] ?? 'System / Unknown') ?>
                                    </td>
                                    <td>
                                        <?php
                                            $actionClass = 'bg-secondary';
                                            if ($log['action'] == 'created') $actionClass = 'bg-success';
                                            if ($log['action'] == 'updated') $actionClass = 'bg-primary';
                                            if ($log['action'] == 'deleted') $actionClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $actionClass ?>"><?= strtoupper(sanitize($log['action'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= sanitize($log['entity_type']) ?> #<?= sanitize($log['entity_id'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="pe-3 text-muted" style="font-size:.85rem;">
                                        <?= sanitize($log['details']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
