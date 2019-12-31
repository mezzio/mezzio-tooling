<?php

/**
 * Mezzio routed middleware
 */

/** @var \Mezzio\Application $app */
$app->get('/', 'App\\Action\\HomePageAction', 'home');
$app->route('/api/posts', 'App\\Action\\PostsAction', ['GET', 'POST'], 'api.posts')
    ->setOptions([
        'sort' => 'updated',
        'order' => 'desc',
    ]);
$app->route('/rest/post', [
    'Api\\Middleware\\Authentication',
    'Api\\Middleware\\Authorization',
    'Api\\Middleware\\Negotiation',
    'Api\\Middleware\\Validation',
    'Api\\Action\\PostAction',
], \Mezzio\Router\Route::HTTP_METHOD_ANY, 'api.rest.post')
    ->setOptions([
        'sort' => 'updated',
        'order' => 'desc',
    ]);
