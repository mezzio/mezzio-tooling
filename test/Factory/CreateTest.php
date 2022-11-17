<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\Create;
use Mezzio\Tooling\Factory\FactoryAlreadyExistsException;
use Mezzio\Tooling\Factory\FactoryClassGenerator;
use Mezzio\Tooling\Factory\FactoryWriteException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use TestHarness\NotReal\TestClass;

use function file_put_contents;

class CreateTest extends TestCase
{
    use ProphecyTrait;

    private vfsStreamDirectory $dir;

    private Create $factory;

    private string $projectRoot;

    protected function setUp(): void
    {
        $this->factory     = new Create(new FactoryClassGenerator());
        $this->dir         = vfsStream::setup('project');
        $this->projectRoot = vfsStream::url('project');
        vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/classes', $this->dir);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRaisesExceptionWhenFactoryClassFileAlreadyExists(): void
    {
        require $this->projectRoot . '/TestClass.php';
        $className = TestClass::class;
        file_put_contents($this->projectRoot . '/TestClassFactory.php', '');

        $this->expectException(FactoryAlreadyExistsException::class);
        $this->factory->createForClass($className);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRaisesExceptionWhenUnableToWriteFactory(): void
    {
        require $this->projectRoot . '/TestClass.php';
        $this->dir->chmod(0544);
        $className = TestClass::class;

        $generator = $this->prophesize(FactoryClassGenerator::class);
        $generator->createFactory($className)->willReturn('not-generated');

        $factory = new Create($generator->reveal());

        $this->expectException(FactoryWriteException::class);
        $factory->createForClass($className);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanCreateFactoryFile(): void
    {
        require $this->projectRoot . '/TestClass.php';
        $className = TestClass::class;

        $generator = new FactoryClassGenerator();
        $factory   = new Create($generator);

        $fileName = $factory->createForClass($className);

        self::assertStringContainsString('TestClassFactory.php', $fileName);

        require $fileName;
        $factoryName = $className . 'Factory';
        $factory     = new $factoryName();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(FactoryClassGenerator::class)->willReturn($generator);
        $instance = $factory($container->reveal());
        self::assertInstanceOf($className, $instance);
    }
}
