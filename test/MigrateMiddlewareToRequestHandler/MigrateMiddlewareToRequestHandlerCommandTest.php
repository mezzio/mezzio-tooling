<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateMiddlewareToRequestHandler;

use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\ArgvException;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\ConvertMiddleware;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand;
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
class MigrateMiddlewareToRequestHandlerCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    private MigrateMiddlewareToRequestHandlerCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(ConsoleOutputInterface::class);

        $this->command = new MigrateMiddlewareToRequestHandlerCommand('');
    }

    private function reflectExecuteMethod(MigrateMiddlewareToRequestHandlerCommand $command): ReflectionMethod
    {
        $r = new ReflectionMethod($command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription(): void
    {
        self::assertStringContainsString(
            'Migrate PSR-15 middleware to request handlers',
            $this->command->getDescription()
        );
    }

    /**
     * @return mixed
     */
    private function getConstantValue(string $const, string $class = MigrateMiddlewareToRequestHandlerCommand::class)
    {
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

        $converter = Mockery::mock('overload:' . ConvertMiddleware::class);
        $converter->shouldReceive('process')
            ->once()
            ->with($path . '/src')
            ->andReturnNull();

        $this->input->method('getOption')->with('src')->willReturn('src');

        $this->output
            ->expects(self::atLeast(2))
            ->method('writeln')
            ->with(self::logicalOr(
                self::matchesRegularExpression('#Scanning "[^"]+" for PSR-15 middleware to convert#'),
                self::stringContains('Done!'),
            ));

        $command = new MigrateMiddlewareToRequestHandlerCommand($path);
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

        $converter = Mockery::mock('overload:' . ConvertMiddleware::class);
        $converter->shouldNotReceive('process');

        $this->input->method('getOption')->with('src')->willReturn('src');

        $command = new MigrateMiddlewareToRequestHandlerCommand($path);
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
