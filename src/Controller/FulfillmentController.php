<?php

namespace App\Controller;

use App\Services\FulfillmentService;
use App\Services\Storage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handle app routes
 */
class FulfillmentController extends Controller
{
    /**
     * Order list page handler
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function index(Request $request, Response $response, $args): Response
    {
        $storage = $this->get('storage');
        $orders = $storage->getOrders();
        $settings = $storage->getSettings();

        return $this->twigResponse($response, 'index.html.twig', [
            'orders' => $orders,
            'settings' => $settings,
            'dark' => !empty($_SESSION['dark'])
        ], $request);
    }

    /**
     * Handler for manual order list update
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function loadOrders(Request $request, Response $response, $args): Response
    {
        $storage = $this->get('storage');
        $fulfillmentService = new FulfillmentService($storage);
        $fulfillmentService->updateOrdersFromShop();
     
        return $response
            ->withHeader('Location', $this->urlFor('index'))
            ->withStatus(302);
    }

    /**
     * Simulate fulfillment progress
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function simulate(Request $request, Response $response, $args): Response
    {
        $storage = $this->get('storage');
        $fulfillmentService = new FulfillmentService($storage);
        $fulfillmentService->simulateFulfillment();

        return $response
            ->withHeader('Location', $this->urlFor('index'))
            ->withStatus(302);
    }

    /**
     * Handle product status update webhook calls
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function handleWebhook(Request $request, Response $response, $args): Response
    {
        // TODO: verify request
        $body = (array)json_decode((string)$request->getBody(), true);
        $_SESSION['shop_id'] = $body['shopID'];
        $storage = new Storage($this->get('app-root'), $body['shopID']);
        $fulfillmentService = new FulfillmentService($storage);
        $fulfillmentService->updateOrdersFromShop();
        
        return $this->jsonResponse($response, [
            'status' => 'ok'
        ]);
    }
}
