# AeroCompare: guion propuesto de sustentación

Propuesta de B6 para **José (integrante A)** y **Anderson (integrante B)**,
preparada el 4 de septiembre de 2026 a partir del
[plan](plan-proyecto.md), el [contrato](contrato-integracion.md), el código local
y la [validación B5](validacion-algoritmos.md).

Los textos entre comillas son sugerencias para ensayar. Las acciones de pantalla
son una **secuencia por realizar**, no constancia de que se haya grabado el video
o completado una revisión en navegador. Los tiempos son orientativos y deben
ajustarse a la duración que exija la evaluación.

## 1. Reparto propuesto según el plan

| Integrante | Responsabilidades asignadas en el plan | Parte sugerida de la sustentación |
| --- | --- | --- |
| José, A | A1 base Laravel/SQLite y contrato; A2 modelo, migración y seeders; A3 buscador y filtros; A4 tarjetas y comparación; A5 integración y preferencias; A6 pruebas funcionales y documentación de uso. | Problema, datos simulados, validación y filtrado, interfaz e integración; presentar la evidencia de A6 cuando esté disponible. |
| Anderson, B | B1 Merge Sort manual; B2 puntuación y criterios; B3 traza real; B4 visualización educativa; B5 pruebas del algoritmo/servicio/traza; B6 documentación y guion. | Fórmula, separación entre puntuación y ordenamiento, código del algoritmo, estabilidad, eventos, complejidad y evidencia automatizada de B5. |

Esta tabla reproduce la asignación del plan. No acredita commits individuales,
horas trabajadas ni finalización de tareas. Para explicar aportes reales, cada
integrante debe contrastarlos con su trabajo y con el historial que corresponda;
no inventar autoría o identificadores de commits.

**Ambos deben entender la solución completa.** La división organiza la exposición,
pero cualquiera debe poder seguir el recorrido desde la búsqueda hasta los eventos
y explicar las decisiones principales del otro.

## 2. Guion de exposición, aproximadamente diez minutos

### 0:00–1:00 · Problema y objetivo · José

Texto propuesto:

> «AeroCompare permite explorar una decisión frecuente: ahorrar dinero o reducir
> el tiempo de viaje. Un vuelo barato puede tardar mucho y uno rápido puede ser
> costoso. Comparamos opciones por precio, duración o una preferencia de equilibrio.
> Los vuelos y precios son simulados; no ofrecemos reservas ni tarifas reales».

Mostrar el aviso de demostración y el buscador. Aclarar una sola vía, un pasajero,
moneda COP y horarios de Bogotá. No calificar un vuelo como universalmente mejor.

### 1:00–2:00 · Datos y capas · José, con transición a Anderson

Texto propuesto:

> «Primero validamos origen, destino y fecha. Filtramos los vuelos de esa ruta y
> día; después convertimos los modelos a arrays simples. El servicio recibe esos
> resultados y aplica el criterio elegido. Así mantenemos separados datos,
> integración, algoritmo y presentación».

Enseñar brevemente la transformación a `FlightData` y la llamada a `rank()` del
controlador, sin recorrer cada campo ni alterar el código. Explicar que la
validación HTTP y el ordenamiento son responsabilidades distintas.

Transición propuesta de Anderson:

> «El servicio decide qué clave comparar; Merge Sort decide cómo ordenar esa
> clave. El algoritmo no conoce Eloquent, SQL, vuelos ni la fórmula de equilibrio».

### 2:00–4:00 · Puntuación y Merge Sort · Anderson

Usar [algoritmo.md](algoritmo.md), secciones 3–5, como apoyo:

1. Mostrar precio y duración de A/B/C. Explicar normalización de cada componente
   y que los pesos suman 1. Con 50/50, C obtiene aproximadamente 0.183333, mientras
   A y B obtienen 0.5. Estas cifras corresponden al conjunto aislado de tres vuelos.
2. Mostrar en `FlightRankingService` la obtención de extremos, `normalize()`, el
   cálculo de `score` y el comparador `<=>`. Señalar que puntuar no ordena.
3. En `MergeSort`, localizar `array_keys`, el caso base, `intdiv`, las llamadas
   recursivas izquierda/derecha y la combinación mediante índices.
4. Explicar el recorrido A/B/C: dividir A de B/C; combinar C,B; comparar A con C;
   después A con B; ante empate conservar A antes de B. Resultado C,A,B.

Texto propuesto:

