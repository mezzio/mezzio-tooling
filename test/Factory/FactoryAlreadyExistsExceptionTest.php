<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\FactoryAlreadyExistsException;
use PHPUnit\Framework\TestCase;

use function sprintf;

class FactoryAlreadyExistsExceptionTest extends TestCase
{
    public function testForClassUsingFileGeneratesExpectedException(): void
    {
        $e = FactoryAlreadyExistsException::forClassUsingFile(self::class, __FILE__);
        self::assertInstanceOf(FactoryAlreadyExistsException::class, $e);
        self::assertStringContainsString(sprintf('class "%s"', self::class), $e->getMessage());
        self::assertStringContainsString(sprintf('file "%s"', __FILE__), $e->getMessage());
    }
}
