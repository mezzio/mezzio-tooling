<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateMiddleware;

use Mezzio\Tooling\CreateMiddleware\CreateMiddleware;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommand;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateMiddlewareCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var ObjectProphecy<InputInterface> */
    private ObjectProphecy $input;

    /** @var ObjectProphecy<ConsoleOutputInterface> */
    private $output;

    private CreateMiddlewareCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new CreateMiddlewareCommand('');

        // Do not require the generated middleware during testing
        $r = new ReflectionProperty($this->command, 'requireMiddlewareBeforeGeneratingFactory');
        $r->setAccessible(true);
        $r->setValue($this->command, false);
    }

    private function reflectExecuteMethod(): ReflectionMethod
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    /**
     * @return ObjectProphecy<Application>
     */
    private function mockApplication()
    {
        $helperSet = $this->prophesize(HelperSet::class)->reveal();

        $factoryCommand = $this->prophesize(Command::class);
        $factoryCommand
            ->run(
                Argument::that(static function ($input) {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertStringContainsString('mezzio:factory:create', (string) $input);
                    Assert::assertStringContainsString('Foo\TestMiddleware', (string) $input);
                    return $input;
                }),
                $this->output->reveal()
            )
            ->willReturn(0);

        $application = $this->prophesize(Application::class);
        $application->getHelperSet()->willReturn($helperSet);
        $application->find('mezzio:factory:create')->will(static fn(): object => $factoryCommand->reveal());

        return $application;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString('Create a PSR-15 middleware', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals(CreateMiddlewareCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments(): void
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasArgument('middleware'));
        $argument = $definition->getArgument('middleware');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateMiddlewareCommand::HELP_ARG_MIDDLEWARE, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateMiddlewareCommand::HELP_OPT_NO_FACTORY, $option->getDescription());

        self::assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateMiddlewareCommand::HELP_OPT_NO_REGISTER, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages(): void
    {
        $this->command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateMiddleware::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestMiddleware', '')
            ->andReturn(__DIR__);

        $this->input->getArgument('middleware')->willReturn('Foo\TestMiddleware');
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating middleware Foo\TestMiddleware'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestMiddleware, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateMiddlewareToBubbleUp(): void
    {
        $this->command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateMiddleware::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestMiddleware', '')
            ->andThrow(CreateMiddlewareException::class, 'ERROR THROWN');

        $this->input->getArgument('middleware')->willReturn('Foo\TestMiddleware');
        $this->output
            ->writeln(Argument::containingString('Creating middleware Foo\TestMiddleware'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
