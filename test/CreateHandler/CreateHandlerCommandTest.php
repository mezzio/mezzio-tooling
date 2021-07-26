<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateHandler;

use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Tooling\CreateHandler\CreateHandler;
use Mezzio\Tooling\CreateHandler\CreateHandlerCommand;
use Mezzio\Tooling\CreateHandler\CreateHandlerException;
use Mezzio\Tooling\CreateHandler\CreateTemplate;
use Mezzio\Tooling\CreateHandler\Template;
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

use function sprintf;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateHandlerCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var ContainerInterface&ObjectProphecy */
    private $container;

    /** @var InputInterface&ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface&ObjectProphecy */
    private $output;

    protected function setUp(): void
    {
        $this->input  = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function createCommand(): CreateHandlerCommand
    {
        return new CreateHandlerCommand(
            $this->container->reveal(),
            ''
        );
    }

    /**
     * Allows disabling of the `require` statement in the command class when testing.
     */
    private function disableRequireHandlerDirective(CreateHandlerCommand $command): void
    {
        $r = new ReflectionProperty($command, 'requireHandlerBeforeGeneratingFactory');
        $r->setAccessible(true);
        $r->setValue($command, false);
    }

    private function reflectExecuteMethod(CreateHandlerCommand $command): ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    /**
     * @return ObjectProphecy|Application
     */
    private function mockApplication(string $forService = 'Foo\TestHandler')
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

    public function testConfigureSetsExpectedDescriptionWhenRequestingAHandler()
    {
        $command = $this->createCommand();
        self::assertStringContainsString(CreateHandlerCommand::HELP_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAHandler()
    {
        $command = $this->createCommand();
        self::assertEquals(CreateHandlerCommand::HELP, $command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $command    = $this->createCommand();
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('handler'));
        $argument = $definition->getArgument('handler');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateHandlerCommand::HELP_ARG_HANDLER, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAHandler()
    {
        $command    = $this->createCommand();
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('no-factory'));
        $option = $definition->getOption('no-factory');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateHandlerCommand::HELP_OPT_NO_FACTORY, $option->getDescription());

        self::assertTrue($definition->hasOption('no-register'));
        $option = $definition->getOption('no-register');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateHandlerCommand::HELP_OPT_NO_REGISTER, $option->getDescription());

        self::assertFalse($definition->hasOption('without-template'));
        self::assertFalse($definition->hasOption('with-template-namespace'));
        self::assertFalse($definition->hasOption('with-template-name'));
        self::assertFalse($definition->hasOption('with-template-extension'));
    }

    public function testConfigureSetsExpectedTemplateOptionsWhenRequestingAHandlerAndRendererIsPresent()
    {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command    = new CreateHandlerCommand($this->container->reveal(), '');
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('without-template'));
        $option = $definition->getOption('without-template');
        self::assertFalse($option->acceptValue());
        self::assertEquals(CreateHandlerCommand::HELP_OPTION_WITHOUT_TEMPLATE, $option->getDescription());

        self::assertTrue($definition->hasOption('with-template-namespace'));
        $option = $definition->getOption('with-template-namespace');
        self::assertTrue($option->acceptValue());
        self::assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_NAMESPACE, $option->getDescription());

        self::assertTrue($definition->hasOption('with-template-name'));
        $option = $definition->getOption('with-template-name');
        self::assertTrue($option->acceptValue());
        self::assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_NAME, $option->getDescription());

        self::assertTrue($definition->hasOption('with-template-extension'));
        $option = $definition->getOption('with-template-extension');
        self::assertTrue($option->acceptValue());
        self::assertEquals(CreateHandlerCommand::HELP_OPTION_WITH_TEMPLATE_EXTENSION, $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andReturn(__DIR__);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testCommandWillGenerateTemplateWhenRendererIsRegistered(): void
    {
        $expectedSubstitutions = [
            '%template-namespace%' => 'foo',
            '%template-name%'      => 'test',
        ];
        $generatedTemplate     = 'foo::test';
        $templateNamespace     = 'foo';
        $templateName          = 'test';

        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', $expectedSubstitutions)
            ->andReturn(__DIR__);

        $template          = new Template(__FILE__, $generatedTemplate);
        $templateGenerator = Mockery::mock('overload:' . CreateTemplate::class);
        $templateGenerator->shouldReceive('generateTemplate')
            ->once()
            ->with('Foo\TestHandler', $templateNamespace, $templateName, null)
            ->andReturn($template);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('without-template')->willReturn(false);
        $this->input->getOption('with-template-namespace')->willReturn(null);
        $this->input->getOption('with-template-name')->willReturn(null);
        $this->input->getOption('with-template-extension')->willReturn(null);
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created template ' . $generatedTemplate . ' in file ' . __FILE__))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testCommandWillGenerateTemplateWithProvidedOptionsWhenRendererIsRegistered(): void
    {
        $templateNamespace     = 'custom';
        $templateName          = 'also-custom';
        $generatedTemplate     = sprintf('%s::%s', $templateNamespace, $templateName);
        $templateExtension     = 'XHTML';
        $expectedSubstitutions = [
            '%template-namespace%' => $templateNamespace,
            '%template-name%'      => $templateName,
        ];

        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', $expectedSubstitutions)
            ->andReturn(__DIR__);

        $template          = new Template(__FILE__, $generatedTemplate);
        $templateGenerator = Mockery::mock('overload:' . CreateTemplate::class);
        $templateGenerator->shouldReceive('generateTemplate')
            ->once()
            ->with('Foo\TestHandler', $templateNamespace, $templateName, $templateExtension)
            ->andReturn($template);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('without-template')->willReturn(false);
        $this->input->getOption('with-template-namespace')->willReturn($templateNamespace);
        $this->input->getOption('with-template-name')->willReturn($templateName);
        $this->input->getOption('with-template-extension')->willReturn($templateExtension);
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created template ' . $generatedTemplate . ' in file ' . __FILE__))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testCommandWillNotGenerateTemplateWithProvidedOptionsWhenWithoutTemplateOptionProvided(): void
    {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andReturn(__DIR__);

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->input->getOption('without-template')->willReturn(true);
        $this->input->getOption('with-template-namespace')->shouldNotBeCalled();
        $this->input->getOption('with-template-name')->shouldNotBeCalled();
        $this->input->getOption('with-template-extension')->shouldNotBeCalled();
        $this->input->getOption('no-factory')->willReturn(false);
        $this->input->getOption('no-register')->willReturn(false);
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created template'))
            ->shouldNotBeCalled();
        $this->output
            ->writeln(Argument::containingString('Created class Foo\TestHandler, in file ' . __DIR__))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateHandlerToBubbleUp()
    {
        $command = $this->createCommand();
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andThrow(CreateHandlerException::class, 'ERROR THROWN');

        $this->input->getArgument('handler')->willReturn('Foo\TestHandler');
        $this->output
            ->writeln(Argument::containingString('Creating request handler Foo\TestHandler'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }

    public function testAllowsExceptionsRaisedFromCreateHandlerToBubbleUpWhenRendererIsRegistered()
    {
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication()->reveal());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('InvalidTestHandler', [
                '%template-namespace%' => 'invalid-test-handler',
                '%template-name%'      => 'invalid-test',
            ])
            ->andThrow(CreateHandlerException::class, 'ERROR THROWN');

        $this->input->getArgument('handler')->willReturn('InvalidTestHandler');
        $this->input->getOption('without-template')->willReturn(false);
        $this->input->getOption('with-template-namespace')->willReturn(null);
        $this->input->getOption('with-template-name')->willReturn(null);
        $this->input->getOption('with-template-extension')->willReturn(null);
        $this->output
            ->writeln(Argument::containingString('Creating request handler InvalidTestHandler'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Success'))
            ->shouldNotBeCalled();

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
