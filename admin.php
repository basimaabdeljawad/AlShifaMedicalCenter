<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز الشفاء الطبي - لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <link rel="stylesheet" href="styles/admin.css">
</head>
<body>

<!-- LOGIN -->
<div id="loginPage">
    <div class="login-box">
        <div class="login-logo">
            <div class="icon-wrap"><i class="fas fa-heartbeat"></i></div>
            <h1>مركز الشفاء الطبي</h1>
            <p>لوحة تحكم الإدارة</p>
        </div>
        <div class="form-group">
            <label>اسم المستخدم</label>
            <div class="input-wrap"><i class="fas fa-user"></i><input type="text" id="loginUser" placeholder="اسم المستخدم"></div>
        </div>
        <div class="form-group">
            <label>كلمة المرور</label>
            <div class="input-wrap"><i class="fas fa-lock"></i><input type="password" id="loginPass" placeholder="كلمة المرور"></div>
        </div>
        <button class="login-btn" onclick="doLogin()"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</button>
        <div class="login-error" id="loginError">❌ اسم المستخدم أو كلمة المرور غير صحيحة</div>
        <a href="homeb.html" class="back-btn"><i class="fas fa-arrow-right"></i> العودة للموقع</a>
    </div>
</div>

<!-- APP -->
<div id="app" style="display:none">
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
                    <div class="logo-text"><h2>مركز الشفاء الطبي</h2><p>لوحة التحكم</p></div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">الرئيسية</div>
                <div class="nav-item active" onclick="navigate('dashboard')" data-page="dashboard" data-roles="admin,receptionist,doctor">
                    <i class="fas fa-th-large"></i> لوحة التحكم
                </div>
                <div class="nav-section">إدارة المركز</div>
                <div class="nav-item" onclick="navigate('appointments')" data-page="appointments" data-roles="admin,receptionist">
                    <i class="fas fa-calendar-check"></i> المواعيد <span class="nbadge" id="apptBadge">0</span>
                </div>
                <div class="nav-item" onclick="navigate('myappointments')" data-page="myappointments" data-roles="doctor">
                    <i class="fas fa-calendar-check"></i> مواعيدي
                </div>
                <div class="nav-item" onclick="navigate('doctors')" data-page="doctors" data-roles="admin">
                    <i class="fas fa-user-md"></i> الأطباء
                </div>
                <div class="nav-item" onclick="navigate('patients')" data-page="patients" data-roles="admin,receptionist">
                    <i class="fas fa-users"></i> المرضى
                </div>
                <div class="nav-item" onclick="navigate('specializations')" data-page="specializations" data-roles="admin">
                    <i class="fas fa-stethoscope"></i> التخصصات
                </div>
                <div class="nav-item" onclick="navigate('clinic_schedules')" data-page="clinic_schedules" data-roles="admin">
                    <i class="fas fa-clock"></i> دوام العيادات
                </div>
                <div class="nav-section">التواصل</div>
                <div class="nav-item" onclick="navigate('messages')" data-page="messages" data-roles="admin,receptionist">
                    <i class="fas fa-envelope"></i> الرسائل <span class="nbadge" id="msgBadge">0</span>
                </div>
                <div class="nav-section">التقارير والنظام</div>
                <div class="nav-item" onclick="navigate('analytics')" data-page="analytics" data-roles="admin">
                    <i class="fas fa-chart-bar"></i> الإحصائيات
                </div>
                <div class="nav-item" onclick="navigate('users')" data-page="users" data-roles="admin">
                    <i class="fas fa-shield-alt"></i> المستخدمين والصلاحيات
                </div>
                <div class="nav-section">الحساب</div>
                <div class="nav-item" onclick="navigate('profile')" data-page="profile" data-roles="admin,receptionist,doctor">
                    <i class="fas fa-user-circle"></i> الصفحة الشخصية
                </div>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar" id="sidebarAvatar"><i class="fas fa-user-tie"></i></div>
                    <div class="ud"><p id="sbName">المدير</p><span id="sbRole">admin</span></div>
                    <button class="logout-btn" onclick="doLogout()" title="خروج"><i class="fas fa-sign-out-alt"></i></button>
                </div>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="main">
            <div class="topbar">
                <div class="topbar-title"><h2 id="pageTitle">لوحة التحكم</h2><p id="pageSubtitle">مرحباً بك</p></div>
                <div class="topbar-actions">
                    <div class="topbar-search"><i class="fas fa-search"></i><input type="text" placeholder="بحث سريع..."></div>
                    <span class="role-tag admin" id="roleTag">مدير النظام</span>
                    <button class="btn btn-outline btn-sm no-print" id="printBtn" style="display:none" onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
                    <button class="btn btn-success btn-sm no-print" id="exportBtn" style="display:none" onclick="doExport()"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-primary no-print" id="addBtn" onclick="openAddModal()" style="display:none"><i class="fas fa-plus"></i> <span id="addBtnText">إضافة</span></button>
                </div>
            </div>

            <!-- DASHBOARD -->
            <div class="page active" id="page-dashboard">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon si-teal"><i class="fas fa-calendar-check"></i></div><div class="stat-info"><h3 id="s-appt">0</h3><p>المواعيد</p></div></div>
                    <div class="stat-card"><div class="stat-icon si-blue"><i class="fas fa-user-md"></i></div><div class="stat-info"><h3 id="s-doc">0</h3><p>الأطباء</p></div></div>
                    <div class="stat-card"><div class="stat-icon si-green"><i class="fas fa-users"></i></div><div class="stat-info"><h3 id="s-pat">0</h3><p>المرضى</p></div></div>
                    <div class="stat-card"><div class="stat-icon si-orange"><i class="fas fa-envelope"></i></div><div class="stat-info"><h3 id="s-msg">0</h3><p>الرسائل</p></div></div>
                </div>
                <div class="dash-grid">
                    <div class="dash-card"><div class="dch"><i class="fas fa-calendar-day"></i><h4>أحدث المواعيد</h4></div><div class="dcb" id="dash-appts"></div></div>
                    <div class="dash-card"><div class="dch"><i class="fas fa-envelope-open-text"></i><h4>آخر الرسائل</h4></div><div class="dcb" id="dash-msgs"></div></div>
                </div>
            </div>

            <!-- APPOINTMENTS -->
            <div class="page" id="page-appointments">
                <div class="filter-bar">
                    <input type="text" id="apptSearch" placeholder="🔍 بحث..." oninput="renderAppointments()">
                    <select id="apptSF" onchange="renderAppointments()">
                        <option value="">كل الحالات</option>
                        <option value="pending">معلق</option>
                        <option value="confirmed">مؤكد</option>
                        <option value="completed">منتهي</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                </div>
                <div class="table-wrap"><table>
                        <thead><tr><th>#</th><th>المريض</th><th>الطبيب</th><th>التاريخ</th><th>الوقت</th><th>الحالة</th><th>ملاحظات</th><th>إجراءات</th></tr></thead>
                        <tbody id="apptTable"></tbody>
                    </table></div>
            </div>

            <!-- MY APPOINTMENTS -->
            <div class="page" id="page-myappointments">
                <div class="table-wrap"><table>
                        <thead><tr><th>#</th><th>المريض</th><th>التاريخ</th><th>الوقت</th><th>الحالة</th><th>ملاحظات</th><th>واتساب</th></tr></thead>
                        <tbody id="myApptTable"></tbody>
                    </table></div>
            </div>

            <!-- DOCTORS -->
            <div class="page" id="page-doctors">
                <div class="filter-bar">
                    <input type="text" id="docSearch" placeholder="🔍 بحث عن طبيب..." oninput="renderDoctors()">
                    <select id="docSpecF" onchange="renderDoctors()"><option value="">كل التخصصات</option></select>
                </div>
                <div class="table-wrap"><table>
                        <thead><tr><th>#</th><th>الطبيب</th><th>التخصص</th><th>البريد</th><th>الهاتف</th><th>نبذة</th><th>إجراءات</th></tr></thead>
                        <tbody id="docTable"></tbody>
                    </table></div>
            </div>

            <!-- PATIENTS -->
            <div class="page" id="page-patients">
                <div class="filter-bar">
                    <input type="text" id="patSearch" placeholder="🔍 بحث عن مريض..." oninput="renderPatients()">
                    <select id="patGF" onchange="renderPatients()">
                        <option value="">كل الجنسين</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>
                <div class="table-wrap"><table>
                        <thead><tr><th>رقم الهوية</th><th>المريض</th><th>الجنس</th><th>تاريخ الميلاد</th><th>الهاتف</th><th>العنوان</th><th>إجراءات</th></tr></thead>
                        <tbody id="patTable"></tbody>
                    </table></div>
            </div>

            <!-- SPECIALIZATIONS -->
            <div class="page" id="page-specializations">
                <div class="table-wrap"><table>
                        <thead><tr><th>#</th><th>التخصص</th><th>عدد الأطباء</th><th>إجراءات</th></tr></thead>
                        <tbody id="specTable"></tbody>
                    </table></div>
            </div>

            <!-- CLINIC SCHEDULES -->
            <div class="page" id="page-clinic_schedules">
                <div class="filter-bar">
                    <input type="text" id="schSearch" placeholder="🔍 بحث عن طبيب أو دوام..." oninput="renderSchedules()">
                </div>
                <div class="table-wrap"><table>
                        <thead><tr><th>#</th><th>الطبيب</th><th>أيام الدوام</th><th>ساعات الدوام</th><th>الموقع</th><th>إجراءات</th></tr></thead>
                        <tbody id="scheduleTable"></tbody>
                    </table></div>
            </div>

            <!-- MESSAGES -->
            <div class="page" id="page-messages"><div id="messagesList"></div></div>

            <!-- ANALYTICS -->
            <div class="page" id="page-analytics">
                <div class="analytics-grid">
                    <div class="chart-card wide"><h4><i class="fas fa-chart-bar" style="color:var(--primary);margin-left:8px"></i>المواعيد حسب الحالة</h4><canvas id="chartStatus" height="90"></canvas></div>
                    <div class="chart-card"><h4><i class="fas fa-user-md" style="color:var(--info);margin-left:8px"></i>أكثر الأطباء طلباً</h4><div id="topDoctors"></div></div>
                    <div class="chart-card"><h4><i class="fas fa-venus-mars" style="color:var(--warning);margin-left:8px"></i>توزيع المرضى بالجنس</h4><canvas id="chartGender" height="200"></canvas></div>
                    <div class="chart-card wide"><h4><i class="fas fa-stethoscope" style="color:var(--success);margin-left:8px"></i>المواعيد حسب التخصص</h4><canvas id="chartSpec" height="90"></canvas></div>
                </div>
            </div>

            <!-- USERS & PERMISSIONS -->
            <div class="page" id="page-users">
                <div class="perms-section">
                    <div class="perms-hdr"><i class="fas fa-table" style="margin-left:8px"></i>جدول الصلاحيات حسب الدور</div>
                    <div style="padding:16px;overflow-x:auto">
                        <table class="perm-table">
                            <thead><tr><th>الصفحة / الميزة</th><th>مدير النظام</th><th>موظف الاستقبال</th><th>طبيب</th></tr></thead>
                            <tbody>
                            <tr><td>لوحة التحكم</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td></tr>
                            <tr><td>كل المواعيد</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>مواعيدي (طبيب)</td><td><span class="pk pk-no">✗</span></td><td><span class="pk pk-no">✗</span></td><td><span class="pk pk-yes">✓</span></td></tr>
                            <tr><td>إدارة الأطباء</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>إدارة المرضى</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>التخصصات</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>الرسائل + الرد بإيميل</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>الإحصائيات والتقارير</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>إدارة المستخدمين</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>إرسال واتساب</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td></tr>
                            <tr><td>طباعة وتصدير CSV</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-no">✗</span></td></tr>
                            <tr><td>الصفحة الشخصية</td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td><td><span class="pk pk-yes">✓</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="table-wrap"><table>
                        <thead><tr><th>#</th><th>اسم المستخدم</th><th>الدور</th><th>إجراءات</th></tr></thead>
                        <tbody id="userTable"></tbody>
                    </table></div>
            </div>

            <!-- PROFILE -->
            <div class="page" id="page-profile">
                <div class="profile-wrap">

                    <!-- بطاقة المعلومات -->
                    <div class="profile-card">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar" id="profileAvatarPreview">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <label class="profile-avatar-edit" for="profileImgFile" title="تغيير الصورة">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profileImgFile" accept="image/*" style="display:none" onchange="previewProfileImg(this)">
                        </div>
                        <div class="profile-name" id="profileNameDisplay">-</div>
                        <div class="profile-role-tag" id="profileRoleDisplay">-</div>
                        <div class="profile-username" id="profileUsernameDisplay" style="margin-top:8px;font-size:12px;color:var(--text-muted)"></div>
                    </div>

                    <!-- فورم التعديل -->
                    <div class="profile-form-card">

                        <div class="profile-section-title">
                            <i class="fas fa-info-circle"></i> المعلومات الأساسية
                        </div>
                        <div class="fg">
                            <div class="field">
                                <label>رقم الهاتف</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" id="profilePhone" placeholder="05XXXXXXXX">
                                </div>
                            </div>
                            <div class="field">
                                <label>البريد الإلكتروني</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="profileEmail" placeholder="example@email.com">
                                </div>
                            </div>
                            <div class="field">
                                <label>العنوان / الموقع</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <input type="text" id="profileAddress" placeholder="مثال: نابلس - شارع الجامعة">
                                </div>
                            </div>
                            <div class="field full">
                                <label>نبذة شخصية</label>
                                <textarea id="profileBio" placeholder="اكتب نبذة مختصرة عنك..."></textarea>
                            </div>
                        </div>

                        <div class="profile-section-title" style="margin-top:28px">
                            <i class="fas fa-lock"></i> تغيير كلمة المرور
                        </div>
                        <div class="fg">
                            <div class="field">
                                <label>كلمة المرور الحالية</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="profileOldPass" placeholder="••••••">
                                </div>
                            </div>
                            <div class="field">
                                <label>كلمة المرور الجديدة</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="profileNewPass" placeholder="••••••">
                                </div>
                            </div>
                            <div class="field">
                                <label>تأكيد كلمة المرور</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="profileConfirmPass" placeholder="••••••">
                                </div>
                            </div>
                        </div>

                        <div class="ma" style="margin-top:28px;justify-content:flex-start">
                            <button class="btn btn-primary" onclick="saveProfile()">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                            <button class="btn btn-outline" onclick="loadProfile()">
                                <i class="fas fa-undo"></i> إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- APPOINTMENT MODAL -->
