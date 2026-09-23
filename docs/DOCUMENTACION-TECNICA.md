# Documentación Técnica

## Despensa Orders

## 1. Propósito Y Alcance

Despensa Orders es una aplicación web interna para administrar pedidos de compra de alimentos y productos de despensa. El flujo principal recibe un archivo Excel con códigos y cantidades, consulta un catálogo local, calcula valores antes del IVA e IVA por categoría, y persiste el pedido con sus líneas.

La aplicación también incluye:

- Autenticación basada en sesión.
- Administración de categorías.
- Administración e importación de productos.
- Historial paginado de pedidos.
- Exportación individual de pedidos a XLSX y PDF.
- Reportes consolidados por periodo, sede, categoría y producto.
- Exportación de reportes a XLSX y PDF.

El código fuente está en `src/` y se monta como `/var/www` en el contenedor PHP durante el desarrollo.

## 2. Tecnologías

| Capa | Tecnología | Versión declarada |
|---|---|---|
| Backend | Laravel | `^13.8` |
| Lenguaje | PHP | `^8.3` |
| ORM | Eloquent | Incluido en Laravel |
| Frontend | Vue | `^3.4` |
| Integración SPA | Inertia.js | `^2.0` |
| Estilos | Tailwind CSS | `^4.0` |
| Bundler | Vite | `^8.0` |
| Enrutado frontend | Ziggy | `^2.0` |
| Base de datos local | MySQL | `8.4` en Docker |
| Base de datos de pruebas | SQLite | Base en memoria |
| Lectura y escritura Excel | PhpSpreadsheet | `^5.8` |
| Generación PDF | DomPDF | `^3.1` |
| Servidor web | Nginx | Imagen Alpine |
| Contenedores | Docker Compose | Desarrollo y producción |

## 3. Arquitectura

### 3.1 Flujo de ejecución

```text
Navegador
    |
    v
Nginx :8080  -- archivos estáticos y proxy FastCGI -->  PHP-FPM :9000
                                                        |
                                                        +--> Laravel / Inertia
                                                        +--> MySQL :3306
                                                        +--> PhpSpreadsheet
                                                        +--> DomPDF
```

En desarrollo, Vite sirve los recursos con HMR en el puerto `5173`. En producción los recursos se compilan durante la imagen Docker y se sirven desde `public/build`.

### 3.2 Backend

- `routes/web.php` define Dashboard, catálogo, pedidos, reportes y perfil.
- `routes/auth.php` define registro, inicio de sesión, recuperación de contraseña, confirmación, verificación y cierre de sesión.
- Los controladores reciben las solicitudes, validan permisos y entregan respuestas Inertia o descargas.
- Los servicios contienen la lectura de Excel, resolución de sedes, generación de pedidos y exportaciones.
- Los modelos Eloquent representan usuarios, categorías, productos, pedidos y líneas.
- Las migraciones definen el esquema y las restricciones de integridad.

### 3.3 Frontend

La entrada es `resources/js/app.js`. Inertia resuelve automáticamente las páginas en `resources/js/Pages/{Nombre}.vue`. El layout autenticado centraliza:

- Menú lateral de navegación.
- Menú móvil.
- Usuario actual.
- Enlaces al perfil y cierre de sesión.
- Mensajes flash.

Páginas principales:

| Página | Archivo | Función |
|---|---|---|
| Dashboard | `Pages/Dashboard.vue` | Estadísticas y pedidos recientes. |
| Categorías | `Pages/Categories/Index.vue` | Listado y acciones del catálogo de categorías. |
| Formulario de categoría | `Pages/Categories/Form.vue` | Crear o editar categorías. |
| Productos | `Pages/Items/Index.vue` | Listado, búsqueda, filtro y acciones. |
| Formulario de producto | `Pages/Items/Form.vue` | Crear o editar productos. |
| Importación de productos | `Pages/Items/Import.vue` | Carga masiva de catálogo. |
| Pedidos | `Pages/Orders/Index.vue` | Historial paginado. |
| Nuevo pedido | `Pages/Orders/Create.vue` | Datos generales y carga inicial. |
| Vista previa | `Pages/Orders/Preview.vue` | Revisión, productos manuales y confirmación. |
| Detalle de pedido | `Pages/Orders/Show.vue` | Detalle, totales y descargas. |
| Reportes | `Pages/Reports/Index.vue` | Filtros, resumen y exportación. |

