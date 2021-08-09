<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Mezzio\Tooling\TemplateResolutionTrait;

use function file_exists;
use function file_put_contents;
use function ltrim;
use function rtrim;
use function sprintf;
use function strlen;
use function strpos;
use function substr;

final class Create
{
    use TemplateResolutionTrait;

    public const TEMPLATE_CONFIG_PROVIDER_FLAT = <<<'EOT'
        <?php
        
        declare(strict_types=1);
        
        namespace %1$s;
        
        /**
         * The configuration provider for the %1$s module
         *
         * @see https://docs.laminas.dev/laminas-component-installer/
         */
        class ConfigProvider
        {
            /**
             * Returns the configuration array
             *
             * To add a bit of a structure, each section is defined in a separate
             * method which returns an array with its configuration.
             */
            public function __invoke() : array
            {
                return [
                    'dependencies' => $this->getDependencies(),
                ];
            }
        
            /**
             * Returns the container dependencies
             */
            public function getDependencies() : array
            {
                return [
                    'invokables' => [
                    ],
                    'factories'  => [
                    ],%2$s
                ];
            }
        }
        
        EOT;

    public const TEMPLATE_CONFIG_PROVIDER_RECOMMENDED = <<<'EOT'
        <?php
        
        declare(strict_types=1);
        
        namespace %1$s;
        
        /**
         * The configuration provider for the %1$s module
         *
         * @see https://docs.laminas.dev/laminas-component-installer/
         */
        class ConfigProvider
        {
            /**
             * Returns the configuration array
             *
             * To add a bit of a structure, each section is defined in a separate
             * method which returns an array with its configuration.
             */
            public function __invoke() : array
            {
                return [
                    'dependencies' => $this->getDependencies(),
                    'templates'    => $this->getTemplates(),
                ];
            }
        
            /**
             * Returns the container dependencies
             */
            public function getDependencies() : array
            {
                return [
                    'invokables' => [
                    ],
                    'factories'  => [
                    ],%3$s
                ];
            }
        
            /**
             * Returns the templates configuration
             */
            public function getTemplates() : array
            {
                return [
                    'paths' => [
                        '%2$s'    => [__DIR__ . '/../templates/'],
                    ],
                ];
            }
        }
        
        EOT;

    public const TEMPLATE_ROUTE_DELEGATOR_CONFIG = <<<'EOT'
        
                    'delegators' => [
                        \Mezzio\Application::class => [
                            RoutesDelegator::class,
                        ],
                    ],
        EOT;

    public const TEMPLATE_ROUTE_DELEGATOR = <<<'EOT'
        <?php
        
        declare(strict_types=1);
        
        namespace %1$s;

        use Psr\Container\ContainerInterface;
        use Mezzio\Application;
        
        /**
         * Routes specific to the %1$s module
         */
        class RoutesDelegator
        {
            public function __invoke(ContainerInterface $container, $serviceName, callable $callback): Application
            {
                /** @var Application $app */
                $app = $callback();

                // Setup routes here:
                //   $app->get('/some/path', Handler\SomeHandler::class, 'path');
                //   $app->post('/some/form', Handler\FormHandler::class, 'form');
                // etc.

                return $app;
            }
        }

        EOT;

    /** @var bool */
    private $useFlatStructure;

    public function __construct(bool $useFlatStructure = false)
    {
        $this->useFlatStructure = $useFlatStructure;
    }

    /**
     * Create source tree for the mezzio module.
     */
    public function process(
        string $moduleName,
        string $modulesPath,
        string $projectDir,
        bool $withRouteDelegator = false,
        string $parentNamespace = ''
    ): ModuleMetadata {
        $moduleRootPath    = sprintf('%s/%s/%s', $projectDir, $modulesPath, $moduleName);
        $moduleSourcePath  = $this->createDirectoryStructure($moduleRootPath, $moduleName);
        $templateNamespace = $this->normalizeTemplateIdentifier($moduleName);
        $moduleName        = $parentNamespace === ''
            ? $moduleName
            : sprintf('%s\\%s', rtrim($parentNamespace, '\\'), $moduleName);

        $this->createConfigProvider($moduleSourcePath, $moduleName, $templateNamespace, $withRouteDelegator);

        if ($withRouteDelegator) {
            $this->createRouteDelegator($moduleSourcePath, $moduleName);
        }

        return new ModuleMetadata(
            $moduleName,
            $this->stripProjectRootFromPath($projectDir, $moduleRootPath),
            $this->stripProjectRootFromPath($projectDir, $moduleSourcePath)
        );
    }

    /**
     * Creates directory structure for new mezzio module.
     *
     * @throws RuntimeException
     */
    private function createDirectoryStructure(string $modulePath, string $moduleName): string
    {
        if (file_exists($modulePath)) {
            throw new RuntimeException(sprintf(
                'Module "%s" already exists',
                $moduleName
            ));
        }

        // Not importing mkdir to allow testing of this path
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (! mkdir($modulePath)) {
            throw new RuntimeException(sprintf(
                'Module directory "%s" cannot be created',
                $modulePath
            ));
        }

        if ($this->useFlatStructure) {
            // Flat structure requested
            return $modulePath;
        }

        // Not importing mkdir to allow testing of this path
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (! mkdir($modulePath . '/src')) {
            throw new RuntimeException(sprintf(
                'Module source directory "%s/src" cannot be created',
                $modulePath
            ));
        }

        $templatePath = sprintf('%s/templates', $modulePath);

        // Not importing mkdir to allow testing of this path
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (! mkdir($templatePath)) {
            throw new RuntimeException(sprintf(
                'Module templates directory "%s" cannot be created',
                $templatePath
            ));
        }

        return sprintf('%s/src', $modulePath);
    }

    /**
     * Creates ConfigProvider for new mezzio module.
     */
    private function createConfigProvider(
        string $sourcePath,
        string $moduleName,
        string $templateNamespace,
        bool $withRouteDelegator
    ): void {
        if ($this->useFlatStructure) {
            file_put_contents(
                sprintf('%s/ConfigProvider.php', $sourcePath),
                sprintf(
                    self::TEMPLATE_CONFIG_PROVIDER_FLAT,
                    $moduleName,
                    $withRouteDelegator ? self::TEMPLATE_ROUTE_DELEGATOR_CONFIG : ''
                )
            );

            return;
        }

        file_put_contents(
            sprintf('%s/ConfigProvider.php', $sourcePath),
            sprintf(
                self::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED,
                $moduleName,
                $templateNamespace,
                $withRouteDelegator ? self::TEMPLATE_ROUTE_DELEGATOR_CONFIG : ''
            )
        );
    }

    private function createRouteDelegator(string $sourcePath, string $moduleName): void
    {
        $classFileContents = sprintf(
            self::TEMPLATE_ROUTE_DELEGATOR,
            $moduleName
        );

        $filename = sprintf('%s/RoutesDelegator.php', $sourcePath);

        file_put_contents($filename, $classFileContents);
    }

    private function stripProjectRootFromPath(string $projectRoot, string $path): string
    {
        if (0 !== strpos($path, $projectRoot)) {
            return $path;
        }

        $relativePath = substr($path, strlen($projectRoot));
        return ltrim($relativePath, '/\\');
    }
}
