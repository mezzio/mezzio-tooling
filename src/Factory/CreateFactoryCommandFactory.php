<?php

namespace Mezzio\Tooling\Factory;

use Psr\Container\ContainerInterface;

use function getcwd;
use function realpath;

class CreateFactoryCommandFactory
{
    public function __invoke(ContainerInterface $container): CreateFactoryCommand
    {
        return new CreateFactoryCommand(
            $container->get(Create::class),
            realpath(getcwd())
        );
    }
}