## 4. Estructura Del Proyecto

```text
.
├── docker/
│   ├── mysql/                 # Inicialización mínima de MySQL
│   ├── nginx/                 # Configuraciones de desarrollo y producción
│   └── php/                   # Dockerfiles, PHP y entrypoint de producción
├── docs/                      # Documentación funcional y técnica
├── src/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/   # Controladores web y autenticación
│   │   │   ├── Middleware/    # Inertia y acceso restringido
│   │   │   └── Requests/      # Validaciones reutilizables
│   │   ├── Models/            # Modelos Eloquent
│   │   ├── Providers/         # Configuración del framework
│   │   └── Services/          # Lógica de negocio y exportadores
│   ├── bootstrap/              # Arranque y registro de middleware
│   ├── config/                 # Configuración Laravel
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/                 # Front controller, logo y build frontend
│   ├── resources/
│   │   ├── css/                # Tailwind y estilos propios
│   │   ├── js/                 # Vue + Inertia
│   │   └── views/pdf/           # Plantillas DomPDF
│   ├── routes/
│   └── tests/
├── docker-compose.yml          # Entorno local
├── docker-compose.prod.yml     # Imagen y servicios de producción
├── Makefile                    # Atajos operativos
└── README.md
```

## 5. Modelo De Datos

### 5.1 Entidades

```text
Category 1 ──── N Item
Item     1 ──── N OrderItem N ──── 1 Order N ──── 1 User
```

Una categoría tiene productos. Un pedido tiene muchas líneas. Cada línea apunta a un producto. Un pedido puede apuntar al usuario que lo creó, pero el usuario es nullable para conservar pedidos si se elimina la cuenta.

### 5.2 Tablas de negocio

#### `categories`

| Campo | Tipo / regla | Descripción |
|---|---|---|
| `id` | Big integer, PK | Identificador interno. |
| `nombre` | String 100 | Nombre de la categoría. |
| `orden` | Integer, default 0 | Orden de presentación y agrupación. |
| `aplica_iva` | Boolean, default true | Define si sus líneas forman base de IVA. |
| `created_at`, `updated_at` | Timestamps | Auditoría técnica. |

#### `items`

| Campo | Tipo / regla | Descripción |
|---|---|---|
| `id` | Big integer, PK | Identificador interno. |
| `codigo_item` | String 20, único en DB | Código usado en archivos y pedidos. |
| `descripcion` | String | Descripción del producto. |
| `precio_unidad` | Decimal 12,2 | Precio por unidad. |
| `presentacion` | String, no nullable en DB | Empaque o presentación. |
| `precio_presentacion` | Decimal 12,2, no nullable en DB | Precio usado en la línea del pedido. |
| `categoria_id` | FK a `categories` | Categoría del producto. |
| `created_at`, `updated_at` | Timestamps | Auditoría técnica. |

#### `orders`

| Campo | Tipo / regla | Descripción |
|---|---|---|
| `id` | Big integer, PK | Identificador interno. |
| `user_id` | FK nullable a `users` | Usuario que creó el pedido; se vuelve null si la cuenta se elimina. |
| `remision` | String 20 | Número de remisión. |
| `sede` | String 100 | Sede final del pedido. |
| `fecha` | Date | Fecha operativa del pedido. |
| `subtotal` | Decimal 14,2 | Suma de líneas antes de IVA. |
| `iva` | Decimal 14,2 | IVA calculado al guardar. |
| `total` | Decimal 14,2 | Subtotal más IVA. |
| `created_at`, `updated_at` | Timestamps | Auditoría técnica. |

Existe un índice único `orders_remision_sede_unique` sobre `remision` y `sede`.

#### `order_items`

| Campo | Tipo / regla | Descripción |
|---|---|---|
| `id` | Big integer, PK | Identificador interno. |
| `order_id` | FK a `orders`, cascade delete | Pedido al que pertenece. |
| `item_id` | FK a `items` | Producto. |
| `cantidad` | Decimal 12,2 | Cantidad pedida. |
| `precio_unitario` | Decimal 12,2 | Precio unitario copiado del catálogo. |
| `precio_presentacion` | Decimal 12,2 | Precio de presentación copiado del catálogo. |
| `total` | Decimal 14,2 | Cantidad multiplicada por precio de presentación. |
| `created_at`, `updated_at` | Timestamps | Auditoría técnica. |

