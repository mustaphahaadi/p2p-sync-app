<?php
/**
 * User Profile
 */
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name       = sanitize($_POST['name'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $phone      = sanitize($_POST['phone'] ?? '');
        
        if (empty($name)) $errors[] = 'Name is required.';
        if (empty($department)) $errors[] = 'Department is required.';
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, department = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $department, $phone, $userId]);
            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_dept'] = $department;
            
            setFlash('success', 'Profile updated successfully!');
            redirect('profile.php');
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
        
        if (empty($errors)) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            
            setFlash('success', 'Password changed successfully!');
            redirect('profile.php');
        }
    }
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

// User stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$userId]);
$notifCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadNotifCount = $stmt->fetchColumn();
?>

<div class="page-content">
    <div class="page-header fade-in">
        <h1><i class="bi bi-person-circle me-2" style="color:var(--primary)"></i> My Profile</h1>
        <p>Manage your account settings</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger fade-in">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= $e ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <!-- Profile Card -->
        <div class="col-lg-4 fade-in">
            <div class="content-card text-center">
                <div class="content-card-body py-4">
                    <div class="user-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= sanitize($user['name']) ?></h5>
                    <p class="text-muted mb-2" style="font-size:.9rem"><?= sanitize($user['email']) ?></p>
                    <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : ($user['role'] === 'lecturer' ? 'bg-info' : 'bg-primary') ?> mb-3">
                        <?= ucfirst($user['role']) ?>
                    </span>
                    
                    <hr>
                    
                    <div class="row text-center g-0">
                        <div class="col-6">
                            <div class="stat-value" style="font-size:1.25rem"><?= $notifCount ?></div>
                            <div class="stat-label">Notifications</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-value" style="font-size:1.25rem"><?= $unreadNotifCount ?></div>
                            <div class="stat-label">Unread</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <p class="text-muted mb-0" style="font-size:.8rem">
                        <i class="bi bi-building me-1"></i> <?= sanitize($user['department']) ?><br>
                        <?php if(!empty($user['phone'])): ?>
                        <i class="bi bi-telephone me-1"></i> <?= sanitize($user['phone']) ?><br>
                        <?php endif; ?>
                        <i class="bi bi-calendar me-1"></i> Joined <?= date('M d, Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Settings -->
        <div class="col-lg-8">
            <!-- Update Profile -->
            <div class="content-card mb-4 fade-in">
                <div class="content-card-header">
                    <h5><i class="bi bi-pencil me-2" style="color:var(--primary)"></i> Update Profile</h5>
                </div>
                <div class="content-card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= sanitize($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email_display" class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" id="email_display" 
                                       value="<?= sanitize($user['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="department" class="form-label fw-semibold">Department</label>
                                <select class="form-select" id="department" name="department" required>
                                    <?php foreach (['Computer Science','Engineering','Business','Arts & Humanities','Sciences','Education','Health Sciences','Law'] as $dept): ?>
                                        <option <?= $user['department'] === $dept ? 'selected' : '' ?>><?= $dept ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="e.g. +1234567890">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-solid">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="content-card fade-in">
                <div class="content-card-header">
                    <h5><i class="bi bi-shield-lock me-2" style="color:#ed8936"></i> Change Password</h5>
                </div>
                <div class="content-card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label fw-semibold">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary-custom">
                            <i class="bi bi-key me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
