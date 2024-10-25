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
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling',
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling.*Middleware',
                ),
            ],
            [
                5,
                new RouteFilterOptions(
                    middleware: ExpressMiddleware::class,
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    name: 'home',
                ),
            ],
            [
                5,
                new RouteFilterOptions(
                    name: 'user.*',
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    path: '/user',
                ),
            ],
            [
                4,
                new RouteFilterOptions(
                    path: '/log.*',
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    path: '/',
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: ['get'],
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: Route::HTTP_METHOD_ANY,
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: 'get',
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: 'post',
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: ['POST', 'GET'],
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: ['POST'],
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    methods: ['GET'],
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    methods: ['PATCH'],
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: ['PATCH', 'POST'],
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    methods: ['patch', 'post'],
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    methods: ['patch'],
                ),
            ],
        ];
    }
}
