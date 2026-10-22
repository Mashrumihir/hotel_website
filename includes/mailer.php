<?php
require_once __DIR__ . '/../config.php';

/**
 * Send Email via Direct SMTP Socket (TLS Port 587 / SSL Port 465)
 * Pure PHP implementation - no external composer dependencies required.
 */
function send_smtp_email($toEmail, $subject, $htmlBody, $altText = '') {
    $settings = $_SESSION['smtp_settings'] ?? [];
    $host = trim((string) ($settings['host'] ?? (defined('SMTP_HOST') ? SMTP_HOST : '')));
    $port = (int) ($settings['port'] ?? (defined('SMTP_PORT') ? SMTP_PORT : 587));
    $username = trim((string) ($settings['username'] ?? (defined('SMTP_USER') ? SMTP_USER : '')));
    $password = (string) ($settings['password'] ?? (defined('SMTP_PASS') ? SMTP_PASS : ''));
    $fromName = trim((string) ($settings['from_name'] ?? (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Hotel Ease')));
    $fromEmail = trim((string) ($settings['from_email'] ?? $username));

    if ($host === '' || $username === '' || $password === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('SMTP is not configured. Set host, username, password, and a valid from email address.');
        return false;
    }

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || $port < 1 || $port > 65535) {
        error_log('SMTP recipient or port is invalid.');
        return false;
    }

    $crlf = "\r\n";

    // Set connection context
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $connectHost = ($port === 465) ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client($connectHost . ':' . $port, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        error_log("SMTP Stream Connection Error: $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, 12);

    $readResponse = function() use ($socket) {
        $response = '';
        while ($str = fgets($socket, 512)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function($cmd, $expectedCodes) use ($socket, $readResponse, $crlf) {
        fputs($socket, $cmd . $crlf);
        $resp = $readResponse();
        $code = substr($resp, 0, 3);
        $expectedCodesArr = (array)$expectedCodes;
        if (!in_array($code, $expectedCodesArr)) {
            error_log("SMTP Command ($cmd) Failed. Server Output: $resp");
            return false;
        }
        return true;
    };

    // 1. Read initial banner
    $banner = $readResponse();
    if (substr($banner, 0, 3) !== '220') {
        fclose($socket);
        return false;
    }

    // 2. EHLO
    $clientDomain = $_SERVER['SERVER_NAME'] ?? 'localhost';
    if (!$sendCommand('EHLO ' . $clientDomain, '250')) {
        fclose($socket);
        return false;
    }

    // 3. STARTTLS if using port 587
    if ($port === 587) {
        if (!$sendCommand('STARTTLS', '220')) {
            fclose($socket);
            return false;
        }

        $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
            error_log("SMTP STARTTLS handshake failed.");
            fclose($socket);
            return false;
        }

        // Send EHLO again after securing connection
        if (!$sendCommand('EHLO ' . $clientDomain, '250')) {
            fclose($socket);
            return false;
        }
    }

    // 4. AUTH LOGIN
    if (!$sendCommand('AUTH LOGIN', '334')) {
        fclose($socket);
        return false;
    }
    if (!$sendCommand(base64_encode($username), '334')) {
        fclose($socket);
        return false;
    }
    if (!$sendCommand(base64_encode($password), '235')) {
        fclose($socket);
        return false;
    }

    // 5. MAIL FROM & RCPT TO
    if (!$sendCommand("MAIL FROM: <$fromEmail>", '250')) {
        fclose($socket);
        return false;
    }
    if (!$sendCommand("RCPT TO: <$toEmail>", ['250', '251'])) {
        fclose($socket);
        return false;
    }

    // 6. DATA
    if (!$sendCommand('DATA', '354')) {
        fclose($socket);
        return false;
    }

    // 7. Headers & Message Content
    $encodedSubject  = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";

    $headers  = "From: $encodedFromName <$fromEmail>" . $crlf;
    $headers .= "To: <$toEmail>" . $crlf;
    $headers .= "Subject: $encodedSubject" . $crlf;
    $headers .= "MIME-Version: 1.0" . $crlf;
    $headers .= "Content-Type: text/html; charset=UTF-8" . $crlf;
    $headers .= "Date: " . date('r') . $crlf;

    // Escape dots on single lines per SMTP RFC 5321
    $preparedBody = str_replace("\n.", "\n..", str_replace("\r\n", "\n", $htmlBody));
    $preparedBody = str_replace("\n", $crlf, $preparedBody);

    $emailContent = $headers . $crlf . $preparedBody . $crlf . '.';
    
    if (!$sendCommand($emailContent, '250')) {
        fclose($socket);
        return false;
    }

    // 8. QUIT
    $sendCommand('QUIT', '221');
    fclose($socket);

    return true;
}

/**
 * Generate & Send HotelEase Luxury OTP Email Layout
 */
function send_otp_email($recipientEmail, $otpCode, $recipientName = '') {
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'HotelEase';
    $subject  = "🔐 $otpCode is your $siteName verification code";

    $displayName = !empty($recipientName) ? htmlspecialchars($recipientName) : 'Valued Guest';

    // Luxury Responsive HTML Email Layout
    $htmlContent = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body { margin: 0; padding: 0; background-color: #071A2F; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased; }
            .wrapper { width: 100%; background-color: #071A2F; padding: 30px 10px; }
            .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
            .top-bar { height: 5px; background: linear-gradient(90deg, #D4AF5A 0%, #F3E5AB 50%, #D4AF5A 100%); }
            .header { background-color: #0B1F3A; padding: 32px 30px; text-align: center; border-bottom: 1px solid rgba(212,175,90,0.2); }
            .brand-name { color: #D4AF5A; font-size: 26px; font-weight: 700; letter-spacing: 2px; margin-top: 4px; text-transform: uppercase; font-family: Georgia, serif; }
            .content { padding: 40px 35px; color: #1E293B; line-height: 1.6; }
            .greeting { font-size: 20px; font-weight: 700; color: #0B1F3A; margin-bottom: 16px; }
            .text-muted { color: #64748B; font-size: 15px; }
            .otp-container { background: linear-gradient(135deg, #F8FAFC 0%, #EDF2F7 100%); border: 2px dashed #C9A227; border-radius: 12px; padding: 25px 20px; text-align: center; margin: 28px 0; }
            .otp-label { font-size: 12px; font-weight: 700; color: #C9A227; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; }
            .otp-code { font-size: 38px; font-weight: 800; color: #0B1F3A; letter-spacing: 10px; font-family: "Courier New", Courier, monospace; margin: 5px 0; }
            .otp-validity { font-size: 13px; color: #64748B; margin-top: 8px; }
            .warning-box { background-color: #FFFBEB; border-left: 4px solid #F59E0B; padding: 14px 18px; border-radius: 6px; font-size: 13px; color: #92400E; margin-top: 24px; }
            .footer { background-color: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 24px 30px; text-align: center; font-size: 13px; color: #94A3B8; }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <div class="container">
                <div class="top-bar"></div>
                <div class="header">
                    <div class="brand-name">' . htmlspecialchars($siteName) . '</div>
                    <div style="color: #94A3B8; font-size: 12px; letter-spacing: 1px; margin-top: 4px;">LUXURY STAY &bull; EFFORTLESS EXPERIENCE</div>
                </div>
                <div class="content">
                    <div class="greeting">Hello, ' . $displayName . ' 👋</div>
                    <p class="text-muted">You requested a password reset for your <strong>' . htmlspecialchars($siteName) . '</strong> account. Please use the verification code below to complete your authentication process:</p>
                    
                    <div class="otp-container">
                        <div class="otp-label">Your Security Verification Code</div>
                        <div class="otp-code">' . htmlspecialchars($otpCode) . '</div>
                        <div class="otp-validity">⏱️ Code valid for 10 minutes. Do not share this code with anyone.</div>
                    </div>

                    <p class="text-muted">For security reasons, this code will automatically expire in 10 minutes. If you did not request a password reset, please ignore this email or contact our 24/7 Concierge desk immediately.</p>
                    
                    <div class="warning-box">
                        🔒 <strong>Security Tip:</strong> HotelEase staff will never ask for your verification code or password over the phone or email.
                    </div>
                </div>
                <div class="footer">
                    <p style="margin: 0 0 8px 0;">&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '. All rights reserved.</p>
                    <p style="margin: 0;">777 Ocean Drive, Paradise Bay &bull; Concierge Desk</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ';

    return send_smtp_email($recipientEmail, $subject, $htmlContent);
}
