<?php
/**
 * Mail Configuration
 * Smart Reminder System
 * 
 * Configure your SMTP settings here.
 * For development, you can use Mailtrap or similar services.
 */

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@campus.edu');
define('MAIL_FROM_NAME', 'Campus Reminder System');
define('MAIL_ENCRYPTION', 'tls');

/**
 * Send email using PHP mail() function
 * For production, integrate PHPMailer or similar library
 */
function sendEmail($to, $subject, $htmlBody) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $htmlBody, $headers);
}

/**
 * Build a styled HTML email template
 */
function buildReminderEmail($userName, $eventTitle, $eventDate, $endDate, $eventTime, $eventDescription, $department) {
    $sFmt = date('Y') == date('Y', strtotime($eventDate)) ? 'l, F j' : 'l, F j, Y';
    $formattedDate = date('l, F j, Y', strtotime($eventDate));
    if (!empty($endDate) && $endDate !== $eventDate) {
        $formattedDate = date($sFmt, strtotime($eventDate)) . ' to ' . date('l, F j, Y', strtotime($endDate));
    }
    
    $formattedTime = $eventTime ? date('g:i A', strtotime($eventTime)) : 'TBA';
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin:0;padding:0;background-color:#f0f2f5;font-family:Segoe UI,Roboto,Helvetica Neue,Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5;padding:40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                        <!-- Header -->
                        <tr>
                            <td style="background:var(--primary);padding:32px 40px;text-align:center;">
                                <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:600;">📅 Event Reminder</h1>
                                <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;">Campus Academic Calendar</p>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style="padding:32px 40px;">
                                <p style="color:#1a1a2e;font-size:16px;margin:0 0 20px;">Hello <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                                <p style="color:#4a4a68;font-size:15px;line-height:1.6;margin:0 0 24px;">This is a reminder about an upcoming academic event:</p>
                                
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9ff;border-radius:8px;border-left:4px solid #667eea;padding:20px;margin:0 0 24px;">
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            <h2 style="color:#1a1a2e;margin:0 0 12px;font-size:20px;">' . htmlspecialchars($eventTitle) . '</h2>
                                            <p style="color:#4a4a68;margin:0 0 8px;font-size:14px;">📆 <strong>Date:</strong> ' . $formattedDate . '</p>
                                            <p style="color:#4a4a68;margin:0 0 8px;font-size:14px;">🕐 <strong>Time:</strong> ' . $formattedTime . '</p>
                                            <p style="color:#4a4a68;margin:0 0 8px;font-size:14px;">🏛️ <strong>Department:</strong> ' . htmlspecialchars($department) . '</p>
                                            <p style="color:#4a4a68;margin:12px 0 0;font-size:14px;line-height:1.5;">' . htmlspecialchars($eventDescription) . '</p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="color:#4a4a68;font-size:14px;line-height:1.6;margin:0;">Please make sure to prepare accordingly. Log in to your dashboard for more details.</p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="background-color:#f8f9ff;padding:20px 40px;text-align:center;border-top:1px solid #e8e8f0;">
                                <p style="color:#8888a0;font-size:12px;margin:0;">Smart Reminder System &copy; ' . date('Y') . ' | Campus Academic Calendar</p>
                                <p style="color:#8888a0;font-size:11px;margin:6px 0 0;">This is an automated notification. Please do not reply.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}
