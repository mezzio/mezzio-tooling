<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use Mezzio\Router\RouteCollector;
use Psr\Container\ContainerInterface;

final class ListRoutesCommandFactory
{
    public function __invoke(ContainerInterface $container): ListRoutesCommand
    {
        /** @var RouteCollector $routeCollector */
        $routeCollector = $container->get(RouteCollector::class);

        /** @var ConfigLoaderInterface $configLoader */
        $configLoader = $container->get(ConfigLoaderInterface::class);

        return new ListRoutesCommand($routeCollector, $configLoader);
    }
}
