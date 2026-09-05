import {connectBrowser} from './chrome.mjs';
const base = process.argv[2] ?? 'http://127.0.0.1:8000';
const {call, evaluate, close} = await connectBrowser();
async function waitFor(expression) {
    for (let i = 0; i < 100; i++) {
        if (await evaluate(expression)) return;
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    throw new Error('No se completó: ' + expression);
}
const flow = () => {
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const origin = document.querySelector('#origin');
    const destination = document.querySelector('#destination');
    const date = document.querySelector('#departure_date');
    const change = (control, value) => { control.value = value; control.dispatchEvent(new Event('change')); };
    check(origin.value === 'BOG' && destination.value === 'MDE' && date.value === '2026-10-15', 'URL reflejada en selectores');
    document.querySelector('#date-picker').open = true;
    const selected = document.querySelector('[data-date="2026-10-15"]');
    check(selected.getAttribute('aria-pressed') === 'true', 'Fecha seleccionada en calendario');
    check(document.querySelector('[data-date="2026-10-14"]').disabled, 'Día sin vuelos deshabilitado');
    change(date, '2026-10-16');
    check(document.querySelector('[data-date="2026-10-16"]').getAttribute('aria-pressed') === 'true', 'Selector actualiza calendario');
    document.querySelector('[data-date="2026-10-15"]').click();
    check(date.value === '2026-10-15' && document.activeElement.dataset.date === date.value, 'Calendario actualiza selector y conserva foco');
    check(document.querySelector('#date-summary').textContent.includes('8 vuelos'), 'Resumen de cantidad');
    let months = 0;
    while (!document.querySelector('#next-month').disabled && months < 24) {
        document.querySelector('#next-month').click(); months++;
        check(document.documentElement.scrollWidth <= innerWidth, 'Mes sin desbordamiento');
    }
    if (months) {
        check(document.querySelector('#calendar-month').textContent !== 'octubre de 2026', 'Navegación de meses');
        document.querySelector('#previous-month').click();
    }
    const reverse = !document.querySelector('#swap-route').hidden;
    if (reverse) {
        document.querySelector('#swap-route').click();
        check(origin.value === 'MDE' && destination.value === 'BOG', 'Intercambiar ruta disponible');
    }
    change(origin, 'CLO');
    check([...destination.options].filter(o=>o.value).every(o=>o.value==='BOG'), 'Destino depende del origen');
    change(destination, 'BOG');
    check(date.value === '', 'Fecha incompatible limpiada');
    change(origin, '');
    check(destination.disabled && date.disabled && date.value === '' && destination.value === '', 'Limpiar dependencias');
    check(document.querySelector('#search-submit').disabled, 'Impedir búsqueda incompleta');
    check(document.documentElement.scrollWidth <= innerWidth, 'Ancho de página');
    return {viewport: innerWidth, months, reverse};
};
try {
    await call('Page.enable');
    await call('Emulation.setTimezoneOverride', {timezoneId: 'Pacific/Honolulu'});
    for (const width of [1440, 768, 375]) {
        await call('Emulation.setDeviceMetricsOverride', {width, height: 950, deviceScaleFactor: 1, mobile: width === 375});
        await call('Page.navigate', {url: base + '/?origin=BOG&destination=MDE&departure_date=2026-10-15'});
        await waitFor(`document.readyState === 'complete' && document.querySelector('[data-date="2026-10-15"]') !== null`);
        await call('Page.bringToFront');
        // Enter sobre un botón de día nativo debe seleccionar sin mover la fecha por zona horaria.
        await evaluate(`document.querySelector('#date-picker').open=true; document.querySelector('[data-date="2026-10-16"]').focus()`);
        await call('Input.dispatchKeyEvent', {type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r'});
        await call('Input.dispatchKeyEvent', {type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13});
        if (!await evaluate(`document.querySelector('#departure_date').value === '2026-10-16'`)) throw new Error('Selección por Enter');
        await evaluate(`document.querySelector('[data-date="2026-10-15"]').click()`);
        console.log('PASS disponibilidad:', await evaluate('(' + flow.toString() + ')()'));
    }
    // Alternativa real por servidor: desactivar JavaScript antes de cargar el formulario.
    await call('Emulation.setScriptExecutionDisabled', {value: true});
    await call('Page.navigate', {url: base + '/'});
    await waitFor('document.readyState === "complete" && location.search === ""');
    await evaluate(`document.querySelector('#origin').value='BOG'; document.querySelector('button[name=stage]').click()`);
    await waitFor(`location.search.includes('stage=availability') && document.querySelector('#destination option[value=MDE]') !== null`);
    await evaluate(`document.querySelector('#destination').value='MDE'; document.querySelector('button[name=stage]').click()`);
    await waitFor(`document.querySelector('#departure_date option[value="2026-10-15"]') !== null`);
    await evaluate(`document.querySelector('#departure_date').value='2026-10-15'; document.querySelector('#search-submit').click()`);
    await waitFor('document.querySelectorAll("[data-flight-choice]").length === 8');
    console.log('PASS búsqueda sin JavaScript: origen → destino → fecha → ocho resultados');
} finally {
    await close();
}
