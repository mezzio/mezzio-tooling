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
        $this->assertInstanceOf(FactoryAlreadyExistsException::class, $e);
        $this->assertStringContainsString(sprintf('class "%s"', __CLASS__), $e->getMessage());
        $this->assertStringContainsString(sprintf('file "%s"', __FILE__), $e->getMessage());
    }
}
