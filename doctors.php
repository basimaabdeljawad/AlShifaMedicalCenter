<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host='localhost'; $db='dataweb'; $user='root'; $pass=''; $charset='utf8mb4';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'خطأ في الاتصال: '.$e->getMessage()]); exit;
}

define('UPLOAD_DIR', __DIR__.'/uploads/doctors/');
define('UPLOAD_URL', 'uploads/doctors/');
if(!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0755,true);

function uploadImage(array $f):array{
    $ok=['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    if($f['error']!==UPLOAD_ERR_OK)   return['success'=>false,'message'=>'خطأ في رفع الملف'];
    if(!in_array($f['type'],$ok))     return['success'=>false,'message'=>'صيغة غير مدعومة'];
    if($f['size']>5*1024*1024)        return['success'=>false,'message'=>'الصورة أكبر من 5MB'];
    $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
    $name='doctor_'.uniqid().'_'.time().'.'.$ext;
    if(!move_uploaded_file($f['tmp_name'],UPLOAD_DIR.$name)) return['success'=>false,'message'=>'فشل حفظ الصورة'];
    return['success'=>true,'filename'=>$name];
}
function deleteOldImage(?string $f):void{
    if($f&&file_exists(UPLOAD_DIR.$f)) unlink(UPLOAD_DIR.$f);
}

// GET
if($_SERVER['REQUEST_METHOD']==='GET'){
    $w=[];$p=[];
    if(!empty($_GET['specialty_id'])){$w[]='d.specialty_id=:sid';$p[':sid']=(int)$_GET['specialty_id'];}
    if(!empty($_GET['q'])){$w[]='d.full_name LIKE :q';$p[':q']='%'.$_GET['q'].'%';}
    $sql="SELECT d.doctor_id,d.full_name,d.specialty_id,s.specialty_name,d.email,d.phone,d.bio,d.image,d.user_id,u.username,u.status
          FROM doctors d JOIN specializations s ON d.specialty_id=s.specialty_id JOIN users u ON d.user_id=u.user_id
          ".($w?'WHERE '.implode(' AND ',$w):'')." ORDER BY d.doctor_id DESC";
    $stmt=$pdo->prepare($sql); $stmt->execute($p); $rows=$stmt->fetchAll();
    foreach($rows as &$r){ $r['image_url']=$r['image']?UPLOAD_URL.$r['image']:null; }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

// POST
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username=trim($_POST['username']??'');$password=trim($_POST['password']??'');
    $full_name=trim($_POST['full_name']??'');$specialty_id=(int)($_POST['specialty_id']??0);
    $email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$bio=trim($_POST['bio']??'');
    if(!$username||!$password||!$full_name||!$specialty_id){
        echo json_encode(['success'=>false,'message'=>'اسم المستخدم وكلمة المرور والاسم والتخصص مطلوبة']); exit;
    }
    $chk=$pdo->prepare("SELECT user_id FROM users WHERE username=?"); $chk->execute([$username]);
    if($chk->fetch()){echo json_encode(['success'=>false,'message'=>'اسم المستخدم مستخدم مسبقاً']); exit;}
    $imageName=null;
    if(!empty($_FILES['image'])&&$_FILES['image']['error']!==UPLOAD_ERR_NO_FILE){
        $up=uploadImage($_FILES['image']);
        if(!$up['success']){echo json_encode(['success'=>false,'message'=>$up['message']]); exit;}
        $imageName=$up['filename'];
    }
    try{
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO users(username,password,role,status) VALUES(?,?,?,?)")
            ->execute([$username,$password,'doctor','active']);
        $uid=$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO doctors(user_id,full_name,specialty_id,email,phone,bio,image) VALUES(?,?,?,?,?,?,?)")
            ->execute([$uid,$full_name,$specialty_id,$email,$phone,$bio,$imageName]);
        $did=$pdo->lastInsertId();
        $pdo->commit();
        echo json_encode(['success'=>true,'data'=>['doctor_id'=>$did,'image_url'=>$imageName?UPLOAD_URL.$imageName:null],'message'=>'تم إضافة الطبيب']);
    }catch(Exception $e){
        $pdo->rollBack(); if($imageName)deleteOldImage($imageName);
        echo json_encode(['success'=>false,'message'=>'خطأ: '.$e->getMessage()]);
    }
    exit;
}

// PUT
if($_SERVER['REQUEST_METHOD']==='PUT'){
    $body=json_decode(file_get_contents('php://input'),true)??[];
    $doctor_id=(int)($body['doctor_id']??0);$full_name=trim($body['full_name']??'');
    $specialty_id=(int)($body['specialty_id']??0);$email=trim($body['email']??'');
    $phone=trim($body['phone']??'');$bio=trim($body['bio']??'');$imageText=trim($body['image']??'');
    if(!$doctor_id||!$full_name||!$specialty_id){
        echo json_encode(['success'=>false,'message'=>'doctor_id والاسم والتخصص مطلوبة']); exit;
    }
    $cur=$pdo->prepare("SELECT image FROM doctors WHERE doctor_id=?"); $cur->execute([$doctor_id]);
    $ex=$cur->fetch();
    if(!$ex){echo json_encode(['success'=>false,'message'=>'الطبيب غير موجود']); exit;}
    $imageName=$imageText?:$ex['image'];
    $pdo->prepare("UPDATE doctors SET full_name=?,specialty_id=?,email=?,phone=?,bio=?,image=? WHERE doctor_id=?")
        ->execute([$full_name,$specialty_id,$email,$phone,$bio,$imageName,$doctor_id]);
    echo json_encode(['success'=>true,'data'=>['image_url'=>$imageName?UPLOAD_URL.$imageName:null],'message'=>'تم تعديل الطبيب']);
    exit;
}

// DELETE
if($_SERVER['REQUEST_METHOD']==='DELETE'){
    $body=json_decode(file_get_contents('php://input'),true)??[];
    $doctor_id=(int)($body['doctor_id']??0);
    if(!$doctor_id){echo json_encode(['success'=>false,'message'=>'doctor_id مطلوب']); exit;}
    $stmt=$pdo->prepare("SELECT user_id,image FROM doctors WHERE doctor_id=?"); $stmt->execute([$doctor_id]);
    $doc=$stmt->fetch();
    if(!$doc){echo json_encode(['success'=>false,'message'=>'الطبيب غير موجود']); exit;}
    try{
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM appointments WHERE doctor_id=?")->execute([$doctor_id]);
        $pdo->prepare("DELETE FROM doctors WHERE doctor_id=?")->execute([$doctor_id]);
        $pdo->prepare("DELETE FROM users WHERE user_id=?")->execute([$doc['user_id']]);
        $pdo->commit(); deleteOldImage($doc['image']);
        echo json_encode(['success'=>true,'message'=>'تم حذف الطبيب']);
    }catch(Exception $e){
        $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'خطأ: '.$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'طريقة الطلب غير مدعومة']);