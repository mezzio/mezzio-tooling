# mezzio-tooling

[![Build Status](https://github.com/mezzio/mezzio-tooling/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/mezzio/mezzio-tooling/actions/workflows/continuous-integration.yml)

*Migration and development tools for Mezzio.*

## Installation

Install via composer:

```bash
$ composer require --dev mezzio/mezzio-tooling
```

## Command line tooling

This package exposes commands for [laminas-cli](https://docs.laminas.dev/laminas-cli), and may be invoked via `vendor/bin/laminas`.

- **mezzio:action:create**: Create an action class file; this is an alias for the `mezzio:handler:create` command, listed below.
- **mezzio:factory:create**: Create a factory class file for the named class.
  The class file is created in the same directory as the class specified.
- **mezzio:handler:create**: Create a PSR-15 request handler class file.
  Also generates a factory for the generated class, and, if a template renderer is registered with the application container, generates a template and modifies the class to render it into a laminas-diactoros `HtmlResponse`.
- **mezzio:middleware:create**: Create a PSR-15 middleware class file.
- **mezzio:middleware:migrate-from-interop**: Migrate interop middlewares and delegators to PSR-15 middlewares and request handlers.
- **mezzio:middleware:to-request-handler**: Migrate PSR-15 middleware to request handlers.
- **mezzio:module:create**: Create and register a middleware module with the application.
- **mezzio:module:deregister**: Deregister a middleware module from the application.
- **mezzio:module:register**: Register a middleware module with the application.

> ### Previous versions
>
> Versions of mezzio/mezzio-tooling prior to v2.0 exposed a `vendor/bin/mezzio` binary, and the various commands exposed all lacked the `mezzio:` prefix, with the following more specific changes:
>
> - `mezzio:middleware:migrate-from-interop` was previously `migrate:interop-middleware`
> - `mezzio:middleware:to-request-handler` was previously `migrate:middleware-to-request-handler`

## Configurable command option values

If the `--modules-path` of your project is not under `src`, you can either provide the path via the `--modules-path` command-line option, or configure it within your application configuration.
By adding the changed path to your application configuration, you can omit the need to use the `--modules-path` option during cli execution for the various `mezzio:module:*` commands.

```php
// In config/autoload/application.global.php:

<?php

declare(strict_types = 1);

use Mezzio\Tooling\Module\CommandCommonOptions;

return [
    /* ... */
    CommandCommonOptions::class => [
        '--modules-path' => 'custom-directory',
    ],
];
```
