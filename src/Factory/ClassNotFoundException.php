<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use InvalidArgumentException;

use function sprintf;

final class ClassNotFoundException extends InvalidArgumentException
{
    public static function forClassName(string $className): self
    {
        return new self(sprintf(
            'Class "%s" could not be autoloaded; did you perhaps mis-type it?',
            $className
        ));
    }
}
