<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use ArrayIterator;
use Mezzio\Router\Route;
use Mezzio\Tooling\Routes\Filter\RouteFilterOptions;
use Mezzio\Tooling\Routes\Filter\RoutesFilter;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function count;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function var_export;

/** @psalm-suppress InternalClass, InternalMethod */
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
                'user.profile',
            ),
            new Route(
                "/",
                new ExpressMiddleware(),
                ['GET'],
                'home',
            ),
            new Route(
                "/login",
                new ExpressMiddleware(),
                ['GET'],
                'user.login',
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                ['GET'],
                'user.logout',
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                ['GET', 'POST'],
                'user.logout',
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                Route::HTTP_METHOD_ANY,
                'user.logout',
            ),
        ];
    }

    public function testAllRoutesWillBeReturnedWhenThereAreNoOptionsSet(): void
    {
        self::assertCount(
            count($this->routes),
            new RoutesFilter(new ArrayIterator($this->routes), new RouteFilterOptions(
                null,
                null,
                null,
                [],
            )),
        );
    }

    #[DataProvider('validFilterDataProvider')]
    public function testCanFilterRoutesWithStringSearchExpression(
        int $expectedNumberOfRoutes,
        RouteFilterOptions $filterOptions,
    ): void {
        $this->setUp();

        $routeFilter = new RoutesFilter(new ArrayIterator($this->routes), $filterOptions);

        self::assertCount(
            $expectedNumberOfRoutes,
            $routeFilter,
            sprintf('Filtered with %s', var_export($filterOptions, true)),
        );
    }

    /**
     * @psalm-return array<array-key, array{0: int, 1: RouteFilterOptions}>
     */
    public static function validFilterDataProvider(): array
    {
        return [
            'middleware-simple-compound-name' => [
                5,
                new RouteFilterOptions(
                    middleware: 'ExpressMiddleware',
                    name: null,
                    path: null,
                    methods: [],
                ),
            ],
            'middleware-simple-class-name'    => [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling',
                    name: null,
                    path: null,
                    methods: [],
                ),
            ],
            'middleware-regex'                => [
                6,
                new RouteFilterOptions(
                    middleware: 'Tooling.*Middleware',
                    name: null,
                    path: null,
                    methods: [],
                ),
            ],
            'middleware-fqcn'                 => [
                5,
                new RouteFilterOptions(
                    middleware: ExpressMiddleware::class,
                    name: null,
                    path: null,
                    methods: [],
                ),
            ],
            'name-bare'                       => [
                1,
                new RouteFilterOptions(
                    middleware: null,
                    name: 'home',
                    path: null,
                    methods: [],
                ),
            ],
            'name-regex'                      => [
                5,
                new RouteFilterOptions(
                    middleware: null,
                    name: 'user.*',
                    path: null,
                    methods: [],
                ),
            ],
            'path-fq'                         => [
                1,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: '/user',
                    methods: [],
                ),
            ],
            'path-fq-regex'                   => [
                4,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: '/log.*',
                    methods: [],
                ),
            ],
            'path-root'                       => [
                6,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: '/',
                    methods: [],
                ),
            ],
            'method-get'                      => [
                6,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['get'],
                ),
            ],
            'method-any'                      => [
                6,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: [],
                ),
            ],
            'method-get-lc'                   => [
                6,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['get'],
                ),
            ],
            'method-post-lc'                  => [
                2,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['post'],
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['POST', 'GET'],
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['POST'],
                ),
            ],
            [
                6,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['GET'],
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['PATCH'],
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['PATCH', 'POST'],
                ),
            ],
            [
                2,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['patch', 'post'],
                ),
            ],
            [
                1,
                new RouteFilterOptions(
                    middleware: null,
                    name: null,
                    path: null,
                    methods: ['patch'],
                ),
            ],
        ];
    }

    public function testInvalidMiddlewareRegexIsIgnored(): void
    {
        // preg_match will raise a warning here
        // phpcs:disable
        /** @psalm-suppress InvalidArgument */
        set_error_handler(static function (): void {});
        // phpcs:enable

        $filter = new RoutesFilter(new ArrayIterator($this->routes), new RouteFilterOptions(
            '^^!(foo',
            null,
            null,
            [],
        ));

        self::assertCount(0, $filter);

        restore_error_handler();
    }

    public function testInvalidNameRegexIsIgnored(): void
    {
        // preg_match will raise a warning here
        // phpcs:disable
        /** @psalm-suppress InvalidArgument */
        set_error_handler(static function (): void {});
        // phpcs:enable

        $filter = new RoutesFilter(new ArrayIterator($this->routes), new RouteFilterOptions(
            null,
            '^^!(foo',
            null,
            [],
        ));

        self::assertCount(0, $filter);

        restore_error_handler();
    }
}
