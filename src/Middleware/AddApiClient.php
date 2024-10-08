<?php

namespace App\Middleware;

use App\Services\Storage;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use UnasOnline\UnasConnect\Api\Client;

/**
 * Add Storage and UnasApiClient to container
 */
class AddApiClient extends Middleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     * 
     * @param Request        $request incoming request
     * @param RequestHandler $handler additional request handler
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $storage = new Storage($this->container->get('app-root'), $_SESSION['shop_id']);
        $this->container->set('storage', $storage);
        $this->container->set('unas-api', new Client([
            'apiKey' => $storage->getApiKey(),
        ], $storage));
        $response = $handler->handle($request);
        return $response;
    }
}
