<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ConfigFileNotWritableException;
use Mezzio\Tooling\Factory\ConfigInjector;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class ConfigInjectorTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var ConfigInjector */
    private $injector;

    /** @var string */
    private $projectRoot;

    protected function setUp() : void
    {
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/config', $this->dir);

        $this->injector = new ConfigInjector($this->projectRoot);
    }

    public function testRaisesExceptionIfConfigNotPresentAndDirectoryIsNotWritable()
    {
        $dir = $this->dir->getChild('config/autoload');
        $dir->chmod(0544);

        $this->expectException(ConfigFileNotWritableException::class);
        $this->injector->injectFactoryForClass(__CLASS__ . 'Factory', __CLASS__);
    }

    public function testRaisesExceptionIfConfigPresentButIsNotWritable()
    {
        touch($this->projectRoot . '/' . ConfigInjector::CONFIG_FILE);
        $file = $this->dir->getChild(ConfigInjector::CONFIG_FILE);
        $file->chmod(0444);

        $this->expectException(ConfigFileNotWritableException::class);
        $this->injector->injectFactoryForClass(__CLASS__ . 'Factory', __CLASS__);
    }

    public function testCreatesConfigFileIfItDidNotPreviouslyExist()
    {
        $this->injector->injectFactoryForClass(__CLASS__ . 'Factory', __CLASS__);
        $config = include($this->projectRoot . '/' . ConfigInjector::CONFIG_FILE);
        $this->assertIsArray($config);
        $this->assertTrue(isset($config['dependencies']['factories']));
        $this->assertCount(1, $config['dependencies']['factories']);
        $this->assertTrue(isset($config['dependencies']['factories'][__CLASS__]));
        $this->assertEquals(__CLASS__ . 'Factory', $config['dependencies']['factories'][__CLASS__]);
    }

    public function testAddsNewEntryToConfigFile()
    {
        $configFile = $this->projectRoot . '/' . ConfigInjector::CONFIG_FILE;
        $contents = <<<'EOT'
<?php
return [
    'dependencies' => [
        'factories' => [
            App\Handler\HelloWorldHandler::class => App\Handler\HelloWorldHandlerFactory::class,
        ],
    ],
];
EOT;
        file_put_contents($configFile, $contents);

        $this->injector->injectFactoryForClass(__CLASS__ . 'Factory', __CLASS__);
        $config = include($this->projectRoot . '/' . ConfigInjector::CONFIG_FILE);
        $this->assertIsArray($config);
        $this->assertTrue(isset($config['dependencies']['factories']));

        $factories = $config['dependencies']['factories'];
        $this->assertCount(2, $factories);

        $this->assertEquals('App\Handler\HelloWorldHandlerFactory', $factories['App\Handler\HelloWorldHandler']);
        $this->assertEquals(__CLASS__ . 'Factory', $factories[__CLASS__]);
    }
}
