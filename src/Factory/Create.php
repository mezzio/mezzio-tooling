<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use ReflectionClass;

use function class_exists;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_writable;
use function sprintf;
use function strrpos;
use function substr;

/** @internal */
class Create
{
    /** @var FactoryClassGenerator */
    private $generator;

    public function __construct(FactoryClassGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return string Filename where factory was created
     * @throws FactoryAlreadyExistsException If a matching factory class file
     *     already exists in the filesystem.
     * @throws ClassNotFoundException If the class cannot be autoloaded.
     * @throws FactoryWriteException If unable to write the factory class file.
     */
    public function createForClass(string $className): string
    {
        if (! class_exists($className)) {
            throw ClassNotFoundException::forClassName($className);
        }

        $factoryFileName = sprintf(
            '%s/%sFactory.php',
            $this->getPathForClass($className),
            $this->getClassName($className)
        );

        if (file_exists($factoryFileName)) {
            throw FactoryAlreadyExistsException::forClassUsingFile($className, $factoryFileName);
        }

        if (! is_writable(dirname($factoryFileName))) {
            throw FactoryWriteException::whenCreatingFile($factoryFileName);
        }

        $factory = $this->generator->createFactory($className);

        if (false === file_put_contents($factoryFileName, $factory)) {
            throw FactoryWriteException::whenCreatingFile($factoryFileName);
        }

        return $factoryFileName;
    }

    private function getPathForClass(string $className): string
    {
        $fileName = (new ReflectionClass($className))->getFileName();
        return dirname($fileName);
    }

    public function getClassName(string $class): string
    {
        return substr($class, strrpos($class, '\\') + 1);
    }
}
