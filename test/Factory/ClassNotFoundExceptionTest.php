<?php

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
