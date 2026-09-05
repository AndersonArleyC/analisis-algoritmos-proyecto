# B5: validación de algoritmo, puntuación y traza

Revisión realizada por encargo de Anderson (integrante B) el **4 de septiembre de
2026**, zona `America/Bogota`, en la rama `test/b5-validacion-algoritmos`.
Base inspeccionada: `dae5b29171626bad82e29c17306e5d05034d0934`, más los archivos
locales indicados abajo. Referencias: [plan](plan-proyecto.md),
[contrato v1](contrato-integracion.md) y [componente B4](componente-demo-algoritmo.md).

Esta revisión evalúa cobertura de requisitos mediante lectura y ejecución de
pruebas. No es una medición porcentual de cobertura de líneas o ramas.

## Estado inicial y alcance

Antes de editar, se leyeron los servicios `MergeSort` y `FlightRankingService`,
las cuatro clases unitarias de B1/B2/B3, `AlgorithmDemoTest`, las pruebas de
JavaScript de B4 y sus fixtures.

Ya existían sin seguimiento en Git los siguientes archivos, que se conservaron:

- `package-lock.json`.
- `tests/Feature/AlgorithmDemoTest.php`.
- `tests/Fixtures/AlgorithmDemoFixtures.php`.
- `tests/Fixtures/render-algorithm-demo.php`.
- `tests/JavaScript/algorithm-demo.test.js`.

Los resultados de B4 incluyen esos archivos locales: no significan que estén
incluidos en el commit base ni son pruebas nuevas creadas por B5.

B5 solo amplía `tests/Unit/FlightRankingTraceTest.php` y crea este documento.
No se cambian servicios, interfaces, dependencias, controladores, rutas, vistas,
README ni archivos de José. No se crean commits ni se hace push.

## Cobertura que ya existía

Las referencias a métodos siguientes corresponden a archivos bajo `tests/Unit/`,
salvo donde se indica otro directorio.

