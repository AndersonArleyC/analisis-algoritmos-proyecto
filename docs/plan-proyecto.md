# AeroCompare — Plan del proyecto

## Uso por ambos integrantes y por Codex

José es el integrante A y Anderson es el integrante B. Repositorio: `AndersonArleyC/analisis-algoritmos-proyecto`.
Ejecutar únicamente la tarea indicada en cada sesión. La especificación siguiente describe el alcance final, no autoriza implementarlo todo.
El [contrato de integración](contrato-integracion.md) define las interfaces compartidas; cualquier cambio debe quedar documentado y coordinado entre ambos integrantes.

## Cierre documental de A1

- Base existente conservada: Laravel instalado y bloqueado en `composer.lock` en 13.30.1; `composer.json` requiere PHP `^8.3` y Laravel `^13.17`.
- Entorno inspeccionado: PHP CLI 8.5.8, Composer 2.10.2 y extensiones `pdo_sqlite` y `sqlite3` disponibles.
- `.env.example` y el `.env` local seleccionan SQLite. `config/database.php` usa `database/database.sqlite` cuando no se establece `DB_DATABASE`. El archivo local existe y `php artisan migrate:status` confirmó las tres migraciones iniciales aplicadas.
- `.env` y los archivos SQLite están ignorados por Git. Cada integrante conserva sus archivos locales; no subirlos.
- Tailwind CSS `^4.0.0` y Vite `^8.0.0` ya están declarados. No se cambiaron dependencias, configuración, Git ni archivos de aplicación.
- La zona horaria base sigue en UTC y el idioma predeterminado en inglés. A2/A3/A5 deberán respetar `America/Bogota` para datos, filtros y presentación; A3/A4/B4 aportarán los textos españoles. No considerar estos requisitos ya implementados.
- Solo se agregan este plan y el contrato. A2–A6 y B1–B6 quedan pendientes. Las verificaciones de A1 no son pruebas de funcionalidades futuras.
- Anderson puede iniciar B1 con listas simples y comparadores, sin depender del modelo Flight ni de la base de datos. La traza se implementará en B3 conforme al formato reservado en el contrato.

## Especificación proporcionada por José

Se conserva a continuación el encargo completo, incluida la división de tareas y el alcance de la sesión inicial. En futuras sesiones prevalece la tarea que se solicite explícitamente.

Desarrolla un proyecto académico en Laravel llamado AeroCompare: un comparador de vuelos con datos simulados que permita encontrar el vuelo más barato, el más rápido y el más equilibrado entre precio y duración.

CONTEXTO ACADÉMICO

Este proyecto corresponde a un examen de Análisis de Algoritmos. Debe evidenciar claramente la implementación manual de Merge Sort, su funcionamiento y su aplicación a un problema concreto.

Somos dos integrantes y la evaluación es individual según los commits, la sustentación y el conocimiento del proyecto. Trabaja por tareas y respeta la división de responsabilidades indicada al final.

No desarrolles todo de una vez. En la primera ejecución, inspecciona el repositorio y realiza únicamente la tarea A1. En ejecuciones posteriores, realiza solo la tarea que indiquemos.

TECNOLOGÍAS Y ALCANCE

* Laravel y PHP, usando una versión compatible con el entorno disponible. Si existe un proyecto, respeta sus versiones.
* Blade, Tailwind CSS y JavaScript sencillo para interacciones.
* SQLite para facilitar la instalación y demostración.
* Evita incorporar frameworks adicionales si no son necesarios.
* Interfaz completamente en español.
* Datos simulados; indicar claramente “Vuelos y precios de demostración”.
* Sin APIs externas, pagos, reservas ni autenticación.
* No sobrescribas archivos existentes sin inspeccionarlos.
* Prioriza código legible, adecuado para que dos estudiantes puedan explicarlo.

PROBLEMA

Un viajero busca vuelos para una ruta y fecha. El vuelo más barato puede tener una duración excesiva, mientras que el más rápido puede ser costoso. La aplicación permite comparar opciones y ordenarlas según las preferencias del usuario.

FUNCIONALIDADES

1. Buscador:

   * Origen.
   * Destino.
   * Fecha de salida.
   * Validación para impedir origen y destino iguales.
   * Una sola vía, un pasajero y moneda COP.
   * Primero filtrar por ruta y fecha; después ejecutar el algoritmo de ordenamiento.

2. Resultados:

   * Aerolínea y código de vuelo.
   * Origen y destino.
   * Fecha y hora de salida y llegada.
   * Duración total, incluyendo escalas.
   * Número de escalas.
   * Equipaje incluido.
   * Precio total por pasajero, incluidos impuestos simulados.
   * Estados claros cuando no haya resultados.

