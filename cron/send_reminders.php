<?php
/**
 * Cron Job: Send Event Reminders
 * 
 * This script should be run daily via cron.
 * 
 * Cron entry (runs every day at 7:00 AM):
 *   0 7 * * * /usr/bin/php /path/to/your/project/cron/send_reminders.php >> /path/to/your/project/cron/cron.log 2>&1
 * 
 * Logic:
 *   For each upcoming event, if today == event_date - reminder_days,
 *   send a reminder email to all matching users and create a notification record.
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    die('Access denied. This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting reminder check...\n";

$pdo = getDBConnection();

// Find events where today == event_date - reminder_days
// i.e., event_date = CURDATE() + reminder_days
$stmt = $pdo->query("
    SELECT * FROM events 
    WHERE event_date >= CURDATE()
    AND DATEDIFF(event_date, CURDATE()) = reminder_days
");
$events = $stmt->fetchAll();

if (empty($events)) {
    echo "[" . date('Y-m-d H:i:s') . "] No reminders to send today.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($events) . " event(s) to send reminders for.\n";

$totalSent = 0;
$totalFailed = 0;

foreach ($events as $event) {
    echo "\n--- Event: {$event['title']} (Date: {$event['event_date']}, Dept: {$event['department']}) ---\n";
    
    // Find target users
    // If department is 'All', notify everyone; otherwise, notify matching department users
    if ($event['department'] === 'All') {
        $userStmt = $pdo->query("SELECT id, name, email FROM users");
    } else {
        $userStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE department = ? OR role = 'admin'");
        $userStmt->execute([$event['department']]);
    }
    $users = $userStmt->fetchAll();
    
    echo "  Target users: " . count($users) . "\n";
    
    foreach ($users as $user) {
        // Check if notification already sent (prevent duplicates)
        $checkStmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND event_id = ? AND DATE(sent_at) = CURDATE()
        ");
        $checkStmt->execute([$user['id'], $event['id']]);
        
        if ($checkStmt->fetch()) {
            echo "  [SKIP] Already notified: {$user['email']}\n";
            continue;
        }
        
        // Build message
        $daysLeft = $event['reminder_days'];
        $message = "Reminder: \"{$event['title']}\" is coming up in {$daysLeft} day" . ($daysLeft > 1 ? 's' : '') . " on " . date('M d, Y', strtotime($event['event_date'])) . ".";
        
        // Create notification record
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, event_id, message, type, is_read) 
            VALUES (?, ?, ?, 'email', 0)
        ");
        $notifStmt->execute([$user['id'], $event['id'], $message]);
        
        // Send email
        $emailBody = buildReminderEmail(
            $user['name'],
            $event['title'],
            $event['event_date'],
            $event['event_time'],
            $event['description'] ?? '',
            $event['department']
        );
        
        $subject = "📅 Reminder: {$event['title']} — " . date('M d, Y', strtotime($event['event_date']));
        
        $sent = sendEmail($user['email'], $subject, $emailBody);
        
        if ($sent) {
            echo "  [OK] Email sent to: {$user['email']}\n";
            $totalSent++;
        } else {
            echo "  [FAIL] Email failed for: {$user['email']}\n";
            $totalFailed++;
        }
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Done! Sent: {$totalSent}, Failed: {$totalFailed}\n";
