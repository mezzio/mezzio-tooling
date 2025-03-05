<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use function array_map;
use function strtoupper;

/** @internal */
final class RouteFilterOptions
{
    /** @var list<non-empty-string> */
    public readonly array $methods;

    /**
     * @param non-empty-string|null $middleware
     * @param non-empty-string|null $name
     * @param non-empty-string|null $path
     * @param list<non-empty-string> $methods
     */
    public function __construct(
        public readonly string|null $middleware,
        public readonly string|null $name,
        public readonly string|null $path,
        array $methods
    ) {
        /** Psalm does not like a first-class callable here */
        $this->methods = array_map(static function (string $method): string {
            return strtoupper($method);
        }, $methods);
    }
}
