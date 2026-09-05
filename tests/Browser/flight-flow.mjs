// Chrome debe estar abierto con --remote-debugging-port=9227 (perfil temporal).
const endpoint = process.env.CHROME_DEBUG_URL ?? 'http://127.0.0.1:9227';
const url = process.argv[2] ?? 'http://127.0.0.1:8000/?origin=BOG&destination=MDE&departure_date=2026-10-15&criterion=balanced&price_importance=50';
const page = await (await fetch(endpoint + '/json/new?about:blank', {method: 'PUT'})).json();
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
    ws.addEventListener('open', resolve, {once: true});
    ws.addEventListener('error', reject, {once: true});
});
let id = 0;
const pending = new Map();
ws.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (pending.has(message.id)) {
        pending.get(message.id)(message);
        pending.delete(message.id);
    }
});

async function call(method, params = {}) {
    const requestId = ++id;
    const response = new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Tiempo agotado: ' + method)), 10000);
        pending.set(requestId, message => {
            clearTimeout(timer);
            resolve(message);
        });
    });
    ws.send(JSON.stringify({id: requestId, method, params}));
    const message = await response;
    if (message.error) throw new Error(JSON.stringify(message.error));
    return message.result;
}

async function evaluate(expression) {
    const result = await call('Runtime.evaluate', {expression, returnByValue: true});
    if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
}

const flow = () => {
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    check(innerWidth === 375, 'Viewport exacto de 375 px');
    const choices = [...document.querySelectorAll('[data-flight-choice]')];
    check(choices.length === 8, 'El ejemplo debe tener ocho vuelos');
    const byCode = code => choices.find(c => c.dataset.code === code);
    const select = code => { const c=byCode(code); c.checked=true; c.dispatchEvent(new Event('change')); };
    const rows = () => [...document.querySelectorAll('#comparison-rows tr')];
    select('DEMO-A');
    check(document.querySelector('#comparison-content').hidden, 'No comparar un solo vuelo');
    select('DEMO-B'); select('DEMO-C');
    check(rows().length === 3 && choices.filter(c=>c.disabled).length === 5, 'Máximo tres vuelos');
    const rowB = rows().find(r=>r.cells[0].textContent.includes('DEMO-B'));
    check(rowB.cells[2].textContent === '+ $ 300.000 COP', 'Diferencia de precio A/B');
    const rowA = rows().find(r=>r.cells[0].textContent.includes('DEMO-A'));
    check(rowA.cells[4].textContent === '+ 600 min', 'Diferencia de duración A/B');
    rowA.querySelector('button').click();
    check(rows().length === 2 && !byCode('DEMO-D').disabled, 'Quitar habilita otra selección');
    check(document.activeElement === byCode('DEMO-A'), 'Foco al quitar');
    document.querySelector('#comparison-clear').click();
    select('DEMO-A'); select('DEMO-G');
    check(document.querySelector('#comparison-references').textContent.includes('DEMO-A') && document.querySelector('#comparison-references').textContent.includes('DEMO-G'), 'Referencias empatadas');
    check(document.documentElement.scrollWidth <= innerWidth, 'Comparación sin desbordamiento de página');
    const region=document.querySelector('#comparison-table').parentElement;
    region.scrollLeft=100;
    check(region.scrollLeft>0, 'Tabla desplazable en móvil');
    const cards=document.querySelector('#flight-results').innerHTML;
    const slider=document.querySelector('#price-importance');
    slider.value='75'; slider.dispatchEvent(new Event('input'));
    check(document.querySelector('#weight-display').textContent.includes('Tiempo 25 %'), 'Complemento del tiempo');
    check(cards===document.querySelector('#flight-results').innerHTML, 'No recalcular al mover el control');
    const steps=[...document.querySelectorAll('[data-demo-step]')];
    const next=document.querySelector('[data-demo-next]');
    const types=new Set();
    check(steps.length>2, 'Traza real disponible');
    for(let i=0;i<steps.length;i++) {
        check(!steps[i].hidden, 'Paso '+i); types.add(steps[i].dataset.demoType);
        check(document.documentElement.scrollWidth<=innerWidth, 'Paso sin desbordamiento '+i);
        if(i<steps.length-1) next.click();
    }
    check(types.size===5 && next.disabled, 'Eventos y límite final');
    document.querySelector('[data-demo-previous]').click();
    check(!steps[steps.length-2].hidden, 'Anterior');
    document.querySelector('[data-demo-reset]').click();
    check(!steps[0].hidden && document.querySelector('[data-demo-previous]').disabled, 'Reinicio');
    const form=document.querySelector('form');
    form.addEventListener('submit',e=>e.preventDefault(),{once:true});
    document.querySelector('#criterion').value='duration';
    form.dispatchEvent(new Event('submit',{cancelable:true}));
    check(choices.every(c=>!c.checked), 'Aplicar búsqueda o criterio limpia selección');
    return 'PASS: comparación, límites, diferencias, empates, foco, preferencias, navegación y ancho exacto 375 px';
};
try {
    await call('Page.enable');
    await call('Emulation.setDeviceMetricsOverride', {
        width: 375, height: 844, deviceScaleFactor: 1, mobile: true,
    });
    await call('Page.navigate', {url});
    let ready = false;
    for (let attempt = 0; attempt < 100; attempt++) {
        ready = await evaluate(`document.querySelector('[data-algorithm-demo]')?.dataset.demoReady === 'true'`);
        if (ready) break;
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    if (!ready) throw new Error('La búsqueda de ejemplo no cargó su demostración en 10 segundos.');
    console.log(await evaluate('('+flow.toString()+')()'));
} finally {
    await call('Page.close');
    ws.close();
}
