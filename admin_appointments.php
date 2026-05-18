<?php
// admin_appointments.php
session_start();

if (
        empty($_SESSION['user_id']) ||
        !in_array($_SESSION['role'], ['admin', 'receptionist'])
) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$pdo = db(); // ✅ تم التصحيح

// ── معالجة تغيير الحالة (AJAX) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $allowed_statuses = ['confirmed', 'completed', 'cancelled', 'rejected', 'pending'];
    $appt_id    = (int)($_POST['appointment_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if ($appt_id && in_array($new_status, $allowed_statuses)) {
        $pdo->prepare("UPDATE appointments SET status=? WHERE appointment_id=?")
                ->execute([$new_status, $appt_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    }
    exit;
}

// ── فلاتر البحث ────────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? '';
$filter_doctor = $_GET['doctor'] ?? '';
$filter_date   = $_GET['date']   ?? '';
$search        = $_GET['search'] ?? '';

$where  = [];
$params = [];

if ($filter_status) { $where[] = 'a.status = ?';                           $params[] = $filter_status; }
if ($filter_doctor) { $where[] = 'd.doctor_id = ?';                        $params[] = (int)$filter_doctor; }
if ($filter_date)   { $where[] = 'a.appointment_date = ?';                 $params[] = $filter_date; }
if ($search)        { $where[] = '(p.full_name LIKE ? OR p.phone LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT
        a.appointment_id, a.appointment_date, a.appointment_time,
        a.status, a.notes, a.medical_file, a.created_at,
        p.full_name AS patient_name, p.phone AS patient_phone, p.gender,
        d.full_name AS doctor_name, s.specialty_name
    FROM appointments a
    JOIN patients        p ON a.patient_id  = p.patient_id
    JOIN doctors         d ON a.doctor_id   = d.doctor_id
    JOIN specializations s ON d.specialty_id = s.specialty_id
    $sql_where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$doctors = $pdo->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name")->fetchAll();

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='pending')                 AS pending,
        SUM(status='confirmed')               AS confirmed,
        SUM(status='completed')               AS completed,
        SUM(status IN ('cancelled','rejected')) AS cancelled
    FROM appointments
")->fetch();

$status_labels = [
        'pending'   => ['label' => 'قيد الانتظار', 'class' => 'badge-warning'],
        'confirmed' => ['label' => 'مؤكد',          'class' => 'badge-info'],
        'completed' => ['label' => 'مكتمل',         'class' => 'badge-success'],
        'cancelled' => ['label' => 'ملغي',           'class' => 'badge-danger'],
        'rejected'  => ['label' => 'مرفوض',         'class' => 'badge-dark'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الحجوزات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Cairo', sans-serif; background: #f0f4f8; color: #1e293b; min-height: 100vh; }
        .topbar { background: linear-gradient(135deg,#0f4c75,#1b6ca8); color:#fff; padding:14px 28px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(0,0,0,.25); }
        .topbar h1 { font-size:1.3rem; font-weight:700; }
        .topbar a { color:#fff; text-decoration:none; }
        .topbar a:hover { opacity:.8; }
        .page-wrapper { max-width:1300px; margin:30px auto; padding:0 20px; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:16px; margin-bottom:28px; }
        .stat-card { background:#fff; border-radius:14px; padding:20px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.08); border-top:4px solid var(--c); }
        .stat-card .num { font-size:2rem; font-weight:700; color:var(--c); }
        .stat-card .lbl { font-size:.82rem; color:#64748b; margin-top:4px; }
        .filter-bar { background:#fff; border-radius:14px; padding:18px 22px; margin-bottom:22px; box-shadow:0 2px 8px rgba(0,0,0,.07); display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
        .fg { display:flex; flex-direction:column; gap:4px; flex:1; min-width:155px; }
        .fg label { font-size:.78rem; color:#64748b; font-weight:600; }
        .fg select, .fg input { border:1.5px solid #e2e8f0; border-radius:8px; padding:8px 12px; font-family:'Cairo',sans-serif; font-size:.88rem; outline:none; }
        .fg select:focus, .fg input:focus { border-color:#1b6ca8; }
        .btn { border:none; border-radius:8px; padding:9px 20px; font-family:'Cairo',sans-serif; font-size:.88rem; cursor:pointer; text-decoration:none; display:inline-block; text-align:center; }
        .btn-primary { background:#1b6ca8; color:#fff; }
        .btn-primary:hover { background:#0f4c75; }
        .btn-secondary { background:#e2e8f0; color:#475569; }
        .btn-secondary:hover { background:#cbd5e1; }
        .table-card { background:#fff; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,.08); overflow:hidden; }
        .table-card > header { padding:18px 22px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
        .table-card > header h2 { font-size:1.05rem; font-weight:700; color:#0f4c75; }
        .count-badge { background:#e8f0fe; color:#1b6ca8; border-radius:20px; padding:2px 12px; font-size:.83rem; font-weight:700; }
        table { width:100%; border-collapse:collapse; }
        thead tr { background:#f8fafc; }
        th { padding:12px 14px; text-align:right; font-size:.8rem; color:#64748b; font-weight:600; white-space:nowrap; }
        td { padding:13px 14px; border-top:1px solid #f1f5f9; font-size:.87rem; vertical-align:middle; }
        tr:hover td { background:#f8fafc; }
        .badge { display:inline-block; border-radius:20px; padding:3px 11px; font-size:.76rem; font-weight:700; }
        .badge-warning { background:#fff7e6; color:#d97706; }
        .badge-info    { background:#e0f2fe; color:#0284c7; }
        .badge-success { background:#dcfce7; color:#16a34a; }
        .badge-danger  { background:#fee2e2; color:#dc2626; }
        .badge-dark    { background:#f1f5f9; color:#475569; }
        .status-select { border:1.5px solid #e2e8f0; border-radius:8px; padding:5px 8px; font-family:'Cairo',sans-serif; font-size:.8rem; cursor:pointer; background:#fff; }
        .file-link { color:#1b6ca8; text-decoration:none; font-size:.82rem; }
        .file-link:hover { text-decoration:underline; }
        .empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
        .empty-state i { font-size:3rem; margin-bottom:14px; display:block; }
        #toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px); background:#1e293b; color:#fff; padding:12px 28px; border-radius:10px; font-size:.92rem; opacity:0; transition:all .3s; z-index:9999; pointer-events:none; }
        #toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
        @media(max-width:768px){ th:nth-child(n+6), td:nth-child(n+6){ display:none; } }
    </style>
</head>
<body>

<div class="topbar">
    <h1><i class="fas fa-calendar-check" style="margin-left:10px"></i> إدارة الحجوزات</h1>
    <span>
        <i class="fas fa-user-shield"></i>
        <?= htmlspecialchars($_SESSION['username'] ?? 'المدير') ?>
        &nbsp;|&nbsp;
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
    </span>
</div>

<div class="page-wrapper">

    <div class="stats-grid">
        <div class="stat-card" style="--c:#64748b"><div class="num"><?= $stats['total'] ?></div><div class="lbl">إجمالي الحجوزات</div></div>
        <div class="stat-card" style="--c:#d97706"><div class="num"><?= $stats['pending'] ?></div><div class="lbl">قيد الانتظار</div></div>
        <div class="stat-card" style="--c:#0284c7"><div class="num"><?= $stats['confirmed'] ?></div><div class="lbl">مؤكدة</div></div>
        <div class="stat-card" style="--c:#16a34a"><div class="num"><?= $stats['completed'] ?></div><div class="lbl">مكتملة</div></div>
        <div class="stat-card" style="--c:#dc2626"><div class="num"><?= $stats['cancelled'] ?></div><div class="lbl">ملغية / مرفوضة</div></div>
    </div>

    <form class="filter-bar" method="GET">
        <div class="fg">
            <label>بحث باسم / هاتف</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث...">
        </div>
        <div class="fg">
            <label>الحالة</label>
            <select name="status">
                <option value="">كل الحالات</option>
                <?php foreach ($status_labels as $key => $val): ?>
                    <option value="<?= $key ?>" <?= $filter_status===$key?'selected':'' ?>><?= $val['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fg">
            <label>الطبيب</label>
            <select name="doctor">
                <option value="">كل الأطباء</option>
                <?php foreach ($doctors as $dr): ?>
                    <option value="<?= $dr['doctor_id'] ?>" <?= (int)$filter_doctor===(int)$dr['doctor_id']?'selected':'' ?>>
                        <?= htmlspecialchars($dr['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fg">
            <label>التاريخ</label>
            <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
        <a href="admin_appointments.php" class="btn btn-secondary"><i class="fas fa-undo"></i> إعادة</a>
    </form>

    <div class="table-card">
        <header>
            <h2><i class="fas fa-table" style="margin-left:8px"></i> قائمة الحجوزات</h2>
            <span class="count-badge"><?= count($appointments) ?> حجز</span>
        </header>

        <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>لا توجد حجوزات تطابق معايير البحث</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>#</th><th>المريض</th><th>الهاتف</th><th>الطبيب / التخصص</th>
                    <th>التاريخ والوقت</th><th>الحالة</th><th>الملف الطبي</th><th>ملاحظات</th><th>تغيير الحالة</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $i => $a):
                    $s = $status_labels[$a['status']] ?? ['label'=>$a['status'],'class'=>'badge-dark'];
                    ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong>
                            <i class="fas fa-<?= $a['gender']==='male'?'mars" style="color:#1b6ca8':'venus" style="color:#db2777' ?>" style="margin-right:4px"></i>
                        </td>
                        <td><?= htmlspecialchars($a['patient_phone']) ?></td>
                        <td><?= htmlspecialchars($a['doctor_name']) ?><br><small style="color:#94a3b8"><?= htmlspecialchars($a['specialty_name']) ?></small></td>
                        <td><?= date('Y/m/d', strtotime($a['appointment_date'])) ?><br>
                            <small style="color:#64748b"><i class="far fa-clock"></i> <?= substr($a['appointment_time'],0,5) ?></small></td>
                        <td><span class="badge <?= $s['class'] ?>"><?= $s['label'] ?></span></td>
                        <td>
                            <?php if ($a['medical_file']): ?>
                                <a class="file-link" href="<?= htmlspecialchars($a['medical_file']) ?>" target="_blank"><i class="fas fa-file-pdf"></i> عرض</a>
                            <?php else: ?>
                                <span style="color:#cbd5e1">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= htmlspecialchars($a['notes']) ?>">
                            <?= htmlspecialchars($a['notes']?:'—') ?>
                        </td>
                        <td>
                            <select class="status-select" data-id="<?= $a['appointment_id'] ?>">
                                <?php foreach ($status_labels as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= $a['status']===$key?'selected':'' ?>><?= $val['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div id="toast"></div>

<script>
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', async function () {
            const fd = new FormData();
            fd.append('action',         'update_status');
            fd.append('appointment_id', this.dataset.id);
            fd.append('status',         this.value);
            try {
                const res  = await fetch('admin_appointments.php', { method:'POST', body:fd });
                const data = await res.json();
                showToast(data.success ? '✅ تم تحديث الحالة' : '❌ ' + (data.message||'حدث خطأ'));
            } catch {
                showToast('❌ تعذّر الاتصال بالخادم');
            }
        });
    });

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }
</script>
</body>
</html>