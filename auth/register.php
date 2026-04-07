<?php
/**
 * User Registration
 */
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => '', 'department' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }
    
    $name       = sanitize($_POST['name'] ?? '');
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $department = sanitize($_POST['department'] ?? '');
    $role       = in_array($_POST['role'] ?? '', ['student', 'lecturer']) ? $_POST['role'] : 'student';
    
    $old = compact('name', 'email', 'department', 'role');
    
    // Validation
    if (empty($name))       $errors[] = 'Full name is required.';
    if (strlen($name) > 100) $errors[] = 'Name must be 100 characters or less.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($department)) $errors[] = 'Department is required.';
    
    // Check duplicate email
    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        }
    }
    
    // Create account
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, department, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $department, $role]);
        
        setFlash('success', 'Account created successfully! Please log in.');
        redirect('auth/login.php');
    }
}

$pageTitle = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — CampusRemind</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Header -->
        <div class="auth-header">
            <div class="mb-2" style="font-size:2rem;">📅</div>
            <h2>Create Account</h2>
            <p>Join CampusRemind and never miss an event</p>
        </div>
        
        <!-- Body -->
        <div class="auth-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" 
                           placeholder="Full Name" value="<?= sanitize($old['name']) ?>" required>
                    <label for="name"><i class="bi bi-person me-1"></i> Full Name</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Email" value="<?= sanitize($old['email']) ?>" required>
                    <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="department" class="form-label small text-muted fw-semibold">Department</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">Select department</option>
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
                    <div class="col-md-6">
                        <label for="role" class="form-label small text-muted fw-semibold">I am a</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="student" <?= $old['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="lecturer" <?= $old['role'] === 'lecturer' ? 'selected' : '' ?>>Lecturer</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required minlength="6">
                    <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm Password" required>
                    <label for="confirm_password"><i class="bi bi-lock-fill me-1"></i> Confirm Password</label>
                </div>
                
                <button type="submit" class="btn btn-primary-solid w-100 py-3">
                    <i class="bi bi-person-plus me-2"></i> Create Account
                </button>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="auth-footer">
            <span class="text-muted">Already have an account?</span>
            <a href="<?= BASE_URL ?>auth/login.php" class="fw-semibold" style="color:var(--primary)">Sign In</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
