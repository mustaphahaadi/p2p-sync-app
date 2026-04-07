<?php
/**
 * Admin — User Management
 */
$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$pdo = getDBConnection();

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid form submission.');
        redirect('admin/users.php');
    }
    
    if ($_POST['action'] === 'change_role') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? '';
        
        if ($targetUserId && in_array($newRole, ['student', 'lecturer', 'admin'])) {
            // Prevent self-demotion
            if ($targetUserId === (int) $_SESSION['user_id'] && $newRole !== 'admin') {
                setFlash('warning', 'You cannot change your own role.');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $targetUserId]);
                setFlash('success', 'User role updated successfully.');
            }
        }
        redirect('admin/users.php');
    }
    
    if ($_POST['action'] === 'delete_user') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        
        if ($targetUserId === (int) $_SESSION['user_id']) {
            setFlash('warning', 'You cannot delete your own account.');
        } elseif ($targetUserId) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            setFlash('success', 'User has been removed.');
        }
        redirect('admin/users.php');
    }
}

// Search
$search = sanitize($_GET['search'] ?? '');
$filterRole = $_GET['role'] ?? '';

$sql = "SELECT id, name, email, department, role, created_at FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterRole) {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Stats
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$studentCount  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$lecturerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='lecturer'")->fetchColumn();
$adminCount    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
?>

<div class="page-content">
    <div class="page-header fade-in">
        <h1><i class="bi bi-people me-2" style="color:var(--primary)"></i> User Management</h1>
        <p>View and manage registered users</p>
    </div>
    
    <!-- Stats -->
    <div class="row g-3 mb-4 fade-in">
        <div class="col-md-3 col-6">
            <div class="stat-card stat-events">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-events"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-value"><?= $totalUsers ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card stat-upcoming">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-upcoming"><i class="bi bi-mortarboard-fill"></i></div>
                    <div>
                        <div class="stat-value"><?= $studentCount ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card stat-notifications">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-notifications"><i class="bi bi-person-workspace"></i></div>
                    <div>
                        <div class="stat-value"><?= $lecturerCount ?></div>
                        <div class="stat-label">Lecturers</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card stat-users">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon icon-users"><i class="bi bi-shield-fill"></i></div>
                    <div>
                        <div class="stat-value"><?= $adminCount ?></div>
                        <div class="stat-label">Admins</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search -->
    <div class="content-card mb-4 fade-in">
        <div class="content-card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <div class="search-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control search-input" name="search"
                               placeholder="Search by name, email, department..." value="<?= $search ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="student" <?= $filterRole === 'student' ? 'selected' : '' ?>>Students</option>
                        <option value="lecturer" <?= $filterRole === 'lecturer' ? 'selected' : '' ?>>Lecturers</option>
                        <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-solid flex-grow-1">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="content-card fade-in">
        <div class="content-card-header">
            <h5>Registered Users (<?= count($users) ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width:32px;height:32px;font-size:.75rem">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <strong><?= sanitize($u['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><?= sanitize($u['department']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : ($u['role'] === 'lecturer' ? 'bg-info' : 'bg-primary') ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                            <td class="text-end">
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                        <li class="dropdown-header small text-muted">Change Role</li>
                                        <?php foreach (['student', 'lecturer', 'admin'] as $r): ?>
                                            <?php if ($r !== $u['role']): ?>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="new_role" value="<?= $r ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        Make <?= ucfirst($r) ?>
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
