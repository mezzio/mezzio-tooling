<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use RuntimeException;

use function sprintf;

final class UnknownTemplateSuffixException extends RuntimeException
{
    public static function forRendererType(string $type): self
    {
        return new self(sprintf(
            'Could not determine template file extension for renderer of type %s;'
            . ' please set the templates.extension configuration option, or pass'
            . ' the extension to use via the --with-template-extension option.',
            $type
        ));
    }
}