### 5.3 Tablas de infraestructura

Laravel también crea `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs` según las migraciones del proyecto. Sesiones, caché y cola están configuradas para usar infraestructura de base de datos en el entorno normal.

## 6. Reglas De Negocio

### 6.1 Cálculo De Una Línea

```text
total de línea = cantidad × precio_presentacion
```

El `precio_unidad` se muestra y se conserva, pero el total de la línea usa `precio_presentacion`.

### 6.2 Cálculo Del Pedido

```text
subtotal = suma(total de todas las líneas)
base IVA = suma(total de líneas cuyas categorías tienen aplica_iva = true)
iva      = base IVA × 0.19
```

El IVA se calcula por categoría, no sobre todo el subtotal cuando hay categorías sin IVA. La pantalla y las exportaciones formatean los valores como pesos colombianos sin decimales visibles, mientras la base de datos conserva dos posiciones decimales.

### 6.3 Catálogo Y Códigos

- `codigo_item` es único en la base de datos.
- Un código que no exista en el catálogo se informa en `not_found` y no se agrega a las líneas.
- Las líneas repetidas del archivo se procesan como líneas separadas; el generador no consolida automáticamente cantidades por código.
- Los grupos de la vista previa se ordenan por `categories.orden`.

### 6.4 Remisión

La aplicación valida la duplicidad antes de la vista previa y antes de guardar. Además, la base de datos protege la regla con un índice único. Una misma remisión puede existir en dos sedes diferentes.

El chequeo se realiza contra la sede resuelta por `SedeCatalog`, no necesariamente contra la sede que el usuario seleccionó inicialmente.

## 7. Procesamiento De Pedidos

### 7.1 `ExcelParser`

`App\Services\ExcelParser`:

1. Carga el libro con PhpSpreadsheet.
2. Toma la hoja activa.
3. Detecta la primera fila con una columna de código reconocida.
4. Detecta cantidad y C.O. si existen.
5. Normaliza código, cantidad y centro de operaciones.
6. Ignora filas sin código válido o con cantidad menor o igual a cero.
7. Devuelve una `Collection` de arreglos con `codigo_item`, `cantidad` y, opcionalmente, `co`.

Los alias exactos y las reglas de normalización están en [Formato de archivos Excel](FORMATO-EXCEL.md).

### 7.2 `SedeCatalog`

`App\Services\SedeCatalog` contiene el catálogo fijo de sedes y centros de operaciones:

| C.O. | Sede |
|---|---|
| 001 | CIUDAD JARDIN |
| 002 | UNICENTRO |
| 004 | JARDIN PLAZA |
| 008 | PANCE |
| 011 | BOCHALEMA PLAZA |

También contiene las sedes que no usan C.O.: CHIPICHAPE, FLORA, GRANADA, LIMONAR, LLANOGRANDE y SAN FERNANDO.

`resolveForParsedItems()` aplica estas reglas:

- Sede sin C.O. o archivo sin C.O.: conserva la sede seleccionada.
- Un solo C.O.: resuelve la sede según el mapa configurado.
- Varios C.O. para una sede que sí usa C.O.: filtra las filas al C.O. seleccionado.
- C.O. no configurado: lanza `ValidationException`.
- Sin filas después del filtro: lanza `ValidationException`.

### 7.3 `OrderGenerator`

`App\Services\OrderGenerator`:

- Consulta los códigos únicos contra `items` con la categoría relacionada.
- Construye grupos por categoría.
- Calcula cada línea y subtotales.
- Obtiene el orden de categorías desde la base de datos.
- Calcula la base gravable y el IVA.
- Devuelve grupos, subtotal, IVA, total y códigos no encontrados.

El método `store()` recalcula los datos y guarda el pedido y sus líneas dentro de `DB::transaction()`. Si una escritura falla, la transacción se revierte.

## 8. Catálogo De Sedes

La sede no se almacena como una tabla; está codificada en `SedeCatalog`. Para agregar, renombrar o retirar sedes es necesario modificar el servicio y sus pruebas unitarias, reconstruir el frontend si corresponde y desplegar una nueva versión.

## 9. Rutas Web

Todas las rutas de negocio requieren autenticación, salvo las rutas públicas de inicio y autenticación.

