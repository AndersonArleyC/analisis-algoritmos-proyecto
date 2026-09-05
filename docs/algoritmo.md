# AeroCompare: algoritmo, puntuación y demostración

Documento de B6 contrastado con el código local el 4 de septiembre de 2026.
El [contrato de integración](contrato-integracion.md) define las interfaces;
el [plan](plan-proyecto.md) distribuye responsabilidades y el
[registro de validación](validacion-algoritmos.md) conserva los resultados de B5.
Este documento explica la implementación; no cambia esas interfaces.

## 1. Problema y recorrido de los datos

AeroCompare es un comparador académico de vuelos con datos simulados. Para una
ruta y fecha, el vuelo más barato puede tardar demasiado y el más rápido puede
ser costoso. Se ofrecen tres criterios ascendentes: precio, duración y una
puntuación que combina ambas preferencias. El objetivo es ayudar a comparar,
sin presentar un vuelo como objetivamente mejor para todas las personas.

La búsqueda corresponde a una sola vía y un pasajero. El precio es un entero en
pesos colombianos, incluidos impuestos simulados; la duración es un entero en
minutos, incluidas las escalas. Los horarios se interpretan en `America/Bogota`.

En el código de integración inspeccionado, el controlador valida la búsqueda y
filtra por origen, destino e intervalo del día de salida. Después convierte los
modelos en arrays del contrato y llama al servicio. **Primero se filtra; después
se calculan puntuaciones y se ordena.** El algoritmo genérico no consulta la base
de datos ni recibe modelos Eloquent.

## 2. Puntuar y ordenar son operaciones diferentes

| Responsabilidad | Código | Qué hace |
| --- | --- | --- |
| Preparar claves | [FlightRankingService.php](../app/Services/FlightRankingService.php) | Valida criterio y peso; para equilibrio obtiene extremos globales, normaliza y calcula una puntuación por vuelo. |
| Ordenar | [MergeSort.php](../app/Services/Algorithms/MergeSort.php) | Usa el comparador recibido para dividir y combinar manualmente. No conoce vuelos, precios ni fórmulas. |
| Explicar la ejecución | [Componente Blade](../resources/views/components/algorithm/demo.blade.php) y [JavaScript](../public/algorithm/demo.js) | Presentan los eventos registrados por PHP y permiten cambiar el paso visible. |

La firma del servicio es:

```php
public function rank(
    array $flights,
    string $criterion = 'price',
    float $priceWeight = 0.5,
    bool $includeTrace = false,
): array;
```

| Criterio | Clave que se compara | Campos añadidos al vuelo |
| --- | --- | --- |
| `price` | `total_price_cop` | `normalized_price`, `normalized_duration` y `score` son `null`. |
| `duration` | `duration_minutes` | Los mismos tres campos son `null`. |
| `balanced` | `score` | Los tres campos contienen floats. |

El servicio devuelve `criterion`, `priceWeight`, `timeWeight`, `flights`,
`comparisons`, `normalization` y `demonstration`. Adapta `items` de Merge Sort a
`flights` y conserva su contador. `normalization` es `null` fuera de equilibrio o
si no hay vuelos; `demonstration` es `null` cuando no se solicita la traza.

Calcular una puntuación no cambia el orden de entrada: produce una clave. Solo
Merge Sort establece el orden final, mediante `$left[$key] <=> $right[$key]`.
No se emplean funciones de ordenamiento incorporadas ni `ORDER BY` para resolverlo.
La tabla de dos o tres vuelos compara diferencias; tampoco vuelve a ordenar ni
recalcula las puntuaciones de equilibrio.

## 3. Normalización, pesos y casos especiales

Para cada vuelo, sean `p` su precio y `d` su duración. Los extremos se calculan
sobre **todos los vuelos recibidos para la búsqueda actual**, antes de ordenar:

```text
precio_normalizado = (p - precio_minimo) / (precio_maximo - precio_minimo)
tiempo_normalizado = (d - duracion_minima) / (duracion_maxima - duracion_minima)

peso_precio = priceWeight
peso_tiempo = 1 - priceWeight
score = peso_precio * precio_normalizado + peso_tiempo * tiempo_normalizado
```

Normalizar permite combinar COP y minutos en una escala sin unidades. Dentro del
conjunto usado para los extremos, los componentes van de 0 a 1. Menor `score`
significa mejor equilibrio según los pesos elegidos.

- El peso predeterminado es `0.5`: mitad precio y mitad tiempo. Con `0` solo influye
  la duración; con `1` solo influye el precio. Los pesos suman 1.
