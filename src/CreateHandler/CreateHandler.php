<?php

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

use JsonException;

use function array_merge;
use function array_pop;
use function array_slice;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function implode;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function mkdir;
use function realpath;
use function sprintf;
use function str_replace;
use function strpos;
use function trim;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/**
 * Create a request handler
 *
 * Creates a request handler class file for a given class in a given project root.
 */
final class CreateHandler extends ClassSkeletons
{
    /**
     * Path to root of project.
     *
     * @var string
     */
    private $projectRoot;

    /**
     * Class skeleton template to use.
     *
     * @var string
     */
    private $skeleton;

    public function __construct(string $classSkeleton = self::CLASS_SKELETON, ?string $projectRoot = null)
    {
        $this->skeleton    = $classSkeleton;
        $this->projectRoot = $projectRoot ?: realpath(getcwd());
    }

    /**
     * @param array $additionalSubstitutions An associative array where the keys
     *     are the substitution strings to match, and the values are the associated
     *     values to substitute.
     * @throws CreateHandlerException
     */
    public function process(
        string $class,
        array $additionalSubstitutions = []
    ): string {
        $path = $this->getClassPath($class);

        [$namespace, $class] = $this->getNamespaceAndClass($class);

        $substitutions = array_merge(
            [
                '%namespace%' => $namespace,
                '%class%'     => $class,
            ],
            $additionalSubstitutions
        );

        $content = $this->skeleton;
        foreach ($substitutions as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        if (is_file($path)) {
            throw CreateHandlerException::classExists($path, $class);
        }

        file_put_contents($path, $content);
        return $path;
    }

    /**
     * @throws CreateHandlerException
     */
    private function getClassPath(string $class): string
    {
        $autoloaders        = $this->getComposerAutoloaders();
        [$namespace, $path] = $this->discoverNamespaceAndPath($class, $autoloaders);

        // Absolute path to namespace
        $path = implode([$this->projectRoot, DIRECTORY_SEPARATOR, $path]);

        $parts     = explode('\\', $class);
        $className = array_pop($parts);

        // Create absolute path to subnamespace, if required
        $nsParts    = explode('\\', trim($namespace, '\\'));
        $subNsParts = array_slice($parts, count($nsParts));

        if (0 < count($subNsParts)) {
            $subNsPath = implode(DIRECTORY_SEPARATOR, $subNsParts);
            $path      = implode([$path, DIRECTORY_SEPARATOR, $subNsPath]);
        }

        // Create path if it does not exist
        if (! is_dir($path)) {
            if (false === mkdir($path, 0755, true)) {
                throw CreateHandlerException::unableToCreatePath($path, $class);
            }
        }

        return $path . DIRECTORY_SEPARATOR . $className . '.php';
    }

    /**
     * @return array Associative array of namespace/path pairs
     * @throws CreateHandlerException
     */
    private function getComposerAutoloaders(): array
    {
        $composerPath = sprintf('%s/composer.json', $this->projectRoot);
        if (! file_exists($composerPath)) {
            throw CreateHandlerException::missingComposerJson();
        }

        try {
            $composer = json_decode(file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw CreateHandlerException::invalidComposerJson($e->getMessage());
        }

        if (! isset($composer['autoload']['psr-4'])) {
            throw CreateHandlerException::missingComposerAutoloaders();
        }

        if (! is_array($composer['autoload']['psr-4'])) {
            throw CreateHandlerException::missingComposerAutoloaders();
        }

        return $composer['autoload']['psr-4'];
    }

    /**
     * @return array [namespace, path]
     * @throws CreateHandlerException
     */
    private function discoverNamespaceAndPath(string $class, array $autoloaders): array
    {
        foreach ($autoloaders as $namespace => $path) {
            if (0 === strpos($class, $namespace)) {
                $path = trim(
                    str_replace(
                        ['/', '\\'],
                        DIRECTORY_SEPARATOR,
                        $path
                    ),
                    DIRECTORY_SEPARATOR
                );
                return [$namespace, $path];
            }
        }

        throw CreateHandlerException::autoloaderNotFound($class);
    }

    /**
     * @return array [namespace, class]
     */
    private function getNamespaceAndClass(string $class): array
    {
        $parts     = explode('\\', $class);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);
        return [$namespace, $className];
    }
}
