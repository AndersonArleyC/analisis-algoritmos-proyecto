# AeroCompare — Contrato de integración v1

Contrato acordado como base de A1 para José (A) y Anderson (B). Define interfaces futuras; no declara que estén implementadas. Alcance y responsabilidades completos en [plan-proyecto.md](plan-proyecto.md). Cambiar este contrato antes de introducir incompatibilidades.

## 1. Capas y responsabilidades

- A2: modelo, migración y datos simulados.
- A3/A5: `FlightSearchController` valida, filtra por origen, destino y fecha de salida en `America/Bogota`, convierte Eloquent a arrays y llama al servicio. No ordena por SQL ni por utilidades de ordenamiento.
- B1: `App\Services\Algorithms\MergeSort`, genérico y estable; no conoce Laravel, vuelos, precios ni Eloquent.
- B2: `App\Services\FlightRankingService`, calcula puntuaciones y proporciona el comparador al algoritmo.
- B3: eventos reales del algoritmo y demostración del subconjunto desde el servicio.
- A4/A5 y B4: Blade presenta resultados y reproduce eventos; JavaScript no vuelve a ordenar ni inventa pasos.

El orden de entrada es el orden recibido después del filtro. Se conserva en empates, sin clave secundaria por ID. No se promete el mismo orden entre consultas SQL independientes sin orden explícito. No usar `sort`, `usort`, `asort`, `Collection::sortBy`, `ORDER BY` ni equivalentes para ordenar vuelos.

## 2. Datos de entrada al servicio

`$flights` es una lista PHP con índices consecutivos desde cero: `list<FlightData>`. Cada `FlightData` es un array con estos campos obligatorios:

| Campo | Tipo y regla |
| --- | --- |
| `id` | `int`, positivo y único dentro de la búsqueda |
| `airline` | `string` no vacío, nombre simulado |
| `flight_code` | `string` no vacío |
| `origin`, `destination` | `string`, códigos de aeropuerto nacionales de tres letras mayúsculas, diferentes entre sí |
| `departure_at`, `arrival_at` | `string`, ISO 8601 con offset, por ejemplo `2026-10-15T08:00:00-05:00`, en `America/Bogota` |
| `duration_minutes` | `int` positivo, duración total incluyendo escalas |
| `stops` | `int` mayor o igual a cero |
| `baggage_description` | `string` no vacío |
| `total_price_cop` | `int` mayor o igual a cero, precio por pasajero con impuestos simulados |

La llegada debe ser salida más `duration_minutes`, incluso al cambiar de día. A2/A3 garantizan la forma y coherencia de estos datos. No enviar Carbon, modelos, colecciones ni valores monetarios formateados. El servicio no consulta la base de datos y no modifica la entrada.

## 3. Interfaz genérica de Merge Sort (B1; traza en B3)

Firma pública prevista, no código a implementar en A1:

```php
public function sort(array $items, callable $compare, bool $recordTrace = false): array;
```

- `$items`: lista de valores simples, no necesita `id`; admite duplicados.
- `$compare($left, $right): int`: negativo si izquierda precede, cero si empatan, positivo si derecha precede. Debe ser consistente y no tener efectos secundarios. Para números usar comparación como `<=>`, no convertir diferencias decimales a entero.
- Retorno: `['items' => list<mixed>, 'comparisons' => int, 'trace' => list<TraceEvent>]`.
- Una comparación de claves equivale a una llamada al comparador entre las cabeceras de las dos sublistas durante la combinación. Copiar sobrantes y comprobar índices no suma comparaciones.
- Ante empate se toma primero el elemento izquierdo. Nunca se modifica la lista del llamador.
- Dividir en `intdiv(n, 2)` elementos a la izquierda y el resto a la derecha; procesar primero izquierda y luego derecha. Esto fija los pasos y contadores de los ejemplos.
- Sin traza, `trace` es `[]`. B1 entrega ordenamiento y contador; B3 habilita `recordTrace=true`. Hasta B3 no consumir la traza desde la interfaz.
- Vacío y un elemento: cero comparaciones; resultado igual a la entrada.
- Mantener O(n log n) de tiempo y O(n) de espacio auxiliar sin traza; el registro añade memoria. Evitar operaciones de extracción que desplacen reiteradamente todos los elementos.

## 4. Interfaz del servicio de ranking (B2/B3)

```php
public function rank(
    array $flights,
    string $criterion = 'price',
    float $priceWeight = 0.5,
    bool $includeTrace = false,
): array;
```