> «La condición menor o igual que cero es deliberada: en un empate elegimos la
> izquierda. Por eso el ordenamiento es estable. Usamos posiciones originales
> para distinguir elementos repetidos y reconstruir la salida sin modificar la entrada».

### 4:00–7:30 · Demostración de la aplicación · Ambos

Seguir la secuencia de la sección siguiente. José puede conducir búsqueda y
comparación; Anderson, criterios, pesos y recorrido del algoritmo. Evitar leer
el código y navegar al mismo tiempo: detenerse en una idea por paso.

### 7:30–9:00 · Complejidad y evidencia · Anderson

Texto propuesto:

> «Sin traza, dividir y combinar cuesta O(n log n) en tiempo y O(n) de espacio
> auxiliar. El registro guarda prefijos completos: no basta contar cuántos eventos
> hay, también importa su tamaño. Si trazáramos una lista arbitrariamente grande,
> ese almacenamiento y su construcción pueden costar O(n²). Por eso la demostración
> registra hasta ocho vuelos, mientras el ranking ordena todos».

Mostrar el informe B5, identificándolo como registro de una ejecución anterior:

| Evidencia registrada en B5 | Resultado |
| --- | --- |
| Algoritmo, ranking y traza | 112 pruebas aprobadas; 2283 aserciones. |
| Renderizado de Blade | 18 pruebas aprobadas; 2310 aserciones. |
| Navegación en Node con adaptador del DOM | 6 pruebas aprobadas. |

Explicar ejemplos de lo que verifican: estabilidad, pesos 0/0.5/1, componentes
constantes, parámetros inválidos, posiciones finales, límite de ocho y contadores
independientes. El número de aserciones no es un porcentaje de cobertura.
No presentar estas pruebas como ensayos en navegador.

Si se decide ejecutar comandos durante el video, usar los de
[validacion-algoritmos.md](validacion-algoritmos.md) y mostrar sus resultados reales
de ese momento. José debe aportar por separado el estado y evidencia de A6;
este guion no anticipa sus resultados.

### 9:00–10:00 · Límites y cierre · Ambos

Texto propuesto:

> «El resultado depende de la preferencia y de los vuelos encontrados. Cambiar el
> conjunto puede cambiar la normalización. La puntuación no es un porcentaje de
> calidad y solo combina precio y duración. El proyecto muestra una implementación
> manual, estable y comprobable de Merge Sort aplicada a un problema de comparación».

Reiterar que ambos pueden explicar el flujo completo. Dejar tiempo para preguntas
según las condiciones de la evaluación.

## 3. Secuencia de demostración propuesta

Los nombres de controles siguientes se contrastaron con las vistas actuales.
La secuencia debe ensayarse con la base local preparada antes de grabar; B6 no
ejecutó la búsqueda ni la comparación en un navegador.

| Paso | Quién conduce | Acción propuesta | Qué explicar o comprobar en pantalla |
| --- | --- | --- | --- |
| 1. Búsqueda de ejemplo | José | Pulsar **Cargar ejemplo**. El enlace actual usa BOG → MDE y fecha 2026-10-15. | Revisar campos y resultados realmente disponibles. El enlace aporta esos parámetros de búsqueda; no inserta datos. Si no hay vuelos, preparar el entorno con las instrucciones de uso de José antes de grabar. |
| 2. Precio | José | Elegir **Más barato** en **Ordenar vuelos** y pulsar **Buscar vuelos y aplicar preferencias**. | Verificar precio total ascendente, ruta/fecha conservadas y contador del ranking. |
| 3. Duración | Anderson | Elegir **Más rápido** y aplicar. | Observar duración ascendente, incluidas escalas. Un orden distinto responde a otra clave. |
| 4. Equilibrio 50/50 | Anderson | Elegir **Mejor equilibrio**, colocar **Importancia del precio** en 50 y aplicar. | Mostrar ambas importancias, puntuaciones y explicación. No asumir los números del conjunto A/B/C si la búsqueda contiene más vuelos. |
| 5. Preferencias extremas | Anderson | Llevar el control a 0 y aplicar; después a 100 y aplicar. | Con 0 se prioriza tiempo; con 100, precio. Mover el control solo cambia los porcentajes mostrados hasta pulsar el botón. Comprobar también que cambian las claves de equilibrio de la demostración. |
| 6. Comparación de opciones | José | Volver a equilibrio 50/50 y aplicar. Seleccionar dos vuelos y después un tercero. Mostrar **Quitar** o **Limpiar selección**. | Comparar precio, duración, escalas y equipaje. Las diferencias se refieren a mínimos entre seleccionados; las puntuaciones de la búsqueda no se recalculan. La nueva búsqueda/aplicación de preferencias limpia la selección. |
| 7. Recorrido del algoritmo | Anderson | Ir a **Cómo funciona Merge Sort**, leer «primeros X vuelos de N resultados», clave y contador. Usar **Siguiente**, **Anterior** y **Reiniciar**. | Localizar entrada, división, comparación, combinación y resultado. Señalar la posición elegida y un empate si aparece. No confundir los vuelos seleccionados para comparar con el subconjunto demostrado. |
| 8. Resultado y contadores | Ambos | Llegar al último evento y contrastarlo con los vuelos del subconjunto. | El contador acumulado coincide con el total de la demostración. El ranking completo tiene otro contador; no se suman. Reiniciar cambia la vista al primer evento, sin volver a ordenar. |

