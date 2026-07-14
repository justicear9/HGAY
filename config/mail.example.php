<?php
/**
 * Copy to config/mail.php on the server (not in Git).
 *
 * Use your hosting provider's SMTP settings — often:
 * - Host: mail.yourdomain.com or the cPanel server hostname (e.g. s1234.use1.mysecurecloudhost.com)
 * - Port: 465 (SSL) or 587 (TLS — contact us if you need 587 support)
 * - Username: full email address
 * - Password: mailbox or app password
 * - From: same mailbox (must be allowed to send)
 *
 * Cloudflare note: if mail.yourdomain.com is orange-cloud (proxied), SMTP will time out.
 * Set the "mail" A record to DNS only (grey cloud), or use the hosting SMTP hostname instead.
 */
define('MAIL_HOST', 'mail.howghanaianareyou.com');
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'orders@howghanaianareyou.com');
define('MAIL_PASSWORD', 'your_mailbox_password');
define('MAIL_ENCRYPTION', 'ssl');
define('MAIL_FROM_ADDRESS', 'orders@howghanaianareyou.com');
define('MAIL_FROM_NAME', 'How Ghanaian Are You');
