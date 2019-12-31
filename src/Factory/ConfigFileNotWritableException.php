<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use RuntimeException;

class ConfigFileNotWritableException extends RuntimeException
{
    public static function forFile(string $file) : self
    {
        return new self(sprintf(
            'Cannot write factory configuration to file "%s";'
            . ' please make sure the file and directory are writable',
            $file
        ));
    }
}
