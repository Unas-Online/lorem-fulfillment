<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use UnasOnline\UnasConnect\Utils\Arrays;

/**
 * Initialize session data
 */
class SessionMiddleware extends Middleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * @param Request        $request incoming request
     * @param RequestHandler $handler additional request handler
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        ini_set('session.cookie_samesite', 'None');
        ini_set('session.cookie_secure', 'true');
        session_set_cookie_params([
            'samesite' => 'None'
        ]);
        session_start();

        if ($request->getUri()->getPath() == '/unas/start') {
            if (Arrays::get($_SESSION, 'shop_id') === null && Arrays::get($_REQUEST, 'shop_id') !== null) {
                $_SESSION['shop_id'] = Arrays::get($_REQUEST, 'shop_id');
            }
        }

        $response = $handler->handle($request);
        return $response;
    }
}
