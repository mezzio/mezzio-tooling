<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\FactoryClassGenerator;
use MezzioTest\Tooling\Factory\TestAsset\ComplexDependencyObject;
use MezzioTest\Tooling\Factory\TestAsset\InvokableObject;
use MezzioTest\Tooling\Factory\TestAsset\SimpleDependencyObject;
use PHPUnit\Framework\TestCase;

class FactoryClassGeneratorTest extends TestCase
{
    /**
     * @var FactoryClassGenerator
     */
    private $generator;

    protected function setUp() : void
    {
        $this->generator = new FactoryClassGenerator();
    }

    public function testCreateFactoryCreatesForInvokable()
    {
        $className = InvokableObject::class;
        $factory = file_get_contents(__DIR__ . '/TestAsset/factories/InvokableObject.php');

        self::assertEquals($factory, $this->generator->createFactory($className));
    }

    public function testCreateFactoryCreatesForSimpleDependencies()
    {
        $className = SimpleDependencyObject::class;
        $factory = file_get_contents(__DIR__. '/TestAsset/factories/SimpleDependencyObject.php');

        self::assertEquals($factory, $this->generator->createFactory($className));
    }

    public function testCreateFactoryCreatesForComplexDependencies()
    {
        $className = ComplexDependencyObject::class;
        $factory = file_get_contents(__DIR__. '/TestAsset/factories/ComplexDependencyObject.php');

        self::assertEquals($factory, $this->generator->createFactory($className));
    }

    /**
     * @runTestInSeparateProcess
     */
    public function testCreateFactoryCreatesAppropriatelyNamedFactoryWhenClassNameAppearsWithinNamespace()
    {
        require __DIR__ . '/TestAsset/classes/ClassDuplicatingNamespaceName.php';
        $className = 'This\Duplicates\ClassDuplicatingNamespaceNameCase\ClassDuplicatingNamespaceName';
        $factory = file_get_contents(__DIR__ . '/TestAsset/factories/ClassDuplicatingNamespaceName.php');

        self::assertEquals($factory, $this->generator->createFactory($className));
    }
}
