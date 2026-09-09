<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Psr\Container\ContainerInterface;

use function getcwd;
use function realpath;

final class CreateCommandFactory
{
    public function __invoke(ContainerInterface $container): CreateCommand
    {
        $path = realpath(getcwd() ?: '.');
        $path = $path === false ? '/tmp' : $path;

        return new CreateCommand(
            $container->get('config'),
            $path,
        );
    }
}
