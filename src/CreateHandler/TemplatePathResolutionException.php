<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use RuntimeException;

class TemplatePathResolutionException extends RuntimeException
{
    public static function forNamespace(string $namespace) : self
    {
        return new self(sprintf(
            'Template path configuration for the namespace "%s" either'
            . ' had no entries or more than one entry; could not determine'
            . ' where to create new template.',
            $namespace
        ));
    }
}
