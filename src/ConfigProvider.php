<?php

declare(strict_types=1);

namespace Mezzio\Tooling;

use Mezzio\Tooling\CreateHandler\CreateHandlerCommand;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommand;
use Mezzio\Tooling\Factory\CreateFactoryCommand;
use Mezzio\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommand;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand;
use Mezzio\Tooling\Module\CreateCommand;
use Mezzio\Tooling\Module\DeregisterCommand;
use Mezzio\Tooling\Module\RegisterCommand;

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
                'mezzio:action:create'                 => CreateHandlerCommand::class,
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
            'invokables' => [
                CreateCommand::class                            => CreateCommand::class,
                CreateFactoryCommand::class                     => CreateFactoryCommand::class,
                CreateHandlerCommand::class                     => CreateHandlerCommand::class,
                CreateMiddlewareCommand::class                  => CreateMiddlewareCommand::class,
                DeregisterCommand::class                        => DeregisterCommand::class,
                MigrateInteropMiddlewareCommand::class          => MigrateInteropMiddlewareCommand::class,
                MigrateMiddlewareToRequestHandlerCommand::class => MigrateMiddlewareToRequestHandlerCommand::class,
                RegisterCommand::class                          => RegisterCommand::class,
            ],
        ];
    }
}
