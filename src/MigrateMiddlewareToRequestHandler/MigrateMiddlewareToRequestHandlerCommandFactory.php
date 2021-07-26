<?php

declare(strict_types=1);

namespace Mezzio\Tooling\MigrateMiddlewareToRequestHandler;

use function getcwd;
use function realpath;

final class MigrateMiddlewareToRequestHandlerCommandFactory
{
    public function __invoke(): MigrateMiddlewareToRequestHandlerCommand
    {
        return new MigrateMiddlewareToRequestHandlerCommand(realpath(getcwd()));
    }
}
