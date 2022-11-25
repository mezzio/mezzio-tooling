<?php

declare(strict_types=1);

namespace MezzioTest\Tooling\MigrateMiddlewareToRequestHandler;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

use function file_get_contents;
use function preg_match;
use function realpath;
use function strtr;

use const DIRECTORY_SEPARATOR;

trait ProjectSetupTrait
{
    private function setupSrcDir(string|vfsStreamDirectory $dir): void
    {
        $base = realpath(__DIR__ . '/TestAsset') . DIRECTORY_SEPARATOR;
        $rdi  = new RecursiveDirectoryIterator($base . 'src');
        $rii  = new RecursiveIteratorIterator($rdi);

        foreach ($rii as $file) {
            if (! self::isPhpFile($file)) {
                continue;
            }

            $filename = $file->getRealPath();
            $contents = file_get_contents($filename);
            $name     = strtr($filename, [$base => '', DIRECTORY_SEPARATOR => '/']);
            vfsStream::newFile($name)
                ->at($dir)
                ->setContent($contents);
        }
    }

    private static function isPhpFile(SplFileInfo $file): bool
    {
        if (! $file->isFile()) {
            return false;
        }

        if ($file->getExtension() !== 'php') {
            return false;
        }

        if (! $file->isReadable()) {
            return false;
        }

        return $file->isWritable();
    }

    /** @return OutputInterface&MockObject */
    private function setupConsoleHelper(): OutputInterface
    {
        $console = $this->createMock(OutputInterface::class);

        $console
            ->expects(self::atLeast(5))
            ->method('writeln')
            ->with(self::logicalOr(
                self::callback(static fn(string $arg): bool => preg_match('#Updating .*src/MultilineMiddleware\.php#', $arg) !== false),
                self::callback(static fn(string $arg): bool => preg_match('#Updating .*src/MultipleInterfacesMiddleware\.php#', $arg) !== false),
                self::callback(static fn(string $arg): bool => preg_match('#Updating .*src/MyActionWithAliases\.php#', $arg) !== false),
                self::callback(static fn(string $arg): bool => preg_match('#Updating .*src/MyMiddleware\.php#', $arg) !== false),
                self::callback(static fn(string $arg): bool => preg_match('#Updating .*src/MyMiddlewareWithHandler\.php#', $arg) !== false),
            ));

        return $console;
    }

    public static function assertExpected(string $dir): void
    {
        $base = $dir;
        $rdi  = new RecursiveDirectoryIterator($dir);
        $rii  = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if (! self::isPhpFile($file)) {
                continue;
            }

            $filename = $file->getPathname();
            $content  = file_get_contents($filename);
            $name     = strtr(
                $filename,
                [
                    $base . '/src'      => __DIR__ . '/TestAsset/expected',
                    DIRECTORY_SEPARATOR => '/',
                ]
            );

            Assert::assertStringEqualsFile($name, $content);
        }
    }
}