- La interfaz usa importancia del precio entre 0 y 100; la integración divide
  ese valor entre 100 antes de llamar al servicio.
- El peso debe ser finito y pertenecer a `[0, 1]`. Criterios desconocidos y pesos
  inválidos producen `InvalidArgumentException`, incluso con lista vacía o con
  criterios que no usan puntuación. La forma válida de cada `FlightData` corresponde
  a la capa de datos e integración, según el contrato.
- Si máximo y mínimo coinciden, `normalize()` devuelve `0.0` para ese componente:
  no hay diferencias que ese atributo permita distinguir y se evita dividir entre cero.
- Con un vuelo, ambos componentes y la puntuación de equilibrio son `0.0`; hay
  cero comparaciones. Con lista vacía se devuelven vuelos vacíos, cero comparaciones
  y `normalization=null`.
- Si todos los precios y duraciones son iguales, todas las puntuaciones son cero
  y se conserva el orden original. Si solo un componente es constante, el otro
  puede distinguir vuelos siempre que su peso sea distinto de cero.
- No se redondea antes de comparar ni se aplica una tolerancia para crear empates.
  La visualización muestra seis decimales de puntuación y conserva el valor recibido
  en el atributo `title`; el formato de presentación no interviene en la decisión.

## 4. Merge Sort manual y estable

Su interfaz pública es:

```php
public function sort(array $items, callable $compare, bool $recordTrace = false): array;
```

Devuelve `items`, `comparisons` y `trace`. El comparador debe ser consistente y
sin efectos secundarios: negativo indica que la izquierda precede, cero indica
empate y positivo indica que precede la derecha.

La implementación ordena una lista de **posiciones originales** obtenida con
`array_keys($items)`. El comparador de posiciones consulta los valores de entrada;
al terminar, se reconstruye la lista de valores siguiendo las posiciones ordenadas.
Esto distingue duplicados sin insertar metadatos en los vuelos ni alterar la entrada.

1. `divide()` devuelve directamente sublistas de cero o un elemento.
2. Para un tamaño `n >= 2`, usa `intdiv(n, 2)` posiciones a la izquierda y el resto
   a la derecha. `array_slice()` crea las sublistas.
3. Procesa primero la izquierda y después la derecha.
4. `merge()` compara sus cabeceras mediante índices. Incorpora una de ellas y avanza
   únicamente el índice correspondiente.
5. Cuando una sublista se agota, copia los sobrantes de la otra sin comparar claves.

La estabilidad está en la condición **`$outcome <= 0`**: ante empate se toma la
izquierda. Dentro de cada mitad se conserva el orden por el mismo procedimiento;
entre mitades, un elemento de la izquierda estaba antes en la entrada. Por tanto,
los elementos con claves iguales mantienen su orden relativo. No se añade un
desempate por ID, precio u otra clave.

Estabilidad no significa que dos consultas SQL independientes tengan necesariamente
el mismo orden de entrada. Se conserva el orden de la lista que recibe cada ejecución.

## 5. Ejemplo A/B/C y recorrido real

Considérese exclusivamente esta entrada, en orden **A, B, C**:

| Vuelo | Precio COP | Duración en minutos | Precio normalizado | Tiempo normalizado | Puntuación 50/50 |
| --- | ---: | ---: | ---: | ---: | ---: |
| A | 200000 | 720 | 0 | 1 | 0.5 |
| B | 500000 | 120 | 1 | 0 | 0.5 |
| C | 280000 | 180 | 4/15 | 1/10 | 11/60 ≈ 0.1833333333 |

Los intervalos son 300000 COP y 600 minutos. Para C:
`(280000 - 200000) / 300000 = 4/15` y `(180 - 120) / 600 = 1/10`.
Su media ponderada 50/50 es `11/60`, menor que `0.5`.

Las fracciones son la explicación matemática. En la comprobación directa con
PHP 8.5.10, el float de C a 50/50 se serializó como `0.18333333333333335`;
la diferencia de representación binaria no cambia el orden.

### Paso a paso con peso 0.5

Las posiciones originales son A = 0, B = 1 y C = 2. Esta tabla corresponde a los
nueve eventos devueltos por la ejecución real comprobada en B6. `step` empieza en
cero; Blade muestra al usuario `step + 1`. El rango excluye su extremo derecho.

