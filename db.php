<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'dataweb');
define('DB_USER', 'root');
define('DB_PASS', '');

function db() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS
            );

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

function json_out($ok, $msg = '', $data = array(), $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => $ok,
        'message' => $msg,
        'data' => $data
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

function body() {
    $data = json_decode(file_get_contents('php://input'), true);
    return $data ? $data : array();
}

function require_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user'])) {
        json_out(false, 'غير مسجّل — سجّل دخولك أولاً', array(), 401);
    }

    return $_SESSION['user'];
}

function require_role() {
    $roles = func_get_args();
    $u = require_login();

    if (!in_array($u['role'], $roles)) {
        json_out(false, 'ليس لديك صلاحية', array(), 403);
    }

    return $u;
}

// توافق مع الملفات اللي تستخدم getDB()
function getDB() { return db(); }
