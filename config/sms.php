<?php
/**
 * SMS Configuration & Helper
 * 
 * In a production environment, this file would connect to an SMS provider's API
 * (like Twilio, Vonage, Hubtel, etc.). For now, it logs SMS messages locally
 * for debugging and development.
 */

function sendSMS($phoneNumber, $message) {
    if (empty($phoneNumber)) {
        return false;
    }

    // Prepare log details
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf(
        "[%s] SMS TO: %s | MSG: %s\n",
        $timestamp,
        $phoneNumber,
        $message
    );

    // Write to a local log file inside the cron/logs or root logs directory
    $logDir = __DIR__ . '/../cron/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/sms.log';
    
    // Attempt to write the log
    if (file_put_contents($logFile, $logEntry, FILE_APPEND) !== false) {
        return true;
    }
    
    return false;
}
