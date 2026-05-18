<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode([
        "success" => false,
        "message" => "لم يتم استقبال البيانات"
    ]);
    exit;
}

$full_name = trim($input['full_name'] ?? '');
$phone     = trim($input['phone'] ?? '');
$email     = trim($input['email'] ?? '');
$subject   = trim($input['subject'] ?? '');
$message   = trim($input['message'] ?? '');

if ($full_name === '' || $message === '') {
    echo json_encode([
        "success" => false,
        "message" => "الاسم والرسالة مطلوبان"
    ]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO contact_messages 
    (full_name, email, phone, subject, message)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ في تحضير الاستعلام"
    ]);
    exit;
}

$stmt->bind_param("sssss", $full_name, $email, $phone, $subject, $message);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "تم إرسال الرسالة بنجاح"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "فشل حفظ الرسالة"
    ]);
}

$stmt->close();
$conn->close();
?>