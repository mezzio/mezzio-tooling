<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use function get_object_vars;
use function in_array;
use function is_array;
use function is_string;

final class RouteFilterOptions
{
    private string $middleware;
    private string $name;
    private string $path;

    /** @var array<array-key,string> */
    private array $methods = [];

    /**
     * @param string|array<array-key,string> $methods
     */
    public function __construct(
        string $middleware = '',
        string $name = '',
        string $path = '',
        $methods = []
    ) {
        if (is_string($methods)) {
            $this->methods = [$methods];
        }
        if (is_array($methods)) {
            $this->methods = $methods;
        }
        $this->middleware = $middleware;
        $this->name       = $name;
        $this->path       = $path;
    }

    public function has(string $filterOption): bool
    {
        if (in_array($filterOption, ['middleware', 'name', 'path'])) {
            return $this->$filterOption !== null;
        }

        if ($filterOption === 'methods') {
            return [] !== $this->methods;
        }

        return false;
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

    /**
     * @return array<array-key,string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function toArray(): array
    {
        $values = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (! empty($value)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
