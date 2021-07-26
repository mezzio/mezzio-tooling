<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use RuntimeException;

use function sprintf;

final class FactoryAlreadyExistsException extends RuntimeException
{
    public static function forClassUsingFile(string $className, string $fileName): self
    {
        return new self(sprintf(
            'Cannot create factory for class "%s"; factory file "%s" already exists!',
            $className,
            $fileName
        ));
    }
}
