(() => {
    const samples = {
        oil: {title:'تغيير زيت وفلتر', description:'تغيير زيت المحرك وفلتر الزيت، ومراجعة مستوى السوائل.', items:[['المصنعية',1,200],['زيت محرك — عبوة',1,1200],['فلتر زيت',1,250]], next:'موعد الصيانة القادمة في هذا المثال: بعد 6 أشهر أو 10,000 كم حسب تعليمات الصيانة.'},
        brakes: {title:'صيانة فرامل', description:'استبدال تيل الفرامل الأمامي ومراجعة منظومة الفرامل.', items:[['المصنعية',1,350],['طقم تيل أمامي',1,1400]], next:'التكلفة والخدمة في المثال للتوضيح، وليستا عرض سعر أو توصية صيانة.'}
    };
    const format = n => new Intl.NumberFormat('ar-EG', {style:'currency', currency:'EGP'}).format(n);
    function show(key) {
        const service = samples[key];
        document.getElementById('demo-title').textContent = service.title;
        document.getElementById('demo-description').textContent = service.description;
        document.getElementById('demo-next').textContent = service.next;
        const body = document.getElementById('demo-items'); body.replaceChildren();
        let total = 0;
        service.items.forEach(([name, quantity, price]) => {
            const row = document.createElement('tr');
            [name,quantity,format(price),format(quantity*price)].forEach(value => {
                const cell = document.createElement('td'); cell.textContent = value; row.append(cell);
            }); body.append(row); total += quantity*price;
        });
        document.getElementById('demo-total').textContent = format(total);
        document.querySelectorAll('[data-service]').forEach(button => {
            button.classList.toggle('active',button.dataset.service === key); button.setAttribute('aria-pressed',String(button.dataset.service === key));
        });
    }
    document.querySelectorAll('[data-service]').forEach(button => button.addEventListener('click',() => show(button.dataset.service)));
    show('oil');
})();
