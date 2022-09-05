<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Sorter;

use Mezzio\Router\Route;

final class RouteSorterByPath
{
    public function __invoke(Route $routeOne, Route $routeTwo): int
    {
        return $routeOne->getPath() <=> $routeTwo->getPath();
    }
}
