# Disponibilidad y mejora visual del buscador

La mejora de José conserva los servicios de ranking, Merge Sort, sus contratos y datos. La página usa Blade, selectores nativos y JavaScript sin bibliotecas adicionales. La cabecera, buscador y tarjetas mantienen una identidad propia de AeroCompare, con estilos locales en `public/flights/search.css`. El componente del algoritmo permanece intacto dentro de una sección desplegable.

## Datos y validación

`FlightAvailability` consulta `Flight` y agrupa en SQLite por origen, destino y `DATE(departure_at)`, devolviendo únicamente cantidad y precio mínimo por día. A2 almacena estas fechas en hora local de America/Bogota; no se convierten a UTC al agrupar. No se envían registros completos para construir el calendario.

`config/airports.php` solo relaciona códigos con nombres. Si falta un nombre se muestra el código; el catálogo nunca crea disponibilidad. Los orígenes proceden de las salidas almacenadas y los destinos dependen del origen. El calendario habilita únicamente fechas con vuelos, incluidas las fechas pasadas de demostración. El precio «Desde» se oculta en las celdas pequeñas, pero permanece en la lista de fechas y el resumen.

Las fechas del cliente son cadenas `YYYY-MM-DD`. La aritmética de meses usa UTC explícito sin convertir el día civil al huso del navegador. La ordenación de fechas es auxiliar; el ranking completo sigue pasando únicamente por `FlightRankingService`.

El servidor valida origen, ruta y día contra la disponibilidad actual. Una URL con fecha no disponible recibe 422 y la fecha deja de estar seleccionada. Este comportamiento reemplaza el anterior resultado vacío con estado 200 para búsquedas inválidas. Cuando no hay vuelos almacenados se muestra un estado vacío y se deshabilitan los controles. Las selecciones válidas, criterio y peso se conservan ante errores.

«Cargar ejemplo» prefiere BOG → MDE, 2026-10-15; si deja de existir, toma una búsqueda del resumen real. El intercambio de ruta se muestra solo si la ruta inversa existe y elimina la fecha si no está disponible en esa ruta.

## Uso sin JavaScript y teclado

El botón **Actualizar destinos y fechas** envía `stage=availability`. Permite cargar destinos después de elegir origen y cargar fechas después de elegir destino. No ejecuta el ranking. Al elegir una fecha se puede enviar **Buscar vuelos**; los criterios y pesos están asociados al mismo formulario. Se comprobó este recorrido con JavaScript desactivado en Chrome.

El calendario utiliza botones nativos dentro de un grupo, sin simular una cuadrícula ARIA. Tab recorre los días disponibles; Enter o Espacio seleccionan un día. Los días sin vuelos son botones deshabilitados. La selección se indica con una marca visible y `aria-pressed`; cada día tiene una etiqueta con fecha, cantidad y precio. La lista de fechas ofrece una alternativa nativa y sincronizada. El foco regresa al día elegido tras actualizar el calendario.

## Comprobaciones reproducibles

```sh
php artisan test
php artisan test --filter=FlightAvailabilityTest
node tests/Browser/availability-flow.mjs http://127.0.0.1:8000
node tests/Browser/flight-flow.mjs
```

Para las pruebas de navegador se necesita Chrome con depuración en el puerto 9227, según el README, y la aplicación con los datos del seeder. El adaptador común está en `tests/Browser/chrome.mjs`. La prueba de disponibilidad revisa 1440, 768 y 375 px y usa un huso horario distinto de Bogotá para detectar desplazamientos de día. El cambio de meses y la ruta inversa se prueban cuando el conjunto los contiene; el resultado indica cuántos meses se recorrieron y si se probó intercambio.

En esta revisión se usó una base temporal con los 32 vuelos originales, una salida adicional en noviembre y una ruta inversa. Esos dos registros se crearon solo para las pruebas, sin modificar los seeders ni la base del proyecto. Se verificaron cambios de origen/destino/mes/fecha, selección mediante Enter, fechas inválidas, limpieza de dependencias, comparación y navegación del algoritmo. Las pruebas PHP también verifican base vacía, catálogo incompleto y alternativa de ejemplo.

La suite PHP final pasó con 156 pruebas y 2741 aserciones. Se inspeccionaron capturas en los tres anchos, incluido el calendario móvil. No se observó desbordamiento horizontal de página; la tabla de comparación conserva su desplazamiento interno. Vite compiló correctamente con las dependencias existentes en una copia temporal. El aviso de `fontaine` es opcional y no se agregó ese paquete.
