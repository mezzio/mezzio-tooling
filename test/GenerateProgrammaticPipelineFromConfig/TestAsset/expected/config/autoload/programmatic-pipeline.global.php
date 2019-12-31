<?php

/**
 * Mezzio programmatic pipeline configuration
 */

use Mezzio\Container\ErrorHandlerFactory;
use Mezzio\Container\ErrorResponseGeneratorFactory;
use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Middleware\ImplicitHeadMiddleware;
use Mezzio\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Middleware\NotFoundHandler;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Stratigility\Middleware\OriginalMessages;

return [
    'dependencies' => [
        'invokables' => [
            ImplicitHeadMiddleware::class => ImplicitHeadMiddleware::class,
            ImplicitOptionsMiddleware::class => ImplicitOptionsMiddleware::class,
            OriginalMessages::class => OriginalMessages::class,
        ],
        'factories' => [
            ErrorHandler::class => ErrorHandlerFactory::class,
            // Override the following in a local config file to use
            // Mezzio\Container\WhoopsErrorResponseGeneratorFactory
            // in order to use Whoops for development error handling.
            ErrorResponseGenerator::class => ErrorResponseGeneratorFactory::class,
            NotFoundHandler::class => NotFoundHandlerFactory::class,
        ],
    ],
    'mezzio' => [
        'programmatic_pipeline' => true,
        'raise_throwables'      => true,
    ],
];
