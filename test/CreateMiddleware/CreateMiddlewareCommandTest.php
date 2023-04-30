<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateMiddleware;

use Mezzio\Tooling\CreateMiddleware\CreateMiddleware;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommand;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    private CreateMiddlewareCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(ConsoleOutputInterface::class);

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

    /** @return Application&MockObject */
    private function mockApplication(): Application
    {
        $helperSet = $this->createMock(HelperSet::class);

        $factoryCommand = $this->createMock(Command::class);
        $factoryCommand
            ->method('run')
            ->with(
                self::callback(static function ($input): bool {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertStringContainsString('mezzio:factory:create', (string) $input);
                    Assert::assertStringContainsString('Foo\TestMiddleware', (string) $input);
                    return true;
                }),
                $this->output
            )
            ->willReturn(0);

        $application = $this->createMock(Application::class);
        $application->method('getHelperSet')->willReturn($helperSet);
        $application->method('find')->with('mezzio:factory:create')->willReturn($factoryCommand);

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
        $this->command->setApplication($this->mockApplication());

        $generator = Mockery::mock('overload:' . CreateMiddleware::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestMiddleware', '')
            ->andReturn(__DIR__);

        $this->input->method('getArgument')->with('middleware')->willReturn('Foo\TestMiddleware');
        $this->input->method('getOption')->willReturnMap([
            ['no-factory', false],
            ['no-register', false],
        ]);
        $this->output
            ->expects(self::atLeast(3))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Creating middleware Foo\TestMiddleware'),
                self::stringContains('Success'),
                self::stringContains('Created class Foo\TestMiddleware, in file ' . __DIR__),
            ));

        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input,
            $this->output
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateMiddlewareToBubbleUp(): void
    {
        $this->command->setApplication($this->mockApplication());

        $generator = Mockery::mock('overload:' . CreateMiddleware::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestMiddleware', '')
            ->andThrow(CreateMiddlewareException::class, 'ERROR THROWN');

        $this->input->method('getArgument')->with('middleware')->willReturn('Foo\TestMiddleware');

        $this->output
            ->expects(self::atLeastOnce())
            ->method('writeln')
            ->with(self::logicalAnd(
                self::stringContains('Creating middleware Foo\TestMiddleware'),
                self::logicalNot(self::stringContains('Success')),
            ));

        $method = $this->reflectExecuteMethod();

        $this->expectException(CreateMiddlewareException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $this->command,
            $this->input,
            $this->output
        );
    }
}
