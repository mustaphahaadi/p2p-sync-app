<?php
/**
 * User Login
 */
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }
    
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;
    
    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required.';
    }
    
    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, password, department, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_dept'] = $user['department'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            setFlash('success', 'Welcome back, ' . sanitize($user['name']) . '!');
            redirect('dashboard.php');
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Sign In';

// Get flash for display
$flash = getFlash();
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
            <div class="mb-2" style="font-size:2rem;">🔐</div>
            <h2>Welcome Back</h2>
            <p>Sign in to your CampusRemind account</p>
        </div>
        
        <!-- Body -->
        <div class="auth-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>
            
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
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Email" value="<?= sanitize($oldEmail) ?>" required autofocus>
                    <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
                </div>
                
                <button type="submit" class="btn btn-primary-solid w-100 py-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                </button>
            </form>
            
            <!-- Demo credentials hint -->
            <div class="mt-4 p-3" style="background:#f8f9ff;border-radius:var(--radius-sm);border:1px dashed var(--border-color)">
                <p class="mb-1 fw-semibold" style="font-size:.8rem;color:var(--text-muted)">
                    <i class="bi bi-info-circle me-1"></i> Default Admin Login
                </p>
                <p class="mb-0" style="font-size:.82rem;color:var(--text-secondary)">
                    <strong>Email:</strong> admin@campus.edu<br>
                    <strong>Password:</strong> Admin@123
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="auth-footer">
            <span class="text-muted">Don't have an account?</span>
            <a href="<?= BASE_URL ?>auth/register.php" class="fw-semibold" style="color:var(--primary)">Create one</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
