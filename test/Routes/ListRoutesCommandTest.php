<?php

namespace MezzioTest\Tooling\Routes;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ListRoutesCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|InputInterface
     */
    private $input;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ConsoleOutputInterface
     */
    private $output;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|RouteCollector
     */
    private  $routeCollection;

    private ListRoutesCommand $command;

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

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString(
            "Print the application's routing table.",
            $this->command->getDescription()
        );
    }

    /**
     * @return mixed
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

    public function testConfigureSetsExpectedArguments(): void
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
            self::assertEquals($this->getConstantValue('HELP_OPT_' . strtoupper(str_replace('-', '_', $arg))), $option->getDescription());
        }
    }
}
