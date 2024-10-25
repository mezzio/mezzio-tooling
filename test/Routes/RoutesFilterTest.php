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
    public static function validFilterDataProvider(): array
    {
        return [
            'middleware-simple-compound-name' => [
                5,
                new RouteFilterOptions(
                    middleware: 'ExpressMiddleware',
                ),
            ],
            'middleware-simple-class-name'    => [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling',
                ),
            ],
            'middleware-regex'                => [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling.*Middleware',
                ),
            ],
            'middleware-fqcn'                 => [
                5,
                new RouteFilterOptions(
                    middleware: ExpressMiddleware::class,
                ),
            ],
            'name-bare'                       => [
                1,
                new RouteFilterOptions(
                    name: 'home',
                ),
            ],
            'name-regex'                      => [
                5,
                new RouteFilterOptions(
                    name: 'user.*',
                ),
            ],
            'path-fq'                         => [
                1,
                new RouteFilterOptions(
                    path: '/user',
                ),
            ],
            'path-fq-regex'                   => [
                4,
                new RouteFilterOptions(
                    path: '/log.*',
                ),
            ],
            'path-root'                       => [
                6,
                new RouteFilterOptions(
                    path: '/',
                ),
            ],
            'method-get'                      => [
                6,
                new RouteFilterOptions(
                    methods: ['get'],
                ),
            ],
            'method-any'                      => [
                6,
                new RouteFilterOptions(
                    methods: Route::HTTP_METHOD_ANY,
                ),
            ],
            'method-get-lc'                   => [
                6,
                new RouteFilterOptions(
                    methods: 'get',
                ),
            ],
            'method-post-lc'                  => [
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
