<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use ArrayObject;
use Generator;
use Mezzio\Tooling\Module\Create;
use Mezzio\Tooling\Module\CreateCommand;
use Mezzio\Tooling\Module\ModuleMetadata;
use Mezzio\Tooling\Module\RuntimeException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function getcwd;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateCommandTest extends TestCase
{
    use CommonOptionsAndAttributesTrait;
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var ObjectProphecy<InputInterface> */
    private $input;

    /** @var ObjectProphecy<ConsoleOutputInterface> */
    private $output;

    /** @var CreateCommand */
    private $command;

    /** @var vfsStreamDirectory */
    private $dir;

    /** @var string */
    private $projectRoot;

    /** @var string */
    private $expectedModuleArgumentDescription;

    protected function setUp(): void
    {
        $this->dir         = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');

        $this->input  = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command                           = new CreateCommand([], '');
        $this->expectedModuleArgumentDescription = CreateCommand::HELP_ARG_MODULE;
    }

    /** @return array|ArrayObject */
    public function createConfig(bool $configAsArrayObject = false)
    {
        $configFile = $this->projectRoot . '/config/config.php';
        $config     = include $configFile;

        if ($configAsArrayObject) {
            $config = new ArrayObject($config);
        }

        return $config;
    }

    /** @psalm-return Generator<string, list<bool>> */
    public function configType(): Generator
    {
        yield 'array'       => [false];
        yield 'ArrayObject' => [true];
    }

    private function reflectExecuteMethod(CreateCommand $command): ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    /**
     * @psalm-return ObjectProphecy<Application>
     */
    private function mockApplicationWithRegisterCommand(
        int $return,
        string $name,
        string $module,
        string $composer,
        string $modulePath,
        OutputInterface $output
    ) {
        $register = $this->prophesize(Command::class);
        $register
            ->run(
                Argument::that(function ($input) use ($name, $module, $composer, $modulePath) {
                    TestCase::assertInstanceOf(ArrayInput::class, $input);

                    $r = new ReflectionProperty($input, 'parameters');
                    $r->setAccessible(true);
                    $parameters = $r->getValue($input);

                    TestCase::assertArrayHasKey('command', $parameters);
                    TestCase::assertEquals($name, $parameters['command']);

                    TestCase::assertArrayHasKey('module', $parameters);
                    TestCase::assertEquals($module, $parameters['module']);

                    TestCase::assertArrayHasKey('--composer', $parameters);
                    TestCase::assertEquals($composer, $parameters['--composer']);

                    TestCase::assertArrayHasKey('--exact-path', $parameters);
                    TestCase::assertEquals($modulePath, $parameters['--exact-path']);

                    return true;
                }),
                $output
            )
            ->willReturn($return);

        // HelperSet is needed as setApplication retrieves it to inject in the new command
        $helperSet = $this->prophesize(HelperSet::class);

        $application = $this->prophesize(Application::class);
        $application->find($name)->will([$register, 'reveal']);
        $application->getHelperSet()->will([$helperSet, 'reveal']);
        return $application;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString('Create and register a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals(CreateCommand::HELP, $this->command->getHelp());
    }

    /**
     * @dataProvider configType
     */
    public function testCommandEmitsExpectedSuccessMessages(bool $configAsArrayObject): void
    {
        $metadata    = new ModuleMetadata(
            'Foo',
            './library/modules',
            './library/modules/Foo/src'
        );
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot, false, '')
            ->andReturn($metadata);

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('flat')->willReturn(false);
        $this->input->getOption('with-route-delegator')->willReturn(false);
        $this->input->getOption('with-namespace')->willReturn('');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output
             ->writeln(Argument::containingString('Created module "Foo" in directory "./library/modules"'))
             ->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            0,
            'mezzio:module:register',
            'Foo',
            'composer.phar',
            'library/modules/Foo/src',
            $this->output->reveal()
        );
        $command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod($command);
        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider configType
     */
    public function testCommandWillFailIfRegisterFails(bool $configAsArrayObject): void
    {
        $metadata    = new ModuleMetadata(
            'Foo',
            './library/modules',
            './library/modules/Foo/src'
        );
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot, false, '')
            ->andReturn($metadata);

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('flat')->willReturn(false);
        $this->input->getOption('with-route-delegator')->willReturn(false);
        $this->input->getOption('with-namespace')->willReturn('');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);

        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output
             ->writeln(Argument::containingString('Created module "Foo" in directory "./library/modules"'))
             ->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            1,
            'mezzio:module:register',
            'Foo',
            'composer.phar',
            'library/modules/Foo/src',
            $this->output->reveal()
        );
        $command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod($command);
        self::assertSame(1, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider configType
     */
    public function testCommandAllowsExceptionsToBubbleUp(bool $configAsArrayObject): void
    {
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->with('Foo', 'library/modules', $projectRoot, false, '')
            ->once()
            ->andThrow(RuntimeException::class, 'ERROR THROWN');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('flat')->willReturn(false);
        $this->input->getOption('with-route-delegator')->willReturn(false);
        $this->input->getOption('with-namespace')->willReturn('');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);

        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $method = $this->reflectExecuteMethod($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ERROR THROWN');
        $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }

    /**
     * @dataProvider configType
     */
    public function testCommandPassesFlatOptionDuringCreation(bool $configAsArrayObject): void
    {
        $metadata    = new ModuleMetadata(
            'Foo',
            './library/modules',
            './library/modules/Foo'
        );
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot, false, '')
            ->andReturn($metadata);

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('flat')->willReturn(true);
        $this->input->getOption('with-route-delegator')->willReturn(false);
        $this->input->getOption('with-namespace')->willReturn('');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output
             ->writeln(Argument::containingString('Created module "Foo" in directory "./library/modules"'))
             ->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            0,
            'mezzio:module:register',
            'Foo',
            'composer.phar',
            'library/modules/Foo',
            $this->output->reveal()
        );
        $command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod($command);
        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider configType
     */
    public function testCommandPassesWithRouteDelegatorOptionDuringCreation(bool $configAsArrayObject): void
    {
        $metadata    = new ModuleMetadata(
            'Foo',
            './library/modules',
            './library/modules/Foo/src'
        );
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot, true, '')
            ->andReturn($metadata);

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('flat')->willReturn(false);
        $this->input->getOption('with-route-delegator')->willReturn(true);
        $this->input->getOption('with-namespace')->willReturn('');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output
             ->writeln(Argument::containingString('Created module "Foo" in directory "./library/modules"'))
             ->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            0,
            'mezzio:module:register',
            'Foo',
            'composer.phar',
            'library/modules/Foo/src',
            $this->output->reveal()
        );
        $command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod($command);
        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    /**
     * @dataProvider configType
     */
    public function testCommandPassesParentNamespaceOptionDuringCreation(bool $configAsArrayObject): void
    {
        $metadata    = new ModuleMetadata(
            'ParentNamespace\\Foo',
            './library/modules',
            './library/modules/Foo/src'
        );
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot, true, 'ParentNamespace')
            ->andReturn($metadata);

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');
        $this->input->getOption('flat')->willReturn(false);
        $this->input->getOption('with-route-delegator')->willReturn(true);
        $this->input->getOption('with-namespace')->willReturn('ParentNamespace');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output
            ->writeln(Argument::containingString(
                'Created module "ParentNamespace\\Foo" in directory "library/modules/Foo"'
            ))
            ->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            0,
            'mezzio:module:register',
            'ParentNamespace\\Foo', // Note: passing parent namespace as module argument!
            'composer.phar',
            'library/modules/Foo/src',
            $this->output->reveal()
        );
        $command->setApplication($app->reveal());

        $method = $this->reflectExecuteMethod($command);
        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }
}
