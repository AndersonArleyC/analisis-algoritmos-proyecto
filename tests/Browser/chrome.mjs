// Adaptador mínimo de Chrome DevTools; no requiere paquetes npm.
export async function connectBrowser() {
    const endpoint = process.env.CHROME_DEBUG_URL ?? 'http://127.0.0.1:9227';
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

    return {call, evaluate, close: async () => { await call('Page.close'); ws.close(); }};
}
