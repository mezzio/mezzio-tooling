<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use RuntimeException;

use function sprintf;

final class ConfigFileNotWritableException extends RuntimeException
{
    public static function forFile(string $file): self
    {
        return new self(sprintf(
            'Cannot write factory configuration to file "%s";'
            . ' please make sure the file and directory are writable',
            $file
        ));
    }
}