<div class="modal-overlay" id="apptModal">
    <div class="modal">
        <div class="mh"><h3 id="apptMT">إضافة موعد</h3><button class="mcl" onclick="closeModal('apptModal')"><i class="fas fa-times"></i></button></div>
        <div class="fg">
            <div class="field"><label>المريض</label><select id="apptPat"></select></div>
            <div class="field"><label>الطبيب</label><select id="apptDoc"></select></div>
            <div class="field"><label>التاريخ</label><input type="date" id="apptDate"></div>
            <div class="field"><label>الوقت</label><input type="time" id="apptTime"></div>
            <div class="field"><label>الحالة</label>
                <select id="apptStat">
                    <option value="pending">معلق</option>
                    <option value="confirmed">مؤكد</option>
                    <option value="completed">منتهي</option>
                    <option value="cancelled">ملغي</option>
                </select>
            </div>
            <div class="field"><label>الملف الطبي</label><input type="text" id="apptFile" placeholder="file.pdf"></div>
            <div class="field full"><label>ملاحظات</label><textarea id="apptNotes"></textarea></div>
        </div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('apptModal')">إلغاء</button>
            <button class="btn btn-wa" onclick="saveAppt(true)"><i class="fab fa-whatsapp"></i> حفظ + واتساب</button>
            <button class="btn btn-primary" onclick="saveAppt(false)"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </div>
