// ═══════════════════════════════════════════
//  validation.js — قيود حقول الإدخال
// ═══════════════════════════════════════════

const V = {
    phone:    v => /^(05|970|00970)\d{8}$/.test(v.replace(/\s/g,'')),
    email:    v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()),
    date:     v => !!v && !isNaN(new Date(v)),
    time:     v => /^\d{2}:\d{2}$/.test(v),
    name:     v => v.trim().length >= 2 && v.trim().length <= 100,
    username: v => /^[a-zA-Z0-9_]{3,30}$/.test(v.trim()),
    password: v => v.length >= 6,
    nonempty: v => v.trim().length > 0,
};

const ERR = {
    phone:    'رقم الهاتف غير صحيح (مثال: 0599123456)',
    email:    'البريد الإلكتروني غير صحيح',
    date:     'التاريخ غير صحيح',
    time:     'الوقت غير صحيح',
    name:     'الاسم يجب أن يكون بين 2 و 100 حرف',
    username: 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام فقط (3-30)',
    password: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
    nonempty: 'هذا الحقل مطلوب',
};

// أضف/ازل class الخطأ على الحقل
function markField(el, ok, msg = '') {
    if (!el) return;
    el.style.borderColor = ok ? '' : 'var(--danger, #e05252)';
    let tip = el.parentElement?.querySelector('.v-tip');
    if (!ok) {
        if (!tip) {
            tip = document.createElement('span');
            tip.className = 'v-tip';
            tip.style.cssText = 'color:#e05252;font-size:11px;display:block;margin-top:3px';
            el.insertAdjacentElement('afterend', tip);
        }
        tip.textContent = msg;
    } else if (tip) {
        tip.remove();
    }
}

// تحقق من حقل واحد وارجع true/false
function validateField(el, rule) {
    const ok = V[rule]?.(el.value) ?? true;
    markField(el, ok, ERR[rule] || '');
    return ok;
}

// ─── Appointment Modal ───────────────────────────────────
function validateApptForm() {
    const pat   = document.getElementById('apptPat');
    const doc   = document.getElementById('apptDoc');
    const date  = document.getElementById('apptDate');
    const time  = document.getElementById('apptTime');

    let ok = true;

    if (!pat?.value)  { markField(pat,  false, 'اختر المريض');  ok = false; }
    else               markField(pat,  true);

    if (!doc?.value)  { markField(doc,  false, 'اختر الطبيب');  ok = false; }
    else               markField(doc,  true);

    if (!validateField(date, 'date')) ok = false;
    if (!validateField(time, 'time')) ok = false;

    // تحقق أن التاريخ مش في الماضي
    if (date?.value) {
        const chosen = new Date(date.value);
        const today  = new Date(); today.setHours(0,0,0,0);
        if (chosen < today) {
            markField(date, false, 'لا يمكن اختيار تاريخ في الماضي');
            ok = false;
        }
    }

    return ok;
}

// ─── Doctor Modal ────────────────────────────────────────
function validateDoctorForm(isEdit = false) {
    let ok = true;
    const fields = [
        ['docName',  'name'],
        ['docEmail', 'email'],
        ['docPhone', 'phone'],
    ];
    if (!isEdit) {
        fields.push(['docUsername', 'username']);
        fields.push(['docPassword', 'password']);
    }
    fields.forEach(([id, rule]) => {
        const el = document.getElementById(id);
        if (el && !validateField(el, rule)) ok = false;
    });
    return ok;
}

// ─── Patient Modal ───────────────────────────────────────
function validatePatientForm() {
    let ok = true;
    if (!validateField(document.getElementById('patName'),  'name'))    ok = false;
    if (!validateField(document.getElementById('patPhone'), 'phone'))   ok = false;
    const dob = document.getElementById('patDob');
    if (dob?.value && !V.date(dob.value)) {
        markField(dob, false, 'تاريخ ميلاد غير صحيح');
        ok = false;
    } else if (dob) markField(dob, true);
    return ok;
}

// ─── User Modal ──────────────────────────────────────────
function validateUserForm(isEdit = false) {
    let ok = true;
    if (!validateField(document.getElementById('userName'), 'username')) ok = false;
    if (!isEdit) {
        if (!validateField(document.getElementById('userPass'), 'password')) ok = false;
    }
    return ok;
}

// ─── Spec Modal ──────────────────────────────────────────
function validateSpecForm() {
    return validateField(document.getElementById('specName'), 'nonempty');
}

// ─── Real-time hints — ربط الأحداث ──────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const bindings = [
        ['docPhone',  'phone'],
        ['patPhone',  'phone'],
        ['docEmail',  'email'],
        ['apptDate',  'date'],
        ['apptTime',  'time'],
        ['docName',   'name'],
        ['patName',   'name'],
        ['docUsername','username'],
        ['docPassword','password'],
        ['userName',  'username'],
        ['specName',  'nonempty'],
    ];
    bindings.forEach(([id, rule]) => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('blur', () => validateField(el, rule));
            el.addEventListener('input', () => {
                if (el.style.borderColor) validateField(el, rule);
            });
        }
    });

    // فلترة رقم الهاتف — أرقام فقط
    ['docPhone','patPhone','waPhone'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => {
            el.value = el.value.replace(/[^\d+\s]/g, '');
        });
    });
});