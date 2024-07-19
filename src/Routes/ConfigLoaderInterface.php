<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

interface ConfigLoaderInterface
{
    public function load(): void;
}
