<?php
/**
 * On-brand HTML email for order confirmation. Kente borders, Ghana colors.
 * $order: array with name, email, quantity, amount_pesewas, currency, paystack_reference
 */
$name = isset($order['name']) ? htmlspecialchars($order['name']) : 'Customer';
$quantity = (int) ($order['quantity'] ?? 1);
$amount = isset($order['amount_pesewas']) ? (int) $order['amount_pesewas'] : 0;
$currency = isset($order['currency']) ? htmlspecialchars($order['currency']) : 'GHS';
$amountFormatted = number_format($amount / 100, 2) . ' ' . $currency;
$ref = isset($order['paystack_reference']) ? htmlspecialchars($order['paystack_reference']) : '';
$red = '#CE1126';
$gold = '#FCD138';
$green = '#006B3F';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order confirmed — How Ghanaian Are You?</title>
</head>
<body style="margin:0; padding:0; background:#1a1a1a; font-family: 'Segoe UI', Arial, sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#1a1a1a;">
    <tr>
      <td align="center" style="padding: 32px 16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 520px; background:#252526; border-collapse: collapse;">
          <!-- Kente top border: red / gold / green stripes -->
          <tr>
            <td style="height: 6px; background: <?php echo $red; ?>;"></td>
          </tr>
          <tr>
            <td style="height: 6px; background: <?php echo $gold; ?>;"></td>
          </tr>
          <tr>
            <td style="height: 6px; background: <?php echo $green; ?>;"></td>
          </tr>
          <tr>
            <td style="padding: 0 24px 24px;">
              <!-- Geometric Kente-style border (repeating pattern as table) -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 3px solid <?php echo $gold; ?>; border-collapse: collapse;">
                <tr>
                  <td style="padding: 28px 24px; border: 2px solid <?php echo $red; ?>;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                      <tr>
                        <td align="center" style="padding-bottom: 8px;">
                          <span style="font-size: 14px; color: <?php echo $gold; ?>; letter-spacing: 0.1em; text-transform: uppercase;">Thank you</span>
                        </td>
                      </tr>
                      <tr>
                        <td align="center" style="padding-bottom: 16px;">
                          <span style="font-size: 22px; font-weight: bold; color: #ffffff;">HOW GHANAIAN ARE YOU?</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 16px 0; border-top: 1px solid rgba(252,209,56,0.4); border-bottom: 1px solid rgba(252,209,56,0.4);">
                          <p style="margin:0; font-size: 16px; color: #e0e0e0; line-height: 1.5;">Hi <?php echo $name; ?>,</p>
                          <p style="margin: 12px 0 0; font-size: 15px; color: #b0b0b0; line-height: 1.5;">Your order is confirmed. We've received your payment of <strong style="color: <?php echo $gold; ?>;"><?php echo $amountFormatted; ?></strong> for <strong><?php echo $quantity; ?></strong> game<?php echo $quantity > 1 ? 's' : ''; ?>.</p>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-top: 16px;">
                          <p style="margin:0; font-size: 14px; color: #909090;">We'll be in touch with delivery details soon. Keep the reference below if you need to get in touch.</p>
                          <?php if ($ref): ?>
                          <p style="margin: 12px 0 0; font-size: 13px; color: #707070;">Reference: <?php echo $ref; ?></p>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <tr>
                        <td align="center" style="padding-top: 24px;">
                          <p style="margin:0; font-size: 13px; color: <?php echo $gold; ?>;">Celebrating Ghanaian pride, one card at a time.</p>
                          <p style="margin: 4px 0 0; font-size: 12px; color: #606060;">— How Ghanaian Are You? • Nezer&Friends</p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Kente bottom border -->
          <tr>
            <td style="height: 6px; background: <?php echo $green; ?>;"></td>
          </tr>
          <tr>
            <td style="height: 6px; background: <?php echo $gold; ?>;"></td>
          </tr>
          <tr>
            <td style="height: 6px; background: <?php echo $red; ?>;"></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
