<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

use Prophecy\Prophet;
use Psr\Container\ContainerInterface;

$prophet = new Prophet();
$container = $prophet->prophesize(ContainerInterface::class);

return $container->reveal();
