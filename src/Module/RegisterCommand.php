<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Mezzio\Tooling\Composer\ComposerPackageFactoryInterface;
use Mezzio\Tooling\Composer\ComposerPackageInterface;
use Mezzio\Tooling\Composer\ComposerProcessFactoryInterface;
use Mezzio\Tooling\ConfigInjector\ConfigAggregatorInjector;
use Mezzio\Tooling\ConfigInjector\InjectorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_dir;
use function sprintf;

final class RegisterCommand extends Command
{
    /**
     * @var string
     */
    public const HELP = <<<'EOT'
        Register an existing middleware module with the application, by:
        
        - Ensuring a PSR-4 autoloader entry is present in composer.json, and the
          autoloading rules have been generated.
        - Ensuring the ConfigProvider class for the module is registered with the
          application configuration.

        If the --exact-path option is provided to the command, that value will
        be treated as the fully qualified path to register for autoloading the
        module.

        EOT;

    /**
     * @var string
     */
    public const HELP_ARG_MODULE = 'The module to register with the application';

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:module:register';

    private ComposerPackageInterface $package;

    private InjectorInterface $injector;

    public function __construct(
        private string $projectRoot,
        ComposerPackageFactoryInterface $packageFactory,
        private ComposerProcessFactoryInterface $processFactory,
        ?InjectorInterface $configInjector = null
    ) {
        $this->package  = $packageFactory->loadPackage($projectRoot);
        $this->injector = $configInjector ?? new ConfigAggregatorInjector($this->projectRoot);

        parent::__construct();
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this->setDescription('Register a middleware module with the application');
        $this->setHelp(self::HELP);
        $this->addOption(
            'exact-path',
            'x',
            InputOption::VALUE_REQUIRED,
            'If provided, this will be the exact path registered for the module',
            null
        );
        CommandCommonOptions::addDefaultOptionsAndArguments($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module      = $input->getArgument('module');
        $composer    = $input->getOption('composer') ?: 'composer';
        $modulesPath = CommandCommonOptions::getModulesPath($input);
        $exactPath   = $input->getOption('exact-path');

        $configProvider = sprintf('%s\ConfigProvider', $module);
        if (! $this->injector->isRegistered($configProvider)) {
            $this->injector->inject(
                $configProvider,
                InjectorInterface::TYPE_CONFIG_PROVIDER
            );
        }

        $modulePath = $exactPath ?: $this->detectModuleSourcePath($modulesPath, $module);

        // If no updates are made to autoloading, no need to update the autoloader.
        // Additionally, since this command registers the module with the
        // application, it can NEVER be a dev autoloading rule.
        if (! $this->package->addPsr4AutoloadRule($module, $modulePath, false)) {
            $output->writeln(sprintf('Registered config provider for module %s', $module));
            return 0;
        }

        $result = $this->processFactory->createProcess([$composer, 'dump-autoload'])->run();
        if (! $result->isSuccessful()) {
            $output->writeln('<error>Unable to dump autoloader rules</error>');
            $output->writeln(sprintf('Command "%s dump-autoload": %s', $composer, $result->getErrorOutput()));
            return 1;
        }

        $output->writeln(sprintf('Registered config provider and autoloading rules for module %s', $module));
        return 0;
    }

    private function detectModuleSourcePath(string $sourcePath, string $module): string
    {
        $modulePath    = sprintf('%s/%s', $sourcePath, $module);
        $canonicalPath = sprintf('%s/%s', $this->projectRoot, $modulePath);

        if (! is_dir($canonicalPath)) {
            throw new RuntimeException(sprintf('Cannot register module; directory "%s" does not exist', $modulePath));
        }

        $nestedSourcePath = $modulePath . '/src';
        $canonicalPath    = sprintf('%s/%s', $this->projectRoot, $nestedSourcePath);

        return is_dir($canonicalPath) ? $nestedSourcePath : $modulePath;
    }
}
