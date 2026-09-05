<script>
(() => {
    const choices = Array.from(document.querySelectorAll('[data-flight-choice]'));
    const panel = document.getElementById('comparison-panel');
    const content = document.getElementById('comparison-content');
    const status = document.getElementById('comparison-status');
    const references = document.getElementById('comparison-references');
    const rows = document.getElementById('comparison-rows');
    const clear = document.getElementById('comparison-clear');
    const money = value => '$ ' + new Intl.NumberFormat('es-CO').format(value) + ' COP';

    function render() {
        const selected = choices.filter(choice => choice.checked);
        choices.forEach(choice => { choice.disabled = selected.length === 3 && !choice.checked; });
        clear.disabled = selected.length === 0;
        status.textContent = `${selected.length} de 3 vuelos seleccionados. ` +
            (selected.length < 2 ? 'Selecciona al menos dos para ver la tabla.' :
                selected.length === 3 ? 'Límite alcanzado. Quita uno para seleccionar otro.' : 'Puedes añadir un vuelo más.');
        content.hidden = selected.length < 2;
        rows.replaceChildren();
        references.textContent = '';
        if (selected.length < 2) return;

        // Solo se calculan referencias y diferencias; se conserva el orden de los resultados.
        const minPrice = Math.min(...selected.map(choice => Number(choice.dataset.price)));
        const minDuration = Math.min(...selected.map(choice => Number(choice.dataset.duration)));
        const cheapest = selected.filter(choice => Number(choice.dataset.price) === minPrice).map(choice => choice.dataset.code);
        const fastest = selected.filter(choice => Number(choice.dataset.duration) === minDuration).map(choice => choice.dataset.code);
        references.textContent = `Referencia de precio: ${cheapest.join(', ')} (${money(minPrice)}). ` +
            `Referencia de duración: ${fastest.join(', ')} (${minDuration} min). Se incluyen todos los empates.`;

        selected.forEach(choice => {
            const flight = choice.dataset;
            const row = document.createElement('tr');
            const values = [
                `${flight.airline} · ${flight.code}`, money(Number(flight.price)),
                `+ ${money(Number(flight.price) - minPrice)}`, `${flight.duration} min`,
                `+ ${Number(flight.duration) - minDuration} min`, flight.stops, flight.baggage,
            ];
            values.forEach((value, index) => {
                const cell = document.createElement(index === 0 ? 'th' : 'td');
                if (index === 0) cell.scope = 'row';
                cell.textContent = value;
                row.append(cell);
            });
            const action = document.createElement('td');
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'Quitar';
            remove.className = 'rounded bg-blue-700 px-3 py-2 text-white';
            remove.setAttribute('aria-label', `Quitar vuelo ${flight.code} de la comparación`);
            remove.addEventListener('click', () => {
                choice.checked = false;
                render();
                choice.focus();
            });
            action.append(remove);
            row.append(action);
            rows.append(row);
        });
    }

    function reset() {
        choices.forEach(choice => { choice.checked = false; });
        render();
    }

    choices.forEach(choice => {
        choice.closest('label').hidden = false;
        choice.addEventListener('change', () => {
            if (choices.filter(item => item.checked).length > 3) choice.checked = false;
            render();
        });
    });
    clear.addEventListener('click', () => { reset(); choices[0]?.focus(); });
    document.querySelector('form').addEventListener('submit', reset);
    // Evita selecciones restauradas por el navegador al regresar a una búsqueda anterior.
    window.addEventListener('pageshow', reset);
    panel.hidden = false;
    reset();
})();
</script>
