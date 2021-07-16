<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Module;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

class CreateCommand extends Command
{
    public const HELP = <<<'EOT'
        Create a new middleware module for the application.
        
        - Creates an appropriate module structure containing a source code tree,
          templates tree, and ConfigProvider class.
        - Adds a PSR-4 autoloader to composer.json, and regenerates the
          autoloading rules.
        - Registers the ConfigProvider class for the module with the application
          configuration.
        EOT;

    public const HELP_ARG_MODULE = 'The module to create and register with the application.';

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:module:create';

    /** @var array|ArrayAccess */
    private $config;

    /** @var string */
    private $projectRoot;

    /** @param array|ArrayAccess $config */
    public function __construct($config, string $projectRoot)
    {
        $this->config      = $config;
        $this->projectRoot = $projectRoot;

        parent::__construct();
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this->setDescription('Create and register a middleware module with the application');
        $this->setHelp(self::HELP);
        CommandCommonOptions::addDefaultOptionsAndArguments($this);
    }

    /**
     * Execute command
     *
     * Executes command by creating new module tree, and then executing
     * the "register" command with the same module name.
     *
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module      = $input->getArgument('module');
        $composer    = $input->getOption('composer') ?: 'composer';
        $modulesPath = CommandCommonOptions::getModulesPath($input, $this->config);

        $creation = new Create();
        $message  = $creation->process($module, $modulesPath, $this->projectRoot);
        $output->writeln(sprintf('<info>%s</info>', $message));

        $registerCommand = 'mezzio:module:register';
        $register        = $this->getApplication()->find($registerCommand);
        return $register->run(new ArrayInput([
            'command'        => $registerCommand,
            'module'         => $module,
            '--composer'     => $composer,
            '--modules-path' => $modulesPath,
        ]), $output);
    }
}
