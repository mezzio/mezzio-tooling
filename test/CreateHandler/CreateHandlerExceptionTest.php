<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Tooling\CreateHandler;

use Mezzio\Tooling\CreateHandler\CreateHandlerException;
use PHPUnit\Framework\TestCase;

class CreateHandlerExceptionTest extends TestCase
{
    public function testMissingComposerJsonReturnsInstance()
    {
        $e = CreateHandlerException::missingComposerJson();
        $this->assertInstanceOf(CreateHandlerException::class, $e);
        $this->assertStringContainsString('Could not find a composer.json', $e->getMessage());
    }

    public function testMissingComposerAutoloadersReturnsInstance()
    {
        $e = CreateHandlerException::missingComposerAutoloaders();
        $this->assertInstanceOf(CreateHandlerException::class, $e);
        $this->assertStringContainsString('PSR-4 autoloaders', $e->getMessage());
    }

    public function testInvalidComposerJsonReturnsInstanceWithErrorMessage()
    {
        $error = 'Invalid or malformed JSON';
        $e = CreateHandlerException::invalidComposerJson($error);
        $this->assertInstanceOf(CreateHandlerException::class, $e);
        $this->assertStringContainsString('Unable to parse composer.json: ', $e->getMessage());
        $this->assertStringContainsString($error, $e->getMessage());
    }

    public function testAutoloaderNotFoundReturnsInstanceUsingClassNameProvided()
    {
        $expected = __CLASS__;
        $e = CreateHandlerException::autoloaderNotFound($expected);
        $this->assertInstanceOf(CreateHandlerException::class, $e);
        $this->assertStringContainsString('match ' . $expected, $e->getMessage());
    }

    public function testUnableToCreatePathReturnsInstanceUsingPathAndClassProvided()
    {
        $path = __FILE__;
        $class = __CLASS__;
        $e = CreateHandlerException::unableToCreatePath($path, $class);
        $this->assertInstanceOf(CreateHandlerException::class, $e);
        $this->assertStringContainsString('directory ' . $path, $e->getMessage());
        $this->assertStringContainsString('class ' . $class, $e->getMessage());
    }

    public function testClassExistsReturnsInstanceUsingPathAndClassProvided()
    {
        $path = __FILE__;
        $class = __CLASS__;
        $e = CreateHandlerException::classExists($path, $class);
        $this->assertInstanceOf(CreateHandlerException::class, $e);
        $this->assertStringContainsString('directory ' . $path, $e->getMessage());
        $this->assertStringContainsString('Class ' . $class, $e->getMessage());
    }
}
