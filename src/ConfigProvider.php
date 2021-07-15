<?php

declare(strict_types=1);

namespace Mezzio\Tooling;

use Mezzio\Tooling\CreateHandler\CreateActionCommand;
use Mezzio\Tooling\CreateHandler\CreateActionCommandFactory;
use Mezzio\Tooling\CreateHandler\CreateHandlerCommand;
use Mezzio\Tooling\CreateHandler\CreateHandlerCommandFactory;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommand;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommandFactory;
use Mezzio\Tooling\Factory\CreateFactoryCommand;
use Mezzio\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommand;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand;
use Mezzio\Tooling\Module\CreateCommand;
use Mezzio\Tooling\Module\CreateCommandFactory;
use Mezzio\Tooling\Module\DeregisterCommand;
use Mezzio\Tooling\Module\DeregisterCommandFactory;
use Mezzio\Tooling\Module\RegisterCommand;
use Mezzio\Tooling\Module\RegisterCommandFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli'  => $this->getConsoleConfig(),
        ];
    }

    public function getConsoleConfig(): array
    {
        return [
            'commands' => [
                'mezzio:action:create'                 => CreateActionCommand::class,
                'mezzio:factory:create'                => CreateFactoryCommand::class,
                'mezzio:handler:create'                => CreateHandlerCommand::class,
                'mezzio:middleware:create'             => CreateMiddlewareCommand::class,
                'mezzio:middleware-to-request-handler' => MigrateMiddlewareToRequestHandlerCommand::class,
                'mezzio:migrate:interop-middleware'    => MigrateInteropMiddlewareCommand::class,
                'mezzio:module:create'                 => CreateCommand::class,
                'mezzio:module:deregister'             => DeregisterCommand::class,
                'mezzio:module:register'               => RegisterCommand::class,
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                CreateActionCommand::class                      => CreateActionCommandFactory::class,
                CreateCommand::class                            => CreateCommandFactory::class,
                CreateHandlerCommand::class                     => CreateHandlerCommandFactory::class,
                CreateMiddlewareCommand::class                  => CreateMiddlewareCommandFactory::class,
                DeregisterCommand::class                        => DeregisterCommandFactory::class,
                RegisterCommand::class                          => RegisterCommandFactory::class,
            ],
            'invokables' => [
                CreateFactoryCommand::class                     => CreateFactoryCommand::class,
                MigrateInteropMiddlewareCommand::class          => MigrateInteropMiddlewareCommand::class,
                MigrateMiddlewareToRequestHandlerCommand::class => MigrateMiddlewareToRequestHandlerCommand::class,
            ],
        ];
    }
}
