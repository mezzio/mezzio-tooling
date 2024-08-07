<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateMiddlewareToRequestHandler;

use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\ArgvException;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\ConvertMiddleware;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand;
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

use function mkdir;
use function preg_match;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateMiddlewareToRequestHandlerCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var ObjectProphecy<InputInterface> */
    private ObjectProphecy $input;

    /** @var ObjectProphecy<ConsoleOutputInterface> */
    private $output;

    private MigrateMiddlewareToRequestHandlerCommand $command;

    protected function setUp(): void
    {
        $this->input  = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(ConsoleOutputInterface::class);

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

        $this->input->getOption('src')->willReturn('src');

        $this->output
            ->writeln(Argument::that(
                static fn($arg) => preg_match('#Scanning "[^"]+" for PSR-15 middleware to convert#', $arg)
            ))
            ->shouldBeCalledTimes(1);
        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalledTimes(1);

        $command = new MigrateMiddlewareToRequestHandlerCommand($path);
        $method  = $this->reflectExecuteMethod($command);

        self::assertSame(0, $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        ));
    }

    public function testAllowsExceptionsFromInvalidSrcDirectoryArgumentToBubbleUp(): void
    {
        vfsStream::setup('migrate');
        $path = vfsStream::url('migrate');

        $converter = Mockery::mock('overload:' . ConvertMiddleware::class);
        $converter->shouldNotReceive('process');

        $this->input->getOption('src')->willReturn('src');

        $command = new MigrateMiddlewareToRequestHandlerCommand($path);
        $method  = $this->reflectExecuteMethod($command);

        $this->expectException(ArgvException::class);
        $this->expectExceptionMessage('Invalid --src argument');

        $method->invoke(
            $command,
            $this->input->reveal(),
            $this->output->reveal()
        );
    }
}
