<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\ConfigLoaderInterface;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function str_replace;
use function strtoupper;

class ListRoutesCommandTest extends TestCase
{
    /** @var (InputInterface&MockObject) */
    private $input;

    /** @var (ConsoleOutputInterface&MockObject) */
    private $output;

    /** @var (RouteCollector&MockObject) */
    private $routeCollection;

    /** @var (ContainerInterface&MockObject) */
    private $container;

    private ListRoutesCommand $command;

    protected function setUp(): void
    {
        /** @var ConfigLoaderInterface $configLoader */
        $configLoader = $this->createMock(ConfigLoaderInterface::class);

        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->input           = $this->createMock(InputInterface::class);
        $this->output          = $this->createMock(ConsoleOutputInterface::class);
        $this->routeCollection = $this->createMock(RouteCollector::class);
        $this->command         = new ListRoutesCommand($container, $configLoader);
    }

    /**
     * @throws ReflectionException
     */
    private function reflectExecuteMethod(): ReflectionMethod
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString(
            "Print the application's routing table.",
            $this->command->getDescription()
        );
    }

    /**
     * @return mixed
     * @throws ReflectionException
     * @param class-string $class
     */
    private function getConstantValue(string $const, string $class = ListRoutesCommand::class)
    {
        $r = new ReflectionClass($class);

        return $r->getConstant($const);
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals($this->getConstantValue('HELP'), $this->command->getHelp());
    }

    public function testConfigureSetsExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        $args = [
            'format',
            'has-middleware',
            'has-name',
            'has-path',
            'sort',
            'supports-method',
        ];

        foreach ($args as $arg) {
            self::assertTrue($definition->hasOption($arg));
            $option = $definition->getOption($arg);
            self::assertTrue($option->isValueRequired());
            self::assertTrue($option->acceptValue());
            self::assertEquals(
                $this->getConstantValue(
                    'HELP_OPT_' . strtoupper(str_replace('-', '_', $arg))
                ),
                $option->getDescription()
            );
        }
    }

    /**
     * @phpcs:ignore Generic.Files.LineLength.TooLong
     * @throws ReflectionException
     */
    public function testSuccessfulExecutionEmitsExpectedOutput(): void
    {
        $routes    = [
            new Route("/", new SimpleMiddleware(), ['GET'], 'home'),
            new Route("/", new ExpressMiddleware(), ['GET'], 'home'),
        ];
        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(RouteCollector::class)
            ->willReturn($collector);

        /** @var ConfigLoaderInterface&MockObject $configLoader */
        $configLoader  = $this->createMock(ConfigLoaderInterface::class);
        $this->command = new ListRoutesCommand($container, $configLoader);

        $outputFormatter = new OutputFormatter(false);

        $this->output
            ->method('getFormatter')
            ->willReturn($outputFormatter);

        // phpcs:disable Generic.Files.LineLength
        $this->output
            ->expects($this->atMost(7))
            ->method('writeln');
        // phpcs:enable
        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                'table',
                false,
                false,
                false,
                false
            );

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input,
                $this->output
            )
        );
    }

    public function testRendersAnEmptyResultWhenNoRoutesArePresent(): void
    {
        $collector = $this->createMock(RouteCollector::class);

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(RouteCollector::class)
            ->willReturn($collector);

        /** @var ConfigLoaderInterface&MockObject $configLoader */
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader
            ->expects($this->once())
            ->method('load');

        $this->command = new ListRoutesCommand($container, $configLoader);

        $this->input
            ->method('getOption')
            ->with('format')
            ->willReturnOnConsecutiveCalls('table', false);
        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with(
                "There are no routes in the application's routing table."
            );

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input,
                $this->output
            )
        );
    }

    public function testRendersRoutesAsJsonWhenFormatSetToJson(): void
    {
        $routes    = [
            new Route("/", new SimpleMiddleware(), ['GET'], 'home'),
            new Route("/", new ExpressMiddleware(), ['GET'], 'home'),
        ];
        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(RouteCollector::class)
            ->willReturn($collector);

        /** @var ConfigLoaderInterface&MockObject $configLoader */
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader
            ->expects($this->once())
            ->method('load');

        $this->command = new ListRoutesCommand($container, $configLoader);

        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                'json', // format
                false, // supports-method
                false, // has-middleware
                false, // has-name
                false, // has-path
                false
            );
        $this->output
            ->expects($this->atMost(2))
            ->method('writeln');

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input,
                $this->output
            )
        );
    }

    /**
     * @dataProvider invalidFormatDataProvider
     * @throws ReflectionException
     */
    public function testThatOnlyAllowedFormatsCanBeSupplied(string $format): void
    {
        $routes    = [
            new Route("/", new SimpleMiddleware(), ['GET'], 'home'),
            new Route("/", new ExpressMiddleware(), ['GET'], 'home'),
        ];
        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(RouteCollector::class)
            ->willReturn($collector);

        /** @var ConfigLoaderInterface $configLoader */
        $configLoader  = $this->createMock(ConfigLoaderInterface::class);
        $this->command = new ListRoutesCommand($container, $configLoader);

        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                $format, // format
                false, // has-middleware
                false, // supports-method
                false, // has-name
                false, // has-path
                false    // sort
            );
        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with(
                "Invalid output format supplied. Valid options are 'table' and 'json'"
            );

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            -1,
            $method->invoke(
                $this->command,
                $this->input,
                $this->output
            )
        );
    }

    /**
     * @return array<array-key,array<array-key,string>>
     */
    public function invalidFormatDataProvider(): array
    {
        return [
            [
                'rabbits',
            ],
            [
                'tables',
            ],
            [
                'toml',
            ],
            [
                'yaml',
            ],
        ];
    }

    /**
     * @dataProvider sortRoutingTableDataProvider
     * @throws ReflectionException
     */
    public function testCanSortResults(string $sortOrder, string $expectedOutput): void
    {
        $routes                = [
            new Route("/contact", new SimpleMiddleware(), ['GET'], 'contact'),
            new Route("/", new ExpressMiddleware(), ['GET'], 'home'),
        ];
        $this->routeCollection = $this->createMock(RouteCollector::class);
        $this->routeCollection
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->expects($this->once())
            ->method('get')
            ->willReturn(
                $this->routeCollection,
            );

        /** @var ConfigLoaderInterface $configLoader */
        $configLoader  = $this->createMock(ConfigLoaderInterface::class);
        $this->command = new ListRoutesCommand($this->container, $configLoader);

        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                'json', // format
                $sortOrder, // sort
                false, // supports-method
                false, // has-middleware
                false, // has-name
                false  // has-path
            );
        $this->output
            ->expects($this->atMost(2))
            ->method('writeln');

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input,
                $this->output
            )
        );
    }

    /**
     * @return array<array-key, array<array-key,string>>
     */
    public function sortRoutingTableDataProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength
        return [
            [
                'name',
                '[{"name":"contact","path":"\/contact","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\SimpleMiddleware"},{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware"}]',
            ],
            [
                'path',
                '[{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware"},{"name":"contact","path":"\/contact","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\SimpleMiddleware"}]',
            ],
        ];
        // phpcs:enable
    }

    /**
     * @dataProvider filterRoutingTableDataProvider
     */
    public function testCanFilterRoutingTable(array $filterOptions, string $expectedOutput): void
    {
        $routes = [
            new Route("/", new SimpleMiddleware(), ['GET'], 'home'),
            new Route("/", new ExpressMiddleware(), ['GET'], 'home'),
        ];

        $routeCollection = $this->createMock(RouteCollector::class);
        $routeCollection
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(RouteCollector::class)
            ->willReturn($routeCollection);

        /** @var ConfigLoaderInterface $configLoader */
        $configLoader  = $this->createMock(ConfigLoaderInterface::class);
        $this->command = new ListRoutesCommand($container, $configLoader);

        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                'json', // format
                false, // sort
                false, // supports-method
                $filterOptions['middleware'], // has-middleware
                false, // has-name
                false                           // has-path
            );

        $this->output
            ->expects($this->atMost(2))
            ->method('writeln');

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input,
                $this->output
            )
        );
    }

    /**
     * @return array<array-key, array<array-key, mixed>>
     */
    public static function filterRoutingTableDataProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength
        return [
            [
                ['middleware' => 'ExpressMiddleware'],
                '[{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware"}]',
            ],
        ];
        // phpcs:enable
    }
}
