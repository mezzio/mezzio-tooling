<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

interface ComposerProcessResultInterface
{
    public function isSuccessful(): bool;

    public function getOutput(): string;

    public function getErrorOutput(): string;
}
