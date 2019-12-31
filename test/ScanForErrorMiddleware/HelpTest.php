<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Tooling\ScanForErrorMiddleware;

use Laminas\Stdlib\ConsoleHelper;
use Mezzio\Tooling\ScanForErrorMiddleware\Help;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class HelpTest extends TestCase
{
    public function testWritesHelpMessageToConsoleUsingCommandProvidedAtInstantiationAndResourceAtInvocation()
    {
        $resource = fopen('php://temp', 'wb+');

        $console = $this->prophesize(ConsoleHelper::class);
        $console
            ->writeLine(
                Argument::that(function ($message) {
                    return false !== strpos($message, 'scan-for-error-middleware');
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            'scan-for-error-middleware',
            $console->reveal()
        );

        $this->assertNull($command($resource));
    }

    public function testTruncatesCommandToBasenameIfItIsARealpath()
    {
        $resource = fopen('php://temp', 'wb+');

        $console = $this->prophesize(ConsoleHelper::class);
        $console
            ->writeLine(
                Argument::that(function ($message) {
                    return false !== strpos($message, basename(__FILE__));
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            realpath(__FILE__),
            $console->reveal()
        );

        $this->assertNull($command($resource));
    }
}