</div>

<!-- DOCTOR MODAL -->
<div class="modal-overlay" id="docModal">
    <div class="modal">
        <div class="mh"><h3 id="docMT">إضافة طبيب</h3><button class="mcl" onclick="closeModal('docModal')"><i class="fas fa-times"></i></button></div>
        <div class="fg">
            <div class="field full"><label>اسم المستخدم (للدخول)</label><input type="text" id="docUsername" placeholder="doctor_name"></div>
            <div class="field full"><label>كلمة المرور</label><input type="password" id="docPassword" placeholder="••••••"></div>
            <div class="field full"><label>الاسم الكامل</label><input type="text" id="docName" placeholder="د. الاسم"></div>
            <div class="field"><label>التخصص</label><select id="docSpec"></select></div>
            <div class="field"><label>البريد</label><input type="email" id="docEmail"></div>
            <div class="field"><label>الهاتف</label><input type="text" id="docPhone" placeholder="05XXXXXXXX"></div>
            <div class="field"><label>صورة</label><input type="text" id="docImg" placeholder="doctor.jpg"></div>
            <div class="field full"><label>نبذة</label><textarea id="docBio"></textarea></div>
        </div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('docModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveDoctor()"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </div>
</div>

<!-- PATIENT MODAL -->
<div class="modal-overlay" id="patModal">
    <div class="modal">
        <div class="mh"><h3 id="patMT">إضافة مريض</h3><button class="mcl" onclick="closeModal('patModal')"><i class="fas fa-times"></i></button></div>
        <div class="fg">
            <div class="field full"><label>رقم الهوية</label><input type="text" id="patId" placeholder="أدخل رقم الهوية"></div>
            <div class="field full"><label>الاسم الكامل</label><input type="text" id="patName"></div>
            <div class="field"><label>الجنس</label>
                <select id="patGender">
                    <option value="male">ذكر</option>
                    <option value="female">أنثى</option>
                </select>
            </div>
            <div class="field"><label>تاريخ الميلاد</label><input type="date" id="patDob"></div>
            <div class="field"><label>الهاتف</label><input type="text" id="patPhone" placeholder="05XXXXXXXX"></div>
            <div class="field"><label>العنوان</label><input type="text" id="patAddr"></div>
        </div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('patModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="savePatient()"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </div>
</div>

<!-- SPEC MODAL -->
<div class="modal-overlay" id="specModal">
    <div class="modal" style="width:400px">
        <div class="mh"><h3 id="specMT">إضافة تخصص</h3><button class="mcl" onclick="closeModal('specModal')"><i class="fas fa-times"></i></button></div>
        <div class="field"><label>اسم التخصص</label><input type="text" id="specName"></div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('specModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveSpec()"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </div>
</div>

<!-- CLINIC SCHEDULE MODAL -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal">
        <div class="mh"><h3 id="scheduleMT">إضافة دوام عيادة</h3><button class="mcl" onclick="closeModal('scheduleModal')"><i class="fas fa-times"></i></button></div>
        <div class="fg">
            <div class="field full"><label>الطبيب</label><select id="schDoctor"></select></div>
            <div class="field"><label>أيام الدوام</label><input type="text" id="schDays" placeholder="مثال: السبت - الأربعاء"></div>
            <div class="field"><label>ساعات الدوام</label><input type="text" id="schHours" placeholder="مثال: 09:00 ص - 03:00 م"></div>
            <div class="field full"><label>الموقع</label><input type="text" id="schLocation" placeholder="مثال: الطابق الثاني - عيادة القلب"></div>
        </div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('scheduleModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveSchedule()"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </div>
</div>

<!-- USER MODAL -->
<div class="modal-overlay" id="userModal">
    <div class="modal" style="width:420px">
        <div class="mh"><h3 id="userMT">إضافة مستخدم</h3><button class="mcl" onclick="closeModal('userModal')"><i class="fas fa-times"></i></button></div>
        <div class="fg" style="grid-template-columns:1fr">
            <div class="field"><label>اسم المستخدم</label><input type="text" id="userName"></div>
            <div class="field"><label>كلمة المرور</label><input type="password" id="userPass"></div>
            <div class="field"><label>الدور</label>
                <select id="userRole">
                    <option value="admin">مدير النظام</option>
                    <option value="receptionist">موظف استقبال</option>
                    <option value="doctor">طبيب</option>
                </select>
            </div>
        </div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('userModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveUser()"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </div>
</div>

<!-- WHATSAPP MODAL -->
<div class="modal-overlay" id="waModal">
    <div class="modal" style="width:520px">
        <div class="mh"><h3><i class="fab fa-whatsapp" style="color:var(--wa);margin-left:8px"></i> إرسال واتساب</h3><button class="mcl" onclick="closeModal('waModal')"><i class="fas fa-times"></i></button></div>
        <div class="fg" style="grid-template-columns:1fr">
            <div class="field"><label>رقم الهاتف</label><input type="text" id="waPhone" placeholder="0599xxxxxx"></div>
            <div class="field"><label>نوع الرسالة</label>
                <select id="waMsgType" onchange="updateWAPreview()">
                    <option value="confirm">تأكيد موعد</option>
                    <option value="remind">تذكير بموعد</option>
                    <option value="cancel">إلغاء موعد</option>
                    <option value="custom">رسالة مخصصة</option>
                </select>
            </div>
            <div class="field"><label>نص الرسالة</label><textarea id="waMsg" style="min-height:100px" oninput="livePreview()"></textarea></div>
        </div>
        <div style="margin:8px 0 4px;font-size:12px;font-weight:700;color:var(--text-muted)">معاينة:</div>
        <div class="wa-preview"><div class="wa-bubble"><div class="wa-hdr">🏥 مركز الشفاء الطبي</div><div id="waPreview"></div></div></div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('waModal')">إلغاء</button>
            <button class="btn btn-wa" onclick="sendWA()"><i class="fab fa-whatsapp"></i> فتح واتساب</button>
        </div>
    </div>
</div>

<!-- MSG VIEW MODAL -->
<div class="modal-overlay" id="msgViewModal">
    <div class="modal">
        <div class="mh"><h3>تفاصيل الرسالة</h3><button class="mcl" onclick="closeModal('msgViewModal')"><i class="fas fa-times"></i></button></div>
        <div id="msgViewContent"></div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('msgViewModal')">إغلاق</button>
            <button class="btn btn-primary" onclick="openReplyFromView()"><i class="fas fa-reply"></i> رد</button>
        </div>
    </div>
</div>

<!-- REPLY MODAL -->
<div class="modal-overlay" id="replyModal">
    <div class="modal" style="width:580px">
        <div class="mh"><h3><i class="fas fa-reply" style="color:var(--primary);margin-left:8px"></i> الرد على الرسالة</h3><button class="mcl" onclick="closeModal('replyModal')"><i class="fas fa-times"></i></button></div>
        <div style="background:#f0f6ff;border-radius:10px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;gap:12px">
            <div class="msg-av" id="replyAv" style="width:44px;height:44px;font-size:18px;flex-shrink:0"></div>
            <div><div style="font-weight:700;font-size:14px" id="replyName"></div><div style="font-size:12px;color:var(--text-muted)" id="replyEmail"></div></div>
            <span class="bs bs-confirmed" style="margin-right:auto"><i class="fas fa-paper-plane" style="margin-left:4px"></i>سيصله على إيميله</span>
        </div>
        <div style="font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:6px">الرسالة الأصلية:</div>
        <div id="replyOrig" style="background:#f9f9f9;border-right:3px solid var(--border);padding:10px 14px;border-radius:8px;font-size:13px;color:var(--text-muted);margin-bottom:18px;line-height:1.7;max-height:80px;overflow-y:auto"></div>
        <div class="field" style="margin-bottom:14px"><label>الموضوع</label><input type="text" id="replySubj"></div>
        <div class="field"><label>نص الرد</label><textarea id="replyBody" style="min-height:120px"></textarea></div>
        <div class="reply-status" id="replySt"></div>
        <div class="ma">
            <button class="btn btn-outline" onclick="closeModal('replyModal')">إلغاء</button>
            <button class="btn btn-primary" id="sendReplyBtn" onclick="sendReply()"><i class="fas fa-paper-plane"></i> إرسال</button>
        </div>
    </div>
</div>

<!-- CONFIRM DELETE -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal confirm-modal">
        <div class="ik">🗑️</div>
        <h3>تأكيد الحذف</h3>
        <p>هل أنت متأكد؟ لا يمكن التراجع عن هذا الإجراء.</p>
        <div style="display:flex;gap:10px;justify-content:center">
            <button class="btn btn-outline" onclick="closeModal('confirmModal')">إلغاء</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">حذف</button>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
    let curUser  = null;
    let curPage  = 'dashboard';
    let editId   = null;
    let delCb    = null;
    let curMsgId = null;
    let waData   = {};
    let chartMap = {};

    const PERMS = {
        admin:        ['dashboard','appointments','doctors','patients','specializations','clinic_schedules','messages','analytics','users','profile'],
        receptionist: ['dashboard','appointments','patients','messages','profile'],
        doctor:       ['dashboard','myappointments','profile'],
    };
    const ROLE_LBL = { admin:'مدير النظام', receptionist:'موظف الاستقبال', doctor:'طبيب' };
    const ROLE_CLS = { admin:'admin', receptionist:'receptionist', doctor:'doctor' };

    const PM = {
        dashboard:        { title:'لوحة التحكم',        sub:'نظرة عامة',                    add:null,              print:false, export:false },
        appointments:     { title:'المواعيد',            sub:'إدارة مواعيد المرضى',          add:'إضافة موعد',      print:true,  export:true  },
        myappointments:   { title:'مواعيدي',             sub:'مواعيدك كطبيب',                add:null,              print:false, export:false },
        doctors:          { title:'الأطباء',             sub:'إدارة الأطباء',                add:'إضافة طبيب',      print:true,  export:true  },
        patients:         { title:'المرضى',              sub:'سجلات المرضى',                 add:'إضافة مريض',      print:true,  export:true  },
        specializations:  { title:'التخصصات',            sub:'التخصصات الطبية',              add:'إضافة تخصص',      print:false, export:false },
        clinic_schedules: { title:'دوام العيادات',       sub:'إدارة مواعيد دوام الأطباء',    add:'إضافة دوام',      print:true,  export:false },
        messages:         { title:'الرسائل',             sub:'رسائل الزوار',                 add:null,              print:false, export:false },
        analytics:        { title:'الإحصائيات',          sub:'تقارير المركز',                add:null,              print:true,  export:false },
        users:            { title:'المستخدمين',          sub:'إدارة الحسابات والصلاحيات',   add:'إضافة مستخدم',    print:false, export:false },
        profile:          { title:'الصفحة الشخصية',     sub:'إدارة معلوماتك الشخصية',       add:null,              print:false, export:false },
    };

    function can(page) { return curUser && PERMS[curUser.role]?.includes(page); }

    function navigate(page) {
        if (!can(page)) { showToast('⛔ ليس لديك صلاحية'); return; }
        curPage = page;
        document.querySelectorAll('.nav-item[data-page]').forEach(el =>
            el.classList.toggle('active', el.getAttribute('data-page') === page));
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.getElementById('page-' + page).classList.add('active');
        const m = PM[page] || {};
        document.getElementById('pageTitle').textContent    = m.title || '';
        document.getElementById('pageSubtitle').textContent = m.sub   || '';
        document.getElementById('addBtn').style.display     = m.add   ? 'inline-flex' : 'none';
        if (m.add) document.getElementById('addBtnText').textContent = m.add;
        document.getElementById('printBtn').style.display  = m.print  ? 'inline-flex' : 'none';
        document.getElementById('exportBtn').style.display = m.export ? 'inline-flex' : 'none';
        renderPage(page);
    }

    function renderPage(p) {
        const fn = {
            dashboard: renderDashboard, appointments: renderAppointments,
            myappointments: renderMyAppts, doctors: renderDoctors,
            patients: renderPatients, specializations: renderSpecs,
            clinic_schedules: renderSchedules, messages: renderMessages,
            analytics: renderAnalytics, users: renderUsers,
            profile: renderProfile,
        };
        fn[p] && fn[p]();
    }

    function applyPerms() {
        const r = curUser.role;
        document.getElementById('sbName').textContent = curUser.username;
        document.getElementById('sbRole').textContent = ROLE_LBL[r] || r;
        const tag = document.getElementById('roleTag');
        tag.textContent = ROLE_LBL[r] || r;
        tag.className   = 'role-tag ' + (ROLE_CLS[r] || 'admin');
        document.querySelectorAll('.nav-item[data-roles]').forEach(el => {
            el.classList.toggle('hidden', !el.getAttribute('data-roles').split(',').includes(r));
        });
    }

    const age = dob => {
        if (!dob) return '-';
        return Math.floor((new Date() - new Date(dob)) / 31557600000) + 'س';
    };
    const ini = n => n ? n.replace('د.','').trim().split(' ').map(w => w[0]||'').join('').slice(0,2) : '?';
    function sb(s) {
        const m = { pending:'bs-pending', confirmed:'bs-confirmed', completed:'bs-completed', cancelled:'bs-cancelled' };
        const t = { pending:'معلق', confirmed:'مؤكد', completed:'منتهي', cancelled:'ملغي' };
        return `<span class="bs ${m[s]||''}">${t[s]||s}</span>`;
    }

    const WA_TPL = {
        confirm: (p,d,a) => `السلام عليكم ${p}،\n\nتم تأكيد موعدك في مركز الشفاء الطبي 🏥\n📅 التاريخ: ${a.appointment_date||a.date}\n⏰ الوقت: ${a.appointment_time||a.time}\n👨‍⚕️ الطبيب: ${d}\n\nنتمنى لك الشفاء العاجل 💚`,
        remind:  (p,d,a) => `تذكير: موعدك غداً في مركز الشفاء الطبي 🏥\n📅 ${a.appointment_date||a.date} ⏰ ${a.appointment_time||a.time}\n👨‍⚕️ ${d}\n\nنرجو الحضور قبل 15 دقيقة ⏳`,
        cancel:  (p,d,a) => `عزيزنا ${p}،\n\nنأسف لإبلاغك بإلغاء موعدك بتاريخ ${a.appointment_date||a.date}.\nيرجى التواصل معنا لتحديد موعد جديد 📞`,
        custom:  ()      => '',
    };

    function openWA(patName, phone, appt, docName) {
        waData = { patName, docName, appt };
        document.getElementById('waPhone').value   = phone || '';
        document.getElementById('waMsgType').value = 'confirm';
        document.getElementById('waMsg').value     = WA_TPL.confirm(patName, docName, appt);
        updateWAPreview();
        document.getElementById('waModal').classList.add('open');
    }
    function updateWAPreview() {
        const t = document.getElementById('waMsgType').value;
        if (t !== 'custom') {
            document.getElementById('waMsg').value = WA_TPL[t]?.(waData.patName, waData.docName, waData.appt) || '';
        }
        document.getElementById('waPreview').innerHTML = document.getElementById('waMsg').value.replace(/\n/g,'<br>');
    }
    function livePreview() {
        document.getElementById('waPreview').innerHTML = document.getElementById('waMsg').value.replace(/\n/g,'<br>');
    }
    function sendWA() {
        let ph = document.getElementById('waPhone').value.replace(/\D/g,'');
        const msg = document.getElementById('waMsg').value.trim();
        if (!ph || !msg) { showToast('❌ أدخل رقم الهاتف والرسالة'); return; }
        if (ph.startsWith('0')) ph = '970' + ph.slice(1);
        window.open(`https://wa.me/${ph}?text=${encodeURIComponent(msg)}`, '_blank');
        closeModal('waModal');
        showToast('✅ تم فتح واتساب');
    }

    function doExport() {
        const rows = window._lastExportData || [];
        if (!rows.length) { showToast('لا يوجد بيانات للتصدير'); return; }
        let csv = '', fn = curPage + '.csv';
        if (curPage === 'appointments') {
            csv = '#,المريض,الطبيب,التاريخ,الوقت,الحالة,الملاحظات\n' +
                rows.map((a,i) => `${i+1},"${a.patient_name||''}","${a.doctor_name||''}","${a.appointment_date||''}","${a.appointment_time||''}","${a.status||''}","${a.notes||''}"`).join('\n');
        } else if (curPage === 'doctors') {
            csv = '#,الاسم,التخصص,البريد,الهاتف\n' +
                rows.map((d,i) => `${i+1},"${d.full_name||''}","${d.specialty_name||''}","${d.email||''}","${d.phone||''}"`).join('\n');
        } else if (curPage === 'patients') {
            csv = 'رقم_الهوية,الاسم,الجنس,تاريخ_الميلاد,الهاتف,العنوان\n' +
                rows.map(p => `"${p.patient_id||''}","${p.full_name||''}","${p.gender==='male'?'ذكر':'أنثى'}","${p.date_of_birth||''}","${p.phone||''}","${p.address||''}"`).join('\n');
        }
        const blob = new Blob(['\uFEFF' + csv], { type:'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob); a.download = fn; a.click();
        showToast('✅ تم تصدير الملف');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('.modal-overlay').forEach(el =>
        el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); }));
    function confirmDel(cb) { delCb = cb; document.getElementById('confirmModal').classList.add('open'); }
    document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
        if (delCb) delCb();
        closeModal('confirmModal');
        delCb = null;
    });

    let tTimer;
    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg; t.style.display = 'block';
        clearTimeout(tTimer);
        tTimer = setTimeout(() => t.style.display = 'none', 3000);
    }

    function openReplyFromView() {
        closeModal('msgViewModal');
        if (curMsgId) openReplyModal(curMsgId);
    }
</script>

<script src="validation.js"></script>
<script src="backend.js"></script>

</body>
</html>