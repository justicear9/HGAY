<?php
/**
 * Send email via SMTP (SSL port 465).
 * Requires config/mail.php to be loaded.
 */
function sendSmtpMail($to, $subject, $bodyHtml) {
  $to = (string) $to;
  $subject = (string) $subject;
  $bodyHtml = (string) $bodyHtml;
  if (!defined('MAIL_HOST')) {
    require_once dirname(__DIR__) . '/config/mail.php';
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
  $fp = @stream_socket_client($host, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
  if (!$fp) {
    error_log('SMTP connect failed: ' . $errstr);
    return false;
  }
  stream_set_blocking($fp, true);
  stream_set_timeout($fp, 15);

  $read = function () use ($fp) {
    $line = fgets($fp, 512);
    return $line !== false ? trim($line) : '';
  };
  $write = function ($msg) use ($fp) {
    $msg = rtrim($msg) . "\r\n";
    return fwrite($fp, $msg) === strlen($msg);
  };

  if ($read() === '') return false;
  if (!$write('EHLO ' . MAIL_HOST)) return false;
  while ($line = $read()) { if (strlen($line) < 4) break; }
  if (!$write('AUTH LOGIN')) return false;
  $read();
  if (!$write(base64_encode(MAIL_USERNAME))) return false;
  $read();
  if (!$write(base64_encode(MAIL_PASSWORD))) return false;
  $auth = $read();
  if (strpos($auth, '235') === false) { fclose($fp); return false; }
  if (!$write('MAIL FROM:<' . MAIL_FROM_ADDRESS . '>')) return false;
  $read();
  if (!$write('RCPT TO:<' . $to . '>')) return false;
  $read();
  if (!$write('DATA')) return false;
  $read();

  $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'How Ghanaian Are You';
  $safeFrom = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($fromName, 'UTF-8', 'Q') : $fromName;
  $safeSubj = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subject, 'UTF-8', 'Q') : $subject;
  $headers = "From: " . $safeFrom . " <" . MAIL_FROM_ADDRESS . ">\r\n";
  $headers .= "To: $to\r\n";
  $headers .= "Subject: " . $safeSubj . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "\r\n";
  if (!$write($headers . $bodyHtml)) return false;
  if (!$write('.')) return false;
  $read();
  fclose($fp);
  return true;
}
