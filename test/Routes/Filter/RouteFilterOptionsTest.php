<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes\Filter;

use Mezzio\Tooling\Routes\Filter\RouteFilterOptions;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use PHPUnit\Framework\TestCase;

class RouteFilterOptionsTest extends TestCase
{
    public function testCanInitialiseOptionsCorrectly(): void
    {
        $options = [
            'middleware' => ExpressMiddleware::class,
            'name'       => 'home',
            'path'       => '/user',
            'methods'    => ['GET'],
        ];

        $routeFilterOptions = new RouteFilterOptions(
            $options['middleware'],
            $options['name'],
            $options['path'],
            $options['methods']
        );

        $this->assertTrue($routeFilterOptions->has('middleware'));
        $this->assertTrue($routeFilterOptions->has('name'));
        $this->assertTrue($routeFilterOptions->has('path'));
        $this->assertTrue($routeFilterOptions->has('methods'));

        $this->assertSame($options['middleware'], $routeFilterOptions->getMiddleware());
        $this->assertSame($options['name'], $routeFilterOptions->getName());
        $this->assertSame($options['path'], $routeFilterOptions->getPath());
        $this->assertSame($options['methods'], $routeFilterOptions->getMethods());
    }

    /**
     * @dataProvider initDataProvider
     */
    public function testCanGetArrayRepresentation(array $options, array $expectedResult): void
    {
        $routeFilterOptions = new RouteFilterOptions(
            $options['middleware'] ?? '',
            $options['name'] ?? '',
            $options['path'] ?? '',
            $options['methods'] ?? []
        );

        $this->assertSame($expectedResult, $routeFilterOptions->toArray());
    }

    /**
     * @return array[]
     */
    public function initDataProvider(): array
    {
        return [
            [
                [
                    'middleware' => ExpressMiddleware::class,
                    'name'       => 'home',
                    'path'       => '/user',
                    'methods'    => ['GET'],
                ],
                [
                    'middleware' => ExpressMiddleware::class,
                    'name'       => 'home',
                    'path'       => '/user',
                    'methods'    => ['GET'],
                ],
            ],
            [
                [
                    'middleware' => ExpressMiddleware::class,
                    'methods'    => ['GET'],
                ],
                [
                    'middleware' => ExpressMiddleware::class,
                    'methods'    => ['GET'],
                ],
            ],
            [
                [
                    'path'    => '/user',
                    'methods' => ['GET'],
                ],
                [
                    'path'    => '/user',
                    'methods' => ['GET'],
                ],
            ],
            [
                [
                    'path'    => '/user',
                    'methods' => 'GET',
                ],
                [
                    'path'    => '/user',
                    'methods' => ['GET'],
                ],
            ],
            [
                [
                    'middleware' => ExpressMiddleware::class,
                    'name'       => 'home',
                ],
                [
                    'middleware' => ExpressMiddleware::class,
                    'name'       => 'home',
                ],
            ],
            [
                [],
                [],
            ],
        ];
    }
}