### 9.1 Rutas públicas

| Método | Ruta | Nombre | Función |
|---|---|---|---|
| GET | `/` | — | Página de bienvenida. |
| GET | `/login` | `login` | Formulario de acceso. |
| POST | `/login` | — | Autenticar. |
| GET | `/register` | `register` | Formulario de registro. |
| POST | `/register` | — | Crear cuenta. |
| GET/POST | `/forgot-password` | `password.request`, `password.email` | Solicitar recuperación. |
| GET | `/reset-password/{token}` | `password.reset` | Mostrar formulario de nueva contraseña. |
| POST | `/reset-password` | `password.store` | Guardar contraseña nueva. |

### 9.2 Rutas autenticadas

| Método | Ruta | Nombre | Middleware adicional |
|---|---|---|---|
| GET | `/dashboard` | `dashboard` | `verified` |
| GET/PATCH/DELETE | `/profile` | `profile.edit`, `profile.update`, `profile.destroy` | — |
| GET | `/orders` | `orders.index` | — |
| GET | `/orders/create` | `orders.create` | — |
| POST | `/orders/preview` | `orders.preview` | — |
| POST | `/orders` | `orders.store` | — |
| GET | `/orders/{order}` | `orders.show` | — |
| GET | `/orders/{order}/xlsx` | `orders.export-xlsx` | — |
| GET | `/orders/{order}/pdf` | `orders.export-pdf` | — |
| DELETE | `/orders/{order}` | `orders.destroy` | Regla especial para ID 3 |

Las rutas auxiliares de autenticación autenticada incluyen `GET /verify-email`, `GET /verify-email/{id}/{hash}`, `POST /email/verification-notification`, `GET/POST /confirm-password`, `PUT /password` y `POST /logout`. Las dos primeras rutas de verificación usan firma y limitación de solicitudes.

### 9.3 Rutas restringidas

Las siguientes rutas están dentro del middleware `restricted.access`:

| Recurso | Rutas principales |
|---|---|
| Reportes | `/reports`, `/reports/xlsx`, `/reports/pdf` |
| Categorías | Resource routes `/categories/*` |
| Productos | `/items/*`, `/items/import` |

`DenyRestrictedAccessForUserThree` rechaza con HTTP 403 cuando el usuario autenticado tiene `id === 3`.

### 9.4 Observación Sobre Resource Routes

`Route::resource()` genera también rutas `show` para categorías y productos, pero `CategoryController` e `ItemController` no implementan un método `show()`. La interfaz no utiliza esas rutas. Si se requiere un detalle individual para estos recursos, debe implementarse explícitamente antes de exponer el enlace.

## 10. Validaciones

### 10.1 Pedido

`GenerateOrderRequest` valida:

| Campo | Regla |
|---|---|
| `archivo` | Obligatorio, archivo `.xlsx` o `.xls`. |
| `remision` | Obligatoria, string, máximo 20 caracteres. |
| `sede` | Obligatoria, string, máximo 100 caracteres. |
| `fecha` | Obligatoria, fecha válida. |
| `manual_items` | Opcional, arreglo. |
| `manual_items.*.codigo_item` | Obligatorio y existente en `items.codigo_item`. |
| `manual_items.*.cantidad` | Obligatoria, entero, mínimo 1. |

### 10.2 Producto

Crear y actualizar producto valida código, descripción, `precio_unidad` y categoría. Los precios deben ser numéricos y no negativos. La validación permite ciertos campos opcionales, pero el esquema de la tabla y el cálculo operativo hacen recomendable exigir presentación y precio de presentación desde el frontend o desde cualquier proceso de importación.

### 10.3 Reporte

Los filtros aceptan fecha inicial, fecha final, sede, categoría e IDs de productos. Cuando se envían parámetros de consulta, las dos fechas pasan a ser obligatorias. La fecha final debe ser mayor o igual a la inicial. Los IDs de categoría y producto deben existir.

## 11. Reportes Y Reglas Fiscales

`ReportController` consulta `order_items` y reconstruye los valores:

```text
subtotal de línea = order_items.total
iva de línea      = subtotal de línea × 0.19 si la categoría actual aplica IVA
total de línea    = subtotal de línea + iva de línea
```

El resultado incluye:

