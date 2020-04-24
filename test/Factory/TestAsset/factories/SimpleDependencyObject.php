<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

use MezzioTest\Tooling\Factory\TestAsset\InvokableObject;
use Psr\Container\ContainerInterface;

class SimpleDependencyObjectFactory
{
    public function __invoke(ContainerInterface $container) : SimpleDependencyObject
    {
        return new SimpleDependencyObject($container->get(InvokableObject::class));
    }
}
