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

use function sprintf;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateHandlerCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    protected function setUp(): void
    {
        $this->input     = $this->createMock(InputInterface::class);
        $this->output    = $this->createMock(ConsoleOutputInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function createCommand(): CreateHandlerCommand
    {
        return new CreateHandlerCommand(
            $this->container,
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

    /** @return Application&MockObject */
    private function mockApplication(string $forService = 'Foo\TestHandler'): Application
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

    public function testConfigureSetsExpectedDescriptionWhenRequestingAHandler(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command = $this->createCommand();
        self::assertStringContainsString(CreateHandlerCommand::HELP_DESCRIPTION, $command->getDescription());
    }

    public function testConfigureSetsExpectedHelpWhenRequestingAHandler(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        self::assertEquals(CreateHandlerCommand::HELP, $command->getHelp());
    }

    public function testConfigureSetsExpectedArguments(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command    = $this->createCommand();
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('handler'));
        $argument = $definition->getArgument('handler');
        self::assertTrue($argument->isRequired());
        self::assertEquals(CreateHandlerCommand::HELP_ARG_HANDLER, $argument->getDescription());
    }

    public function testConfigureSetsExpectedOptionsWhenRequestingAHandler(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
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

    public function testConfigureSetsExpectedTemplateOptionsWhenRequestingAHandlerAndRendererIsPresent(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command    = new CreateHandlerCommand($this->container, '');
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

    public function testSuccessfulExecutionEmitsExpectedMessages(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andReturn(__DIR__);

        $this->input->method('getArgument')->with('handler')->willReturn('Foo\TestHandler');
        $this->input->method('getOption')
            ->willReturnMap([
                ['no-factory', false],
                ['no-register', false],
            ]);
        $this->output
            ->expects(self::atLeast(3))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Creating request handler Foo\TestHandler'),
                self::stringContains('Success'),
                self::stringContains('Created class Foo\TestHandler, in file ' . __DIR__)
            ));

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
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

        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication());

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

        $this->input->method('getArgument')->with('handler')->willReturn('Foo\TestHandler');
        $this->input->method('getOption')
            ->willReturnMap([
                ['without-template' => false],
                ['with-template-namespace', null],
                ['with-template-name', null],
                ['with-template-extension', null],
                ['no-factory', false],
                ['no-register', false],
            ]);
        $this->output
            ->expects(self::atLeast(4))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Creating request handler Foo\TestHandler'),
                self::stringContains('Success'),
                self::stringContains('Created template ' . $generatedTemplate . ' in file ' . __FILE__),
                self::stringContains('Created class Foo\TestHandler, in file ' . __DIR__)
            ));

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
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

        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication());

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

        $this->input->method('getArgument')->with('handler')->willReturn('Foo\TestHandler');
        $this->input->method('getOption')
            ->willReturnMap([
                ['without-template', false],
                ['with-template-namespace', $templateNamespace],
                ['with-template-name', $templateName],
                ['with-template-extension', $templateExtension],
                ['no-factory', false],
                ['no-register', false],
            ]);

        $this->output
            ->expects(self::atLeast(4))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Creating request handler Foo\TestHandler'),
                self::stringContains('Success'),
                self::stringContains('Created template ' . $generatedTemplate . ' in file ' . __FILE__),
                self::stringContains('Created class Foo\TestHandler, in file ' . __DIR__)
            ));

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
        ));
    }

    public function testCommandWillNotGenerateTemplateWithProvidedOptionsWhenWithoutTemplateOptionProvided(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andReturn(__DIR__);

        $this->input->method('getArgument')->with('handler')->willReturn('Foo\TestHandler');
        $this->input->method('getOption')
            ->willReturnMap([
                ['without-template', true],
                ['no-factory', false],
                ['no-register', false],
            ]);

        $this->output
            ->expects(self::atLeast(3))
            ->method('writeln')
            ->with(self::logicalAnd(
                self::logicalOr(
                    self::stringContains('Creating request handler Foo\TestHandler'),
                    self::stringContains('Success'),
                    self::stringContains('Created class Foo\TestHandler, in file ' . __DIR__)
                ),
                self::logicalNot(self::stringContains('Created template')),
            ));

        $method = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
        ));
    }

    public function testAllowsExceptionsRaisedFromCreateHandlerToBubbleUp(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(false);
        $command = $this->createCommand();
        $command->setApplication($this->mockApplication());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('Foo\TestHandler', [])
            ->andThrow(CreateHandlerException::class, 'ERROR THROWN');

        $this->input->method('getArgument')->with('handler')->willReturn('Foo\TestHandler');
        $this->output
            ->expects(self::atLeast(1))
            ->method('writeln')
            ->with(self::logicalAnd(
                self::stringContains('Creating request handler Foo\TestHandler'),
                self::logicalNot(self::stringContains('Success')),
            ));

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $command,
            $this->input,
            $this->output
        );
    }

    public function testAllowsExceptionsRaisedFromCreateHandlerToBubbleUpWhenRendererIsRegistered(): void
    {
        $this->container->method('has')->with(TemplateRendererInterface::class)->willReturn(true);
        $command = $this->createCommand();
        $this->disableRequireHandlerDirective($command);
        $command->setApplication($this->mockApplication());

        $generator = Mockery::mock('overload:' . CreateHandler::class);
        $generator->shouldReceive('process')
            ->once()
            ->with('InvalidTestHandler', [
                '%template-namespace%' => 'invalid-test-handler',
                '%template-name%'      => 'invalid-test',
            ])
            ->andThrow(CreateHandlerException::class, 'ERROR THROWN');

        $this->input->method('getArgument')->with('handler')->willReturn('InvalidTestHandler');
        $this->input->method('getOption')->willReturnMap([
            ['without-template', false],
            ['with-template-namespace', null],
            ['with-template-name', null],
            ['with-template-extension', null],
        ]);

        $this->output
            ->expects(self::atLeast(1))
            ->method('writeln')
            ->with(self::logicalAnd(
                self::stringContains('Creating request handler InvalidTestHandler'),
                self::logicalNot(self::stringContains('Success')),
            ));

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(CreateHandlerException::class);
        $this->expectExceptionMessage('ERROR THROWN');

        $method->invoke(
            $command,
            $this->input,
            $this->output
        );
    }
}
