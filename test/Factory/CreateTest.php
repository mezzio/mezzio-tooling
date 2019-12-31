<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ClassNotFoundException;
use Mezzio\Tooling\Factory\Create;
use Mezzio\Tooling\Factory\FactoryAlreadyExistsException;
use Mezzio\Tooling\Factory\FactoryClassGenerator;
use Mezzio\Tooling\Factory\FactoryWriteException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CreateTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $dir;

    /** @var Create */
    private $factory;

    /** @var string */
    private $projectRoot;

    protected function setUp() : void
    {
        $this->factory = new Create();
        $this->dir = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/classes', $this->dir);
    }

    public function testRaisesExceptionWhenClassDoesNotExist()
    {
        $class = __CLASS__ . '\NotFound';
        $this->expectException(ClassNotFoundException::class);
        $this->factory->createForClass($class);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRaisesExceptionWhenFactoryClassFileAlreadyExists()
    {
        require $this->projectRoot . '/TestClass.php';
        $className = 'TestHarness\NotReal\TestClass';
        file_put_contents($this->projectRoot . '/TestClassFactory.php', '');

        $this->expectException(FactoryAlreadyExistsException::class);
        $this->factory->createForClass($className);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRaisesExceptionWhenUnableToWriteFactory()
    {
        require $this->projectRoot . '/TestClass.php';
        $this->dir->chmod(0544);
        $className = 'TestHarness\NotReal\TestClass';

        $generator = $this->prophesize(FactoryClassGenerator::class);
        $generator->createFactory($className)->willReturn('not-generated');

        $factory = new Create($generator->reveal());

        $this->expectException(FactoryWriteException::class);
        $factory->createForClass($className);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanCreateFactoryFile()
    {
        require $this->projectRoot . '/TestClass.php';
        $className = 'TestHarness\NotReal\TestClass';

        $generator = new FactoryClassGenerator();
        $factory = new Create($generator);

        $fileName = $factory->createForClass($className);

        $this->assertStringContainsString('TestClassFactory.php', $fileName);

        require $fileName;
        $factoryName = $className . 'Factory';
        $factory = new $factoryName();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(FactoryClassGenerator::class)->willReturn($generator);
        $instance = $factory($container->reveal());
        $this->assertInstanceOf($className, $instance);
    }
}
