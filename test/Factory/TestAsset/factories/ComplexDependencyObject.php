<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

use Psr\Container\ContainerInterface;
use MezzioTest\Tooling\Factory\TestAsset\SecondComplexDependencyObject;
use MezzioTest\Tooling\Factory\TestAsset\SimpleDependencyObject;

class ComplexDependencyObjectFactory
{
    public function __invoke(ContainerInterface $container) : ComplexDependencyObject
    {
        return new ComplexDependencyObject(
            $container->get(SimpleDependencyObject::class),
            $container->get(SecondComplexDependencyObject::class)
        );
    }
}
