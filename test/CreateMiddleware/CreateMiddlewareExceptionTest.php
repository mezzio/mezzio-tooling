<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateMiddleware;

use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareException;
use PHPUnit\Framework\TestCase;

class CreateMiddlewareExceptionTest extends TestCase
{
    public function testMissingComposerJsonReturnsInstance(): void
    {
        $e = CreateMiddlewareException::missingComposerJson();
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('Could not find a composer.json', $e->getMessage());
    }

    public function testMissingComposerAutoloadersReturnsInstance(): void
    {
        $e = CreateMiddlewareException::missingComposerAutoloaders();
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('PSR-4 autoloaders', $e->getMessage());
    }

    public function testInvalidComposerJsonReturnsInstanceWithErrorMessage(): void
    {
        $error = 'Invalid or malformed JSON';
        $e     = CreateMiddlewareException::invalidComposerJson($error);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('Unable to parse composer.json: ', $e->getMessage());
        self::assertStringContainsString($error, $e->getMessage());
    }

    public function testAutoloaderNotFoundReturnsInstanceUsingClassNameProvided(): void
    {
        $expected = self::class;
        $e        = CreateMiddlewareException::autoloaderNotFound($expected);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('match ' . $expected, $e->getMessage());
    }

    public function testUnableToCreatePathReturnsInstanceUsingPathAndClassProvided(): void
    {
        $path  = __FILE__;
        $class = self::class;
        $e     = CreateMiddlewareException::unableToCreatePath($path, $class);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('directory ' . $path, $e->getMessage());
        self::assertStringContainsString('class ' . $class, $e->getMessage());
    }

    public function testClassExistsReturnsInstanceUsingPathAndClassProvided(): void
    {
        $path  = __FILE__;
        $class = self::class;
        $e     = CreateMiddlewareException::classExists($path, $class);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('directory ' . $path, $e->getMessage());
        self::assertStringContainsString('Class ' . $class, $e->getMessage());
    }
}
