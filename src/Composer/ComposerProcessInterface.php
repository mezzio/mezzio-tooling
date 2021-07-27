<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

interface ComposerProcessInterface
{
    public function run(): ComposerProcessResultInterface;
}
