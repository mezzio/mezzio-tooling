<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Sorter;

use Mezzio\Router\Route;

class RouteSorterByPath
{
    public function __invoke(Route $routeOne, Route $routeTwo): int
    {
        if ($routeOne->getPath() === $routeTwo->getPath()) {
            return 0;
        }

        return $routeOne->getPath() < $routeTwo->getPath() ? -1 : 1;
    }
}
