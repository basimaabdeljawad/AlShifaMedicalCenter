<?php
// ─────────────────────────────────────────
//  Login.php  —  تسجيل الدخول
// ─────────────────────────────────────────
require_once 'db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'طريقة غير مسموحة', [], 405);
}

$d = body();
$username = trim($d['username'] ?? '');
$password = trim($d['password'] ?? '');

if (!$username || !$password) {
    json_out(false, 'أدخل اسم المستخدم وكلمة المرور');
}

$st = db()->prepare('SELECT * FROM users WHERE username = ? AND status = "active"');
$st->execute([$username]);
$user = $st->fetch();

// قبول كلمة المرور النصية العادية (كما في بياناتك الحالية 123456)
// أو المشفّرة بـ password_hash
$valid = $user && (
        $user['password'] === $password ||
        password_verify($password, $user['password'])
    );

if (!$valid) {
    json_out(false, 'اسم المستخدم أو كلمة المرور غير صحيحة', [], 401);
}

unset($user['password']);

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['role']    = $user['role'];
$_SESSION['username'] = $user['username'] ?? $username;

// لو الدور طبيب، جيب doctor_id من جدول doctors
if ($user['role'] === 'doctor') {
    $dr = db()->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
    $dr->execute([$user['user_id']]);
    $doc = $dr->fetch();

    $_SESSION['doctor_id'] = $doc ? (int)$doc['doctor_id'] : 0;
    $user['doctor_id'] = $_SESSION['doctor_id'];
} else {
    $_SESSION['doctor_id'] = 0;
    $user['doctor_id'] = 0;
}

$user['username'] = $_SESSION['username'];

$_SESSION['user'] = $user;

json_out(true, 'تم تسجيل الدخول', $user);