<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ConfigFileNotWritableException;
use Mezzio\Tooling\Factory\ConfigInjector;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function touch;

class ConfigInjectorTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var ConfigInjector */
    private $injector;

    /** @var string */
    private $projectRoot;

    protected function setUp(): void
    {
        $this->dir         = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/config', $this->dir);

        $this->injector = new ConfigInjector($this->projectRoot);
    }

    public function testRaisesExceptionIfConfigNotPresentAndDirectoryIsNotWritable(): void
    {
        $dir = $this->dir->getChild('config/autoload');
        $dir->chmod(0544);

        $this->expectException(ConfigFileNotWritableException::class);
        $this->injector->injectFactoryForClass(self::class . 'Factory', self::class);
    }

    public function testRaisesExceptionIfConfigPresentButIsNotWritable(): void
    {
        touch($this->projectRoot . '/' . ConfigInjector::CONFIG_FILE);
        $file = $this->dir->getChild(ConfigInjector::CONFIG_FILE);
        $file->chmod(0444);

        $this->expectException(ConfigFileNotWritableException::class);
        $this->injector->injectFactoryForClass(self::class . 'Factory', self::class);
    }

    public function testCreatesConfigFileIfItDidNotPreviouslyExist(): void
    {
        $this->injector->injectFactoryForClass(self::class . 'Factory', self::class);
        $config = include $this->projectRoot . '/' . ConfigInjector::CONFIG_FILE;
        self::assertIsArray($config);
        self::assertTrue(isset($config['dependencies']['factories']));
        self::assertCount(1, $config['dependencies']['factories']);
        self::assertTrue(isset($config['dependencies']['factories'][self::class]));
        self::assertEquals(self::class . 'Factory', $config['dependencies']['factories'][self::class]);
    }

    public function testAddsNewEntryToConfigFile(): void
    {
        $configFile = $this->projectRoot . '/' . ConfigInjector::CONFIG_FILE;
        $contents   = <<<'EOT'
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

        $this->injector->injectFactoryForClass(self::class . 'Factory', self::class);
        $config = include $this->projectRoot . '/' . ConfigInjector::CONFIG_FILE;
        self::assertIsArray($config);
        self::assertTrue(isset($config['dependencies']['factories']));

        $factories = $config['dependencies']['factories'];
        self::assertCount(2, $factories);

        self::assertEquals('App\Handler\HelloWorldHandlerFactory', $factories['App\Handler\HelloWorldHandler']);
        self::assertEquals(self::class . 'Factory', $factories[self::class]);
    }
}
