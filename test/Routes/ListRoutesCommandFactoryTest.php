<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use Mezzio\Tooling\Routes\ListRoutesCommandFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ListRoutesCommandFactoryTest extends TestCase
{
    public function testCanInstantiateListRoutesCommandObject(): void
    {
        /** @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->atMost(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(Application::class),
                $this->createMock(MiddlewareFactory::class),
                $this->createMock(RouteCollector::class),
            );
        $factory = new ListRoutesCommandFactory();

        $this->assertInstanceOf(
            ListRoutesCommand::class,
            $factory->__invoke($container)
        );
    }
}
