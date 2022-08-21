<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\Routes\Renderer;

use Mezzio\Router\Route;
use Mezzio\Tooling\Routes\Renderer\JsonRenderer;
use MezzioTest\Tooling\Routes\Middleware\ExpressMiddleware;
use MezzioTest\Tooling\Routes\Middleware\SimpleMiddleware;
use PHPUnit\Framework\TestCase;

use function str_replace;

class JsonRendererTest extends TestCase
{
    /** @var Route[] */
    private array $routes;

    public function setUp(): void
    {
        $this->routes = [
            new Route(
                "/user/profile",
                new SimpleMiddleware(),
                ['GET'],
                'user.profile'
            ),
            new Route(
                "/",
                new ExpressMiddleware(),
                ['GET'],
                'home'
            ),
            new Route(
                "/login",
                new ExpressMiddleware(),
                ['GET'],
                'user.login'
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                ['GET'],
                'user.logout'
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                ['GET', 'POST'],
                'user.logout'
            ),
            new Route(
                "/logout",
                new ExpressMiddleware(),
                Route::HTTP_METHOD_ANY,
                'user.logout'
            ),
        ];
    }

    public function testCanRenderOutputInJsonFormat()
    {
        $outputFormatter = new JsonRenderer();
        $expectedOutput  = <<<EOF
[
    {
        "method": ["GET"],
        "middleware": "MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\SimpleMiddleware",
        "name": "user.profile",
        "path": "/user/profile"
    },
    {
        "method": ["GET"],
        "middleware": "MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware",
        "name": "home",
        "path": "/"
    },
    {
        "method": ["GET"],
        "middleware": "MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware",
        "name": "user.login",
        "path": "/login"
    },
    {
        "method": ["GET"],
        "middleware": "MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware",
        "name": "user.logout",
        "path": "/logout"
    },
    {
        "method": ["GET", "POST"],
        "middleware": "MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware",
        "name": "user.logout",
        "path": "/logout"
    },
    {
        "method": null,
        "middleware": "MezzioTest\\\\Tooling\\\\Routes\\\\Middleware\\\\ExpressMiddleware",
        "name": "user.logout",
        "path": "/logout"
    }
]
EOF;

        $this->assertSame(
            str_replace([' ', "\n"], '', $expectedOutput),
            $outputFormatter->render($this->routes)
        );
    }
}
