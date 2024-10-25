<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

final class EmptyRouteFilterOptions implements RouteFilterOptionsInterface
{
    public function has(string $filterOption): bool
    {
        return false;
    }

    public function getMiddleware(): string
    {
        return "";
    }

    public function getName(): string
    {
        return "";
    }

    public function getPath(): string
    {
        return "";
    }

    public function getMethods(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [];
    }
}
