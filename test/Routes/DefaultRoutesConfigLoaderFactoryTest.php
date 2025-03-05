<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Tooling\Routes\DefaultRoutesConfigLoaderFactory;
use Mezzio\Tooling\Routes\RoutesFileConfigLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DefaultRoutesConfigLoaderFactoryTest extends TestCase
{
    public function testCanInstantiateTheDefaultRoutesConfigLoader(): void
    {
        $factory = new DefaultRoutesConfigLoaderFactory();

        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->atMost(2))
            ->method("get")
            ->willReturnOnConsecutiveCalls(
                $this->createMock(Application::class),
                $this->createMock(MiddlewareFactory::class),
            );

        $result = $factory($container);

        $this->assertInstanceOf(RoutesFileConfigLoader::class, $result);
    }
}
