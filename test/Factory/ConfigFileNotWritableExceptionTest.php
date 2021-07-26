<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ConfigFileNotWritableException;
use PHPUnit\Framework\TestCase;

use function sprintf;

class ConfigFileNotWritableExceptionTest extends TestCase
{
    public function testForFileGeneratesExpectedException()
    {
        $e = ConfigFileNotWritableException::forFile(__FILE__);
        self::assertInstanceOf(ConfigFileNotWritableException::class, $e);
        self::assertStringContainsString(sprintf('file "%s"', __FILE__), $e->getMessage());
    }
}
