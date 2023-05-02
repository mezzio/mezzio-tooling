<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use Mezzio\Tooling\Routes\ListRoutesCommandFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class ListRoutesCommandFactoryTest extends TestCase
{
    private $routeCollection;

    public function testCanInstantiateListRoutesCommandObject()
    {
        $this->routeCollection = $this->createMock(RouteCollector::class);
        $this->routeCollection
            ->method('getRoutes')
            ->willReturn([]);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(RouteCollector::class)
            ->willReturn($this->routeCollection);
        $factory = new ListRoutesCommandFactory();

        $this->assertInstanceOf(
            ListRoutesCommand::class,
            $factory->__invoke($container)
        );
    }
}
