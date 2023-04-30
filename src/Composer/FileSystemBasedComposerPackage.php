<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Composer;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_readable;
use function is_writable;
use function json_decode;
use function json_encode;
use function rtrim;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class FileSystemBasedComposerPackage implements ComposerPackageInterface
{
    private string $composerFile;

    public function __construct(string $projectRoot)
    {
        $this->composerFile = sprintf('%s/composer.json', $projectRoot);
    }

    public function addPsr4AutoloadRule(string $namespace, string $path, bool $isDev = false): bool
    {
        $namespace = rtrim($namespace, '\\') . '\\';
        $path      = rtrim($path, '/\\') . '/';
        $key       = $isDev ? 'autoload-dev' : 'autoload';
        $package   = $this->parse();

        if (isset($package[$key]['psr-4'][$namespace])) {
            // Nothing to do; a rule already exists
            return false;
        }

        $package[$key]['psr-4'][$namespace] = $path;
        $this->write($package);

        return true;
    }

    public function removePsr4AutoloadRule(string $namespace, bool $isDev = false): bool
    {
        $namespace = rtrim($namespace, '\\') . '\\';
        $key       = $isDev ? 'autoload-dev' : 'autoload';
        $package   = $this->parse();

        if (! isset($package[$key]['psr-4'][$namespace])) {
            // Nothing to do; no rule exists
            return false;
        }

        unset($package[$key]['psr-4'][$namespace]);
        $this->write($package);

        return true;
    }

    /**
     * @return mixed[]
     */
    private function parse(): array
    {
        if (! file_exists($this->composerFile)) {
            return [];
        }

        if (! is_readable($this->composerFile)) {
            throw ComposerFileException::dueToPermissions($this->composerFile);
        }

        $contents = file_get_contents($this->composerFile);
        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function write(array $package): void
    {
        $path = dirname($this->composerFile);
        if (! is_dir($path)) {
            throw ComposerFileException::dueToMissingDirectory($path);
        }

        if (! is_writable($path)) {
            throw ComposerFileException::dueToDirectoryPermissions($path);
        }

        $contents = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->composerFile, $contents . "\n");
    }
}
