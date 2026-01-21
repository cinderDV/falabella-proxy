<?php
namespace App;

use RocketLabs\SellerCenterSdk\Endpoint\Endpoints;
use RocketLabs\SellerCenterSdk\Core\Configuration;
use RocketLabs\SellerCenterSdk\Core\Client as RocketClient;
use RocketLabs\SellerCenterSdk\Core\Response\ErrorResponse;

class Client {
    private $config;

    public function __construct() {
        // Configuración según el estándar de Rocket Labs
        $this->config = new Configuration(
            $_ENV['FALABELLA_ENDPOINT'],
            $_ENV['FALABELLA_USER_ID'],
            $_ENV['FALABELLA_API_KEY']
        );
    }

    private function handleResponse($response) {
        if ($response instanceof ErrorResponse) {
            throw new \Exception(
                $response->getMessage() ?: 'API Error',
                $response->getCode() ?: 500
            );
        }
        return $response;
    }

    public function getOrders($params) {
        $client = RocketClient::create($this->config);

        // Construir el request usando el builder pattern del SDK
        $builder = Endpoints::order()->getOrders();

        // Mapear parámetros del query string al builder
        if (isset($params['CreatedAfter'])) {
            $builder->setCreatedAfter(new \DateTime($params['CreatedAfter']));
        }
        if (isset($params['CreatedBefore'])) {
            $builder->setCreatedBefore(new \DateTime($params['CreatedBefore']));
        }
        if (isset($params['UpdatedAfter'])) {
            $builder->setUpdatedAfter(new \DateTime($params['UpdatedAfter']));
        }
        if (isset($params['UpdatedBefore'])) {
            $builder->setUpdatedBefore(new \DateTime($params['UpdatedBefore']));
        }
        if (isset($params['Limit'])) {
            $builder->setLimit($params['Limit']);
        }
        if (isset($params['Offset'])) {
            $builder->setOffset($params['Offset']);
        }
        if (isset($params['Status'])) {
            $builder->setStatus($params['Status']);
        }

        $request = $builder->build();
        $response = $this->handleResponse($client->call($request));

        // Convertir a array serializable
        $orders = [];
        foreach ($response->getOrders()->toArray() as $order) {
            $orders[] = [
                'OrderId' => $order->getOrderId(),
                'OrderNumber' => $order->getOrderNumber(),
                'CustomerFirstName' => $order->getCustomerFirstName(),
                'CustomerLastName' => $order->getCustomerLastName(),
                'PaymentMethod' => $order->getPaymentMethod(),
                'Price' => $order->getPrice(),
                'CreatedAt' => $order->getCreatedAt()->format('Y-m-d\TH:i:sP'),
                'UpdatedAt' => $order->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
            ];
        }

        return $orders;
    }

    public function getOrder($id) {
        $client = RocketClient::create($this->config);

        $request = Endpoints::order()->getOrder($id);
        $response = $this->handleResponse($client->call($request));
        $order = $response->getOrder();

        // Convertir a array serializable
        return [
            'OrderId' => $order->getOrderId(),
            'OrderNumber' => $order->getOrderNumber(),
            'CustomerFirstName' => $order->getCustomerFirstName(),
            'CustomerLastName' => $order->getCustomerLastName(),
            'PaymentMethod' => $order->getPaymentMethod(),
            'Price' => $order->getPrice(),
            'CreatedAt' => $order->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'UpdatedAt' => $order->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
        ];
    }

    public function getOrderByNumber($orderNumber) {
        $client = RocketClient::create($this->config);

        // GetOrders sin filtros para buscar por OrderNumber
        $builder = Endpoints::order()->getOrders();
        $builder->setLimit(1000); // Ajusta según necesidad

        $request = $builder->build();
        $response = $this->handleResponse($client->call($request));

        // Buscar en la colección
        foreach ($response->getOrders()->toArray() as $order) {
            if ($order->getOrderNumber() == $orderNumber) {
                // Retornar como array para JSON
                return [
                    'OrderId' => $order->getOrderId(),
                    'OrderNumber' => $order->getOrderNumber(),
                    'CustomerFirstName' => $order->getCustomerFirstName(),
                    'CustomerLastName' => $order->getCustomerLastName(),
                    'PaymentMethod' => $order->getPaymentMethod(),
                    'Price' => $order->getPrice(),
                    'CreatedAt' => $order->getCreatedAt()->format('Y-m-d\TH:i:sP'),
                    'UpdatedAt' => $order->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
                ];
            }
        }

        throw new \Exception("Order not found with OrderNumber: $orderNumber", 404);
    }
}