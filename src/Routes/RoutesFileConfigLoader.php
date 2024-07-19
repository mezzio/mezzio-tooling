<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use InvalidArgumentException;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

use function file_exists;

final class RoutesFileConfigLoader implements ConfigLoaderInterface
{
    private string $configFilePath;
    private Application $app;
    private MiddlewareFactory $middlewareFactory;
    private ContainerInterface $container;

    public function __construct(
        string $configFilePath,
        Application $app,
        MiddlewareFactory $middlewareFactory,
        ContainerInterface $container,
    ) {
        $this->configFilePath = $configFilePath;
        $this->app = $app;
        $this->middlewareFactory = $middlewareFactory;
        $this->container = $container;
    }

    public function load(): void
    {
        if (! file_exists($this->configFilePath)) {
            throw new InvalidArgumentException("Configuration file not found: {$this->configFilePath}");
        }

        (require $configFilePath)(
            $this->app,
            $this->middlewareFactory,
            $this->container
        );
    }
}
