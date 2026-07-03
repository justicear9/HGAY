<?php
/**
 * Order confirmation HTML email — kente frame + site palette.
 * Uses <img> for kente (Gmail blocks CSS background-image on remote hosts).
 *
 * @var array $order name, email, quantity, amount_pesewas, currency,
 *              paystack_reference, payment_mode, order_id
 */
require_once dirname(__DIR__) . '/lib/seo.php';

$name = isset($order['name']) ? htmlspecialchars($order['name']) : 'Customer';
$quantity = (int) ($order['quantity'] ?? 1);
$amount = isset($order['amount_pesewas']) ? (int) $order['amount_pesewas'] : 0;
$currency = isset($order['currency']) ? htmlspecialchars($order['currency']) : 'GHS';
$amountFormatted = number_format($amount / 100, 2) . ' ' . $currency;
$ref = isset($order['paystack_reference']) ? htmlspecialchars($order['paystack_reference']) : '';
$payOnDelivery = ($order['payment_mode'] ?? '') === 'pay_on_delivery';
$orderId = isset($order['order_id']) ? (int) $order['order_id'] : 0;

$kenteHorizontal = hgay_email_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/kentepatternpng_horizontal.png');
$kenteVertical = hgay_email_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/kentepatternpng.png');
$logoUrl = hgay_email_absolute_url('HGAY_ASSETS/logo.png');
$siteUrl = hgay_email_absolute_url('');

$bg = '#0a0a0b';
$card = '#18181b';
$text = '#fafafa';
$textSoft = '#a1a1aa';
$textMuted = '#71717a';
$gold = '#FCD116';
$red = '#E30613';
$green = '#006B3F';
$border = 'rgba(255,255,255,0.08)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Order confirmed — How Ghanaian Are You?</title>
  <!--[if mso]>
  <style type="text/css">
    body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
  </style>
  <![endif]-->
