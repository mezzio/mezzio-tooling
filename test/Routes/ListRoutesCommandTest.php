<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    private ListRoutesCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(ConsoleOutputInterface::class);

        $routes = [
            new Route("/", new SimpleMiddleware(), ['GET'], 'home'),
            new Route("/", new ExpressMiddleware(), ['GET'], 'home'),
        ];

        $this->routeCollection = $this->createMock(RouteCollector::class);
        $this->routeCollection
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->command = new ListRoutesCommand($this->routeCollection);
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
        $outputFormatter = new OutputFormatter(false);

        $this->output
            ->method('getFormatter')
            ->willReturn($outputFormatter);

        // phpcs:disable Generic.Files.LineLength
        $this->output
            ->method('writeln')
            ->withConsecutive(
                ["+------+------+---------+------------<fg=black;bg=white;options=bold> Routes </>------------------------------------+"],
                ["|<info> Name </info>|<info> Path </info>|<info> Methods </info>|<info> Middleware                                             </info>|"],
                ["+------+------+---------+--------------------------------------------------------+"],
                ["| home | /    | GET     | MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware  |"],
                ["| home | /    | GET     | MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware |"],
                ["+------+------+---------+--------------------------------------------------------+"],
                ["Listing the application's routing table in table format."],
            );
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
        $this->routeCollection = $this->createMock(RouteCollector::class);
        $this->routeCollection
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn([]);

        $this->command = new ListRoutesCommand($this->routeCollection);

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
        // phpcs:disable Generic.Files.LineLength
        $this->output
            ->method('writeln')
            ->withConsecutive(
                [
                    '[{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\SimpleMiddleware"},{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware"}]',
                ],
                ["Listing the application's routing table in JSON format."],
            );
        // phpcs:enable

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
        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                $format, // format
                false, // has-middleware
                false, // supports-method
                false, // has-name
                false, // has-path
                false   // sort
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
     * @return array[]
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
            ->method('getRoutes')
            ->willReturn($routes);

        $this->command = new ListRoutesCommand($this->routeCollection);

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
            ->method('writeln')
            ->withConsecutive(
                [$expectedOutput],
                ["Listing the application's routing table in JSON format."]
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

    /**
     * @return array[]
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
            ->method('getRoutes')
            ->willReturn($routes);

        $this->command = new ListRoutesCommand($routeCollection);

        /*if (! empty($filterOptions['middleware'])) {
            $this->input
                ->expects($this->once())
                ->method('getOption')
                ->with('has-middleware')
                ->willReturn($filterOptions['middleware']);
        }*/

        $this->input
            ->method('getOption')
            ->willReturnOnConsecutiveCalls(
                'json', // format
                false, // sort
                false, // supports-method
                $filterOptions['middleware'], // has-middleware
                false, // has-name
                false  // has-path
            );

        $this->output
            ->method('writeln')
            ->withConsecutive(
                [$expectedOutput],
                ["Listing the application's routing table in JSON format."]
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

    /**
     * @return array[]
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
