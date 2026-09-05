# AeroCompare

Proyecto académico de Análisis de Algoritmos de José (integrante A) y Anderson (integrante B). Un vuelo barato puede tardar demasiado y uno rápido puede costar más: AeroCompare permite buscar una ruta y fecha, ordenar las opciones según precio, duración o equilibrio, y comparar sus diferencias.

**Vuelos y precios de demostración.** Los datos, aerolíneas e impuestos son simulados. No hay APIs externas, autenticación, reservas ni pagos. Se contempla una sola vía, un pasajero y moneda COP. La interfaz usa Laravel, Blade, Tailwind y JavaScript sencillo; SQLite facilita la demostración.

## Requisitos comprobados

- Git y Composer 2. El entorno de validación usa Composer 2.10.2.
- PHP compatible con `composer.lock`: aunque `composer.json` declara `^8.3`, las versiones bloqueadas de Symfony requieren **PHP 8.4.1 o superior dentro de PHP 8**. Se ejecutó con PHP CLI **8.5.8**. Usar `composer install` para conservar esas versiones.
- Extensiones utilizadas por las dependencias y pruebas: `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `Phar`, `session`, `tokenizer`, `xml` y `xmlwriter`. Composer admite los polyfills incluidos para algunas de ellas. Para la base de datos se necesita **PDO y pdo_sqlite**; `sqlite3` también está disponible en el entorno revisado.
- Node.js y npm para Vite/Tailwind. Entorno comprobado: **Node 24.19.0 y npm 11.17.0**. `package.json` declara Vite `^8.0.0` y Tailwind `^4.0.0`; no hay `package-lock.json` en este checkout.
- Permisos de escritura en `storage`, `bootstrap/cache` y `database`.

Comprueba las herramientas en la terminal que utilizarás para ejecutar Laravel:

```sh
php -v
php --ini
php -m
composer -V
node -v
npm -v
```

En Windows con varias instalaciones de PHP, revisa `where.exe php`; en macOS, `which php`. El PHP de MAMP u otro servidor puede ser distinto del de la terminal. Habilita las extensiones en el `php.ini` que muestra `php --ini`.

## Instalación desde un clon

### Windows — PowerShell

Con Git, PHP, Composer y Node ya disponibles en `PATH`:

```powershell
git clone https://github.com/AndersonArleyC/analisis-algoritmos-proyecto.git
Set-Location analisis-algoritmos-proyecto
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
```

### macOS — Terminal

Con las mismas herramientas disponibles:

```sh
git clone https://github.com/AndersonArleyC/analisis-algoritmos-proyecto.git
cd analisis-algoritmos-proyecto
composer install
[ -f .env ] || cp .env.example .env
[ -f database/database.sqlite ] || touch database/database.sqlite
```

### Configuración común

Edita **tu `.env` local** con estos valores, conservando las demás opciones de `.env.example`:

```dotenv
APP_NAME=AeroCompare
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
```

Deja `DB_DATABASE` comentado o sin definir para usar `database/database.sqlite`, según `config/database.php`. Si necesitas otra ubicación, usa una ruta absoluta entre comillas; en Windows puedes emplear barras `/`, por ejemplo `C:/proyectos/AeroCompare/database/database.sqlite`. No definas `DB_URL` para esta instalación SQLite.

La zona horaria está fijada en `config/app.php` como `America/Bogota`. Los horarios se almacenan como horas locales de Bogotá y se entregan al ranking con offset `-05:00`. Los textos de la interfaz están en español.

Solo en la primera instalación, si `APP_KEY` está vacío:

```sh
php artisan key:generate
```

Después, en ambos sistemas:

```sh
php artisan config:clear
composer check-platform-reqs
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abre `http://127.0.0.1:8000`. No necesitas arrancar MySQL ni un trabajador de colas para este flujo. Si utilizas MAMP, la raíz pública del sitio debe apuntar a `public`, no a la raíz del repositorio.

El seeder crea **32 vuelos**, tres rutas y tres fechas, con ocho opciones por búsqueda disponible. Se puede repetir `php artisan db:seed`: actualiza por código y fecha/hora de salida sin duplicar vuelos ni cambiar sus IDs. No borra datos ajenos al conjunto de demostración.

No subas `.env`, la base SQLite, `vendor` ni `node_modules`. Cada integrante mantiene su configuración local. Para incorporar migraciones nuevas usa `php artisan migrate`; no es necesario borrar la base de datos.

