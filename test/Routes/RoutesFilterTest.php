<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use ArrayIterator;
use Mezzio\Router\Route;
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

    public function testFiltersOutEmptyOptions(): void
    {
        $routeFilter = new RoutesFilter(
            new ArrayIterator($this->routes),
            [
                'middleware' => null,
                'name'       => '',
                'path'       => '/user',
            ]
        );

        $this->assertSame(
            ['path' => '/user'],
            $routeFilter->getFilterOptions()
        );
    }

    /**
     * @param array<string, mixed> $filterOptions
     * @dataProvider validFilterDataProvider
     */
    public function testCanFilterRoutesWithStringSearchExpression(
        int $expectedNumberOfRoutes,
        array $filterOptions = []
    ): void {
        $this->setUp();

        $routeFilter = new RoutesFilter(new ArrayIterator($this->routes), $filterOptions);

        $this->assertCount(
            $expectedNumberOfRoutes,
            $routeFilter,
            sprintf(
                'Filtered with %s',
                var_export($filterOptions, true)
            )
        );
    }

    /**
     * @psalm-return array<array-key, array{0: int, 1: array<string, mixed>}>
     */
    public static function validFilterDataProvider(): array
    {
        return [
            'middleware-simple-compound-name' => [
                5,
                [
                    'middleware' => 'ExpressMiddleware',
                ],
            ],
            'middleware-simple-class-name'    => [
                6,
                [
                    'middleware' => 'Tooling',
                ],
            ],
            'middleware-regex'                => [
                6,
                [
                    'middleware' => 'Tooling.*Middleware',
                ],
            ],
            'middleware-fqcn'                 => [
                5,
                [
                    'middleware' => ExpressMiddleware::class,
                ],
            ],
            'name-bare'                       => [
                1,
                [
                    'name' => 'home',
                ],
            ],
            'name-regex'                      => [
                5,
                [
                    'name' => 'user.*',
                ],
            ],
            'path-fq'                         => [
                1,
                [
                    'path' => '/user',
                ],
            ],
            'path-fq-regex'                   => [
                4,
                [
                    'path' => '/log.*',
                ],
            ],
            'path-root'                       => [
                6,
                [
                    'path' => '/',
                ],
            ],
            'method-get'                      => [
                6,
                [
                    'method' => 'GET',
                ],
            ],
            'method-any'                      => [
                6,
                [
                    'method' => Route::HTTP_METHOD_ANY,
                ],
            ],
            'method-get-lc'                   => [
                6,
                [
                    'method' => 'get',
                ],
            ],
            'method-post-lc'                  => [
                2,
                [
                    'method' => 'post',
                ],
            ],
            /*[
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
            ],*/
        ];
    }
}
