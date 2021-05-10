<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateMiddleware;

use Mezzio\Tooling\CreateMiddleware\CreateMiddlewareException;
use PHPUnit\Framework\TestCase;

class CreateMiddlewareExceptionTest extends TestCase
{
    public function testMissingComposerJsonReturnsInstance()
    {
        $e = CreateMiddlewareException::missingComposerJson();
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('Could not find a composer.json', $e->getMessage());
    }

    public function testMissingComposerAutoloadersReturnsInstance()
    {
        $e = CreateMiddlewareException::missingComposerAutoloaders();
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('PSR-4 autoloaders', $e->getMessage());
    }

    public function testInvalidComposerJsonReturnsInstanceWithErrorMessage()
    {
        $error = 'Invalid or malformed JSON';
        $e = CreateMiddlewareException::invalidComposerJson($error);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('Unable to parse composer.json: ', $e->getMessage());
        self::assertStringContainsString($error, $e->getMessage());
    }

    public function testAutoloaderNotFoundReturnsInstanceUsingClassNameProvided()
    {
        $expected = __CLASS__;
        $e = CreateMiddlewareException::autoloaderNotFound($expected);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('match ' . $expected, $e->getMessage());
    }

    public function testUnableToCreatePathReturnsInstanceUsingPathAndClassProvided()
    {
        $path = __FILE__;
        $class = __CLASS__;
        $e = CreateMiddlewareException::unableToCreatePath($path, $class);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('directory ' . $path, $e->getMessage());
        self::assertStringContainsString('class ' . $class, $e->getMessage());
    }

    public function testClassExistsReturnsInstanceUsingPathAndClassProvided()
    {
        $path = __FILE__;
        $class = __CLASS__;
        $e = CreateMiddlewareException::classExists($path, $class);
        self::assertInstanceOf(CreateMiddlewareException::class, $e);
        self::assertStringContainsString('directory ' . $path, $e->getMessage());
        self::assertStringContainsString('Class ' . $class, $e->getMessage());
    }
}
