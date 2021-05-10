<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

class SecondComplexDependencyObject
{
    public function __construct(InvokableObject $invokableObject)
    {
    }
}
