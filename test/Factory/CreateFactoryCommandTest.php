<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ClassNotFoundException;
use Mezzio\Tooling\Factory\ConfigInjector;
use Mezzio\Tooling\Factory\Create;
use Mezzio\Tooling\Factory\CreateFactoryCommand;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateFactoryCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    /** @var CreateFactoryCommand */
    private $command;

    protected function setUp() : void
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new CreateFactoryCommand('factory:create');
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        self::assertStringContainsString('Create a factory', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        self::assertEquals(CreateFactoryCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasArgument('class'));
        $argument = $definition->getArgument('class');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateFactoryCommand::HELP_ARG_CLASS, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptions()
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        self::assertEquals(CreateFactoryCommand::HELP_OPT_NO_REGISTER, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $generator = Mockery::mock('overload:' . Create::class);
        $generator->shouldReceive('createForClass')
            ->once()
            ->with('Foo\TestHandler')
            ->andReturn(__DIR__);

        $generator = Mockery::mock('overload:' . ConfigInjector::class);
        $generator->shouldReceive('injectFactoryForClass')
            ->once()
            ->with('Foo\TestHandlerFactory', 'Foo\TestHandler')
            ->andReturn('some-file-name');

        $this->input->getArgument('class')->willReturn('Foo\TestHandler');
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating factory for class Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Registering factory with container'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created factory class Foo\TestHandlerFactory, in file ' . __DIR__))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Registered factory to container'))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateToBubbleUp()
    {
        $generator = Mockery::mock('overload:' . Create::class);
        $generator->shouldReceive('createForClass')
            ->once()
            ->with('Foo\TestHandler')
            ->andThrow(ClassNotFoundException::class, 'ERROR THROWN');

        $this->input->getArgument('class')->willReturn('Foo\TestHandler');
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating factory for class Foo\TestHandler'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod();

        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
