<?php
// ══════════════════════════════════════════════════════
//  get_doctors.php — API للصفحة العامة (الزوار)
//  يُرجع الأطباء مع إمكانية الفلترة بالاسم والتخصص
// ══════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host='localhost'; $db='dataweb'; $user='root'; $pass=''; $charset='utf8mb4';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'خطأ في الاتصال']); exit;
}

define('UPLOAD_URL','uploads/doctors/');

$w=[];$p=[];

// فلتر الاسم
if(!empty($_GET['name'])){
    $w[]='d.full_name LIKE :name';
    $p[':name']='%'.trim($_GET['name']).'%';
}

// فلتر التخصص (يقبل اسم أو ID)
if(!empty($_GET['specialty'])){
    $sp=trim($_GET['specialty']);
    if(is_numeric($sp)){
        $w[]='d.specialty_id=:spid';
        $p[':spid']=(int)$sp;
    } else {
        $w[]='s.specialty_name LIKE :spname';
        $p[':spname']='%'.$sp.'%';
    }
}

$sql="SELECT d.doctor_id, d.full_name, s.specialty_name, d.email, d.phone, d.bio, d.image
      FROM doctors d
      JOIN specializations s ON d.specialty_id=s.specialty_id
      JOIN users u ON d.user_id=u.user_id
      WHERE u.status='active'
      ".($w?' AND '.implode(' AND ',$w):'')."
      ORDER BY d.doctor_id ASC";

$stmt=$pdo->prepare($sql);
$stmt->execute($p);
$rows=$stmt->fetchAll();

foreach($rows as &$r){
    $r['image_url']=$r['image'] ? UPLOAD_URL.$r['image'] : null;
}

// جلب كل التخصصات للـ dropdown
$specs=$pdo->query("SELECT specialty_id, specialty_name FROM specializations ORDER BY specialty_name")->fetchAll();

echo json_encode([
    'success'         => true,
    'doctors'         => $rows,
    'specializations' => $specs
]);