<?php
/**
 * Order confirmation HTML email — kente frame + site palette.
 * Uses opaque <img> kente tiles (Gmail blocks CSS background-image).
 *
 * Frame:
 *   [======== full-width top bar ========]
 *   [side][         content        ][side]
 *   [======== full-width bot bar ========]
 *
 * Top/bottom bars span the full 560px so the corner cells are solid kente.
 * Side strips sit flush under/above those bars (fixed height covers the body).
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

// Cache-bust so mail clients pick up asset updates after deploy
$kenteQ = '?v=20260714b';
$kenteHorizontal = hgay_email_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/kentepatternpng_horizontal.png') . $kenteQ;
$kenteVertical = hgay_email_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/kentepatternpng.png') . $kenteQ;
$logoUrl = hgay_email_absolute_url('HGAY_ASSETS/logo.png');
$siteUrl = hgay_email_absolute_url('');

$frameW = 560;
$sideW = 28;
$barH = 28;
$innerW = $frameW - (2 * $sideW);
// Tall enough to cover confirmation body; excess continues the side pattern
$sideH = 680;

$bg = '#0a0a0b';
$card = '#18181b';
$text = '#fafafa';
$textSoft = '#a1a1aa';
$textMuted = '#71717a';
$gold = '#FCD116';
$red = '#E30613';
$green = '#006B3F';
$border = 'rgba(255,255,255,0.08)';

$kh = htmlspecialchars($kenteHorizontal);
$kv = htmlspecialchars($kenteVertical);
$cell0 = 'padding:0;margin:0;font-size:0;line-height:0;border:0;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="color-scheme" content="dark only">
  <meta name="supported-color-schemes" content="dark">
  <title>Order confirmed — How Ghanaian Are You?</title>
  <!--[if mso]>
  <style type="text/css">
    body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
  </style>
  <![endif]-->
  <style type="text/css">
    :root { color-scheme: dark; supported-color-schemes: dark; }
  </style>
</head>
<body style="margin:0; padding:0; width:100% !important; background:<?php echo $bg; ?> !important; font-family: 'Segoe UI', Arial, Helvetica, sans-serif; -webkit-text-size-adjust:100%;">
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    Your How Ghanaian Are You? order is confirmed<?php echo $payOnDelivery ? ' — pay on delivery.' : '.'; ?>
  </div>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="<?php echo $bg; ?>" style="background:<?php echo $bg; ?> !important; border-collapse:collapse; border-spacing:0;">
    <tr>
      <td align="center" bgcolor="<?php echo $bg; ?>" style="padding:24px 12px; background:<?php echo $bg; ?> !important;">

        <table role="presentation" width="<?php echo $frameW; ?>" cellspacing="0" cellpadding="0" border="0" bgcolor="<?php echo $bg; ?>" style="width:<?php echo $frameW; ?>px; max-width:<?php echo $frameW; ?>px; border-collapse:collapse; border-spacing:0; mso-table-lspace:0pt; mso-table-rspace:0pt;">

          <!-- Full-width top kente (fills the corner columns too) -->
          <tr>
            <td colspan="3" width="<?php echo $frameW; ?>" height="<?php echo $barH; ?>" valign="top" bgcolor="<?php echo $bg; ?>" style="width:<?php echo $frameW; ?>px; height:<?php echo $barH; ?>px; <?php echo $cell0; ?>">
              <img src="<?php echo $kh; ?>" width="<?php echo $frameW; ?>" height="<?php echo $barH; ?>" alt="" style="display:block; width:<?php echo $frameW; ?>px; height:<?php echo $barH; ?>px; border:0;">
            </td>
          </tr>

          <tr>
            <td width="<?php echo $sideW; ?>" valign="top" bgcolor="<?php echo $bg; ?>" style="width:<?php echo $sideW; ?>px; <?php echo $cell0; ?>">
              <img src="<?php echo $kv; ?>" width="<?php echo $sideW; ?>" height="<?php echo $sideH; ?>" alt="" style="display:block; width:<?php echo $sideW; ?>px; height:<?php echo $sideH; ?>px; border:0;">
            </td>

            <td width="<?php echo $innerW; ?>" valign="top" bgcolor="<?php echo $card; ?>" style="width:<?php echo $innerW; ?>px; background:<?php echo $card; ?> !important; padding:0; vertical-align:top;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:28px 24px 20px; text-align:center;">
                    <a href="<?php echo htmlspecialchars($siteUrl); ?>" style="text-decoration:none;">
                      <img src="<?php echo htmlspecialchars($logoUrl); ?>" width="180" height="54" alt="How Ghanaian Are You" style="display:block; margin:0 auto; max-width:180px; height:auto; border:0;">
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 24px 8px; text-align:center;">
                    <p style="margin:0; font-size:11px; letter-spacing:0.14em; text-transform:uppercase; color:<?php echo $gold; ?>;">Order confirmed</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 24px 24px; text-align:center;">
                    <h1 style="margin:0; font-family: Georgia, 'Times New Roman', serif; font-size:26px; line-height:1.2; font-weight:700; color:<?php echo $text; ?>;">
                      <span style="color:<?php echo $red; ?>;">Thank you,</span><br>
                      <?php echo $name; ?>
                    </h1>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 24px 24px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid <?php echo $border; ?>; border-left:4px solid <?php echo $gold; ?>; border-radius:12px; background:#111113;">
                      <tr>
                        <td style="padding:18px 20px;">
                          <p style="margin:0 0 6px; font-size:11px; letter-spacing:0.12em; text-transform:uppercase; color:<?php echo $textMuted; ?>;">Your order</p>
                          <p style="margin:0; font-size:18px; font-weight:700; color:<?php echo $text; ?>;">
                            <?php echo $quantity; ?> game<?php echo $quantity === 1 ? '' : 's'; ?> · <span style="color:<?php echo $gold; ?>;"><?php echo htmlspecialchars($amountFormatted); ?></span>
                          </p>
                          <?php if ($payOnDelivery): ?>
                            <p style="margin:10px 0 0; font-size:14px; color:<?php echo $textSoft; ?>;">Pay on delivery</p>
                          <?php else: ?>
                            <p style="margin:10px 0 0; font-size:14px; color:<?php echo $textSoft; ?>;">Payment received via Hubtel. Thank you!</p>
                          <?php endif; ?>
                          <?php if ($orderId > 0): ?>
                            <p style="margin:12px 0 0; font-size:13px; color:<?php echo $textMuted; ?>;">Order #<?php echo $orderId; ?></p>
                          <?php endif; ?>
                          <?php if ($ref !== ''): ?>
                            <p style="margin:4px 0 0; font-size:13px; color:<?php echo $textMuted; ?>;">Reference: <?php echo $ref; ?></p>
                          <?php endif; ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 24px 28px; text-align:center;">
                    <p style="margin:0; font-size:15px; line-height:1.55; color:<?php echo $textSoft; ?>;">
                      We'll contact you soon with delivery details. If you have questions, reply to this email.
                    </p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 24px 20px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
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
                  <td style="padding:0 24px 32px; text-align:center;">
                    <p style="margin:0; font-size:14px; color:<?php echo $gold; ?>;">Celebrating Ghanaian pride, one card at a time.</p>
                    <p style="margin:8px 0 0; font-size:12px; color:<?php echo $textMuted; ?>;">How Ghanaian Are You? &middot; Nezer&amp;Friends</p>
                    <p style="margin:16px 0 0;">
                      <a href="<?php echo htmlspecialchars($siteUrl); ?>" style="display:inline-block; padding:12px 22px; background:<?php echo $gold; ?>; color:#0a0a0b; font-size:14px; font-weight:700; text-decoration:none; border-radius:999px;">Visit our website</a>
                    </p>
                  </td>
                </tr>
              </table>
            </td>

            <td width="<?php echo $sideW; ?>" valign="top" bgcolor="<?php echo $bg; ?>" style="width:<?php echo $sideW; ?>px; <?php echo $cell0; ?>">
              <img src="<?php echo $kv; ?>" width="<?php echo $sideW; ?>" height="<?php echo $sideH; ?>" alt="" style="display:block; width:<?php echo $sideW; ?>px; height:<?php echo $sideH; ?>px; border:0;">
            </td>
          </tr>

          <!-- Full-width bottom kente -->
          <tr>
            <td colspan="3" width="<?php echo $frameW; ?>" height="<?php echo $barH; ?>" valign="top" bgcolor="<?php echo $bg; ?>" style="width:<?php echo $frameW; ?>px; height:<?php echo $barH; ?>px; <?php echo $cell0; ?>">
              <img src="<?php echo $kh; ?>" width="<?php echo $frameW; ?>" height="<?php echo $barH; ?>" alt="" style="display:block; width:<?php echo $frameW; ?>px; height:<?php echo $barH; ?>px; border:0;">
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
