<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\ConfigLoaderInterface;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function array_reverse;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class ListRoutesCommandTest extends TestCase
{
    private RouteCollector&MockObject $routeCollector;
    private CommandTester $tester;
    private ListRoutesCommand $command;

    protected function setUp(): void
    {
        $configLoader         = $this->createMock(ConfigLoaderInterface::class);
        $this->routeCollector = $this->createMock(RouteCollector::class);
        $this->command        = new ListRoutesCommand(
            $this->routeCollector,
            $configLoader,
        );
        $this->tester         = new CommandTester($this->command);
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString(
            "Print the application's routing table.",
            $this->command->getDescription(),
        );
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertNotEmpty($this->command->getHelp());
    }

    public function testExpectedTableOutputWhenThereAreNoArguments(): void
    {
        $this->routeCollector->expects(self::once())
            ->method('getRoutes')
            ->willReturn([
                new Route('/', new ExpressMiddleware(), ['GET'], 'home'),
            ]);

        self::assertSame(
            Command::SUCCESS,
            $this->tester->execute([]),
        );

        self::assertSame(
            <<<'EOF'
            +------+------+---------+------------ Routes ------------------------------------+
            | Name | Path | Methods | Middleware                                             |
            +------+------+---------+--------------------------------------------------------+
            | home | /    | GET     | MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware |
            +------+------+---------+--------------------------------------------------------+
            
            EOF,
            $this->tester->getDisplay(),
        );
    }

    public function testNameFilterIsApplied(): void
    {
        $this->routeCollector->expects(self::once())
            ->method('getRoutes')
            ->willReturn([
                new Route('/', new ExpressMiddleware(), ['GET'], 'home'),
                new Route('/gah', new ExpressMiddleware(), ['GET'], 'work'),
            ]);

        self::assertSame(
            Command::SUCCESS,
            $this->tester->execute(['--format' => 'json', '--has-name' => 'ho*']),
        );

        self::assertJsonStringEqualsJsonString(
            json_encode([
                [
                    'name'       => 'home',
                    'path'       => '/',
                    'methods'    => 'GET',
                    'middleware' => ExpressMiddleware::class,
                ],
            ]),
            $this->tester->getDisplay(),
        );
    }

    public function testPathFilterIsApplied(): void
    {
        $this->routeCollector->expects(self::once())
            ->method('getRoutes')
            ->willReturn([
                new Route('/foo', new ExpressMiddleware(), ['GET'], 'home'),
                new Route('/bar', new ExpressMiddleware(), ['GET'], 'work'),
            ]);

        self::assertSame(
            Command::SUCCESS,
            $this->tester->execute(['--format' => 'json', '--has-path' => '/ba']),
        );

        self::assertJsonStringEqualsJsonString(
            json_encode([
                [
                    'name'       => 'work',
                    'path'       => '/bar',
                    'methods'    => 'GET',
                    'middleware' => ExpressMiddleware::class,
                ],
            ]),
            $this->tester->getDisplay(),
        );
    }

    public function testAnEmptyRouteListIsAnError(): void
    {
        $this->routeCollector->expects(self::once())
            ->method('getRoutes')
            ->willReturn([]);

        self::assertSame(
            Command::FAILURE,
            $this->tester->execute([]),
        );

        self::assertStringContainsString(
            "There are no routes in the application's routing table.",
            $this->tester->getDisplay(),
        );
    }

    #[DataProvider('invalidFormatDataProvider')]
    public function testUnrecognizedFormatsAreIgnoredAndATableIsOutput(string $format): void
    {
        $this->routeCollector->expects(self::once())
            ->method('getRoutes')
            ->willReturn([
                new Route('/', new ExpressMiddleware(), ['GET'], 'home'),
            ]);

        self::assertSame(
            Command::FAILURE,
            $this->tester->execute([
                '--format' => $format,
            ]),
        );

        self::assertStringContainsString(
            "Invalid output format supplied. Valid options are 'table' and 'json'",
            $this->tester->getDisplay(),
        );
    }

    /**
     * @return array<array-key,array<array-key,string>>
     */
    public static function invalidFormatDataProvider(): array
    {
        return [
            ['rabbits'],
            ['tables'],
            ['toml'],
            ['yaml'],
        ];
    }

    /**
     * @param list<Route> $routes
     */
    #[DataProvider('sortRoutingTableDataProvider')]
    public function testCanSortResults(string $sortOrder, array $routes, string $expectJson): void
    {
        $this->routeCollector->expects(self::once())
            ->method('getRoutes')
            ->willReturn($routes);

        self::assertSame(
            Command::SUCCESS,
            $this->tester->execute([
                '--format' => 'json',
                '--sort'   => $sortOrder,
            ])
        );

        self::assertJsonStringEqualsJsonString(
            $expectJson,
            $this->tester->getDisplay(),
        );
    }

    /**
     * @return list<array{0: string, 1: list<Route>, 2: string}>
     */
    public static function sortRoutingTableDataProvider(): array
    {
        $routes = [
            new Route("/contact", new SimpleMiddleware(), ['GET'], 'contact'),
            new Route("/", new SimpleMiddleware(), ['GET'], 'home'),
        ];

        $routeArray = [
            [
                'name'       => 'contact',
                'path'       => '/contact',
                'methods'    => 'GET',
                'middleware' => SimpleMiddleware::class,
            ],
            [
                'name'       => 'home',
                'path'       => '/',
                'methods'    => 'GET',
                'middleware' => SimpleMiddleware::class,
            ],
        ];

        $contactFirst = json_encode($routeArray, JSON_THROW_ON_ERROR);
        $contactLast  = json_encode(array_reverse($routeArray), JSON_THROW_ON_ERROR);

        return [
            [
                'name',
                $routes,
                $contactFirst,
            ],
            [
                'path',
                $routes,
                $contactLast,
            ],
            [
                'not-recognised', // test default to 'name'
                $routes,
                $contactFirst,
            ],
        ];
    }
}
