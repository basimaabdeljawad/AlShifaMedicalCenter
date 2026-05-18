<?php
// 1. استدعاء ملف الاتصال المعتمد في مشروعك
require_once 'db.php';

try {
    // 2. الحصول على كائن الاتصال PDO من دالة db() الموجودة في ملف db.php
    $pdo = db();

    // 3. استعلام لجلب أطباء قسم "العيون" ليتطابق مع محتوى الصفحة
    $sql = "SELECT doctors.*, specializations.specialty_name 
            FROM doctors 
            JOIN specializations ON doctors.specialty_id = specializations.specialty_id
            WHERE specializations.specialty_name LIKE :dept";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['dept' => '%العيون%']);

    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    // في حال حدوث خطأ في الاتصال أو الاستعلام
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}

// مصفوفة تحتوي على نصائح طبية للعيون ليتم اختيار واحدة منها عشوائياً في نصيحة اليوم
$daily_tips = [
        "<strong>قاعدة 20-20-20:</strong> كل 20 دقيقة أمام الشاشات، انظر لشكل يبعد 20 قدماً لمدة 20 ثانية لإراحة عينيك.",
        "<strong>احذر الجفاف:</strong> احرص على ترطيب عينيك بالقطرات المصنفة كدموع اصطناعية إذا كنت تجلس في غرف مكيفة لفترات طويلة.",
        "<strong>النظارة الشمسية ضرورة:</strong> ارتداء نظارة شمسية أصلية تحمي بنسبة 100% من الأشعة فوق البنفسجية يقي الشبكية من الأضرار المبكرة.",
        "<strong>الفحص الدوري:</strong> إذا كنت تعاني من مرض السكري أو ضغط الدم، فزيارتك السنوية لطبيب العيون تحميك من مضاعفات اعتلال الشبكية.",
        "<strong>تجنب فرك العين:</strong> فرك العين بقوة وبشكل مستمر قد يضعف أنسجة القرنية ويتسبب في حدوث القرنية المخروطية."
];
// اختيار نصيحة عشوائية
$random_tip = $daily_tips[array_rand($daily_tips)];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قسم طب وجراحة العيون</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="styles/homeb.css">
    <link rel="stylesheet" href="styles/dept-style.css">
    <style>
        /* تنسيق بطاقة نصيحة اليوم المميزة */
        .daily-tip-box {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-right: 5px solid #1d4ed8;
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .daily-tip-box .tip-icon {
            background: #1d4ed8;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .daily-tip-box .tip-text {
            color: #1e3a8a;
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>

<body>
<div id="header-placeholder"></div>

<section class="dept-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>قسم طب وجراحة العيون</h1>
        <p>رعاية متقدمة لعيونكم بأحدث التقنيات العالمية</p>
    </div>
</section>

<main class="main-wrapper">

    <aside class="sidebar">
        <div class="booking-card">
            <div class="booking-icon">
                <i class="fas fa-calendar-check fa-3x" style="color: var(--main-blue); margin-bottom: 15px;"></i>
            </div>
            <h3>احجز موعدك الآن</h3>
            <p>فريقنا الطبي جاهز لاستقبال استفساراتكم على مدار الساعة</p>

            <div class="booking-actions">
                <a href="reserve.php" class="btn-action btn-reserve" style="background-color: #16a34a; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 10px; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-laptop-medical"></i> احجز موعدًا
                </a>
                <a href="tel:05XXXXXXXX" class="btn-action btn-call">
                    <i class="fas fa-phone-alt"></i> اتصل بنا
                </a>

            </div>
        </div>
    </aside>

    <div class="content-side">

        <section class="info-card">
            <h2 class="section-title"><i class="fas fa-eye"></i> عن القسم</h2>
            <div class="about-and-gallery">
                <div class="about-text">
                    <p>نجمع بين الخبرة الطبية والتقنيات الحديثة لنقدم رعاية متكاملة لصحة عينيك. فريقنا يحرص على توفير تجربة علاجية آمنة ومريحة لجميع المرضى وفق أعلى المعايير الطبية لضمان سلامة وجودة النظر لك ولكافة أفراد عائلتك.</p>
                </div>
                <div class="mini-gallery">
                    <img src="images/eye0.jpg" alt="عيادة العيون">
                    <img src="images/eye1.jpg" alt="فحص مجهري">
                </div>
            </div>
        </section>

        <section class="info-card">
            <h2 class="section-title"><i class="fas fa-stethoscope"></i> الخدمات الشاملة التي نقدمها</h2>
            <div class="services-grid">
                <div class="service-box">
                    <div class="service-content">
                        <i class="fas fa-microscope service-icon"></i>
                        <h4>فحص النظر الشامل والكمبيوتر</h4>
                        <p>قياس دقيق لحدة الإبصار والعيوب الانكسارية باستخدام أجهزة متطورة.</p>
                    </div>
                </div>
                <div class="service-box">
                    <div class="service-content">
                        <i class="fas fa-procedures service-icon"></i>
                        <h4>عمليات تصحيح النظر والليزك</h4>
                        <p>أحدث تقنيات الفيمتو ليزك والفيمتو سمايل لضمان التخلص من النظارة بأمان.</p>
                    </div>
                </div>
                <div class="service-box">
                    <div class="service-content">
                        <i class="fas fa-briefcase-medical service-icon"></i>
                        <h4>علاج المياه البيضاء والزرقاء</h4>
                        <p>إزالة الساد (المياه البيضاء) بالموجات فوق الصوتية (الفاكو) وزراعة أحدث العدسات.</p>
                    </div>
                </div>
                <div class="service-box">
                    <div class="service-content">
                        <i class="fas fa-eye-dropper service-icon"></i>
                        <h4>متابعة اعتلال الشبكية السكري</h4>
                        <p>فحص قاع العين الدوري وحقن الشبكية وعلاجها بالليزر لحماية نظر مرضى السكري.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-card">
            <h2 class="section-title"><i class="fas fa-user-md"></i> نخبة أطباء القسم</h2>

            <div class="doctors-modern-grid">
                <?php
                if (count($doctors) > 0) {
                    foreach ($doctors as $row) {
                        ?>
                        <div class="doc-card-modern">
                            <div class="doc-image">
                                <img src="uploads/doctors//<?php echo !empty($row['image']) ? $row['image'] : 'default.jpg'; ?>" alt="<?php echo htmlspecialchars($row['full_name']); ?>">
                                <div class="doc-overlay">
                                    <a href="profdoctor.php?id=<?php echo $row['doctor_id']; ?>" class="view-profile-btn">الملف الطبي</a>
                                </div>
                            </div>
                            <div class="doc-details">
                                <h4><?php echo htmlspecialchars($row['full_name']); ?></h4>
                                <span><?php echo htmlspecialchars($row['specialty_name']); ?></span>
                                <p><i class="fas fa-star" style="color: #ffcc00;"></i> 4.9 (120 تقييم)</p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p style='text-align:center; width:100%; padding:20px;'>لا يوجد أطباء مضافين في هذا القسم حالياً.</p>";
                }
                ?>
            </div>
        </section>

        <section class="info-card">
            <h2 class="section-title"><i class="fas fa-lightbulb"></i> نصيحة اليوم الطبية</h2>
            <div class="daily-tip-box">
                <div class="tip-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <p class="tip-text">
                    <?php echo $random_tip; ?>
                </p>
            </div>
        </section>

    </div>
</main>

<div id="footer-placeholder"></div>

<script>
    // جلب الهيدر والفوتر من الملفات الخارجية
    async function loadComponents() {
        try {
            const h = await fetch('header.html');
            if(h.ok) document.getElementById('header-placeholder').innerHTML = await h.text();
            const f = await fetch('footer.html');
            if(f.ok) document.getElementById('footer-placeholder').innerHTML = await f.text();
        } catch (err) { console.error("Error loading components:", err); }
    }

    window.onload = loadComponents;
</script>
</body>
</html>