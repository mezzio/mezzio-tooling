<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Tooling\MigrateMezzio22;

use Mezzio\Tooling\MigrateMezzio22\MigrateMezzio22Command;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MigrateOriginalMessageCallsCommandTest extends TestCase
{
    protected function setUp()
    {
        $this->root = vfsStream::setup('mezzio22');
        $this->url = vfsStream::url('mezzio22');
        mkdir($this->url . '/config');
        touch($this->url . '/config/config.php');
        touch($this->url . '/config/pipeline.php');

        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new MigrateMezzio22Command('migrate:mezzio-v2.2');
        $this->command->setProjectDir($this->url);
    }

    private function reflectExecuteMethod()
    {
        $r = new ReflectionMethod($this->command, 'execute');
        $r->setAccessible(true);
        return $r;
    }

    public function testConfigureSetsExpectedDescription()
    {
        $this->assertContains('Migrate an Mezzio application to version 2.2', $this->command->getDescription());
    }

    public function testConfigureSetsExpectedHelp()
    {
        $this->assertEquals(MigrateMezzio22Command::HELP, $this->command->getHelp());
    }

    public function testCommandUpdatesConfigAndPipeline()
    {
        $config = sprintf('%s/config/config.php', $this->url);
        $pipeline = sprintf('%s/config/pipeline.php', $this->url);

        file_put_contents($config, $this->getOriginalConfig());
        file_put_contents($pipeline, $this->getOriginalPipeline());

        $this->output
            ->writeln(Argument::containingString('Migrating application to Mezzio 2.2'))
            ->shouldBeCalled();

        $this->output->writeln(Argument::containingString('- Updating config/config.php'))->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Adding Mezzio\Router\ConfigProvider to config'))
            ->shouldBeCalled();
        $this->output
            ->writeln(Argument::containingString('Adding Mezzio\ConfigProvider to config'))
            ->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('- Updating config/pipeline.php'))
            ->shouldBeCalled();
        $this->output->writeln(Argument::containingString('pipeRoutingMiddleware()'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('ImplicitHeadMiddleware'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('ImplicitOptionsMiddleware'))->shouldBeCalled();
        $this->output->writeln(Argument::containingString('pipeDispatchMiddleware()'))->shouldBeCalled();

        $this->output
            ->writeln(Argument::containingString('Done!'))
            ->shouldBeCalled();

        $method = $this->reflectExecuteMethod();
        $this->assertSame(0, $method->invoke(
            $this->command,
            $this->input->reveal(),
            $this->output->reveal()
        ));

        $configAfterUpdate = file_get_contents($config);
        $pipelineAfterUpdate = file_get_contents($pipeline);

        $this->assertEquals(
            $this->getExpectedConfig(),
            $configAfterUpdate,
            'Configuration was not updated as expected'
        );
        $this->assertEquals(
            $this->getExpectedPipeline(),
            $pipelineAfterUpdate,
            'Pipeline was not updated as expected'
        );
    }

    private function getOriginalConfig()
    {
        return <<< 'EOT'
<?php

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/config-cache.php',
];

$aggregator = new ConfigAggregator([
    // Include cache configuration
    new ArrayProvider($cacheConfig),

    // Default App module config
    App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();
EOT;
    }

    private function getOriginalPipeline()
    {
        return <<< 'EOT'
$app->pipeRoutingMiddleware();
$app->pipe(\Mezzio\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Mezzio\Middleware\ImplicitOptionsMiddleware::class);
$app->pipeDispatchMiddleware();
EOT;
    }

    public function getExpectedConfig()
    {
        return <<< 'EOT'
<?php

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/config-cache.php',
];

$aggregator = new ConfigAggregator([
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    // Include cache configuration
    new ArrayProvider($cacheConfig),

    // Default App module config
    App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();
EOT;
    }

    public function getExpectedPipeline()
    {
        return <<< 'EOT'
$app->pipe(\Mezzio\Router\Middleware\RouteMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\ImplicitHeadMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\ImplicitOptionsMiddleware::class);
$app->pipe(\Mezzio\Router\Middleware\DispatchMiddleware::class);
EOT;
    }
}
