<?php
/**
 * Delete Event (Admin only)
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$eventId = (int) ($_GET['id'] ?? 0);

if (!$eventId) {
    setFlash('danger', 'Invalid event.');
    redirect('events/index.php');
}

$pdo = getDBConnection();

// Verify event exists
$stmt = $pdo->prepare("SELECT id, title FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found.');
    redirect('events/index.php');
}

// Delete related notifications first (cascade should handle this, but just in case)
$stmt = $pdo->prepare("DELETE FROM notifications WHERE event_id = ?");
$stmt->execute([$eventId]);

// Delete event
$stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
$stmt->execute([$eventId]);

setFlash('success', 'Event "' . sanitize($event['title']) . '" has been deleted.');
redirect('events/index.php');
