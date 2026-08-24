<?php
// Simple SMTP Mailer Script to bypass mail() and sendmail.ini
// Usage: require_once 'smtp_mailer.php'; $result = send_smtp_email($to, $subject, $body);

function send_smtp_email($to, $subject, $body) {
    // Configuration - REPLACE THESE WITH YOUR OWN DETAILS
    // For Gmail, enable 2-Factor Authentication and generate an App Password: https://myaccount.google.com/apppasswords
    $smtp_host = 'smtp.gmail.com'; 
    $smtp_port = 587; 
    $smtp_user = 'mnmrubikscubeshop00@gmail.com'; // REPLACE THIS
    $smtp_pass = 'voplugivmywygngl';    // REPLACE THIS
    $from_email = $smtp_user;
    $from_name  = "MnM Cube Shop";

    // Create the message headers
    $headers = array(
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=utf-8",
        "From: $from_name <$from_email>",
        "To: $to",
        "Subject: $subject",
        "Date: " . date("r")
    );
    
    // Combine headers and body
    $message_data = "";
    foreach ($headers as $header) {
        $message_data .= $header . "\r\n";
    }
    $message_data .= "\r\n" . $body . "\r\n";

    // Connect to SMTP Server
    $socket = stream_socket_client("tcp://$smtp_host:$smtp_port", $errno, $errstr, 30);
    if (!$socket) {
        error_log("SMTP Connection Failed: $errstr ($errno)");
        return false;
    }

    // specific helper to read server response
    function read_smtp_response($socket) {
        $data = "";
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $data;
    }

    // Helper to send command
    function smtp_command($socket, $cmd) {
        fputs($socket, $cmd . "\r\n");
        return read_smtp_response($socket);
    }

    read_smtp_response($socket); // Initial greeting

    // EHLO
    smtp_command($socket, "EHLO " . gethostname());

    // STARTTLS
    $response = smtp_command($socket, "STARTTLS");
    if (strpos($response, '220') === false) return false;

    // Enable crypto
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return false;

    // EHLO again after TLS
    smtp_command($socket, "EHLO " . gethostname());

    // AUTH LOGIN
    smtp_command($socket, "AUTH LOGIN");
    smtp_command($socket, base64_encode($smtp_user));
    $response = smtp_command($socket, base64_encode($smtp_pass));
    if (strpos($response, '235') === false) return false; // Authentication success code

    // MAIL FROM
    smtp_command($socket, "MAIL FROM: <$from_email>");

    // RCPT TO
    smtp_command($socket, "RCPT TO: <$to>");

    // DATA
    smtp_command($socket, "DATA");

    // Send Message
    fputs($socket, $message_data . "\r\n.\r\n");
    $response = read_smtp_response($socket);

    // QUIT
    smtp_command($socket, "QUIT");
    fclose($socket);

    // Check if message queued (250 OK)
    return strpos($response, '250') !== false;
}
?>
