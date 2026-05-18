<?php
// ─────────────────────────────────────────
//  Logout.php  —  تسجيل الخروج
// ─────────────────────────────────────────
session_start();
session_destroy();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج'], JSON_UNESCAPED_UNICODE);