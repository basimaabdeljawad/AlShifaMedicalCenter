<?php
/**
 * send_reply.php — مركز الشفاء الطبي
 * يستخدم PHPMailer مع Gmail SMTP لإرسال الردود
 *
 * ⚙️ إعداد PHPMailer:
 *   composer require phpmailer/phpmailer
 * أو حمّل الملفات يدوياً من:
 *   https://github.com/PHPMailer/PHPMailer
 */

// ===== إعدادات Gmail =====
define('GMAIL_USER', 'basimaabdjawad@gmail.com');   // ← بدّل هذا بإيميلك
define('GMAIL_PASS', 'salg uuid yfht jzkd');     // ← App Password (مش كلمة سر Gmail العادية)
define('FROM_NAME',  'مركز الشفاء الطبي');

/*
 * كيف تحصل على App Password في Gmail:
 * 1. فعّل التحقق بخطوتين: myaccount.google.com/security
 * 2. روح: myaccount.google.com/apppasswords
 * 3. اختر "Mail" واضغط Generate
 * 4. انسخ الكود المكوّن من 16 حرف والصقه أعلاه
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// قبول POST فقط
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// قراءة البيانات
$data = json_decode(file_get_contents('php://input'), true);

$to_email = trim($data['to_email'] ?? '');
$to_name  = trim($data['to_name']  ?? '');
$subject  = trim($data['subject']  ?? '');
$body     = trim($data['body']     ?? '');

// تحقق من البيانات
if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح']);
    exit;
}
if (!$subject || !$body) {
    echo json_encode(['success' => false, 'message' => 'الموضوع والرسالة مطلوبان']);
    exit;
}

// ===== تحميل PHPMailer =====
// المسار إذا استخدمت composer:
$autoload = __DIR__ . '/vendor/autoload.php';
// المسار إذا حمّلت يدوياً:
$manual   = __DIR__ . '/PHPMailer/src/';

if (file_exists($autoload)) {
    require $autoload;
} elseif (file_exists($manual . 'PHPMailer.php')) {
    require $manual . 'Exception.php';
    require $manual . 'PHPMailer.php';
    require $manual . 'SMTP.php';
} else {
    echo json_encode([
        'success' => false,
        'message' => 'PHPMailer غير موجود. ثبّته عبر: composer require phpmailer/phpmailer'
    ]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // إعدادات السيرفر
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = GMAIL_USER;
    $mail->Password   = GMAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // المرسِل والمستلم
    $mail->setFrom(GMAIL_USER, FROM_NAME);
    $mail->addAddress($to_email, $to_name);
    $mail->addReplyTo(GMAIL_USER, FROM_NAME);

    // محتوى الإيميل (HTML)
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = buildEmailHTML($to_name, $body, $subject);
    $mail->AltBody = strip_tags($body); // نسخة نصية

    $mail->send();

    // حفظ الرد في ملف log (اختياري)
    logReply($to_email, $to_name, $subject, $body);

    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال الرد بنجاح إلى ' . $to_email
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'فشل الإرسال: ' . $mail->ErrorInfo
    ]);
}

// ===== قالب HTML للإيميل =====
function buildEmailHTML($name, $body, $subject) {
    $body_html = nl2br(htmlspecialchars($body));
    return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f4f7f6; margin:0; padding:0; }
  .wrap { max-width:600px; margin:30px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
  .header { background:linear-gradient(135deg,#064f4f,#0a6e6e); padding:28px 32px; text-align:center; }
  .header h1 { color:#fff; margin:0; font-size:20px; }
  .header p  { color:rgba(255,255,255,0.7); margin:6px 0 0; font-size:13px; }
  .body { padding:32px; }
  .greeting { font-size:16px; font-weight:bold; color:#1a2e35; margin-bottom:16px; }
  .message { font-size:14px; line-height:1.9; color:#444; background:#f9fbfb; border-right:4px solid #0a6e6e; padding:16px 20px; border-radius:8px; }
  .footer { background:#f4f7f6; padding:20px 32px; text-align:center; font-size:12px; color:#999; border-top:1px solid #eee; }
  .footer a { color:#0a6e6e; text-decoration:none; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🏥 مركز الشفاء الطبي</h1>
    <p>رد على استفسارك: {$subject}</p>
  </div>
  <div class="body">
    <div class="greeting">السلام عليكم {$name}،</div>
    <div class="message">{$body_html}</div>
    <p style="font-size:13px;color:#888;margin-top:24px">شكراً لتواصلك معنا. نسعد بخدمتك دائماً.</p>
  </div>
  <div class="footer">
    مركز الشفاء الطبي &nbsp;|&nbsp;
    <a href="tel:+970599000000">0599-000-000</a>
    <br><small>هذا البريد أُرسل تلقائياً، يمكنك الرد عليه مباشرة.</small>
  </div>
</div>
</body>
</html>
HTML;
}

// ===== تسجيل الردود (اختياري) =====
function logReply($email, $name, $subject, $body) {
    $log_file = __DIR__ . '/reply_log.txt';
    $entry = sprintf(
        "[%s] إلى: %s <%s> | الموضوع: %s\n",
        date('Y-m-d H:i:s'), $name, $email, $subject
    );
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}
?>