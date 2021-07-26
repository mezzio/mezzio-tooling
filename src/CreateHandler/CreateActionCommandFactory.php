<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use Psr\Container\ContainerInterface;

use function getcwd;
use function realpath;

final class CreateActionCommandFactory
{
    public function __invoke(ContainerInterface $container): CreateActionCommand
    {
        return new CreateActionCommand($container, realpath(getcwd()));
    }
}
