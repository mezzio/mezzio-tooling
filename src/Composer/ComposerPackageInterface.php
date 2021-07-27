<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

interface ComposerPackageInterface
{
    /**
     * Add a PSR-4 autoloading rule to the project package.
     *
     * A "true" return value indicates an update was made.
     */
    public function addPsr4AutoloadRule(string $namespace, string $path, bool $isDev = false): bool;

    /**
     * Remove a PSR-4 autoloading rule from the project package.
     *
     * A "true" return value indicates an update was made.
     */
    public function removePsr4AutoloadRule(string $namespace, bool $isDev = false): bool;
}
