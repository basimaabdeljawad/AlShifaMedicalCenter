<?php
$host = 'localhost';
$db   = 'dataweb';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('<p style="text-align:center;padding:60px;color:red">خطأ في الاتصال بقاعدة البيانات</p>');
}

$doctor_id = (int)($_GET['id'] ?? 0);

if (!$doctor_id) {
    header('Location: doctor.html');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        d.doctor_id,
        d.full_name,
        d.email,
        d.phone,
        d.bio,
        d.image,
        s.specialty_name,
        cs.clinic_days,
        cs.clinic_hours,
        cs.clinic_location
    FROM doctors d
    JOIN specializations s ON d.specialty_id = s.specialty_id
    LEFT JOIN clinic_schedules cs ON cs.doctor_id = d.doctor_id
    WHERE d.doctor_id = ?
    LIMIT 1
");

$stmt->execute([$doctor_id]);
$doc = $stmt->fetch();

if (!$doc) {
    header('Location: doctor.html');
    exit;
}

$name     = htmlspecialchars($doc['full_name'] ?? '');
$spec     = htmlspecialchars($doc['specialty_name'] ?? '');
$email    = htmlspecialchars($doc['email'] ?? '');
$phone    = htmlspecialchars($doc['phone'] ?? '');
$bio      = nl2br(htmlspecialchars($doc['bio'] ?? ''));
$days     = htmlspecialchars($doc['clinic_days'] ?? 'غير محدد');
$hours    = htmlspecialchars($doc['clinic_hours'] ?? 'غير محدد');
$location = htmlspecialchars($doc['clinic_location'] ?? 'غير محدد');

$imgSrc = ($doc['image'] && file_exists(__DIR__ . '/uploads/doctors/' . $doc['image']))
    ? 'uploads/doctors/' . $doc['image']
    : 'images/default-doctor.png';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> - مركز الشفاء الطبي</title>

    <link rel="stylesheet" href="styles/myst.css">
    <link rel="stylesheet" href="styles/homeb.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        *:not(i):not(.fa):not(.fas):not(.fab):not(.fa-solid) {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            direction: rtl;
            padding-top: 80px;
        }

        .hero-section {
            position: relative;
            width: 100%;
            height: 300px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-right: 8%;
            color: white;
        }

        .blur-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('<?= $imgSrc ?>');
            background-size: cover;
            background-position: center;
            filter: blur(15px) brightness(0.5);
            transform: scale(1.08);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin: 0;
            text-shadow: 3px 3px 15px rgba(0,0,0,0.6);
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-top: 10px;
            opacity: 0.9;
        }

        .main-card {
            max-width: 1100px;
            margin: -60px auto 50px auto;
            position: relative;
            z-index: 3;
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            padding: 50px;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        .doctor-image-box {
            flex: 0 0 300px;
        }

        .doctor-img {
            width: 100%;
            height: 450px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }

        .doctor-info {
            flex: 1;
        }

        .doctor-info h2 {
            color: #1a3a5a;
            font-size: 2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .doctor-info h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 80px;
            height: 4px;
            background-color: #114aba;
            border-radius: 2px;
        }

        .bio-text {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #4a4a4a;
            margin-bottom: 35px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            background-color: #f9fbfc;
            padding: 30px;
            border-radius: 18px;
            border: 1px dashed #d1d9e6;
        }

        .grid-item {
            font-size: 1.1rem;
            color: #2c3e50;
            word-break: break-word;
        }

        .grid-item i {
            color: #114aba;
            margin-left: 8px;
            width: 20px;
            text-align: center;
        }

        .grid-item span {
            display: block;
            color: #114aba;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .hero-section {
                height: 250px;
                padding-right: 5%;
            }

            .hero-content h1 {
                font-size: 2.2rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .main-card {
                flex-direction: column;
                align-items: center;
                margin: -50px 15px 40px 15px;
                padding: 25px;
            }

            .doctor-image-box {
                flex: 0 0 auto;
                width: 100%;
            }

            .doctor-img {
                height: 360px;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div id="header-placeholder"></div>

<header class="hero-section">
    <div class="blur-background"></div>

    <div class="hero-content">
        <h1><?= $name ?></h1>
        <p><?= $spec ?></p>
    </div>
</header>

<main class="main-card">
    <div class="doctor-image-box">
        <img
                src="<?= $imgSrc ?>"
                alt="<?= $name ?>"
                class="doctor-img"
                onerror="this.src='images/default-doctor.png'"
        >
    </div>

    <div class="doctor-info">
        <h2>نبذة عن الطبيب</h2>

        <p class="bio-text">
            <?= $bio ?: 'لا توجد نبذة متاحة حالياً.' ?>
        </p>

        <div class="contact-grid">
            <div class="grid-item">
                <span><i class="fa-solid fa-envelope"></i> البريد الإلكتروني</span>
                <?= $email ?: 'غير متوفر' ?>
            </div>

            <div class="grid-item">
                <span><i class="fa-solid fa-calendar-days"></i> أيام العيادة</span>
                <?= $days ?>
            </div>

            <div class="grid-item">
                <span><i class="fa-solid fa-clock"></i> مواعيد العيادة</span>
                <?= $hours ?>
            </div>

            <div class="grid-item">
                <span><i class="fa-solid fa-location-dot"></i> موقع العيادة</span>
                <?= $location ?>
            </div>
        </div>
    </div>
</main>

<div id="footer-placeholder"></div>

<script src="script.js"></script>

</body>
</html>