<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

interface RouteFilterOptionsInterface
{
    public function has(string $filterOption): bool;

    public function getMiddleware(): string;

    public function getName(): string;

    public function getPath(): string;

    /**
     * @return array<array-key,string>
     */
    public function getMethods(): array;

    public function toArray(): array;
}
