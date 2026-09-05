# B4: componente de demostración de Merge Sort

El componente anónimo `resources/views/components/algorithm/demo.blade.php` recibe
la propiedad `demonstration` de B3, sin cambiar el contrato. B4 no agrega rutas ni
conecta el buscador; la conexión queda para A5.

## Invocación en A5

Al integrar el ranking, solicitar la demostración con la firma existente:

```php
$result = $rankingService->rank($flights, $criterion, $priceWeight, true);
```

Pasar a Blade el resultado o su campo `demonstration`. Si la vista recibe `$result`:

```blade
<x-algorithm.demo :demonstration="$result['demonstration']" />
```

Si recibe directamente `$demonstration`:

```blade
<x-algorithm.demo :demonstration="$demonstration" />
```

La propiedad puede ser `null` u omitirse; se muestra un estado sin demostración.
Una demostración con entrada vacía conserva los eventos `input` y `result` de B3,
cero comparaciones y navegación entre esos dos eventos. No se crean pasos ficticios.

## Recursos y aislamiento

- `public/algorithm/demo.css`: estilos limitados a `.algorithm-demo`, con disposición
  adaptable para pantallas pequeñas.
- `public/algorithm/demo.js`: navegación con JavaScript estándar, sin dependencias.
- `resources/views/algorithm/partials/event.blade.php`: presentación de cada tipo de evento.
- `resources/views/algorithm/partials/sequence.blade.php`: posiciones, códigos y claves de los vuelos.

El componente carga CSS y JavaScript mediante `asset()` y `@once`. No requiere
agregar importaciones a `app.js`, `app.css`, Vite ni al layout, ni ejecutar una
compilación de recursos. Se pueden incluir varias instancias en una página;
cada una mantiene su navegación independiente y los recursos se incluyen una vez.
Se inicializan los componentes presentes al cargar la página; la inserción dinámica
por AJAX no forma parte de esta integración.

## Uso de los datos reales

Blade recorre exclusivamente `demonstration.trace`, en el orden recibido, y resuelve
cada posición contra `demonstration.input[position]`. No usa IDs como posiciones.
La lista de entrada identifica los primeros X vuelos de N resultados; B3 aplica el
límite de ocho. El componente no recorta de nuevo ni modifica el ranking completo.

| Evento | Presentación |
| --- | --- |
| `input` | Lista original de la demostración. |
| `split` | Lista dividida y sublistas izquierda y derecha. |
| `compare` | Cabeceras, decisión recibida, vuelo elegido y prefijo combinado. |
| `merge` | Sublista combinada, con sobrantes. |
| `result` | Orden final del subconjunto indicado por las posiciones del evento. |

Cada paso muestra rango original `[inicio, fin)`, profundidad y comparaciones
acumuladas del evento. El resumen muestra `demonstration.comparisons`, que es
exclusivamente el total de la demostración. No se suma al contador del ranking.

El precio se presenta en COP, la duración en minutos y la puntuación con seis
decimales; el atributo `title` conserva el valor recibido para consultar más detalle.
La decisión de cada comparación viene de `outcome` y `chosen`, nunca de los valores
formateados. No se recalculan puntuaciones ni normalizaciones. En equilibrio se
explica que los extremos corresponden a todos los resultados de la búsqueda.

JavaScript solo selecciona un panel ya renderizado, actualiza el estado de los
botones y anuncia el paso y el contador mediante una región `aria-live`. Anterior
y Reiniciar se desactivan al inicio; Siguiente se desactiva al final. Los botones
son `type="button"`, admiten teclado y no envían formularios de la vista anfitriona.
Sin JavaScript, todos los eventos pueden leerse en orden y los controles se ocultan.

## Comprobación reproducible

Desde la raíz del repositorio, con las dependencias existentes instaladas:

```powershell
php vendor/bin/phpunit tests/Feature/AlgorithmDemoTest.php
node --test tests/JavaScript/algorithm-demo.test.js
```

Las pruebas de Blade usan datos de B3 sin base de datos: nulo, vacío, un vuelo,
A/B/C, ocho y diez vuelos, los tres criterios, posiciones de todos los eventos,
empates, elección, escape de texto y varias instancias.

Las pruebas de JavaScript generan primero HTML real de Blade con
`tests/Fixtures/render-algorithm-demo.php`. Un adaptador mínimo del DOM, ejecutado
con Node, prueba avance y retroceso completos, límites, reinicio y aislamiento.
No es un navegador y no verifica el aspecto visual ni la distribución de píxeles.

Para una revisión visual al conectar A5: comprobar a 375 px y en escritorio,
recorrer los cinco tipos de evento con Siguiente, volver con Anterior, reiniciar,
usar Tab/Enter, y revisar los estados nulo y vacío. Confirmar que al mostrar más
de ocho resultados el subconjunto conserve sus códigos y posiciones originales.
