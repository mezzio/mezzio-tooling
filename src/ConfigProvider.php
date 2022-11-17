<?php // phpcs:disable Generic.Files.LineLength.TooLong


declare(strict_types=1);

namespace Mezzio\Tooling;

use Mezzio\Tooling\CreateHandler\CreateActionCommand;
use Mezzio\Tooling\CreateHandler\CreateActionCommandFactory;
use Mezzio\Tooling\CreateHandler\CreateHandlerCommand;
use Mezzio\Tooling\CreateHandler\CreateHandlerCommandFactory;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommand;
use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareCommandFactory;
use Mezzio\Tooling\Factory\Create;
use Mezzio\Tooling\Factory\CreateFactory;
use Mezzio\Tooling\Factory\CreateFactoryCommand;
use Mezzio\Tooling\Factory\CreateFactoryCommandFactory;
use Mezzio\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommand;
use Mezzio\Tooling\MigrateInteropMiddleware\MigrateInteropMiddlewareCommandFactory;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommand;
use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\MigrateMiddlewareToRequestHandlerCommandFactory;
use Mezzio\Tooling\Module\CreateCommand;
use Mezzio\Tooling\Module\CreateCommandFactory;
use Mezzio\Tooling\Module\DeregisterCommand;
use Mezzio\Tooling\Module\DeregisterCommandFactory;
use Mezzio\Tooling\Module\RegisterCommand;
use Mezzio\Tooling\Module\RegisterCommandFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli'  => $this->getConsoleConfig(),
        ];
    }

    /**
     * @return array<string, array{mezzio:action:create: class-string<CreateActionCommand>, mezzio:factory:create: class-string<CreateFactoryCommand>, mezzio:handler:create: class-string<CreateHandlerCommand>, mezzio:middleware:create: class-string<CreateMiddlewareCommand>, mezzio:middleware:migrate-from-interop: class-string<MigrateInteropMiddlewareCommand>, mezzio:middleware:to-request-handler: class-string<MigrateMiddlewareToRequestHandlerCommand>, mezzio:module:create: class-string<CreateCommand>, mezzio:module:deregister: class-string<DeregisterCommand>, mezzio:module:register: class-string<RegisterCommand>}>
     */
    public function getConsoleConfig(): array
    {
        return [
            'commands' => [
                'mezzio:action:create'                   => CreateActionCommand::class,
                'mezzio:factory:create'                  => CreateFactoryCommand::class,
                'mezzio:handler:create'                  => CreateHandlerCommand::class,
                'mezzio:middleware:create'               => CreateMiddlewareCommand::class,
                'mezzio:middleware:migrate-from-interop' => MigrateInteropMiddlewareCommand::class,
                'mezzio:middleware:to-request-handler'   => MigrateMiddlewareToRequestHandlerCommand::class,
                'mezzio:module:create'                   => CreateCommand::class,
                'mezzio:module:deregister'               => DeregisterCommand::class,
                'mezzio:module:register'                 => RegisterCommand::class,
            ],
        ];
    }

    /**
     * @return array{factories: array{Create: string, CreateActionCommand: string, CreateCommand: string, CreateFactoryCommand: string, CreateHandlerCommand: string, CreateMiddlewareCommand: string, DeregisterCommand: string, MigrateInteropMiddlewareCommand: string, MigrateMiddlewareToRequestHandlerCommand: string, RegisterCommand: string}}
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                Create::class                                   => CreateFactory::class,
                CreateActionCommand::class                      => CreateActionCommandFactory::class,
                CreateCommand::class                            => CreateCommandFactory::class,
                CreateFactoryCommand::class                     => CreateFactoryCommandFactory::class,
                CreateHandlerCommand::class                     => CreateHandlerCommandFactory::class,
                CreateMiddlewareCommand::class                  => CreateMiddlewareCommandFactory::class,
                DeregisterCommand::class                        => DeregisterCommandFactory::class,
                MigrateInteropMiddlewareCommand::class          => MigrateInteropMiddlewareCommandFactory::class,
                MigrateMiddlewareToRequestHandlerCommand::class => MigrateMiddlewareToRequestHandlerCommandFactory::class,
                RegisterCommand::class                          => RegisterCommandFactory::class,
            ],
        ];
    }
}
