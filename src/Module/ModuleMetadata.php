<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use function preg_replace;

final class ModuleMetadata
{
    private readonly string $sourcePath;

    public function __construct(
        private readonly string $name,
        private readonly string $rootPath,
        string $sourcePath
    ) {
        $this->sourcePath = preg_replace('#^\./#', '', $sourcePath);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function sourcePath(): string
    {
        return $this->sourcePath;
    }
}
