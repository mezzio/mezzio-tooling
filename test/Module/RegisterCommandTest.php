<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Mezzio\Tooling\Composer\ComposerPackageFactoryInterface;
use Mezzio\Tooling\Composer\ComposerPackageInterface;
use Mezzio\Tooling\Composer\ComposerProcessFactoryInterface;
use Mezzio\Tooling\Composer\ComposerProcessInterface;
use Mezzio\Tooling\Composer\ComposerProcessResultInterface;
use Mezzio\Tooling\ConfigInjector\ConfigAggregatorInjector;
use Mezzio\Tooling\ConfigInjector\InjectorInterface;
use Mezzio\Tooling\Module\RegisterCommand;
use Mezzio\Tooling\Module\RuntimeException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function mkdir;
use function preg_replace;
use function sprintf;

class RegisterCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    private vfsStreamDirectory $dir;

    /** @var ObjectProphecy<InputInterface> */
    private $input;

    /** @var ObjectProphecy<ConsoleOutputInterface> */
    private $output;

    /** @var RegisterCommand */
    private $command;

    /** @var string */
    private $expectedModuleArgumentDescription;

    /** @var ComposerPackageInterface&MockObject */
    private $package;

    /** @var ComposerProcessFactoryInterface&MockObject */
    private $processFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir            = vfsStream::setup('project');
        $this->package        = $this->createMock(ComposerPackageInterface::class);
        $this->processFactory = $this->createMock(ComposerProcessFactoryInterface::class);

        $packageFactory = $this->createMock(ComposerPackageFactoryInterface::class);
        $packageFactory->method('loadPackage')->with($this->dir->url())->willReturn($this->package);

        $this->input                             = $this->prophesize(InputInterface::class);
        $this->output                            = $this->prophesize(ConsoleOutputInterface::class);
        $this->command                           = new RegisterCommand(
            $this->dir->url(),
            $packageFactory,
            $this->processFactory
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider injectedEnabled
     */
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

        $this->input->getArgument('module')->willReturn($module);
        $this->input->getOption('composer')->willReturn($composer);
        $this->input->getOption('modules-path')->willReturn($modulesPath);
        $this->input->getOption('exact-path')->willReturn($exactPath);

        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with($configProvider)
            ->andReturn(! $injected)
            ->once();
        if ($injected) {
            $injectorMock
                ->shouldReceive('inject')
                ->with($configProvider, InjectorInterface::TYPE_CONFIG_PROVIDER)
                ->once();
        } else {
            $injectorMock
                ->shouldNotReceive('inject');
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
                ->writeln(Argument::containingString(
                    'Registered config provider and autoloading rules for module ' . $module
                ))
                ->shouldBeCalled();
        }

        if ($enabled === false) {
            $this->processFactory
                ->expects($this->never())
                ->method('createProcess');

            $this->output
                ->writeln(Argument::containingString(
                    'Registered config provider for module ' . $module
                ))
                ->shouldBeCalled();
        }

        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAllowsRuntimeExceptionsThrownFromEnableToBubbleUp(): void
    {
        $this->input->getArgument('module')->willReturn('MyApp');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('exact-path')->willReturn(null);

        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(true)
            ->once();

        $this->processFactory->expects($this->never())->method('createProcess');

        $method = $this->reflectExecuteMethod();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot register module; directory "library/modules/MyApp" does not exist');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
