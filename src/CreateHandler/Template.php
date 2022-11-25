<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

/**
 * Value object representing details of a generated template.
 */
final class Template
{
    public function __construct(private string $path, private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
