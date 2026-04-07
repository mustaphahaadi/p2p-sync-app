<?php
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();
$unreadCount = isLoggedIn() ? getUnreadCount($_SESSION['user_id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Determine parent directory for nested pages
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Reminder System for Campus Activities — Stay updated on academic events, exams, and deadlines.">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?>CampusRemind</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>

<!-- Mobile sidebar toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
</button>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ====== Sidebar ====== -->
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= BASE_URL ?>dashboard.php">
        <span class="sidebar-brand-icon"><i class="bi bi-bell-fill"></i></span>
        <span class="sidebar-brand-text">CampusRemind</span>
    </a>

    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <div class="sidebar-section-label">Main</div>
            <a class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>dashboard.php">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
            <a class="sidebar-link <?= ($currentPage === 'index' && $currentDir === 'events') || $currentPage === 'events' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>events/index.php">
                <i class="bi bi-calendar-event"></i>
                <span>Events</span>
            </a>
            <a class="sidebar-link <?= $currentPage === 'notifications' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>notifications.php">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-label">Admin</div>
            <a class="sidebar-link <?= $currentPage === 'users' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>admin/users.php">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
            <a class="sidebar-link <?= ($currentPage === 'create' && $currentDir === 'events') ? 'active' : '' ?>"
               href="<?= BASE_URL ?>events/create.php">
                <i class="bi bi-plus-circle"></i>
                <span>Add Event</span>
            </a>
        </div>
        <?php endif; ?>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Account</div>
            <a class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>profile.php">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a class="sidebar-link" href="<?= BASE_URL ?>auth/logout.php" style="color: var(--rose);">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a class="sidebar-user" href="<?= BASE_URL ?>profile.php">
            <div class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= sanitize($currentUser['name'] ?? 'User') ?></div>
                <div class="sidebar-user-role"><?= sanitize($currentUser['role'] ?? '') ?> · <?= sanitize($currentUser['department'] ?? '') ?></div>
            </div>
        </a>
    </div>
</aside>

<!-- ====== Main Content Wrapper ====== -->
<div class="main-content">

<?php endif; ?>

<!-- Flash Messages -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="<?= isLoggedIn() ? 'page-content' : 'container mt-3' ?>" style="padding-bottom:0;">
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