</head>
<body style="margin:0; padding:0; width:100% !important; background:<?php echo $bg; ?>; font-family: 'Segoe UI', Arial, Helvetica, sans-serif; -webkit-text-size-adjust:100%;">
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    Your How Ghanaian Are You? order is confirmed<?php echo $payOnDelivery ? ' — pay on delivery.' : '.'; ?>
  </div>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:<?php echo $bg; ?>;">
    <tr>
      <td align="center" style="padding:24px 12px;">

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px; border-collapse:collapse;">

          <!-- Top kente strip (img — works in Gmail) -->
          <tr>
            <td colspan="3" style="padding:0; font-size:0; line-height:0;">
              <img src="<?php echo htmlspecialchars($kenteHorizontal); ?>" width="560" height="32" alt="" style="display:block; width:100%; max-width:560px; height:32px; border:0;">
            </td>
          </tr>

          <tr>
            <!-- Left kente strip -->
            <td width="18" valign="top" style="padding:0; font-size:0; line-height:0; background:<?php echo $bg; ?>;">
              <img src="<?php echo htmlspecialchars($kenteVertical); ?>" width="18" height="520" alt="" style="display:block; width:18px; min-width:18px; height:520px; border:0;">
            </td>

            <!-- Main card -->
            <td style="background:<?php echo $card; ?>; padding:0;">

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td style="padding:28px 28px 20px; text-align:center;">
                    <a href="<?php echo htmlspecialchars($siteUrl); ?>" style="text-decoration:none;">
                      <img src="<?php echo htmlspecialchars($logoUrl); ?>" width="180" height="54" alt="How Ghanaian Are You" style="display:block; margin:0 auto; max-width:180px; height:auto; border:0;">
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 28px 8px; text-align:center;">
                    <p style="margin:0; font-size:11px; letter-spacing:0.14em; text-transform:uppercase; color:<?php echo $gold; ?>;">Order confirmed</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 28px 24px; text-align:center;">
                    <h1 style="margin:0; font-family: Georgia, 'Times New Roman', serif; font-size:26px; line-height:1.2; font-weight:700; color:<?php echo $text; ?>;">
                      <span style="color:<?php echo $red; ?>;">Thank you,</span><br>
                      <?php echo $name; ?>
                    </h1>
                  </td>
                </tr>

                <!-- Order summary -->
                <tr>
                  <td style="padding:0 28px 24px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid <?php echo $border; ?>; border-left:4px solid <?php echo $gold; ?>; border-radius:12px; background:#111113;">
                      <tr>
                        <td style="padding:20px 22px;">
                          <p style="margin:0 0 14px; font-size:13px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:<?php echo $textMuted; ?>;">Your order</p>
                          <p style="margin:0 0 8px; font-size:16px; line-height:1.5; color:<?php echo $textSoft; ?>;">
                            <strong style="color:<?php echo $text; ?>;"><?php echo $quantity; ?></strong>
                            game<?php echo $quantity > 1 ? 's' : ''; ?>
                            &middot;
                            <strong style="color:<?php echo $gold; ?>;"><?php echo $amountFormatted; ?></strong>
                          </p>
                          <?php if ($payOnDelivery): ?>
                          <p style="margin:0; font-size:15px; line-height:1.55; color:<?php echo $textSoft; ?>;">
                            Pay on delivery when we bring your order. Please have payment ready.
                          </p>
                          <?php elseif (($order['payment_mode'] ?? '') === 'hubtel'): ?>
                          <p style="margin:0; font-size:15px; line-height:1.55; color:<?php echo $textSoft; ?>;">
                            Payment received via Hubtel. Thank you!
                          </p>
                          <?php else: ?>
                          <p style="margin:0; font-size:15px; line-height:1.55; color:<?php echo $textSoft; ?>;">
                            Payment received. Thank you!
                          </p>
                          <?php endif; ?>
                          <?php if ($orderId > 0): ?>
                          <p style="margin:14px 0 0; font-size:13px; color:<?php echo $textMuted; ?>;">Order #<?php echo $orderId; ?></p>
                          <?php endif; ?>
                          <?php if ($ref): ?>
                          <p style="margin:6px 0 0; font-size:13px; color:<?php echo $textMuted; ?>;">Reference: <?php echo $ref; ?></p>
                          <?php endif; ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 28px 28px;">
                    <p style="margin:0; font-size:15px; line-height:1.6; color:<?php echo $textSoft; ?>;">
                      We'll contact you soon with delivery details. If you have questions, reply to this email.
                    </p>
                  </td>
                </tr>

                <!-- Ghana colour accent -->
                <tr>
                  <td style="padding:0 28px 24px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td height="4" style="background:<?php echo $red; ?>; font-size:0; line-height:0;">&nbsp;</td>
                      </tr>
                      <tr>
                        <td height="4" style="background:<?php echo $gold; ?>; font-size:0; line-height:0;">&nbsp;</td>
                      </tr>
                      <tr>
                        <td height="4" style="background:<?php echo $green; ?>; font-size:0; line-height:0;">&nbsp;</td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 28px 32px; text-align:center;">
                    <p style="margin:0; font-size:14px; color:<?php echo $gold; ?>;">Celebrating Ghanaian pride, one card at a time.</p>
                    <p style="margin:8px 0 0; font-size:12px; color:<?php echo $textMuted; ?>;">How Ghanaian Are You? &middot; Nezer&amp;Friends</p>
                    <p style="margin:16px 0 0;">
                      <a href="<?php echo htmlspecialchars($siteUrl); ?>" style="display:inline-block; padding:12px 22px; background:<?php echo $gold; ?>; color:#0a0a0b; font-size:14px; font-weight:700; text-decoration:none; border-radius:999px;">Visit our website</a>
                    </p>
                  </td>
                </tr>
              </table>

            </td>

            <!-- Right kente strip -->
            <td width="18" valign="top" style="padding:0; font-size:0; line-height:0; background:<?php echo $bg; ?>;">
              <img src="<?php echo htmlspecialchars($kenteVertical); ?>" width="18" height="520" alt="" style="display:block; width:18px; min-width:18px; height:520px; border:0;">
            </td>
          </tr>

          <!-- Bottom kente strip -->
          <tr>
            <td colspan="3" style="padding:0; font-size:0; line-height:0;">
              <img src="<?php echo htmlspecialchars($kenteHorizontal); ?>" width="560" height="32" alt="" style="display:block; width:100%; max-width:560px; height:32px; border:0;">
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
