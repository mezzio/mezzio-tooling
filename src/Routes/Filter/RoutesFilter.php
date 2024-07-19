<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use ArrayIterator;
use Exception;
use FilterIterator;
use Mezzio\Router\Route;

use function array_filter;
use function array_intersect;
use function array_walk;
use function get_class;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function stripos;
use function strtoupper;

/**
 * RoutesFilter filters a traversable list of Route objects based on any of the four Route criteria,
 * those being the route's name, path, middleware, or supported method(s).
 *
 * @template-extends FilterIterator<array-key,Route>
 */
final class RoutesFilter extends FilterIterator
{
    /**
     * An array storing the list of route options to filter a route on along with their respective values.
     *
     * The four allowed route options are: name, path, method, and middleware.
     * Name and path can be a fixed string, such as user.profile, or a regular expression, such as user.*.
     * Middleware can only contain a class name. Method can be either a string which contains one of the
     * allowed HTTP methods, or an array of HTTP methods.
     *
     * @var array
     */
    private array $filterOptions;

    /**
     * @param ArrayIterator<array-key, Route> $routes
     */
    public function __construct(ArrayIterator $routes, array $filterOptions = [])
    {
        parent::__construct($routes);

        $this->filterOptions = $filterOptions;

        // Filter out any options that are, effectively, "empty".
        $this->filterOptions = array_filter(
            $this->filterOptions,
            function ($value) {
                return ! empty($value);
            }
        );
    }

    public function getFilterOptions(): array
    {
        return $this->filterOptions;
    }

    public function accept(): bool
    {
        /** @var Route $route */
        $route = $this->getInnerIterator()->current();

        if (empty($this->filterOptions)) {
            return true;
        }

        if (! empty($this->filterOptions['name'])) {
            return $route->getName() === $this->filterOptions['name']
                || $this->matchesByRegex($route, 'name');
        }

        if (! empty($this->filterOptions['path'])) {
            return $route->getPath() === $this->filterOptions['path']
                || $this->matchesByRegex($route, 'path');
        }

        if (! empty($this->filterOptions['method'])) {
            return $this->matchesByMethod($route);
        }

        if (! empty($this->filterOptions['middleware'])) {
            return $this->matchesByMiddleware($route);
        }

        return false;
    }

    /**
     * Match the route against a regular expression based on the field in $matchType.
     *
     * $matchType can be either "path" or "name".
     *
     * @return false|int|null
     */
    public function matchesByRegex(Route $route, string $routeAttribute)
    {
        if ($routeAttribute === 'path') {
            $path = (string) $this->filterOptions['path'];
            return preg_match(
                sprintf("/^%s/", str_replace('/', '\/', $path)),
                $route->getPath()
            );
        }

        if ($routeAttribute === 'name') {
            return preg_match(
                sprintf(
                    "/%s/",
                    (string) $this->filterOptions['name']
                ),
                $route->getName()
            );
        }
    }

    /**
     * Match if the current route supports the method(s) supplied.
     */
    public function matchesByMethod(Route $route): bool
    {
        if ($route->allowsAnyMethod()) {
            return true;
        }

        if ($this->filterOptions['method'] === Route::HTTP_METHOD_ANY) {
            return true;
        }

        if (is_string($this->filterOptions['method'])) {
            return in_array(
                strtoupper($this->filterOptions['method']),
                $route->getAllowedMethods() ?? []
            );
        }

        if (is_array($this->filterOptions['method'])) {
            array_walk(
                $this->filterOptions['method'],
                fn (string &$value) => $value = strtoupper($value)
            );
            return ! empty(array_intersect(
                $this->filterOptions['method'],
                $route->getAllowedMethods() ?? []
            ));
        }

        return false;
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
    public function matchesByMiddleware(Route $route): bool
    {
        $middlewareClass   = get_class($route->getMiddleware());
        $matchesMiddleware = (string) $this->filterOptions['middleware'];

        try {
            return $middlewareClass === $matchesMiddleware
                || stripos($middlewareClass, $matchesMiddleware)
                || preg_match(
                    sprintf('/%s/', $matchesMiddleware),
                    $middlewareClass
                );
        } catch (Exception $e) {
            return false;
        }
    }
}
