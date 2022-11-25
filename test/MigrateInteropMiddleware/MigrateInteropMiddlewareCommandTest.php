<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateInteropMiddleware;

use Mezzio\Tooling\MigrateInteropMiddleware\ArgvException;
use Mezzio\Tooling\MigrateInteropMiddleware\ConvertInteropMiddleware;
use Mezzio\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommand;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function mkdir;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateInteropMiddlewareCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    private MigrateInteropMiddlewareCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(ConsoleOutputInterface::class);

        $this->command = new MigrateInteropMiddlewareCommand('');
    }

    private function reflectExecuteMethod(MigrateInteropMiddlewareCommand $command): ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString(
            'Migrate http-interop middleware and delegators',
            $this->command->getDescription()
        );
    }

    private function getConstantValue(
        string $const,
        string $class = MigrateInteropMiddlewareCommand::class
    ): bool|string|int|float {
        $r = new ReflectionClass($class);

        return $r->getConstant($const);
    }

    public function testConfigureSetsExpectedHelp(): void
    {
        self::assertEquals($this->getConstantValue('HELP'), $this->command->getHelp());
    }

    public function testConfigureSetsExpectedArguments(): void
    {
        $definition = $this->command->getDefinition();
        self::assertTrue($definition->hasOption('src'));
        $option = $definition->getOption('src');
        self::assertTrue($option->isValueRequired());
        self::assertEquals($this->getConstantValue('HELP_OPT_SRC'), $option->getDescription());
    }

    public function testSuccessfulExecutionEmitsExpectedMessages(): void
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');
        mkdir($path . '/src');

        $converter = Mockery::mock('overload:' . ConvertInteropMiddleware::class);
        $converter->shouldReceive('process')
            ->once()
            ->with($path . '/src')
            ->andReturnNull();

        $this->input->method('getOption')->with('src')->willReturn('src');

        $this->output
            ->expects(self::atLeast(2))
            ->method('writeln')
            ->with(self::logicalOr(
                self::stringContains('Scanning for usage of http-interop middleware...'),
                self::stringContains('Done!'),
            ));

        $command = new MigrateInteropMiddlewareCommand($path);
        $method  = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input,
            $this->output
        ));
    }

    public function testAllowsExceptionsFromInvalidSrcDirectoryArgumentToBubbleUp(): void
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');

        $converter = Mockery::mock('overload:' . ConvertInteropMiddleware::class);
        $converter->shouldNotReceive('process');

        $this->input->method('getOption')->with('src')->willReturn('src');

        $command = new MigrateInteropMiddlewareCommand($path);
        $method  = $this->reflectExecuteMethod($command);

        $this->expectException(ArgvException::class);
        $this->expectExceptionMessage('Invalid --src argument');

        $method->invoke(
            $command,
            $this->input,
            $this->output
        );
    }
}
