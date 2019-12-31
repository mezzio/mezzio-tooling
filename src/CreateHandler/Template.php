<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

/**
 * Value object representing details of a generated template.
 */
class Template
{
    /** @var string */
    private $name;

    /** @var string */
    private $path;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPath() : string
    {
        return $this->path;
    }
}
