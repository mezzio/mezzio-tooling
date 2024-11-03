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
    public function __construct(
        private readonly string $configFilePath,
        private readonly Application $app,
        private readonly MiddlewareFactory $middlewareFactory,
        private readonly ContainerInterface $container
    ) {
    }

    public function load(): void
    {
        if (! file_exists($this->configFilePath)) {
            throw new InvalidArgumentException("Configuration file not found: {$this->configFilePath}");
        }

        (require $this->configFilePath)(
            $this->app,
            $this->middlewareFactory,
            $this->container
        );
    }
}
