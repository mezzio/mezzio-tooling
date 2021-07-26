<?php

declare(strict_types=1);

namespace Mezzio\Tooling\MigrateInteropMiddleware;

use function getcwd;
use function realpath;

final class MigrateInteropMiddlewareCommandFactory
{
    public function __invoke(): MigrateInteropMiddlewareCommand
    {
        return new MigrateInteropMiddlewareCommand(realpath(getcwd()));
    }
}
