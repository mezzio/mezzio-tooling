<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateHandler;

use Mezzio\Tooling\CreateHandler\CreateHandlerException;
use PHPUnit\Framework\TestCase;

class CreateHandlerExceptionTest extends TestCase
{
    public function testMissingComposerJsonReturnsInstance()
    {
        $e = CreateHandlerException::missingComposerJson();
        self::assertInstanceOf(CreateHandlerException::class, $e);
        self::assertStringContainsString('Could not find a composer.json', $e->getMessage());
    }

    public function testMissingComposerAutoloadersReturnsInstance()
    {
        $e = CreateHandlerException::missingComposerAutoloaders();
        self::assertInstanceOf(CreateHandlerException::class, $e);
        self::assertStringContainsString('PSR-4 autoloaders', $e->getMessage());
    }

    public function testInvalidComposerJsonReturnsInstanceWithErrorMessage()
    {
        $error = 'Invalid or malformed JSON';
        $e     = CreateHandlerException::invalidComposerJson($error);
        self::assertInstanceOf(CreateHandlerException::class, $e);
        self::assertStringContainsString('Unable to parse composer.json: ', $e->getMessage());
        self::assertStringContainsString($error, $e->getMessage());
    }

    public function testAutoloaderNotFoundReturnsInstanceUsingClassNameProvided()
    {
        $expected = self::class;
        $e        = CreateHandlerException::autoloaderNotFound($expected);
        self::assertInstanceOf(CreateHandlerException::class, $e);
        self::assertStringContainsString('match ' . $expected, $e->getMessage());
    }

    public function testUnableToCreatePathReturnsInstanceUsingPathAndClassProvided()
    {
        $path  = __FILE__;
        $class = self::class;
        $e     = CreateHandlerException::unableToCreatePath($path, $class);
        self::assertInstanceOf(CreateHandlerException::class, $e);
        self::assertStringContainsString('directory ' . $path, $e->getMessage());
        self::assertStringContainsString('class ' . $class, $e->getMessage());
    }

    public function testClassExistsReturnsInstanceUsingPathAndClassProvided()
    {
        $path  = __FILE__;
        $class = self::class;
        $e     = CreateHandlerException::classExists($path, $class);
        self::assertInstanceOf(CreateHandlerException::class, $e);
        self::assertStringContainsString('directory ' . $path, $e->getMessage());
        self::assertStringContainsString('Class ' . $class, $e->getMessage());
    }
}
