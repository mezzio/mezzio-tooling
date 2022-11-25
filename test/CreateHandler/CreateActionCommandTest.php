<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateHandler;

use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Tooling\CreateHandler\CreateActionCommand;
use Mezzio\Tooling\CreateHandler\CreateHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
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
class CreateActionCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @psalm-var ContainerInterface&MockObject */
    private ContainerInterface $container;

    /** @psalm-var InputInterface&MockObject */
    private InputInterface $input;

    /** @psalm-var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    protected function setUp(): void
    {
        $this->input     = $this->createMock(InputInterface::class);
        $this->output    = $this->createMock(ConsoleOutputInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function createCommand(): CreateActionCommand
    {
        return new CreateActionCommand(
            $this->container,
            ''
        );
    }

    /**
     * Allows disabling of the `require` statement in the command class when testing.
     */
    private function disableRequireHandlerDirective(CreateActionCommand $command): void
    {
        $r = new ReflectionProperty($command, 'requireHandlerBeforeGeneratingFactory');
        $r->setAccessible(true);
        $r->setValue($command, false);
    }

    private function reflectExecuteMethod(CreateActionCommand $command): ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    /** @return Application&MockObject */
    private function mockApplication(string $forService = 'Foo\TestAction'): Application
    {
        $helperSet = $this->createMock(HelperSet::class);

        $factoryCommand = $this->createMock(Command::class);
        $factoryCommand
            ->method('run')
            ->with(
                self::callback(static function ($input) use ($forService): bool {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertStringContainsString('mezzio:factory:create', (string) $input);
                    Assert::assertStringContainsString($forService, (string) $input);
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

    public function testConfigureSetsExpectedDescriptionWhenRequestingAnAction(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command = $this->createCommand();
        self::assertStringContainsString(CreateActionCommand::HELP_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAnAction(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command = $this->createCommand();
        self::assertEquals(CreateActionCommand::HELP, $command->getHelp());
    }

    public function testConfigureSetsExpectedArgumentsWhenRequestingAnAction(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command    = $this->createCommand();
        $definition = $command->getDefinition();
        self::assertTrue($definition->hasArgument('action'));
        $argument = $definition->getArgument('action');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateActionCommand::HELP_ARG_ACTION, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAnAction(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command    = $this->createCommand();
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateActionCommand::HELP_OPT_NO_FACTORY, $option->getDescription());

        self::assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateActionCommand::HELP_OPT_NO_REGISTER, $option->getDescription());

        self::assertFalse($definition->hasOption('without-template'));
        self::assertFalse($definition->hasOption('with-template-namespace'));
        self::assertFalse($definition->hasOption('with-template-name'));
        self::assertFalse($definition->hasOption('with-template-extension'));
    }

    public function testConfigureSetsExpectedTemplateOptionsWhenRequestingAnActionAndRendererIsPresent(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command    = $this->createCommand();
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('without-template'));
        $option = $definition->getOption('without-template');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateActionCommand::HELP_OPTION_WITHOUT_TEMPLATE, $option->getDescription());

        self::assertTrue($definition->hasOption('with-template-namespace'));
        $option = $definition->getOption('with-template-namespace');
        self::assertTrue($option->acceptValue());
        self::assertEquals(CreateActionCommand::HELP_OPTION_WITH_TEMPLATE_NAMESPACE, $option->getDescription());

        self::assertTrue($definition->hasOption('with-template-name'));
        $option = $definition->getOption('with-template-name');
        self::assertTrue($option->acceptValue());
        self::assertEquals(CreateActionCommand::HELP_OPTION_WITH_TEMPLATE_NAME, $option->getDescription());

        self::assertTrue($definition->hasOption('with-template-extension'));
        $option = $definition->getOption('with-template-extension');
        self::assertTrue($option->acceptValue());
        self::assertEquals(CreateActionCommand::HELP_OPTION_WITH_TEMPLATE_EXTENSION, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessagesWhenRequestingAnAction(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication('Foo\TestAction'));

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestAction', [])
            ->andReturn(__DIR__);

        $this->input->method('getArgument')->with('action')->willReturn('Foo\TestAction');
        $this->input->method('getOption')->willReturnMap([
            ['no-factory', false],
            ['no-register', false],
        ]);
        $this->output
            ->expects(self::atLeast(3))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Creating action Foo\TestAction'),
                self::stringContains('Success'),
                self::stringContains('Created class Foo\TestAction, in file ' . __DIR__)
            ));

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
        ));
    }
}
