<?php

namespace App\Controller;

use App\Services\FulfillmentService;
use App\Services\Storage;
use App\Utils\Filesystem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UnasOnline\UnasConnect\Api\Client;

/**
 * Handle routes required for UNAS integration
 */
class UnasAppController extends Controller
{
    /**
     * Authenticate using start request from UNAS, redirect
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function start(Request $request, Response $response, $args): Response
    {
        $storage = new Storage($this->get('app-root'), $_SESSION['shop_id']);
        if ($storage->hasSettings()) {
            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        } else {
            return $response
                ->withHeader('Location', '/settings')
                ->withStatus(302);
        }
    }

    /**
     * Handle app install request from UNAS
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function install(Request $request, Response $response, $args): Response
    {
        $body = $request->getParsedBody();
        $app = $this->get('unas-app');
        $storage = new Storage($this->container->get('app-root'), $body['shop_id']);
        $r = $app->requestApiKey($body['shop_id'], $body['time'], $body['token']);
        $storage->setApiKey($r['api_key']);
        
        // save api token
        $client = new Client([
            'apiKey' => $storage->getApiKey(),
        ], $storage);
        $client->login();

        if ($storage->isVip()) {
            $fulfillmentService = new FulfillmentService($storage);
            $apiResponse = $fulfillmentService->createAutomatism();
            $storage->setAutomatismId($apiResponse->getResponse()['Automatism']['Id']);
        }

        return $this->jsonResponse($response, [
            'status' => 'ok',
        ]);
    }

    /**
     * Handle app uninstall request from UNAS
     *
     * @param Request  $request  incoming request
     * @param Response $response outgoing response
     * @param array    $args     args - unused
     */
    public function uninstall(Request $request, Response $response, $args): Response
    {
        $shopId = $request->getParsedBody()['shop_id'];
        $storage = new Storage($this->container->get('app-root'), $shopId);

        if ($storage->isVip()) {
            try {
                $fulfillmentService = new FulfillmentService($storage);
                $fulfillmentService->deleteAutomatism();
            } catch (\Exception $e) {
                //
            }
        }
        
        Filesystem::rrmdir($this->container->get('app-root') . "/data/$shopId");
        
        return $this->jsonResponse($response, [
            'status' => 'ok',
        ]);
    }
}
