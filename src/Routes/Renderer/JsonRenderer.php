<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes\Renderer;

use Mezzio\Router\Route;

use function get_class;
use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * JsonRenderer outputs/renders a list of Route objects in JSON format.
 */
class JsonRenderer implements RendererInterface
{
    /**
     * @param $routes Route[]
     */
    public function render(array $routes): string
    {
        $output = [];

        foreach ($routes as $route) {
            $output[] = $this->convertRouteToArray($route);
        }

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    private function convertRouteToArray(Route $route): array
    {
        return [
            'method'     => $route->getAllowedMethods(),
            'middleware' => get_class($route->getMiddleware()),
            'name'       => $route->getName(),
            'path'       => $route->getPath(),
        ];
    }
}
