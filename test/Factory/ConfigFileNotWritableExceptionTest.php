<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ConfigFileNotWritableException;
use PHPUnit\Framework\TestCase;

class ConfigFileNotWritableExceptionTest extends TestCase
{
    public function testForFileGeneratesExpectedException()
    {
        $e = ConfigFileNotWritableException::forFile(__FILE__);
        $this->assertInstanceOf(ConfigFileNotWritableException::class, $e);
        $this->assertStringContainsString(sprintf('file "%s"', __FILE__), $e->getMessage());
    }
}
