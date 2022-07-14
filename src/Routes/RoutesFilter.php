<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use ArrayIterator;
use FilterIterator;
use Mezzio\Router\Route;

use function array_intersect;
use function array_walk;
use function get_class;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strtoupper;

/**
 * RoutesFilter filters a traversable list of Route objects based on any of the four Route criteria,
 * those being the route's name, path, middleware, or supported method(s).
 */
class RoutesFilter extends FilterIterator
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
    private array $options;

    public function __construct(ArrayIterator $routes, array $options = [])
    {
        parent::__construct($routes);

        $this->options = $options;
    }

    public function accept(): bool
    {
        /** @var Route $route */
        $route = $this->getInnerIterator()->current();

        if (! empty($this->options['name'])) {
            return $route->getName() === $this->options['name'] || $this->matchesByRegex($route, 'name');
        }

        if (! empty($this->options['path'])) {
            return $route->getPath() === $this->options['path'] || $this->matchesByRegex($route, 'path');
        }

        if (
            (! empty($this->options['method']) || $this->options['method'] === Route::HTTP_METHOD_ANY) &&
            $this->matchByMethod($route)
        ) {
            return true;
        }

        if (! empty($this->options['middleware'])) {
            return get_class($route->getMiddleware()) === (string) $this->options['middleware'];
        }

        return false;
    }

    /**
     * Match the route against a regular expression based on the field in $matchType.
     *
     * $matchType can be either "path" or "name".
     *
     * @return false|int
     */
    public function matchesByRegex(Route $route, string $routeAttribute)
    {
        if ($routeAttribute === 'path') {
            $path = (string) $this->options['path'];
            return preg_match(
                sprintf("/^%s/", str_replace('/', '\/', $path)),
                $route->getPath()
            );
        }

        if ($routeAttribute === 'name') {
            return preg_match(sprintf("/%s/", (string) $this->options['name']), $route->getName());
        }
    }

    /**
     * Match if the current route supports the method(s) supplied.
     */
    public function matchByMethod(Route $route): bool
    {
        if ($route->allowsAnyMethod()) {
            return true;
        }

        if (is_string($this->options['method'])) {
            return in_array(strtoupper($this->options['method']), $route->getAllowedMethods());
        }

        if (is_array($this->options['method'])) {
            array_walk($this->options['method'], fn(&$value) => $value = strtoupper($value));
            return ! empty(array_intersect(
                $this->options['method'],
                $route->getAllowedMethods()
            ));
        }

        return false;
    }
}
