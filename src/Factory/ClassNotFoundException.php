<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use InvalidArgumentException;

class ClassNotFoundException extends InvalidArgumentException
{
    public static function forClassName(string $className) : self
    {
        return new self(sprintf(
            'Class "%s" could not be autoloaded; did you perhaps mis-type it?',
            $className
        ));
    }
}
