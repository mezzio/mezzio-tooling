<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

/**
 * Value object representing details of a generated template.
 */
final class Template
{
    public function __construct(private readonly string $path, private readonly string $name)
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
