<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\ClassNotFoundException;
use PHPUnit\Framework\TestCase;

use function sprintf;

class ClassNotFoundExceptionTest extends TestCase
{
    public function testForClassNameGeneratesExpectedException(): void
    {
        $e = ClassNotFoundException::forClassName(self::class);
        self::assertInstanceOf(ClassNotFoundException::class, $e);
        self::assertStringContainsString(sprintf('Class "%s"', self::class), $e->getMessage());
    }
}
