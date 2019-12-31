<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Tooling\MigrateMezzio22;

use Mezzio\Tooling\MigrateMezzio22\UpdateConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigTest extends TestCase
{
    public function setUp()
    {
        $this->root = vfsStream::setup('mezzio22');
        $this->url = vfsStream::url('mezzio22');
        mkdir($this->url . '/config');
        touch($this->url . '/config/config.php');
    }

    public function testInjectsProvidersInStandardSkeletonSetup()
    {
        $originalConfig = <<< 'EOT'
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

        $expectedConfig = <<< 'EOT'
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

        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::containingString('Adding Mezzio\Router\ConfigProvider to config'))
            ->shouldBeCalled();
        $output
            ->writeln(Argument::containingString('Adding Mezzio\ConfigProvider to config'))
            ->shouldBeCalled();

        $updateConfig = new UpdateConfig();

        $this->assertNull($updateConfig($output->reveal(), $this->url));
    }

    public function testInjectsProvidersWhenConfigReferencesFullyQualifiedAggregatorClassName()
    {
        $originalConfig = <<< 'EOT'
$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator([
    // Include cache configuration
    new \Laminas\ConfigAggregator\ArrayProvider($cacheConfig),

    // Default App module config
    \App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
EOT;

        $expectedConfig = <<< 'EOT'
$aggregator = new \Laminas\ConfigAggregator\ConfigAggregator([
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    // Include cache configuration
    new \Laminas\ConfigAggregator\ArrayProvider($cacheConfig),

    // Default App module config
    \App\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new \Laminas\ConfigAggregator\PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
EOT;

        $output = $this->prophesize(OutputInterface::class);
        $output
            ->writeln(Argument::containingString('Adding Mezzio\Router\ConfigProvider to config'))
            ->shouldBeCalled();
        $output
            ->writeln(Argument::containingString('Adding Mezzio\ConfigProvider to config'))
            ->shouldBeCalled();

        $updateConfig = new UpdateConfig();

        $this->assertNull($updateConfig($output->reveal(), $this->url));
    }
}
