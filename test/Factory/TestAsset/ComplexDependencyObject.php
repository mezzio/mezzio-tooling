<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory\TestAsset;

class ComplexDependencyObject
{
    public function __construct(
        SimpleDependencyObject $simpleDependencyObject,
        SecondComplexDependencyObject $secondComplexDependencyObject
    ) {
    }
}
