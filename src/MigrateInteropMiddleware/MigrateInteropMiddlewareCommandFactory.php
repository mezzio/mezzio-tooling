<?php

declare(strict_types=1);

namespace Mezzio\Tooling\MigrateInteropMiddleware;

use function getcwd;
use function realpath;

final class MigrateInteropMiddlewareCommandFactory
{
    public function __invoke(): MigrateInteropMiddlewareCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new MigrateInteropMiddlewareCommand($path);
    }
}