| `step` | Evento | Profundidad y rango | Acción y posiciones registradas | Comparaciones acumuladas |
| ---: | --- | --- | --- | ---: |
| 0 | `input` | 0, `[0,3)` | Entrada `[0,1,2]` = A, B, C. | 0 |
| 1 | `split` | 0, `[0,3)` | Izquierda `[0]`; derecha `[1,2]`. | 0 |
| 2 | `split` | 1, `[1,3)` | B y C se separan en `[1]` y `[2]`. A ya era un caso base. | 0 |
| 3 | `compare` | 1, `[1,3)` | B frente a C: gana C. `outcome=1`, `chosen=2`, prefijo `[2]`. | 1 |
| 4 | `merge` | 1, `[1,3)` | Se copia B, que sobra; queda `[2,1]` = C, B. | 1 |
| 5 | `compare` | 0, `[0,3)` | A frente a C: gana C. `outcome=1`, `chosen=2`, prefijo `[2]`. | 2 |
| 6 | `compare` | 0, `[0,3)` | A y B empatan en 0.5. `outcome=0`, se elige A por estar a la izquierda: prefijo `[2,0]`. | 3 |
| 7 | `merge` | 0, `[0,3)` | Se copia B; queda `[2,0,1]` = C, A, B. | 3 |
| 8 | `result` | 0, `[0,3)` | Resultado final `[2,0,1]`. | 3 |

### Comparación entre criterios y pesos

| Criterio | Peso del precio | Orden | Puntuaciones A, B, C | Comparaciones |
| --- | ---: | --- | --- | ---: |
| `price` | 0.5 | A, C, B | No se calculan; campos `null`. | 2 |
| `duration` | 0.5 | B, C, A | No se calculan; campos `null`. | 3 |
| `balanced` | 0 | B, C, A | 1; 0; 0.1 | 3 |
| `balanced` | 0.5 | C, A, B | 0.5; 0.5; 11/60 | 3 |
| `balanced` | 1 | A, C, B | 0; 1; 4/15 | 2 |

Estos órdenes, puntuaciones y contadores se comprobaron mediante una llamada
directa a `FlightRankingService::rank()` con arrays completos y `includeTrace=true`,
sin base de datos. Para este conjunto de tres vuelos, ranking y demostración tienen
la misma entrada y sus contadores coinciden, aunque son ejecuciones independientes.
No se debe atribuir la tabla a una búsqueda con más opciones: al añadir vuelos
pueden cambiar los extremos, las puntuaciones y el número de comparaciones.

## 6. Contadores, traza y límite de ocho

Una comparación de claves es **una llamada al comparador entre las cabeceras de
dos sublistas durante `merge()`**. Comprobar índices, dividir listas, calcular
extremos, puntuar, copiar sobrantes o cambiar de panel no incrementa ese contador.
No es una medida de milisegundos ni de todas las operaciones del programa.

Cada llamada a `sort()` comienza con contador cero y una traza local. Si se activa
`recordTrace`, se registran `input`, `split`, `compare`, `merge` y `result` desde
la misma ejecución. Cada evento tiene `step`, `type`, `depth`, `range`,
`comparisons` y `data`. El resultado del comparador se normaliza a -1/0/1 para
registrarlo **sin llamar al comparador por segunda vez**.

`compare.data.merged` guarda el prefijo después de incorporar la posición elegida;
`merge.data.positions` incluye los sobrantes. Los arrays guardados por valor
mantienen sus instantáneas aunque continúe la combinación.

Al solicitar `includeTrace=true`, el servicio:

1. Calcula las puntuaciones con los extremos de todos los resultados, si corresponde.
2. Ordena todos los vuelos sin traza; ese contador se devuelve en `result.comparisons`.
3. Toma los primeros `min(8, n)` vuelos de la entrada ya puntuada, antes de ordenar.
4. Ejecuta Merge Sort de nuevo sobre ese subconjunto con traza. Su contador queda
   en `result.demonstration.comparisons` y no se suma al anterior.

`demonstration` identifica la selección con `selection='first_input_items'`,
`limit=8` y `total_results=n`; contiene criterio, clave, entrada, vuelos ordenados,
contador y traza. Cada posición de los eventos referencia `demonstration.input`.
Los ocho son los primeros recibidos, **no los ocho mejor clasificados**, ni los
dos o tres seleccionados para comparar.

El límite pertenece al servicio de demostración: el Merge Sort genérico puede
recibir más de ocho elementos y el ranking normal procesa toda la búsqueda.
Una entrada vacía con traza produce `input` y `result` vacíos, ambos con contador cero.

Blade presenta esas posiciones mediante los parciales de
`resources/views/algorithm/partials/`. El JavaScript de B4 únicamente oculta o
muestra paneles, actualiza botones y anuncia el contador del evento visible.
Reiniciar vuelve a la entrada; no ejecuta de nuevo PHP. Sin JavaScript se pueden
leer todos los eventos. `demonstration=null` muestra el estado sin demostración.

