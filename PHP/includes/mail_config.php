<?php
/**
 * PHPMailer Configuration and Mail Helper
 * Project: ONE PIECE Password Recovery Flow
 */

// Autoload Composer dependencies (PHPMailer)
$autoloader_paths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php'
];

$autoloader_loaded = false;
foreach ($autoloader_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloader_loaded = true;
        break;
    }
}

if (!$autoloader_loaded) {
    error_log("PHPMailer autoloader could not be found. Please ensure 'composer install' was run in the project root.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration
// EDIT THESE SETTINGS WITH YOUR ACTIVE SMTP CREDENTIALS
// Note: For Gmail, use an App Password generated at: https://myaccount.google.com/apppasswords
if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // 'tls' (port 587) or 'ssl' (port 465)
if (!defined('SMTP_AUTH')) define('SMTP_AUTH', true);
if (!defined('SMTP_USER')) define('SMTP_USER', getenv('SMTP_USER') ?: 'jamesrel.galagate@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('SMTP_PASS') ?: 'znmfgsezgfukgmyn');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'jamesrel.galagate@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'ONE PIECE Support');

/**
 * Configure and get a ready-to-use PHPMailer instance
 * @return PHPMailer
 */
function get_mailer_instance() {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        
        if (strtolower(SMTP_SECURE) === 'ssl' || SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Port       = (int)SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Set default sender
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    } catch (Exception $e) {
        error_log("PHPMailer initialization error: " . $e->getMessage());
    }

    return $mail;
}

/**
 * Send 6-digit OTP verification email for Password Reset
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name / username
 * @param string|int $otp The 6-digit OTP code
 * @param int $expires_minutes Expiration duration in minutes (default 10)
 * @return array ['success' => bool, 'message' => string]
 */
function send_otp_email($to_email, $to_name, $otp, $expires_minutes = 10) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'message' => 'PHPMailer library is not available. Please check composer installation.'
        ];
    }

    $mail = get_mailer_instance();

    try {
        // Recipients
        $mail->addAddress($to_email, $to_name ?: 'Valued User');

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Password Reset OTP Code - ONE PIECE";

        $escaped_name = htmlspecialchars($to_name ?: 'User');
        $escaped_otp = htmlspecialchars($otp);

        // Responsive Branded HTML Body
        $html_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset OTP</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #0c0f14;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    color: #f8fafc;
                }
                .wrapper {
                    width: 100%;
                    table-layout: fixed;
                    background-color: #0c0f14;
                    padding: 30px 0;
                }
                .main-table {
                    background-color: #12161f;
                    margin: 0 auto;
                    width: 100%;
                    max-width: 580px;
                    border-radius: 14px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    overflow: hidden;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
                }
                .header-bar {
                    background: linear-gradient(135deg, #181d28 0%, #12161f 100%);
                    padding: 26px 30px;
                    text-align: center;
                    border-bottom: 2px solid #f59e0b;
                }
                .logo-text {
                    font-size: 26px;
                    font-weight: 900;
                    letter-spacing: 1.5px;
                    color: #ffffff;
                    text-transform: uppercase;
                }
                .logo-parts {
                    color: #f59e0b;
                    margin-left: 2px;
                }
                .content-body {
                    padding: 32px 30px;
                    color: #cbd5e1;
                    font-size: 15px;
                    line-height: 1.65;
                }
                .greeting {
                    font-size: 18px;
                    font-weight: 700;
                    color: #ffffff;
                    margin-bottom: 12px;
                }
                .otp-card {
                    background: rgba(245, 158, 11, 0.07);
                    border: 2px dashed #f59e0b;
                    border-radius: 12px;
                    padding: 22px 20px;
                    text-align: center;
                    margin: 26px 0;
                }
                .otp-label {
                    font-size: 12px;
                    font-weight: 700;
                    letter-spacing: 1.5px;
                    text-transform: uppercase;
                    color: #fbbf24;
                    margin-bottom: 8px;
                }
                .otp-number {
                    font-size: 38px;
                    font-weight: 900;
                    letter-spacing: 8px;
                    color: #f59e0b;
                    font-family: 'Courier New', monospace, sans-serif;
                }
                .expiry-pill {
                    display: inline-block;
                    margin-top: 10px;
                    background: rgba(245, 158, 11, 0.18);
                    color: #fbbf24;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 4px 12px;
                    border-radius: 20px;
                }
                .alert-note {
                    background: rgba(255, 255, 255, 0.03);
                    border-left: 3px solid #f59e0b;
                    padding: 12px 16px;
                    border-radius: 4px;
                    font-size: 13px;
                    color: #94a3b8;
                    margin-top: 20px;
                }
                .footer {
                    background-color: #0d1117;
                    padding: 20px 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #64748b;
                    border-top: 1px solid rgba(255, 255, 255, 0.05);
                }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <table class='main-table' align='center' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td class='header-bar'>
                            <div class='logo-text'>ONE<span class='logo-parts'>PIECE</span></div>
                        </td>
                    </tr>
                    <tr>
                        <td class='content-body'>
                            <div class='greeting'>Hello, {$escaped_name}!</div>
                            <p>We received a request to reset your password for your ONE PIECE account.</p>
                            <p>Please use the following 6-digit One-Time PIN (OTP) to verify your identity and complete your password reset:</p>
                            
                            <div class='otp-card'>
                                <div class='otp-label'>Your One-Time PIN (OTP)</div>
                                <div class='otp-number'>{$escaped_otp}</div>
                                <div class='expiry-pill'>⏱️ Valid for {$expires_minutes} minutes</div>
                            </div>
                            
                            <div class='alert-note'>
                                <strong>Security Notice:</strong> For your protection, never share this OTP with anyone. ONE PIECE support will never ask for your OTP. If you did not initiate this request, you can safely ignore this email.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class='footer'>
                            <p style='margin: 0 0 6px 0;'>&copy; " . date('Y') . " ONE PIECE System. All rights reserved.</p>
                            <p style='margin: 0;'>This is an automated system message. Please do not reply directly to this email.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";

        $mail->Body = $html_body;

        // Plain text version for non-HTML email clients
        $mail->AltBody = "Hello {$to_name},\n\n"
            . "Your ONE PIECE password reset verification code is: {$otp}\n\n"
            . "This code is valid for {$expires_minutes} minutes.\n\n"
            . "If you did not request a password reset, please ignore this message.\n\n"
            . "Best regards,\nONE PIECE Team";

        $mail->send();

        return [
            'success' => true,
            'message' => 'OTP has been sent to your registered email address.'
        ];
    } catch (Exception $e) {
        error_log("PHPMailer send failed: " . $mail->ErrorInfo);
        return [
            'success' => false,
            'message' => 'Mailer Error: ' . $mail->ErrorInfo
        ];
    }
}