## Vite durante el desarrollo

Mantén dos terminales abiertas:

```sh
# Terminal 1
php artisan serve --host=127.0.0.1 --port=8000
```

```sh
# Terminal 2
npm run dev
```

Vite actualiza los recursos mientras editas. Para una demostración sin el servidor de Vite, ejecuta `npm run build` y utiliza los archivos de `public/build`. El buscador carga su estilo y comportamiento desde `public/flights/`, también cuando Vite no está ejecutándose. El algoritmo carga sus propios recursos locales. No se añadieron dependencias de diseño.

## Uso y búsqueda de ejemplo

1. Pulsa **Cargar ejemplo** o elige Bogotá (`BOG`), Medellín (`MDE`) y fecha `2026-10-15` en los selectores (15 de octubre de 2026). Deben aparecer **ocho vuelos**. Las fechas de los seeders son fijas, no relativas al día actual.
2. Selecciona **Más barato**, **Más rápido** o **Mejor equilibrio**. El servidor filtra primero por ruta y día de salida y después llama a `FlightRankingService`.
3. Ajusta la importancia del precio entre 0 y 100. El tiempo es su complemento; el valor inicial es 50/50. Los pesos solo afectan Mejor equilibrio. Mover el control no cambia resultados: pulsa **Aplicar preferencias**. El resumen indica las preferencias realmente aplicadas.
4. Consulta en las tarjetas los horarios, duración total con escalas, equipaje y precio con impuestos simulados. Una llegada puede corresponder al día siguiente.
5. Marca **dos o tres vuelos** para compararlos. La tabla muestra la diferencia frente al menor precio y la menor duración de la selección, indicando todas las referencias empatadas. Puede desplazarse horizontalmente dentro de su contenedor en móvil. Quita una selección para habilitar otra o pulsa **Limpiar selección**. Aplicar una búsqueda o criterio nuevo limpia la comparación.
6. Abre la sección desplegable **Cómo funciona Merge Sort** y utiliza Siguiente, Anterior y Reiniciar. La demostración corresponde al criterio y pesos aplicados. Solo reproduce hasta los primeros ocho vuelos en su orden de entrada; el ranking procesa todos. Su contador es independiente del contador del ranking completo.

Los aeropuertos y días proceden de los vuelos almacenados. Elige origen, luego destino y después un día habilitado en el calendario o en **Fechas disponibles**. Los días muestran el precio mínimo cuando hay espacio; al elegirlos aparece un resumen con fecha, cantidad y precio. Origen y destino deben ser distintos. El servidor rechaza rutas y fechas no disponibles incluso si se manipula la URL. Las preferencias también se validan. Si no hay datos, se informa y se deshabilita la búsqueda.

Sin JavaScript, elige un origen y pulsa **Actualizar destinos y fechas**; elige un destino y vuelve a pulsar Actualizar; selecciona una fecha en la lista y pulsa **Buscar vuelos**. Puedes aplicar preferencias y consultar las tarjetas y los pasos del algoritmo. El calendario interactivo y la comparación requieren JavaScript.

## Algoritmo y equilibrio

Merge Sort se implementa manualmente en PHP, separado del controlador. Divide recursivamente y combina sublistas ordenadas. Conserva el orden de entrada cuando las claves empatan. Su complejidad temporal es O(n log n) y el espacio auxiliar convencional sin traza es O(n); guardar eventos requiere memoria adicional. JavaScript no ordena los vuelos ni calcula puntuaciones.

Para cada búsqueda completa:

```text
precio_normalizado = (precio - precio_minimo) / (precio_maximo - precio_minimo)
tiempo_normalizado = (duracion - duracion_minima) / (duracion_maxima - duracion_minima)
puntuacion = peso_precio * precio_normalizado + (1 - peso_precio) * tiempo_normalizado
```

Si un máximo es igual al mínimo, ese componente vale cero. Menor puntuación significa mejor equilibrio según los pesos. Es una regla propia del proyecto, **no un porcentaje de calidad**. Depende de los vuelos encontrados; comparar una selección no vuelve a normalizarlos. Se redondea solo para mostrar.

Ejemplo A/B/C incluido en la búsqueda:

| Vuelo | Precio COP | Duración | Puntuación 50/50 mostrada |
| --- | ---: | ---: | ---: |
| DEMO-A | $200.000 | 720 min | 0,500000 |
| DEMO-B | $500.000 | 120 min | 0,500000 |
| DEMO-C | $280.000 | 180 min | 0,183333 |

