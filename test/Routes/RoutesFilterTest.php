<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use ArrayIterator;
use Mezzio\Router\Route;
use Mezzio\Tooling\Routes\Filter\RouteFilterOptions;
use Mezzio\Tooling\Routes\Filter\RouteFilterOptionsInterface;
use Mezzio\Tooling\Routes\Filter\RoutesFilter;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\TestCase;

use function sprintf;
use function var_export;

class RoutesFilterTest extends TestCase
{
    /** @var array<int,Route> */
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

    /**
     * @dataProvider validFilterDataProvider
     */
    public function testCanFilterRoutesWithStringSearchExpression(
        int $expectedNumberOfRoutes,
        RouteFilterOptionsInterface $filterOptions
    ): void {
        $this->setUp();

        $routeFilter = new RoutesFilter(new ArrayIterator($this->routes), $filterOptions);

        $this->assertCount(
            $expectedNumberOfRoutes,
            $routeFilter,
            sprintf('Filtered with %s', var_export($filterOptions, true))
        );
    }

    /**
     * @psalm-return array<array-key, array{0: int, 1: RouteFilterOptionsInterface}>
     */
    public function validFilterDataProvider(): array
    {
        return [
            [
                5,
                new RouteFilterOptions(
                    middleware: 'ExpressMiddleware',
                    name: null,
                    path: null,
                    methods: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling',
                    name: null,
                    path: null,
                    methods: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling.*Middleware',
                    name: null,
                    path: null,
                    methods: null,
                ),
            ],
            [
                5,
                new RouteFilterOptions(
                    middleware: ExpressMiddleware::class,
                    name: null,
                    path: null,
                    methods: null,
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    name: 'home',
                    middleware: null,
                    path: null,
                    methods: null,
                ),
            ],
            [
                5,
                new RouteFilterOptions(
                    name: 'user.*',
                    middleware: null,
                    path: null,
                    methods: null,
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    path: '/user',
                    middleware: null,
                    name: null,
                    methods: null,
                ),
            ],
            [
                4,
                new RouteFilterOptions(
                    path: '/log.*',
                    middleware: null,
                    name: null,
                    methods: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    path: '/',
                    middleware: null,
                    name: null,
                    methods: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: ['get'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: Route::HTTP_METHOD_ANY,
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: 'get',
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: 'post',
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: ['POST', 'GET'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: ['POST'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: ['GET'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    methods: ['PATCH'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: ['PATCH', 'POST'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: ['patch', 'post'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    methods: ['patch'],
                    middleware: null,
                    name: null,
                    path: null,
                ),
            ],
        ];
    }
}
