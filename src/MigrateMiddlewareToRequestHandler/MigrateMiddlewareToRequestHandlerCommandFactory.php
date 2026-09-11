<?php

declare(strict_types=1);

namespace Mezzio\Tooling\MigrateMiddlewareToRequestHandler;

use function getcwd;
use function realpath;

final class MigrateMiddlewareToRequestHandlerCommandFactory
{
    public function __invoke(): MigrateMiddlewareToRequestHandlerCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new MigrateMiddlewareToRequestHandlerCommand($path);
    }
}
