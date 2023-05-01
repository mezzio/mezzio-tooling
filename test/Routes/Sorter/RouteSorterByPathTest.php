<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes\Sorter;

use Mezzio\Router\Route;
use Mezzio\Tooling\Routes\Sorter\RouteSorterByPath;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\TestCase;

use function count;
use function sprintf;
use function usort;

class RouteSorterByPathTest extends TestCase
{
    /** @var Route[] */
    private array $routes = [];

    public function setUp(): void
    {
        $this->routes = [
            new Route(
                "/user/profile",
                new SimpleMiddleware(),
                ['GET'],
                'user.profile'
            ),
            new Route(
                "/",
                new ExpressMiddleware(),
                ['GET'],
                'home'
            ),
            new Route(
                "/login",
                new ExpressMiddleware(),
                ['GET'],
                'user.login'
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                ['GET'],
                'user.logout'
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                ['GET', 'POST'],
                'user.logout'
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                Route::HTTP_METHOD_ANY,
                'user.logout'
            ),
        ];
    }

    public function testCanSortRoutesByNameInAscendingOrderOfName(): void
    {
        $sorter = new RouteSorterByPath();
        usort($this->routes, $sorter);

        $this->assertCount(6, $this->routes);

        $sortedPaths = [
            '/',
            '/login',
            '/logout',
            '/logout',
            '/logout',
            '/user/profile',
        ];
        for ($i = 0; $i < count($sortedPaths); $i++) {
            $this->assertSame(
                $sortedPaths[$i],
                $this->routes[$i]->getPath(),
                sprintf("Paths for element %d don't match", $i)
            );
        }
    }

    public function testWillReturnAnEmptyArrayIfNoRoutesAreProvided(): void
    {
        $routes = [];
        $sorter = new RouteSorterByPath();
        usort($routes, $sorter);

        $this->assertEmpty($routes);
    }
}
