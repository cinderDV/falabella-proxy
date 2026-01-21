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

    private function serializeOrder($order) {
        $addressBilling = $order->getAddressBilling();
        $addressShipping = $order->getAddressShipping();

        return [
            'OrderId' => $order->getOrderId(),
            'OrderNumber' => $order->getOrderNumber(),
            'CustomerFirstName' => $order->getCustomerFirstName(),
            'CustomerLastName' => $order->getCustomerLastName(),
            'PaymentMethod' => $order->getPaymentMethod(),
            'Remarks' => $order->getRemarks(),
            'DeliveryInfo' => $order->getDeliveryInfo(),
            'Price' => $order->getPrice(),
            'GiftMessage' => $order->getGiftMessage(),
            'VoucherCode' => $order->getVoucherCode(),
            'NationalRegistrationNumber' => $order->getNationalRegistrationNumber(),
            'ItemsCount' => $order->getItemsCount(),
            'PromisedShippingTime' => $order->getPromisedShippingTime() ? $order->getPromisedShippingTime()->format('Y-m-d\TH:i:sP') : null,
            'ExtraAttributes' => $order->getExtraAttributes(),
            'CreatedAt' => $order->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'UpdatedAt' => $order->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
            'AddressBilling' => $addressBilling ? [
                'FirstName' => $addressBilling->getFirstName(),
                'LastName' => $addressBilling->getLastName(),
                'Phone' => $addressBilling->getPhone(),
                'Phone2' => $addressBilling->getPhone2(),
                'Address' => $addressBilling->getAddress(),
                'Address2' => $addressBilling->getAddress2(),
                'Address3' => $addressBilling->getAddress3(),
                'Address4' => $addressBilling->getAddress4(),
                'Address5' => $addressBilling->getAddress5(),
                'City' => $addressBilling->getCity(),
                'Ward' => $addressBilling->getWard(),
                'Region' => $addressBilling->getRegion(),
                'PostCode' => $addressBilling->getPostCode(),
                'Country' => $addressBilling->getCountry(),
            ] : null,
            'AddressShipping' => $addressShipping ? [
                'FirstName' => $addressShipping->getFirstName(),
                'LastName' => $addressShipping->getLastName(),
                'Phone' => $addressShipping->getPhone(),
                'Phone2' => $addressShipping->getPhone2(),
                'Address' => $addressShipping->getAddress(),
                'Address2' => $addressShipping->getAddress2(),
                'Address3' => $addressShipping->getAddress3(),
                'Address4' => $addressShipping->getAddress4(),
                'Address5' => $addressShipping->getAddress5(),
                'City' => $addressShipping->getCity(),
                'Ward' => $addressShipping->getWard(),
                'Region' => $addressShipping->getRegion(),
                'PostCode' => $addressShipping->getPostCode(),
                'Country' => $addressShipping->getCountry(),
            ] : null,
            'Statuses' => $order->getStatuses() ? $order->getStatuses()->toArray() : [],
        ];
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
            $orders[] = $this->serializeOrder($order);
        }

        return $orders;
    }

    public function getOrder($id) {
        $client = RocketClient::create($this->config);

        $request = Endpoints::order()->getOrder($id);
        $response = $this->handleResponse($client->call($request));
        $order = $response->getOrder();

        return $this->serializeOrder($order);
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
                return $this->serializeOrder($order);
            }
        }

        throw new \Exception("Order not found with OrderNumber: $orderNumber", 404);
    }

    public function getOrderItems($orderId) {
        $client = RocketClient::create($this->config);

        $request = Endpoints::order()->getOrderItems($orderId);
        $response = $this->handleResponse($client->call($request));

        $items = [];
        foreach ($response->getItems()->toArray() as $item) {
            $items[] = [
                'OrderItemId' => $item->getOrderItemId(),
                'OrderId' => $item->getOrderId(),
                'Name' => $item->getName(),
                'Sku' => $item->getSku(),
                'ShopSku' => $item->getShopSku(),
                'ShippingType' => $item->getShippingType(),
                'ItemPrice' => $item->getItemPrice(),
                'PaidPrice' => $item->getPaidPrice(),
                'WalletCredits' => $item->getWalletCredits(),
                'TaxAmount' => $item->getTaxAmount(),
                'ShippingAmount' => $item->getShippingAmount(),
                'VoucherAmount' => $item->getVoucherAmount(),
                'VoucherCode' => $item->getVoucherCode(),
                'Status' => $item->getStatus(),
                'ShipmentProvider' => $item->getShipmentProvider(),
                'TrackingCode' => $item->getTrackingCode(),
                'Reason' => $item->getReason(),
                'ReasonDetail' => $item->getReasonDetail(),
                'PromisedShippingTimes' => $item->getPromisedShippingTimes() ? $item->getPromisedShippingTimes()->format('Y-m-d\TH:i:sP') : null,
                'ShippingProviderType' => $item->getShippingProviderType(),
                'ExtraAttributes' => $item->getExtraAttributes(),
                'CreatedAt' => $item->getCreatedAt() ? $item->getCreatedAt()->format('Y-m-d\TH:i:sP') : null,
                'UpdatedAt' => $item->getUpdatedAt() ? $item->getUpdatedAt()->format('Y-m-d\TH:i:sP') : null,
            ];
        }

        return $items;
    }
}