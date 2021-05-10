<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateInteropMiddleware;

use Mezzio\Tooling\MigrateInteropMiddleware\ArgvException;
use Mezzio\Tooling\MigrateInteropMiddleware\ConvertInteropMiddleware;
use Mezzio\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommand;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateInteropMiddlewareCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var InputInterface|ObjectProphecy */
    private $input;

    /** @var ConsoleOutputInterface|ObjectProphecy */
    private $output;

    /** @var MigrateInteropMiddlewareCommand */
    private $command;

    protected function setUp() : void
    {
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

        $this->command = new MigrateInteropMiddlewareCommand('migrate:interop-middleware');
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        self::assertStringContainsString(
            'Migrate http-interop middleware and delegators',
            $this->command->getDescription()
        );
    }

    private function getConstantValue(string $const, string $class = MigrateInteropMiddlewareCommand::class)
    {
        $r = new ReflectionClass($class);

        return $r->getConstant($const);
    }

    public function testConfigureSetsExpectedHelp()
    {
        self::assertEquals($this->getConstantValue('HELP'), $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments()
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasOption('src'));
        $option = $definition->getOption('src');
        self::assertTrue($option->isValueRequired());
        self::assertEquals($this->getConstantValue('HELP_OPT_SRC'), $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages()
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');
        mkdir($path . '/src');

        $converter = Mockery::mock('overload:' . ConvertInteropMiddleware::class);
        $converter->shouldReceive('process')
            ->once()
            ->with($path . '/src')
            ->andReturnNull();

        $this->input->getOption('src')->willReturn('src');

        $this->output
            ->writeln(Argument::containingString('Scanning for usage of http-interop middleware...'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $this->command->setProjectDir($path);
        $method = $this->reflectExecuteMethod();

        self::assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsFromInvalidSrcDirectoryArgumentToBubbleUp()
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');

        $converter = Mockery::mock('overload:' . ConvertInteropMiddleware::class);
        $converter->shouldNotReceive('process');

        $this->input->getOption('src')->willReturn('src');

        $this->command->setProjectDir($path);
        $method = $this->reflectExecuteMethod();

        $this->expectException(ArgvException::class);
        $this->expectExceptionMessage('Invalid --src argument');

        $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
