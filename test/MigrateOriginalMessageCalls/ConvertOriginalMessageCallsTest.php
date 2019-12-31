<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Tooling\MigrateOriginalMessageCalls;

use Mezzio\Tooling\MigrateOriginalMessageCalls\ConvertOriginalMessageCalls;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ConvertOriginalMessageCallsTest extends TestCase
{
    use ProjectSetupTrait;

    public function testConvertsFilesAndEmitsInfoMessagesAsExpected()
    {
        $dir = vfsStream::setup('migrate');
        $this->setupSrcDir($dir);
        $path = vfsStream::url('migrate');

        $console = $this->setupConsoleHelper();

        $converter = new ConvertOriginalMessageCalls($console->reveal());
        $converter->process($path);
    }
}
