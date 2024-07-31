<?php

namespace App\Controller;

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use UnasOnline\UnasConnect\Api\Response as ApiResponse;

/**
 * Base class for all controllers
 */
class Controller
{
    protected Container $container;
    private $routeParser;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->routeParser = $container->get('app')->getRouteCollector()->getRouteParser();
    }

    /**
     * Returns an entry of the container by its name.
     *
     * @template T
     * @param string|class-string<T> $id Entry name or a class name.
     *
     * @return mixed|T
     * @throws DependencyException Error while resolving the entry.
     * @throws NotFoundException No entry found for the given name.
     */
    protected function get(string $id)
    {
        return $this->container->get($id);
    }

    /**
     * Returns the corresponding full url for a route name
     *
     * @param string $route
     *
     * @return string
     */
    public function urlFor(string $route)
    {
        return $this->routeParser->urlFor($route);
    }

    public function apiCall(
        string $method,
        array $xml,
        string $rootElement = '',
        bool $withoutToken = false
    ): ApiResponse {
        return $this->get('unas-api')->apiCall($method, $xml, $rootElement, $withoutToken);
    }

    /**
     * Adds a "context" field to the associative array $data with useful information for templates.
     *
     * @param array $data
     * @param Request $request
     * @return array
     */
    protected function addContext(array $data, Request $request): array
    {
        $data['context'] = [
            'uri' => $request->getUri(),
            'urlFor' => fn($route) => $this->urlFor($route),
        ];

        return $data;
    }

    /**
     * Renders twig view $template using $data into $response, returns $response.
     *
     * @param Response $response
     * @param string   $template
     * @param $data
     * @param Request $request
     *
     * @return Response
     */
    protected function twigResponse(Response $response, string $template, ?array $data, Request $request): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, $template, $this->addContext($data, $request), $request);
    }

    /**
     * Writes $data as json to $response, returns $response.
     *
     * @param Response $response
     * @param $data
     * @return Response
     */
    protected function jsonResponse(Response $response, $data): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response;
    }
}
