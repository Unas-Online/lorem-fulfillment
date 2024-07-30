<?php

namespace App\Middleware;

use DI\Container;

/**
 * Base class for all middleware
 */
class Middleware
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}
