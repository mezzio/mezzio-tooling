<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

use function array_filter;
use function array_keys;
use function array_map;
use function array_shift;
use function count;
use function implode;
use function natsort;
use function sprintf;
use function str_repeat;
use function strrpos;
use function substr;

/** @internal */
class FactoryClassGenerator
{
    public const FACTORY_TEMPLATE = <<<'EOT'
        <?php
        
        declare(strict_types=1);
        
        namespace %2$s;
        
        %3$s
        
        class %1$sFactory
        {
            public function __invoke(ContainerInterface $container) : %1$s
            {
                return new %1$s(%4$s);
            }
        }
        
        EOT;

    public function createFactory(string $className): string
    {
        $class                 = $this->getClassName($className);
        $namespace             = $this->getNamespace($className);
        $constructorParameters = $this->getConstructorParameters($className);

        $imports   = array_keys($constructorParameters);
        $imports[] = ContainerInterface::class;

        return sprintf(
            self::FACTORY_TEMPLATE,
            $class,
            $namespace,
            $this->formatImportStatements($imports),
            $this->createArgumentString($constructorParameters)
        );
    }

    private function getClassName(string $className): string
    {
        return substr($className, strrpos($className, '\\') + 1);
    }

    private function getNamespace(string $className): string
    {
        return substr($className, 0, strrpos($className, '\\'));
    }

    /**
     * @throws UnidentifiedTypeException If a parameter defines a non-class typehint.
     */
    private function getConstructorParameters(string $className): array
    {
        $reflectionClass = new ReflectionClass($className);

        if (! $reflectionClass->getConstructor()) {
            return [];
        }

        $constructorParameters = $reflectionClass->getConstructor()->getParameters();

        if (! $constructorParameters) {
            return [];
        }

        $constructorParameters = array_filter(
            $constructorParameters,
            function (ReflectionParameter $argument) {
                if ($argument->isOptional()) {
                    return false;
                }

                if (null === $argument->getType()) {
                    throw UnidentifiedTypeException::forArgument($argument->getName());
                }

                return true;
            }
        );

        if (! $constructorParameters) {
            return [];
        }

        $mappedParameters = [];
        foreach ($constructorParameters as $parameter) {
            $reflectionType = $parameter->getType();
            if ($reflectionType !== null) {
                $fqcn                    = $reflectionType->getName();
                $mappedParameters[$fqcn] = $this->getClassName($fqcn);
            }
        }

        return $mappedParameters;
    }

    private function formatImportStatements(array $imports): string
    {
        natsort($imports);
        $imports = array_map(function ($import) {
            return sprintf('use %s;', $import);
        }, $imports);
        return implode("\n", $imports);
    }

    private function createArgumentString(array $arguments): string
    {
        $arguments = array_map(function ($argument) {
            return sprintf('$container->get(%s::class)', $argument);
        }, $arguments);
        switch (count($arguments)) {
            case 0:
                return '';
            case 1:
                return array_shift($arguments);
            default:
                $argumentPad = str_repeat(' ', 12);
                $closePad    = str_repeat(' ', 8);
                return sprintf(
                    "\n%s%s\n%s",
                    $argumentPad,
                    implode(",\n" . $argumentPad, $arguments),
                    $closePad
                );
        }
    }
}
