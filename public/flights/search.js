// Disponibilidad precalculada en PHP. No ordena resultados ni calcula puntuaciones.
(() => {
    const payload = document.getElementById('flight-availability');
    if (!payload) return;
    const {routes, labels} = JSON.parse(payload.textContent);
    const origin = document.getElementById('origin');
    const destination = document.getElementById('destination');
    const date = document.getElementById('departure_date');
    const calendar = document.getElementById('availability-calendar');
    const daysElement = document.getElementById('calendar-days');
    const monthTitle = document.getElementById('calendar-month');
    const previous = document.getElementById('previous-month');
    const next = document.getElementById('next-month');
    const picker = document.getElementById('date-picker');
    const swap = document.getElementById('swap-route');
    const shortcut = document.getElementById('open-calendar');
    const summary = document.getElementById('date-summary');
    let month = '';
    const money = value => '$ ' + new Intl.NumberFormat('es-CO').format(value) + ' COP';
    const availableDays = () => routes[origin.value]?.[destination.value] ?? {};
    // Fechas civiles YYYY-MM-DD: UTC solo para aritmética de calendario, nunca hora local del navegador.
    const utcDate = value => new Date(value + 'T12:00:00Z');
    const fullDate = value => new Intl.DateTimeFormat('es-CO', {dateStyle: 'full', timeZone: 'UTC'}).format(utcDate(value));
    const moveMonth = (value, offset) => {
        const date = utcDate(value + '-01');
        date.setUTCMonth(date.getUTCMonth() + offset);
        return date.toISOString().slice(0, 7);
    };
    function options(select, entries, placeholder, selected) {
        select.replaceChildren(new Option(placeholder, ''));
        entries.forEach(([value, label]) => select.add(new Option(label, value)));
        select.value = entries.some(([value]) => value === selected) ? selected : '';
        select.disabled = entries.length === 0;
    }
    function updateSelection() {
        const info = availableDays()[date.value];
        shortcut.firstChild.textContent = info ? fullDate(date.value) + ' ' : 'Elige un día disponible ';
        summary.textContent = info ? `${fullDate(date.value)} · ${info.count} vuelos · Desde ${money(info.min_price)}` :
            Object.keys(availableDays()).length ? 'Elige un día para consultar sus opciones y precio mínimo.' : 'No hay fechas disponibles. Selecciona una ruta con vuelos.';
        document.getElementById('search-submit').disabled = !info;
        document.getElementById('apply-preferences').disabled = !info;
        renderCalendar();
    }
    function renderCalendar(focusDate = null) {
        const days = availableDays();
        const dates = Object.keys(days).sort(); // Orden auxiliar de fechas ISO.
        calendar.hidden = dates.length === 0;
        daysElement.replaceChildren();
        if (!dates.length) return;
        const firstMonth = dates[0].slice(0, 7);
        const lastMonth = dates[dates.length - 1].slice(0, 7);
        if (!month || month < firstMonth || month > lastMonth) month = date.value.slice(0, 7) || firstMonth;
        monthTitle.textContent = new Intl.DateTimeFormat('es-CO', {month: 'long', year: 'numeric', timeZone: 'UTC'}).format(utcDate(month + '-01'));
        previous.disabled = month === firstMonth;
        next.disabled = month === lastMonth;
        const first = utcDate(month + '-01');
        const padding = (first.getUTCDay() + 6) % 7;
        for (let i = 0; i < padding; i++) {
            const blank = document.createElement('span');
            blank.setAttribute('aria-hidden', 'true');
            daysElement.append(blank);
        }
        const last = utcDate(moveMonth(month, 1) + '-01');
        last.setUTCDate(0);
        for (let number = 1; number <= last.getUTCDate(); number++) {
            const key = `${month}-${String(number).padStart(2, '0')}`;
            const info = days[key];
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.date = key;
            button.disabled = !info;
            button.setAttribute('aria-pressed', String(date.value === key));
            button.setAttribute('aria-label', `${fullDate(key)}. ${info ? `${info.count} vuelos. Desde ${money(info.min_price)}` : 'Sin vuelos'}`);
            const dayNumber = document.createElement('strong');
            dayNumber.textContent = (date.value === key ? '✓ ' : '') + number;
            const price = document.createElement('small');
            price.textContent = info ? 'Desde $ ' + new Intl.NumberFormat('es-CO').format(info.min_price) : '—';
            button.append(dayNumber, price);
            button.addEventListener('click', () => {
                date.value = key;
                updateSelection();
                daysElement.querySelector(`[data-date="${key}"]`).focus();
            });
            daysElement.append(button);
        }
        if (focusDate) daysElement.querySelector(`[data-date="${focusDate}"]`)?.focus();
    }
    function refreshDates() {
        const days = availableDays();
        options(date, Object.entries(days).sort(([a], [b]) => a.localeCompare(b)).map(([key, info]) =>
            [key, `${key} · ${info.count} vuelos · Desde ${money(info.min_price)}`]), 'Selecciona una fecha', date.value);
        month = date.value.slice(0, 7) || Object.keys(days).sort()[0]?.slice(0, 7) || '';
        document.getElementById('date-hint').textContent = Object.keys(days).length ? 'Solo se muestran días con vuelos para esta ruta.' : 'Selecciona origen y destino para ver fechas con vuelos.';
        swap.hidden = !routes[destination.value]?.[origin.value];
        updateSelection();
    }
    function refreshDestinations() {
        const destinations = Object.keys(routes[origin.value] ?? {});
        options(destination, destinations.map(code => [code, labels[code] ?? code]), 'Elige tu destino', destination.value);
        document.getElementById('destination-hint').textContent = destinations.length ? 'Destinos con vuelos desde tu origen.' : 'Primero selecciona un origen.';
        refreshDates();
    }
    origin.addEventListener('change', refreshDestinations);
    destination.addEventListener('change', refreshDates);
    date.addEventListener('change', () => { month = date.value.slice(0, 7); updateSelection(); });
    previous.addEventListener('click', () => { month = moveMonth(month, -1); renderCalendar(); if (previous.disabled) next.focus(); });
    next.addEventListener('click', () => { month = moveMonth(month, 1); renderCalendar(); if (next.disabled) previous.focus(); });
    swap.addEventListener('click', () => {
        if (!routes[destination.value]?.[origin.value]) return;
        const oldOrigin = origin.value;
        origin.value = destination.value;
        options(destination, Object.keys(routes[origin.value]).map(code => [code, labels[code] ?? code]), 'Elige tu destino', oldOrigin);
        refreshDates();
        destination.focus();
    });
    shortcut.addEventListener('click', event => { event.preventDefault(); picker.open = true; picker.scrollIntoView({block: 'nearest'}); if (!date.disabled) date.focus(); });
    const slider = document.getElementById('price-importance');
    slider.addEventListener('input', () => { document.getElementById('weight-display').textContent = `Precio ${slider.value} % · Tiempo ${100 - Number(slider.value)} %`; });
    // Búsquedas cargadas por URL e historial conservan solo selecciones disponibles.
    window.addEventListener('pageshow', refreshDestinations);
    refreshDestinations();
})();