C queda antes que A y B por equilibrio. Las otras opciones de esta búsqueda conservan los extremos usados en este cálculo. La elección depende de las necesidades del viajero.

## Pruebas y validación

```sh
php artisan test
php artisan test --testsuite=Feature
php artisan test --filter=FlightSearchTest
php artisan test --filter=FlightRankingIntegrationTest
```

`phpunit.xml` configura SQLite en memoria para las pruebas; estas no necesitan borrar la base local. Las pruebas funcionales cubren filtros, límites del día en Bogotá, validaciones, criterios, pesos, A/B/C, resultado vacío y demostración limitada con normalización global.

Se añadió una prueba de navegador sin dependencias npm adicionales: `tests/Browser/flight-flow.mjs`. Necesita Node 24, la aplicación en ejecución con el seeder y Chrome con depuración remota en un perfil temporal.

macOS, en otra terminal:

```sh
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless --remote-debugging-port=9227 --user-data-dir="/tmp/aerocompare-browser-tests"
```

Windows PowerShell (ajusta la ubicación si Chrome está instalado en otro directorio):

```powershell
& "$env:ProgramFiles/Google/Chrome/Application/chrome.exe" --headless --remote-debugging-port=9227 --user-data-dir="$env:TEMP/aerocompare-browser-tests"
```

Después, en la raíz del proyecto:

```sh
node tests/Browser/flight-flow.mjs
```

El script usa un viewport exacto de **375 px** y comprueba selección de dos/tres vuelos, bloqueo del cuarto, diferencias, empates, foco al quitar, desplazamiento de la tabla, pesos complementarios, aplicación explícita, navegación de pasos y limpieza de selección. Puedes pasar otra URL completa como primer argumento si el servidor usa otro puerto. Cierra el Chrome temporal al terminar.

Validación realizada en A6: **150 pruebas PHP y 2708 aserciones aprobadas**; `composer check-platform-reqs` correcto; prueba de navegador aprobada a 375 px. Se inspeccionaron capturas de formulario/preferencias, tarjetas, comparación y demostración: ancho de página 375 px, sin desbordamiento horizontal. El navegador usó HTML recién renderizado por Laravel con SQLite en memoria y recursos locales del algoritmo, tanto con los estilos de respaldo como con Tailwind compilado.

También se ejecutaron `npm install` y `npm run build` en una copia temporal con el mismo `package.json`, configuración Vite y recursos. La compilación terminó correctamente con Vite 8.2.2, sin cambiar dependencias del repositorio. Emitió un aviso no bloqueante sobre `fontaine`, una optimización opcional de fuentes; no se añadió ese paquete. La configuración existente descarga Instrument Sans desde Bunny durante la compilación, por lo que ese paso necesita conexión a Internet.

Pendientes reales: ejecutar la instalación completa en un equipo Windows y agregar el enlace real del video. Las instrucciones de PowerShell fueron revisadas, pero no ejecutadas en Windows durante A6. B5/B6 siguen bajo responsabilidad de Anderson.

## Documentación y responsabilidades

- [Plan y división del proyecto](docs/plan-proyecto.md).
- [Contrato de integración](docs/contrato-integracion.md).
- [Disponibilidad, calendario y revisión visual](docs/disponibilidad-buscador.md).
- [Componente de demostración B4](docs/componente-demo-algoritmo.md).
- [Validación de algoritmos](docs/validacion-algoritmos.md), a cargo de Anderson.

`docs/algoritmo.md` y `docs/sustentacion.md` corresponden a B6 y aún no están presentes en este checkout. No se crean enlaces a archivos inexistentes.

| Integrante | Responsabilidades asignadas en el plan |
| --- | --- |
| José — A | A1 base y contrato; A2 modelo y datos; A3 buscador; A4 tarjetas y comparación; A5 integración y preferencias; A6 validación funcional, instalación y uso. |
| Anderson — B | B1 Merge Sort; B2 puntuación y criterios; B3 traza; B4 vista educativa; B5 pruebas unitarias; B6 documentación del algoritmo y sustentación. |

Esta distribución describe responsabilidades, no acredita commits ni da por terminadas tareas ajenas. Ambos integrantes deben poder explicar la solución completa.

**Video de sustentación: pendiente de agregar el enlace real.**
