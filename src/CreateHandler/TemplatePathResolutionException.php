<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use RuntimeException;

use function sprintf;

final class TemplatePathResolutionException extends RuntimeException
{
    public static function forNamespace(string $namespace): self
    {
        return new self(sprintf(
            'Template path configuration for the namespace "%s" either'
            . ' had no entries or more than one entry; could not determine'
            . ' where to create new template.',
            $namespace
        ));
    }
}
