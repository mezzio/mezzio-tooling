<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Mezzio\Tooling\Composer\ComposerPackageFactoryInterface;
use Mezzio\Tooling\Composer\ComposerPackageInterface;
use Mezzio\Tooling\Composer\ComposerProcessFactoryInterface;
use Mezzio\Tooling\Composer\ComposerProcessInterface;
use Mezzio\Tooling\Composer\ComposerProcessResultInterface;
use Mezzio\Tooling\Module\DeregisterCommand;
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
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class DeregisterCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    /** @var DeregisterCommand */
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

        $this->input   = $this->prophesize(InputInterface::class);
        $this->output  = $this->prophesize(ConsoleOutputInterface::class);
        $this->command = new DeregisterCommand(
            $this->dir->url(),
            $packageFactory,
            $this->processFactory
        );
        $this->expectedModuleArgumentDescription = DeregisterCommand::HELP_ARG_MODULE;
    }

    private function reflectExecuteMethod(): ReflectionMethod
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        self::assertStringContainsString('Deregister a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
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
     * @dataProvider removedDisabled
     */
    public function testRemoveFromConfigurationAndDisableModuleEmitsExpectedMessages(
        bool $removed,
        bool $disabled
    ): void {
        $module                = 'MyApp';
        $composer              = 'composer.phar';
        $configProvider        = $module . '\ConfigProvider';

        $this->input->getArgument('module')->willReturn('MyApp');
        $this->input->getOption('composer')->willReturn('composer.phar');

        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with($configProvider)
            ->andReturn($removed)
            ->once();
        if ($removed) {
            $injectorMock
                ->shouldReceive('remove')
                ->with($configProvider)
                ->once();
        } else {
            $injectorMock
                ->shouldNotReceive('remove');
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
                ->writeln(Argument::containingString(
                    'Removed config provider and autoloading rules for module ' . $module
                ))
                ->shouldBeCalled();
        }

        if ($disabled === false) {
            $this->processFactory
                ->expects($this->never())
                ->method('createProcess');
            $this->output
                ->writeln(Argument::containingString(
                    'Removed config provider for module ' . $module
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

    public function testAllowsExceptionsThrownFromDisableToBubbleUp()
    {
        $this->input->getArgument('module')->willReturn('MyApp');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        $injectorMock = Mockery::mock('overload:' . ConfigAggregatorInjector::class);
        $injectorMock
            ->shouldReceive('isRegistered')
            ->with('MyApp\ConfigProvider')
            ->andReturn(false)
            ->once();

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
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
