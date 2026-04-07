<?php
/**
 * Database Setup Script
 * Run this once to create the database and tables.
 * 
 * Visit: http://localhost:8080/setup.php
 * Delete this file after setup is complete.
 */
session_start();

$success = false;
$error = '';
$step = $_POST['step'] ?? 'credentials';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'install') {
    $host     = trim($_POST['db_host'] ?? 'localhost');
    $username = trim($_POST['db_user'] ?? 'root');
    $password = $_POST['db_pass'] ?? '';
    
    try {
        // Connect without database first
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Read and execute schema
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found: $schemaFile");
        }
        
        $schema = file_get_contents($schemaFile);
        
        // Split by semicolons and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            fn($s) => !empty($s) && !str_starts_with($s, '--')
        );
        
        foreach ($statements as $sql) {
            if (!empty(trim($sql))) {
                $pdo->exec($sql);
            }
        }
        
        // Update config/database.php with the correct credentials
        $configContent = "<?php\n";
        $configContent .= "/**\n * Database Configuration\n * Smart Reminder System\n */\n\n";
        $configContent .= "define('DB_HOST', '$host');\n";
        $configContent .= "define('DB_NAME', 'smart_reminder_db');\n";
        $configContent .= "define('DB_USER', '$username');\n";
        $configContent .= "define('DB_PASS', '$password');\n";
        $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
        $configContent .= "function getDBConnection() {\n";
        $configContent .= "    static \$pdo = null;\n";
        $configContent .= "    if (\$pdo === null) {\n";
        $configContent .= "        \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;\n";
        $configContent .= "        \$options = [\n";
        $configContent .= "            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n";
        $configContent .= "            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
        $configContent .= "            PDO::ATTR_EMULATE_PREPARES   => false,\n";
        $configContent .= "        ];\n";
        $configContent .= "        try {\n";
        $configContent .= "            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);\n";
        $configContent .= "        } catch (PDOException \$e) {\n";
        $configContent .= "            error_log(\"Database connection failed: \" . \$e->getMessage());\n";
        $configContent .= "            die(\"Database connection failed. Please check your configuration.\");\n";
        $configContent .= "        }\n";
        $configContent .= "    }\n";
        $configContent .= "    return \$pdo;\n";
        $configContent .= "}\n";
        
        file_put_contents(__DIR__ . '/config/database.php', $configContent);
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — CampusRemind</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px">
        <div class="auth-header">
            <div class="mb-2" style="font-size:2rem;">⚙️</div>
            <h2>Database Setup</h2>
            <p>Configure your MySQL connection to get started</p>
        </div>
        
        <div class="auth-body">
            <?php if ($success): ?>
                <div class="text-center">
                    <div class="empty-state-icon mx-auto mb-3" style="background:#f0fdf4">
                        <i class="bi bi-check-circle-fill" style="color:#22c55e"></i>
                    </div>
                    <h5 class="fw-bold text-success">Setup Complete!</h5>
                    <p class="text-muted mb-3">Database has been created and configured successfully.</p>
                    
                    <div class="p-3 mb-4" style="background:#f8f9ff;border-radius:var(--radius-sm);border:1px dashed var(--border-color)">
                        <p class="mb-1 fw-semibold" style="font-size:.85rem;color:var(--text-secondary)">Default Admin Login:</p>
                        <p class="mb-0" style="font-size:.85rem">
                            <strong>Email:</strong> admin@campus.edu<br>
                            <strong>Password:</strong> Admin@123
                        </p>
                    </div>
                    
                    <div class="alert alert-warning" style="font-size:.85rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Important:</strong> Delete <code>setup.php</code> for security.
                    </div>
                    
                    <a href="auth/login.php" class="btn btn-primary-gradient w-100 py-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Go to Login
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info" style="font-size:.85rem">
                    <i class="bi bi-info-circle me-1"></i>
                    This will create the <code>smart_reminder_db</code> database with all required tables.
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="step" value="install">
                    
                    <div class="mb-3">
                        <label for="db_host" class="form-label fw-semibold">MySQL Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" 
                               value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label fw-semibold">MySQL Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" 
                               value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label for="db_pass" class="form-label fw-semibold">MySQL Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" 
                               placeholder="Leave empty if no password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary-gradient w-100 py-3">
                        <i class="bi bi-database-gear me-2"></i> Install Database
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="auth-footer">
            <span class="text-muted" style="font-size:.8rem">CampusRemind — Smart Reminder System</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
