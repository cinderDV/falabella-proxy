# Falabella Seller Center API Proxy

Microservicio PHP que actúa como proxy autenticado para la API de Falabella Seller Center.

## Propósito

n8n no puede firmar correctamente las peticiones a la API de Falabella (requiere HMAC-SHA256). Este servicio encapsula el SDK oficial de Rocket Labs para gestionar la autenticación automáticamente.

## Stack

- PHP 8.3+
- Slim Framework 4
- Rocket Labs Seller Center SDK
- Composer patches (fix PHP 8.3 compatibility)

## Instalación

```bash
# Con Nix
nix-shell
composer install

# Sin Nix (requiere PHP 8.1+)
composer install
```

Configurar credenciales:

```bash
cp .env.example .env
# Editar .env con tus credenciales de Falabella
```

## Uso

**Servidor de desarrollo:**

```bash
php -S localhost:8080 -t public/
```

**Endpoints:**

```bash
# Listar órdenes (soporta filtros: CreatedAfter, Limit, Status, etc.)
GET /orders?CreatedAfter=2025-01-01T00:00:00&Limit=10

# Obtener orden por OrderId (ID interno de la API)
GET /orders/{orderId}

# Buscar orden por OrderNumber (número visible en UI Falabella)
GET /orders/number/{orderNumber}
```

**Nota:** `OrderNumber` (ej: 3219360993) es el visible en Falabella UI. `OrderId` (ej: 1137486465) es el ID interno de la API.

## Estructura

```
├── composer.json       # Dependencias + configuración de parches
├── shell.nix          # Entorno Nix
├── patches/           # Parche PHP 8.3 para el SDK
├── public/index.php   # Entrypoint
└── src/Client.php     # Wrapper del SDK
```

## Parche SDK

El SDK oficial usa constantes deprecadas en PHP 8.3. El parche en `patches/` se aplica automáticamente con `composer install` via `cweagans/composer-patches`.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
```

Configurar variables de entorno del servidor (no usar archivo .env en producción).

## Respuestas

**Éxito:**
```json
[
  {
    "OrderId": 1137486465,
    "OrderNumber": "3219360993",
    "CustomerFirstName": "Juan",
    "CustomerLastName": "Pérez",
    "PaymentMethod": "ecommPay",
    "Price": "551980.00",
    "CreatedAt": "2025-12-21T16:10:57+00:00",
    "UpdatedAt": "2025-12-24T10:41:47+00:00"
  }
]
```

**Error:**
```json
{
  "error": "Order not found with OrderNumber: 123456",
  "code": 404
}
```
