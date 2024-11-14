<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

interface RouteFilterOptionsInterface
{
    /**
     * Determines if the specified filter ($filterOption) has been set
     */
    public function has(string $filterOption): bool;

    /**
     * Retrieves a route middleware filter the available routing data by
     */
    public function getMiddleware(): string|null;

    /**
     * Retrieves a route name filter the available routing data by
     */
    public function getName(): string|null;

    /**
     * Retrieves a route path to filter the available routing data by
     */
    public function getPath(): string|null;

    /**
     * Returns any route methods to filter the available routing data by
     */
    public function getMethods(): array|string|null;

    public function toArray(): array;
}
