<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

$originalPath = $request->getAttribute('originalRequest', $request)->getUri()->getPath();

$middleware = function ($req, $res, $next) {
    $original = $req->getAttribute('originalRequest', $req);
    return $res;
};

$originalScheme = $request->getAttribute('originalUri', $request->getUri())->getScheme();

$middleware = function ($req, $res, $next) {
    $originalUri = $req->getAttribute('originalUri', $req->getUri());
    $originalRequest = $req->getAttribute('originalRequest', $req);
    $response = $res->getOriginalResponse();
    return $response;
};
