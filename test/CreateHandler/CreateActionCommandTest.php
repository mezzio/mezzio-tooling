<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateHandler;

use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Tooling\CreateHandler\CreateHandler;
use Mezzio\Tooling\CreateHandler\CreateActionCommand;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
    use ProphecyTrait;

    /** @var ContainerInterface&ObjectProphecy */
    private $container;

    /** @var InputInterface&ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface&ObjectProphecy */
    private $output;

    protected function setUp() : void
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function createCommand() : CreateActionCommand
    {
        return new CreateActionCommand(
            $this->container->reveal(),
            ''
        );
    }

    /**
     * Allows disabling of the `require` statement in the command class when testing.
     */
    private function disableRequireHandlerDirective(CreateActionCommand $command) : void
    {
        $r = new ReflectionProperty($command, 'requireHandlerBeforeGeneratingFactory');
        $r->setAccessible(true);
        $r->setValue($command, false);
    }

    private function reflectExecuteMethod(CreateActionCommand $command) : ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    /**
     * @return ObjectProphecy|Application
     */
    private function mockApplication(string $forService = 'Foo\TestAction')
    {
        $helperSet = $this->prophesize(HelperSet::class)->reveal();

        $factoryCommand = $this->prophesize(Command::class);
        $factoryCommand
            ->run(
                Argument::that(function ($input) use ($forService) {
                    Assert::assertInstanceOf(ArrayInput::class, $input);
                    Assert::assertStringContainsString('mezzio:factory:create', (string) $input);
                    Assert::assertStringContainsString($forService, (string) $input);
                    return $input;
                }),
                $this->output->reveal()
            )
            ->willReturn(0);

        $application = $this->prophesize(Application::class);
        $application->getHelperSet()->willReturn($helperSet);
        $application->find('mezzio:factory:create')->will([$factoryCommand, 'reveal']);

        return $application;
    }

    public function testConfigureSetsExpectedDescriptionWhenRequestingAnAction()
    {
        $command = $this->createCommand();
        self::assertStringContainsString(CreateActionCommand::HELP_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAnAction()
    {
        $command = $this->createCommand();
        self::assertEquals(CreateActionCommand::HELP, $command->getHelp());
    }

    public function testConfigureSetsExpectedArgumentsWhenRequestingAnAction()
    {
        $command = $this->createCommand();
        $definition = $command->getDefinition();
        self::assertTrue($definition->hasArgument('action'));
        $argument = $definition->getArgument('action');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateActionCommand::HELP_ARG_ACTION, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAnAction()
    {
        $command = $this->createCommand();
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

    public function testConfigureSetsExpectedTemplateOptionsWhenRequestingAnActionAndRendererIsPresent()
    {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
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

    public function testSuccessfulExecutionEmitsExpectedMessagesWhenRequestingAnAction()
    {
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication('Foo\TestAction')->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestAction', [])
            ->andReturn(__DIR__);

        $this->input->getArgument('action')->willReturn('Foo\TestAction');
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating action Foo\TestAction'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestAction, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }
}
