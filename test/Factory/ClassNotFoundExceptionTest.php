<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ClassNotFoundException;
use PHPUnit\Framework\TestCase;

class ClassNotFoundExceptionTest extends TestCase
{
    public function testForClassNameGeneratesExpectedException()
    {
        $e = ClassNotFoundException::forClassName(__CLASS__);
        $this->assertInstanceOf(ClassNotFoundException::class, $e);
        $this->assertStringContainsString(sprintf('Class "%s"', __CLASS__), $e->getMessage());
    }
}
