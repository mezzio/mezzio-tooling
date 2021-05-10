<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateInteropMiddleware\TestAsset;

use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;

class MyInteropDelegate implements DelegateInterface
{
    public function handle(ServerRequestInterface $request) : \Psr\Http\Message\ResponseInterface
    {
    }
}