/**
 * Send Super Admin Role Switch Confirmation Email via Gmail SMTP
 * 
 * @param string $to_email Recipient Inactive Super Admin email
 * @param string $to_name Recipient name
 * @param string $current_admin_name Current Active Super Admin name
 * @param string $current_admin_email Current Active Super Admin email
 * @param string $confirmation_url Full URL with 24-hour verification token
 * @param string $expires_display Formatted expiration timestamp or '24 hours'
 * @return array ['success' => bool, 'message' => string]
 */
function send_role_switch_confirmation_email($to_email, $to_name, $current_admin_name, $current_admin_email, $confirmation_url, $expires_display = '24 hours') {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'message' => 'PHPMailer library is not available. Please check composer installation.'
        ];
    }

    $mail = get_mailer_instance();

    try {
        // Recipients
        $mail->addAddress($to_email, $to_name ?: 'Super Administrator');

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Action Required: Super Admin Role Handover Request - ONE PIECE";

        $escaped_to_name = htmlspecialchars($to_name ?: 'Super Administrator');
        $escaped_from_name = htmlspecialchars($current_admin_name ?: 'Current Super Admin');
        $escaped_from_email = htmlspecialchars($current_admin_email ?: 'system');
        $escaped_url = htmlspecialchars($confirmation_url);

        $html_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Super Admin Role Handover Request</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #0c0f14;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    color: #f8fafc;
                }
                .wrapper {
                    width: 100%;
                    table-layout: fixed;
                    background-color: #0c0f14;
                    padding: 30px 0;
                }
                .main-table {
                    background-color: #12161f;
                    margin: 0 auto;
                    width: 100%;
                    max-width: 600px;
                    border-radius: 14px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    overflow: hidden;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
                }
                .header-bar {
                    background: linear-gradient(135deg, #181d28 0%, #12161f 100%);
                    padding: 26px 30px;
                    text-align: center;
                    border-bottom: 2px solid #ef4444;
                }
                .logo-text {
                    font-size: 24px;
                    font-weight: 900;
                    letter-spacing: 1.5px;
                    color: #ffffff;
                    text-transform: uppercase;
                }
                .logo-parts {
                    color: #f59e0b;
                    margin-left: 2px;
                }
                .badge-role {
                    display: inline-block;
                    margin-top: 6px;
                    font-size: 11px;
                    font-weight: 800;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                    background: rgba(239, 68, 68, 0.2);
                    color: #f87171;
                    padding: 3px 10px;
                    border-radius: 12px;
                    border: 1px solid rgba(239, 68, 68, 0.4);
                }
                .content-body {
                    padding: 32px 30px;
                    color: #cbd5e1;
                    font-size: 15px;
                    line-height: 1.65;
                }
                .greeting {
                    font-size: 18px;
                    font-weight: 700;
                    color: #ffffff;
                    margin-bottom: 12px;
                }
                .request-card {
                    background: rgba(239, 68, 68, 0.06);
                    border: 1px solid rgba(239, 68, 68, 0.25);
                    border-radius: 12px;
                    padding: 18px 20px;
                    margin: 22px 0;
                }
                .request-title {
                    font-size: 13px;
                    font-weight: 700;
                    color: #f87171;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 8px;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 6px;
                    font-size: 14px;
                }
                .detail-label {
                    color: #94a3b8;
                }
                .detail-val {
                    color: #ffffff;
                    font-weight: 600;
                }
                .btn-container {
                    text-align: center;
                    margin: 28px 0;
                }
                .accept-btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    color: #ffffff !important;
                    font-size: 16px;
                    font-weight: 700;
                    text-decoration: none;
                    padding: 15px 32px;
                    border-radius: 10px;
                    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
                    transition: all 0.3s ease;
                }
                .expiry-pill {
                    display: inline-block;
                    background: rgba(245, 158, 11, 0.15);
                    color: #fbbf24;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 5px 14px;
                    border-radius: 20px;
                    margin-top: 10px;
                }
                .rule-note {
                    background: rgba(255, 255, 255, 0.03);
                    border-left: 3px solid #f59e0b;
                    padding: 12px 16px;
                    border-radius: 4px;
                    font-size: 13px;
                    color: #94a3b8;
                    margin-top: 22px;
                }
                .fallback-box {
                    margin-top: 20px;
                    padding: 12px;
                    background: #0d1117;
                    border-radius: 8px;
                    font-size: 12px;
                    word-break: break-all;
                    color: #94a3b8;
                }
                .footer {
                    background-color: #0d1117;
                    padding: 20px 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #64748b;
                    border-top: 1px solid rgba(255, 255, 255, 0.05);
                }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <table class='main-table' align='center' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td class='header-bar'>
                            <div class='logo-text'>ONE<span class='logo-parts'>PIECE</span></div>
                            <div class='badge-role'>Super Admin Role Handover</div>
                        </td>
                    </tr>
                    <tr>
                        <td class='content-body'>
                            <div class='greeting'>Hello, {$escaped_to_name}!</div>
                            <p>The currently active Super Admin has initiated a formal request to transfer the <strong>Active Super Admin role</strong> to your account.</p>
                            
                            <div class='request-card'>
                                <div class='request-title'>Transfer Request Details</div>
                                <div class='detail-row'>
                                    <span class='detail-label'>From Current Super Admin:</span>
                                    <span class='detail-val'>{$escaped_from_name} ({$escaped_from_email})</span>
                                </div>
                                <div class='detail-row'>
                                    <span class='detail-label'>Role to be Transferred:</span>
                                    <span class='detail-val'>Active Super Admin</span>
                                </div>
                                <div class='detail-row'>
                                    <span class='detail-label'>Target Account:</span>
                                    <span class='detail-val'>{$escaped_to_name} ({$to_email})</span>
                                </div>
                            </div>

                            <p style='margin-bottom: 20px;'>To accept this handover and activate your Super Admin account, please click the confirmation button below:</p>
                            
                            <div class='btn-container'>
                                <a href='{$escaped_url}' class='accept-btn' target='_blank'>Accept &amp; Activate Super Admin Account</a>
                                <div><span class='expiry-pill'>⏱️ This confirmation link is valid for {$expires_display}</span></div>
                            </div>

                            <div class='rule-note'>
                                <strong>System Business Rule:</strong> Only ONE (1) Super Admin can be Active at any given time.
                                Once you click the button above, your account will be immediately set to <strong>Active</strong>, the previous Super Admin will be set to <strong>Inactive</strong> with their session invalidated, and you will be directed to the dashboard.
                            </div>

                            <div class='fallback-box'>
                                If the button above does not work, copy and paste this URL into your browser:<br>
                                <a href='{$escaped_url}' style='color: #f59e0b;'>{$escaped_url}</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class='footer'>
                            <p style='margin: 0 0 6px 0;'>&copy; " . date('Y') . " ONE PIECE System. Highly Restricted Super Admin Operations.</p>
                            <p style='margin: 0;'>If you did not expect this handover, please contact system security immediately.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";

        $mail->Body = $html_body;

        // Plain text version
        $mail->AltBody = "Hello {$to_name},\n\n"
            . "The current Active Super Admin ({$current_admin_name} - {$current_admin_email}) is requesting to hand over the Active Super Admin role to your account.\n\n"
            . "Business Rule: Only ONE Super Admin can have an 'Active' status at any time.\n\n"
            . "To accept and activate your account, please visit the following link:\n"
            . "{$confirmation_url}\n\n"
            . "This link is valid for {$expires_display}.\n\n"
            . "Best regards,\nONE PIECE Security";

        $mail->send();

        return [
            'success' => true,
            'message' => 'Role switch confirmation email sent successfully.'
        ];
    } catch (Exception $e) {
        error_log("PHPMailer send failed: " . $mail->ErrorInfo);
        return [
            'success' => false,
            'message' => 'Mailer Error: ' . $mail->ErrorInfo
        ];
    }
}

