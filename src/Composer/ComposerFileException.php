<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use RuntimeException;

use function sprintf;

class ComposerFileException extends RuntimeException
{
    public static function dueToMissingFile(string $filename): self
    {
        return new self(sprintf(
            'Unable to open "%s"; file does not exist',
            $filename
        ));
    }

    public static function dueToPermissions(string $filename): self
    {
        return new self(sprintf(
            'Unable to open "%s"; lacking read permissions',
            $filename
        ));
    }

    public static function dueToMissingDirectory(string $directory): self
    {
        return new self(sprintf(
            'Unable to write composer.json; directory "%s" does not exist',
            $directory
        ));
    }

    public static function dueToDirectoryPermissions(string $directory): self
    {
        return new self(sprintf(
            'Unable to write composer.json; lacking permissions to write to directory "%s"',
            $directory
        ));
    }
}
