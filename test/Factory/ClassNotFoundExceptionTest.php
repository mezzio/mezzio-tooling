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
        self::assertInstanceOf(ClassNotFoundException::class, $e);
        self::assertStringContainsString(sprintf('Class "%s"', __CLASS__), $e->getMessage());
    }
}
