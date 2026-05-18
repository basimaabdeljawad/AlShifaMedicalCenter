<?php
// ─────────────────────────────────────────
//  Stats.php  —  الإحصائيات للوحة التحكم
// ─────────────────────────────────────────
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

require_role('admin');
$pdo = db();

$data = [
    // ── أرقام سريعة ──────────────────────
    'appointments_total'   => (int)$pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn(),
    'appointments_pending' => (int)$pdo->query('SELECT COUNT(*) FROM appointments WHERE status="pending"')->fetchColumn(),
    'doctors_total'        => (int)$pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn(),
    'patients_total'       => (int)$pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
    'messages_total'       => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),

    // ── مواعيد حسب الحالة ────────────────
    'by_status' => $pdo->query('
        SELECT status, COUNT(*) AS cnt
        FROM appointments
        GROUP BY status
    ')->fetchAll(),

    // ── مواعيد حسب التخصص ────────────────
    'by_specialty' => $pdo->query('
        SELECT s.specialty_name, COUNT(a.appointment_id) AS cnt
        FROM specializations s
        LEFT JOIN doctors      d ON d.specialty_id  = s.specialty_id
        LEFT JOIN appointments a ON a.doctor_id      = d.doctor_id
        GROUP BY s.specialty_id
        ORDER BY cnt DESC
    ')->fetchAll(),

    // ── أكثر الأطباء طلباً ───────────────
    'top_doctors' => $pdo->query('
        SELECT d.full_name, COUNT(a.appointment_id) AS cnt
        FROM doctors d
        LEFT JOIN appointments a ON a.doctor_id = d.doctor_id
        GROUP BY d.doctor_id
        ORDER BY cnt DESC
        LIMIT 5
    ')->fetchAll(),

    // ── توزيع المرضى بالجنس ──────────────
    'gender_distribution' => $pdo->query('
        SELECT gender, COUNT(*) AS cnt
        FROM patients
        GROUP BY gender
    ')->fetchAll(),
];

json_out(true, '', $data);