Para evidenciar el límite, usar una búsqueda con más de ocho opciones si la base
preparada las ofrece y anotar el total real. Si solo hay ocho, se puede explicar el
límite y apoyar el caso de diez con la evidencia automatizada de B5, identificándola
como prueba de arrays. No inventar una cantidad de resultados o un empate para el video.

Si se muestran A/B/C aislados como apoyo, anunciar expresamente que se trata del
ejemplo matemático de tres vuelos. En la lectura del código de B6 se comprobó su
salida por PHP CLI: precio A,C,B; duración B,C,A; equilibrio 50/50 C,A,B, con tres
comparaciones y nueve eventos. Esto no acredita una ejecución de la interfaz.

## 4. Preguntas de práctica y respuestas

**¿Qué parte calcula la puntuación y cuál ordena?**
`FlightRankingService` obtiene extremos y calcula `score` en equilibrio. El
comparador selecciona la clave; `MergeSort` realiza el ordenamiento manual.

**¿Por qué no sumamos directamente pesos y minutos?**
Tienen unidades y escalas diferentes. Normalizamos cada componente con sus extremos
para combinarlos sin que el tamaño numérico de los COP domine por sí solo.

**¿Qué pasa si todos los precios son iguales?**
El denominador de precio sería cero; el código asigna `0.0` a ese componente.
El tiempo puede distinguir vuelos si tiene peso positivo. Si ambos componentes
son constantes, todas las puntuaciones empatan y se conserva el orden recibido.

**¿Qué significan pesos 0 y 1? ¿Se permiten valores fuera del intervalo?**
Con peso de precio 0 solo influye la duración; con 1 solo el precio. El servicio
rechaza pesos no finitos o fuera de `[0,1]`, incluso con entrada vacía.

**¿Por qué Merge Sort es estable aquí?**
Durante la combinación, `outcome <= 0` toma primero la izquierda en empates.
Cada mitad también se ordena de forma estable y sus posiciones provienen del
orden original. Un desempate adicional por ID alteraría esa regla del contrato.

**¿Qué ocurriría si se usara `< 0` en vez de `<= 0`?**
Ante empate entraría primero la derecha. Dos elementos iguales situados en mitades
distintas podrían invertir su orden original y se perdería la estabilidad.

**¿Por qué registrar posiciones en lugar de IDs?**
El algoritmo genérico admite valores sin ID y duplicados. Las posiciones distinguen
cada aparición. B4 obtiene código y clave mediante `demonstration.input[position]`.

**¿Por qué usar `<=>` en vez de convertir una resta a entero?**
El comparador necesita un signo fiable. Una diferencia decimal menor que uno
convertida a entero puede volverse cero y crear un empate incorrecto.

**¿Por qué no se redondea antes de ordenar?**
Dos puntuaciones cercanas podrían parecer iguales. El código compara el float
recibido; solo lo formatea para mostrarlo. No promete aritmética racional exacta.

**¿De dónde salen O(n log n) y O(n)?**
Hay una profundidad logarítmica de divisiones y trabajo lineal por nivel para
dividir/combinar. Sin traza, las sublistas y buffers vivos ocupan O(n); la pila
es O(log n). Las copias acumuladas a lo largo de la ejecución no son lo mismo
que el máximo de memoria utilizada simultáneamente.