- Resumen global.
- Desglose por pedido.
- Desglose por producto.

El rango de fechas se aplica a `orders.fecha` con `whereBetween`, por lo que es inclusivo. Los filtros de sede, categoría y producto se combinan como condiciones adicionales.

El IVA de un reporte se determina con la configuración actual de la categoría relacionada. Si una categoría se modifica después de crear pedidos, el reporte puede no coincidir con el `iva` histórico almacenado en `orders`. Esta diferencia debe considerarse antes de cambiar la configuración fiscal de categorías.

## 12. Exportaciones

### Pedido individual

| Servicio | Formato | Contenido |
|---|---|---|
| `XlsxExporter` | XLSX | Hoja `PEDIDO`, datos generales, categorías, líneas, subtotales y totales. |
| `PdfExporter` | PDF | Vista `resources/views/pdf/pedido.blade.php`, carta horizontal, logo y detalle. |

La plantilla indica que los precios no incluyen IVA y muestra el IVA por separado en el bloque de totales.

### Reporte

| Servicio | Formato | Contenido |
|---|---|---|
| `ReportXlsxExporter` | XLSX | Hoja `REPORTE`, filtros, resumen, detalle por pedido y detalle por producto. |
| `ReportPdfExporter` | PDF | Vista `resources/views/pdf/reporte-pedidos.blade.php`, carta horizontal. |

Los exportadores generan archivos temporales y responden como descarga. Los nombres sanitizan la sede para el nombre de archivo.

## 13. Instalación Local

### 13.1 Requisitos

- Docker Desktop o Docker Engine con Compose.
- Make, si se desean usar los atajos del `Makefile`.
- Acceso al repositorio.
- Puertos disponibles `8080`, `8081`, `3307`, `5173` y `9000` según el servicio que se utilice.

### 13.2 Preparar El Entorno

Desde la raíz del proyecto, copie el archivo de ejemplo:

En PowerShell:

```powershell
Copy-Item src/.env.example src/.env
```

En Linux, WSL o Git Bash:

```bash
cp src/.env.example src/.env
```

Para Docker local, configure `src/.env` con al menos:

```dotenv
APP_NAME="Pedidos Despensa"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=despensa
DB_USERNAME=despensa
DB_PASSWORD=despensa

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

La contraseña y los nombres anteriores coinciden con el `docker-compose.yml` de desarrollo. En un equipo compartido deben reemplazarse por secretos apropiados.

### 13.3 Construir Y Arrancar

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php pnpm install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
docker compose exec php pnpm run build
```

La aplicación queda en `http://localhost:8080`. phpMyAdmin queda en `http://localhost:8081` con el host de base de datos `mysql` y las credenciales configuradas en Compose.

Si el proyecto ya tiene dependencias instaladas, los pasos de `composer install` y `pnpm install` pueden omitirse.

### 13.4 Usar El Makefile

| Comando | Equivalente / función |
|---|---|
| `make up` | `docker compose up -d` |
| `make down` | `docker compose down` |
| `make bash` | Shell Bash dentro del contenedor PHP. |
| `make migrate` | Ejecuta `php artisan migrate`. |
| `make seed` | Ejecuta `php artisan db:seed`. |
| `make test` | Ejecuta la suite PHPUnit mediante Artisan. |
| `make pnpm-dev` | Inicia Vite con HMR en el contenedor PHP. |
| `make pnpm-build` | Genera el build frontend de producción. |
| `make logs` | Sigue los logs de los servicios Docker. |

El `CategorySeeder` usa `create()` y no es idempotente. No ejecute `make seed` repetidamente en una base de datos con datos reales, porque duplicará las categorías iniciales.

### 13.5 Desarrollo Frontend

Para trabajar con recarga automática:

```bash
make up
make pnpm-dev
```

El servidor Vite escucha en `0.0.0.0:5173` y HMR utiliza `localhost`. La aplicación se sigue abriendo desde Nginx en el puerto `8080`.

## 14. Despliegue En Producción

El archivo `docker-compose.prod.yml` define:

- Nginx construido desde la etapa `nginx`.
- PHP-FPM construido desde la etapa `php`.
- MySQL 8.4 con volumen persistente.
- Migraciones automáticas al iniciar el contenedor PHP.
- Build frontend incluido en la imagen.
- Sin publicación directa de puertos al host; el servicio Nginx usa `expose: 80` y requiere un proxy o balanceador externo.

