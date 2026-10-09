# Control de Bodegas

Sistema web para una bodega que almacena mercadería **importada** (por mar, aire o tierra) de terceros y la entrega a sus clientes, de forma parcial o total, cobrando el almacenaje y las entregas. Incluye autenticación y autorización por roles.

> **Estado:** el flujo completo está implementado: ingreso → almacenaje con tarifas → entregas parciales o totales (retiro o domicilio) → comprobante de cobro → pago.

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

| Correo                  | Rol           | Alcance                                        |
| ----------------------- | ------------- | ---------------------------------------------- |
| admin@bodegas.test      | Administrador | Todo (rol protegido)                           |
| supervisor@bodegas.test | Supervisor    | Todas las bodegas, clientes e ingresos         |
| bodeguero@bodegas.test  | Bodeguero     | Solo Bodega Central y Norte; registra ingresos |
| auditor@bodegas.test    | Auditor       | Solo lectura de todas las bodegas              |

## Módulos

- **Panel**: ingresos activos, unidades almacenadas, pendiente de cobrar, entregas a domicilio activas y actividad de 14 días.
- **Ingresos**: recepción de contenedores, furgones o carga aérea para un cliente, con tipo de transporte, BL/guía aérea/carta de porte, DUCA, transportista y origen. Folio `ING-000001`…
- **Unidades de almacenaje**: contenedor, palet, caja o bulto en jerarquía, con código propio, piezas, peso, volumen y ubicación.
- **Tarifas**: almacenaje (por unidad y día, con días libres), manejo (por unidad entregada) y entrega a domicilio (por entrega). Una tarifa por cliente o por tipo de unidad tiene prioridad sobre la general.
- **Entregas**: retiro en bodega o a domicilio, parcial o total (`ENT-000001`…). Domicilio: transportista, destino, y seguimiento programada → en ruta → entregada, con constancia. Se puede cancelar mientras no esté cobrada.
- **Cobros**: cada entrega genera un comprobante (`COB-000001`…) con el desglose de almacenaje, manejo y domicilio; se puede pagar al entregar o después. Las unidades se cobran una sola vez, en la entrega que las saca.
- **Clientes**: catálogo y estado de cuenta (facturado, cobrado, pendiente y almacenaje acumulado sin cobrar).
- **Catálogos y administración**: bodegas, transportistas, usuarios y roles con matriz de permisos editable.

### Reglas de cobro

- El almacenaje cuenta días calendario desde la recepción (el primer día cuenta) hasta que la unidad sale; los días libres no se cobran.
- Solo se cobran las unidades sin contenido: un contenedor con palets se cobra por sus palets; un contenedor sellado, como contenedor.
- Entregar una unidad que contiene otras libera todo su contenido. Cuando sale lo último, el ingreso queda finalizado.

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
