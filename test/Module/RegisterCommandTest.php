<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Composer\ComposerPackageFactoryInterface;
use Mezzio\Tooling\Composer\ComposerPackageInterface;
use Mezzio\Tooling\Composer\ComposerProcessFactoryInterface;
use Mezzio\Tooling\Composer\ComposerProcessInterface;
use Mezzio\Tooling\Composer\ComposerProcessResultInterface;
use Mezzio\Tooling\ConfigInjector\InjectorInterface;
use Mezzio\Tooling\Module\RegisterCommand;
use Mezzio\Tooling\Module\RuntimeException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function mkdir;
use function preg_replace;
use function sprintf;

/** @covers \Mezzio\Tooling\Module\RegisterCommand */
class RegisterCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;

    private vfsStreamDirectory $dir;

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    private RegisterCommand $command;

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
        $this->command                           = new RegisterCommand(
            $this->dir->url(),
            $packageFactory,
            $this->processFactory,
            $this->injector
        );
        $this->expectedModuleArgumentDescription = RegisterCommand::HELP_ARG_MODULE;
    }

    private function reflectExecuteMethod(): ReflectionMethod
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString('Register a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals(RegisterCommand::HELP, $this->command->getHelp());
    }

    /** @psalm-return array<string, array{0: bool, 1: bool, 2: bool, 3: string}> */
    public function injectedEnabled(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'custom module structure, injected and enabled'                             => [true,  true,  true,  './library/modules', null],
            'custom module structure, injected and NOT enabled'                         => [true,  false, true,  './library/modules', null],
            'custom module structure, NOT injected but enabled'                         => [false, true,  true,  './library/modules', null],
            'custom module structure, NOT injected and NOT enabled'                     => [false, false, true,  './library/modules', null],
            'recommended module structure, injected and enabled'                        => [true,  true,  true,  'src', null],
            'recommended module structure, injected and NOT enabled'                    => [true,  false, true,  'src', null],
            'recommended module structure, NOT injected but enabled'                    => [false, true,  true,  'src', null],
            'recommended module structure, NOT injected and NOT enabled'                => [false, false, true,  'src', null],
            'flat module structure, injected and enabled'                               => [true,  true,  false, 'src', null],
            'flat module structure, injected and NOT enabled'                           => [true,  false, false, 'src', null],
            'flat module structure, NOT injected but enabled'                           => [false, true,  false, 'src', null],
            'flat module structure, NOT injected and NOT enabled'                       => [false, false, false, 'src', null],
            'recommended module structure, no module source path, injected and enabled' => [true,  true,  true,  null, null],
            'flat module structure, no module source path, injected and enabled'        => [true,  true,  false, null, null],
            'exact path, recommended structure, injected and enabled'                   => [true,  true,  true, null, './library/Foo'],
            'exact path, flat structure, injected and enabled'                          => [true,  true,  false, null, './library/Foo'],
        ];
        // phpcs:enable
    }

    /** @dataProvider injectedEnabled */
    public function testCommandEmitsExpectedMessagesWhenItInjectsConfigurationAndEnablesModule(
        bool $injected,
        bool $enabled,
        bool $isRecommendedStructure,
        ?string $modulesPath,
        ?string $exactPath
    ): void {
        $module                = 'MyApp';
        $composer              = 'composer.phar';
        $configProvider        = $module . '\ConfigProvider';
        $normalizedModulesPath = $modulesPath === null ? 'src' : preg_replace('#^./#', '', $modulesPath);
        $expectedAutoloadPath  = $exactPath ?: sprintf(
            '%s/%s%s',
            $normalizedModulesPath,
            $module,
            $isRecommendedStructure ? '/src' : ''
        );
        $pathToCreate          = $exactPath === null
            ? sprintf(
                '%s/%s/%s%s',
                $this->dir->url(),
                $normalizedModulesPath,
                $module,
                $isRecommendedStructure ? '/src' : ''
            )
            : sprintf(
                '%s/%s',
                $this->dir->url(),
                $exactPath
            );
        mkdir($pathToCreate, 0777, true);

        $this->input->method('getArgument')->with('module')->willReturn($module);
        $this->input->method('getOption')->willReturnMap([
            ['composer', $composer],
            ['modules-path', $modulesPath],
            ['exact-path', $exactPath],
        ]);

        $this->injector
            ->expects(self::once())
            ->method('isRegistered')
            ->with($configProvider)
            ->willReturn(! $injected);

        if ($injected) {
            $this->injector
                ->expects(self::once())
                ->method('inject')
                ->with($configProvider, InjectorInterface::TYPE_CONFIG_PROVIDER);
        } else {
            $this->injector
                ->expects(self::never())
                ->method('inject')
                ->with($configProvider, InjectorInterface::TYPE_CONFIG_PROVIDER);
        }

        $this->package
            ->expects($this->once())
            ->method('addPsr4AutoloadRule')
            ->with(
                $module,
                $expectedAutoloadPath,
                false
            )
            ->willReturn($enabled);

        if ($enabled === true) {
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
                ->with(self::stringContains('Registered config provider and autoloading rules for module ' . $module));
        }

        if ($enabled === false) {
            $this->processFactory
                ->expects($this->never())
                ->method('createProcess');

            $this->output
                ->expects(self::atLeastOnce())
                ->method('writeln')
                ->with(self::stringContains('Registered config provider for module ' . $module));
        }

        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input,
            $this->output
        ));
    }

    public function testAllowsRuntimeExceptionsThrownFromEnableToBubbleUp(): void
    {
        $this->input->method('getArgument')->with('module')->willReturn('MyApp');
        $this->input->method('getOption')->willReturnMap([
            ['composer', 'composer.phar'],
            ['modules-path', './library/modules'],
            ['exact-path', null],
        ]);

        $this->injector
            ->expects(self::once())
            ->method('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->willReturn(true);

        $this->processFactory->expects($this->never())->method('createProcess');

        $method = $this->reflectExecuteMethod();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot register module; directory "library/modules/MyApp" does not exist');

        $method->invoke(
            $this->command,
            $this->input,
            $this->output
        );
    }
}
