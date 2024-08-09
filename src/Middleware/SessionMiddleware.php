<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Initialize session data
 */
class SessionMiddleware extends Middleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        ini_set('session.cookie_samesite', 'None');
        ini_set('session.cookie_secure', 'true');
        session_start();
        $response = $handler->handle($request);
        return $response;
    }
}
