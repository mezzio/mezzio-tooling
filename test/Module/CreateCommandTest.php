<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Module;

use ArrayObject;
use Generator;
use Mezzio\Tooling\Module\Create;
use Mezzio\Tooling\Module\CreateCommand;
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

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
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
     * @param OutputInterface&ObjectProphecy $output
     * @return Application&ObjectProphecy
     */
    private function mockApplicationWithRegisterCommand(
        int $return,
        string $name,
        string $module,
        string $composer,
        string $modulesPath,
        $output
    ) {
        $register = $this->prophesize(Command::class);
        $register
            ->run(
                Argument::that(function ($input) use ($name, $module, $composer, $modulesPath) {
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

                    TestCase::assertArrayHasKey('--modules-path', $parameters);
                    TestCase::assertEquals($modulesPath, $parameters['--modules-path']);

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

    public function testConfigureSetsExpectedDescription()
    {
        self::assertStringContainsString('Create and register a middleware module', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        self::assertEquals(CreateCommand::HELP, $this->command->getHelp());
    }

    /**
     * @dataProvider configType
     */
    public function testCommandEmitsExpectedSuccessMessages(bool $configAsArrayObject)
    {
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot)
            ->andReturn('SUCCESSFULLY RAN CREATE');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);
        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output->writeln(Argument::containingString('SUCCESSFULLY RAN CREATE'))->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            0,
            'mezzio:module:register',
            'Foo',
            'composer.phar',
            'library/modules',
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
    public function testCommandWillFailIfRegisterFails(bool $configAsArrayObject)
    {
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->once()
            ->with('Foo', 'library/modules', $projectRoot)
            ->andReturn('SUCCESSFULLY RAN CREATE');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset', $this->dir);

        $command = new CreateCommand($this->createConfig($configAsArrayObject), $projectRoot);

        $this->output->writeln(Argument::containingString('SUCCESSFULLY RAN CREATE'))->shouldBeCalled();

        $app = $this->mockApplicationWithRegisterCommand(
            1,
            'mezzio:module:register',
            'Foo',
            'composer.phar',
            'library/modules',
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
    public function testCommandAllowsExceptionsToBubbleUp(bool $configAsArrayObject)
    {
        $projectRoot = getcwd();
        $creation    = Mockery::mock('overload:' . Create::class);
        $creation->shouldReceive('process')
            ->with('Foo', 'library/modules', $projectRoot)
            ->once()
            ->andThrow(RuntimeException::class, 'ERROR THROWN');

        $this->input->getArgument('module')->willReturn('Foo');
        $this->input->getOption('composer')->willReturn('composer.phar');
        $this->input->getOption('modules-path')->willReturn('./library/modules');

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
}
