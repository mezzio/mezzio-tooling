<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use RuntimeException;

use function sprintf;

final class UnidentifiedTypeException extends RuntimeException
{
    public static function forArgument(string $argument): self
    {
        return new self(sprintf(
            'Cannot identify type for constructor argument "%s"; no type hint, or non-class/interface type hint',
            $argument
        ));
    }
}
