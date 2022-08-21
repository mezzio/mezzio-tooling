<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function str_replace;
use function strtoupper;

class ListRoutesCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|InputInterface */
    private $input;

    /** @var ObjectProphecy|OutputInterface */
    private $output;

    /** @var ObjectProphecy|RouteCollector */
    private $routeCollection;

    private ListRoutesCommand $command;

    private array $routes;

    protected function setUp(): void
    {
        $this->input  = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->routes = [
            new Route(
                "/",
                new SimpleMiddleware(),
                ['GET'],
                'home'
            ),
            new Route(
                "/",
                new ExpressMiddleware(),
                ['GET'],
                'home'
            ),
        ];

        $this->routeCollection = $this->prophesize(RouteCollector::class);
        $this->routeCollection
            ->getRoutes()
            ->willReturn($this->routes);

        $this->command = new ListRoutesCommand($this->routeCollection->reveal());
    }

    /**
     * @return ObjectProphecy|Application
     */
    private function mockApplication(RouteCollector $routeCollector)
    {
        $helperSet = $this->prophesize(HelperSet::class)->reveal();

        $factoryCommand = $this->prophesize(ListRoutesCommand::class);
        $factoryCommand
            ->run(
                Argument::that(function ($input) {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertStringContainsString('mezzio:routes:list', (string) $input);
                    return $input;
                }),
                $this->output->reveal()
            )
            ->willReturn(0);
        $factoryCommand->routeCollector = $routeCollector;

        /** @var Application|ObjectProphecy $application */
        $application = $this->prophesize(Application::class);
        $application->getHelperSet()->willReturn($helperSet);
        $application->find('mezzio:routes:list')->will([$factoryCommand, 'reveal']);

        return $application;
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
     * @phpcs:ignore
     * @throws ReflectionException
     */
    public function testSuccessfulExecutionEmitsExpectedOutput(): void
    {
        $outputFormatter = new OutputFormatter(false);

        $this->output
            ->writeln(Argument::containingString(
                "Listing the application's routing table in table format."
            ))
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    "+------+------+---------+------------<fg=black;bg=white;options=bold> Routes </>------------------------------------+"
                )
            )
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    "|<info> Name </info>|<info> Path </info>|<info> Methods </info>|<info> Middleware                                             </info>|"
                )
            )
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    "| home | /    | GET     | MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware  |"
                )
            )
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    "| home | /    | GET     | MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware |"
                )
            )
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    "+------+------+---------+--------------------------------------------------------+"
                )
            )
            ->shouldBeCalled();
        $this->output
            ->getFormatter()
            ->shouldBeCalled()
            ->willReturn($outputFormatter);

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input->reveal(),
                $this->output->reveal()
            )
        );
    }

    public function testRendersAnEmptyResultWhenNoRoutesArePresent(): void
    {
        $this->routeCollection = $this->prophesize(RouteCollector::class);
        $this->routeCollection
            ->getRoutes()
            ->willReturn([]);

        $this->command = new ListRoutesCommand($this->routeCollection->reveal());

        $this->input
            ->getOption('format')
            ->willReturn(false);
        $this->output
            ->writeln(Argument::containingString(
                "There are no routes in the application's routing table."
            ))
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    ""
                )
            )
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input->reveal(),
                $this->output->reveal()
            )
        );
    }

    public function testRendersRoutesAsJsonWhenFormatSetToJson(): void
    {
        $this->input
            ->getOption('format')
            ->willReturn('json');
        $this->output
            ->writeln(Argument::containingString(
                "Listing the application's routing table in JSON format."
            ))
            ->shouldBeCalled();
        $this->output
            ->writeln(
                Argument::containingString(
                    '[{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\SimpleMiddleware"},{"name":"home","path":"\/","methods":"GET","middleware":"MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware"}]'
                )
            )
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            0,
            $method->invoke(
                $this->command,
                $this->input->reveal(),
                $this->output->reveal()
            )
        );
    }

    /**
     * @dataProvider invalidFormatDataProvider
     * @throws ReflectionException
     */
    public function testThatOnlyAllowedFormatsCanBeSupplied($format): void
    {
        $this->input
            ->getOption('format')
            ->willReturn($format);
        $this->output
            ->writeln(Argument::containingString(
                "Invalid output format supplied. Valid options are 'table' and 'json'"
            ))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        self::assertSame(
            -1,
            $method->invoke(
                $this->command,
                $this->input->reveal(),
                $this->output->reveal()
            )
        );
    }

    public function invalidFormatDataProvider(): array
    {
        return [
            [
                'rabbits'
            ],
            [
                'tables'
            ],
            [
                'toml'
            ],
            [
                'yaml'
            ],
        ];
    }
}
