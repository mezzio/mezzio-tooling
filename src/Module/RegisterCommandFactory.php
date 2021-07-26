<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use function getcwd;
use function realpath;

final class RegisterCommandFactory
{
    public function __invoke(): RegisterCommand
    {
        return new RegisterCommand(realpath(getcwd()));
    }
}
