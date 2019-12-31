<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Tooling\GenerateProgrammaticPipelineFromConfig;

use Laminas\Stdlib\ConsoleHelper;
use Mezzio\Tooling\GenerateProgrammaticPipelineFromConfig\Command;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;

class CommandTest extends TestCase
{
    /** @var ConsoleHelper|ProphecyInterface */
    private $console;

    /** @var Command */
    private $command;

    public function setUp()
    {
        $this->console = $this->prophesize(ConsoleHelper::class);
        $this->command = new Command(
            'generate-programmatic-pipeline-from-config',
            $this->console->reveal()
        );
    }

    public function assertHelp($resource = STDOUT)
    {
        $this->console
            ->writeLine(
                Argument::containingString('generate-programmatic-pipeline-from-config <command> [options]'),
                true,
                $resource
            )
            ->shouldBeCalled();
    }

    public function helpRequests()
    {
        return [
            'no-args'            => [[]],
            'help-arg'           => [['help']],
            'help-flag'          => [['-h']],
            'help-opt'           => [['--help']],
            'generate-help'      => [['generate', 'help']],
            'generate-help-flag' => [['generate', '-h']],
            'generate-help-arg'  => [['generate', '--help']],
        ];
    }

    /**
     * @dataProvider helpRequests
     *
     * @param array $args
     */
    public function testHelpRequestEmitsHelp(array $args)
    {
        $this->assertHelp();
        $this->assertEquals(0, $this->command->process($args));
    }

    public function testUnknownCommandDisplaysErrorAndHelp()
    {
        $this->console
            ->writeLine(
                Argument::containingString('Unknown command'),
                true,
                STDERR
            )
            ->shouldBeCalled();
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $this->command->process(['unknown']));
    }

    public function testErrorDuringGenerationEmitsErrorMessageButNoHelp()
    {
        $this->console
            ->writeLine(
                Argument::containingString(
                    'Generating programmatic pipeline for an existing Mezzio application...'
                )
            )
            ->shouldBeCalled();

        $this->console
            ->writeLine(
                Argument::containingString('Error during generation'),
                true,
                STDERR
            )
            ->shouldBeCalled();

        $this->console
            ->writeLine(
                Argument::containingString('not found'),
                true,
                STDERR
            )
            ->shouldBeCalled();

        $this->console
            ->writeLine(
                Argument::containingString('generate-programmatic-pipeline-from-config <command> [options]'),
                true,
                Argument::any()
            )
            ->shouldNotBeCalled();

        $this->assertEquals(1, $this->command->process(['generate']));
    }

    public function testReportsSuccessWhenRunSuccessfully()
    {
        $dir = vfsStream::setup('project');
        vfsStream::newFile('config/config.php')
            ->at($dir)
            ->setContent(file_get_contents(__DIR__ . '/TestAsset/asset/config/config.php'));

        vfsStream::newFile('public/index.php')
            ->at($dir)
            ->setContent(file_get_contents(__DIR__ . '/TestAsset/asset/public/index.php'));

        vfsStream::newDirectory('config/autoload', 0755)
            ->at($dir);

        $this->console
            ->writeLine(
                Argument::containingString(
                    'Generating programmatic pipeline for an existing Mezzio application...'
                )
            )
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('Success'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('enabling programmatic pipelines'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('defining the pipeline'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('defining the routes'))
            ->shouldBeCalled();
        $this->console
            ->writeLine(Argument::containingString('running the application'))
            ->shouldBeCalled();

        $this->command->projectDir = vfsStream::url('project');

        $this->assertEquals(0, $this->command->process(['generate']));
    }
}
