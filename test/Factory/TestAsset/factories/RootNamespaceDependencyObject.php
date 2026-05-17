<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

use Psr\Container\ContainerInterface;
use stdClass;

class RootNamespaceDependencyObjectFactory
{
    public function __invoke(ContainerInterface $container) : RootNamespaceDependencyObject
    {
        return new RootNamespaceDependencyObject($container->get(stdClass::class));
    }
}
