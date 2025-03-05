<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use ArrayIterator;
use FilterIterator;
use Mezzio\Router\Route;
use Traversable;

use function array_intersect;
use function assert;
use function preg_match;
use function sprintf;
use function str_replace;
use function stripos;

/**
 * RoutesFilter filters a traversable list of Route objects based on any of the four Route criteria,
 * those being the route's name, path, middleware, or supported method(s).
 *
 * @internal
 *
 * @template-covariant TKey of int
 * @extends FilterIterator<int, Route, Traversable<TKey, Route>>
 */
final class RoutesFilter extends FilterIterator
{
    /**
     * @param ArrayIterator<TKey, Route> $routes
     */
    public function __construct(
        ArrayIterator $routes,
        private readonly RouteFilterOptions $options,
    ) {
        parent::__construct($routes);
    }

    public function accept(): bool
    {
        /** @var Route $route */
        $route = $this->getInnerIterator()->current();

        if ($this->options->name !== null) {
            return $route->getName() === $this->options->name
                || $this->matches($route->getName(), $this->options->name);
        }

        if ($this->options->path !== null) {
            return $route->getPath() === $this->options->path
                || $this->matches($route->getPath(), $this->options->path);
        }

        if ($this->options->middleware !== null) {
            return $this->matchesByMiddleware($route);
        }

        if ($this->options->methods !== []) {
            return $this->matchesByMethod($route);
        }

        return true;
    }

    /**
     * @param non-empty-string $subject
     * @param non-empty-string $search
     */
    private function matches(string $subject, string $search): bool
    {
        return (bool) preg_match(
            sprintf("/^%s/", str_replace('/', '\/', $search)),
            $subject,
        );
    }

    /**
     * Match if the current route supports the method(s) supplied.
     */
    private function matchesByMethod(Route $route): bool
    {
        if ($route->allowsAnyMethod()) {
            return true;
        }

        return array_intersect(
            $this->options->methods,
            $route->getAllowedMethods() ?? []
        ) !== [];
    }

    /**
     * This method checks if a route is handled by a given middleware class
     *
     * The function first checks if there is an exact match on the middleware
     * class' name, then a partial match to any part of the class' name, and
     * finally uses a regular expression to attempt a pattern match against
     * the class' name. The intent is to perform checks from the least to the
     * most computationally expensive, to avoid excessive overhead.
     */
    private function matchesByMiddleware(Route $route): bool
    {
        assert($this->options->middleware !== null);
        $middlewareClass = $route->getMiddleware()::class;

        return $middlewareClass === $this->options->middleware
            || stripos($middlewareClass, $this->options->middleware) !== false
            || (bool) preg_match(
                sprintf('/%s/', $this->escapeNamespaceSeparatorForRegex($this->options->middleware)),
                $middlewareClass
            );
    }

    /** @param non-empty-string $toMatch */
    private function escapeNamespaceSeparatorForRegex(string $toMatch): string
    {
        return str_replace('\\', '\\\\', $toMatch);
    }
}
