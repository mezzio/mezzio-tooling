<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ClassNotFoundException;
use Mezzio\Tooling\Factory\ConfigInjector;
use Mezzio\Tooling\Factory\Create;
use Mezzio\Tooling\Factory\CreateFactoryCommand;
use Mezzio\Tooling\Factory\FactoryClassGenerator;
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

    protected function setUp(): void
    {
        $this->input  = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new CreateFactoryCommand(
            new Create(new FactoryClassGenerator()),
            ''
        );
    }

    private function reflectExecuteMethod(CreateFactoryCommand $command): ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString('Create a factory', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals(CreateFactoryCommand::HELP, $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments(): void
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasArgument('class'));
        $argument = $definition->getArgument('class');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateFactoryCommand::HELP_ARG_CLASS, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        self::assertEquals(CreateFactoryCommand::HELP_OPT_NO_REGISTER, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages(): void
    {
        $generator = $this->prophesize(Create::class);
        $generator->createForClass('Foo\TestHandler')->willReturn(__DIR__)->shouldBeCalled();

        $injector = Mockery::mock('overload:' . ConfigInjector::class);
        $injector->shouldReceive('injectFactoryForClass')
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

        $command = new CreateFactoryCommand($generator->reveal(), '');

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateToBubbleUp(): void
    {
        $generator = $this->prophesize(Create::class);
        $generator->createForClass('Foo\TestHandler')->willThrow(ClassNotFoundException::class)->shouldBeCalled();

        $this->input->getArgument('class')->willReturn('Foo\TestHandler');
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating factory for class Foo\TestHandler'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $command = new CreateFactoryCommand($generator->reveal(), '');

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(ClassNotFoundException::class);

        $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
