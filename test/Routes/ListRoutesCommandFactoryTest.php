<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Tooling\Routes\ListRoutesCommand;
use Mezzio\Tooling\Routes\ListRoutesCommandFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ListRoutesCommandFactoryTest extends TestCase
{
    public function testCanInstantiateListRoutesCommandObject(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->atMost(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(Application::class),
                $this->createMock(MiddlewareFactory::class),
            );
        $factory = new ListRoutesCommandFactory();

        $this->assertInstanceOf(
            ListRoutesCommand::class,
            $factory->__invoke($container)
        );
    }
}
