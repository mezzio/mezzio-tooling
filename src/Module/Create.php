<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Mezzio\Tooling\TemplateResolutionTrait;

use function file_exists;
use function file_put_contents;
use function sprintf;

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
                    ],
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
                    ],
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

    /** @var bool */
    private $useFlatStructure;

    public function __construct(bool $useFlatStructure = false)
    {
        $this->useFlatStructure = $useFlatStructure;
    }

    /**
     * Create source tree for the mezzio module.
     */
    public function process(string $moduleName, string $modulesPath, string $projectDir): ModuleMetadata
    {
        $moduleRootPath   = sprintf('%s/%s/%s', $projectDir, $modulesPath, $moduleName);
        $moduleSourcePath = $this->createDirectoryStructure($moduleRootPath, $moduleName);
        $this->createConfigProvider($moduleSourcePath, $moduleName);

        return new ModuleMetadata(
            $moduleName,
            $moduleRootPath,
            $moduleSourcePath
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
    private function createConfigProvider(string $sourcePath, string $moduleName): void
    {
        if ($this->useFlatStructure) {
            file_put_contents(
                sprintf('%s/ConfigProvider.php', $sourcePath),
                sprintf(
                    self::TEMPLATE_CONFIG_PROVIDER_FLAT,
                    $moduleName
                )
            );

            return;
        }

        file_put_contents(
            sprintf('%s/ConfigProvider.php', $sourcePath),
            sprintf(
                self::TEMPLATE_CONFIG_PROVIDER_RECOMMENDED,
                $moduleName,
                $this->normalizeTemplateIdentifier($moduleName)
            )
        );
    }
}