`criterion`: solamente `price`, `duration` o `balanced`. `priceWeight`: número finito entre 0 y 1 inclusive; `timeWeight = 1 - priceWeight`. El controlador convierte el control 0–100 dividiendo por 100. El servicio rechaza criterio o peso inválidos con `InvalidArgumentException`, incluso con lista vacía. La capa HTTP valida antes y muestra errores en español.

Claves ascendentes: `total_price_cop`, `duration_minutes` y `score`, respectivamente. El peso no afecta los criterios `price` o `duration`.

Para `balanced`, recorrer todos los vuelos filtrados antes de ordenar:

```text
normalized_price = (price - min_price) / (max_price - min_price)
normalized_duration = (duration - min_duration) / (max_duration - min_duration)
score = priceWeight * normalized_price + (1 - priceWeight) * normalized_duration
```

Si un denominador es cero, su componente vale `0.0`. No redondear para comparar ni aplicar tolerancias que alteren empates. Redondear únicamente al presentar. La puntuación es una regla propia del proyecto que depende del conjunto encontrado y de los pesos, no un porcentaje de calidad ni una recomendación objetiva.

Retorno estable en todos los criterios:

```text
{
  criterion: "price" | "duration" | "balanced",
  priceWeight: float,
  timeWeight: float,
  flights: list<RankedFlight>,
  comparisons: int,
  normalization: null | {
    min_price: int, max_price: int,
    min_duration: int, max_duration: int
  },
  demonstration: null | Demonstration
}
```

`RankedFlight` contiene los campos originales y `normalized_price`, `normalized_duration`, `score`: floats para `balanced`, `null` para los otros criterios. `normalization` es `null` fuera de `balanced` o si no hay vuelos. `comparisons` cuenta exclusivamente el ordenamiento completo, no el cálculo de extremos ni la demostración.

Sin vuelos: `flights=[]`, `comparisons=0`, `normalization=null`. Con `includeTrace=false`, `demonstration=null`. La comparación visual de dos o tres vuelos reutiliza estos valores; no normaliza de nuevo. Las etiquetas de más barato y más rápido incluyen todos los empates y se determinan sobre la búsqueda completa.

## 5. Demostración de hasta ocho vuelos (B3/B4)

Con `includeTrace=true`, el servicio toma los primeros `min(8, count($flights))` vuelos en el orden de entrada, antes de ordenar. Reutiliza puntuaciones calculadas sobre toda la búsqueda. Ejecuta Merge Sort sobre ese subconjunto con registro real, independientemente del ordenamiento completo sin traza.

```text
Demonstration = {
  selection: "first_input_items",
  limit: 8,
  total_results: int,
  criterion: "price" | "duration" | "balanced",
  key: "total_price_cop" | "duration_minutes" | "score",
  input: list<RankedFlight>,
  flights: list<RankedFlight>,
  comparisons: int,
  trace: list<TraceEvent>
}
```

`input` conserva el subconjunto original; `flights` es su resultado ordenado. Mostrar “Demostración de los primeros X vuelos de N resultados, en su orden de entrada”. No sumar su contador al principal. Para vacío, devolver esta misma estructura con listas vacías, contador cero y eventos `input` y `result` vacíos.

## 6. Eventos reales de la traza

El algoritmo usa posiciones originales de su propia lista, no IDs de vuelos. Así admite valores iguales sin ambigüedad. En la demostración, cada posición referencia `demonstration.input[position]`, de donde la vista obtiene código y clave. No es necesario insertar metadatos en los elementos originales.

Campos comunes obligatorios:

```text
TraceEvent = {
  step: int,          // consecutivo desde 0
  type: "input" | "split" | "compare" | "merge" | "result",
  depth: int,         // raíz = 0
  range: [int, int],  // intervalo original [inicio, fin), fin excluido
  comparisons: int,  // acumulado de esta ejecución
  data: object       // array asociativo PHP, según tabla
}
```

| Tipo | `data` y significado |
| --- | --- |
| `input` | `{positions: list<int>}`. Una vez al inicio, lista original completa. |
| `split` | `{positions: list<int>, left: list<int>, right: list<int>}`. Antes de descender, división de un rango de al menos dos elementos. |
| `compare` | `{left: int, right: int, outcome: -1\|0\|1, chosen: int, merged: list<int>}`. Posiciones de las cabeceras comparadas, signo del comparador, posición elegida y prefijo combinado después de incorporarla. Incrementa el contador exactamente en uno. |
| `merge` | `{positions: list<int>}`. Sublista ordenada completa después de combinar y copiar sobrantes. |
| `result` | `{positions: list<int>}`. Una vez al final, orden final de toda la ejecución. |

