<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateMiddlewareToRequestHandler\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class MultipleInterfacesMiddleware implements
    MyInterface,
    RequestHandlerInterface,
    SomeInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface {
        return new JsonResponse(['status' => 1]);
    }
}
