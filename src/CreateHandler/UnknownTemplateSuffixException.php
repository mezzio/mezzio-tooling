<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use RuntimeException;

class UnknownTemplateSuffixException extends RuntimeException
{
    public static function forRendererType(string $type) : self
    {
        return new self(sprintf(
            'Could not determine template file extension for renderer of type %s;'
            . ' please set the templates.extension configuration option, or pass'
            . ' the extension to use via the --with-template-extension option.',
            $type
        ));
    }
}
