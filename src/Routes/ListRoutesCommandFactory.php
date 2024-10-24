<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Tooling\Routes\RoutesFileConfigLoader;
use Psr\Container\ContainerInterface;

final class ListRoutesCommandFactory
{
    public function __invoke(ContainerInterface $container): ListRoutesCommand
    {
        /** @var Application $application */
        $application = $container->get(Application::class);

        /** @var MiddlewareFactory $factory */
        $factory = $container->get(MiddlewareFactory::class);

        $configLoader = new RoutesFileConfigLoader('config/routes.php', $application, $factory, $container);

        return new ListRoutesCommand($container, $configLoader);
    }
}
