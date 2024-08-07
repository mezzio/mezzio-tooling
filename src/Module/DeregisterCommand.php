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
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function sprintf;

final class DeregisterCommand extends Command
{
    /**
     * @var string
     */
    public const HELP = <<<'EOT'
        Deregister an existing middleware module from the application, by:

        - Removing the associated PSR-4 autoloader entry from composer.json, and
          regenerating autoloading rules.
        - Removing the associated ConfigProvider class for the module from the
          application configuration.
        EOT;

    /**
     * @var string
     */
    public const HELP_ARG_MODULE = 'The module to register with the application';

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:module:deregister';

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
        $this->setDescription('Deregister a middleware module from the application');
        $this->setHelp(self::HELP);
        CommandCommonOptions::addDefaultOptionsAndArguments($this);
    }

    /**
     * Deregister module.
     *
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module   = $input->getArgument('module');
        $composer = $input->getOption('composer') ?: 'composer';

        $configProvider = sprintf('%s\ConfigProvider', $module);
        assert($configProvider !== '');

        if ($this->injector->isRegistered($configProvider)) {
            $this->injector->remove($configProvider);
        }

        // If no updates are made to autoloading, no need to update the autoloader.
        // Additionally, since this command deregisters the module with the
        // application, it can NEVER be a dev autoloading rule.
        if (! $this->package->removePsr4AutoloadRule($module, false)) {
            $output->writeln(sprintf('Removed config provider for module %s', $module));
            return 0;
        }

        $result = $this->processFactory->createProcess([$composer, 'dump-autoload'])->run();
        if (! $result->isSuccessful()) {
            $output->writeln('<error>Unable to dump autoloader rules</error>');
            $output->writeln(sprintf('Command "%s dump-autoload": %s', $composer, $result->getErrorOutput()));
            return 1;
        }

        $output->writeln(sprintf('Removed config provider and autoloading rules for module %s', $module));
        return 0;
    }
}
