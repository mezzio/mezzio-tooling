# mezzio-tooling

[![Build Status](https://travis-ci.org/mezzio/mezzio-tooling.svg?branch=master)](https://travis-ci.org/mezzio/mezzio-tooling)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-tooling/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-tooling?branch=master)

*Migration and development tools for Mezzio.*

## Installation

Install via composer:

```bash
$ composer require --dev mezzio/mezzio-tooling
```

## `mezzio` Tool

- `vendor/bin/mezzio`: Entry point for all tooling. Currently exposes the
  following:

  - **middleware:create**: Create an http-interop middleware class file.
  - **migrate:error-middleware-scanner**: Scan for legacy error middleware or
    error middleware invocation.
  - **migrate:original-messages**: Migrate getOriginal*() calls to request
    attributes.
  - **migrate:pipeline**: Generate a programmatic pipeline and routes from
    configuration.
  - **module:create**: Create and register a middleware module with the
    application.
  - **module:deregister**: Deregister a middleware module from the application.
  - **module:register**: Register a middleware module with the application.

## Legacy Tooling

Prior to the 0.4.0 release, the following tools were exposed. They are still
present in 0.4.0, but will be removed at some point in the future, likely 1.0.0:

- `vendor/bin/mezzio-migrate-original-messages`: Ensure your application
  does not use the Stratigility-specific PSR-7 message decorators.

- `vendor/bin/mezzio-module`: Create the source tree for an Mezzio
  module, de/register the module in application configuration, and
  enable/disable autoloading of the module via composer.

- `vendor/bin/mezzio-pipeline-from-config`: Update a pre-2.0 Mezzio
  application to use programmatic pipelines instead.

- `vendor/bin/mezzio-scan-for-error-middleware`: Scan for Stratigility
  `ErrorMiddlewareInterface` implementations (both direct and duck-typed), as
  well as invocations of error middleware (via the optional third argument to
  `$next`).

Each will provide usage details when invoked without arguments, or with the
`help`, `--help`, or `-h` flags.
