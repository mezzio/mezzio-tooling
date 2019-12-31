<?php

/**
 * Mezzio middleware pipeline
 */

/** @var \Mezzio\Application $app */
$app->pipe(\Laminas\Stratigility\Middleware\OriginalMessages::class);
$app->pipe(\Laminas\Stratigility\Middleware\ErrorHandler::class);
$app->pipe('Mezzio\\Helper\\ServerUrlMiddleware');
$app->pipe('App\\Middleware\\XClacksOverhead');
$app->pipe('/api', [
    'Api\\Middleware\\Authentication',
    'Api\\Middleware\\Authorization',
    'Api\\Middleware\\Negotiation',
    'Api\\Middleware\\Validation',
]);
$app->pipeRoutingMiddleware();
$app->pipe('Mezzio\\Helper\\UrlHelperMiddleware');
$app->pipeDispatchMiddleware();
$app->pipe('App\\Middleware\\NotFoundHandler');
$app->pipeErrorHandler('App\\Middleware\\ErrorMiddleware');
$app->pipe(\Mezzio\Middleware\NotFoundHandler::class);
