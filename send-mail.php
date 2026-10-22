<?php
/**
 * El Mallahey Logistics - Contact Form Email Handler
 * Target Email: info@elmallahey.com
 * Website: https://elmallahey.com
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Read raw JSON or form-urlencoded POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

// Extract and sanitize fields
$name = '';
if (isset($data['name'])) {
    $name = trim(strip_tags((string)$data['name']));
} elseif (isset($data['fullName'])) {
    $name = trim(strip_tags((string)$data['fullName']));
} elseif (isset($data['firstName']) || isset($data['lastName'])) {
    $name = trim(strip_tags(((string)($data['firstName'] ?? '')) . ' ' . ((string)($data['lastName'] ?? ''))));
}

$email   = isset($data['email']) ? filter_var(trim((string)$data['email']), FILTER_SANITIZE_EMAIL) : '';
$subject = isset($data['subject']) ? trim(strip_tags((string)$data['subject'])) : '';
$phone   = isset($data['phone']) ? trim(strip_tags((string)$data['phone'])) : '';
$message = isset($data['message']) ? trim(strip_tags((string)$data['message'])) : '';
$lang    = (isset($data['lang']) && $data['lang'] === 'en') ? 'en' : 'ar';

// Validation
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => ($lang === 'en') ? 'Please fill in all required fields.' : 'يرجى ملء جميع الحقول المطلوبة.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => ($lang === 'en') ? 'Please enter a valid email address.' : 'صيغة البريد الإلكتروني غير صحيحة.'
    ]);
    exit;
}

// Target email settings
$toEmail = 'info@elmallahey.com';
$emailSubject = 'طلب تواصل جديد من موقع الملاحي: ' . $name . (!empty($subject) ? ' - ' . $subject : '');

// Clean phone digits for WhatsApp if provided
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);

// HTML Message Body
$emailBody = '
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طلب تواصل جديد - الملاحي</title>
</head>
<body style="font-family: Arial, Tahoma, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; direction: rtl; text-align: right;">
  <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
    
    <div style="background-color: #001f3f; padding: 25px; text-align: center; border-bottom: 4px solid #FF671F;">
      <h2 style="color: #FF671F; margin: 0; font-size: 24px; letter-spacing: 0.5px;">EL MALLAHEY LOGISTICS - الملاحي</h2>
      <p style="color: #d1d5db; margin: 6px 0 0 0; font-size: 14px;">رسالة جديدة من نموذج التواصل في الموقع الإلكتروني</p>
    </div>

    <div style="padding: 30px;">
      <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr style="border-bottom: 1px solid #e5e7eb;">
          <td style="padding: 12px; font-weight: bold; width: 35%; color: #003DA5;">الاسم بالكامل:</td>
          <td style="padding: 12px; color: #111827;">' . htmlspecialchars($name) . '</td>
        </tr>
        <tr style="border-bottom: 1px solid #e5e7eb;">
          <td style="padding: 12px; font-weight: bold; color: #003DA5;">البريد الإلكتروني:</td>
          <td style="padding: 12px; color: #111827;"><a href="mailto:' . htmlspecialchars($email) . '" style="color: #FF671F; text-decoration: none; font-weight: 600;">' . htmlspecialchars($email) . '</a></td>
        </tr>';

if (!empty($subject)) {
  $emailBody .= '
        <tr style="border-bottom: 1px solid #e5e7eb;">
          <td style="padding: 12px; font-weight: bold; color: #003DA5;">نوع الاستفسار / الخدمة:</td>
          <td style="padding: 12px; color: #111827; font-weight: 600;">' . htmlspecialchars($subject) . '</td>
        </tr>';
}

if (!empty($phone)) {
  $emailBody .= '
        <tr style="border-bottom: 1px solid #e5e7eb;">
          <td style="padding: 12px; font-weight: bold; color: #003DA5;">رقم الهاتف:</td>
          <td style="padding: 12px; color: #111827;"><a href="tel:' . htmlspecialchars($phone) . '" dir="ltr" style="color: #FF671F; text-decoration: none;">' . htmlspecialchars($phone) . '</a></td>
        </tr>';
}

$emailBody .= '
        <tr style="border-bottom: 1px solid #e5e7eb;">
          <td style="padding: 12px; font-weight: bold; color: #003DA5;">تاريخ وتوقيت الإرسال:</td>
          <td style="padding: 12px; color: #6b7280;" dir="ltr">' . date('Y-m-d H:i:s') . '</td>
        </tr>
      </table>

      <div style="background-color: #f8fafc; border-right: 4px solid #FF671F; padding: 16px; border-radius: 6px; margin-top: 10px;">
        <h4 style="margin: 0 0 10px 0; color: #001f3f; font-size: 16px;">نص الرسالة:</h4>
        <p style="margin: 0; line-height: 1.6; color: #374151; white-space: pre-line;">' . nl2br(htmlspecialchars($message)) . '</p>
      </div>

      <div style="margin-top: 25px; text-align: center;">';

if (!empty($cleanPhone)) {
  $emailBody .= '
        <a href="https://wa.me/' . $cleanPhone . '" style="display: inline-block; background-color: #25D366; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; margin-left: 10px; font-weight: bold;">
          محادثة عبر واتساب
        </a>';
}

$emailBody .= '
        <a href="mailto:' . htmlspecialchars($email) . '" style="display: inline-block; background-color: #FF671F; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: bold;">
          الرد عبر الإيميل
        </a>
      </div>
    </div>

    <div style="background-color: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb;">
      تم إرسال هذا البريد تلقائياً عبر موقع الملاحي للخدمات اللوجستية (<a href="https://elmallahey.com" style="color: #6b7280; text-decoration: none;">elmallahey.com</a>).
    </div>

  </div>
</body>
</html>
';

// Standard PHP mail headers with UTF-8 support
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=UTF-8';
$headers[] = 'From: El Mallahey Website <' . $toEmail . '>';
$headers[] = 'Reply-To: =?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
$headers[] = 'X-Mailer: PHP/' . phpversion();

// UTF-8 encoded subject
$encodedSubject = '=?UTF-8?B?' . base64_encode($emailSubject) . '?=';

// Send email
$mailSent = @mail($toEmail, $encodedSubject, $emailBody, implode("\r\n", $headers));

if ($mailSent) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => ($lang === 'en')
            ? 'Your message has been sent successfully. We will contact you soon.'
            : 'تم استلام رسالتك بنجاح! سنتواصل معك قريباً.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => ($lang === 'en')
            ? 'Could not send email via server. Please verify mail server settings.'
            : 'تعذر إرسال البريد عبر الخادم. يرجى مراجعة إعدادات البريد.'
    ]);
}
