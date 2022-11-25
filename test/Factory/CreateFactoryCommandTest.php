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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    private CreateFactoryCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(ConsoleOutputInterface::class);

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
        $generator = $this->createMock(Create::class);
        $generator
            ->expects(self::atLeastOnce())
            ->method('createForClass')
            ->with('Foo\TestHandler')
            ->willReturn(__DIR__);

        $injector = Mockery::mock('overload:' . ConfigInjector::class);
        $injector->shouldReceive('injectFactoryForClass')
            ->once()
            ->with('Foo\TestHandlerFactory', 'Foo\TestHandler')
            ->andReturn('some-file-name');

        $this->input->method('getArgument')->with('class')->willReturn('Foo\TestHandler');
        $this->input->method('getOption')->with('no-register')->willReturn(false);

        $this->output
            ->expects(self::atLeast(5))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Creating factory for class Foo\TestHandler'),
                self::stringContains('Registering factory with container'),
                self::stringContains('Success'),
                self::stringContains('Created factory class Foo\TestHandlerFactory, in file ' . __DIR__),
                self::stringContains('Registered factory to container'),
            ));

        $command = new CreateFactoryCommand($generator, '');

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateToBubbleUp(): void
    {
        $generator = $this->createMock(Create::class);
        $generator->expects(self::atLeastOnce())
            ->method('createForClass')
            ->with('Foo\TestHandler')
            ->willThrowException(new ClassNotFoundException());

        $this->input->method('getArgument')->with('class')->willReturn('Foo\TestHandler');
        $this->input->method('getOption')->with('no-register')->willReturn(false);

        $this->output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->with(self::logicalAnd(
                self::stringContains('Creating factory for class Foo\TestHandler'),
                self::logicalNot(self::stringContains('Success')),
            ));

        $command = new CreateFactoryCommand($generator, '');

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(ClassNotFoundException::class);

        $method->invoke(
            $command,
            $this->input,
            $this->output
        );
    }
}
