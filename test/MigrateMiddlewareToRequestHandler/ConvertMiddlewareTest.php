<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateMiddlewareToRequestHandler;

use Mezzio\Tooling\MigrateMiddlewareToRequestHandler\ConvertMiddleware;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ConvertMiddlewareTest extends TestCase
{
    use ProjectSetupTrait;

    public function testConvertsFilesAndEmitsInfoMessagesAsExpected()
    {
        $dir = vfsStream::setup('migrate');
        $this->setupSrcDir($dir);
        $path = vfsStream::url('migrate');

        $console = $this->setupConsoleHelper();

        $converter = new ConvertMiddleware($console->reveal());
        $converter->process($path);

        self::assertExpected($path);
    }
}
