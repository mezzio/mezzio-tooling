<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use ArrayIterator;
use Mezzio\Router\Route;
use Mezzio\Tooling\Routes\RoutesFilter;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RoutesFilterTest extends TestCase
{
    use ProphecyTrait;

    private array $routes;

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

    /**
     * @dataProvider validFilterDataProvider
     */
    public function testCanFilterRoutesProperly(int $expectedNumberOfRoutes, array $filterOptions = []): void
    {
        $this->setUp();

        $routeFilter = new RoutesFilter(new ArrayIterator($this->routes), $filterOptions);

        $this->assertCount($expectedNumberOfRoutes, $routeFilter);
    }

    public function validFilterDataProvider(): array
    {
        return [
            [
                1,
                [
                    'name' => 'home',
                ],
            ],
            [
                5,
                [
                    'name' => 'user.*',
                ],
            ],
            [
                1,
                [
                    'path' => '/user',
                ],
            ],
            [
                4,
                [
                    'path' => '/log.*',
                ],
            ],
            [
                6,
                [
                    'path' => '/',
                ],
            ],
            [
                6,
                [
                    'method' => 'GET',
                ],
            ],
            [
                1,
                [
                    'method' => Route::HTTP_METHOD_ANY,
                ],
            ],
            [
                6,
                [
                    'method' => 'get',
                ],
            ],
            [
                2,
                [
                    'method' => 'post',
                ],
            ],
            [
                6,
                [
                    'method' => ['POST', 'GET'],
                ],
            ],
            [
                2,
                [
                    'method' => ['POST'],
                ],
            ],
            [
                6,
                [
                    'method' => ['GET'],
                ],
            ],
            [
                1,
                [
                    'method' => ['PATCH'],
                ],
            ],
            [
                2,
                [
                    'method' => ['PATCH', 'POST'],
                ],
            ],
            [
                2,
                [
                    'method' => ['patch', 'post'],
                ],
            ],
            [
                1,
                [
                    'method' => ['patch'],
                ],
            ],
        ];
    }
}