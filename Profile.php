<?php
require_once 'db.php';


// تشغيل الـ session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = $_SESSION['user_id'] ?? null;
    $role   = $_SESSION['role']   ?? null; // استخدم الـ role من الـ session مباشرة

    if (!$userId) {
        json_out(false, 'غير مصرح', [], 401);
        exit;
    }

    if ($method === 'GET') {
        $profile = [
            'user_id'   => $userId,
            'username'  => $_SESSION['username'] ?? '',
            'role'      => $role,
            'full_name' => $_SESSION['username'] ?? '',
            'phone'     => '',
            'email'     => '',
            'address'   => '',
            'bio'       => '',
            'image_url' => '',
        ];

        if ($role === 'doctor') {
            $ds = db()->prepare("
    SELECT full_name, phone, email, bio, image
    FROM doctors WHERE user_id = ?
");
            $ds->execute([$userId]);
            $doc = $ds->fetch();

            if ($doc) {
                $profile['full_name'] = $doc['full_name'] ?: $profile['username'];
                $profile['phone']     = $doc['phone']     ?: '';
                $profile['email']     = $doc['email']     ?: '';
                $profile['bio']       = $doc['bio']       ?: '';
                $profile['image_url'] = $doc['image']     ?: ''; // image مش image_url
            }
        } else {
            // admin أو receptionist
            try {
                $ps = db()->prepare("SELECT phone, email, address, bio, image_url FROM user_profiles WHERE user_id = ?");
                $ps->execute([$userId]);
                $prof = $ps->fetch();
                if ($prof) {
                    $profile['phone']     = $prof['phone']     ?: '';
                    $profile['email']     = $prof['email']     ?: '';
                    $profile['address']   = $prof['address']   ?: '';
                    $profile['bio']       = $prof['bio']       ?: '';
                    $profile['image_url'] = $prof['image_url'] ?: '';
                }
            } catch (Throwable $e) { /* جدول غير موجود */ }
        }

        json_out(true, '', $profile);
        exit;
    }

    if ($method === 'PUT') {
        $d       = body();
        $phone   = trim($d['phone']   ?? '');
        $email   = trim($d['email']   ?? '');
        $address = trim($d['address'] ?? '');
        $bio     = trim($d['bio']     ?? '');
        $oldPw   = trim($d['old_password'] ?? '');
        $newPw   = trim($d['new_password'] ?? '');

        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                json_out(false, 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل');
                exit;
            }
            $check = db()->prepare("SELECT password FROM users WHERE user_id = ?");
            $check->execute([$userId]);
            $user = $check->fetch();
            if (!$user || !password_verify($oldPw, $user['password'])) {
                // دعم كلمات المرور النصية العادية أيضاً
                if (!$user || $user['password'] !== $oldPw) {
                    json_out(false, 'كلمة المرور الحالية غير صحيحة');
                    exit;
                }
            }
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            db()->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hash, $userId]);
        }

        if ($role === 'doctor') {
            $check = db()->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
            $check->execute([$userId]);
            if ($check->fetch()) {
                db()->prepare("UPDATE doctors SET phone=?, email=?, bio=? WHERE user_id=?")
                    ->execute([$phone, $email, $bio, $userId]);
            }
        } else {
            try {
                $check = db()->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
                $check->execute([$userId]);
                if ($check->fetch()) {
                    db()->prepare("UPDATE user_profiles SET phone=?, email=?, address=?, bio=? WHERE user_id=?")
                        ->execute([$phone, $email, $address, $bio, $userId]);
                } else {
                    db()->prepare("INSERT INTO user_profiles (user_id, phone, email, address, bio) VALUES (?,?,?,?,?)")
                        ->execute([$userId, $phone, $email, $address, $bio]);
                }
            } catch (Throwable $e) {
                db()->exec("CREATE TABLE IF NOT EXISTS user_profiles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    phone VARCHAR(20) DEFAULT '',
                    email VARCHAR(100) DEFAULT '',
                    address VARCHAR(255) DEFAULT '',
                    bio TEXT,
                    image_url VARCHAR(255) DEFAULT '',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                db()->prepare("INSERT INTO user_profiles (user_id, phone, email, address, bio) VALUES (?,?,?,?,?)")
                    ->execute([$userId, $phone, $email, $address, $bio]);
            }
        }

        json_out(true, 'تم حفظ التغييرات بنجاح');
        exit;
    }

    if ($method === 'POST') {
        if (!isset($_FILES['image'])) { json_out(false, 'لا توجد صورة'); exit; }

        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) { json_out(false, 'نوع الملف غير مدعوم'); exit; }
        if ($file['size'] > 2 * 1024 * 1024) { json_out(false, 'الحجم يتجاوز 2MB'); exit; }

        if ($role === 'doctor') {
            // صور الأطباء في مجلد doctors
            $uploadDir = __DIR__ . '/uploads/doctors/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filename = 'doctor_' . $userId . '_' . time() . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                json_out(false, 'فشل رفع الصورة'); exit;
            }

            // نخزن اسم الملف فقط في جدول doctors
            db()->prepare("UPDATE doctors SET image=? WHERE user_id=?")
                ->execute([$filename, $userId]);

            $url = 'uploads/doctors/' . $filename;

        } else {
            // صور الأدمن والاستقبال في مجلد profiles
            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                json_out(false, 'فشل رفع الصورة'); exit;
            }

            $url = 'uploads/profiles/' . $filename;

            try {
                $check = db()->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
                $check->execute([$userId]);
                if ($check->fetch()) {
                    db()->prepare("UPDATE user_profiles SET image_url=? WHERE user_id=?")
                        ->execute([$url, $userId]);
                } else {
                    db()->prepare("INSERT INTO user_profiles (user_id, image_url) VALUES (?,?)")
                        ->execute([$userId, $url]);
                }
            } catch (Throwable $e) { /* تجاهل */ }
        }

        json_out(true, 'تم رفع الصورة', ['image_url' => $url]);
        exit;
    }

    json_out(false, 'طريقة الطلب غير مدعومة', [], 405); }catch (Throwable $e) {
    json_out(false, 'خطأ: ' . $e->getMessage(), [], 500);
}