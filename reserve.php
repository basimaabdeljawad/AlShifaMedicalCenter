<?php
// reserve.php — النسخة المحدثة بأنيميشن ناعم وجاذب للانتباه لأيقونة الروبوت
require_once __DIR__ . '/db.php';
$pdo = db();

// ── 1) محرك الحفظ: يستقبل البيانات من الفورم ويخزنها فوراً ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $p_name    = trim($_POST['patient_name'] ?? '');
        $p_phone   = trim($_POST['patient_phone'] ?? '');
        $p_id      = trim($_POST['patient_id'] ?? '');
        $p_dob     = $_POST['patient_dob'] ?? '';
        $gender    = $_POST['gender'] ?? '';
        $p_address = trim($_POST['patient_address'] ?? '');
        $doc_id    = (int)($_POST['doctor_id'] ?? 0);
        $a_date    = $_POST['date'] ?? '';
        $a_time    = $_POST['time'] ?? '';
        $notes     = htmlspecialchars($_POST['notes'] ?? '');

        if (!$p_name || !$p_phone || !$p_dob || !$doc_id || !$a_date || !$a_time || !$p_address) {
            echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة واختيار الوقت المتاح بما في ذلك العنوان.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE phone = ?");
        $stmt->execute([$p_phone]);
        $patient = $stmt->fetch();

        if ($patient) {
            $patient_id = $patient['patient_id'];
            $upd = $pdo->prepare("UPDATE patients SET date_of_birth = ?, address = ? WHERE patient_id = ?");
            $upd->execute([$p_dob, $p_address, $patient_id]);
        } else {
            $ins = $pdo->prepare("INSERT INTO patients (full_name, phone, gender, date_of_birth, address) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$p_name, $p_phone, $gender, $p_dob, $p_address]);
            $patient_id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$patient_id, $doc_id, $a_date, $a_time, $notes]);

        echo json_encode(['success' => true, 'message' => 'تم تسجيل الموعد وحفظ البيانات بنجاح!']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit;
    }
}

// ── 2) AJAX: أطباء حسب التخصص ───────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'doctors') {
    header('Content-Type: application/json; charset=utf-8');
    $specId = (int)($_GET['specialty_id'] ?? 0);
    if (!$specId) { echo json_encode([]); exit; }
    $s = $pdo->prepare("SELECT doctor_id, full_name FROM doctors WHERE specialty_id=? ORDER BY full_name");
    $s->execute([$specId]);
    echo json_encode($s->fetchAll());
    exit;
}