### 14.1 Variables obligatorias

El Compose de producción exige:

```dotenv
APP_KEY=<clave-generada>
APP_URL=https://dominio-de-la-aplicacion
```

También deben definirse credenciales seguras de MySQL y, según el entorno, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD` y `LOG_LEVEL`.

La aplicación debe ejecutarse con:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

### 14.2 Arranque

Ejemplo genérico:

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec php php artisan db:seed
```

El entrypoint `docker/php/entrypoint.prod.sh` ejecuta `php artisan migrate --force` antes de iniciar PHP-FPM. El seeder no se ejecuta automáticamente; debe ejecutarse solo cuando corresponda y revisando si la base ya contiene categorías.

### 14.3 HTTPS Y Proxy

`bootstrap/app.php` confía en las cabeceras `X-Forwarded-*`. `AppServiceProvider` fuerza HTTPS cuando el entorno es producción, `APP_URL` empieza por `https://` o `FORCE_HTTPS=true`. Configure correctamente el proxy externo y el dominio para que las URLs de paginación, enlaces y descargas utilicen HTTPS.

## 15. Configuración Importante

| Variable | Valor local típico | Efecto |
|---|---|---|
| `APP_URL` | `http://localhost:8080` | URL base para enlaces generados. |
| `APP_KEY` | Generada por Artisan | Cifrado de la aplicación. |
| `APP_DEBUG` | `true` local / `false` producción | Detalle de errores. |
| `DB_CONNECTION` | `mysql` en Docker | Driver de base de datos. |
| `DB_HOST` | `mysql` en Docker | Nombre del servicio de MySQL. |
| `SESSION_DRIVER` | `database` | Persistencia de sesiones. |
| `CACHE_STORE` | `database` | Persistencia de caché. |
| `QUEUE_CONNECTION` | `database` | Cola de trabajos. |
| `MAIL_MAILER` | `log` en ejemplo | Los correos se escriben en logs. |
| `APP_LOCALE` | `en` en el ejemplo | Locale de Laravel; la interfaz contiene textos en español. |
| Zona horaria | `UTC` fija en `config/app.php` | Zona horaria efectiva del framework; no existe una variable `APP_TIMEZONE` utilizada por este proyecto. |

El archivo `.env.example` proviene del esqueleto de Laravel y mantiene SQLite como valor de ejemplo. El Compose local también levanta MySQL y la configuración recomendada para trabajar con ese servicio es la mostrada en la sección de instalación. Si se elige SQLite deliberadamente, debe configurarse y mantenerse fuera del flujo MySQL de Compose.

## 16. Pruebas

La configuración de PHPUnit usa:

- `APP_ENV=testing`.
- SQLite `:memory:`.
- Caché en array.
- Sesión en array.
- Cola síncrona.
- Mailer array.

Ejecutar la suite:

```bash
make test
```

O directamente dentro del contenedor:

```bash
docker compose exec php php artisan test
```

Cobertura funcional existente:

| Suite | Verifica |
|---|---|
| `Unit/ExcelParserTest.php` | XLSX, XLS, encabezados, cantidades y C.O. |
| `Unit/SedeCatalogTest.php` | Resolución, ajuste y filtrado de sedes. |
| `Feature/CatalogAccessTest.php` | Restricciones del usuario con ID 3. |
| `Feature/OrderDuplicationTest.php` | Unicidad de remisión por sede y protección de DB. |
| `Feature/OrderIndexTest.php` | Historial, paginación, borrado y permisos. |
| `Feature/OrderShowTest.php` | Usuario que creó el pedido. |
| `Feature/ReportTest.php` | Filtros, IVA, totales y exportaciones. |
| `Feature/DashboardTest.php` | Pedidos recientes y usuario. |
| `Feature/Auth/*` | Registro, autenticación, contraseñas y verificación scaffold. |
| `Feature/ProfileTest.php` | Perfil y eliminación de cuenta. |

## 17. Operación Y Mantenimiento

### 17.1 Logs

Los logs de Laravel se almacenan en `src/storage/logs/laravel.log` en desarrollo. Para seguir logs de Docker:

```bash
make logs
```

Para revisar errores de aplicación dentro del contenedor:

```bash
docker compose exec php tail -f storage/logs/laravel.log
```

