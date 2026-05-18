async function http(file, method = 'GET', body = null, params = {}) {
    const url = new URL(file, location.href);

    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') {
            url.searchParams.set(k, v);
        }
    });

    const res = await fetch(url, {
        method: method,
        credentials: 'same-origin',
        headers: body ? { 'Content-Type': 'application/json' } : {},
        body: body ? JSON.stringify(body) : null
    });

    const data = await res.json();

    if (!data.success) {
        throw new Error(data.message || 'خطأ غير معروف');
    }

    return data.data;
}

async function doLogin() {
    const u = document.getElementById('loginUser').value.trim();
    const p = document.getElementById('loginPass').value.trim();

    try {
        const userData = await http('Login.php', 'POST', {
            username: u,
            password: p
        });

        curUser = userData;

        document.getElementById('loginPage').style.display = 'none';
        document.getElementById('app').style.display = 'block';
        document.getElementById('loginError').style.display = 'none';

        applyPerms();
        navigate('dashboard');

    } catch (e) {
        document.getElementById('loginError').style.display = 'block';
    }
}

async function doLogout() {
    await http('Logout.php', 'POST').catch(() => {});
    curUser = null;
    document.getElementById('loginPage').style.display = 'flex';
    document.getElementById('app').style.display = 'none';
}

async function renderDashboard() {
    try {
        const s = await http('Stats.php');

        // الطبيب ما يشوف إحصائيات الكل
        if (curUser.role === 'doctor') {
            document.getElementById('s-appt').textContent = s.my_appointments_total || 0;
            document.getElementById('s-doc').textContent  = '-';
            document.getElementById('s-pat').textContent  = '-';
            document.getElementById('s-msg').textContent  = '-';
        } else {
            document.getElementById('s-appt').textContent = s.appointments_total || 0;
            document.getElementById('s-doc').textContent  = s.doctors_total      || 0;
            document.getElementById('s-pat').textContent  = s.patients_total     || 0;
            document.getElementById('s-msg').textContent  = s.messages_total     || 0;
            document.getElementById('apptBadge').textContent = s.appointments_pending || 0;
        }

    } catch (e) {}

    try {
        const params = curUser.role === 'doctor' ? { my_appointments: 1 } : {};
        const appts  = await http('Appointments.php', 'GET', null, params);

        document.getElementById('dash-appts').innerHTML = appts.slice(0, 5).map(a => `
            <div class="appt-item">
                <div class="appt-time">${a.appointment_time || ''}</div>
                <div class="appt-info">
                    <div class="nm">${a.patient_name || '-'}</div>
                    <div class="dc">${a.doctor_name  || '-'}</div>
                </div>
                ${sb(a.status)}
            </div>
        `).join('') || '<div class="empty-state"><i class="fas fa-calendar-times"></i><p>لا توجد مواعيد</p></div>';

    } catch (e) {}

    // الرسائل للأدمن والاستقبال فقط
    if (curUser.role !== 'doctor') {
        try {
            const msgs = await http('Messages.php');
            document.getElementById('dash-msgs').innerHTML = msgs.slice(0, 5).map(m => `
                <div class="msg-item">
                    <div class="msg-av">${ini(m.full_name)}</div>
                    <div>
                        <div style="font-size:13px;font-weight:600">${m.subject  || ''}</div>
                        <div style="font-size:11px;color:var(--text-muted)">${m.full_name || ''}</div>
                    </div>
                </div>
            `).join('') || '<div class="empty-state"><i class="fas fa-envelope-open"></i><p>لا توجد رسائل</p></div>';
        } catch (e) {}
    } else {
        document.getElementById('dash-msgs').innerHTML =
            '<div class="empty-state"><i class="fas fa-envelope-open"></i><p>لا توجد رسائل</p></div>';
    }
}

