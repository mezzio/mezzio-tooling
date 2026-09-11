<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateMiddleware;

use function getcwd;
use function realpath;

final class CreateMiddlewareCommandFactory
{
    public function __invoke(): CreateMiddlewareCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new CreateMiddlewareCommand($path);
    }
}
