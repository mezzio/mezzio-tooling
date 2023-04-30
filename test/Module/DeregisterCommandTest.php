<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Composer\ComposerPackageFactoryInterface;
use Mezzio\Tooling\Composer\ComposerPackageInterface;
use Mezzio\Tooling\Composer\ComposerProcessFactoryInterface;
use Mezzio\Tooling\Composer\ComposerProcessInterface;
use Mezzio\Tooling\Composer\ComposerProcessResultInterface;
use Mezzio\Tooling\ConfigInjector\InjectorInterface;
use Mezzio\Tooling\Module\DeregisterCommand;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class DeregisterCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;

    private vfsStreamDirectory $dir;

    /** @var InputInterface&MockObject */
    private $input;

    /** @var ConsoleOutputInterface&MockObject */
    private $output;

    private DeregisterCommand $command;

    private string $expectedModuleArgumentDescription;

    /** @var ComposerPackageInterface&MockObject */
    private ComposerPackageInterface $package;

    /** @var ComposerProcessFactoryInterface&MockObject */
    private ComposerProcessFactoryInterface $processFactory;

    /** @var InjectorInterface&MockObject */
    private InjectorInterface $injector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir            = vfsStream::setup('project');
        $this->package        = $this->createMock(ComposerPackageInterface::class);
        $this->processFactory = $this->createMock(ComposerProcessFactoryInterface::class);
        $this->injector       = $this->createMock(InjectorInterface::class);

        $packageFactory = $this->createMock(ComposerPackageFactoryInterface::class);
        $packageFactory->method('loadPackage')->with($this->dir->url())->willReturn($this->package);

        $this->input                             = $this->createMock(InputInterface::class);
        $this->output                            = $this->createMock(ConsoleOutputInterface::class);
        $this->command                           = new DeregisterCommand(
            $this->dir->url(),
            $packageFactory,
            $this->processFactory,
            $this->injector
        );
        $this->expectedModuleArgumentDescription = DeregisterCommand::HELP_ARG_MODULE;
    }

    private function reflectExecuteMethod(): ReflectionMethod
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString('Deregister a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals(DeregisterCommand::HELP, $this->command->getHelp());
    }

    /** @psalm-return array<string, array{0: bool, 1: bool}> */
    public function removedDisabled(): array
    {
        return [
            'removed and disabled'         => [true,  true],
            'removed and NOT disabled'     => [true,  false],
            'NOT removed but disabled'     => [false, true],
            'NOT removed and NOT disabled' => [false, false],
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider removedDisabled
     */
    public function testRemoveFromConfigurationAndDisableModuleEmitsExpectedMessages(
        bool $removed,
        bool $disabled
    ): void {
        $module         = 'MyApp';
        $composer       = 'composer.phar';
        $configProvider = $module . '\ConfigProvider';

        $this->input->method('getArgument')->with('module')->willReturn('MyApp');
        $this->input->method('getOption')->with('composer')->willReturn('composer.phar');

        $this->injector
            ->expects(self::once())
            ->method('isRegistered')
            ->with($configProvider)
            ->willReturn($removed);

        if ($removed) {
            $this->injector
                ->expects(self::once())
                ->method('remove')
                ->with($configProvider);
        } else {
            $this->injector
                ->expects(self::never())
                ->method('remove');
        }

        $this->package
            ->expects($this->once())
            ->method('removePsr4AutoloadRule')
            ->with($module, false)
            ->willReturn($disabled);

        if ($disabled === true) {
            $processResult = new class implements ComposerProcessResultInterface {
                public function isSuccessful(): bool
                {
                    return true;
                }

                public function getOutput(): string
                {
                    return '';
                }

                public function getErrorOutput(): string
                {
                    throw new RuntimeException(__METHOD__ . ' should not be called');
                }
            };

            $process = $this->createMock(ComposerProcessInterface::class);
            $process->expects($this->once())->method('run')->willReturn($processResult);
            $this->processFactory
                ->expects($this->once())
                ->method('createProcess')
                ->with([$composer, 'dump-autoload'])
                ->willReturn($process);

            $this->output
                ->expects(self::atLeastOnce())
                ->method('writeln')
                ->with(self::stringContains('Removed config provider and autoloading rules for module ' . $module));
        }

        if ($disabled === false) {
            $this->processFactory
                ->expects($this->never())
                ->method('createProcess');
            $this->output
                ->expects(self::atLeastOnce())
                ->method('writeln')
                ->with(self::stringContains('Removed config provider for module ' . $module));
        }

        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input,
            $this->output
        ));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAllowsExceptionsThrownFromDisableToBubbleUp(): void
    {
        $this->input->method('getArgument')->with('module')->willReturn('MyApp');
        $this->input->method('getOption')->willReturnMap([
            ['composer', 'composer.phar'],
            ['modules-path', './library/modules'],
        ]);

        $this->injector
            ->expects(self::once())
            ->method('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->willReturn(false);

        $this->package
            ->expects($this->once())
            ->method('removePsr4AutoloadRule')
            ->with('MyApp', false)
            ->willThrowException(new RuntimeException('Testing Exception Message'));

        $this->processFactory->expects($this->never())->method('createProcess');

        $method = $this->reflectExecuteMethod();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Testing Exception Message');

        $method->invoke(
            $this->command,
            $this->input,
            $this->output
        );
    }
}