3. Tres criterios de ordenamiento:

   * Más barato: precio ascendente.
   * Más rápido: duración ascendente.
   * Mejor equilibrio: puntuación ponderada ascendente.

4. Preferencias:

   * Control de 0 a 100 para indicar la importancia del precio.
   * La importancia del tiempo es el complemento.
   * Valor inicial: 50 % precio y 50 % tiempo.
   * Mostrar ambos porcentajes y explicar el efecto.
   * Permitir recalcular con un botón, sin recargar ante cada movimiento del control.

5. Comparación:

   * Seleccionar dos o tres vuelos.
   * Mostrar una tabla con precio, duración, escalas y equipaje.
   * Mostrar diferencias de precio y duración.
   * No afirmar que un vuelo es objetivamente el mejor: depende del criterio.

ALGORITMO Y PUNTUACIÓN

Implementar Merge Sort manualmente en PHP.

No usar sort, usort, asort, Collection::sortBy, ORDER BY ni equivalentes para resolver el ordenamiento de vuelos. Los filtros de búsqueda sí pueden ejecutarse en la base de datos.

Separar el algoritmo de los controladores.

Para el criterio de equilibrio:

precio_normalizado =
(precio - precio_minimo) / (precio_maximo - precio_minimo)

tiempo_normalizado =
(duracion - duracion_minima) / (duracion_maxima - duracion_minima)

puntuacion =
puntuacion = (peso_precio * precio_normalizado) + (peso_tiempo * tiempo_normalizado)

Reglas:

* Usar duración en minutos y precio como entero en pesos.
* Los pesos suman 1.
* Calcular mínimos y máximos sobre todos los resultados de la búsqueda actual, antes de ordenarlos.
* No recalcular la normalización sobre los vuelos seleccionados para comparar.
* Si el máximo y mínimo son iguales, ese componente normalizado vale 0.
* Si no hay resultados, devolver una lista vacía.
* Menor puntuación significa mejor equilibrio según los pesos.
* No redondear antes de comparar; redondear únicamente para mostrar.
* Explicar que la puntuación depende de los vuelos encontrados y es una regla propia del proyecto.

Merge Sort debe ser estable: cuando dos elementos tengan la misma clave de ordenamiento, conservar su orden de entrada.

VISUALIZACIÓN EDUCATIVA

Agregar una sección “Cómo funciona Merge Sort” con:

* Lista de entrada.
* Divisiones recursivas.
* Comparaciones durante las combinaciones.
* Sublistas combinadas.
* Resultado final.
* Contador de comparaciones de claves.
* Botones anterior, siguiente y reiniciar.
* Clave usada: precio, duración o puntuación.

La traza debe provenir de la ejecución real del algoritmo en PHP, no de una animación ficticia ni de otro ordenamiento en JavaScript.

Limitar la demostración paso a paso a un máximo de 8 vuelos para que sea legible. Indicar qué subconjunto se está demostrando. El ordenamiento normal debe procesar todos los resultados.

Explicar:

* División y combinación.
* Complejidad temporal O(n log n).
* Espacio auxiliar O(n) de la implementación convencional sin almacenar la traza.
* El registro de pasos consume memoria adicional.

MODELO DE DATOS

Modelo Flight con:

* id
* airline
* flight_code
* origin
* destination
* departure_at
* arrival_at
* duration_minutes
* stops
* baggage_description
* total_price_cop

Usar rutas nacionales y zona horaria America/Bogota. La llegada debe ser posterior a la salida y coherente con la duración total, incluso cuando cambie de día.

Crear al menos 30 vuelos simulados, varias rutas y fechas. Incluir al menos una ruta y fecha con 8 o más opciones.

Agregar un conjunto demostrativo para la misma ruta y fecha:

* Vuelo A: $200.000 y 720 minutos.
* Vuelo B: $500.000 y 120 minutos.
* Vuelo C: $280.000 y 180 minutos.

Con pesos 50/50, C debe obtener mejor puntuación que A y B.

Facilitar la elección de una búsqueda con datos mediante un botón “Cargar ejemplo”.

ESTRUCTURA Y CONTRATO ENTRE INTEGRANTES

Usar como base:

* app/Models/Flight.php
* app/Http/Controllers/FlightSearchController.php
* app/Services/FlightRankingService.php
* app/Services/Algorithms/MergeSort.php
* resources/views/flights/index.blade.php
* resources/views/components/flights/
* resources/views/algorithm/
* tests/Unit/
* tests/Feature/

