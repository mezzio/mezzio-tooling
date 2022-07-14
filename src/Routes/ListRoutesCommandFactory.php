<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use Mezzio\Router\RouteCollector;
use Psr\Container\ContainerInterface;

class ListRoutesCommandFactory
{
    public function __invoke(ContainerInterface $container): ListRoutesCommand
    {
        $routesCollector = $container->get(RouteCollector::class);

        return new ListRoutesCommand($routesCollector);
    }
}
