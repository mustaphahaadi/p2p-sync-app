<?php
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();
$unreadCount = isLoggedIn() ? getUnreadCount($_SESSION['user_id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Reminder System for Campus Activities — Stay updated on academic events, exams, and deadlines.">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?>Campus Reminder</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- ====== Navbar ====== -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand navbar-brand-custom" href="<?= BASE_URL ?>dashboard.php">
            <span class="navbar-brand-icon"><i class="bi bi-bell-fill"></i></span>
            CampusRemind
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>dashboard.php">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $currentPage === 'events' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>events/index.php">
                        <i class="bi bi-calendar-event me-1"></i> Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $currentPage === 'notifications' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>notifications.php">
                        <i class="bi bi-bell me-1"></i> Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $currentPage === 'users' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>admin/users.php">
                        <i class="bi bi-people me-1"></i> Users
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                       role="button" data-bs-toggle="dropdown">
                        <span class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></span>
                        <span class="d-none d-md-inline" style="font-weight:600;font-size:.9rem;color:var(--text-primary)">
                            <?= sanitize($currentUser['name'] ?? 'User') ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="border-radius:var(--radius-md)">
                        <li>
                            <div class="dropdown-header">
                                <small class="text-muted"><?= sanitize($currentUser['role'] ?? '') ?> &middot; <?= sanitize($currentUser['department'] ?? '') ?></small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>profile.php">
                                <i class="bi bi-person me-2"></i> Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Flash Messages -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