**¿La traza mantiene ese mismo costo espacial?**
No. Cada evento `compare` guarda un prefijo completo. Sumar sus tamaños durante
una combinación produce un costo cuadrático. El registro completo sobre n valores
puede ocupar O(n²) y requerir O(n²) de tiempo para construir las instantáneas,
aunque las comparaciones sigan siendo O(n log n).

**¿Entonces el ranking de la aplicación se vuelve cuadrático?**
El ranking ordena n vuelos sin traza y solo traza `m=min(8,n)`. Su costo es
O(n log n + m²) en tiempo y O(n + m²) en espacio. Con el límite fijo de ocho,
conserva O(n log n) y O(n) respecto de n.

**¿Qué mide exactamente el contador?**
Llamadas al comparador de claves entre cabeceras en `merge()`. No cuenta filtros,
normalización, verificaciones de índices, copias de sobrantes ni clics. Nueve
eventos de A/B/C no significan nueve comparaciones: solo tres son `compare`.

**¿Por qué hay dos contadores y dos ordenamientos?**
Uno corresponde al ranking completo y otro a la demostración independiente. Se
reutilizan las puntuaciones globales, pero se ordena el subconjunto con registro
para hacerlo legible. Sus comparaciones no se suman al total del ranking.

**¿Por qué no normalizar solamente los ocho vuelos?**
Podrían tener extremos distintos y producir otro equilibrio. La demostración debe
explicar el mismo criterio aplicado en la búsqueda, usando sus puntuaciones globales.
La prueba de B3 con un máximo de precio externo al octavo verifica esta diferencia.

**¿La puntuación de un vuelo puede cambiar sin cambiar su precio ni duración?**
Sí: si cambia el conjunto encontrado, pueden cambiar mínimos y máximos. Por eso
no es una calificación absoluta ni debe compararse directamente entre búsquedas.

**¿Qué ocurre con cero o un vuelo?**
Merge Sort hace cero comparaciones. Con traza emite `input` y `result`, sin
divisiones ni combinaciones. En equilibrio, el vuelo único tiene componentes y
puntuación cero; para vacío la normalización del servicio es `null`.

**¿El navegador vuelve a ejecutar Merge Sort?**
No. PHP emite los eventos y Blade los presenta. El JavaScript educativo cambia
paneles y botones. El JavaScript de comparación calcula diferencias entre
seleccionados, sin recalcular el ranking ni la normalización.

**¿Qué pruebas reales podemos citar?**
Las registradas en B5, con sus comandos y límites. El renderizado de Blade y el
adaptador del DOM de Node son pruebas automatizadas, no revisión visual. Si se
graba una comprobación en navegador o se ejecuta A6, citar esa evidencia real
por separado y con su fecha.

## 5. Preparación del video y datos pendientes

Lista propuesta, todavía por confirmar por el equipo:

- [ ] Confirmar duración, formato y requisitos exactos de la evaluación.
- [ ] Preparar la aplicación y la base local conforme a las instrucciones de uso
  de José; comprobar que **Cargar ejemplo** devuelve datos y anotar su cantidad real.
- [ ] Ensayar la secuencia completa, los pesos 0/50/100 y un caso con empate;
  comprobar audio, legibilidad de texto y captura de pantalla.
- [ ] Revisar el estado de A6 con José y tener disponibles sus comandos/resultados
  reales, junto con el informe B5. No presentar archivos locales sin seguimiento
  como si ya estuvieran incluidos en una revisión compartida.
- [ ] Repartir la narración y practicar preguntas cruzadas: José explica estabilidad
  y normalización; Anderson explica filtros, datos y recorrido de integración.
- [ ] Grabar, revisar reproducción y permisos de acceso, y completar el enlace real.

| Dato | Estado |
| --- | --- |
| Duración y formato exigidos por la evaluación | Pendiente de confirmar. Los diez minutos del guion son orientativos. |
| Ensayo en navegador y datos disponibles para grabación | Pendientes de realizar y registrar. B6 solo leyó el código y comprobó A/B/C por CLI. |
| Evidencia final de A6 | Pendiente de incorporar por José en sus documentos, cuando corresponda. |
| Fecha y duración de la grabación final | Pendientes. |
| Enlace real al video | **Pendiente: pegar aquí el enlace después de grabar y comprobar el acceso.** |

Este guion no asigna aportes adicionales, no documenta commits ni afirma que exista
un video. La explicación técnica ampliada está en [algoritmo.md](algoritmo.md).
