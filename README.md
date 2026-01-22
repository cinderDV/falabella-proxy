# Falabella Seller Center API Proxy

Microservicio PHP ultra-minimalista que actúa como proxy autenticado para la API de Falabella Seller Center.

## Propósito

n8n no puede firmar correctamente las peticiones a la API de Falabella (requiere HMAC-SHA256 + timestamp + RFC3986 encoding). Este servicio encapsula el SDK oficial de Rocket Labs para gestionar la autenticación y firma de URLs automáticamente.

## Stack

- PHP 8.0 (Apache)
- Slim Framework 4
- Rocket Labs Seller Center SDK
- Docker (deployment en Coolify)

## Instalación Local

```bash
# Con Nix
nix-shell
composer install

# Sin Nix (requiere PHP 8.0+)
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
# Health check (para Coolify)
GET /health

# Listar órdenes (soporta filtros: CreatedAfter, Limit, Status, etc.)
GET /orders?CreatedAfter=2025-01-01T00:00:00&Limit=10

# Buscar orden por OrderNumber (número visible en UI Falabella)
GET /orders/number/{orderNumber}

# Obtener orden completa por OrderId (ID interno de la API)
GET /orders/{orderId}

# Obtener productos/items de una orden
GET /orders/{orderId}/items
```

**Nota sobre IDs:**
- **OrderNumber** (ej: `3219360993`): Visible en la UI de Falabella como "Orden N°"
- **OrderId** (ej: `1137486465`): ID interno de la API, visible como "SO N°"

## Respuestas

**Health Check:**
```json
{
  "status": "ok",
  "service": "falabella-proxy",
  "timestamp": "2026-01-22T10:30:00+00:00"
}
```

**Orden completa (GET /orders/{id}):**
```json
{
  "OrderId": 1137486465,
  "OrderNumber": "3219360993",
  "CustomerFirstName": "Juan",
  "CustomerLastName": "Pérez",
  "OrderNumber": "3219360993",
  "PaymentMethod": "ecommPay",
  "Remarks": null,
  "DeliveryInfo": "",
  "Price": "551980.00",
  "GiftOption": 0,
  "GiftMessage": null,
  "VoucherCode": null,
  "CreatedAt": "2025-12-21T16:10:57+00:00",
  "UpdatedAt": "2025-12-24T10:41:47+00:00",
  "PromisedShippingTime": "2025-12-30T17:00:00+00:00",
  "ExtraAttributes": "{}",
  "Statuses": ["delivered"],
  "AddressBilling": {
    "FirstName": "Juan",
    "LastName": "Pérez",
    "Phone": "+56912345678",
    "Address": "Calle Principal 123",
    "CustomerEmail": "juan@ejemplo.com",
    "City": "Santiago",
    "Ward": "",
    "Region": "Metropolitana",
    "PostCode": "8320000",
    "Country": "Chile"
  },
  "AddressShipping": { /* misma estructura */ },
  "NationalRegistrationNumber": "12345678-9",
  "ItemsCount": 1
}
```

**Items de una orden (GET /orders/{id}/items):**
```json
{
  "OrderItems": [
    {
      "OrderItemId": "12345",
      "ShopId": "67890",
      "OrderId": "1137486465",
      "Name": "Producto Ejemplo",
      "Sku": "SKU-123",
      "Variation": "Talla M",
      "ShopSku": "SHOP-SKU-123",
      "ShippingType": "Dropshipping",
      "ItemPrice": "25990.00",
      "PaidPrice": "25990.00",
      "Currency": "CLP",
      "Status": "delivered",
      /* ... más campos ... */
    }
  ]
}
```

**Error:**
```json
{
  "error": "Order not found with OrderNumber: 123456",
  "code": 404
}
```

## Estructura del Proyecto

```
├── Dockerfile          # PHP 8.0 + Apache + parche inline SDK
├── composer.json       # Dependencias (SDK, Slim, dotenv)
├── shell.nix          # Entorno Nix para desarrollo
├── public/
│   ├── index.php      # Rutas Slim Framework
│   └── .htaccess      # Rewrite rules para Apache
└── src/
    └── Client.php     # Wrapper del SDK de Falabella
```

## Compatibilidad PHP 8.0

El SDK oficial usa constantes deprecadas (`FILTER_FLAG_HOST_REQUIRED`, `FILTER_FLAG_SCHEME_REQUIRED`) que no existen en PHP 8.1+. El `Dockerfile` aplica un parche inline con `sed` durante la build:

```dockerfile
RUN sed -i 's/filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED)/filter_var($url, FILTER_VALIDATE_URL)/' \
    /var/www/html/vendor/rocket-labs/sellercenter-sdk-php/src/RocketLabs/SellerCenterSdk/Core/Configuration.php
```

## Deployment en Coolify

### 1. Configuración del Servicio

- **Type:** Dockerfile
- **Dockerfile:** `./Dockerfile`
- **Port:** 80
- **Health Check Path:** `/health`
- **Health Check Interval:** 30s

### 2. Variables de Entorno

En Coolify, configurar:

```
FALABELLA_ENDPOINT=https://sellercenter-api.falabella.com
FALABELLA_USER_ID=tu-email@ejemplo.com
FALABELLA_API_KEY=tu-api-key-aqui
```

### 3. Network Alias (Importante)

Coolify cambia el nombre del contenedor en cada redeploy. Para conectividad estable desde n8n:

```bash
# Después de cada deploy, ejecutar:
docker network connect coolify <container-id> --alias falabella-proxy
```

Luego en n8n usar: `http://falabella-proxy/orders`

### 4. Sin Exposición Pública

Este servicio NO debe estar expuesto públicamente. Solo accesible desde la red interna de Docker (n8n u otros servicios internos).

## Desarrollo

**Servidor local:**
```bash
nix-shell
composer install
php -S localhost:8080 -t public/
```

**Testing:**
```bash
curl -i http://localhost:8080/health
curl -i "http://localhost:8080/orders?Limit=5"
curl -i http://localhost:8080/orders/1137486465
curl -i http://localhost:8080/orders/1137486465/items
curl -i http://localhost:8080/orders/number/3219360993
```

## Licencia

Este es un proyecto interno. El SDK de Rocket Labs está bajo su propia licencia.
