# Despensa Orders

Aplicación web para generar y gestionar pedidos de compra de alimentos (despensa) a partir de archivos Excel, construida con **Laravel 13**, **Inertia.js** (Vue 3) y **Tailwind CSS 4**.

La documentación funcional y técnica se encuentra en `docs/`:

- [Manual de usuario](docs/MANUAL-USUARIO.md): operación diaria, catálogo, pedidos, reportes, exportaciones y solución de problemas.
- [Documentación técnica](docs/DOCUMENTACION-TECNICA.md): arquitectura, instalación, modelo de datos, rutas, cálculos, permisos, pruebas y despliegue.
- [Formato de archivos Excel](docs/FORMATO-EXCEL.md): estructura aceptada para generar pedidos e importar productos.

## Requisitos

- Docker y Docker Compose
- Make
- Un navegador web moderno

En el entorno Docker local la aplicación usa MySQL. El archivo `src/.env.example` conserva el valor de SQLite del esqueleto de Laravel, por lo que una instalación nueva debe configurar las variables MySQL indicadas en la [documentación técnica](docs/DOCUMENTACION-TECNICA.md).

## Inicio rápido

```bash
make up           # Inicia todos los servicios
make migrate      # Ejecuta migraciones
make seed         # Puebla categorías
make pnpm-build    # Genera los recursos frontend si aún no existen
make bash          # Shell opcional dentro del contenedor PHP
```

Una vez iniciado, la aplicación está disponible en `http://localhost:8080`.

## Servicios Docker

| Servicio    | Puerto | Descripción                     |
|-------------|--------|---------------------------------|
| nginx       | 8080   | Servidor web (proxy a php-fpm)  |
| php-fpm     | 9000   | PHP 8.3 + Node 22 + pnpm        |
| Vite HMR    | 5173   | Hot Module Replacement (frontend)|
| mysql       | 3307   | Base de datos MySQL 8.4         |
| phpmyadmin  | 8081   | Administrador web de BD         |

## Comandos disponibles

| Comando             | Acción                                    |
|---------------------|-------------------------------------------|
| `make up`           | `docker compose up -d`                    |
| `make down`         | `docker compose down`                     |
| `make bash`         | Shell dentro del contenedor PHP           |
| `make migrate`      | Ejecuta migraciones                       |
| `make seed`         | Puebla la base de datos (categorías)      |
| `make test`         | Ejecuta PHPUnit                           |
| `make pnpm-dev`     | Inicia Vite HMR                           |
| `make pnpm-build`   | Build de producción del frontend          |
| `make logs`         | Tail de logs de todos los servicios       |

## Tecnologías

- **Backend:** Laravel 13, PHP 8.3
- **Frontend:** Vue 3, Inertia.js 2, Tailwind CSS 4, Vite 8
- **Base de datos:** MySQL 8.4 (dev), SQLite (tests)
- **Exportación:** XLSX (PhpSpreadsheet), PDF (DomPDF)

## Estructura del proyecto

```
├── docker/              # Configuración Docker (nginx, mysql, php)
├── docs/                # Manual, documentación técnica y formatos Excel
├── src/                 # Código fuente (montado en /var/www)
│   ├── app/
│   │   ├── Http/Controllers/   # Controladores
│   │   ├── Models/             # Modelos Eloquent
│   │   └── Services/           # Lógica de negocio
│   ├── database/
│   │   ├── migrations/         # Migraciones
│   │   └── seeders/            # Seeders
│   ├── resources/
│   │   └── js/                 # Frontend Vue 3 + Inertia
│   ├── routes/                 # Definición de rutas
│   └── tests/                  # Tests PHPUnit
├── docker-compose.yml
├── Makefile
└── AGENTS.md
```

## Modelos de datos

- **Category** — Categorías de productos (nombre, orden, aplica_iva)
- **Item** — Productos (código, descripción, precios, categoría)
- **Order** — Pedidos (remisión, sede, fecha, subtotal, iva, total)
- **OrderItem** — Líneas del pedido (cantidad, precios, total)

El **IVA (19%)** se aplica por categoría según el campo `aplica_iva`.

## Flujo principal

1. El usuario carga un archivo `.xlsx`/`.xls` con códigos de producto y cantidades
2. El sistema parsea el archivo, busca los productos en el catálogo y agrupa por categoría
3. Se muestra una vista previa con subtotales, IVA y total
4. Al confirmar, se persiste el pedido en la base de datos
5. El pedido puede exportarse como XLSX o PDF

## Tests

```bash
make test
```

Los tests usan SQLite en memoria. No requieren base de datos externa.

## Licencia

Este proyecto es de uso interno.
