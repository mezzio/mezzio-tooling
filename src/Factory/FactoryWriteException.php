<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use RuntimeException;

class FactoryWriteException extends RuntimeException
{
    public static function whenCreatingFile(string $filename) : self
    {
        return new self(sprintf(
            'Unable to create factory file "%s"; please verify you have write permissions to that directory',
            $filename
        ));
    }
}
