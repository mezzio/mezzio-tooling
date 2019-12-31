<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Tooling\MigrateMezzio22;

use Mezzio\Tooling\MigrateMezzio22\UpdatePipeline;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePipelineTest extends TestCase
{
    public function setUp()
    {
        $this->root = vfsStream::setup('mezzio22');
        $this->url = vfsStream::url('mezzio22');
        mkdir($this->url . '/config');
        touch($this->url . '/config/pipeline.php');
    }

    public function testUpdatesPipelineReferencingFullyQualifiedNames()
    {
        $originalContents = <<< 'EOT'
$app->pipeRoutingMiddleware();
$app->pipe(\Mezzio\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Mezzio\Middleware\ImplicitOptionsMiddleware::class);
$app->pipeDispatchMiddleware();
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Mezzio\Router\Middleware\RouteMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\ImplicitOptionsMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\DispatchMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('pipeRoutingMiddleware()'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('pipeDispatchMiddleware()'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }

    public function testUpdatesPipelineReferencingRelativeNames()
    {
        $originalContents = <<< 'EOT'
$app->pipe(Mezzio\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(Mezzio\Middleware\ImplicitOptionsMiddleware::class);
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Mezzio\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\ImplicitOptionsMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }

    public function testUpdatesPipelineReferencingClassNamesOnly()
    {
        $originalContents = <<< 'EOT'
$app->pipe(ImplicitHeadMiddleware::class);
$app->pipe(ImplicitOptionsMiddleware::class);
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Mezzio\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\ImplicitOptionsMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }

    public function testUpdatesPipelineReferencingOnlyRoutingAndDispatchMiddleware()
    {
        $originalContents = <<< 'EOT'
$app->pipeRoutingMiddleware();
$app->pipeDispatchMiddleware();
EOT;
        file_put_contents($this->url . '/config/pipeline.php', $originalContents);

        $expectedContents = <<< 'EOT'
$app->pipe(\Mezzio\Router\Middleware\RouteMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\DispatchMiddleware::class);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('pipeRoutingMiddleware()'))->shouldBeCalled();
        $output->writeln(Argument::containingString('pipeDispatchMiddleware()'))->shouldBeCalled();

        $command = new UpdatePipeline();
        $this->assertNull($command($output->reveal(), $this->url));

        $test = file_get_contents($this->url . '/config/pipeline.php');
        $this->assertEquals($expectedContents, $test);
    }
}
