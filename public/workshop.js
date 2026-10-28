(() => {
    const $ = id => document.getElementById(id);
    const base = document.querySelector('meta[name="api-base"]').content;
    const state = {token: null, user: null, mode: 'login', page: 1, lastPage: 1, search: '', cars: []};
    const roles = {owner: 'حساب صاحب عربية', mechanic: 'مساحة الورشة', admin: 'إدارة النظام'};
    const number = value => new Intl.NumberFormat('ar-EG').format(Number(value));
    function notice(message, error = false) {
        $('notice').textContent = message;
        $('notice').classList.toggle('error', error);
        $('notice').hidden = false;
    }
    async function api(path, method = 'GET', data) {
        const response = await fetch(base + path, {
            method, headers: {Accept: 'application/json', 'Content-Type': 'application/json', ...(state.token ? {Authorization: 'Bearer ' + state.token} : {})},
            ...(data ? {body: JSON.stringify(data)} : {})
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 401 && state.token) reset();
            const message = body.errors ? Object.values(body.errors).flat().join('\n') : ({403: 'حسابك لا يملك صلاحية العملية دي. راجع عضوية الورشة وموافقة صاحب العربية.', 404: 'البيانات المطلوبة غير موجودة أو غير متاحة لحسابك.', 429: 'محاولات كتير في وقت قصير. استنى دقيقة وجرب تاني.'}[response.status] || body.message || 'تعذر إتمام العملية.');
            throw new Error(message);
        }
        return body;
    }
    function bindForm(id, handler) {
        $(id).addEventListener('submit', async event => {
            event.preventDefault();
            const button = event.submitter;
            if (button) button.disabled = true;
            try { await handler(Object.fromEntries(new FormData(event.target)), event); }
            catch (error) { notice(error.message, true); }
            finally { if (button) button.disabled = false; }
        });
    }
    function node(tag, text, className) {
        const element = document.createElement(tag);
        if (text !== undefined) element.textContent = text;
        if (className) element.className = className;
        return element;
    }
    function reset() {
        state.token = null; state.user = null; state.cars = [];
        $('auth').hidden = false; $('workspace').hidden = true; $('logout').hidden = true;
        $('history-panel').hidden = true; $('auth-form').reset();
        $('invitation-result').textContent = ''; $('invitation-result').hidden = true;
    }
    function mode(value) {
        state.mode = value;
        const register = value === 'register';
        $('name-label').hidden = !register; $('role-label').hidden = !register;
        $('auth-form').elements.name.required = register;
        $('auth-title').textContent = register ? 'ابدأ سجل جديد' : 'أهلًا برجوعك';
        $('auth-submit').textContent = register ? 'إنشاء حساب' : 'دخول لحسابك ←';
        $('login-tab').classList.toggle('selected', !register); $('register-tab').classList.toggle('selected', register);
        $('auth-form').elements.password.autocomplete = register ? 'new-password' : 'current-password';
    }
    $('login-tab').onclick = () => mode('login'); $('register-tab').onclick = () => mode('register');
    bindForm('auth-form', async data => {
        if (state.mode === 'register') data.password_confirmation = data.password;
        const result = await api('/' + state.mode, 'POST', data);
        state.token = result.token; state.page = 1; state.search = '';
        $('auth-form').reset();
        await refresh(); notice('أهلًا بيك. حسابك جاهز.');
    });
    async function refresh() {
        state.user = (await api('/me')).data;
        const user = state.user;
        $('auth').hidden = true; $('workspace').hidden = false; $('logout').hidden = false;
        $('greeting').textContent = 'أهلًا، ' + user.name;
        $('role-name').textContent = roles[user.role];
        $('workspace-description').textContent = user.role === 'owner' ? 'عربياتك، تاريخ صيانتها، وصلاحيات الورش في مكان واحد.' : 'سجل الخدمة وتابع العربيات المصرّح لورشتك بالشغل عليها.';
        document.querySelectorAll('[data-role]').forEach(element => { element.hidden = element.dataset.role !== user.role; });
        $('manager-panel').hidden = !user.is_garage_manager;
        $('service-panel').hidden = user.role !== 'mechanic' || !user.garage_id;
        $('vehicles-panel').hidden = user.role === 'admin';
        $('metrics').hidden = !user.is_garage_manager;
        $('cars-hint').textContent = user.role === 'owner' ? 'اختار عربية لعرض سجلها أو اسمح لورشة بتسجيل صيانة.' : 'العربيات بتظهر بعد موافقة أصحابها. مش بنعرض تاريخها عند ورش تانية.';
        $('membership').textContent = user.garage_id ? 'أنت عضو في الورشة رقم ' + user.garage_id : 'اطلب دعوة من مدير الورشة بنفس بريد حسابك.';
        $('accept-form').hidden = !!user.garage_id;
        if (user.role !== 'admin') await loadCars();
        if (user.is_garage_manager) {
            const [metrics, staff] = await Promise.all([api('/workshop/dashboard'), api('/workshop/staff')]);
            $('metrics').replaceChildren();
            [['صيانة النهارده', metrics.services_today], ['مصنعية الشهر (جنيه)', metrics.labor_total_this_month], ['أعضاء الفريق', metrics.staff_count]].forEach(([label, value]) => {
                const element = node('div', label, 'metric'); element.append(node('b', number(value))); $('metrics').append(element);
            });
            $('staff-list').replaceChildren();
            staff.data.forEach(member => {
                const row = node('div', member.name + (member.is_garage_manager ? ' · مدير' : ''), 'car');
                if (!member.is_garage_manager) {
                    const remove = node('button', 'إزالة من الورشة', 'quiet');
                    remove.onclick = async () => {
                        if (!confirm('إلغاء عضوية ' + member.name + ' في الورشة؟')) return;
                        try { await api('/workshop/staff/' + member.id, 'DELETE'); await refresh(); notice('تم إلغاء عضوية الموظف.'); }
                        catch (error) { notice(error.message, true); }
                    }; row.append(remove);
                }
                $('staff-list').append(row);
            });
        }
    }
    async function loadCars() {
        const result = await api((state.user.role === 'owner' ? '/cars' : '/workshop/cars') + '?page=' + state.page + '&search=' + encodeURIComponent(state.search));
        state.cars = result.data; state.lastPage = result.meta.last_page;
        $('car-count').textContent = number(result.meta.total);
        $('page-label').textContent = state.page + ' / ' + state.lastPage;
        $('previous').disabled = state.page <= 1; $('next').disabled = state.page >= state.lastPage;
        $('cars-list').replaceChildren();
        document.querySelectorAll('.car-select').forEach(select => {
            select.replaceChildren(new Option('اختار العربية', ''));
            state.cars.forEach(car => select.add(new Option(`${car.make} ${car.model} · ${car.plate_number}`, car.id)));
        });
        if (!state.cars.length) $('cars-list').append(node('p', 'مفيش عربيات لعرضها حاليًا. أضف عربية أو راجع إذن صاحبها.', 'empty'));
        state.cars.forEach(car => {
            const row = node('article', undefined, 'car');
            row.append(node('h3', car.make + ' ' + car.model), node('p', car.plate_number + ' · ' + car.year + ' · ' + car.mileage));
            if (state.user.role === 'owner') {
                const button = node('button', 'عرض تاريخ الصيانة ←', 'quiet');
                button.onclick = () => history(car).catch(error => notice(error.message, true)); row.append(button);
            } else row.append(node('p', 'رقم العربية: ' + car.id));
            $('cars-list').append(row);
        });
    }
    async function history(car) {
        $('history-title').textContent = 'تاريخ ' + car.make + ' ' + car.model;
        $('history-list').replaceChildren();
        let page = 1, lastPage = 1;
        do {
            const result = await api(`/cars/${car.id}/service-records?page=${page}`);
            lastPage = result.meta.last_page;
            result.data.forEach(record => {
                const row = node('article', undefined, 'record');
                row.append(node('h3', record.service_type + ' · ' + record.service_date), node('p', record.garage?.name || ''), node('p', record.description || ''), node('p', 'العداد: ' + record.mileage_at_service), node('p', 'الإجمالي: ' + number(record.total_cost) + ' جنيه'));
                (record.parts || []).forEach(part => row.append(node('p', part.part_name + ' × ' + part.quantity + ' — ' + number(part.total_price) + ' جنيه')));
                if (record.next_service_date) row.append(node('p', 'الموعد القادم: ' + record.next_service_date));
                $('history-list').append(row);
            });
            page++;
        } while (page <= lastPage);
        if (!$('history-list').children.length) $('history-list').append(node('p', 'لسه مفيش صيانة مسجلة للعربية دي.', 'empty'));
        $('history-panel').hidden = false; $('history-panel').scrollIntoView({behavior: 'smooth'});
    }
    $('close-history').onclick = () => { $('history-panel').hidden = true; };
    $('refresh').onclick = () => refresh().catch(error => notice(error.message, true));
    $('previous').onclick = () => { state.page--; loadCars().catch(error => notice(error.message, true)); };
    $('next').onclick = () => { state.page++; loadCars().catch(error => notice(error.message, true)); };
    bindForm('search-form', async data => { state.search = data.search; state.page = 1; await loadCars(); });
    bindForm('car-form', async (data, event) => { await api('/cars', 'POST', data); event.target.reset(); await refresh(); notice('العربية اتضافت.'); });
    bindForm('access-form', async (data, event) => {
        await api(`/cars/${data.car_id}/garage-access/${data.garage_id}`, event.submitter.value === 'grant' ? 'POST' : 'DELETE');
        notice(event.submitter.value === 'grant' ? 'تم السماح للورشة بتسجيل الصيانة.' : 'تم سحب إذن تسجيل وتعديل الصيانة.');
    });
    bindForm('invite-form', async data => {
        const result = await api('/workshop/invitations', 'POST', data);
        $('invitation-result').textContent = 'شارك الرمز مع الموظف صاحب البريد ده فقط. صالح لمدة يومين:\n' + result.invitation_token;
        $('invitation-result').hidden = false;
    });
    bindForm('accept-form', async data => { await api('/workshop/invitations/accept', 'POST', data); await refresh(); notice('انضمّيت للورشة بنجاح.'); });
    bindForm('manager-form', async data => { await api('/garages/' + data.garage_id + '/manager', 'PUT', {user_id: data.user_id}); notice('تم تعيين مدير الورشة.'); });
    $('add-part').onclick = () => {
        const row = node('div', undefined, 'part-row');
        [['part_name', 'اسم القطعة', 'text'], ['quantity', 'الكمية', 'number'], ['price', 'سعر الوحدة (جنيه)', 'number']].forEach(([key, title, type]) => {
            const label = node('label', title); const input = document.createElement('input'); input.dataset.key = key; input.type = type; input.required = true;
            if (type === 'number') { input.min = key === 'quantity' ? '1' : '0'; input.step = key === 'quantity' ? '1' : '0.01'; }
            label.append(input); row.append(label);
        });
        const remove = node('button', 'حذف القطعة', 'quiet'); remove.type = 'button'; remove.onclick = () => row.remove(); row.append(remove); $('parts').append(row);
    };
    bindForm('service-form', async (data, event) => {
        const carId = data.car_id; delete data.car_id; data.garage_id = state.user.garage_id;
        if (!data.next_service_date) delete data.next_service_date;
        data.parts = [...$('parts').children].map(row => Object.fromEntries([...row.querySelectorAll('input')].map(input => [input.dataset.key, input.value])));
        await api(`/cars/${carId}/service-records`, 'POST', data);
        event.target.reset(); $('parts').replaceChildren(); await refresh(); notice('الصيانة وقطع الغيار اتحفظوا، وصاحب العربية يقدر يشوفهم في سجله.');
    });
    $('logout').onclick = async () => { try { await api('/logout', 'POST'); } catch (_) {} reset(); notice('تم تسجيل الخروج.'); };
})();
