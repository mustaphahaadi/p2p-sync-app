<?php
/**
 * Entry point — redirect to dashboard or login
 */
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('auth/login.php');
}
