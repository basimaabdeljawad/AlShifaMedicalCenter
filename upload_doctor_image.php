<?php
// ══════════════════════════════════════════════════════
//  upload_doctor_image.php
//  يُستدعى عند تعديل طبيب + رفع صورة جديدة
// ══════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host='localhost'; $db='dataweb'; $user='root'; $pass=''; $charset='utf8mb4';
try {
    $pdo=new PDO("mysql:host=$host;dbname=$db;charset=$charset",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
}catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'خطأ في الاتصال']); exit;
}

define('UPLOAD_DIR',__DIR__.'/uploads/doctors/');
define('UPLOAD_URL','uploads/doctors/');
if(!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0755,true);

$doctor_id=(int)($_POST['doctor_id']??0);
if(!$doctor_id){echo json_encode(['success'=>false,'message'=>'doctor_id مطلوب']); exit;}

// جلب الصورة الحالية
$cur=$pdo->prepare("SELECT image FROM doctors WHERE doctor_id=?");
$cur->execute([$doctor_id]);
$ex=$cur->fetch();
if(!$ex){echo json_encode(['success'=>false,'message'=>'الطبيب غير موجود']); exit;}

$imageName=$ex['image'];

// رفع الصورة الجديدة
if(!empty($_FILES['image'])&&$_FILES['image']['error']!==UPLOAD_ERR_NO_FILE){
    $allowed=['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    $f=$_FILES['image'];
    if($f['error']!==UPLOAD_ERR_OK){echo json_encode(['success'=>false,'message'=>'خطأ في رفع الملف']); exit;}
    if(!in_array($f['type'],$allowed)){echo json_encode(['success'=>false,'message'=>'صيغة غير مدعومة']); exit;}
    if($f['size']>5*1024*1024){echo json_encode(['success'=>false,'message'=>'الصورة أكبر من 5MB']); exit;}
    $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
    $newName='doctor_'.uniqid().'_'.time().'.'.$ext;
    if(!move_uploaded_file($f['tmp_name'],UPLOAD_DIR.$newName)){
        echo json_encode(['success'=>false,'message'=>'فشل حفظ الصورة']); exit;
    }
    // حذف القديمة
    if($imageName&&file_exists(UPLOAD_DIR.$imageName)) unlink(UPLOAD_DIR.$imageName);
    $imageName=$newName;
}

// تحديث باقي الحقول
$full_name=trim($_POST['full_name']??'');
$specialty_id=(int)($_POST['specialty_id']??0);
$email=trim($_POST['email']??'');
$phone=trim($_POST['phone']??'');
$bio=trim($_POST['bio']??'');

$pdo->prepare("UPDATE doctors SET full_name=?,specialty_id=?,email=?,phone=?,bio=?,image=? WHERE doctor_id=?")
    ->execute([$full_name,$specialty_id,$email,$phone,$bio,$imageName,$doctor_id]);

echo json_encode([
    'success'  => true,
    'data'     => ['image_url'=>$imageName?UPLOAD_URL.$imageName:null],
    'message'  => 'تم تعديل الطبيب'
]);