`split`, `compare` y `merge` usan el rango y profundidad de la llamada que combina. `input` y `result` usan raíz y `[0,n]`. No emitir divisiones ni combinaciones para cero o un elemento. El contador comienza en cero, solo aumenta con `compare`, y el último evento coincide con el contador devuelto. `outcome` es el signo normalizado del resultado del comparador; no se llama al comparador otra vez para registrar.

Anterior/siguiente/reiniciar solo cambian el evento mostrado. Las instantáneas deben conservar su contenido original, aunque los buffers internos cambien posteriormente.

## 7. Ejemplo completo del algoritmo genérico

Entrada: `sort([200000, 100000], fn ($a, $b) => $a <=> $b, true)`.

Salida esperada por contrato (ejemplo, no prueba ejecutada):

```json
{
  "items": [100000, 200000],
  "comparisons": 1,
  "trace": [
    {"step":0,"type":"input","depth":0,"range":[0,2],"comparisons":0,"data":{"positions":[0,1]}},
    {"step":1,"type":"split","depth":0,"range":[0,2],"comparisons":0,"data":{"positions":[0,1],"left":[0],"right":[1]}},
    {"step":2,"type":"compare","depth":0,"range":[0,2],"comparisons":1,"data":{"left":0,"right":1,"outcome":1,"chosen":1,"merged":[1]}},
    {"step":3,"type":"merge","depth":0,"range":[0,2],"comparisons":1,"data":{"positions":[1,0]}},
    {"step":4,"type":"result","depth":0,"range":[0,2],"comparisons":1,"data":{"positions":[1,0]}}
  ]
}
```

## 8. Ejemplo de entrada y salida del servicio

Entrada completa construida en PHP únicamente como documentación:

```php
$common = [
    'airline' => 'Aerolínea de demostración',
    'origin' => 'BOG',
    'destination' => 'MDE',
    'departure_at' => '2026-10-15T08:00:00-05:00',
    'stops' => 0,
    'baggage_description' => 'Un artículo personal',
];
$flights = [
    array_merge($common, ['id' => 1, 'flight_code' => 'DEMO-A',
        'arrival_at' => '2026-10-15T20:00:00-05:00',
        'duration_minutes' => 720, 'total_price_cop' => 200000]),
    array_merge($common, ['id' => 2, 'flight_code' => 'DEMO-B',
        'arrival_at' => '2026-10-15T10:00:00-05:00',
        'duration_minutes' => 120, 'total_price_cop' => 500000]),
    array_merge($common, ['id' => 3, 'flight_code' => 'DEMO-C',
        'arrival_at' => '2026-10-15T11:00:00-05:00',
        'duration_minutes' => 180, 'total_price_cop' => 280000]),
];
$result = $service->rank($flights, 'balanced', 0.5, false);
```

Salida esperada: `criterion='balanced'`, ambos pesos `0.5`, `comparisons=3`, `demonstration=null`, `normalization=['min_price'=>200000, 'max_price'=>500000, 'min_duration'=>120, 'max_duration'=>720]`. `flights` contiene los arrays originales completos en orden C, A, B, con los siguientes campos añadidos (fracciones expresadas matemáticamente; los valores reales son floats sin redondear):

| Vuelo | `normalized_price` | `normalized_duration` | `score` |
| --- | --- | --- | --- |
| C | 4/15 | 1/10 | 11/60 ≈ 0.1833333333333333 |
| A | 0 | 1 | 0.5 |
| B | 1 | 0 | 0.5 |

C precede a ambos; A precede a B por estabilidad. Para `price`, el orden es A,C,B; para `duration`, B,C,A. Con peso 0 gana B; con peso 1 gana A. Estos números corresponden exclusivamente a estos tres vuelos: añadir opciones cambia los extremos. A2 debe escoger sus otras opciones de demostración de forma que conserve el comportamiento exigido de C frente a A y B y B5 debe comprobarlo.

## 9. Validación futura y entrega

B5 verificará vacío, un elemento, duplicados, estabilidad, entradas desordenadas, criterios, componentes constantes, pesos 0/0.5/1 y A/B/C. Verificará además los contadores y que las posiciones del evento final reproduzcan el resultado de la demostración, incluso con más de ocho resultados y normalización global. A6 verificará filtros por ruta/fecha local, validación HTTP, presentación y selección de dos o tres vuelos.

Para comenzar B1 basta este contrato y el plan: no requiere A2. A5 depende del modelo/filtro de A2/A3 y del servicio B2; la vista educativa B4 requiere B3. No crear implementaciones vacías de estas tareas en A1 ni atribuir trabajo pendiente a ninguno de los integrantes.
