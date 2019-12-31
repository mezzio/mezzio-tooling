<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateInteropMiddleware\TestAsset;

use Interop\Http\ServerMiddleware\DelegateInterface as Handler;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;

class InteropAliasMiddleware implements ServerMiddleware
{
    public function process(ServerRequestInterface $request, Handler $handler)
    {
        // do something
    }
}
