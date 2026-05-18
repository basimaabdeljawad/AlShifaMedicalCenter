<?php
// Appointments.php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

/* =========================
   دوال مساعدة
========================= */

function send_json($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function get_body_data() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (is_array($json)) {
        return $json;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return [];
}


$role = $_SESSION['role'] ?? 'admin';

/* =========================
   GET: جلب المواعيد
========================= */

if ($method === 'GET') {
    try {
        $where = [];
        $params = [];

        if ($role === 'doctor') {
            $doctor_id = (int)($_SESSION['doctor_id'] ?? 0);
            $where[] = "a.doctor_id = ?";
            $params[] = $doctor_id;
        }

        if (!empty($_GET['status'])) {
            $where[] = "a.status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['doctor_id'])) {
            $where[] = "a.doctor_id = ?";
            $params[] = (int)$_GET['doctor_id'];
        }

        if (!empty($_GET['date'])) {
            $where[] = "a.appointment_date = ?";
            $params[] = $_GET['date'];
        }

        if (!empty($_GET['q'])) {
            $q = '%' . $_GET['q'] . '%';
            $where[] = "(p.full_name LIKE ? OR p.phone LIKE ? OR d.full_name LIKE ?)";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $sql = "
            SELECT
                a.appointment_id,
                a.patient_id,
                a.doctor_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.notes,
                a.medical_file,
                a.created_at,

                COALESCE(p.full_name, 'مريض غير معروف') AS patient_name,
                COALESCE(p.phone, '') AS patient_phone,
                COALESCE(p.gender, '') AS gender,

                COALESCE(d.full_name, 'طبيب غير معروف') AS doctor_name,
                COALESCE(s.specialty_name, 'غير محدد') AS specialty_name

            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.patient_id
            LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON d.specialty_id = s.specialty_id
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        send_json(true, 'تم جلب المواعيد بنجاح', $appointments);

    } catch (Throwable $e) {
        send_json(false, 'خطأ في جلب المواعيد: ' . $e->getMessage(), [], 500);
    }
}

/* =========================
   POST: إضافة موعد
========================= */

if ($method === 'POST') {
    try {
        if (!in_array($role, ['admin', 'receptionist'])) {
            send_json(false, 'غير مصرح', null, 403);
        }

        $b = get_body_data();

        $patient_id = (int)($b['patient_id'] ?? 0);
        $doctor_id  = (int)($b['doctor_id'] ?? 0);
        $date       = trim($b['appointment_date'] ?? $b['date'] ?? '');
        $time       = trim($b['appointment_time'] ?? $b['time'] ?? '');
        $status     = trim($b['status'] ?? 'pending');
        $notes      = trim($b['notes'] ?? '');
        $file       = trim($b['medical_file'] ?? '');

        if (!$patient_id || !$doctor_id || !$date || !$time) {
            send_json(false, 'بيانات الموعد ناقصة');
        }

        $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'];
        if (!in_array($status, $allowed_statuses)) {
            $status = 'pending';
        }

        $check = $pdo->prepare("
            SELECT appointment_id
            FROM appointments
            WHERE doctor_id = ?
              AND appointment_date = ?
              AND appointment_time = ?
              AND status NOT IN ('cancelled', 'rejected')
            LIMIT 1
        ");
        $check->execute([$doctor_id, $date, $time]);

        if ($check->fetch()) {
            send_json(false, 'هذا الموعد محجوز بالفعل');
        }

        $stmt = $pdo->prepare("
            INSERT INTO appointments
            (patient_id, doctor_id, appointment_date, appointment_time, status, notes, medical_file)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $patient_id,
            $doctor_id,
            $date,
            $time,
            $status,
            $notes,
            $file
        ]);

        send_json(true, 'تمت إضافة الموعد بنجاح', [
            'appointment_id' => (int)$pdo->lastInsertId()
        ]);

    } catch (Throwable $e) {
        send_json(false, 'خطأ في إضافة الموعد: ' . $e->getMessage(), null, 500);
    }
}

/* =========================
   PUT: تعديل موعد
========================= */

if ($method === 'PUT') {
    try {
        if (!in_array($role, ['admin', 'receptionist'])) {
            send_json(false, 'غير مصرح', null, 403);
        }

        $b = get_body_data();

        $appointment_id = (int)($b['appointment_id'] ?? $b['id'] ?? 0);
        $patient_id     = (int)($b['patient_id'] ?? 0);
        $doctor_id      = (int)($b['doctor_id'] ?? 0);
        $date           = trim($b['appointment_date'] ?? $b['date'] ?? '');
        $time           = trim($b['appointment_time'] ?? $b['time'] ?? '');
        $status         = trim($b['status'] ?? 'pending');
        $notes          = trim($b['notes'] ?? '');
        $file           = trim($b['medical_file'] ?? '');

        if (!$appointment_id) {
            send_json(false, 'رقم الموعد مطلوب');
        }

        if (!$patient_id || !$doctor_id || !$date || !$time) {
            send_json(false, 'بيانات الموعد ناقصة');
        }

        $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'];
        if (!in_array($status, $allowed_statuses)) {
            $status = 'pending';
        }

        $stmt = $pdo->prepare("
            UPDATE appointments
            SET
                patient_id = ?,
                doctor_id = ?,
                appointment_date = ?,
                appointment_time = ?,
                status = ?,
                notes = ?,
                medical_file = ?
            WHERE appointment_id = ?
        ");

        $stmt->execute([
            $patient_id,
            $doctor_id,
            $date,
            $time,
            $status,
            $notes,
            $file,
            $appointment_id
        ]);

        send_json(true, 'تم تعديل الموعد بنجاح', [
            'appointment_id' => $appointment_id
        ]);

    } catch (Throwable $e) {
        send_json(false, 'خطأ في تعديل الموعد: ' . $e->getMessage(), null, 500);
    }
}

/* =========================
   DELETE: حذف موعد
========================= */

if ($method === 'DELETE') {
    try {
        if (!in_array($role, ['admin', 'receptionist'])) {
            send_json(false, 'غير مصرح', null, 403);
        }

        $b = get_body_data();

        $appointment_id = (int)($b['appointment_id'] ?? $b['id'] ?? 0);

        if (!$appointment_id && !empty($_GET['id'])) {
            $appointment_id = (int)$_GET['id'];
        }

        if (!$appointment_id) {
            send_json(false, 'رقم الموعد مطلوب');
        }

        $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);

        send_json(true, 'تم حذف الموعد بنجاح', [
            'appointment_id' => $appointment_id
        ]);

    } catch (Throwable $e) {
        send_json(false, 'خطأ في حذف الموعد: ' . $e->getMessage(), null, 500);
    }
}

send_json(false, 'طريقة الطلب غير مدعومة', null, 405);