Antes de implementar las tareas posteriores, dejar definido en docs/contrato-integracion.md:

* Campos que recibe el servicio de ordenamiento.
* Criterios permitidos: price, duration y balanced.
* Parámetro priceWeight entre 0 y 1.
* Estructura del resultado: vuelos ordenados, puntuaciones cuando correspondan y cantidad de comparaciones.
* Estructura de los eventos de la traza.
* Ejemplo de entrada y salida.

No pasar modelos Eloquent al algoritmo genérico: transformar los resultados en arrays o DTO sencillos en la capa de integración.

DISEÑO

Interfaz moderna, clara y responsive:

* Fondo claro y acentos azules.
* Buscador destacado.
* Tarjetas de vuelos fáciles de comparar.
* Pestañas o botones para los tres criterios.
* Etiquetas de más barato y más rápido que contemplen empates.
* Puntuación acompañada de una explicación; no presentarla como porcentaje de calidad.
* Tabla de comparación usable en móvil.
* Vista del algoritmo con textos legibles y controles accesibles.
* Sin copiar marcas ni usar logotipos externos.

PRUEBAS IMPORTANTES

Comprobar:

* Merge Sort con listas vacías, un elemento, duplicados y entradas desordenadas.
* Estabilidad cuando las claves empatan.
* Orden ascendente por precio y duración.
* Normalización cuando todos los valores son iguales.
* Pesos 0, 0.5 y 1.
* Ejemplo A/B/C.
* Filtros por ruta y fecha.
* Validación de entradas.
* Coherencia entre la traza y el resultado del subconjunto demostrado.

DOCUMENTACIÓN

README.md con:

* Problema y objetivo.
* Alcance y carácter simulado de los datos.
* Requisitos e instalación.
* Migraciones, seeders y ejecución.
* Cómo cargar una búsqueda de ejemplo.
* Explicación de Merge Sort.
* Fórmula de equilibrio y limitaciones.
* Ejemplo de resultados.
* Comandos de pruebas.
* Autores y responsabilidades.
* Campo pendiente para agregar el enlace real al video.

Agregar:

* docs/algoritmo.md
* docs/contrato-integracion.md
* docs/sustentacion.md

No inventar nombres de autores, enlaces, resultados de pruebas ni aportes.

DIVISIÓN DEL TRABAJO

Integrante A:
A1. Base Laravel, configuración SQLite y contrato de integración.
A2. Modelo Flight, migración y seeders.
A3. Buscador, validaciones y filtros.
A4. Tarjetas de resultados y comparación de dos o tres vuelos.
A5. Integración del buscador con el servicio de ranking y controles de preferencias.
A6. Pruebas funcionales y documentación de instalación y uso.

Integrante B:
B1. Merge Sort genérico, estable y manual.
B2. Servicio de puntuación y criterios de ordenamiento.
B3. Registro real de pasos y comparaciones.
B4. Interfaz educativa del algoritmo.
B5. Pruebas unitarias del algoritmo y puntuación.
B6. Documentación del algoritmo y guion base de sustentación.

FORMA DE TRABAJAR

* Implementa únicamente la tarea solicitada.
* No suplantes la autoría de otro integrante.
* No crees commits ni hagas push automáticamente.
* Al terminar, informa archivos modificados, funcionamiento, validación realizada y mensaje de commit sugerido.
* Si falta una dependencia de otra tarea, explica cuál es. No implementes silenciosamente el trabajo del otro.
* Usa las convenciones y el contrato acordados.
* Ambos integrantes deben poder explicar la solución completa.

ESTADO ACTUAL Y TAREA DE ESTA SESIÓN

Soy José, integrante A. Mi compañero Anderson es el integrante B.

El repositorio es AndersonArleyC/analisis-algoritmos-proyecto. Laravel ya está instalado y funciona en ambos computadores. El contrato de integración todavía no existe.

En esta sesión, realiza únicamente lo pendiente de A1:

* Inspecciona la base existente y conserva sus versiones y configuración.
* No reinstales Laravel ni reinicialices Git.
* Revisa la configuración de SQLite sin sobrescribir el .env local.
* Crea docs/contrato-integracion.md con las interfaces, formatos de datos, resultados y eventos de traza que compartiremos.
* Guarda esta especificación general y la división de tareas en docs/plan-proyecto.md para que ambos podamos consultarlas desde Codex.
* No implementes todavía A2 ni ninguna tarea de Anderson.
* No hagas commits ni push.

Al terminar, explica qué se definió y qué archivos debo subir para que Anderson pueda empezar B1.