| Requisito | Evidencia existente y alcance |
| --- | --- |
| Merge Sort genérico | `MergeSortTest::test_sorts_numbers_without_changing_the_input`, con `numberLists`: vacío, un elemento, dos elementos, entrada ordenada/inversa, duplicados, todos iguales, negativos y decimales. `test_uses_the_comparator_sign_for_custom_ordering` verifica un comparador de longitud descendente, con signos distintos de ±1. |
| Precio, duración y retorno del servicio | `FlightRankingServiceTest::test_price_and_duration_order_do_not_depend_on_weight`: ambos criterios con pesos 0, 0.5 y 1; comprueba orden, contador y campos de puntuación nulos. `test_defaults_and_return_structure_match_the_contract` comprueba valores predeterminados y estructura completa. |
| Equilibrio y ejemplo A/B/C | `FlightRankingServiceTest::test_abc_equilibrium_with_each_weight`: pesos 0, 0.5 y 1, extremos, componentes normalizados, puntuaciones y comparaciones del ranking. |
| Estabilidad | `MergeSortTest::test_preserves_input_order_for_equal_keys_across_both_halves`; `FlightRankingServiceTest::test_ties_preserve_input_order_without_secondary_keys`; `FlightRankingTraceTest::test_equal_flights_remain_stable_in_the_demonstration`. Usan orden de entrada e IDs que no equivalen al orden numérico esperado. |
| Conservación de la entrada | Las pruebas numéricas de `MergeSortTest`, `FlightRankingServiceTest::test_original_fields_and_input_are_preserved` y `FlightRankingTraceTest::test_only_the_demonstration_is_limited_and_counters_are_independent` comparan entrada original y campos recibidos, con y sin traza. |
| Componentes constantes y casos base | `FlightRankingServiceTest::test_constant_components_normalize_to_zero`: precios iguales, duraciones iguales y ambos iguales. `test_empty_lists` y `test_single_flights`: los tres criterios, cero comparaciones y normalización correspondiente; el vuelo único incluye precio cero. |
| Precisión | `FlightRankingServiceTest::test_scores_are_not_rounded_or_treated_as_ties` distingue 0.2500005 de 0.25. La comparación genérica también incluye diferencias decimales menores que uno. |
| Entradas inválidas de configuración | `FlightRankingServiceTest::test_invalid_criteria_are_rejected_even_for_empty_lists`: criterios desconocidos, vacío, espacios y mayúsculas. `test_invalid_weights_are_rejected_for_every_criterion`: negativos, mayores que uno, `NAN`, `INF` y `-INF`, con listas vacías y pobladas, para los tres criterios; espera `InvalidArgumentException`. |
| Eventos reales y resultado | `MergeSortTraceTest::test_trace_matches_the_contract_example_exactly` compara el ejemplo completo de dos valores. `test_events_replay_real_comparisons_and_merges` contrasta cada evento con llamadas reales al comparador y reproduce cabeceras, elecciones, prefijos, sobrantes y posiciones finales sin volver a ordenar. Verifica pasos, rangos, profundidad, signos y contador. |
| Orden recursivo, duplicados e instantáneas | `MergeSortTraceTest::test_odd_splits_follow_the_original_ranges_left_first` verifica la división impar y el recorrido izquierdo primero. `test_ties_use_original_positions_and_snapshots_survive_later_calls` verifica posiciones en empates y conservación de eventos tras reutilizar el algoritmo. |
| Demostración de hasta ocho vuelos | `FlightRankingTraceTest::test_only_the_demonstration_is_limited_and_counters_are_independent`: tamaños 0, 1, 7, 8, 9 y 10 para los tres criterios. Comprueba selección de los primeros vuelos originales, metadatos, ranking completo y reconstrucción del resultado del subconjunto mediante el último evento. |
| Normalización global | `FlightRankingServiceTest::test_normalization_and_ranking_use_all_flights_and_reset_between_calls` incluye extremos después del octavo vuelo. `FlightRankingTraceTest::test_balanced_demonstration_uses_extremes_outside_the_first_eight` usa un precio máximo externo que cambia el orden de B y C, y comprueba los valores normalizados recibidos por la demostración. |
| Contadores independientes | La prueba de límites de `FlightRankingTraceTest` verifica contadores esperados para ambas ejecuciones: por ejemplo, diez vuelos en orden inverso requieren 19 comparaciones en el ranking y 12 en la demostración de ocho. El contador final de la traza coincide con sus eventos `compare`; activar la demostración conserva el resultado y contador globales. |
| Renderizado B4 | `tests/Feature/AlgorithmDemoTest.php`: nulo, vacío, un vuelo, A/B/C, ocho y diez vuelos, los tres criterios, posiciones de cada evento, decisiones, empates, escape de texto y carga única de recursos con varias instancias. |
| Navegación B4 | `tests/JavaScript/algorithm-demo.test.js`: avance y retroceso completos, límites, reinicio, nulo, inicialización e independencia entre instancias, con HTML real generado por Blade y un adaptador mínimo del DOM en Node. |

## Hueco relevante y cambio de B5

Las pruebas de ranking ya verificaban los pesos 0/0.5/1, pero todas las ejecuciones
de demostración de equilibrio usaban **0.5**. Por tanto, no protegían directamente
contra una regresión que restaurara el peso predeterminado al construir la
demostración, aunque el ranking completo respetara el peso elegido.

Se añade un solo método parametrizado:
`FlightRankingTraceTest::test_balanced_demonstration_uses_the_requested_weight`,
con dos casos: peso 0 y peso 1. Reutiliza el fixture de vuelos del archivo y el
verificador existente del evento final. Comprueba:

- Orden esperado del subconjunto y puntuaciones de su entrada.
- Igualdad de los vuelos completos del ranking y la demostración para A/B/C.
- Contadores esperados de cada ejecución y coherencia del último evento.

