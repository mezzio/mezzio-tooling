<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateInteropMiddleware\TestAsset;

use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class MultilineMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        DelegateInterface $delegate
    ) : \Psr\Http\Message\ResponseInterface {
        return $delegate->handle($request);
    }
}
