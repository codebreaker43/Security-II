<?php
/**
 * SMTP Mailer Bridge for MOTOPARTS
 * Bridges to centralized PHPMailer in PHP/includes/mail_config.php
 */

require_once __DIR__ . '/../../includes/mail_config.php';

/**
 * Legacy wrapper function to maintain compatibility if called elsewhere
 */
function send_smtp_email($to, $subject, $body) {
    try {
        $mail = get_mailer_instance();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        return $mail->send();
    } catch (\Exception $e) {
        error_log("send_smtp_email error: " . $e->getMessage());
        return false;
    }
}
?>
