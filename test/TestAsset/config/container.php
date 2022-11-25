<?php

declare(strict_types=1);

use PHPUnit\Framework\MockObject\Generator;
use Psr\Container\ContainerInterface;

// Note: not using an anonymous class because `psr/container` changes major version constantly,
//       and is a pain to maintain in dependency upgrades.
return (new Generator())
    ->getMock(ContainerInterface::class);
