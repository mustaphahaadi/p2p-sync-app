<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_dept'] = 'All';

// simulate the script essentially
ob_start();
include 'events/index.php';
$output = ob_get_clean();

echo "Length: " . strlen($output) . "\n";
if (strpos($output, "bi-calendar3") !== false) {
    echo "Found calendar icon (event details rendered)!\n";
} else {
    echo "DID NOT FIND EVENT DETAILS!\n";
}
