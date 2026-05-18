<?php
// ─────────────────────────────────────────
//  Specializations.php  —  التخصصات
// ─────────────────────────────────────────
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

require_role('admin');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = db()->query('
        SELECT s.*, COUNT(d.doctor_id) AS doctor_count
        FROM specializations s
        LEFT JOIN doctors d ON d.specialty_id = s.specialty_id
        GROUP BY s.specialty_id
        ORDER BY s.specialty_id
    ')->fetchAll();
    json_out(true, '', $rows);
}

if ($method === 'POST') {
    $d = body();
    if (empty($d['specialty_name'])) json_out(false, 'اسم التخصص مطلوب');
    db()->prepare('INSERT INTO specializations (specialty_name) VALUES (?)')->execute([$d['specialty_name']]);
    json_out(true, 'تم إضافة التخصص ✅', ['specialty_id' => db()->lastInsertId()]);
}

if ($method === 'PUT') {
    $d = body();
    if (empty($d['specialty_id']) || empty($d['specialty_name'])) json_out(false, 'specialty_id و specialty_name مطلوبان');
    db()->prepare('UPDATE specializations SET specialty_name = ? WHERE specialty_id = ?')->execute([$d['specialty_name'], $d['specialty_id']]);
    json_out(true, 'تم تعديل التخصص ✅');
}

if ($method === 'DELETE') {
    $d = body();
    if (empty($d['specialty_id'])) json_out(false, 'specialty_id مطلوب');
    db()->prepare('DELETE FROM specializations WHERE specialty_id = ?')->execute([$d['specialty_id']]);
    json_out(true, 'تم حذف التخصص ✅');
}