async function renderAppointments() {
    const q = document.getElementById('apptSearch')?.value?.trim() || '';
    const st = document.getElementById('apptSF')?.value || '';

    const params = {};
    if (st) params.status = st;
    if (q) params.q = q;

    try {
        const rows = await http('Appointments.php', 'GET', null, params);
        window._lastExportData = rows;

        document.getElementById('apptTable').innerHTML = rows.length ? rows.map((a, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="avc">
                        <div class="avatar">${ini(a.patient_name)}</div>
                        <div class="av-name">${a.patient_name || '-'}</div>
                    </div>
                </td>
                <td>
                    <div class="avc">
                        <div class="avatar" style="background:var(--medical-gradient)">${ini(a.doctor_name)}</div>
                        <div class="av-name">${a.doctor_name || '-'}</div>
                    </div>
                </td>
                <td>${a.appointment_date || '-'}</td>
                <td>${a.appointment_time || '-'}</td>
                <td>${sb(a.status)}</td>
                <td>${a.notes || '-'}</td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-sm btn-outline btn-icon" onclick="editApptFromDB(${a.appointment_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-wa btn-sm btn-icon" onclick="openWAFromDB('${(a.patient_name || '').replace(/'/g, "\\'")}', '${a.appointment_date || ''}', '${a.appointment_time || ''}', '${(a.doctor_name || '').replace(/'/g, "\\'")}', '${a.patient_phone || ''}')">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDel(() => delApptDB(${a.appointment_id}))">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>لا توجد مواعيد</p>
                    </div>
                </td>
            </tr>
        `;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function editApptFromDB(id) {
    try {
        const rows = await http('Appointments.php');
        const a = rows.find(x => Number(x.appointment_id) === Number(id));

        if (!a) return;

        editId = a.appointment_id;

        document.getElementById('apptMT').textContent = 'تعديل الموعد';

        await loadPatientsDropdown('apptPat', a.patient_id);
        await loadDoctorsDropdown('apptDoc', a.doctor_id);

        document.getElementById('apptDate').value = a.appointment_date || '';
        document.getElementById('apptTime').value = a.appointment_time || '';
        document.getElementById('apptStat').value = a.status || 'pending';
        document.getElementById('apptFile').value = a.medical_file || '';
        document.getElementById('apptNotes').value = a.notes || '';

        document.getElementById('apptModal').classList.add('open');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function saveAppt(withWA = false) {
    if (!validateApptForm()) return;
    const body = {
        patient_id: Number(document.getElementById('apptPat').value),
        doctor_id: Number(document.getElementById('apptDoc').value),
        appointment_date: document.getElementById('apptDate').value,
        appointment_time: document.getElementById('apptTime').value,
        status: document.getElementById('apptStat').value,
        medical_file: document.getElementById('apptFile').value,
        notes: document.getElementById('apptNotes').value
    };

    try {
        if (editId) {
            body.appointment_id = editId;
            await http('Appointments.php', 'PUT', body);
            showToast('تم تعديل الموعد ✅');
        } else {
            const r = await http('Appointments.php', 'POST', body);
            body.appointment_id = r.appointment_id;
            showToast('تم إضافة الموعد ✅');
        }

        closeModal('apptModal');
        renderAppointments();
        renderDashboard();

        if (withWA) {
            const patSelect = document.getElementById('apptPat');
            const docSelect = document.getElementById('apptDoc');

            const pat = patSelect.options[patSelect.selectedIndex].text;
            const doc = docSelect.options[docSelect.selectedIndex].text;

            waData = {
                patName: pat,
                docName: doc,
                appt: body
            };

            document.getElementById('waPhone').value = '';
            document.getElementById('waMsgType').value = 'confirm';
            document.getElementById('waMsg').value = WA_TPL.confirm(pat, doc, body);
            updateWAPreview();
            document.getElementById('waModal').classList.add('open');
        }

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function delApptDB(id) {
    try {
        await http('Appointments.php', 'DELETE', {
            appointment_id: id
        });

        renderAppointments();
        renderDashboard();
        showToast('تم حذف الموعد ✅');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

function openWAFromDB(patName, date, time, docName, phone) {
    const appt = {
        appointment_date: date,
        appointment_time: time
    };

    waData = {
        patName: patName,
        docName: docName,
        appt: appt
    };

    document.getElementById('waPhone').value = phone;
    document.getElementById('waMsgType').value = 'confirm';
    document.getElementById('waMsg').value = WA_TPL.confirm(patName, docName, appt);
    updateWAPreview();
    document.getElementById('waModal').classList.add('open');
}

async function renderDoctors() {
    const q = document.getElementById('docSearch')?.value?.trim() || '';
    const sp = document.getElementById('docSpecF')?.value || '';

    const params = {};
    if (sp) params.specialty_id = sp;
    if (q) params.q = q;

    try {
        const rows = await http('doctors.php', 'GET', null, params);
        window._lastExportData = rows;

        const sf = document.getElementById('docSpecF');

        if (sf && sf.options.length <= 1) {
            const specs = await http('Specializations.php');

            specs.forEach(s => {
                const o = document.createElement('option');
                o.value = s.specialty_id;
                o.textContent = s.specialty_name;
                sf.appendChild(o);
            });
        }

        document.getElementById('docTable').innerHTML = rows.length ? rows.map((d, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="avc">
                        <div class="avatar">${ini(d.full_name)}</div>
                        <div class="av-name">${d.full_name || '-'}</div>
                    </div>
                </td>
                <td>${d.specialty_name || '-'}</td>
                <td>${d.email || '-'}</td>
                <td>${d.phone || '-'}</td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${d.bio || '-'}</td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-sm btn-outline btn-icon" onclick="editDocFromDB(${d.doctor_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDel(() => delDocDB(${d.doctor_id}))">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <i class="fas fa-user-md"></i>
                        <p>لا يوجد أطباء</p>
                    </div>
                </td>
            </tr>
        `;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function editDocFromDB(id) {
    try {
        const rows = await http('doctors.php');
        const d = rows.find(x => Number(x.doctor_id) === Number(id));

        if (!d) return;

        editId = d.doctor_id;

        document.getElementById('docMT').textContent = 'تعديل الطبيب';

        const specs = await http('Specializations.php');
        const ss = document.getElementById('docSpec');

        ss.innerHTML = specs.map(s => `<option value="${s.specialty_id}">${s.specialty_name}</option>`).join('');
        ss.value = d.specialty_id;

        document.getElementById('docName').value = d.full_name || '';
        document.getElementById('docEmail').value = d.email || '';
        document.getElementById('docPhone').value = d.phone || '';
        document.getElementById('docBio').value = d.bio || '';

        const imgFile = document.getElementById('docImgFile');
        if (imgFile) imgFile.value = '';

        const preview = document.getElementById('docImgPreview');
        const previewImg = document.getElementById('docImgPreviewImg');

        if (preview && previewImg && d.image_url) {
            previewImg.src = d.image_url;
            preview.style.display = 'block';
        } else if (preview) {
            preview.style.display = 'none';
        }

        document.getElementById('docModal').classList.add('open');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function httpForm(file, method, formData) {
    const url = new URL(file, location.href);

    const res = await fetch(url, {
        method: method,
        credentials: 'same-origin',
        body: formData
    });

    const data = await res.json();

    if (!data.success) {
        throw new Error(data.message || 'خطأ غير معروف');
    }

    return data.data;
}

async function saveDoctor() {
    if (!validateDoctorForm(!!editId)) return;
    const fd = new FormData();

    fd.append('full_name', document.getElementById('docName').value.trim());
    fd.append('specialty_id', document.getElementById('docSpec').value);
    fd.append('email', document.getElementById('docEmail').value.trim());
    fd.append('phone', document.getElementById('docPhone').value.trim());
    fd.append('bio', document.getElementById('docBio').value.trim());

    const imgFile = document.getElementById('docImgFile')?.files?.[0];

    if (imgFile) {
        fd.append('image', imgFile);
    }

    try {
        if (editId) {
            fd.append('doctor_id', editId);

            if (imgFile) {
                await httpForm('upload_doctor_image.php', 'POST', fd);
            } else {
                await http('doctors.php', 'PUT', {
                    doctor_id: editId,
                    full_name: document.getElementById('docName').value.trim(),
                    specialty_id: Number(document.getElementById('docSpec').value),
                    email: document.getElementById('docEmail').value.trim(),
                    phone: document.getElementById('docPhone').value.trim(),
                    bio: document.getElementById('docBio').value.trim()
                });
            }

            showToast('تم تعديل الطبيب ✅');

        } else {
            fd.append('username', document.getElementById('docUsername').value.trim());
            fd.append('password', document.getElementById('docPassword').value.trim());

            await httpForm('doctors.php', 'POST', fd);

            showToast('تم إضافة الطبيب ✅');
        }

        closeModal('docModal');
        renderDoctors();
        renderDashboard();

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function delDocDB(id) {
    try {
        await http('doctors.php', 'DELETE', {
            doctor_id: id
        });

        renderDoctors();
        renderDashboard();
        showToast('تم حذف الطبيب ✅');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function renderPatients() {
    const q = document.getElementById('patSearch')?.value?.trim() || '';
    const gn = document.getElementById('patGF')?.value || '';

    const params = {};
    if (gn) params.gender = gn;
    if (q) params.q = q;

    try {
        const rows = await http('Patients.php', 'GET', null, params);
        window._lastExportData = rows;

        document.getElementById('patTable').innerHTML = rows.length ? rows.map((p, i) => `
            <tr>
                <td>${p.patient_id}</td>
                <td>
                    <div class="avc">
                        <div class="avatar" style="background:${p.gender === 'female' ? 'linear-gradient(135deg,#e05252,#f5a6a6)' : 'var(--medical-gradient)'}">${ini(p.full_name)}</div>
                        <div class="av-name">${p.full_name || '-'}</div>
                    </div>
                </td>
                <td>${p.gender === 'male' ? 'ذكر' : 'أنثى'}</td>
                <td>${p.date_of_birth || '-'} (${age(p.date_of_birth)})</td>
                <td>${p.phone || '-'}</td>
                <td>${p.address || '-'}</td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-sm btn-outline btn-icon" onclick="editPatFromDB(${p.patient_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDel(() => delPatDB(${p.patient_id}))">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>لا يوجد مرضى</p>
                    </div>
                </td>
            </tr>
        `;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function editPatFromDB(id) {
    try {
        const rows = await http('Patients.php');
        const p = rows.find(x => Number(x.patient_id) === Number(id));

        if (!p) return;

        editId = p.patient_id;

        document.getElementById('patMT').textContent = 'تعديل المريض';
        document.getElementById('patName').value = p.full_name || '';
        document.getElementById('patGender').value = p.gender || 'male';
        document.getElementById('patDob').value = p.date_of_birth || '';
        document.getElementById('patPhone').value = p.phone || '';
        document.getElementById('patAddr').value = p.address || '';

        document.getElementById('patModal').classList.add('open');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function savePatient() {
    const body = {
        patient_id: document.getElementById('patId').value.trim(),
        full_name: document.getElementById('patName').value.trim(),
        gender: document.getElementById('patGender').value,
        date_of_birth: document.getElementById('patDob').value,
        phone: document.getElementById('patPhone').value.trim(),
        address: document.getElementById('patAddr').value.trim()
    };

    if (!body.patient_id || !body.full_name) {
        showToast('❌ رقم الهوية والاسم مطلوبان');
        return;
    }

    try {
        if (editId) {
            body.old_patient_id = editId;
            await http('Patients.php', 'PUT', body);
            showToast('✅ تم تعديل المريض بنجاح');
        } else {
            await http('Patients.php', 'POST', body);
            showToast('✅ تمت إضافة المريض بنجاح');
        }

        closeModal('patModal');
        editId = null;
        renderPatients();
        renderDashboard();

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function delPatDB(id) {
    try {
        await http('Patients.php', 'DELETE', {
            patient_id: id
        });

        renderPatients();
        showToast('تم حذف المريض ✅');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function renderSpecs() {
    try {
        const rows = await http('Specializations.php');

        document.getElementById('specTable').innerHTML = rows.length ? rows.map((s, i) => `
            <tr>
                <td>${i + 1}</td>
                <td><strong>${s.specialty_name || '-'}</strong></td>
                <td><span class="bs bs-confirmed">${s.doctor_count || 0} طبيب</span></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-sm btn-outline btn-icon" onclick="editSpecFromDB(${s.specialty_id}, '${(s.specialty_name || '').replace(/'/g, "\\'")}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDel(() => delSpecDB(${s.specialty_id}))">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="4">
                    <div class="empty-state">
                        <i class="fas fa-stethoscope"></i>
                        <p>لا توجد تخصصات</p>
                    </div>
                </td>
            </tr>
        `;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

function editSpecFromDB(id, name) {
    editId = id;
    document.getElementById('specMT').textContent = 'تعديل التخصص';
    document.getElementById('specName').value = name;
    document.getElementById('specModal').classList.add('open');
}

async function saveSpec() {
    if (!validateSpecForm()) return;
    const name = document.getElementById('specName').value.trim();

    if (!name) return;

    try {
        if (editId) {
            await http('Specializations.php', 'PUT', {
                specialty_id: editId,
                specialty_name: name
            });

            showToast('تم تعديل التخصص ✅');
        } else {
            await http('Specializations.php', 'POST', {
                specialty_name: name
            });

            showToast('تم إضافة التخصص ✅');
        }

        closeModal('specModal');
        renderSpecs();

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function delSpecDB(id) {
    try {
        await http('Specializations.php', 'DELETE', {
            specialty_id: id
        });

        renderSpecs();
        showToast('تم حذف التخصص ✅');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function renderMessages() {
    try {
        const msgs = await http('Messages.php');

        document.getElementById('messagesList').innerHTML = msgs.length ? msgs.map(m => `
            <div class="msg-card">
                <div class="msg-card-header">
                    <div class="msg-from">
                        <div class="msg-av">${ini(m.full_name)}</div>
                        <div>
                            <div style="font-weight:700;font-size:14px">${m.full_name || '-'}</div>
                            <div class="msg-meta">${m.email || ''} · ${m.phone || ''}</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button class="btn btn-sm btn-primary" onclick="openReplyModal(${m.message_id})">
                            <i class="fas fa-reply"></i> رد
                        </button>
                        <button class="btn btn-sm btn-outline btn-icon" onclick="viewMsg(${m.message_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDel(() => delMsgDB(${m.message_id}))">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="msg-subject">${m.subject || ''}</div>
                <div class="msg-body">${m.message || ''}</div>
            </div>
        `).join('') : `
            <div class="empty-state" style="padding:60px">
                <i class="fas fa-inbox" style="font-size:48px;display:block;margin-bottom:14px;opacity:.2"></i>
                <p>لا توجد رسائل</p>
            </div>
        `;

        window._msgs = msgs;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

function viewMsg(id) {
    const m = (window._msgs || []).find(x => Number(x.message_id) === Number(id));

    if (!m) return;

    curMsgId = id;

    document.getElementById('msgViewContent').innerHTML = `
        <div style="background:#f0f6ff;border-radius:12px;padding:20px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div class="msg-av" style="width:44px;height:44px;font-size:18px">${ini(m.full_name)}</div>
                <div>
                    <div style="font-weight:800;font-size:16px">${m.full_name || '-'}</div>
                    <div style="font-size:12px;color:var(--text-muted)">${m.email || ''} · ${m.phone || ''}</div>
                </div>
            </div>
            <div style="font-weight:700;font-size:15px;margin-bottom:8px;color:var(--primary)">${m.subject || ''}</div>
            <div style="font-size:14px;line-height:1.9">${m.message || ''}</div>
        </div>
    `;

    document.getElementById('msgViewModal').classList.add('open');
}

function openReplyModal(msgId) {
    const m = (window._msgs || []).find(x => Number(x.message_id) === Number(msgId));

    if (!m) return;

    curMsgId = msgId;

    document.getElementById('replyAv').textContent = ini(m.full_name);
    document.getElementById('replyName').textContent = m.full_name || '';
    document.getElementById('replyEmail').textContent = m.email || '(لا يوجد إيميل)';
    document.getElementById('replyOrig').textContent = m.message || '';
    document.getElementById('replySubj').value = 'رد: ' + (m.subject || '');
    document.getElementById('replyBody').value = '';

    const st = document.getElementById('replySt');
    st.className = 'reply-status';
    st.innerHTML = '';

    const btn = document.getElementById('sendReplyBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> إرسال';

    document.getElementById('replyModal').classList.add('open');
}

async function sendReply() {
    const m = (window._msgs || []).find(x => Number(x.message_id) === Number(curMsgId));

    if (!m) return;

    const subj = document.getElementById('replySubj').value.trim();
    const body = document.getElementById('replyBody').value.trim();
    const st = document.getElementById('replySt');
    const btn = document.getElementById('sendReplyBtn');

    if (!subj || !body) {
        st.className = 'reply-status error';
        st.innerHTML = '<i class="fas fa-exclamation-circle"></i> الموضوع والرد مطلوبان';
        return;
    }

    if (!m.email) {
        st.className = 'reply-status error';
        st.innerHTML = '<i class="fas fa-exclamation-circle"></i> لا يوجد إيميل';
        return;
    }

    st.className = 'reply-status loading';
    st.innerHTML = '<i class="fas fa-circle-notch spin"></i> جاري الإرسال...';
    btn.disabled = true;

    try {
        await http('send_reply.php', 'POST', {
            to_email: m.email,
            to_name: m.full_name,
            subject: subj,
            body: body
        });

        st.className = 'reply-status success';
        st.innerHTML = '<i class="fas fa-check-circle"></i> تم الإرسال';
        btn.innerHTML = '<i class="fas fa-check"></i> تم';

        showToast('تم إرسال الرد ✅');

        setTimeout(() => closeModal('replyModal'), 2000);

    } catch (e) {
        st.className = 'reply-status error';
        st.innerHTML = '<i class="fas fa-times-circle"></i> ' + e.message;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> إعادة';
    }
}

async function delMsgDB(id) {
    try {
        await http('Messages.php', 'DELETE', {
            message_id: id
        });

        renderMessages();
        renderDashboard();
        showToast('تم حذف الرسالة ✅');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function renderUsers() {
    try {
        const rows = await http('Users.php');

        const rl = {
            admin: 'مدير النظام',
            receptionist: 'موظف استقبال',
            doctor: 'طبيب',
            patient: 'مريض'
        };

        const rb = {
            admin: 'bs-confirmed',
            receptionist: 'bs-pending',
            doctor: 'bs-completed',
            patient: 'bs-cancelled'
        };

        document.getElementById('userTable').innerHTML = rows.length ? rows.map((u, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="avc">
                        <div class="avatar" style="background:var(--medical-gradient)">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="av-name">${u.username || '-'}</div>
                    </div>
                </td>
                <td><span class="bs ${rb[u.role] || 'bs-pending'}">${rl[u.role] || u.role}</span></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-sm btn-outline btn-icon" onclick="editUsrFromDB(${u.user_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-icon" onclick="confirmDel(() => delUsrDB(${u.user_id}))">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="4">
                    <div class="empty-state">
                        <i class="fas fa-users-cog"></i>
                        <p>لا يوجد مستخدمون</p>
                    </div>
                </td>
            </tr>
        `;

        window._users = rows;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

function editUsrFromDB(id) {
    const u = (window._users || []).find(x => Number(x.user_id) === Number(id));

    if (!u) return;

    editId = u.user_id;

    document.getElementById('userMT').textContent = 'تعديل المستخدم';
    document.getElementById('userName').value = u.username || '';
    document.getElementById('userPass').value = '';
    document.getElementById('userRole').value = u.role || 'admin';

    document.getElementById('userModal').classList.add('open');
}

async function saveUser() {
    if (!validateUserForm(!!editId)) return;
    const body = {
        username: document.getElementById('userName').value,
        password: document.getElementById('userPass').value,
        role: document.getElementById('userRole').value
    };

    try {
        if (editId) {
            body.user_id = editId;
            await http('Users.php', 'PUT', body);
            showToast('تم تعديل المستخدم ✅');
        } else {
            await http('Users.php', 'POST', body);
            showToast('تم إضافة المستخدم ✅');
        }

        closeModal('userModal');
        renderUsers();

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function delUsrDB(id) {
    try {
        await http('Users.php', 'DELETE', {
            user_id: id
        });

        renderUsers();
        showToast('تم حذف المستخدم ✅');

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function renderMyAppts() {
    try {
        const rows = await http('Appointments.php', 'GET', null, { my_appointments: 1 });

        document.getElementById('myApptTable').innerHTML = rows.length ? rows.map((a, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${a.patient_name || '-'}</td>
                <td>${a.appointment_date || '-'}</td>
                <td>${a.appointment_time || '-'}</td>
                <td>${sb(a.status)}</td>
                <td>${a.notes || '-'}</td>
                <td>
                    <button class="btn btn-wa btn-sm" onclick="openWAFromDB('${(a.patient_name || '').replace(/'/g, "\\'")}', '${a.appointment_date || ''}', '${a.appointment_time || ''}', '${(a.doctor_name || '').replace(/'/g, "\\'")}', '${a.patient_phone || ''}')">
                        <i class="fab fa-whatsapp"></i>
                    </button>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>لا توجد مواعيد</p>
                    </div>
                </td>
            </tr>
        `;

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

async function renderAnalytics() {
    Object.values(chartMap || {}).forEach(c => {
        if (c) c.destroy();
    });

    chartMap = {};

    try {
        const s = await http('Stats.php');

        const statusLabels = {
            pending: 'معلق',
            confirmed: 'مؤكد',
            completed: 'منتهي',
            cancelled: 'ملغي',
            rejected: 'مرفوض'
        };

        const byStatus = s.by_status || [];

        chartMap.status = new Chart(document.getElementById('chartStatus'), {
            type: 'bar',
            data: {
                labels: byStatus.map(x => statusLabels[x.status] || x.status),
                datasets: [{
                    data: byStatus.map(x => Number(x.cnt)),
                    backgroundColor: ['#f5a623', '#0062ff', '#00c896', '#e05252', '#9b59b6'],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        const gd = s.gender_distribution || [];
        const males = Number(gd.find(x => x.gender === 'male')?.cnt || 0);
        const females = Number(gd.find(x => x.gender === 'female')?.cnt || 0);

        chartMap.gender = new Chart(document.getElementById('chartGender'), {
            type: 'doughnut',
            data: {
                labels: ['ذكور', 'إناث'],
                datasets: [{
                    data: [males, females],
                    backgroundColor: ['#0062ff', '#00b4d8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'Cairo',
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        chartMap.spec = new Chart(document.getElementById('chartSpec'), {
            type: 'bar',
            data: {
                labels: (s.by_specialty || []).map(x => x.specialty_name),
                datasets: [{
                    data: (s.by_specialty || []).map(x => Number(x.cnt)),
                    backgroundColor: 'rgba(10,110,110,.7)',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        const topDoctors = s.top_doctors || [];
        const mx = Math.max(...topDoctors.map(x => Number(x.cnt)), 1);

        document.getElementById('topDoctors').innerHTML = topDoctors.map(({ full_name, cnt }) => `
            <div class="top-doc-item">
                <div class="avatar" style="width:30px;height:30px;font-size:11px;border-radius:8px;flex-shrink:0">${ini(full_name)}</div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:600;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${full_name}</div>
                    <div class="tdb">
                        <div class="fill" style="width:${Math.round(Number(cnt) / mx * 100)}%"></div>
                    </div>
                </div>
                <div class="tdc">${cnt}</div>
            </div>
        `).join('');

    } catch (e) {
        showToast('❌ خطأ في الإحصائيات: ' + e.message);
    }
}

async function openAddModal() {
    editId = null;
    if (curPage === 'clinic_schedules') {
        editId = null;

        document.getElementById('scheduleMT').textContent = 'إضافة دوام عيادة';
        document.getElementById('schDays').value = '';
        document.getElementById('schHours').value = '';
        document.getElementById('schLocation').value = '';

        fillScheduleDoctors();

        document.getElementById('scheduleModal').classList.add('open');
        return;
    }
    if (curPage === 'appointments') {
        document.getElementById('apptMT').textContent = 'إضافة موعد جديد';

        await loadPatientsDropdown('apptPat');
        await loadDoctorsDropdown('apptDoc');

        ['apptDate', 'apptTime', 'apptFile', 'apptNotes'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        document.getElementById('apptStat').value = 'pending';
        document.getElementById('apptModal').classList.add('open');

    } else if (curPage === 'doctors') {
        document.getElementById('docMT').textContent = 'إضافة طبيب جديد';

        const specs = await http('Specializations.php');
        const ss = document.getElementById('docSpec');

        ss.innerHTML = specs.map(s => `<option value="${s.specialty_id}">${s.specialty_name}</option>`).join('');

        ['docUsername', 'docPassword', 'docName', 'docEmail', 'docPhone', 'docBio'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        const imgFile = document.getElementById('docImgFile');
        if (imgFile) imgFile.value = '';

        const preview = document.getElementById('docImgPreview');
        if (preview) preview.style.display = 'none';

        document.getElementById('docModal').classList.add('open');

    } else if (curPage === 'patients') {
        document.getElementById('patMT').textContent = 'إضافة مريض جديد';

        ['patName', 'patDob', 'patPhone', 'patAddr'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        document.getElementById('patGender').value = 'male';
        document.getElementById('patModal').classList.add('open');

    } else if (curPage === 'specializations') {
        document.getElementById('specMT').textContent = 'إضافة تخصص';
        document.getElementById('specName').value = '';
        document.getElementById('specModal').classList.add('open');

    } else if (curPage === 'users') {
        document.getElementById('userMT').textContent = 'إضافة مستخدم';

        ['userName', 'userPass'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        document.getElementById('userRole').value = 'admin';
        document.getElementById('userModal').classList.add('open');
    }
}

async function loadPatientsDropdown(selectId, selectedId = null) {
    const rows = await http('Patients.php');
    const el = document.getElementById(selectId);

    el.innerHTML = rows.map(p => `<option value="${p.patient_id}">${p.full_name}</option>`).join('');

    if (selectedId) {
        el.value = selectedId;
    }
}

async function loadDoctorsDropdown(selectId, selectedId = null) {
    const rows = await http('doctors.php');
    const el = document.getElementById(selectId);

    el.innerHTML = rows.map(d => `<option value="${d.doctor_id}">${d.full_name}</option>`).join('');

    if (selectedId) {
        el.value = selectedId;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const oldImgField = document.getElementById('docImg')?.closest('.field');

    if (oldImgField) {
        oldImgField.innerHTML = `
            <label>صورة الطبيب</label>
            <input type="file" id="docImgFile" accept="image/*" onchange="previewDocImg(this)">
            <div id="docImgPreview" style="margin-top:8px;display:none">
                <img id="docImgPreviewImg"
                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--primary)"
                     src="" alt="معاينة">
            </div>
        `;
    }
});

function previewDocImg(input) {
    const preview = document.getElementById('docImgPreview');
    const previewImg = document.getElementById('docImgPreviewImg');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = e => {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);

    } else {
        preview.style.display = 'none';
    }
}


async function fillScheduleDoctors() {
    try {
        const doctors = await http('Doctors.php', 'GET');
        const select = document.getElementById('schDoctor');
        if (!select) return;

        select.innerHTML = '<option value="">اختر الطبيب</option>';

        (doctors || []).forEach(d => {
            select.innerHTML += `<option value="${d.doctor_id}">${d.full_name}</option>`;
        });

    } catch (e) {
        console.error(e);
        showToast('❌ حدث خطأ أثناء تحميل الأطباء');
    }
}

async function renderSchedules() {
    try {
        const rows = await http('ClinicSchedules.php', 'GET');
        const q = (document.getElementById('schSearch')?.value || '').toLowerCase();
        const tbody = document.getElementById('scheduleTable');

        if (!tbody) return;

        const filtered = (rows || []).filter(s =>
            String(s.doctor_name || '').toLowerCase().includes(q) ||
            String(s.clinic_days || '').toLowerCase().includes(q) ||
            String(s.clinic_hours || '').toLowerCase().includes(q) ||
            String(s.clinic_location || '').toLowerCase().includes(q)
        );

        if (!filtered.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;color:#777;padding:20px">
                        لا يوجد دوام عيادات حالياً
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map((s, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${s.doctor_name || '-'}</td>
                <td>${s.clinic_days || '-'}</td>
                <td>${s.clinic_hours || '-'}</td>
                <td>${s.clinic_location || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editSchedule('${s.schedule_id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteSchedule('${s.schedule_id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (e) {
        console.error(e);
        showToast('❌ خطأ أثناء تحميل دوام العيادات');
    }
}

async function editSchedule(id) {
    try {
        const rows = await http('ClinicSchedules.php', 'GET');
        const s = (rows || []).find(x => String(x.schedule_id) === String(id));

        if (!s) {
            showToast('❌ لم يتم العثور على الدوام');
            return;
        }

        editId = id;

        await fillScheduleDoctors();

        document.getElementById('scheduleMT').textContent = 'تعديل دوام عيادة';
        document.getElementById('schDoctor').value = s.doctor_id;
        document.getElementById('schDays').value = s.clinic_days || '';
        document.getElementById('schHours').value = s.clinic_hours || '';
        document.getElementById('schLocation').value = s.clinic_location || '';

        document.getElementById('scheduleModal').classList.add('open');

    } catch (e) {
        console.error(e);
        showToast('❌ حدث خطأ أثناء فتح التعديل');
    }
}

async function saveSchedule() {
    const body = {
        doctor_id: document.getElementById('schDoctor').value,
        clinic_days: document.getElementById('schDays').value.trim(),
        clinic_hours: document.getElementById('schHours').value.trim(),
        clinic_location: document.getElementById('schLocation').value.trim()
    };

    if (!body.doctor_id || !body.clinic_days || !body.clinic_hours || !body.clinic_location) {
        showToast('❌ جميع بيانات الدوام مطلوبة');
        return;
    }

    try {
        if (editId) {
            body.schedule_id = editId;
            await http('ClinicSchedules.php', 'PUT', body);
        } else {
            await http('ClinicSchedules.php', 'POST', body);
        }

        closeModal('scheduleModal');
        editId = null;
        showToast('✅ تم حفظ دوام العيادة');
        renderSchedules();
        renderDashboard();

    } catch (e) {
        console.error(e);
        showToast('❌ ' + e.message);
    }
}

function deleteSchedule(id) {
    confirmDel(async () => {
        try {
            await http('ClinicSchedules.php', 'DELETE', { schedule_id: id });
            showToast('✅ تم حذف دوام العيادة');
            renderSchedules();
            renderDashboard();

        } catch (e) {
            console.error(e);
            showToast('❌ ' + e.message);
        }
    });
}

// ════════════════════════════════════════════════
//  PROFILE — الصفحة الشخصية
// ════════════════════════════════════════════════

async function renderProfile() {
    await loadProfile();
}

async function loadProfile() {
    try {
        const data = await http('Profile.php', 'GET');

        // ملء الحقول
        document.getElementById('profilePhone').value   = data.phone   || '';
        document.getElementById('profileEmail').value   = data.email   || '';
        document.getElementById('profileAddress').value = data.address || '';
        document.getElementById('profileBio').value     = data.bio     || '';

        // تفريغ حقول كلمة المرور
        document.getElementById('profileOldPass').value     = '';
        document.getElementById('profileNewPass').value     = '';
        document.getElementById('profileConfirmPass').value = '';

        // اسم المستخدم والدور
        document.getElementById('profileNameDisplay').textContent     = data.full_name || data.username || '-';
        document.getElementById('profileRoleDisplay').textContent     = ROLE_LBL[data.role] || data.role || '-';
        document.getElementById('profileUsernameDisplay').textContent = '@' + (data.username || '');
// بعد سطر profileUsernameDisplay
        document.getElementById('sbName').textContent = data.full_name || data.username || '-';
        // الصورة
        const av = document.getElementById('profileAvatarPreview');
        if (data.image_url) {
            // أضف المسار الكامل إذا كان اسم ملف فقط
            const imgSrc = data.image_url.startsWith('uploads/')
                ? data.image_url
                : 'uploads/doctors/' + data.image_url;
            av.innerHTML = `<img src="${imgSrc}" alt="صورة الملف الشخصي" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;

            // تحديث الـ sidebar أيضاً
            const sbAv = document.getElementById('sidebarAvatar');
            if (sbAv) {
                sbAv.innerHTML = `<img src="${imgSrc}" style="width:100%;height:100%;object-fit:cover;border-radius:10px">`;
            }
        } else {
            av.innerHTML = `<i class="fas fa-user-tie"></i>`;
        }

        // تحديث صورة الـ sidebar أيضاً
        const sbAv = document.getElementById('sidebarAvatar');
        if (sbAv) {
            if (data.image_url) {
                const imgSrc = data.image_url.startsWith('uploads/')
                    ? data.image_url
                    : 'uploads/doctors/' + data.image_url;
                sbAv.innerHTML = `<img src="${imgSrc}" style="width:36px;height:36px;object-fit:cover;border-radius:10px;display:block;">`;
            } else {
                sbAv.innerHTML = `<i class="fas fa-user-tie"></i>`;
            }
        }

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}

function previewProfileImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('profileAvatarPreview').innerHTML =
                `<img src="${e.target.result}" alt="معاينة" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveProfile() {
    const newPass     = document.getElementById('profileNewPass').value;
    const confirmPass = document.getElementById('profileConfirmPass').value;
    const oldPass     = document.getElementById('profileOldPass').value;

    // التحقق من كلمة المرور
    if (newPass && newPass !== confirmPass) {
        showToast('❌ كلمة المرور الجديدة غير متطابقة');
        return;
    }
    if (newPass && !oldPass) {
        showToast('❌ أدخل كلمة المرور الحالية أولاً');
        return;
    }
    if (newPass && newPass.length < 6) {
        showToast('❌ كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        return;
    }

    try {
        // رفع الصورة أولاً إن وجدت
        const imgFile = document.getElementById('profileImgFile')?.files?.[0];
        if (imgFile) {
            const fd = new FormData();
            fd.append('image', imgFile);
            await httpForm('Profile.php', 'POST', fd);
        }

        // حفظ البيانات الأساسية
        const body = {
            phone:   document.getElementById('profilePhone').value.trim(),
            email:   document.getElementById('profileEmail').value.trim(),
            address: document.getElementById('profileAddress').value.trim(),
            bio:     document.getElementById('profileBio').value.trim(),
        };

        if (newPass) {
            body.old_password = oldPass;
            body.new_password = newPass;
        }

        await http('Profile.php', 'PUT', body);

        showToast('✅ تم حفظ التغييرات بنجاح');

        // تفريغ حقول كلمة المرور والصورة
        document.getElementById('profileOldPass').value     = '';
        document.getElementById('profileNewPass').value     = '';
        document.getElementById('profileConfirmPass').value = '';
        if (document.getElementById('profileImgFile')) {
            document.getElementById('profileImgFile').value = '';
        }

        // إعادة تحميل البيانات لتحديث الصورة في الـ sidebar
        await loadProfile();

    } catch (e) {
        showToast('❌ ' + e.message);
    }
}