<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * This class provides a default factory implementation for retrieving an app's routes

 * The assumption with this "default" config loader factory is that you're
 * loading routes from config/routes.php in your Mezzio application. So, if
 * that's not the case in your application, create a custom loader
 * implementation and override the alias in the DiC.
 */
final class DefaultRoutesConfigLoaderFactory
{
    public function __invoke(ContainerInterface $container): ConfigLoaderInterface
    {
        /** @var Application $application */
        $application = $container->get(Application::class);

        /** @var MiddlewareFactory $factory */
        $factory = $container->get(MiddlewareFactory::class);

        return new RoutesFileConfigLoader(
            'config/routes.php',
            $application,
            $factory,
            $container
        );
    }
}