/**
 * Send notification email when a Super Admin account is ACTIVATED
 * 
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name / username
 * @return array ['success' => bool, 'message' => string]
 */
function send_superadmin_activated_notification($to_email, $to_name) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'message' => 'PHPMailer library is not available.'
        ];
    }

    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Invalid recipient email address.'
        ];
    }

    $mail = get_mailer_instance();

    try {
        $mail->addAddress($to_email, $to_name ?: 'Super Administrator');
        $mail->isHTML(true);
        $mail->Subject = "Your Account is Now the Active Super Admin - ONE PIECE";

        $escaped_name = htmlspecialchars($to_name ?: 'Super Administrator');

        // Resolve base URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $login_url = $protocol . $host . "/Final_Form/PHP/login.php";

        $html_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Super Admin Activated</title>
            <style>
                body { margin: 0; padding: 0; background-color: #0c0f14; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #f8fafc; }
                .wrapper { width: 100%; background-color: #0c0f14; padding: 30px 0; }
                .main-table { background-color: #12161f; margin: 0 auto; width: 100%; max-width: 580px; border-radius: 14px; border: 1px solid rgba(255, 255, 255, 0.08); overflow: hidden; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); }
                .header-bar { background: linear-gradient(135deg, #181d28 0%, #12161f 100%); padding: 26px 30px; text-align: center; border-bottom: 2px solid #10b981; }
                .logo-text { font-size: 24px; font-weight: 900; letter-spacing: 1.5px; color: #ffffff; text-transform: uppercase; }
                .logo-parts { color: #f59e0b; margin-left: 2px; }
                .badge-role { display: inline-block; margin-top: 6px; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 3px 10px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.4); }
                .content-body { padding: 32px 30px; color: #cbd5e1; font-size: 15px; line-height: 1.65; }
                .greeting { font-size: 18px; font-weight: 700; color: #ffffff; margin-bottom: 12px; }
                .status-card { background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0; }
                .status-icon { font-size: 32px; margin-bottom: 8px; }
                .status-title { font-size: 16px; font-weight: 800; color: #34d399; text-transform: uppercase; letter-spacing: 1px; }
                .status-desc { font-size: 13px; color: #94a3b8; margin-top: 4px; }
                .btn-container { text-align: center; margin: 26px 0; }
                .login-btn { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff !important; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); }
                .alert-note { background: rgba(255, 255, 255, 0.03); border-left: 3px solid #f59e0b; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #94a3b8; margin-top: 20px; }
                .footer { background-color: #0d1117; padding: 20px 30px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid rgba(255, 255, 255, 0.05); }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <table class='main-table' align='center' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td class='header-bar'>
                            <div class='logo-text'>ONE<span class='logo-parts'>PIECE</span></div>
                            <div class='badge-role'>👑 Role Activated</div>
                        </td>
                    </tr>
                    <tr>
                        <td class='content-body'>
                            <div class='greeting'>Greetings, {$escaped_name}!</div>
                            <p>This is an official system notification that your account has been set to <strong>ACTIVE Super Administrator</strong>.</p>
                            
                            <div class='status-card'>
                                <div class='status-icon'>👑</div>
                                <div class='status-title'>Account Status: ACTIVE</div>
                                <div class='status-desc'>You now hold primary administrative control over the ONE PIECE system.</div>
                            </div>

                            <p>According to system policy, only ONE (1) Super Admin can be active at a time. All other Super Admin accounts have been set to Inactive.</p>
                            
                            <div class='btn-container'>
                                <a href='{$login_url}' class='login-btn'>Sign In to Super Admin Dashboard</a>
                            </div>

                            <div class='alert-note'>
                                <strong>Security Reminder:</strong> If you did not expect this role change, please inspect your audit logs immediately.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class='footer'>
                            <p style='margin: 0 0 6px 0;'>&copy; " . date('Y') . " ONE PIECE System. Highly Restricted Role Management.</p>
                            <p style='margin: 0;'>Automated notification. Please do not reply directly.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";

        $mail->Body = $html_body;
        $mail->AltBody = "Hello {$to_name},\n\n"
            . "Your account has been activated as the ACTIVE Super Administrator for ONE PIECE.\n"
            . "You can now log in at: {$login_url}\n\n"
            . "Best regards,\nONE PIECE Security";

        $mail->send();
        return ['success' => true, 'message' => 'Activation notification email sent.'];
    } catch (Exception $e) {
        error_log("Notification mail failed: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
?>