No se repiten las pruebas de peso 0.5 ni se añaden combinaciones exhaustivas de
tamaños, criterios o errores ya cubiertos. No se detectó un defecto de producción
en la revisión ni en las ejecuciones realizadas; los servicios permanecen intactos
mientras José desarrolla A5.

## Resultados comprobados para A/B/C

Entrada: A = 200000 COP y 720 minutos; B = 500000 COP y 120 minutos;
C = 280000 COP y 180 minutos. Estos resultados corresponden a ese conjunto de
tres arrays, no a la totalidad de un seeder o una búsqueda real.

| Peso del precio | Puntuaciones A, B, C | Orden | Comparaciones del ranking |
| --- | --- | --- | --- |
| 0 | 1; 0; 0.1 | B, C, A | 3 |
| 0.5 | 0.5; 0.5; 11/60 ≈ 0.1833333333 | C, A, B | 3 |
| 1 | 0; 1; 4/15 ≈ 0.2666666667 | A, C, B | 2 |

Con 50/50, A precede a B por estabilidad. Las fracciones de la tabla describen
las puntuaciones esperadas; el servicio conserva floats sin redondear para ordenar.
Los dos nuevos casos verifican también los órdenes y contadores de la demostración
con pesos 0 y 1.

## Entorno y comandos ejecutados

Desde la raíz `analisis-algoritmos-proyecto`, en Windows PowerShell:

```powershell
php -v
composer -V
node -v
php vendor/bin/phpunit --version
```

Entorno observado: **PHP 8.5.10**, **Composer 2.10.3**, **Node v24.20.0** y
**PHPUnit 12.5.34**. Se utilizaron las dependencias instaladas, sin modificarlas.

### Algoritmo, puntuación y traza

```powershell
php vendor/bin/phpunit tests/Unit/MergeSortTest.php tests/Unit/FlightRankingServiceTest.php tests/Unit/MergeSortTraceTest.php tests/Unit/FlightRankingTraceTest.php
```

Resultado real: **112 pruebas aprobadas, 2283 aserciones, 0 fallos**, código de
salida 0. Incluye las 110 pruebas existentes y los dos casos añadidos por B5.

### Renderizado automatizado de B4

```powershell
php vendor/bin/phpunit tests/Feature/AlgorithmDemoTest.php
```

Resultado real: **18 pruebas aprobadas, 2310 aserciones, 0 fallos**, código de
salida 0. Renderiza Blade y examina el HTML con `DOMDocument`/`DOMXPath`.

### Navegación automatizada de B4

```powershell
node --test tests/JavaScript/algorithm-demo.test.js
```

Resultado real: **6 pruebas aprobadas, 0 fallos, 0 omitidas**, código de salida 0.
Genera HTML mediante PHP y ejecuta el JavaScript con un adaptador del DOM en Node.

### Formato y revisión del cambio

```powershell
php vendor/bin/pint --test tests/Unit/FlightRankingTraceTest.php
git diff --check
```

Pint: **aprobado**. `git diff --check`: sin incidencias de espacios en el diff.

## Límites de esta validación

- **No se realizó ninguna prueba en navegador ni revisión visual en esta sesión.**
  El DOM de PHP y el adaptador de Node no validan distribución, CSS, dimensiones,
  interacción real de teclado ni comportamiento de lectores de pantalla.
- Las pruebas del algoritmo y servicio usan arrays independientes de la base de
  datos. La forma de `FlightData` y su coherencia pertenecen a A2/A3 según el
  contrato; no se añadieron exigencias de validación de vuelos al servicio.
- No se ejecutaron seeders ni se validaron filtros HTTP, preferencias de la vista
  integrada o el recorrido A5 completo. Este informe no certifica tareas de José.
- Los resultados corresponden al estado local revisado y a los comandos enumerados;
  no se afirma que se ejecutara toda la suite funcional del proyecto.
