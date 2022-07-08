<?php

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
    use ProphecyTrait;

    /**
     * @var RouteCollector|ObjectProphecy $routeCollection
     */
    private $routeCollection;

    public function testCanInstantiateListRoutesCommandObject()
    {
        $this->routeCollection = $this->prophesize(RouteCollector::class);

        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        $container
            ->get(RouteCollector::class)
            ->willReturn($this->routeCollection->reveal());
        $factory = new ListRoutesCommandFactory();

        $this->assertInstanceOf(
            ListRoutesCommand::class,
            $factory->__invoke($container->reveal())
        );
    }

}
