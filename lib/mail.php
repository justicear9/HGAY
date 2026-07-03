<?php
/**
 * SMTP mail with clearer errors and RFC dot-stuffing.
 */
declare(strict_types=1);

function smtp_read_line($fp): string
{
    $line = fgets($fp, 515);
    return $line !== false ? $line : '';
}

function smtp_read_response($fp): string
{
    $data = '';
    while (true) {
        $line = smtp_read_line($fp);
        if ($line === '') {
            break;
        }
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $data;
}

function smtp_write($fp, string $msg): bool
{
    return fwrite($fp, rtrim($msg) . "\r\n") !== false;
}

function smtp_stuff_body(string $bodyHtml): string
{
    $normalized = str_replace(["\r\n", "\r"], "\n", $bodyHtml);
    $lines = explode("\n", $normalized);
    $out = [];
    foreach ($lines as $line) {
        if ($line !== '' && $line[0] === '.') {
            $line = '.' . $line;
        }
        $out[] = $line;
    }
    return implode("\r\n", $out) . "\r\n";
}

function hgay_mail_config_path(): string
{
    return dirname(__DIR__) . '/config/mail.php';
}

function hgay_mail_is_configured(): bool
{
    $path = hgay_mail_config_path();
    if (!is_file($path)) {
        return false;
    }
    require_once $path;
    return defined('MAIL_HOST')
        && MAIL_HOST !== ''
        && MAIL_HOST !== 'your-smtp-host'
        && defined('MAIL_USERNAME')
        && MAIL_USERNAME !== ''
        && MAIL_USERNAME !== 'your@email.com';
}

/**
 * @return array{ok: bool, error: string}
 */
function sendSmtpMail(string $to, string $subject, string $bodyHtml): array
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid recipient email.'];
    }

    if (!hgay_mail_is_configured()) {
        return ['ok' => false, 'error' => 'config/mail.php is missing or still has placeholder SMTP values.'];
    }

    $host = 'ssl://' . MAIL_HOST . ':' . (int) MAIL_PORT;
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($host, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        $msg = 'SMTP connect failed (' . MAIL_HOST . ':' . MAIL_PORT . '): ' . $errstr;
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    stream_set_blocking($fp, true);
    stream_set_timeout($fp, 20);

    $ehloHost = 'localhost';
    if (defined('MAIL_FROM_ADDRESS') && str_contains(MAIL_FROM_ADDRESS, '@')) {
        $ehloHost = substr(MAIL_FROM_ADDRESS, (int) strpos(MAIL_FROM_ADDRESS, '@') + 1);
    }

    $greeting = smtp_read_response($fp);
    if ($greeting === '' || !str_starts_with($greeting, '220')) {
        fclose($fp);
        $msg = 'SMTP greeting failed: ' . trim($greeting);
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    if (!smtp_write($fp, 'EHLO ' . $ehloHost)) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP EHLO write failed.'];
    }
    smtp_read_response($fp);

    if (!smtp_write($fp, 'AUTH LOGIN')) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP AUTH write failed.'];
    }
    smtp_read_response($fp);

    if (!smtp_write($fp, base64_encode(MAIL_USERNAME))) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP username write failed.'];
    }
    smtp_read_response($fp);

    if (!smtp_write($fp, base64_encode(MAIL_PASSWORD))) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP password write failed.'];
    }
    $auth = smtp_read_response($fp);
    if (!str_contains($auth, '235')) {
        fclose($fp);
        $msg = 'SMTP authentication failed. Check MAIL_USERNAME and MAIL_PASSWORD in config/mail.php.';
        error_log($msg . ' Response: ' . trim($auth));
        return ['ok' => false, 'error' => $msg];
    }

    if (!smtp_write($fp, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>')) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP MAIL FROM failed.'];
    }
    $mailFrom = smtp_read_response($fp);
    if (!str_contains($mailFrom, '250')) {
        fclose($fp);
        $msg = 'SMTP rejected MAIL FROM: ' . trim($mailFrom);
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    if (!smtp_write($fp, 'RCPT TO:<' . $to . '>')) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP RCPT TO write failed.'];
    }
    $rcpt = smtp_read_response($fp);
    if (!str_contains($rcpt, '250') && !str_contains($rcpt, '251')) {
        fclose($fp);
        $msg = 'SMTP rejected recipient: ' . trim($rcpt);
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    if (!smtp_write($fp, 'DATA')) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP DATA write failed.'];
    }
    $dataReady = smtp_read_response($fp);
    if (!str_starts_with(trim($dataReady), '354')) {
        fclose($fp);
        $msg = 'SMTP DATA not accepted: ' . trim($dataReady);
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'How Ghanaian Are You';
    $safeFrom = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($fromName, 'UTF-8', 'Q') : $fromName;
    $safeSubj = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subject, 'UTF-8', 'Q') : $subject;
    $headers = "From: {$safeFrom} <" . MAIL_FROM_ADDRESS . ">\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: {$safeSubj}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "\r\n";

    $payload = smtp_stuff_body($headers . $bodyHtml);
    if (fwrite($fp, $payload) === false) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP message body write failed.'];
    }
    if (!smtp_write($fp, '.')) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP end-of-data failed.'];
    }
    $sent = smtp_read_response($fp);
    smtp_write($fp, 'QUIT');
    fclose($fp);

    if (!str_contains($sent, '250')) {
        $msg = 'SMTP send failed after DATA: ' . trim($sent);
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    return ['ok' => true, 'error' => ''];
}
