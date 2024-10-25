<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use function array_walk;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_string;
use function strtoupper;

final class RouteFilterOptions implements RouteFilterOptionsInterface
{
    /** @var array<array-key,string> */
    private array $methods = [];

    /** @param string|null|array<array-key,string> $methods */
    public function __construct(
        private string $middleware = "",
        private string $name = "",
        private string $path = "",
        $methods = ""
    ) {
        if (is_string($methods)) {
            $this->methods = [strtoupper($methods)];
        }

        if (is_array($methods)) {
            array_walk($methods, fn(string &$value) => $value = strtoupper($value));
            $this->methods = $methods;
        }
    }

    public function has(string $filterOption): bool
    {
        if (! in_array($filterOption, ['methods', 'middleware', 'name', 'path'])) {
            return false;
        }

        if (in_array($filterOption, ['middleware', 'name', 'path'])) {
            return $this->$filterOption !== "";
        }

        return $this->methods !== [];
    }

    public function getMiddleware(): string
    {
        return $this->middleware;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function toArray(): array
    {
        $values = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (is_string($value) && $value !== "") {
                $values[$key] = $value;
            }
            if (is_array($value) && ! empty($value)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
