<?php
/**
 * PHPMailer Configuration and Mail Helper
 * Project: MOTOPARTS Password Recovery Flow
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
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'MOTOPARTS Support');

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
        $mail->Subject = "Password Reset OTP Code - MOTOPARTS";

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
                    border-bottom: 2px solid #ff5500;
                }
                .logo-text {
                    font-size: 26px;
                    font-weight: 900;
                    letter-spacing: 1px;
                    color: #ffffff;
                    text-transform: uppercase;
                }
                .logo-parts {
                    color: #ff5500;
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
                    background: rgba(255, 85, 0, 0.07);
                    border: 2px dashed #ff5500;
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
                    color: #ff8c42;
                    margin-bottom: 8px;
                }
                .otp-number {
                    font-size: 38px;
                    font-weight: 900;
                    letter-spacing: 8px;
                    color: #ff5500;
                    font-family: 'Courier New', monospace, sans-serif;
                }
                .expiry-pill {
                    display: inline-block;
                    margin-top: 10px;
                    background: rgba(255, 85, 0, 0.18);
                    color: #ffaa77;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 4px 12px;
                    border-radius: 20px;
                }
                .alert-note {
                    background: rgba(255, 255, 255, 0.03);
                    border-left: 3px solid #ff5500;
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
                            <div class='logo-text'>MOTO<span class='logo-parts'>PARTS</span></div>
                        </td>
                    </tr>
                    <tr>
                        <td class='content-body'>
                            <div class='greeting'>Hello, {$escaped_name}!</div>
                            <p>We received a request to reset your password for your MOTOPARTS account.</p>
                            <p>Please use the following 6-digit One-Time PIN (OTP) to verify your identity and complete your password reset:</p>
                            
                            <div class='otp-card'>
                                <div class='otp-label'>Your One-Time PIN (OTP)</div>
                                <div class='otp-number'>{$escaped_otp}</div>
                                <div class='expiry-pill'>⏱️ Valid for {$expires_minutes} minutes</div>
                            </div>
                            
                            <div class='alert-note'>
                                <strong>Security Notice:</strong> For your protection, never share this OTP with anyone. MOTOPARTS support will never ask for your OTP. If you did not initiate this request, you can safely ignore this email.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class='footer'>
                            <p style='margin: 0 0 6px 0;'>&copy; " . date('Y') . " MOTOPARTS. All rights reserved.</p>
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
            . "Your MOTOPARTS password reset verification code is: {$otp}\n\n"
            . "This code is valid for {$expires_minutes} minutes.\n\n"
            . "If you did not request a password reset, please ignore this message.\n\n"
            . "Best regards,\nMOTOPARTS Team";

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
?>
