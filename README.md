# Control de Bodegas

Sistema web para administrar bodegas, catálogo de productos, existencias y movimientos de inventario, con autenticación y autorización por roles.

## Stack

| Capa            | Tecnología                                                                         |
| --------------- | ---------------------------------------------------------------------------------- |
| Backend         | Laravel 13 · PHP 8.5 · Fortify (login, 2FA, passkeys)                              |
| Autorización    | spatie/laravel-permission 8 (roles + permisos)                                     |
| Frontend        | Inertia 3 · Vue 3 · TypeScript · Tailwind CSS 4 · shadcn-vue (reka-ui) · Wayfinder |
| Infraestructura | Docker: php-fpm, nginx, PostgreSQL 18, Redis                                       |

Plantilla base: **Laravel Vue Starter Kit** oficial, con un sistema de diseño propio (paleta slate/índigo con acento ámbar, tipografías Plus Jakarta Sans + JetBrains Mono, modo claro/oscuro, accesibilidad AA).

## Puesta en marcha

```bash
cp .env.example .env
docker compose up -d --build
docker exec control-bodegas-app composer install
docker exec control-bodegas-app php artisan key:generate
docker exec control-bodegas-app php artisan migrate --seed
docker exec control-bodegas-app npm install
docker exec control-bodegas-app npm run build
```

App: http://localhost:8075 · PostgreSQL expuesto en `localhost:54325`.

Para desarrollo con recarga en caliente: `docker exec -it control-bodegas-app npm run dev` (o `npm run dev` en el host si tienes PHP, por Wayfinder).

## Usuarios de prueba (solo fuera de producción)

Contraseña de todos: `password`

| Correo                  | Rol           | Alcance                                                         |
| ----------------------- | ------------- | --------------------------------------------------------------- |
| admin@bodegas.test      | Administrador | Todo (rol protegido)                                            |
| supervisor@bodegas.test | Supervisor    | Todas las bodegas, catálogos y todos los movimientos            |
| bodeguero@bodegas.test  | Bodeguero     | Solo Bodega Central y Norte; entradas, salidas y transferencias |
| auditor@bodegas.test    | Auditor       | Solo lectura de todas las bodegas                               |

## Módulos

- **Panel**: valor del inventario, alertas de stock bajo, actividad de 14 días, valor por bodega.
- **Existencias**: stock por producto y bodega, filtros y alertas de mínimos.
- **Movimientos**: entradas (con proveedor y costo), salidas, transferencias entre bodegas y ajustes por conteo físico. Folios automáticos (`ENT-000001`…), transacciones con bloqueo de filas y validación de stock disponible. Los movimientos son inmutables (bitácora).
- **Catálogos**: productos, categorías, proveedores y bodegas.
- **Administración**: usuarios (rol + bodegas asignadas + activo/inactivo) y roles con matriz de permisos editable.

## Autorización

- Los permisos se definen en `app/Support/Permissions.php` (fuente única) y se siembran con `RolesAndPermissionsSeeder`.
- Las rutas se protegen con middleware `can:<permiso>`; el rol `Administrador` pasa todos los gates (`Gate::before`).
- `warehouses.all` da acceso a todas las bodegas; sin él, el usuario solo ve y opera las bodegas asignadas.
- Los usuarios desactivados son expulsados en su siguiente petición (middleware `active`).
- No hay registro público: las cuentas las crea un administrador.
- El frontend recibe `auth.permissions` y oculta menús/acciones con `usePermissions().can()`.

## Calidad

```bash
docker exec control-bodegas-app php artisan test
docker exec control-bodegas-app vendor/bin/phpstan analyse
docker exec control-bodegas-app vendor/bin/pint
docker exec control-bodegas-app npm run check
docker exec control-bodegas-app npm run types:check
```
