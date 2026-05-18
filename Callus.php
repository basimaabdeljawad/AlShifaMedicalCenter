<?php
// الاتصال بقاعدة البيانات
require_once 'db.php';

$success = '';
$error = '';

// عند الضغط على زر إرسال الرسالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $subject   = trim($_POST['subject'] ?? '');
    $message   = trim($_POST['message'] ?? '');

    if ($full_name === '' || $message === '') {
        $error = 'الاسم والرسالة مطلوبان';
    } else {
        try {
            $stmt = db()->prepare("
INSERT INTO contact_messages
(full_name, email, phone, subject, message)
VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
$full_name,
$email ?: null,
$phone ?: null,
$subject ?: null,
$message
]);

$success = 'تم إرسال رسالتك بنجاح ✅';

} catch (Exception $e) {
$error = 'حدث خطأ أثناء حفظ الرسالة';
}
}
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/callus.css">
  <link rel="stylesheet" href="styles/myst.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="styles/homeb.css">

  <title>تواصل معنا</title>
</head>

<body>

<div id="header-placeholder"></div>

<div class="container">

  <!-- HEADER -->
  <div class="header">
    <h1>تواصل معنا</h1>
    <p>
      في مركز الشفاء الطبي نرحب باستفساراتكم ورسائلكم بكل اهتمام.
      يمكنكم التواصل معنا للحجز، الاستفسار، أو طلب أي معلومة تخص خدماتنا الطبية،
      وسيسعد فريقنا بخدمتكم ومتابعتكم بأسرع وقت ممكن.
    </p>
  </div>

  <!-- GRID -->
  <div class="grid">

    <!-- FORM -->
    <div class="form-card">
      <h2>أرسل رسالتك</h2>

      <?php if ($success): ?>
      <div style="background:#e8f9ee;color:#16803c;padding:12px;border-radius:10px;margin-bottom:15px;text-align:center;font-weight:600;">
        <?php echo $success; ?>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div style="background:#fdeaea;color:#b42318;padding:12px;border-radius:10px;margin-bottom:15px;text-align:center;font-weight:600;">
        <?php echo $error; ?>
      </div>
      <?php endif; ?>

      <form class="form" method="POST" action="callus.php">

        <div class="row">
          <input
                  type="text"
                  name="full_name"
                  placeholder="الاسم الكامل"
                  required
          >

          <input
                  type="tel"
                  name="phone"
                  placeholder="رقم الهاتف"
          >
        </div>

        <div class="row">
          <input
                  type="email"
                  name="email"
                  placeholder="البريد الإلكتروني"
          >

          <input
                  type="text"
                  name="subject"
                  placeholder="عنوان الرسالة"
          >
        </div>

        <textarea
                name="message"
                placeholder="اكتب رسالتك هنا..."
                required
        ></textarea>

        <button type="submit">إرسال الرسالة</button>

      </form>
    </div>

    <!-- SIDE -->
    <div class="side">

      <div class="card">
        <h3>معلومات التواصل</h3>
        <div class="info">
          <div>📍 نابلس - شارع سفيان</div>
          <div>📞 +970 599 123 456</div>
          <div>✉️ info@alshifa-medical.com</div>
          <div>💬 واتساب متاح</div>
        </div>
      </div>

      <div class="card">
        <h3>أوقات الدوام</h3>
        <div class="info">
          <div>السبت - الخميس: 08:00 ص - 08:00 م</div>
          <div>الجمعة: إجازة</div>
        </div>
      </div>

    </div>

  </div>

  <!-- MAP TITLE -->
  <div class="map-title">
    <h2>موقعنا على الخريطة</h2>
  </div>

  <!-- MAP -->
  <div class="map">
    <iframe src="https://maps.google.com/maps?q=Soufian%20Street%20Nablus&t=&z=15&ie=UTF8&iwloc=&output=embed"></iframe>
  </div>

</div>

<div id="footer-placeholder"></div>

<script src="script.js"></script>

</body>
</html>