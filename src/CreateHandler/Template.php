<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

/**
 * Value object representing details of a generated template.
 */
final class Template
{
    private string $name;

    private string $path;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
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
