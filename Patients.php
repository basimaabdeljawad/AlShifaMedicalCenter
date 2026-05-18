<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

function normalize_date($date) {
    $date = trim($date);

    if ($date === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
        $parts = explode('/', $date);
        return $parts[2] . '-' . $parts[0] . '-' . $parts[1];
    }

    return null;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        require_role('admin', 'receptionist');

        $rows = db()->query("
            SELECT 
                patient_id,
                full_name,
                phone,
                gender,
                date_of_birth,
                address
            FROM patients
            ORDER BY full_name ASC
        ")->fetchAll();

        json_out(true, '', $rows);
        exit;
    }

    if ($method === 'POST') {
        require_role('admin', 'receptionist');

        $d = body();

        $patient_id = trim($d['patient_id'] ?? '');
        $full_name = trim($d['full_name'] ?? '');
        $phone = trim($d['phone'] ?? '');
        $gender = trim($d['gender'] ?? '');
        $date_of_birth = normalize_date($d['date_of_birth'] ?? '');
        $address = trim($d['address'] ?? '');

        if ($patient_id === '' || $full_name === '') {
            json_out(false, 'رقم الهوية والاسم مطلوبان');
            exit;
        }

        if (!in_array($gender, ['male', 'female'])) {
            json_out(false, 'الجنس غير صحيح');
            exit;
        }

        $check = db()->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
        $check->execute([$patient_id]);

        if ($check->fetch()) {
            json_out(false, 'رقم الهوية موجود مسبقاً، لا يمكن تكراره');
            exit;
        }

        $stmt = db()->prepare("
            INSERT INTO patients
            (patient_id, full_name, phone, gender, date_of_birth, address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $patient_id,
            $full_name,
            $phone,
            $gender,
            $date_of_birth,
            $address
        ]);

        json_out(true, 'تمت إضافة المريض بنجاح');
        exit;
    }

    if ($method === 'PUT') {
        require_role('admin', 'receptionist');

        $d = body();

        $patient_id = trim($d['patient_id'] ?? '');
        $old_patient_id = trim($d['old_patient_id'] ?? '');
        $full_name = trim($d['full_name'] ?? '');
        $phone = trim($d['phone'] ?? '');
        $gender = trim($d['gender'] ?? '');
        $date_of_birth = normalize_date($d['date_of_birth'] ?? '');
        $address = trim($d['address'] ?? '');

        if ($old_patient_id === '' || $patient_id === '' || $full_name === '') {
            json_out(false, 'رقم الهوية والاسم مطلوبان');
            exit;
        }

        if (!in_array($gender, ['male', 'female'])) {
            json_out(false, 'الجنس غير صحيح');
            exit;
        }

        if ($patient_id !== $old_patient_id) {
            $check = db()->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
            $check->execute([$patient_id]);

            if ($check->fetch()) {
                json_out(false, 'رقم الهوية الجديد موجود مسبقاً');
                exit;
            }
        }

        $stmt = db()->prepare("
            UPDATE patients
            SET patient_id = ?, full_name = ?, phone = ?, gender = ?, date_of_birth = ?, address = ?
            WHERE patient_id = ?
        ");

        $stmt->execute([
            $patient_id,
            $full_name,
            $phone,
            $gender,
            $date_of_birth,
            $address,
            $old_patient_id
        ]);

        json_out(true, 'تم تعديل بيانات المريض بنجاح');
        exit;
    }

    if ($method === 'DELETE') {
        require_role('admin', 'receptionist');

        $d = body();
        $patient_id = trim($d['patient_id'] ?? '');

        if ($patient_id === '') {
            json_out(false, 'رقم الهوية مطلوب للحذف');
            exit;
        }

        $stmt = db()->prepare("DELETE FROM patients WHERE patient_id = ?");
        $stmt->execute([$patient_id]);

        json_out(true, 'تم حذف المريض بنجاح');
        exit;
    }

    json_out(false, 'طريقة الطلب غير مدعومة', [], 405);
    exit;

} catch (Throwable $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
        json_out(false, 'رقم الهوية موجود مسبقاً، لا يمكن تكراره', [], 409);
        exit;
    }

    json_out(false, 'خطأ من السيرفر: ' . $e->getMessage(), [], 500);
    exit;
}