### 17.2 Migraciones

En desarrollo:

```bash
make migrate
```

Para un entorno de pruebas descartable:

```bash
docker compose exec php php artisan migrate:fresh --seed
```

`migrate:fresh` borra todas las tablas. Nunca debe ejecutarse en producción sin una copia de seguridad y autorización explícita.

La migración de unicidad de remisión verifica duplicados existentes y falla con ejemplos si encuentra alguno. Corrija esos duplicados antes de ejecutar la migración.

### 17.3 Copias De Seguridad

La base de datos de desarrollo se conserva en el volumen Docker `db_data`. En producción se debe programar un respaldo de MySQL fuera del contenedor y probar periódicamente la restauración. Los archivos Excel originales y las exportaciones no se almacenan automáticamente por la aplicación.

### 17.4 Cambios De Catálogo

Los cambios de nombre, orden o IVA de categorías afectan la generación de nuevos pedidos y también la reconstrucción de reportes. Antes de cambiar `aplica_iva`, genere los reportes históricos necesarios o documente el cambio.

## 18. Seguridad Y Controles

- Las rutas de negocio usan middleware de autenticación.
- Las solicitudes Inertia usan protección CSRF de Laravel.
- El inicio de sesión limita intentos por correo e IP.
- Las contraseñas se guardan con hash.
- Nginx bloquea archivos ocultos salvo `.well-known`.
- En producción `APP_DEBUG` debe estar deshabilitado.
- Las credenciales predeterminadas de Docker son solo para desarrollo y no deben reutilizarse.
- El contenedor de producción no publica MySQL directamente al host.
- El acceso de catálogo, reportes y borrado está restringido en servidor para el usuario ID 3.

## 19. Limitaciones Y Riesgos Conocidos

1. **Permiso basado en ID fijo**: la regla especial depende de `user.id === 3`; no existe una tabla de roles ni una política configurable.
2. **Verificación de correo**: existen rutas y pantallas de verificación generadas por Breeze, pero `User` no implementa `MustVerifyEmail`; no debe asumirse que la verificación sea un requisito efectivo.
3. **Correo por defecto en logs**: la recuperación de contraseña no llega a usuarios reales mientras `MAIL_MAILER=log` no se reconfigure.
4. **Categorías y productos relacionados**: sus claves foráneas no definen borrado en cascada. El borrado de registros con dependencias puede ser rechazado por la base de datos.
5. **Importación parcial**: la importación de productos procesa fila por fila, no utiliza una transacción global y no muestra el detalle de errores en la interfaz.
6. **Importación sin upsert**: importar dos veces el mismo código no actualiza el producto y puede provocar una violación de unicidad.
7. **Límite de código inconsistente**: la validación permite hasta 50 caracteres, pero la columna `items.codigo_item` tiene longitud 20.
8. **Campos operativos no nulos**: la validación permite omitir presentación y precio de presentación en algunos caminos, aunque la tabla los define como no nulos y el precio de presentación es indispensable para calcular.
9. **IVA histórico de reportes**: los reportes consultan la categoría actual, mientras el pedido conserva el IVA calculado al crearse.
10. **Resource routes incompletas**: las rutas `show` de categorías y productos se generan automáticamente, pero no hay métodos `show()` en sus controladores.
11. **Zona horaria**: el código usa UTC y el frontend propone la fecha del navegador. En operaciones con cambio de día debe verificarse la fecha enviada.
12. **Sedes fijas**: añadir una sede requiere modificar código y pruebas; no existe catálogo administrable.

## 20. Guía Para Nuevos Cambios

Antes de modificar el sistema:

1. Revise el modelo, la migración y el controlador relacionado.
2. Determine si el cambio afecta el cálculo de IVA o el formato de exportación.
3. Mantenga la validación del backend como fuente de seguridad; la validación del frontend es solo de experiencia de usuario.
4. Agregue o ajuste pruebas unitarias y funcionales.
5. Si cambia el formato Excel, actualice `docs/FORMATO-EXCEL.md` y los tests de `ExcelParser`.
6. Si cambia una ruta o pantalla, actualice el inventario de rutas y el manual de usuario.
7. Ejecute `make test` y `make pnpm-build` antes de desplegar.
8. Revise migraciones, índices y compatibilidad con MySQL y SQLite de pruebas.
