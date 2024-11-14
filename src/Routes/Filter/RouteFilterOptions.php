<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use function array_filter;
use function array_walk;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_string;
use function strtoupper;

final class RouteFilterOptions implements RouteFilterOptionsInterface
{
    private array $allowedFilterOptions = ['methods', 'middleware', 'name', 'path'];

    /** @param string|null|array<array-key,string> $methods */
    public function __construct(
        private string|null $middleware,
        private string|null $name,
        private string|null $path,
        private array|string|null $methods
    ) {
        if (is_string($methods)) {
            $this->methods = [strtoupper($methods)];
        }

        if (is_array($methods)) {
            array_walk($methods, fn(string &$value) => $value = strtoupper($value));
            $this->methods = $methods;
        }
    }

    private function getFilterOptionsMinusMethods(): array
    {
        return array_filter($this->allowedFilterOptions, fn($value) => $value !== "methods");
    }

    public function has(string $filterOption): bool
    {
        if (! in_array($filterOption, $this->allowedFilterOptions)) {
            return false;
        }

        if (in_array($filterOption, $this->getFilterOptionsMinusMethods())) {
            return $this->$filterOption !== null;
        }

        return $this->methods !== [] && $this->methods !== null;
    }

    public function getMiddleware(): string|null
    {
        return $this->middleware;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getPath(): string|null
    {
        return $this->path;
    }

    public function getMethods(): array|string|null
    {
        return $this->methods;
    }

    public function toArray(): array
    {
        $values = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (in_array($key, $this->getFilterOptionsMinusMethods()) && $value !== "") {
                $values[$key] = $value;
            }

            if ($key === "methods" && ! empty($value)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
