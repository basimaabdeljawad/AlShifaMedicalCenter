<?php
// save_appointment.php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, string $msg): never {
    echo json_encode(['success' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'طريقة الطلب غير صحيحة.');

$pdo = db();

// ── استلام البيانات ────────────────────────────────────────────────────────
$patientName  = trim($_POST['patient_name']  ?? '');
$patientPhone = trim($_POST['patient_phone'] ?? '');
$patientDob   = trim($_POST['patient_dob']   ?? ''); // تم إضافة استلام تاريخ الميلاد
$gender       = trim($_POST['gender']        ?? '');
$notes        = trim($_POST['notes']         ?? '');
$apptDate     = trim($_POST['date']          ?? '');
$apptTime     = trim($_POST['time']          ?? '');
$doctorId     = (int)($_POST['doctor_id']    ?? 0);
$doctorName   = trim($_POST['doctor']        ?? '');

// ── تحقق ──────────────────────────────────────────────────────────────────
if (!$patientName)  respond(false, 'الاسم الكامل مطلوب.');
if (!$patientPhone) respond(false, 'رقم الهاتف مطلوب.');
if (!$patientDob)   respond(false, 'تاريخ الميلاد مطلوب.'); // تحقق من تاريخ الميلاد
if (!in_array($gender, ['male','female'])) respond(false, 'يرجى اختيار الجنس.');
if (!$apptDate)     respond(false, 'يرجى تحديد تاريخ الموعد.');
if (!$apptTime)     respond(false, 'يرجى اختيار وقت الموعد.');
if (strtotime($apptDate) < strtotime(date('Y-m-d'))) respond(false, 'لا يمكن الحجز في تاريخ ماضٍ.');

// ── إيجاد الطبيب ──────────────────────────────────────────────────────────
$resolvedDoctorId = null;

if ($doctorId > 0) {
    $s = $pdo->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ? LIMIT 1");
    $s->execute([$doctorId]);
    if ($r = $s->fetch()) $resolvedDoctorId = $r['doctor_id'];
}

if (!$resolvedDoctorId && $doctorName) {
    $s = $pdo->prepare("SELECT doctor_id FROM doctors WHERE full_name = ? LIMIT 1");
    $s->execute([$doctorName]);
    if (!($r = $s->fetch())) {
        $s = $pdo->prepare("SELECT doctor_id FROM doctors WHERE full_name LIKE ? LIMIT 1");
        $s->execute(["%{$doctorName}%"]);
        $r = $s->fetch();
    }
    if ($r) $resolvedDoctorId = $r['doctor_id'];
}

if (!$resolvedDoctorId) {
    $r = $pdo->query("SELECT doctor_id FROM doctors LIMIT 1")->fetch();
    if ($r) $resolvedDoctorId = $r['doctor_id'];
}

if (!$resolvedDoctorId) respond(false, 'لا يوجد أطباء مسجلون. تواصل مع الإدارة.');

// ── تحقق من التعارض ────────────────────────────────────────────────────────
$timeForDB = strlen($apptTime) === 5 ? $apptTime . ':00' : $apptTime;
$chk = $pdo->prepare("
    SELECT appointment_id FROM appointments
    WHERE doctor_id=? AND appointment_date=? AND appointment_time=?
      AND status NOT IN ('cancelled','rejected') LIMIT 1
");
$chk->execute([$resolvedDoctorId, $apptDate, $timeForDB]);
if ($chk->fetch()) respond(false, 'هذا الوقت محجوز مسبقاً، يرجى اختيار وقت آخر.');

// ── إيجاد المريض أو إنشاؤه ────────────────────────────────────────────────
$s = $pdo->prepare("SELECT patient_id FROM patients WHERE full_name=? AND phone=? LIMIT 1");
$s->execute([$patientName, $patientPhone]);
$patient = $s->fetch();

if ($patient) {
    $patientId = $patient['patient_id'];
    // تحديث تاريخ الميلاد إذا كان NULL في السجل القديم
    $pdo->prepare("UPDATE patients SET date_of_birth=? WHERE patient_id=? AND date_of_birth IS NULL")
        ->execute([$patientDob, $patientId]);
} else {
    // إدخال المريض الجديد مع تاريخ الميلاد في عمود date_of_birth
    $pdo->prepare("INSERT INTO patients (user_id,full_name,phone,gender,date_of_birth) VALUES (NULL,?,?,?,?)")
        ->execute([$patientName, $patientPhone, $gender, $patientDob]);
    $patientId = (int)$pdo->lastInsertId();
}

// ── رفع الملف الطبي ────────────────────────────────────────────────────────
$medicalFile = null;
if (!empty($_FILES['medical_reports']['name'][0]) && $_FILES['medical_reports']['error'][0] === UPLOAD_ERR_OK) {
    $dir = __DIR__ . '/uploads/medical_files/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmp  = $_FILES['medical_reports']['tmp_name'][0];
    $size = $_FILES['medical_reports']['size'][0];
    if ($size > 5*1024*1024) respond(false, 'حجم الملف يتجاوز 5MB.');
    $fi   = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fi, $tmp); finfo_close($fi);
    if (!in_array($mime, ['application/pdf','image/jpeg','image/png'])) respond(false, 'نوع الملف غير مدعوم.');
    $ext  = pathinfo($_FILES['medical_reports']['name'][0], PATHINFO_EXTENSION);
    $name = 'med_'.$patientId.'_'.time().'.'.$ext;
    move_uploaded_file($tmp, $dir.$name);
    $medicalFile = 'uploads/medical_files/'.$name;
}

// ── حفظ الموعد ────────────────────────────────────────────────────────────
try {
    $pdo->prepare("
        INSERT INTO appointments (patient_id,doctor_id,appointment_date,appointment_time,notes,status,medical_file)
        VALUES (?,?,?,?,?,'pending',?)
    ")->execute([$patientId, $resolvedDoctorId, $apptDate, $timeForDB, $notes?:null, $medicalFile]);

    respond(true, 'تم إرسال طلب الحجز بنجاح! سيتم التواصل معك لتأكيد الموعد.');
} catch (PDOException $e) {
    respond(false, 'حدث خطأ أثناء الحفظ، يرجى المحاولة مجدداً.');
}
