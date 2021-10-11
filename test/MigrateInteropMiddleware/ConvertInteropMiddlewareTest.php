<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateInteropMiddleware;

use Mezzio\Tooling\MigrateInteropMiddleware\ConvertInteropMiddleware;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ConvertInteropMiddlewareTest extends TestCase
{
    use ProjectSetupTrait;

    public function testConvertsFilesAndEmitsInfoMessagesAsExpected(): void
    {
        $dir = vfsStream::setup('migrate');
        $this->setupSrcDir($dir);
        $path = vfsStream::url('migrate');

        $console = $this->setupConsoleHelper();

        $converter = new ConvertInteropMiddleware($console->reveal());
        $converter->process($path);

        self::assertExpected($path);
    }
}
