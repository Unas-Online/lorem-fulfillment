<?php

namespace App\Services;

use UnasOnline\UnasConnect\Api\Client;
use UnasOnline\UnasConnect\Api\Response;

class FulfillmentService
{
    private Client $client;
    private Storage $storage;
    
    /**
     * Initialize UnasApiClient for FulfillmentService
     *
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
        $this->client = new Client([
            'apiKey' => $storage->getApiKey(),
        ], $storage);
    }

    /**
     * Drop locally stored orders, update from shop
     *
     * @return void
     */
    public function updateOrdersFromShop(): void
    {
        $orderResponse = $this->client->apiCall('getOrder', []);
        $settings = $this->storage->getSettings();

        $this->storage->deleteOrders();
        foreach ($orderResponse->getResponse()['Order'] as $order) {
            if (in_array($order->StatusID, array_values($settings))) {
                $this->storage->updateOrder((array)$order);
            }
        }
    }

    /**
     * Create automatism to call webhook on order status change
     *
     * @return Response api response
     */
    public function createAutomatism(): Response
    {
        return $this->client->apiCall('setAutomatism', [
            'Automatism' => [
                [
                    'Action' => 'add',
                    'Name' => 'lorem-fulfillment-webhook',
                    'Active' => 'yes',
                    'Schedule' => [
                        'Type' => 'instant'
                    ],
                    'Event' => [
                        'Type' => 'order_status_modify'
                    ],
                    'Operation' => [
                        'Type' => 'webhook',
                        'Config' => [
                            'WebhookUrl' => 'https://demoapp.unas.cloud/webhook/orderstatus'
                        ]
                    ]
                ]
            ]
        ], 'Automatisms');
    }

    /**
     * Delete automatism created by createAutomatism
     *
     * @return Response api response
     */
    public function deleteAutomatism(): ?Response
    {
        $id = $this->storage->getAutomatismId();
        if (is_null($id)) {
            return null;
        }
        
        return $this->client->apiCall('setAutomatism', [
            'Automatism' => [
                [
                    'Action' => 'delete',
                    'Id' => $id,
                ]
            ]
        ], 'Automatisms');
    }

    /**
     * Simulate the fulfillment process
     *
     * Updates status of some orders, based on random values.
     *
     * @return void
     */
    public function simulateFulfillment(): void
    {
        $settings = $this->storage->getSettings();
        $settingsFlip = array_flip($settings);
        $orders = $this->storage->getOrders();

        foreach ($orders as $orderData) {
            $proceed = rand(0, 4) != 1;
            if (!$proceed) {
                continue;
            }
            
            $orderStatus = $orderData['StatusID'];
    
            if ($settingsFlip[$orderStatus] == 'status_teljesites') {
                $success = rand(0, 4) != 1;
                $this->client->apiCall('setOrder', [
                    'Order' => [
                        [
                            'Id' => $orderData['Id'],
                            'Key' => $orderData['Key'],
                            'Action' => 'modify',
                            'Status' => $success
                                ? $settings['status_teljesitve']
                                : $settings['status_sikertelen'],
                                ]
                        ]
                ], 'Orders');
            } elseif ($settingsFlip[$orderStatus] == 'status_atad') {
                $this->client->apiCall('setOrder', [
                    'Order' => [
                        [
                            'Id' => $orderData['Id'],
                            'Key' => $orderData['Key'],
                            'Action' => 'modify',
                            'Status' => $settings['status_teljesites'],
                        ]
                    ]
                ], 'Orders');
            }
        }

        $this->updateOrdersFromShop();
    }
}
