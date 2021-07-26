<?php

declare(strict_types=1);

use Prophecy\Prophet;
use Psr\Container\ContainerInterface;

$prophet   = new Prophet();
$container = $prophet->prophesize(ContainerInterface::class);

return $container->reveal();
