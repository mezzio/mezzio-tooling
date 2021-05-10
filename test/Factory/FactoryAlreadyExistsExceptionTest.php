<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\FactoryAlreadyExistsException;
use PHPUnit\Framework\TestCase;

class FactoryAlreadyExistsExceptionTest extends TestCase
{
    public function testForClassUsingFileGeneratesExpectedException()
    {
        $e = FactoryAlreadyExistsException::forClassUsingFile(__CLASS__, __FILE__);
        self::assertInstanceOf(FactoryAlreadyExistsException::class, $e);
        self::assertStringContainsString(sprintf('class "%s"', __CLASS__), $e->getMessage());
        self::assertStringContainsString(sprintf('file "%s"', __FILE__), $e->getMessage());
    }
}
