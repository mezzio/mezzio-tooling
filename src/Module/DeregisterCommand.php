<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Laminas\ComposerAutoloading\Command\Disable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeregisterCommand extends Command
{
    public const HELP = <<< 'EOT'
        Deregister an existing middleware module from the application, by:
        
        - Removing the associated PSR-4 autoloader entry from composer.json, and
          regenerating autoloading rules.
        - Removing the associated ConfigProvider class for the module from the
          application configuration.
        EOT;

    public const HELP_ARG_MODULE = 'The module to register with the application';

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:module:deregister';

    /** @var string */
    private $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;

        parent::__construct();
    }

    /**
     * Configure command.
     */
    protected function configure() : void
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
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $module = $input->getArgument('module');
        $composer = $input->getOption('composer') ?: 'composer';
        $modulesPath = CommandCommonOptions::getModulesPath($input);

        $injector = new ConfigAggregatorInjector($this->projectRoot);
        $configProvider = sprintf('%s\ConfigProvider', $module);
        if ($injector->isRegistered($configProvider)) {
            $injector->remove($configProvider);
        }

        $disable = new Disable($this->projectRoot, $modulesPath, $composer);
        $disable->process($module);

        $output->writeln(sprintf('Removed autoloading rules and configuration entries for module %s', $module));
        return 0;
    }
}
