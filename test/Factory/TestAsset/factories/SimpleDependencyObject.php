<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

use Psr\Container\ContainerInterface;
use MezzioTest\Tooling\Factory\TestAsset\InvokableObject;

class SimpleDependencyObjectFactory
{
    public function __invoke(ContainerInterface $container) : SimpleDependencyObject
    {
        return new SimpleDependencyObject($container->get(InvokableObject::class));
    }
}
