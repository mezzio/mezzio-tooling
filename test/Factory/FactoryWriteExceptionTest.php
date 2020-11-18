<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\Factory;

use Mezzio\Tooling\Factory\FactoryWriteException;
use PHPUnit\Framework\TestCase;

class FactoryWriteExceptionTest extends TestCase
{
    public function testWhenCreatingFileGeneratesExpectedException()
    {
        $e = FactoryWriteException::whenCreatingFile(__FILE__);
        self::assertInstanceOf(FactoryWriteException::class, $e);
        self::assertStringContainsString('file "' . __FILE__ . '"', $e->getMessage());
    }
}
