<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

use RuntimeException;

class FactoryAlreadyExistsException extends RuntimeException
{
    public static function forClassUsingFile(string $className, string $fileName) : self
    {
        return new self(sprintf(
            'Cannot create factory for class "%s"; factory file "%s" already exists!',
            $className,
            $fileName
        ));
    }
}