// ── 3) AJAX: أوقات محجوزة لطبيب في تاريخ ─────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'booked') {
    header('Content-Type: application/json; charset=utf-8');
    $did  = (int)($_GET['doctor_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    if (!$did || !$date) { echo json_encode([]); exit; }
    $s = $pdo->prepare("
        SELECT appointment_time FROM appointments
        WHERE doctor_id=? AND appointment_date=? AND status NOT IN ('cancelled','rejected')
    ");
    $s->execute([$did, $date]);
    echo json_encode(array_map(fn($r) => substr($r['appointment_time'], 0, 5), $s->fetchAll()));
    exit;
}

$specs = $pdo->query("
    SELECT s.specialty_id, s.specialty_name
    FROM specializations s
    WHERE EXISTS (SELECT 1 FROM doctors d WHERE d.specialty_id = s.specialty_id)
    ORDER BY s.specialty_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز الشفاء | حجز موعد ذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles/reserve.css">
    <link rel="stylesheet" href="styles/myst.css">
    <link rel="stylesheet" href="styles/homeb.css">
    <style>
        #result-msg {
            display: none;
            margin: 16px 0;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: .95rem;
            font-weight: 600;
            text-align: center;
        }
        #result-msg.success { background: #dcfce7; color: #16a34a; border: 1.5px solid #bbf7d0; display: block !important; }
        #result-msg.error   { background: #fee2e2; color: #dc2626; border: 1.5px solid #fca5a5; display: block !important; }
        .main-submit-btn:disabled { opacity: .6; cursor: not-allowed; }
        .time-btn.booked {
            opacity: .4 !important;
            cursor: not-allowed !important;
            text-decoration: line-through;
            pointer-events: none;
            background: #eee !important;
        }

        /* ── المظهر العصري المتناسق مع الألوان الزرقاء للموقع ── */
        .modern-ai-trigger {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(29, 78, 216, 0.35);
            z-index: 999999;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid #ffffff;

            /* تفعيل أنيميشن لفت الانتباه الناعم والتلقائي */
            animation: modernBotPulse 5s infinite ease-in-out;
        }

        /* تأثير مميز عند تمرير الماوس فوق الروبوت */
        .modern-ai-trigger:hover {
            transform: translateY(-5px) scale(1.08) rotate(8deg);
            box-shadow: 0 12px 30px rgba(29, 78, 216, 0.5);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            animation-play-state: paused; /* إيقاف الأنيميشن الدوري مؤقتاً عند وقوف الماوس للتركيز */
        }

        .modern-ai-trigger .pulse-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 11px;
            height: 11px;
            background: #10b981;
            border: 2px solid #fff;
            border-radius: 50%;
        }

        /* أنيميشن لفت الانتباه الناعم والاحترافي (نبض + اهتزاز طفيف دوري) */
        @keyframes modernBotPulse {
            0%, 100%, 20% {
                transform: scale(1);
                box-shadow: 0 8px 25px rgba(29, 78, 216, 0.35);
            }
            30% {
                transform: scale(1.08);
                box-shadow: 0 12px 30px rgba(29, 78, 216, 0.55);
            }
            35% {
                transform: scale(1.05) rotate(5deg);
            }
            40% {
                transform: scale(1.05) rotate(-5deg);
            }
            45% {
                transform: scale(1.05) rotate(3deg);
            }
            50% {
                transform: scale(1.05) rotate(0deg);
            }
            60% {
                transform: scale(1);
                box-shadow: 0 8px 25px rgba(29, 78, 216, 0.35);
            }
        }

        .modern-chat-container {
            position: fixed;
            bottom: 105px;
            right: 30px;
            width: 370px;
            height: 500px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
            display: none;
            flex-direction: column;
            z-index: 999999;
            overflow: hidden;
            font-family: 'Cairo', sans-serif;
            border: 1px solid rgba(37, 99, 235, 0.15);
            transform: translateY(20px) scale(0.95);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modern-chat-container.active {
            display: flex;
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .modern-chat-header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #ffffff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modern-chat-header .bot-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modern-chat-header .bot-info i {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px;
            border-radius: 10px;
            font-size: 1.1rem;
        }
        .modern-chat-header .bot-title {
            font-size: 0.95rem;
            font-weight: 700;
        }
        .modern-chat-header .bot-status {
            font-size: 0.75rem;
            color: #bfdbfe;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .modern-chat-header .bot-status::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #4ade80;
            border-radius: 50%;
        }
        .modern-chat-header .close-chat {
            cursor: pointer;
            font-size: 1.4rem;
            color: #93c5fd;
            transition: color 0.2s;
        }
        .modern-chat-header .close-chat:hover { color: #ffffff; }

        .modern-chat-body {
            flex: 1;
            padding: 18px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .msg-bubble {
            padding: 12px 16px;
            font-size: 0.9rem;
            line-height: 1.6;
            max-width: 85%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .msg-bubble.bot {
            background: #ffffff;
            color: #334155;
            border-radius: 2px 14px 14px 14px;
            align-self: flex-start;
            border: 1px solid #e2e8f0;
        }
        .msg-bubble.user {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border-radius: 14px 2px 14px 14px;
            align-self: flex-end;
        }
        .msg-bubble.loading {
            background: #e2e8f0;
            color: #475569;
            border-radius: 2px 12px 12px 12px;
            align-self: flex-start;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .msg-bubble.error-msg {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
            border-radius: 2px 12px 12px 12px;
            align-self: flex-start;
        }

        .modern-chat-footer {
            padding: 14px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
            background: #ffffff;
        }
        .modern-chat-footer input {
            flex: 1;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            font-family: inherit;
            font-size: 0.88rem;
            color: #1e293b;
        }
        .modern-chat-footer input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .modern-chat-footer button {
            background: #2563eb;
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            font-size: 1rem;
        }
        .modern-chat-footer button:hover {
            background: #1d4ed8;
        }

        .modern-chat-body::-webkit-scrollbar { width: 4px; }
        .modern-chat-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

<div id="header-placeholder"></div>

<main class="main-wrapper">
    <form id="appointmentForm" class="booking-card" enctype="multipart/form-data">
        <input type="hidden" name="created_at" value="<?= date('Y-m-d H:i:s') ?>">

        <header class="card-header">
            <h1 class="page-title">حجز موعد طبي جديد</h1>
            <p class="page-subtitle">قم بتعبئة البيانات أدناه لتأكيد موعدك مع أمهر الأطباء</p>
        </header>

        <div id="result-msg"></div>

        <div class="card-section">
            <h2 class="section-title"><i class="fas fa-calendar-alt"></i> تفاصيل الحجز</h2>
            <div class="form-section grid-2">
                <div class="input-group">
                    <label><i class="fas fa-hospital-user"></i> القسم الطبي</label>
                    <select required name="clinic" id="clinicSelect" onchange="loadDoctors(this.options[this.selectedIndex].dataset.id)">
                        <option value="" disabled selected>اختر القسم</option>
                        <?php foreach ($specs as $sp): ?>
                            <option value="<?= htmlspecialchars($sp['specialty_name']) ?>" data-id="<?= (int)$sp['specialty_id'] ?>">
                                <?= htmlspecialchars($sp['specialty_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-user-md"></i> الطبيب المختص</label>
                    <select required name="doctor" id="doctorSelect" onchange="onDoctorChange()">
                        <option value="" disabled selected>اختر القسم أولاً</option>
                    </select>
                    <input type="hidden" name="doctor_id" id="doctorIdInput">
                </div>
            </div>
            <div class="form-section grid-main">
                <div class="input-group">
                    <label><i class="far fa-calendar-check"></i> تاريخ الموعد</label>
                    <input type="date" name="date" id="apptDate" class="modern-input" required min="<?= date('Y-m-d') ?>" onchange="onDateChange()">
                </div>
                <div class="input-group">
                    <label><i class="far fa-clock"></i> الفترات المتاحة</label>
                    <div class="time-slots-container" id="timeSlots"></div>
                </div>
            </div>
        </div>

        <div class="card-section user-data-section">
            <h2 class="section-title"><i class="fas fa-id-card"></i> بيانات المريض</h2>
            <div class="grid-2 mb-20">
                <div class="input-group">
                    <label>الاسم الكامل</label>
                    <input type="text" name="patient_name" class="modern-input" placeholder="كما في الهوية" required pattern="^[a-zA-Z\s\u0600-\u06FF]+$" title="الاسم يجب أن يحتوي على حروف فقط">
                </div>
                <div class="input-group">
                    <label>رقم الهاتف</label>
                    <input type="tel" name="patient_phone" class="modern-input" placeholder="05xxxxxxxx" required pattern="[0-9]+" title="يرجى إدخال أرقام فقط" inputmode="numeric">
                </div>
            </div>
            <div class="grid-2 mb-20">
                <div class="input-group">
                    <label>رقم الهوية / الإقامة</label>
                    <input type="text" name="patient_id" class="modern-input" placeholder="10xxxxxxxx" required pattern="[0-9]+" title="يرجى إدخال أرقام فقط" inputmode="numeric">
                </div>
                <div class="input-group">
                    <label>تاريخ الميلاد</label>
                    <input type="date" name="patient_dob" class="modern-input" required max="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="grid-2 mb-20">
                <div class="input-group">
                    <label>الجنس</label>
                    <select name="gender" required>
                        <option value="" disabled selected>اختر النوع</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>العنوان الحالي</label>
                    <input type="text" name="patient_address" class="modern-input" placeholder="المدينة، الحي أو الشارع" required>
                </div>
            </div>
            <div class="grid-2 mb-20">
                <div class="input-group" style="grid-column: span 2;">
                    <label>ارفاق تقارير طبية (اختياري)</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="medical_reports[]" id="medical_reports" hidden multiple>
                        <label for="medical_reports" class="file-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>اسحب الملفات هنا أو اضغط للرفع</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="input-group">
                <label>ملاحظات إضافية أو أعراض</label>
                <textarea name="notes" class="modern-input" rows="3" placeholder="اكتب هنا أي تفاصيل تود إخبار الطبيب بها..."></textarea>
            </div>
        </div>

        <footer class="card-footer" style="margin-top: 30px;">
            <button type="submit" class="main-submit-btn" id="submitBtn">
                <span>تأكيد حجز الموعد</span>
                <i class="fas fa-check-circle"></i>
            </button>
        </footer>
    </form>
</main>

<div id="footer-placeholder"></div>

<script>
    async function loadDoctors(specId) {
        const sel = document.getElementById('doctorSelect');
        document.getElementById('doctorIdInput').value = '';
        sel.innerHTML = '<option value="" disabled selected>جاري التحميل...</option>';
        generateTimeSlots(8, 20, []);
        try {
            const res  = await fetch(`?ajax=doctors&specialty_id=${specId}`);
            const docs = await res.json();
            if (!docs.length) {
                sel.innerHTML = '<option value="" disabled selected>لا يوجد أطباء في هذا القسم</option>';
                return;
            }
            sel.innerHTML = '<option value="" disabled selected>اختر الطبيب</option>';
            docs.forEach(d => {
                const o = document.createElement('option');
                o.value = d.full_name; o.dataset.doctorId = d.doctor_id; o.textContent = d.full_name;
                sel.appendChild(o);
            });
        } catch { sel.innerHTML = '<option value="" disabled selected>خطأ في التحميل</option>'; }
    }

    function onDoctorChange() {
        const sel = document.getElementById('doctorSelect');
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('doctorIdInput').value = opt.dataset.doctorId || '';
        const date = document.getElementById('apptDate').value;
        if (date) loadBookedTimes(opt.dataset.doctorId, date);
        else generateTimeSlots(8, 20, []);
    }

    function onDateChange() {
        const doctorId = document.getElementById('doctorIdInput').value;
        const date     = document.getElementById('apptDate').value;
        if (doctorId && date) loadBookedTimes(doctorId, date);
        else generateTimeSlots(8, 20, []);
    }

    async function loadBookedTimes(doctorId, date) {
        try {
            const res    = await fetch(`?ajax=booked&doctor_id=${doctorId}&date=${date}`);
            const booked = await res.json();
            generateTimeSlots(8, 20, booked);
        } catch { generateTimeSlots(8, 20, []); }
    }

    function generateTimeSlots(startHour, endHour, bookedTimes = []) {
        const container = document.getElementById('timeSlots');
        container.innerHTML = '';
        for (let hour = startHour; hour <= endHour; hour++) {
            const value    = hour.toString().padStart(2, '0') + ':00';
            const isBooked = bookedTimes.includes(value);
            const wrapper = document.createElement('div'); wrapper.className = 'slot-wrapper';
            const input = document.createElement('input');
            input.type = 'radio'; input.name = 'time'; input.id = 't' + value.replace(':', ''); input.value = value; input.hidden = true; input.disabled = isBooked;
            if (hour === startHour && !isBooked) input.required = true;
            const label = document.createElement('label');
            label.htmlFor = input.id; label.className = 'time-btn' + (isBooked ? ' booked' : ''); label.textContent = value;
            if (isBooked) label.title = 'هذا الوقت محجوز مسبقاً';
            wrapper.appendChild(input); wrapper.appendChild(label); container.appendChild(wrapper);
        }
    }
    generateTimeSlots(8, 20, []);

    document.getElementById('appointmentForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn'); const msg = document.getElementById('result-msg');
        if (!document.getElementById('doctorIdInput').value) {
            msg.style.display = 'block'; msg.className = 'error'; msg.textContent = 'يرجى اختيار الطبيب أولاً.'; return;
        }
        btn.disabled = true; btn.innerHTML = '<span>جاري الإرسال...</span> <i class="fas fa-spinner fa-spin"></i>'; msg.style.display = 'none';
        try {
            const res  = await fetch('reserve.php', { method: 'POST', body: new FormData(this) });
            const data = await res.json();
            msg.style.display = 'block'; msg.className = data.success ? 'success' : 'error'; msg.textContent = data.message;
            if (data.success) {
                this.reset();
                document.getElementById('doctorSelect').innerHTML = '<option value="" disabled selected>اختر القسم أولاً</option>';
                document.getElementById('doctorIdInput').value = ''; generateTimeSlots(8, 20, []);
                window.scrollTo({ top: msg.offsetTop - 20, behavior: 'smooth' });
            }
        } catch (err) {
            msg.style.display = 'block'; msg.className = 'error'; msg.textContent = 'حدث خطأ في معالجة البيانات، يرجى المحاولة مجدداً.';
        } finally {
            btn.disabled = false; btn.innerHTML = '<span>تأكيد حجز الموعد</span> <i class="fas fa-check-circle"></i>';
        }
    });
</script>

<script src="script.js"></script>


<div id="ai-widget-btn" class="modern-ai-trigger">
    <i class="fas fa-robot"></i>
    <span class="pulse-dot"></span>
</div>

<div id="ai-chat-box" class="modern-chat-container">
    <div class="modern-chat-header">
        <div class="bot-info">
            <i class="fas fa-robot"></i>
            <div>
                <div class="bot-title">المساعد الطبي الذكي</div>
                <div class="bot-status">متصل الآن</div>
            </div>
        </div>
        <span id="ai-close-btn" class="close-chat">&times;</span>
    </div>

    <div id="chat-contents" class="modern-chat-body">
        <div class="msg-bubble bot">
            مرحباً بك في مركز الشفاء الطبي. 👋<br>
            أنا هنا لأوجهك، يرجى كتابة الأعراض التي تشعر بها وسأقترح عليك العيادة المناسبة فوراً.
        </div>
    </div>

    <div class="modern-chat-footer">
        <input type="text" id="user-symptoms" placeholder="اكتب أعراضك هنا بوضوح..." autocomplete="off">
        <button id="ai-send-btn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const widgetBtn = document.getElementById('ai-widget-btn');
        const chatBox = document.getElementById('ai-chat-box');
        const closeBtn = document.getElementById('ai-close-btn');
        const sendBtn = document.getElementById('ai-send-btn');
        const inputField = document.getElementById('user-symptoms');
        const container = document.getElementById('chat-contents');

        function toggleChat() {
            if (!chatBox.classList.contains('active')) {
                chatBox.style.display = 'flex';
                setTimeout(() => {
                    chatBox.classList.add('active');
                }, 10);
            } else {
                chatBox.classList.remove('active');
                setTimeout(() => {
                    chatBox.style.display = 'none';
                }, 300);
            }
        }

        widgetBtn.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', toggleChat);

        async function sendSymptomsMessage() {
            const txt = inputField.value.trim();
            if (!txt) return;

            container.innerHTML += '<div class="msg-bubble user">' + txt + '</div>';
            inputField.value = '';
            container.scrollTop = container.scrollHeight;

            const loadingId = 'load-' + Date.now();
            container.innerHTML += '<div id="' + loadingId + '" class="msg-bubble loading"><i class="fas fa-circle-notch fa-spin"></i> جاري تحليل الأعراض...</div>';
            container.scrollTop = container.scrollHeight;

            try {
                const res = await fetch('ai_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: txt })
                });
                const data = await res.json();

                const loadElem = document.getElementById(loadingId);
                if (loadElem) loadElem.remove();

                container.innerHTML += '<div class="msg-bubble bot">' + data.reply + '</div>';
            } catch (err) {
                const loadElem = document.getElementById(loadingId);
                if (loadElem) loadElem.remove();
                container.innerHTML += '<div class="msg-bubble error-msg">نعتذر، حدث خطأ أثناء الاتصال بالخادم الطبي.</div>';
            }
            container.scrollTop = container.scrollHeight;
        }

        sendBtn.addEventListener('click', sendSymptomsMessage);
        inputField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendSymptomsMessage();
            }
        });
    });
</script>
</body>
</html>