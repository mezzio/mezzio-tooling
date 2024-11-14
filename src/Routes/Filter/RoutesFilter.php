<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Filter;

use ArrayIterator;
use Exception;
use FilterIterator;
use Iterator;
use Mezzio\Router\Route;

use function array_intersect;
use function get_class;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function stripos;

/**
 * RoutesFilter filters a traversable list of Route objects based on any of the four Route criteria,
 * those being the route's name, path, middleware, or supported method(s).

 * @template TKey
 * @template-covariant TValue
 * @template TIterator as Iterator<TKey, TValue>
 * @template-extends FilterIterator<TKey, TValue, TIterator>
 */
final class RoutesFilter extends FilterIterator
{
    /**
     * @param ArrayIterator<int, Route> $routes
     * @param RouteFilterOptionsInterface $filterOptions An array storing the list of route options to
     *                                    filter a route on along with their respective values.
     *                                    The four allowed route options are: name, path, method,
     *                                    and middleware.  Name and path can be a fixed string,
     *                                    such as user.profile, or a regular expression, such as
     *                                    user.*. Middleware can only contain a class name.
     *                                    Method can be either a string which contains one of
     *                                    the allowed HTTP methods, or an array of HTTP methods.
     */
    public function __construct(ArrayIterator $routes, private RouteFilterOptionsInterface $filterOptions)
    {
        parent::__construct($routes);

        $this->filterOptions = $filterOptions;
    }

    public function getFilterOptions(): RouteFilterOptionsInterface
    {
        return $this->filterOptions;
    }

    public function accept(): bool
    {
        /** @var Route $route */
        $route = $this->getInnerIterator()->current();

        if ($this->filterOptions->has("name")) {
            return $route->getName() === $this->filterOptions->getName()
                || $this->matchesByRegex($route, 'name');
        }

        if ($this->filterOptions->has("path")) {
            return $route->getPath() === $this->filterOptions->getPath()
                || $this->matchesByRegex($route, 'path');
        }

        if ($this->filterOptions->has("middleware")) {
            return $this->matchesByMiddleware($route);
        }

        if ($this->filterOptions->has("methods")) {
            return $this->matchesByMethod($route);
        }

        return true;
    }

    /**
     * Match the route against a regular expression based on the field in $matchType.
     *
     * @param 'name'|'path' $routeAttribute
     */
    private function matchesByRegex(Route $route, string $routeAttribute): bool
    {
        if (! in_array($routeAttribute, ["name", "path"])) {
            return false;
        }

        if ($routeAttribute === 'path') {
            $path = $this->filterOptions->getPath();
            return (bool) preg_match(
                sprintf("/^%s/", str_replace('/', '\/', $path)),
                $route->getPath()
            );
        }

        return (bool) preg_match(
            sprintf("/%s/", $this->filterOptions->getName()),
            $route->getName()
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

        $methods = $this->filterOptions->getMethods() ?? [];
        return array_intersect(
            is_string($methods) ? [$methods] : $methods,
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
        $middlewareClass   = get_class($route->getMiddleware());
        $matchesMiddleware = $this->filterOptions->getMiddleware() ?? "";

        try {
            return $middlewareClass === $matchesMiddleware
                || (bool) stripos($middlewareClass, $matchesMiddleware)
                || (bool) preg_match(
                    sprintf('/%s/', $matchesMiddleware),
                    $middlewareClass
                );
        } catch (Exception $e) {
            return false;
        }
    }
}
