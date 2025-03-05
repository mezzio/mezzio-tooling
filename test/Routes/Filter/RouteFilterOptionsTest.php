<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes\Filter;

use Mezzio\Tooling\Routes\Filter\RouteFilterOptions;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress InternalClass, InternalProperty, InternalMethod */
class RouteFilterOptionsTest extends TestCase
{
    public function testThatMethodsAreConvertedToUppercase(): void
    {
        $options = new RouteFilterOptions(
            'foo',
            'bar',
            'baz',
            ['bing', 'bong'],
        );

        self::assertSame(['BING', 'BONG'], $options->methods);
    }
}
