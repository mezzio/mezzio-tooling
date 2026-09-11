<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use Psr\Container\ContainerInterface;

use function getcwd;
use function realpath;

final class CreateHandlerCommandFactory
{
    public function __invoke(ContainerInterface $container): CreateHandlerCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new CreateHandlerCommand($container, $path);
    }
}
