<?php

namespace App\Controller;

use App\Services\FulfillmentService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * View and save app settings
 */
class SettingsController extends Controller
{
    /**
     * Render settings page
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function settings(Request $request, Response $response, $args): Response
    {
        $apiResponse = $this->apiCall('getOrderStatus', [], 'Params')->getResponse();
        $storage = $this->get('storage');

        return $this->twigResponse($response, 'settings.html.twig', [
            'response' => $apiResponse,
            'settings' => $storage->getSettings(),
        ], $request);
    }

    /**
     * Save settings, redirect to main page
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function saveSettings(Request $request, Response $response, $args)
    {
        $storage = $this->get('storage');
        $storage->setSettings($request->getParsedBody());
        $fulfillmentService = new FulfillmentService($storage);
        $fulfillmentService->updateOrdersFromShop();

        return $response
            ->withHeader('Location', $this->urlFor('index'))
            ->withStatus(302);
    }
}
