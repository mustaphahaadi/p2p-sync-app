<?php
/**
 * Common Functions & Session Bootstrap
 * Smart Reminder System
 */

session_start();

require_once __DIR__ . '/../config/database.php';

// ---- Base URL Helper ----
define('BASE_URL', '/');

/**
 * Redirect helper
 */
function redirect($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}

/**
 * Flash message helper
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login — redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access this page.');
        redirect('auth/login.php');
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Access denied. Admin privileges required.');
        redirect('dashboard.php');
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, department, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get unread notification count
 */
function getUnreadCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

/**
 * CSRF token generation & validation
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get category badge class
 */
function getCategoryBadge($category) {
    $badges = [
        'lecture'      => 'bg-primary',
        'exam'         => 'bg-danger',
        'registration' => 'bg-success',
        'seminar'      => 'bg-info',
        'workshop'     => 'bg-warning text-dark',
        'deadline'     => 'bg-dark',
        'other'        => 'bg-secondary',
    ];
    return $badges[$category] ?? 'bg-secondary';
}

/**
 * Get category icon
 */
function getCategoryIcon($category) {
    $icons = [
        'lecture'      => 'bi-book',
        'exam'         => 'bi-pencil-square',
        'registration' => 'bi-person-plus',
        'seminar'      => 'bi-people',
        'workshop'     => 'bi-tools',
        'deadline'     => 'bi-alarm',
        'other'        => 'bi-calendar-event',
    ];
    return $icons[$category] ?? 'bi-calendar-event';
}

/**
 * Time-ago helper
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
