<noscript><p>Activa JavaScript para seleccionar y comparar dos o tres vuelos. Puedes consultar todos sus datos en las tarjetas.</p></noscript>
<section id="comparison-panel" hidden aria-labelledby="comparison-title" class="mt-6 rounded-xl border border-blue-200 bg-white p-5">
    <h2 id="comparison-title" class="text-xl font-semibold">Comparar vuelos seleccionados</h2>
    <p id="comparison-help" class="my-2">Selecciona dos o tres vuelos de esta búsqueda. Para cambiar uno, desmárcalo o utiliza Quitar. Una nueva búsqueda limpia la selección.</p>
    <p id="comparison-status" role="status" aria-live="polite" aria-atomic="true" class="my-3 font-medium"></p>
    <button id="comparison-clear" type="button" class="my-3 rounded bg-blue-700 px-4 py-2 text-white disabled:opacity-50">Limpiar selección</button>
    <div id="comparison-content" hidden>
        <p id="comparison-references" class="my-3 text-sm text-slate-700"></p>
        <p class="my-2 text-sm">Las diferencias se calculan solo entre los vuelos seleccionados. La elección depende de tus necesidades.</p>
        <div role="region" aria-label="Tabla de comparación de vuelos" tabindex="0" class="overflow-x-auto" style="overflow-x: auto">
            <table id="comparison-table" class="text-sm" aria-describedby="comparison-references">
                <caption class="mb-2 text-left font-semibold">Vuelos y precios de demostración · Comparación en COP y minutos</caption>
                <thead class="bg-blue-50"><tr>
                    <th scope="col">Vuelo</th><th scope="col">Precio total COP</th><th scope="col">Diferencia de precio</th>
                    <th scope="col">Duración total</th><th scope="col">Diferencia de duración</th>
                    <th scope="col">Escalas</th><th scope="col">Equipaje</th><th scope="col">Selección</th>
                </tr></thead>
                <tbody id="comparison-rows"></tbody>
            </table>
        </div>
    </div>
</section>
