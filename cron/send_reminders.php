<?php
/**
 * Cron Job: Send Event Reminders
 * 
 * This script should be run daily via cron.
 * 
 * Cron entry (ideal for minute-level granularity):
 *   * * * * * /usr/bin/php /path/to/your/project/cron/send_reminders.php >> /path/to/your/project/cron/cron.log 2>&1
 * 
 * Logic:
 *   For each published upcoming event, calculate exact Trigger Date = event_date + event_time - reminder intervals.
 *   If NOW() >= Trigger Date, send email & SMS placeholders. Only one reminder is sent per user per event.
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    die('Access denied. This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/sms.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting reminder check...\n";

$pdo = getDBConnection();

// Find events where current time >= (event_date + event_time) - interval
$stmt = $pdo->query("
    SELECT * FROM events 
    WHERE status = 'published' 
    AND event_date >= CURDATE()
    AND (
        (reminder_unit = 'days' AND DATE_SUB(CAST(CONCAT(event_date, ' ', IFNULL(event_time, '00:00:00')) AS DATETIME), INTERVAL reminder_time DAY) <= NOW()) OR
        (reminder_unit = 'hours' AND DATE_SUB(CAST(CONCAT(event_date, ' ', IFNULL(event_time, '00:00:00')) AS DATETIME), INTERVAL reminder_time HOUR) <= NOW()) OR
        (reminder_unit = 'minutes' AND DATE_SUB(CAST(CONCAT(event_date, ' ', IFNULL(event_time, '00:00:00')) AS DATETIME), INTERVAL reminder_time MINUTE) <= NOW())
    )
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
        $userStmt = $pdo->query("SELECT id, name, email, phone FROM users");
    } else {
        $userStmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE department = ? OR role = 'admin'");
        $userStmt->execute([$event['department']]);
    }
    $users = $userStmt->fetchAll();
    
    echo "  Target users: " . count($users) . "\n";
    
    foreach ($users as $user) {
        // Check if notification already sent for THIS EVENT completely (not just today)
        $checkStmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND event_id = ?
        ");
        $checkStmt->execute([$user['id'], $event['id']]);
        
        if ($checkStmt->fetch()) {
            echo "  [SKIP] Already notified: {$user['email']}\n";
            continue;
        }
        
        // Build message
        $unitStr = $event['reminder_unit'];
        $timeVal = $event['reminder_time'];
        if ($timeVal == 1 && substr($unitStr, -1) === 's') {
            $unitStr = substr($unitStr, 0, -1);
        }
        
        $message = "Reminder: \"{$event['title']}\" is coming up in {$timeVal} {$unitStr} on " . date('M d, Y', strtotime($event['event_date'])) . ".";
        
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
            $event['end_date'] ?? null,
            $event['event_time'],
            $event['description'] ?? '',
            $event['department']
        );
        
        $subject = "📅 Reminder: {$event['title']} — " . date('M d, Y', strtotime($event['event_date']));
        
        $sentParams = 0;
        
        // Send SMS if phone exists
        if (!empty($user['phone'])) {
            $smsSent = sendSMS($user['phone'], "CAMPUS ALERT: " . $message);
            if ($smsSent) {
                echo "  [OK] SMS sent to: {$user['phone']}\n";
                $sentParams++;
            }
        }
        
        $sentEmail = sendEmail($user['email'], $subject, $emailBody);
        
        if ($sentEmail) {
            echo "  [OK] Email sent to: {$user['email']}\n";
            $sentParams++;
        }
        
        if ($sentParams > 0) {
            $totalSent++;
        } else {
            echo "  [FAIL] Failed to notify: {$user['email']}\n";
            $totalFailed++;
        }
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Done! Successfully Notified: {$totalSent}, Failed: {$totalFailed}\n";
