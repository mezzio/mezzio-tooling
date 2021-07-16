<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use function getcwd;
use function realpath;

class DeregisterCommandFactory
{
    public function __invoke(): DeregisterCommand
    {
        return new DeregisterCommand(realpath(getcwd()));
    }
}
