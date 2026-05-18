<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

use stdClass;

class RootNamespaceDependencyObject
{
    public function __construct(stdClass $stdClass)
    {
    }
}
