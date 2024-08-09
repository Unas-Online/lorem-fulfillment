<?php

namespace App\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Validate requests related to UNAS app integration
 */
class VerifyUnasAppRequest extends Middleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $app = $this->container->get('unas-app');

        $params = $request->getMethod() == 'GET'
            ? $request->getQueryParams()
            : $request->getParsedBody();

        $verify = $app->verifyRequest($params['shop_id'], $params['time'], $params['token'], $params['hmac']);
        if ($verify !== true) {
            throw new Exception('Validation error: ' . $verify);
        }

        $_SESSION['shop_id'] = $params['shop_id'];
        
        $response = $handler->handle($request);
        return $response;
    }
}
