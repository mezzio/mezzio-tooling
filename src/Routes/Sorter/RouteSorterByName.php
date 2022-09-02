<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Sorter;

use Mezzio\Router\Route;

class RouteSorterByName
{
    public function __invoke(Route $routeOne, Route $routeTwo): int
    {
        if ($routeOne->getName() === $routeTwo->getName()) {
            return 0;
        }

        return $routeOne->getName() < $routeTwo->getName() ? -1 : 1;
    }
}
