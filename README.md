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

  - **middleware:create**: Create a PSR-15 middleware class file.
  - **migrate:interop-middleware**: Migrate interop middlewares and delegators
    to PSR-15 middlewares and request handlers.
  - **module:create**: Create and register a middleware module with the
    application.
  - **module:deregister**: Deregister a middleware module from the application.
  - **module:register**: Register a middleware module with the application.