## 7. Complejidad de esta implementación

Sea `n` el número de vuelos recibidos y `m = min(8, n)` el tamaño de la demostración.
Se supone un comparador O(1), campos de tamaño acotado y operaciones numéricas de
costo constante. Las consultas a la base de datos no forman parte de este análisis.
Para cero o un elemento se aplica el caso base O(1).

### Ejecución normal, sin traza

Dividir y copiar con `array_slice()`, combinar con índices y reconstruir los valores
da la recurrencia `T(n) = T(floor(n/2)) + T(ceil(n/2)) + O(n)`: tiempo **O(n log n)**.
Esta versión no omite combinaciones por detectar que la entrada ya está ordenada.
No usa extracciones que desplacen reiteradamente todos los elementos.

El espacio auxiliar máximo vivo es **O(n)**: posiciones, sublistas y buffers de
combinación. La pila recursiva requiere O(log n), incluida en esa cota. Aunque a lo
largo de la ejecución se copian O(n log n) posiciones, no todas esas copias están
vivas simultáneamente. No es un ordenamiento in situ.

`FlightRankingService` obtiene los extremos en O(n), prepara los vuelos en O(n)
y ordena en O(n log n). Su tiempo total normal es O(n log n) y su espacio O(n),
incluida la lista de salida con campos añadidos.

### Qué cambia al almacenar la traza

El número de eventos es O(n log n), pero **no todos tienen tamaño constante**.
Cada comparación guarda el prefijo completo `merged`. En una combinación de
tamaño `k`, los prefijos pueden tener tamaños `1, 2, ..., k-1`; su suma es O(k²).
Mantener las instantáneas obliga a conservar/copiar esos contenidos cuando el
buffer sigue creciendo. Los arrays de PHP pueden compartir contenido mientras
no cambia, pero esa copia al modificar no elimina el costo de preservar prefijos.

Sumar este trabajo a lo largo del árbol da **O(n²) de almacenamiento de traza y
O(n²) de tiempo total para construirla en el peor caso**. Las comparaciones de
claves siguen siendo O(n log n); lo que crece es el registro de instantáneas.
No sería correcto afirmar O(n) de memoria total al trazar una lista arbitrariamente
grande con este formato. Es un análisis del código, no un benchmark de memoria.

| Ejecución | Tiempo | Espacio |
| --- | --- | --- |
| `sort(..., false)` | O(n log n) | O(n) auxiliar |
| `sort(..., true)` sobre n elementos | O(n²) con las instantáneas | O(n²) con la traza |
| `rank(..., false)` | O(n log n) | O(n) |
| `rank(..., true)` | O(n log n + m²) | O(n + m²) |

Como la aplicación fija `m <= 8`, el costo de la demostración está acotado y el
ranking conserva O(n log n) de tiempo y O(n) de espacio respecto de `n`. Blade
también materializa los paneles y sus secuencias, por lo que almacenar/renderizar
pasos no es gratuito. Navegar no reordena: el script recorre los paneles registrados
para cambiar su visibilidad.

## 8. Limitaciones y evidencia

- Vuelos, aerolíneas y precios son de demostración; no hay tarifas en tiempo real,
  compra ni reserva. Las diferencias mostradas no constituyen una oferta comercial.
- Los pesos expresan preferencias subjetivas. El equilibrio solo combina precio y
  duración; escalas y equipaje se muestran para que la persona los considere.
- La normalización depende del conjunto encontrado. Cambiar ruta, fecha u opciones
  puede cambiar una puntuación, incluso si el precio y duración de un vuelo no cambian.
- Una puntuación no es un porcentaje de calidad ni permite comparar directamente
  vuelos normalizados en búsquedas diferentes. Elegir dos o tres vuelos no redefine
  los extremos originales.
- Un componente constante aporta cero por convenio. Con pesos extremos pueden
  aparecer empates adicionales; la estabilidad conserva el orden recibido.
- Los floats tienen precisión finita; se evita redondear antes de comparar, sin
  prometer aritmética racional exacta.

[B5](validacion-algoritmos.md) registró 112 pruebas del algoritmo/servicio/traza
aprobadas con 2283 aserciones, 18 pruebas de renderizado con 2310 aserciones y 6
pruebas de navegación en Node. Son resultados de esa validación, no una nueva
ejecución de la suite en B6 ni una revisión visual en navegador. B6 contrastó el
código y ejecutó únicamente la comprobación directa A/B/C descrita en este documento.
La validación final de uso e instalación de A6 debe mantenerse en la documentación
correspondiente de José.
