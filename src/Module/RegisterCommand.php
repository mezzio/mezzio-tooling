<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Laminas\ComponentInstaller\Injector\ConfigAggregatorInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Laminas\ComposerAutoloading\Command\Enable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterCommand extends Command
{
    public const HELP = <<< 'EOT'
Register an existing middleware module with the application, by:

- Ensuring a PSR-4 autoloader entry is present in composer.json, and the
  autoloading rules have been generated.
- Ensuring the ConfigProvider class for the module is registered with the
  application configuration.
EOT;

    public const HELP_ARG_MODULE = 'The module to register with the application';

    /**
     * Configure command.
     */
    protected function configure() : void
    {
        $this->setDescription('Register a middleware module with the application');
        $this->setHelp(self::HELP);
        CommandCommonOptions::addDefaultOptionsAndArguments($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $module = $input->getArgument('module');
        $composer = $input->getOption('composer') ?: 'composer';
        $modulesPath = CommandCommonOptions::getModulesPath($input);

        $injector = new ConfigAggregatorInjector(getcwd());
        $configProvider = sprintf('%s\ConfigProvider', $module);
        if (! $injector->isRegistered($configProvider)) {
            $injector->inject(
                $configProvider,
                InjectorInterface::TYPE_CONFIG_PROVIDER
            );
        }

        $enable = new Enable(getcwd(), $modulesPath, $composer);
        $enable->setMoveModuleClass(false);
        $enable->process($module);

        $output->writeln(sprintf('Registered autoloading rules and added configuration entry for module %s', $module));
        return 0;
    }
}
