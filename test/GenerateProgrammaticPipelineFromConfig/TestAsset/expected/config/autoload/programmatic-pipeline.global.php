<?php

/**
 * Mezzio programmatic pipeline configuration
 */

use Mezzio\Container\ErrorHandlerFactory;
use Mezzio\Container\ErrorResponseGeneratorFactory;
use Mezzio\Container\NotFoundDelegateFactory;
use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Delegate\DefaultDelegate;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Middleware\ImplicitHeadMiddleware;
use Mezzio\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Middleware\NotFoundHandler;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Stratigility\Middleware\OriginalMessages;

return [
    'dependencies' => [
        'aliases' => [
            // Override the following to provide an alternate default delegate.
            DefaultDelegate::class => NotFoundDelegate::class,
        ],
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
            // Override the following to use an alternate "not found" delegate.
            NotFoundDelegate::class => NotFoundDelegateFactory::class,
            NotFoundHandler::class => NotFoundHandlerFactory::class,
        ],
    ],
    'mezzio' => [
        'programmatic_pipeline' => true,
        'raise_throwables'      => true,
    ],
];
