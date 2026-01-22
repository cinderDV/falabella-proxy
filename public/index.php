<?php
// Suprimir warnings de deprecación del SDK legacy
error_reporting(E_ALL & ~E_DEPRECATED);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Client;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno (Coolify las inyecta)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$app = AppFactory::create();
$falabella = new Client();

// --- RUTAS ---

// Listar órdenes: GET /orders
$app->get('/orders', function (Request $request, Response $response) use ($falabella) {
    try {
        $params = $request->getQueryParams();
        $data = $falabella->getOrders($params);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $httpCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
        $error = ['error' => $e->getMessage(), 'code' => $e->getCode()];
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($httpCode);
    }
});

// Buscar por OrderNumber: GET /orders/number/{orderNumber}
$app->get('/orders/number/{orderNumber}', function (Request $request, Response $response, array $args) use ($falabella) {
    try {
        $data = $falabella->getOrderByNumber($args['orderNumber']);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $httpCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
        $error = ['error' => $e->getMessage(), 'code' => $e->getCode()];
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($httpCode);
    }
});

// Detalle de orden por OrderId: GET /orders/{id}
$app->get('/orders/{id}', function (Request $request, Response $response, array $args) use ($falabella) {
    try {
        $data = $falabella->getOrder($args['id']);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $httpCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
        $error = ['error' => $e->getMessage(), 'code' => $e->getCode()];
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($httpCode);
    }
});

// Obtener items/productos de una orden: GET /orders/{id}/items
$app->get('/orders/{id}/items', function (Request $request, Response $response, array $args) use ($falabella) {
    try {
        $data = $falabella->getOrderItems($args['id']);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $httpCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
        $error = ['error' => $e->getMessage(), 'code' => $e->getCode()];
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($httpCode);
    }
});

// Health check para Coolify: GET /health
$app->get('/health', function (Request $request, Response $response) {
    $health = [
        'status' => 'ok',
        'service' => 'falabella-proxy',
        'timestamp' => date('c')
    ];
    $response->getBody()->write(json_encode($health));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();