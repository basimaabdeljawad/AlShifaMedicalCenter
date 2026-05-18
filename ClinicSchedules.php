<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        require_role('admin');

        $sql = "
            SELECT
                cs.schedule_id,
                cs.doctor_id,
                d.full_name AS doctor_name,
                cs.clinic_days,
                cs.clinic_hours,
                cs.clinic_location
            FROM clinic_schedules cs
            JOIN doctors d ON d.doctor_id = cs.doctor_id
            ORDER BY cs.schedule_id DESC
        ";

        $rows = db()->query($sql)->fetchAll();
        json_out(true, '', $rows);
        exit;
    }

    if ($method === 'POST') {
        require_role('admin');

        $d = body();

        $doctor_id = intval($d['doctor_id'] ?? 0);
        $clinic_days = trim($d['clinic_days'] ?? '');
        $clinic_hours = trim($d['clinic_hours'] ?? '');
        $clinic_location = trim($d['clinic_location'] ?? '');

        if ($doctor_id <= 0 || $clinic_days === '' || $clinic_hours === '' || $clinic_location === '') {
            json_out(false, 'جميع بيانات دوام العيادة مطلوبة');
            exit;
        }

        $check = db()->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ?");
        $check->execute([$doctor_id]);

        if (!$check->fetch()) {
            json_out(false, 'الطبيب غير موجود في قاعدة البيانات');
            exit;
        }

        $stmt = db()->prepare("
            INSERT INTO clinic_schedules 
            (doctor_id, clinic_days, clinic_hours, clinic_location)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $doctor_id,
            $clinic_days,
            $clinic_hours,
            $clinic_location
        ]);

        json_out(true, 'تمت إضافة دوام العيادة بنجاح', [
            'schedule_id' => db()->lastInsertId()
        ]);
        exit;
    }

    if ($method === 'PUT') {
        require_role('admin');

        $d = body();

        $schedule_id = intval($d['schedule_id'] ?? 0);
        $doctor_id = intval($d['doctor_id'] ?? 0);
        $clinic_days = trim($d['clinic_days'] ?? '');
        $clinic_hours = trim($d['clinic_hours'] ?? '');
        $clinic_location = trim($d['clinic_location'] ?? '');

        if ($schedule_id <= 0 || $doctor_id <= 0 || $clinic_days === '' || $clinic_hours === '' || $clinic_location === '') {
            json_out(false, 'جميع بيانات دوام العيادة مطلوبة');
            exit;
        }

        $stmt = db()->prepare("
            UPDATE clinic_schedules
            SET doctor_id = ?, clinic_days = ?, clinic_hours = ?, clinic_location = ?
            WHERE schedule_id = ?
        ");

        $stmt->execute([
            $doctor_id,
            $clinic_days,
            $clinic_hours,
            $clinic_location,
            $schedule_id
        ]);

        json_out(true, 'تم تعديل دوام العيادة بنجاح');
        exit;
    }

    if ($method === 'DELETE') {
        require_role('admin');

        $d = body();
        $schedule_id = intval($d['schedule_id'] ?? 0);

        if ($schedule_id <= 0) {
            json_out(false, 'رقم الدوام مطلوب للحذف');
            exit;
        }

        $stmt = db()->prepare("DELETE FROM clinic_schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);

        json_out(true, 'تم حذف دوام العيادة بنجاح');
        exit;
    }

    json_out(false, 'طريقة الطلب غير مدعومة', [], 405);
    exit;

} catch (Throwable $e) {
    json_out(false, 'خطأ من السيرفر: ' . $e->getMessage(), [], 500);
    exit;
}