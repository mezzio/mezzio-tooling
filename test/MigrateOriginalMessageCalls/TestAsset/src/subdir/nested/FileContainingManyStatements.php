<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

$originalPath = $request->getOriginalRequest()->getUri()->getPath();

$middleware = function ($req, $res, $next) {
    $original = $req->getOriginalRequest();
    return $res;
};

$originalScheme = $request->getOriginalUri()->getScheme();

$middleware = function ($req, $res, $next) {
    $originalUri = $req->getOriginalUri();
    $originalRequest = $req->getOriginalRequest();
    $response = $res->getOriginalResponse();
    return $response;
};
