<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Tooling\MigrateOriginalMessageCalls;

use Laminas\Stdlib\ConsoleHelper;
use Mezzio\Tooling\MigrateOriginalMessageCalls\Help;
use PHPUnit_Framework_TestCase as TestCase;
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
                    return false !== strpos($message, 'migrate-original-message-calls');
                }),
                true,
                $resource
            )
            ->shouldBeCalled();

        $command = new Help(
            'migrate-original-message-calls',
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
