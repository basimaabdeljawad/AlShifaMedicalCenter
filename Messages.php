<?php
// ─────────────────────────────────────────
//  Messages.php  —  الرسائل
// ─────────────────────────────────────────

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: جلب الرسائل للأدمن وموظف الاستقبال ──
if ($method === 'GET') {
    require_role('admin', 'receptionist');

    $rows = db()
        ->query('SELECT * FROM contact_messages ORDER BY created_at DESC')
        ->fetchAll();

    json_out(true, '', $rows);
    exit;
}

// ── POST: إرسال رسالة من صفحة تواصل معنا العامة ──
// بدون require_role لأن الزائر غير مسجل دخول
if ($method === 'POST') {
    $d = body();

    $full_name = trim($d['full_name'] ?? '');
    $email     = trim($d['email'] ?? '');
    $phone     = trim($d['phone'] ?? '');
    $subject   = trim($d['subject'] ?? '');
    $message   = trim($d['message'] ?? '');

    if ($full_name === '' || $message === '') {
        json_out(false, 'الاسم والرسالة مطلوبان');
        exit;
    }

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

    json_out(true, 'تم إرسال رسالتك، سنرد عليك قريباً ✅');
    exit;
}

// ── DELETE: حذف رسالة من لوحة التحكم ──
if ($method === 'DELETE') {
    require_role('admin', 'receptionist');

    $d = body();

    if (empty($d['message_id'])) {
        json_out(false, 'message_id مطلوب');
        exit;
    }

    db()
        ->prepare('DELETE FROM contact_messages WHERE message_id = ?')
        ->execute([$d['message_id']]);

    json_out(true, 'تم حذف الرسالة ✅');
    exit;
}

json_out(false, 'طريقة الطلب غير مدعومة');
exit;