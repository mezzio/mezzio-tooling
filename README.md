# mezzio-tooling

[![Build Status](https://github.com/mezzio/mezzio-tooling/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/mezzio/mezzio-tooling/actions/workflows/continuous-integration.yml)

> ## 🇷🇺 Русским гражданам
> 
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
> 
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
> 
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
> 
> ## 🇺🇸 To Citizens of Russia
> 
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
> 
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
> 
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

*Migration and development tools for Mezzio.*

## Installation

Install via composer:

```bash
$ composer require --dev mezzio/mezzio-tooling
```

## Command line tooling

This package exposes commands for [laminas-cli](https://docs.laminas.dev/laminas-cli), and may be invoked via `vendor/bin/laminas`.

- `mezzio:action:create`: Create an action class file; this is an alias for the `mezzio:handler:create` command, listed below.
- `mezzio:factory:create`: Create a factory class file for the named class.
  The class file is created in the same directory as the class specified.
- `mezzio:handler:create`: Create a PSR-15 request handler class file.
  Also generates a factory for the generated class, and, if a template renderer is registered with the application container, generates a template and modifies the class to render it into a laminas-diactoros `HtmlResponse`.
- **mezzio:middleware:create**: Create a PSR-15 middleware class file.
- **mezzio:middleware:migrate-from-interop**: Migrate interop middlewares and delegators to PSR-15 middlewares and request handlers.
- **mezzio:middleware:to-request-handler**: Migrate PSR-15 middleware to request handlers.
- **mezzio:module:create**: Create and register a middleware module with the application.
- **mezzio:module:deregister**: Deregister a middleware module from the application.
- **mezzio:module:register**: Register a middleware module with the application.
- **mezzio:routes:list**: List the application's routing table. 

### Routes

#### mezzio:routes:list

This command lists the application's routing table.
For each route, it prints its name, path, middleware, and any additional options, in a tabular format to the terminal.
The routes are listed in no particular order, by default.

The command supports several options, listed in the table below.

| Command Long        | Command Short | Description                                                                                                                                                                                                                                                                                                                      |
|---------------------|---------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `--format`          | `-f`          | Set the format of the command's output. The supported values are `table`, which is the default, and `json`. If you set the format to json, then we recommend using [jq](https://stedolan.github.io/jq/manual/) to query/filter the command's output.                                                                             |
| `--sort`            | `-s`          | Sort the command's output. The supported values are `name` and `path`.                                                                                                                                                                                                                                                           |
| `--supports-method` | `-m`          | Accepts a comma-separated list of one or more HTTP methods, and filters out routes which don't support those methods.                                                                                                                                                                                                            |
| `--has-path`        | `-p`          | Accepts a comma-separated list of one or more paths, and filters out routes with paths that don't match. The paths can be a regular expression, supported by the preg_* functions. For example, "/,/api/ping,*/ping".                                                                                                            |
| `--has-name`        | `-n`          | Accepts a comma-separated list of one or more names, and filters out routes with names that don't match. The names can be fixed strings, or regular expressions supported by the preg_* functions. For example, "user,user.register,*.register,user*".                                                                           |
| `--has-middleware`  | `-w`          | Accepts a comma-separated list of one or more middleware classes, and filters out routes that do not require those classes. The classes can be fully-qualified, unqualified, or a regular expression, supported by the preg_* functions. For example, "\Mezzio\Middleware\LazyLoadingMiddleware,LazyLoadingMiddleware,\Mezzio*". |

##### Usage Example

Here is an example of what you can expect from running the command.

```bash
$ mezzio:routes:list

| Name | Path | Middleware | Options |
|---|---|---|---|
| home | / | App/Handler/HomePageHandler | none |
```

Here is an example of what you can expect from running the command and setting the format to `json`.

```json
{
  "routes": [
    {
      "name": "home",
      "path": "/",
      "middleware": "\\App\\Handler\\HomePageHandler",
      "options": ""
    }
  ]
}
```

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
