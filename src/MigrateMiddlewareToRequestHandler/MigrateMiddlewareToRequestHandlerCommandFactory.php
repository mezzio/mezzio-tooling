<?php

namespace Mezzio\Tooling\MigrateMiddlewareToRequestHandler;

use function getcwd;
use function realpath;

class MigrateMiddlewareToRequestHandlerCommandFactory
{
    public function __invoke(): MigrateMiddlewareToRequestHandlerCommand
    {
        return new MigrateMiddlewareToRequestHandlerCommand(realpath(getcwd()));
    }
}
