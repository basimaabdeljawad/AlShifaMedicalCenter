<?php
// ─────────────────────────────────────────
//  Users.php  —  المستخدمون والصلاحيات
// ─────────────────────────────────────────
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

require_role('admin');
$user   = $_SESSION['user'];
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────
if ($method === 'GET') {
    $rows = db()->query('SELECT user_id, username, role, status, created_at FROM users ORDER BY user_id')->fetchAll();
    json_out(true, '', $rows);
}

// ── POST: إضافة مستخدم ───────────────────
if ($method === 'POST') {
    $d = body();
    if (empty($d['username']) || empty($d['password']) || empty($d['role'])) {
        json_out(false, 'username, password, role  مطلوبة');
    }
    // تحقق من عدم تكرار اسم المستخدم
    $exists = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $exists->execute([$d['username']]);
    if ($exists->fetchColumn() > 0) json_out(false, 'اسم المستخدم موجود مسبقاً');

    db()->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
        ->execute([$d['username'], password_hash($d['password'], PASSWORD_DEFAULT), $d['role']]);
    json_out(true, 'تم إضافة المستخدم ✅', ['user_id' => db()->lastInsertId()]);
}

// ── PUT: تعديل مستخدم ────────────────────
if ($method === 'PUT') {
    $d = body();
    if (empty($d['user_id'])) json_out(false, 'user_id مطلوب');

    $fields = [];
    $params = [];
    if (!empty($d['username'])) { $fields[] = 'username = ?'; $params[] = $d['username']; }
    if (!empty($d['password'])) { $fields[] = 'password = ?'; $params[] = password_hash($d['password'], PASSWORD_DEFAULT); }
    if (!empty($d['role']))     { $fields[] = 'role = ?';     $params[] = $d['role']; }
    if (!empty($d['status']))   { $fields[] = 'status = ?';   $params[] = $d['status']; }

    if (!$fields) json_out(false, 'لا يوجد شيء للتعديل');
    $params[] = $d['user_id'];
    db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = ?')->execute($params);
    json_out(true, 'تم تعديل المستخدم ✅');
}

// ── DELETE: حذف مستخدم ───────────────────
if ($method === 'DELETE') {
    $d = body();
    if (empty($d['user_id'])) json_out(false, 'user_id مطلوب');
    if ($d['user_id'] == $user['user_id']) json_out(false, 'لا يمكن حذف حسابك الحالي ❌', [], 403);
    db()->prepare('DELETE FROM users WHERE user_id = ?')->execute([$d['user_id']]);
    json_out(true, 'تم حذف المستخدم ✅');
}