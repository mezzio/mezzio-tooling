<?php

namespace Mezzio\Tooling\Module;

use function getcwd;
use function realpath;

class RegisterCommandFactory
{
    public function __invoke(): RegisterCommand
    {
        return new RegisterCommand(realpath(getcwd()));
    }
}
