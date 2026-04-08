<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM events");
$events = $stmt->fetchAll();

foreach ($events as $event) {
    try {
        if (!empty($event['end_date']) && $event['end_date'] !== $event['event_date']) {
            $sFmt = date('Y') == date('Y', strtotime($event['event_date'])) ? 'M d' : 'M d, Y';
            $x = date($sFmt, strtotime($event['event_date'])) . ' - ' . date('M d, Y', strtotime($event['end_date']));
        }
        $y = mb_strimwidth($event['description'] ?? '', 0, 120, '...');
        $z = sanitize($y);
        echo "Event {$event['id']} OK\n";
    } catch (\Throwable $t) {
        echo "Error on event {$event['id']}: " . $t->getMessage() . "\n";
    }
}
